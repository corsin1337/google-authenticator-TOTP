<?php

class TOTP
{

    function checkSecretKey()
    {
        return isset($_GET['secretkey']);
    }

    function base32_decode($base32)
    {
        $base32_chars = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z', '2', '3', '4', '5', '6', '7'
        );
        $bits = "";
        for ($i = 0; $i < strlen($base32); $i++) {
            $char = strtoupper($base32[$i]);
            $idx = array_search($char, $base32_chars);
            $bits .= str_pad(decbin($idx), 5, '0', STR_PAD_LEFT);
        }
        $bytes = array();
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $bytes[] = bindec(substr($bits, $i, 8));
        }
        return $bytes;
    }

    function hmac_sha1($key, $data)
    {
        $key = pack('C*', ...$key);
        return hash_hmac("sha1", $data, $key, true);
    }

    function hotp($secret, $counter, $length = 6)
    {
        $secret = $this->base32_decode($secret);
        $counter = pack("N*", 0) . pack("N*", $counter);
        $hash = $this->hmac_sha1($secret, $counter);
        $offset = ord(substr($hash, -1));
        $offset = $offset & 0x0F;
        $truncated_hash = substr($hash, $offset, 4);
        $value = unpack("N", $truncated_hash)[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, $length);
        return str_pad($value % $modulo, $length, "0", STR_PAD_LEFT);
    }

    function totp($secret, $time_step = 30, $start_time = 0, $length = 6)
    {
        $counter = floor((time() - $start_time) / $time_step);
        return $this->hotp($secret, $counter, $length);
    }
    function reloadOnTime() {
        $secondsLeft = abs(time() % 30 - 30);
        header("Refresh:" . $secondsLeft);
        echo '<script>
            timeLeft = ' . $secondsLeft . ';
        
            function countdown() {
                timeLeft--;
                document.getElementById("seconds").innerHTML = String( timeLeft );
                if (timeLeft > 0) {
                    setTimeout(countdown, 1000);
                }
            }
            setTimeout(countdown, 1000);</script>
                <span id="seconds">' . $secondsLeft . '</span>';
    }
}