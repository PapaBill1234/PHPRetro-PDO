<?php
final class Totp {
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    public static function secret(): string { return self::encode(random_bytes(20)); }
    public static function verify(string $secret, string $code, int $window = 1): bool {
        if (preg_match('/^[0-9]{6}$/', $code) !== 1) return false;
        $counter = intdiv(time(), 30);
        for ($offset = -$window; $offset <= $window; $offset++) if (hash_equals(self::code($secret, $counter + $offset), $code)) return true;
        return false;
    }
    private static function code(string $secret, int $counter): string {
        $key = self::decode($secret); if ($key === '') return '000000';
        $hash = hash_hmac('sha1', pack('N2', 0, $counter), $key, true); $offset = ord($hash[19]) & 15;
        $value = ((ord($hash[$offset]) & 127) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }
    private static function encode(string $data): string { $bits=''; foreach(str_split($data) as $char) $bits.=str_pad(decbin(ord($char)),8,'0',STR_PAD_LEFT); $out=''; foreach(str_split($bits,5) as $chunk){if(strlen($chunk)<5)$chunk=str_pad($chunk,5,'0');$out.=self::ALPHABET[bindec($chunk)];} return $out; }
    private static function decode(string $input): string { $bits=''; foreach(str_split(strtoupper(preg_replace('/[^A-Z2-7]/','',$input))) as $char){$pos=strpos(self::ALPHABET,$char);if($pos===false)return '';$bits.=str_pad(decbin($pos),5,'0',STR_PAD_LEFT);} $out=''; foreach(str_split($bits,8) as $chunk) if(strlen($chunk)===8)$out.=chr(bindec($chunk)); return $out; }
}