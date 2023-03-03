<?php

class TOTP
{
    private static $BASE32_CHARS = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', '2', '3', '4', '5', '6', '7'
    );

    public function base32_decode($base32)
    {
        $bits = "";
        for ($i = 0; $i < strlen($base32); $i++) {
            $char = strtoupper($base32[$i]);
            if (!in_array($char, self::$BASE32_CHARS)) {
                throw new Exception('Invalid base32 string');
            }
            $idx = array_search($char, self::$BASE32_CHARS);
            $bits .= sprintf('%05b', $idx);
        }
        $bytes = array();
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $bytes[] = bindec(substr($bits, $i, 8));
        }
        return $bytes;
    }

    public function hmac_sha1($key, $data)
    {
        $key = pack('C*', ...$key);
        return hash_hmac("sha1", $data, $key, true);
    }

    public function hotp($secret, $counter, $length = 6)
    {
        $secret = $this->base32_decode($secret);
        $counter = pack("N*", 0) . pack("N*", $counter);
        $hash = $this->hmac_sha1($secret, $counter);
        $offset = ord(substr($hash, -1));
        $truncated_hash = substr($hash, $offset & 0x0F, 4);
        $value = unpack("N", $truncated_hash)[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, $length);
        return str_pad($value % $modulo, $length, "0", STR_PAD_LEFT);
    }

    public function totp($secret, $time_step = 30, $start_time = 0, $length = 6)
    {
        $counter = floor((time() - $start_time) / $time_step);
        return $this->hotp($secret, $counter, $length);
    }

    public function reloadOnTime($time_step = 30)
    {
        $timer = abs(time() % $time_step - $time_step);
        header("Refresh:" . $timer);
    }
}