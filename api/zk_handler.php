<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/zk_config.php';
require_once __DIR__ . '/../includes/ZKTeco.php';
header('Content-Type: application/json; charset=utf-8');
$action = $_REQUEST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Unknown action'];
switch ($action) {
    case 'test_connection':
        if (ZK_SIMULATION_MODE) { $response = ['status'=>'success','message'=>'محاكاة نشطة']; break; }
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if ($zk->connect()) { $zk->disconnect(); $response = ['status'=>'success','message'=>'متصل']; }
        else { $response = ['status'=>'error','message'=>'تعذر الاتصال']; }
        break;
    case 'verify_fingerprint':
        if (ZK_SIMULATION_MODE) {
            $r = $conn->query("SELECT id, full_name FROM patients WHERE fingerprint_enrolled=1 LIMIT 1");
            if ($r && $r->num_rows > 0) {
                $row = $r->fetch_assoc();
                $_SESSION['patient_id'] = $row['id'];
                $response = ['status'=>'success','message'=>'مرحباً '.$row['full_name'],'redirect'=>'dashboard.php'];
            } else { $response = ['status'=>'error','message'=>'لا يوجد مريض مسجل']; }
            break;
        }
        $zk = new ZKTeco(ZK_DEVICE_IP, ZK_DEVICE_PORT);
        if (!$zk->connect()) { $response = ['status'=>'error','message'=>'فشل']; break; }
        $logs = $zk->getAttendance(); $zk->disconnect();
        if (empty($logs)) { $response = ['status'=>'error','message'=>'لا توجد بصمات']; break; }
        $uid = $logs[0]['user_id'];
        $stmt = $conn->prepare("SELECT id, full_name FROM patients WHERE fingerprint_id = ? LIMIT 1");
        $stmt->bind_param("s", $uid); $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $_SESSION['patient_id'] = $row['id'];
            $response = ['status'=>'success','message'=>'مرحباً '.$row['full_name'],'redirect'=>'dashboard.php'];
        } else { $response = ['status'=>'error','message'=>'البصمة غير مسجلة']; }
        break;
    case 'enroll_fingerprint':
        if (!isset($_SESSION['patient_id'])) { $response=['status'=>'error','message'=>'سجل دخولك']; break; }
        $uid = $_POST['device_user_id'] ?? '';
        if (!$uid) { $response=['status'=>'error','message'=>'رقم مطلوب']; break; }
        $pid = (int)$_SESSION['patient_id'];
        $stmt = $conn->prepare("UPDATE patients SET fingerprint_id=?, fingerprint_enrolled=1 WHERE id=?");
        $stmt->bind_param("si", $uid, $pid);
        $response = $stmt->execute() ? ['status'=>'success','message'=>'تم الربط'] : ['status'=>'error','message'=>'فشل'];
        break;
    case 'sync_logs':
        $response = ['status'=>'success','message'=>'تم'];
        break;
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);