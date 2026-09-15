<?php
/** Cache unit tests. No database. Run: php tests/cache_test.php */
require_once dirname(__DIR__).'/includes/Cache.php';

$assertions = 0;
function check(bool $condition, string $message): void {
    global $assertions;
    if (!$condition) { throw new RuntimeException($message); }
    $assertions++;
}

$tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phpretro-cache-'.bin2hex(random_bytes(4));
mkdir($tmp, 0775, true);

$cache = new FileCache($tmp, 'test');
check($cache->get('missing') === null, 'file miss returns null');
check($cache->set('settings:all', ['site_name' => 'Hotel', 'site_language' => 'en']) === true, 'file set array');
check($cache->get('settings:all') === ['site_name' => 'Hotel', 'site_language' => 'en'], 'file get returns the array');
check($cache->set('locale:en:landing.login', ['hotel.is' => 'Hotel is']) === true, 'file set locale');
check($cache->get('locale:en:landing.login')['hotel.is'] === 'Hotel is', 'file get locale string');
check($cache->delete('locale:en:landing.login') === true, 'file delete');
check($cache->get('locale:en:landing.login') === null, 'file delete is a miss');
check($cache->clear() === true, 'file clear');
check($cache->get('settings:all') === null, 'file clear drops remaining keys');

$cache->set('ttl-alive', 'ok', 60);
check($cache->get('ttl-alive') === 'ok', 'file ttl in the future is a hit');

final class FakeRedis
{
    public array $data = [];
    public function get($key) { return $this->data[$key] ?? false; }
    public function set($key, $value) { $this->data[$key] = $value; return true; }
    public function setex($key, $ttl, $value) { $this->data[$key] = $value; return true; }
    public function del($key) { unset($this->data[$key]); return 1; }
    public function keys($pattern) {
        $prefix = rtrim(str_replace('*', '', (string) $pattern), ':');
        $out = [];
        foreach (array_keys($this->data) as $stored) {
            if (str_starts_with((string) $stored, $prefix)) { $out[] = $stored; }
        }
        return $out;
    }
}

$redis = new RedisCache(new FakeRedis(), 'phpretro');
check($redis->get('settings:all') === null, 'redis miss returns null');
check($redis->set('settings:all', ['site_closed' => '0']) === true, 'redis set');
check($redis->get('settings:all') === ['site_closed' => '0'], 'redis get');
check($redis->set('hotel:status', ['online' => 'online'], 1800) === true, 'redis setex path');
check($redis->get('hotel:status')['online'] === 'online', 'redis ttl value stored');
$redis->delete('settings:all');
check($redis->get('settings:all') === null, 'redis delete');
$redis->clear();
check($redis->get('hotel:status') === null, 'redis clear');

putenv('CACHE_DRIVER=file');
putenv('CACHE_PATH='.$tmp);
putenv('CACHE_PREFIX=phpretro');
CacheFactory::reset();
$fromFactory = CacheFactory::instance();
check($fromFactory instanceof FileCache, 'factory default is FileCache');
check(CacheFactory::activeDriver() === 'file', 'active driver is file');
$fromFactory->set('factory', 'yes');
check($fromFactory->get('factory') === 'yes', 'factory instance writes');

putenv('CACHE_DRIVER=redis');
CacheFactory::reset();
$fallback = CacheFactory::create();
check($fallback instanceof FileCache, 'redis without a client falls back to FileCache');
check(CacheFactory::activeDriver() === 'file', 'fallback driver is file');

$config = (string) file_get_contents(dirname(__DIR__).'/includes/config.php');
check(!preg_match('/password\s*=\s*[\'"][^\'"]+[\'"]/i', $config), 'config.php has no literal password assignment');
check(!preg_match('/secret\s*=\s*[\'"][^\'"]+[\'"]/i', $config), 'config.php has no literal secret assignment');
check(str_contains($config, 'getenv') || str_contains($config, '$_ENV'), 'config.php only injects getenv/$_ENV');
check(!str_contains($config, 'DB_PASS="'), 'config.php does not embed DB_PASS');

$example = (string) file_get_contents(dirname(__DIR__).'/.env.example');
foreach (['DB_DSN', 'DB_USER', 'DB_PASS', 'MAIL_FROM', 'POLARIS_CMS_SECRET', 'CACHE_DRIVER', 'REDIS_PASSWORD'] as $name) {
    check(str_contains($example, $name), '.env.example documents '.$name);
}

foreach (glob($tmp.DIRECTORY_SEPARATOR.'*') ?: [] as $file) { @unlink($file); }
@rmdir($tmp);

echo 'PASS: '.$assertions.' assertions; FileCache/RedisCache/factory/env.'."\n";
