<?php
/**
 * Cache layer (Phase 9).
 *
 * Driver is selected by CACHE_DRIVER: "file" (default) or "redis".
 * Redis uses ext-redis when loaded, otherwise Predis\Client if present.
 * If Redis is requested but neither client is available, FileCache is used.
 *
 * Values are JSON. Do not store objects that need PHP serialize().
 */
interface Cache
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, ?int $ttlSeconds = null): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
}

final class FileCache implements Cache
{
    public function __construct(private string $directory, private string $prefix = 'phpretro')
    {
        if ($this->directory === '') {
            throw new InvalidArgumentException('FileCache directory is empty.');
        }
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException('FileCache cannot create '.$this->directory);
        }
    }

    public function get(string $key): mixed
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !array_key_exists('v', $payload)) {
            return null;
        }
        $expires = (int) ($payload['e'] ?? 0);
        if ($expires > 0 && $expires < time()) {
            @unlink($path);
            return null;
        }
        return $payload['v'];
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        $payload = json_encode(
            ['v' => $value, 'e' => ($ttlSeconds !== null && $ttlSeconds > 0) ? time() + $ttlSeconds : 0],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (!is_string($payload)) {
            return false;
        }
        $path = $this->path($key);
        $tmp = $path.'.tmp.'.bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
            return false;
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }

    public function delete(string $key): bool
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return true;
        }
        return @unlink($path);
    }

    public function clear(): bool
    {
        $ok = true;
        foreach (glob($this->directory.DIRECTORY_SEPARATOR.'*.cache') ?: [] as $file) {
            if (!@unlink($file)) {
                $ok = false;
            }
        }
        return $ok;
    }

    private function path(string $key): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.hash('sha256', $this->prefix."\0".$key).'.cache';
    }
}

final class RedisCache implements Cache
{
    public function __construct(private object $redis, private string $prefix = 'phpretro')
    {
    }

    public function get(string $key): mixed
    {
        $raw = $this->redis->get($this->namespaced($key));
        if ($raw === false || $raw === null || $raw === '') {
            return null;
        }
        $payload = json_decode((string) $raw, true);
        if (!is_array($payload) || !array_key_exists('v', $payload)) {
            return null;
        }
        return $payload['v'];
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        $payload = json_encode(['v' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            return false;
        }
        $namespaced = $this->namespaced($key);
        if ($ttlSeconds !== null && $ttlSeconds > 0 && method_exists($this->redis, 'setex')) {
            return (bool) $this->redis->setex($namespaced, $ttlSeconds, $payload);
        }
        return (bool) $this->redis->set($namespaced, $payload);
    }

    public function delete(string $key): bool
    {
        $this->redis->del($this->namespaced($key));
        return true;
    }

    public function clear(): bool
    {
        $pattern = $this->namespaced('*');
        if (method_exists($this->redis, 'scan')) {
            $iterator = null;
            do {
                $keys = $this->redis->scan($iterator, $pattern);
                if (is_array($keys)) {
                    foreach ($keys as $found) {
                        $this->redis->del($found);
                    }
                }
            } while ($iterator);
            return true;
        }
        if (method_exists($this->redis, 'keys')) {
            $keys = $this->redis->keys($pattern);
            if (is_array($keys)) {
                foreach ($keys as $found) {
                    $this->redis->del($found);
                }
            }
        }
        return true;
    }

    private function namespaced(string $key): string
    {
        return $this->prefix.':'.$key;
    }
}

final class CacheFactory
{
    private static ?Cache $instance = null;
    private static string $driver = 'file';

    public static function instance(): Cache
    {
        return self::$instance ??= self::create();
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$driver = 'file';
    }

    public static function activeDriver(): string
    {
        self::instance();
        return self::$driver;
    }

    public static function create(?string $driver = null): Cache
    {
        $requested = strtolower((string) ($driver ?? (getenv('CACHE_DRIVER') ?: 'file')));
        $prefix = (string) (getenv('CACHE_PREFIX') ?: 'phpretro');
        if ($requested === 'redis') {
            $redis = self::connectRedis();
            if ($redis !== null) {
                self::$driver = 'redis';
                return new RedisCache($redis, $prefix);
            }
        }
        self::$driver = 'file';
        $path = (string) (getenv('CACHE_PATH') ?: '');
        if ($path === '') {
            $path = dirname(__DIR__).DIRECTORY_SEPARATOR.'cache';
        }
        return new FileCache($path, $prefix);
    }

    private static function connectRedis(): ?object
    {
        $host = (string) (getenv('REDIS_HOST') ?: '127.0.0.1');
        $port = (int) (getenv('REDIS_PORT') ?: 6379);
        $password = getenv('REDIS_PASSWORD');
        $database = (int) (getenv('REDIS_DATABASE') ?: 0);
        try {
            if (class_exists(\Redis::class)) {
                $redis = new \Redis();
                if (!$redis->connect($host, $port, 1.5)) {
                    return null;
                }
                if (is_string($password) && $password !== '') {
                    $redis->auth($password);
                }
                if ($database > 0) {
                    $redis->select($database);
                }
                return $redis;
            }
            if (class_exists(\Predis\Client::class)) {
                $params = ['host' => $host, 'port' => $port, 'database' => $database];
                if (is_string($password) && $password !== '') {
                    $params['password'] = $password;
                }
                return new \Predis\Client($params);
            }
        } catch (Throwable $exception) {
            return null;
        }
        return null;
    }
}
