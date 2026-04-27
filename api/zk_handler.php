<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/zk_config.php';
require_once __DIR__ . '/../includes/ZKTeco.php';
header('Content-Type: application/json; charset=utf-8');
ini_set('max_execution_time', 60);

$action = $_REQUEST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Unknown action'];

// ============================================================
// Get ALL network interfaces (Ethernet, WiFi, APIPA all included)
// ============================================================
function getAllSubnets() {
    $subnets = [];
    $output = '';
    if (PHP_OS_FAMILY === 'Windows') {
        $output = @shell_exec('ipconfig');
    } else {
        $output = @shell_exec('ip -4 addr show 2>/dev/null || ifconfig 2>/dev/null');
    }
    if (preg_match_all('/(?:IPv4|inet)[^:]*[:\s]+(\d+\.\d+\.\d+\.\d+)/i', $output, $matches)) {
        foreach ($matches[1] as $ip) {
            if ($ip === '127.0.0.1') continue;
            if (substr($ip, 0, 4) === '0.0.') continue;
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $subnet = "$parts[0].$parts[1].$parts[2]";
                if (!in_array($subnet, $subnets)) {
                    $subnets[] = $subnet;
                }
            }
        }
    }
    return $subnets;
}

// ============================================================
// FAST UDP DISCOVERY: Send to all IPs at once, listen for replies
// ============================================================
function fastDiscoverSubnet($subnet, $waitSec = 3) {
    if (!function_exists('socket_create')) return [];
    
    $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if (!$sock) return [];
    
    @socket_set_nonblock($sock);
    @socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    
    // ZKTeco CMD_CONNECT packet (cmd=1000)
    $packet = pack('SSSS', 1000, 0, 0, 0);
    
    // Spray packet to all 254 IPs
    for ($i = 1; $i <= 254; $i++) {
        $ip = "$subnet.$i";
        @socket_sendto($sock, $packet, strlen($packet), 0, $ip, 4370);
    }
    // Also try broadcast
    @socket_sendto($sock, $packet, strlen($packet), 0, "$subnet.255", 4370);
    
    // Listen for replies
    $found = [];
    $startTime = microtime(true);
    while ((microtime(true) - $startTime) < $waitSec) {
        $r = [$sock]; $w = null; $e = null;
        if (@socket_select($r, $w, $e, 0, 200000)) {
            $reply = ''; $from = ''; $p = 0;
            if (@socket_recvfrom($sock, $reply, 1024, 0, $from, $p)) {
                // ZKTeco reply has cmd=2000 (ACK_OK) at start
                if (strlen($reply) >= 8) {
                    $u = unpack('Scmd', substr($reply, 0, 2));
                    if ($u['cmd'] === 2000 && !in_array($from, $found)) {
                        $found[] = $from;
                    }
                }
            }
        }
    }
    @socket_close($sock);
    return $found;
}

// ============================================================
// Test if ZKTeco is at given IP
// ============================================================
function testZKConnection($ip) {
    $zk = new ZKTeco($ip, ZK_DEVICE_PORT);
    if ($zk->connect()) { $zk->disconnect(); return true; }
    return false;
}

switch ($action) {
    
    case 'test_connection':
        if (testZKConnection(ZK_DEVICE_IP)) {
            $response = ['status'=>'success','message'=>'متصل','ip'=>ZK_DEVICE_IP];
        } else {
            $response = ['status'=>'error','message'=>'الجهاز غير متصل','ip'=>ZK_DEVICE_IP];
        }
        break;

    // ========== AUTO-DISCOVERY: Scan ALL local subnets ==========
    case 'discover':
        $subnets = getAllSubnets();
        if (empty($subnets)) {
            $response = ['status'=>'error','message'=>'لا توجد شبكات نشطة'];
            break;
        }
        
        $allFound = [];
        $scannedSubnets = [];
        
        foreach ($subnets as $subnet) {
            $scannedSubnets[] = "$subnet.0/24";
            $found = fastDiscoverSubnet($subnet, 2);
            $allFound = array_merge($allFound, $found);
        }
        
        $allFound = array_unique($allFound);
        
        if (!empty($allFound)) {
            saveZKDeviceIP($allFound[0]);
            $response = [
                'status'=>'success',
                'message'=>'تم العثور على ' . count($allFound) . ' جهاز',
                'devices'=>array_values($allFound),
                'saved'=>$allFound[0],
                'subnets_scanned'=>$scannedSubnets
            ];
        } else {
            $response = [
                'status'=>'error',
                'message'=>'لم يتم العثور على أي جهاز',
                'subnets_scanned'=>$scannedSubnets,
                'hint'=>'تأكد من توصيل الجهاز بنفس الشبكة'
            ];
        }
        break;

    // ========== Show local network info ==========
    case 'network_info':
        $subnets = getAllSubnets();
        $info = [];
        if (PHP_OS_FAMILY === 'Windows') {
            $raw = @shell_exec('ipconfig');
        } else {
            $raw = @shell_exec('ip -4 addr show 2>/dev/null');
        }
        $response = [
            'status'=>'success',
            'subnets'=>$subnets,
            'sockets_enabled'=>function_exists('socket_create'),
            'shell_exec_enabled'=>function_exists('shell_exec'),
            'os'=>PHP_OS_FAMILY,
            'raw_output'=>$raw
        ];
        break;

    case 'set_device_ip':
        $ip = $_POST['ip'] ?? '';
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $response = ['status'=>'error','message'=>'IP غير صحيح'];
            break;
        }
        if (testZKConnection($ip)) {
            saveZKDeviceIP($ip);
            $response = ['status'=>'success','message'=>'تم حفظ IP الجهاز: '.$ip];
        } else {
            // Save anyway, maybe user knows it's correct
            saveZKDeviceIP($ip);
            $response = ['status'=>'warning','message'=>'تم الحفظ لكن لم نستطع الاتصال بـ '.$ip];
        }
        break;

    case 'start_fp_login':
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) {
            $response = ['status'=>'error','message'=>'الجهاز غير متصل']; break;
        }
        $logs = $zk->getAttendance(); $zk->disconnect();
        $latestTime = '';
        foreach ($logs as $log) if ($log['time'] > $latestTime) $latestTime = $log['time'];
        $_SESSION['fp_baseline'] = $latestTime;
        $_SESSION['fp_start_time'] = time();
        $response = ['status'=>'waiting','message'=>'ضع إصبعك على الجهاز...'];
        break;

    case 'check_fp_login':
        if (!isset($_SESSION['fp_baseline'])) {
            $response = ['status'=>'error','message'=>'يجب البدء أولاً']; break;
        }
        if ((time() - $_SESSION['fp_start_time']) > 30) {
            unset($_SESSION['fp_baseline'], $_SESSION['fp_start_time']);
            $response = ['status'=>'timeout','message'=>'انتهى الوقت']; break;
        }
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) {
            $response = ['status'=>'error','message'=>'فقد الاتصال']; break;
        }
        $logs = $zk->getAttendance(); $zk->disconnect();
        $newest = null;
        foreach ($logs as $log) {
            if ($log['time'] > $_SESSION['fp_baseline']) {
                if (!$newest || $log['time'] > $newest['time']) $newest = $log;
            }
        }
        if ($newest) {
            $uid = $newest['user_id'];
            unset($_SESSION['fp_baseline'], $_SESSION['fp_start_time']);
            $stmt = $conn->prepare("SELECT id, full_name FROM patients WHERE fingerprint_id = ? LIMIT 1");
            $stmt->bind_param("s", $uid); $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $_SESSION['patient_id'] = $row['id'];
                $time = $newest['time'];
                $conn->query("INSERT INTO attendance_logs (patient_id, device_user_id, log_time, log_type) VALUES ({$row['id']},'$uid','$time','fp_login')");
                $response = ['status'=>'success','message'=>'مرحباً '.$row['full_name'],'redirect'=>'dashboard.php'];
            } else {
                $response = ['status'=>'not_registered','message'=>'البصمة (User ID: '.$uid.') غير مرتبطة'];
            }
        } else {
            $response = ['status'=>'waiting','message'=>'بانتظار البصمة...'];
        }
        break;

    case 'start_fp_enroll':
        if (!isset($_SESSION['patient_id'])) { $response=['status'=>'error','message'=>'سجل دخولك']; break; }
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) { $response=['status'=>'error','message'=>'الجهاز غير متصل']; break; }
        $logs = $zk->getAttendance(); $zk->disconnect();
        $latestTime = '';
        foreach ($logs as $log) if ($log['time'] > $latestTime) $latestTime = $log['time'];
        $_SESSION['enroll_baseline'] = $latestTime;
        $_SESSION['enroll_start_time'] = time();
        $response = ['status'=>'waiting','message'=>'ضع إصبعك المسجل على الجهاز...'];
        break;

    case 'check_fp_enroll':
        if (!isset($_SESSION['patient_id'])) { $response=['status'=>'error','message'=>'سجل دخولك']; break; }
        if (!isset($_SESSION['enroll_baseline'])) { $response=['status'=>'error','message'=>'يجب البدء']; break; }
        if ((time() - $_SESSION['enroll_start_time']) > 30) {
            unset($_SESSION['enroll_baseline'], $_SESSION['enroll_start_time']);
            $response = ['status'=>'timeout','message'=>'انتهى الوقت']; break;
        }
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) { $response=['status'=>'error','message'=>'فقد الاتصال']; break; }
        $logs = $zk->getAttendance(); $zk->disconnect();
        $newest = null;
        foreach ($logs as $log) {
            if ($log['time'] > $_SESSION['enroll_baseline']) {
                if (!$newest || $log['time'] > $newest['time']) $newest = $log;
            }
        }
        if ($newest) {
            $uid = $newest['user_id'];
            $pid = (int)$_SESSION['patient_id'];
            unset($_SESSION['enroll_baseline'], $_SESSION['enroll_start_time']);
            $check = $conn->prepare("SELECT full_name FROM patients WHERE fingerprint_id = ? AND id != ?");
            $check->bind_param("si", $uid, $pid); $check->execute();
            if ($existing = $check->get_result()->fetch_assoc()) {
                $response = ['status'=>'error','message'=>'هذه البصمة مرتبطة بـ '.$existing['full_name']];
                break;
            }
            $stmt = $conn->prepare("UPDATE patients SET fingerprint_id=?, fingerprint_enrolled=1 WHERE id=?");
            $stmt->bind_param("si", $uid, $pid);
            $response = $stmt->execute() ? ['status'=>'success','message'=>'✅ تم ربط البصمة (User ID: '.$uid.')'] : ['status'=>'error','message'=>'فشل'];
        } else {
            $response = ['status'=>'waiting','message'=>'بانتظار البصمة...'];
        }
        break;

    case 'cancel_scan':
        unset($_SESSION['fp_baseline'], $_SESSION['fp_start_time']);
        unset($_SESSION['enroll_baseline'], $_SESSION['enroll_start_time']);
        $response = ['status'=>'success','message'=>'تم الإلغاء'];
        break;

    case 'unlink_fp':
        if (!isset($_SESSION['patient_id'])) { $response=['status'=>'error','message'=>'سجل دخولك']; break; }
        $pid = (int)$_SESSION['patient_id'];
        $conn->query("UPDATE patients SET fingerprint_id=NULL, fingerprint_enrolled=0 WHERE id=$pid");
        $response = ['status'=>'success','message'=>'تم فصل البصمة'];
        break;
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>