<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/zk_config.php';
require_once __DIR__ . '/../includes/ZKTeco.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Unknown action'];

// Helper: get local network subnet
function getLocalSubnet() {
    $ips = [];
    if (PHP_OS_FAMILY === 'Windows') {
        $output = shell_exec('ipconfig');
        preg_match_all('/IPv4[^:]*:\s*([0-9.]+)/', $output, $matches);
        $ips = $matches[1] ?? [];
    } else {
        $output = shell_exec("hostname -I");
        $ips = explode(' ', trim($output));
    }
    foreach ($ips as $ip) {
        if (preg_match('/^(192\.168\.\d+|10\.\d+\.\d+|172\.\d+\.\d+)\./', $ip, $m)) {
            $parts = explode('.', $ip);
            return "$parts[0].$parts[1].$parts[2]";
        }
    }
    return null;
}

// Helper: test if a ZKTeco device is at given IP
function testZKConnection($ip, $timeout = 1) {
    $zk = new ZKTeco($ip, ZK_DEVICE_PORT);
    if ($zk->connect()) { $zk->disconnect(); return true; }
    return false;
}

switch ($action) {
    
    // ========== TEST CURRENT CONFIGURED DEVICE ==========
    case 'test_connection':
        if (testZKConnection(ZK_DEVICE_IP)) {
            $response = ['status'=>'success','message'=>'متصل ✓','ip'=>ZK_DEVICE_IP];
        } else {
            $response = ['status'=>'error','message'=>'الجهاز غير متصل','ip'=>ZK_DEVICE_IP];
        }
        break;

    // ========== AUTO-DISCOVER DEVICES ON NETWORK ==========
    case 'discover':
        $subnet = getLocalSubnet();
        if (!$subnet) {
            $response = ['status'=>'error','message'=>'لم نتمكن من تحديد الشبكة المحلية'];
            break;
        }
        $found = [];
        $commonIPs = [201, 202, 100, 50, 1, 254, 200, 99, 10, 20];
        foreach ($commonIPs as $last) {
            $ip = "$subnet.$last";
            if (testZKConnection($ip, 1)) {
                $found[] = $ip;
            }
        }
        if (!empty($found)) {
            saveZKDeviceIP($found[0]);
            $response = ['status'=>'success','message'=>'تم العثور على ' . count($found) . ' جهاز','devices'=>$found,'saved'=>$found[0]];
        } else {
            $response = ['status'=>'error','message'=>'لم يتم العثور على أي جهاز في الشبكة','subnet'=>$subnet];
        }
        break;

    // ========== MANUALLY SET DEVICE IP ==========
    case 'set_device_ip':
        $ip = $_POST['ip'] ?? '';
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $response = ['status'=>'error','message'=>'IP غير صحيح'];
            break;
        }
        if (testZKConnection($ip, 2)) {
            saveZKDeviceIP($ip);
            $response = ['status'=>'success','message'=>'تم حفظ IP الجهاز: '.$ip];
        } else {
            $response = ['status'=>'error','message'=>'لم نستطع الاتصال بـ '.$ip];
        }
        break;

    // ========== START FINGERPRINT LOGIN (capture baseline) ==========
    case 'start_fp_login':
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) {
            $response = ['status'=>'error','message'=>'الجهاز غير متصل'];
            break;
        }
        $logs = $zk->getAttendance();
        $zk->disconnect();
        
        // Get baseline: latest scan time before user places finger
        $latestTime = '';
        foreach ($logs as $log) {
            if ($log['time'] > $latestTime) $latestTime = $log['time'];
        }
        $_SESSION['fp_baseline'] = $latestTime;
        $_SESSION['fp_start_time'] = time();
        $response = ['status'=>'waiting','message'=>'ضع إصبعك على الجهاز...'];
        break;

    // ========== POLL FOR NEW FINGERPRINT SCAN ==========
    case 'check_fp_login':
        if (!isset($_SESSION['fp_baseline'])) {
            $response = ['status'=>'error','message'=>'يجب البدء أولاً'];
            break;
        }
        // Timeout after 30 seconds
        if ((time() - $_SESSION['fp_start_time']) > ZK_SCAN_TIMEOUT) {
            unset($_SESSION['fp_baseline'], $_SESSION['fp_start_time']);
            $response = ['status'=>'timeout','message'=>'انتهى الوقت، حاول مرة أخرى'];
            break;
        }
        
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) {
            $response = ['status'=>'error','message'=>'فقد الاتصال بالجهاز'];
            break;
        }
        $logs = $zk->getAttendance();
        $zk->disconnect();
        
        // Find any log newer than baseline
        $newest = null;
        foreach ($logs as $log) {
            if ($log['time'] > $_SESSION['fp_baseline']) {
                if (!$newest || $log['time'] > $newest['time']) {
                    $newest = $log;
                }
            }
        }
        
        if ($newest) {
            $uid = $newest['user_id'];
            unset($_SESSION['fp_baseline'], $_SESSION['fp_start_time']);
            
            // Match with database
            $stmt = $conn->prepare("SELECT id, full_name FROM patients WHERE fingerprint_id = ? LIMIT 1");
            $stmt->bind_param("s", $uid);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $_SESSION['patient_id'] = $row['id'];
                $time = $newest['time'];
                $conn->query("INSERT INTO attendance_logs (patient_id, device_user_id, log_time, log_type) VALUES ({$row['id']},'$uid','$time','fp_login')");
                $response = ['status'=>'success','message'=>'مرحباً '.$row['full_name'],'redirect'=>'dashboard.php'];
            } else {
                $response = ['status'=>'not_registered','message'=>'البصمة (User ID: '.$uid.') غير مرتبطة بأي حساب'];
            }
        } else {
            $remaining = ZK_SCAN_TIMEOUT - (time() - $_SESSION['fp_start_time']);
            $response = ['status'=>'waiting','message'=>'بانتظار البصمة...','remaining'=>$remaining];
        }
        break;

    // ========== START FINGERPRINT ENROLLMENT ==========
    case 'start_fp_enroll':
        if (!isset($_SESSION['patient_id'])) {
            $response = ['status'=>'error','message'=>'سجل دخولك أولاً'];
            break;
        }
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) {
            $response = ['status'=>'error','message'=>'الجهاز غير متصل'];
            break;
        }
        $logs = $zk->getAttendance();
        $zk->disconnect();
        
        $latestTime = '';
        foreach ($logs as $log) {
            if ($log['time'] > $latestTime) $latestTime = $log['time'];
        }
        $_SESSION['enroll_baseline'] = $latestTime;
        $_SESSION['enroll_start_time'] = time();
        $response = ['status'=>'waiting','message'=>'ضع إصبعك المسجل على الجهاز...'];
        break;

    // ========== POLL FOR FINGERPRINT TO ENROLL ==========
    case 'check_fp_enroll':
        if (!isset($_SESSION['patient_id'])) {
            $response = ['status'=>'error','message'=>'سجل دخولك']; break;
        }
        if (!isset($_SESSION['enroll_baseline'])) {
            $response = ['status'=>'error','message'=>'يجب البدء أولاً']; break;
        }
        if ((time() - $_SESSION['enroll_start_time']) > ZK_SCAN_TIMEOUT) {
            unset($_SESSION['enroll_baseline'], $_SESSION['enroll_start_time']);
            $response = ['status'=>'timeout','message'=>'انتهى الوقت'];
            break;
        }
        
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) {
            $response = ['status'=>'error','message'=>'فقد الاتصال']; break;
        }
        $logs = $zk->getAttendance();
        $zk->disconnect();
        
        $newest = null;
        foreach ($logs as $log) {
            if ($log['time'] > $_SESSION['enroll_baseline']) {
                if (!$newest || $log['time'] > $newest['time']) {
                    $newest = $log;
                }
            }
        }
        
        if ($newest) {
            $uid = $newest['user_id'];
            $pid = (int)$_SESSION['patient_id'];
            unset($_SESSION['enroll_baseline'], $_SESSION['enroll_start_time']);
            
            // Check if this fingerprint is already linked to another user
            $check = $conn->prepare("SELECT id, full_name FROM patients WHERE fingerprint_id = ? AND id != ?");
            $check->bind_param("si", $uid, $pid);
            $check->execute();
            if ($existing = $check->get_result()->fetch_assoc()) {
                $response = ['status'=>'error','message'=>'هذه البصمة مرتبطة بـ '.$existing['full_name']];
                break;
            }
            
            // Link fingerprint to current user
            $stmt = $conn->prepare("UPDATE patients SET fingerprint_id=?, fingerprint_enrolled=1 WHERE id=?");
            $stmt->bind_param("si", $uid, $pid);
            if ($stmt->execute()) {
                $response = ['status'=>'success','message'=>'✅ تم ربط البصمة بنجاح (User ID: '.$uid.')'];
            } else {
                $response = ['status'=>'error','message'=>'فشل الحفظ في قاعدة البيانات'];
            }
        } else {
            $response = ['status'=>'waiting','message'=>'بانتظار البصمة...'];
        }
        break;

    // ========== CANCEL ANY ONGOING SCAN ==========
    case 'cancel_scan':
        unset($_SESSION['fp_baseline'], $_SESSION['fp_start_time']);
        unset($_SESSION['enroll_baseline'], $_SESSION['enroll_start_time']);
        $response = ['status'=>'success','message'=>'تم الإلغاء'];
        break;

    // ========== UNLINK FINGERPRINT ==========
    case 'unlink_fp':
        if (!isset($_SESSION['patient_id'])) {
            $response = ['status'=>'error','message'=>'سجل دخولك']; break;
        }
        $pid = (int)$_SESSION['patient_id'];
        $conn->query("UPDATE patients SET fingerprint_id=NULL, fingerprint_enrolled=0 WHERE id=$pid");
        $response = ['status'=>'success','message'=>'تم فصل البصمة'];
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>