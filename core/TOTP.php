<?php
/**
 * Lightweight TOTP (Google Authenticator) Helper Class (RFC 6238)
 */
class TOTP {

    /**
     * Decode Base32 string
     */
    private static function base32Decode($base32) {
        $base32 = strtoupper($base32);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $decoded = '';
        $buffer = 0;
        $bufferSize = 0;
        
        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            
            $buffer = ($buffer << 5) | $pos;
            $bufferSize += 5;
            
            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $decoded .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }
        return $decoded;
    }

    /**
     * Get current or specific time slice code
     */
    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        
        $secretKey = self::base32Decode($secret);
        
        // Pack time slice into 8-byte binary string (N* is 32-bit big endian)
        $timeBinary = pack('N*', 0) . pack('N*', $timeSlice);
        
        // Calculate HMAC-SHA1
        $hash = hash_hmac('sha1', $timeBinary, $secretKey, true);
        
        // Dynamic truncation
        $offset = ord($hash[19]) & 0xf;
        $otp = (
            (ord($hash[$offset]) & 0x7f) << 24 |
            (ord($hash[$offset + 1]) & 0xff) << 16 |
            (ord($hash[$offset + 2]) & 0xff) << 8 |
            (ord($hash[$offset + 3]) & 0xff)
        );
        
        $code = $otp % 1000000;
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted 2FA code within a +/- 1 time-slice window.
     *
     * Returns the matched time-slice (int) on success, or false on failure.
     * Callers should persist the returned slice and reject any code whose
     * slice is <= the last accepted one (replay protection).
     */
    public static function verify($secret, $code, $discrepancy = 1) {
        $code = preg_replace('/\s+/', '', (string) $code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $slice = $currentTimeSlice + $i;
            if (hash_equals(self::getCode($secret, $slice), $code)) {
                return $slice;
            }
        }
        return false;
    }

    /**
     * Generate a cryptographically-random 16-character Base32 secret (80 bits).
     */
    public static function generateSecret() {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bytes = random_bytes(16);
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $alphabet[ord($bytes[$i]) & 31];
        }
        return $secret;
    }
}
