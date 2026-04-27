<?php
$configFile = __DIR__ . '/zk_device.txt';

if (file_exists($configFile)) {
    $savedIP = trim(file_get_contents($configFile));
    if (filter_var($savedIP, FILTER_VALIDATE_IP)) {
        define('ZK_DEVICE_IP', $savedIP);
    } else {
        define('ZK_DEVICE_IP', '0.0.0.0');
    }
} else {
    define('ZK_DEVICE_IP', '0.0.0.0');
}

define('ZK_DEVICE_PORT', 4370);
define('ZK_SCAN_TIMEOUT', 30);

function saveZKDeviceIP($ip) {
    file_put_contents(__DIR__ . '/zk_device.txt', $ip);
}
?>