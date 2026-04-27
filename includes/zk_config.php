<?php
// =====================================================
// ZKTeco Configuration
// =====================================================
$configFile = __DIR__ . '/zk_device.txt';

// Read saved IP from file (auto-discovered or manually set)
if (file_exists($configFile)) {
    $savedIP = trim(file_get_contents($configFile));
    if (filter_var($savedIP, FILTER_VALIDATE_IP)) {
        define('ZK_DEVICE_IP', $savedIP);
    } else {
        define('ZK_DEVICE_IP', '192.168.1.201');
    }
} else {
    define('ZK_DEVICE_IP', '192.168.1.201');
}

define('ZK_DEVICE_PORT', 4370);
define('ZK_SCAN_TIMEOUT', 30); // seconds to wait for fingerprint
define('ZK_POLL_INTERVAL', 1500); // milliseconds between polls

// Helper to save discovered IP
function saveZKDeviceIP($ip) {
    file_put_contents(__DIR__ . '/zk_device.txt', $ip);
}
?>