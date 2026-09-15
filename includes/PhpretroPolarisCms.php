<?php
/**
 * PolarIS CMS HTTP API (HMAC) with TCP RCON fallback.
 *
 * Same {key, data} payload on both transports. PolarIS CommandRegistry is the
 * source of truth: docs/cms-api-reference.md and protocol/rcon-contract.json.
 *
 * Env: POLARIS_CMS_URL, POLARIS_CMS_KEY, POLARIS_CMS_SECRET,
 *      POLARIS_RCON_HOST, POLARIS_RCON_PORT.
 */
class PhpretroPolarisCmsError extends RuntimeException {}

class PhpretroPolarisCms
{
    public const STATUS_OK = 0;
    public const STATUS_ERROR = 1;
    public const HABBO_NOT_FOUND = 2;
    public const ROOM_NOT_FOUND = 3;
    public const SYSTEM_ERROR = 4;

    private static ?self $instance = null;

    /** @param ?callable(string $url, string $body, array $headers): string $httpSender */
    /** @param ?callable(string $host, int $port, string $body): string $tcpSender */
    public function __construct(
        public readonly string $cmsUrl = '',
        public readonly string $cmsKey = '',
        public readonly string $cmsSecret = '',
        public readonly string $rconHost = '',
        public readonly int $rconPort = 0,
        private mixed $httpSender = null,
        private mixed $tcpSender = null,
        private int $timeoutSeconds = 5,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            (string) (getenv('POLARIS_CMS_URL') ?: ''),
            (string) (getenv('POLARIS_CMS_KEY') ?: ''),
            (string) (getenv('POLARIS_CMS_SECRET') ?: ''),
            (string) (getenv('POLARIS_RCON_HOST') ?: ''),
            (int) (getenv('POLARIS_RCON_PORT') ?: 0),
        );
    }

    public static function instance(): self
    {
        return self::$instance ??= self::fromEnv();
    }

    public static function setInstance(?self $instance): void
    {
        self::$instance = $instance;
    }

    public function cmsConfigured(): bool
    {
        return $this->cmsUrl !== '' && $this->cmsKey !== '' && $this->cmsSecret !== '';
    }

    public function rconConfigured(): bool
    {
        return $this->rconHost !== '' && $this->rconPort > 0;
    }

    public function configured(): bool
    {
        return $this->cmsConfigured() || $this->rconConfigured();
    }

    public function commandUrl(): string
    {
        $url = rtrim($this->cmsUrl, '/');
        if ($url === '') { return ''; }
        if (str_ends_with($url, '/api/cms/command')) { return $url; }
        if (str_ends_with($url, '/api/cms')) { return $url.'/command'; }
        return $url.'/api/cms/command';
    }

    public function encode(string $key, array $data = []): string
    {
        $payload = ['key' => $key, 'data' => $data === [] ? new stdClass() : $data];
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function signature(string $body, string $timestamp, string $nonce): string
    {
        return hash_hmac('sha256', $this->cmsKey."\n".$timestamp."\n".$nonce."\n".$body, $this->cmsSecret);
    }

    public function cmsHeaders(string $body, ?string $timestamp = null, ?string $nonce = null): array
    {
        $timestamp ??= (string) time();
        $nonce ??= bin2hex(random_bytes(16));
        return [
            'Content-Type' => 'application/json',
            'X-Cms-Key' => $this->cmsKey,
            'X-Cms-Timestamp' => $timestamp,
            'X-Cms-Nonce' => $nonce,
            'X-Cms-Signature' => $this->signature($body, $timestamp, $nonce),
        ];
    }

    /** @return array{status:int,message:string,transport:string,raw:string} */
    public function command(string $key, array $data = []): array
    {
        if (!$this->configured()) {
            throw new PhpretroPolarisCmsError('PolarIS CMS/RCON is not configured.', 503);
        }
        $body = $this->encode($key, $data);
        if ($this->cmsConfigured()) {
            $raw = $this->postCms($body);
            $transport = 'cms';
        } else {
            $raw = $this->postRcon($body);
            $transport = 'rcon';
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !array_key_exists('status', $decoded)) {
            throw new PhpretroPolarisCmsError('PolarIS returned an invalid response.', 502);
        }
        return [
            'status' => (int) $decoded['status'],
            'message' => (string) ($decoded['message'] ?? ''),
            'transport' => $transport,
            'raw' => $raw,
        ];
    }

    /** @return ?array{status:int,message:string,transport:string,raw:string} */
    public function tryCommand(string $key, array $data = []): ?array
    {
        if (!$this->configured()) { return null; }
        try {
            return $this->command($key, $data);
        } catch (PhpretroPolarisCmsError) {
            return null;
        }
    }

    public function reloadCatalogNotice(): string
    {
        if (!$this->configured()) {
            return 'PolarIS catalog cache not reloaded (CMS/RCON not configured).';
        }
        try {
            $result = $this->command('updatecatalog', []);
        } catch (PhpretroPolarisCmsError $error) {
            return 'PolarIS catalog reload failed: '.$error->getMessage();
        }
        if ($result['status'] !== self::STATUS_OK) {
            return 'PolarIS catalog reload failed: '.($result['message'] !== '' ? $result['message'] : 'status '.$result['status']);
        }
        return 'PolarIS catalog reloaded.';
    }

    public function alertUser(int $userId, string $message): array
    {
        return $this->command('alertuser', ['user_id' => $userId, 'message' => $message]);
    }

    public function hotelAlert(string $message, string $url = ''): array
    {
        $data = ['message' => $message];
        if ($url !== '') { $data['url'] = $url; }
        return $this->command('hotelalert', $data);
    }

    public function notifyUserReport(Database $db, int $senderId, int $reportedId, string $reason, string $evidence): ?array
    {
        if ($senderId < 1 || $reportedId < 1 || $senderId === $reportedId) { return null; }
        $sender = $db->fetchRow('SELECT id, username FROM users WHERE id = ?', [$senderId]);
        $reported = $db->fetchRow('SELECT id, username FROM users WHERE id = ?', [$reportedId]);
        if ($sender === false || $reported === false) { return null; }
        $message = trim($reason."\n\n".$evidence);
        if ($message === '') { $message = 'User report'; }
        return $this->tryCommand('modticket', [
            'sender_id' => (int) $sender['id'],
            'sender_username' => self::limit((string) $sender['username'], 64),
            'reported_id' => (int) $reported['id'],
            'reported_username' => self::limit((string) $reported['username'], 64),
            'reported_room_id' => 0,
            'message' => self::limit($message, 4096),
        ]);
    }

    private static function limit(string $value, int $max): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
    }

    private function postCms(string $body): string
    {
        $url = $this->commandUrl();
        $headers = $this->cmsHeaders($body);
        if (is_callable($this->httpSender)) {
            return (string) ($this->httpSender)($url, $body, $headers);
        }
        return $this->httpPost($url, $body, $headers);
    }

    private function postRcon(string $body): string
    {
        if (is_callable($this->tcpSender)) {
            return (string) ($this->tcpSender)($this->rconHost, $this->rconPort, $body);
        }
        return $this->tcpPost($this->rconHost, $this->rconPort, $body);
    }

    private function httpPost(string $url, string $body, array $headers): string
    {
        if (function_exists('curl_init')) {
            $lines = [];
            foreach ($headers as $name => $value) { $lines[] = $name.': '.$value; }
            $handle = curl_init($url);
            if ($handle === false) { throw new PhpretroPolarisCmsError('Could not start CMS HTTP request.', 502); }
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $lines,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
            ]);
            $raw = curl_exec($handle);
            $error = curl_error($handle);
            curl_close($handle);
            if (!is_string($raw) || $raw === '') {
                throw new PhpretroPolarisCmsError($error !== '' ? $error : 'Empty CMS HTTP response.', 502);
            }
            return $raw;
        }
        $headerLines = [];
        foreach ($headers as $name => $value) { $headerLines[] = $name.': '.$value; }
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headerLines),
                'content' => $body,
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || $raw === '') {
            throw new PhpretroPolarisCmsError('CMS HTTP request failed.', 502);
        }
        return $raw;
    }

    private function tcpPost(string $host, int $port, string $body): string
    {
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client('tcp://'.$host.':'.$port, $errno, $errstr, $this->timeoutSeconds);
        if ($socket === false) {
            throw new PhpretroPolarisCmsError('RCON connect failed: '.$errstr, 502);
        }
        stream_set_timeout($socket, $this->timeoutSeconds);
        fwrite($socket, $body);
        if (function_exists('stream_socket_shutdown')) {
            @stream_socket_shutdown($socket, STREAM_SHUT_WR);
        }
        $raw = stream_get_contents($socket);
        fclose($socket);
        if (!is_string($raw) || $raw === '') {
            throw new PhpretroPolarisCmsError('Empty RCON response.', 502);
        }
        return $raw;
    }
}
