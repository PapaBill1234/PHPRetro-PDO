<?php
final class Totp {
    public static function secret(): string { return rtrim(strtr(base64_encode(random_bytes(20)), '+/', 'AB'), '='); }
    public static function verify(string $secret, string $code): bool { return preg_match('/^[0-9]{6}$/', $code) === 1 && hash_equals(self::code($secret, intdiv(time(),30)), $code); }
    private static function code(string $secret, int $counter): string { $bin=base64_decode(strtr($secret,'AB','+/'),true); if($bin===false){return '000000';}$hash=hash_hmac('sha1',pack('N2',0,$counter),$bin,true);$offset=ord($hash[19])&15;$value=((ord($hash[$offset])&127)<<24)|(ord($hash[$offset+1])<<16)|(ord($hash[$offset+2])<<8)|ord($hash[$offset+3]);return str_pad((string)($value%1000000),6,'0',STR_PAD_LEFT); }
}