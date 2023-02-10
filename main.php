<?php
require_once('totp.class.php');

if (isset($_GET['secretkey'])) {
    $secret = $_GET['secretkey'];
    $totp = new TOTP($secret);
    echo $result = $totp->totp($secret);
    $totp->reloadOnTime();
}