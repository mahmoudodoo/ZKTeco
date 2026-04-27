<?php
// ============================================================
// SEMAH SETUP - Run once via browser to create all files
// Visit: http://localhost/semah/setup.php
// ============================================================
$base = __DIR__;
$files = [];

// =========== includes/db.php ===========
$files['includes/db.php'] = <<<'EOT'
<?php
$conn = new mysqli("localhost", "root", "", "semah_db");
if ($conn->connect_error) { die("DB Error: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");
EOT;

// =========== includes/zk_config.php ===========
$files['includes/zk_config.php'] = <<<'EOT'
<?php
define('ZK_DEVICE_IP',   '192.168.1.201');
define('ZK_DEVICE_PORT', 4370);
define('ZK_SIMULATION_MODE', true);
EOT;

// =========== includes/auth_check.php ===========
$files['includes/auth_check.php'] = <<<'EOT'
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['patient_id'])) { header("Location: auth.php"); exit; }
EOT;

// =========== includes/guest_check.php ===========
$files['includes/guest_check.php'] = <<<'EOT'
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['patient_id'])) { header("Location: dashboard.php"); exit; }
EOT;

// =========== includes/sidebar.php ===========
$files['includes/sidebar.php'] = <<<'EOT'
<?php $cp = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
<div class="patient-info">
<div class="avatar-box"><?= mb_substr($full_name ?? 'م', 0, 1, 'utf-8') ?></div>
<div style="font-weight:900"><?= htmlspecialchars($full_name ?? 'مريض') ?></div>
<div style="font-size:11px;color:#00d4ff;margin-top:5px">الحالة: نشطة</div>
</div>
<nav class="nav-list">
<a href="dashboard.php" class="nav-item <?= $cp=='dashboard.php'?'active':'' ?>">📋 الملف الصحي</a>
<a href="digital_tests.php" class="nav-item <?= $cp=='digital_tests.php'?'active':'' ?>">🧪 التحاليل</a>
<a href="history.php" class="nav-item <?= $cp=='history.php'?'active':'' ?>">📚 السجلات</a>
<a href="settings.php" class="nav-item <?= $cp=='settings.php'?'active':'' ?>">⚙️ الإعدادات</a>
<a href="zk_admin.php" class="nav-item <?= $cp=='zk_admin.php'?'active':'' ?>">🔌 ZKTeco</a>
<a href="index.php" class="nav-item">🏠 الرئيسية</a>
</nav>
<a href="logout.php" class="nav-item logout-btn">🚪 تسجيل الخروج</a>
</aside>
EOT;

// =========== includes/sidebar_styles.php ===========
$files['includes/sidebar_styles.php'] = <<<'EOT'
<style>
:root{--bg:#020405;--cyan:#00d4ff;--card:#0b1118;--inner:#161f2b;--border:#1a2634;--text:#fff;--dim:#8899a6;--danger:#ff4d6d;--success:#00ff88}
*{box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',sans-serif;margin:0;display:flex;height:100vh;overflow:hidden}
.sidebar{width:280px;background:#010203;border-left:1px solid var(--border);display:flex;flex-direction:column;padding:30px 15px;flex-shrink:0}
.patient-info{text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid var(--border)}
.avatar-box{width:60px;height:60px;background:linear-gradient(45deg,var(--cyan),#005f73);border-radius:12px;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.5rem}
.nav-list{list-style:none;padding:0;flex-grow:1;margin:0}
.nav-item{display:block;padding:14px 18px;color:var(--dim);text-decoration:none;border-radius:8px;margin-bottom:6px;font-size:14px;transition:.3s}
.nav-item:hover,.nav-item.active{background:rgba(0,212,255,.1);color:var(--cyan)}
.logout-btn{color:var(--danger);border-top:1px solid var(--border);padding-top:20px;margin-top:10px}
.main-wrapper{flex-grow:1;display:flex;flex-direction:column;overflow-y:auto}
.top-header{height:75px;padding:0 40px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:rgba(2,4,5,.8);position:sticky;top:0;z-index:10}
button,input,select,textarea{font-family:inherit}
</style>
EOT;

// =========== includes/ZKTeco.php ===========
$files['includes/ZKTeco.php'] = <<<'EOT'
<?php
class ZKTeco {
    private $ip, $port, $socket;
    private $session_id = 0; private $reply_id = -1;
    const USHRT_MAX = 65535;
    public function __construct($ip, $port = 4370) { $this->ip = $ip; $this->port = $port; }
    public function connect() {
        if (!function_exists('socket_create')) return false;
        $this->socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$this->socket) return false;
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec'=>3,'usec'=>0]);
        $buf = $this->makeHeader(1000, 0, 0, '');
        @socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        $r=''; $f=''; $p=0;
        if (@socket_recvfrom($this->socket, $r, 1024, 0, $f, $p)) {
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($r, 0, 8));
            $this->session_id = hexdec($u['h6'].$u['h5']);
            return true;
        }
        return false;
    }
    public function disconnect() { if ($this->socket) @socket_close($this->socket); }
    private function chksum($p) {
        $c=0; $i=count($p); $j=1;
        while ($i>1) { $c += unpack('S', pack('C2', $p['c'.$j], $p['c'.($j+1)]))[1];
            if ($c > self::USHRT_MAX) $c -= self::USHRT_MAX; $i-=2; $j+=2; }
        if ($i) $c += $p['c'.$j];
        while ($c > self::USHRT_MAX) $c -= self::USHRT_MAX;
        $c = ~$c; while ($c < 0) $c += self::USHRT_MAX;
        return pack('S', $c);
    }
    private function makeHeader($cmd, $chk, $sid, $str) {
        $b = pack('SSSS', $cmd, $chk, $sid, $this->reply_id) . $str;
        $b = unpack('C'.(8+strlen($str)).'c', $b);
        $u = unpack('S', $this->chksum($b));
        if (is_array($u)) foreach($u as $v) $u = $v;
        $this->reply_id = ($this->reply_id + 1) % self::USHRT_MAX;
        return pack('SSSS', $cmd, $u, $sid, $this->reply_id) . $str;
    }
    public function getAttendance() {
        $buf = $this->makeHeader(13, 0, $this->session_id, '');
        @socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        $r=''; $f=''; $p=0; $logs=[];
        while (@socket_recvfrom($this->socket, $r, 1024, 0, $f, $p)) {
            if (strlen($r) <= 8) break;
            $d = substr($r, 8); $cnt = floor(strlen($d) / 40);
            for ($i=0; $i<$cnt; $i++) {
                $row = substr($d, $i*40, 40);
                $logs[] = ['user_id'=>trim(substr($row,2,24)),'time'=>date('Y-m-d H:i:s')];
            }
            if (strlen($r) < 1024) break;
        }
        return $logs;
    }
}
EOT;

// =========== api/zk_handler.php ===========
$files['api/zk_handler.php'] = <<<'EOT'
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
EOT;

// =========== auth.php ===========
$files['auth.php'] = <<<'EOT'
<?php
session_start();
if (isset($_SESSION['patient_id'])) { header("Location: dashboard.php"); exit; }
require_once 'includes/db.php';
$err = ''; $msg = '';
if (isset($_POST['login_final'])) {
    $id = $conn->real_escape_string($_POST['user_national_id']);
    $pw = $conn->real_escape_string($_POST['user_password']);
    $r = $conn->query("SELECT id FROM patients WHERE national_id='$id' AND password='$pw'");
    if ($r && $r->num_rows > 0) {
        $_SESSION['patient_id'] = $r->fetch_assoc()['id'];
        header("Location: dashboard.php"); exit;
    }
    $err = "كلمة المرور غير صحيحة";
}
if (isset($_POST['register'])) {
    $fn = $conn->real_escape_string($_POST['full_name']);
    $nid = $conn->real_escape_string($_POST['national_id']);
    $pw = $conn->real_escape_string($_POST['password']);
    $g = $conn->real_escape_string($_POST['gender']);
    $bd = $conn->real_escape_string($_POST['birth_date']);
    $c = isset($_POST['country']) ? $conn->real_escape_string($_POST['country']) : 'سعودي';
    if (!preg_match('/^[0-9]{10}$/', $nid)) $err = "رقم الهوية 10 أرقام";
    elseif (!preg_match('/^[a-zA-Z0-9@_#]{8,15}$/', $pw)) $err = "كلمة المرور 8-15 حرف";
    else {
        $chk = $conn->query("SELECT id FROM patients WHERE national_id='$nid'");
        if ($chk && $chk->num_rows > 0) $err = "رقم مسجل مسبقاً";
        else {
            $sql = "INSERT INTO patients (full_name, gender, nationality, national_id, password, account_status, birth_date) VALUES ('$fn','$g','$c','$nid','$pw','نشط','$bd')";
            if ($conn->query($sql)) $msg = "تم إنشاء ملفك! سجل دخولك الآن";
            else $err = "خطأ: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>سِـمَـه | بوابة</title>
<style>
:root{--bg:#020405;--cyan:#00d4ff;--card:#0b1118;--border:#1a2634;--text:#fff;--dim:#8899a6;--danger:#ff4d6d}
body.s{--bg:#E0E5E7;--text:#001f3f;--card:#fff;--border:#cfd8dc;--cyan:#0077b6}
*{box-sizing:border-box}body{background:var(--bg);color:var(--text);font-family:'Segoe UI',sans-serif;margin:0;min-height:100vh;transition:.6s}
header{border-bottom:1px solid var(--border);padding:20px 0;position:fixed;top:0;width:100%;z-index:1000;background:var(--bg)}
.cont{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;justify-content:space-between;align-items:center}
.cyan{color:var(--cyan)}
.wrap{display:flex;justify-content:center;align-items:center;min-height:100vh;padding:100px 20px}
.card{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:40px;max-width:450px;width:100%;text-align:center}
input,select{width:100%;padding:15px;border-radius:10px;border:1px solid var(--border);background:rgba(255,255,255,.05);color:var(--text);margin-bottom:15px;font-family:inherit}
body.s input,body.s select{background:#f8f9fa;color:#111}
.btn{width:100%;padding:16px;background:var(--cyan);color:#000;border:none;border-radius:10px;font-weight:900;cursor:pointer;font-size:15px}
.fp{margin-top:20px;padding:20px;border:2px dashed var(--cyan);border-radius:15px;cursor:pointer;color:var(--cyan)}
.fp.scan{animation:p 1.5s infinite}@keyframes p{0%,100%{opacity:1}50%{opacity:.5}}
.tg{color:var(--cyan);cursor:pointer;text-decoration:underline}
.al{padding:10px;border-radius:8px;margin-bottom:15px;font-size:13px}
.ae{background:rgba(255,77,109,.1);color:var(--danger);border:1px solid var(--danger)}
.ao{background:rgba(0,255,136,.1);color:#00ff88;border:1px solid #00ff88}
#zk{font-size:12px;padding:5px 12px;border-radius:20px}
.on{background:rgba(0,255,136,.1);color:#00ff88}
.off{background:rgba(255,77,109,.1);color:var(--danger)}
</style></head><body id="b">
<header><div class="cont">
<div style="font-weight:900">AETHERIS <span class="cyan">CLINICAL</span></div>
<div id="zk" class="off">● ZKTeco...</div>
</div></header>
<div class="wrap"><div class="card">
<?php if($err): ?><div class="al ae">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if($msg): ?><div class="al ao">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div id="lg">
<h2>دخول <span class="cyan">النظام</span></h2>
<p style="color:var(--dim)">تحقق من هويتك</p>
<div id="p1">
<input type="text" id="ic" placeholder="رقم الهوية (10 أرقام)">
<button class="btn" onclick="vr()">التحقق</button>
</div>
<div id="p2" style="display:none">
<form method="POST">
<input type="hidden" name="user_national_id" id="ci">
<input type="password" name="user_password" placeholder="كلمة المرور" required>
<button type="submit" name="login_final" class="btn">دخول</button>
</form>
<div style="margin:20px 0;color:var(--dim);font-size:12px">— أو —</div>
<div class="fp" id="fp" onclick="bio()">
<div style="font-size:32px">🔒</div>
<div id="ft" style="font-weight:bold;font-size:13px">ضع إصبعك على ZKTeco</div>
</div></div>
<p style="margin-top:25px;color:var(--dim);font-size:14px">ليس لديك ملف؟ <span class="tg" onclick="tg('s')">أنشئ حساب</span></p>
</div>
<div id="sg" style="display:none">
<h2 style="color:var(--cyan)">إنشاء <span style="color:var(--text)">ملف</span></h2>
<form method="POST">
<input type="text" name="full_name" placeholder="الاسم" required>
<select name="gender" required><option value="" disabled selected>الجنس</option><option value="ذكر">ذكر</option><option value="أنثى">أنثى</option></select>
<input type="date" name="birth_date" required>
<select name="nationality" id="ns" onchange="tc()" required><option value="سعودي">سعودي</option><option value="غير ذلك">غير ذلك</option></select>
<div id="cf" style="display:none"><input type="text" name="country" placeholder="البلد"></div>
<input type="number" name="national_id" placeholder="رقم الهوية" required>
<input type="password" name="password" placeholder="كلمة المرور (8-15 حرف)" required>
<button type="submit" name="register" class="btn">حفظ</button>
</form>
<p style="margin-top:25px;color:var(--dim);font-size:14px">لديك حساب؟ <span class="tg" onclick="tg('l')">دخول</span></p>
</div>
</div></div>
<script>
window.addEventListener('load',()=>{fetch('api/zk_handler.php?action=test_connection').then(r=>r.json()).then(d=>{
  const t=document.getElementById('zk');
  if(d.status==='success'){t.className='on';t.textContent='● '+d.message;}
  else{t.className='off';t.textContent='● غير متصل';}
}).catch(()=>{});});
function vr(){const v=document.getElementById('ic').value;
  if(v.length<10){alert('10 أرقام');return;}
  document.getElementById('p1').style.display='none';
  document.getElementById('p2').style.display='block';
  document.getElementById('ci').value=v;}
function bio(){const a=document.getElementById('fp'),t=document.getElementById('ft');
  a.classList.add('scan');t.textContent='جاري القراءة...';
  fetch('api/zk_handler.php?action=verify_fingerprint').then(r=>r.json()).then(d=>{
    a.classList.remove('scan');
    if(d.status==='success'){t.textContent='✅ '+d.message;setTimeout(()=>location.href=d.redirect,1000);}
    else{t.textContent='❌ '+d.message;setTimeout(()=>t.textContent='ضع إصبعك على ZKTeco',2500);}
  });}
function tc(){document.getElementById('cf').style.display=document.getElementById('ns').value==='غير ذلك'?'block':'none';}
function tg(m){const b=document.getElementById('b');
  if(m==='s'){b.classList.add('s');document.getElementById('lg').style.display='none';document.getElementById('sg').style.display='block';}
  else{b.classList.remove('s');document.getElementById('lg').style.display='block';document.getElementById('sg').style.display='none';}}
</script></body></html>
EOT;

// =========== dashboard.php ===========
$files['dashboard.php'] = <<<'EOT'
<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
$pid = (int)$_SESSION['patient_id'];
$r = $conn->query("SELECT p.*, hr.* FROM patients p LEFT JOIN health_records hr ON p.id = hr.patient_id WHERE p.id = $pid LIMIT 1");
$patient = $r && $r->num_rows > 0 ? $r->fetch_assoc() : [];
$full_name = htmlspecialchars($patient['full_name'] ?? 'مريض');
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>الملف الصحي</title>
<?php include 'includes/sidebar_styles.php'; ?>
<style>
.gr{padding:35px;display:grid;grid-template-columns:320px 1fr 320px;gap:25px}
.pn{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:25px}
.ph{font-size:11px;color:var(--cyan);text-transform:uppercase;letter-spacing:2px;margin-bottom:20px;font-weight:bold}
.rw{background:var(--inner);padding:12px 18px;border-radius:12px;margin-bottom:12px;display:flex;justify-content:space-between;font-size:13px}
.cn{text-align:center}
.st{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:20px}
.sb{background:#000;padding:15px 5px;border-radius:15px;border:1px solid var(--border);text-align:center}
.sb .v{font-size:1.3rem;color:var(--cyan);font-weight:900;display:block}
.sb .l{font-size:10px;color:var(--dim)}
.bp{background:rgba(255,77,109,.1);border:1px solid var(--danger);color:var(--danger);padding:8px 20px;border-radius:50px;display:inline-block;margin-top:15px;font-weight:bold;font-size:13px}
</style></head><body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrapper">
<header class="top-header">
<div style="font-weight:bold">AETHERIS <span style="color:var(--cyan)">CLINICAL</span></div>
<div style="font-size:11px;color:var(--dim)">● سِـمَـه نشط</div>
</header>
<div class="gr">
<div class="pn">
<div class="ph">العلاجات</div>
<div class="rw" style="border-right:3px solid var(--cyan)"><span>Glucophage 500mg</span></div>
<div class="rw" style="border-right:3px solid var(--cyan)"><span>Lisinopril 10mg</span></div>
<div class="ph" style="margin-top:30px">التشخيصات</div>
<div class="rw"><span style="color:var(--dim)">الأمراض:</span><span style="color:var(--cyan)"><?= htmlspecialchars($patient['chronic_diseases'] ?? 'سليم') ?></span></div>
</div>
<div class="pn cn">
<h1 style="margin:0;font-weight:900"><?= $full_name ?></h1>
<div style="font-size:11px;color:var(--dim);margin-top:10px">ID: <?= htmlspecialchars($patient['national_id'] ?? '-') ?></div>
<img src="assets/images/heart.png" style="width:100%;max-width:300px;mix-blend-mode:screen;margin:20px 0" onerror="this.style.display='none'">
<div class="st">
<div class="sb"><span class="v">98%</span><span class="l">SPO2</span></div>
<div class="sb"><span class="v">36.7</span><span class="l">TEMP</span></div>
<div class="sb"><span class="v">72</span><span class="l">BPM</span></div>
</div>
<div class="bp">ضغط: 120/80</div>
</div>
<div class="pn">
<div class="ph">المؤشرات</div>
<div class="rw"><span style="color:var(--dim)">فصيلة الدم:</span><span style="color:var(--cyan)"><?= htmlspecialchars($patient['blood_type'] ?? '-') ?></span></div>
<div class="rw"><span style="color:var(--dim)">الحساسية:</span><span style="color:var(--danger)"><?= htmlspecialchars($patient['allergies'] ?? 'لا يوجد') ?></span></div>
<div class="ph" style="margin-top:30px">البصمة</div>
<div class="rw"><span style="color:var(--dim)">ZKTeco:</span><span style="color:<?= !empty($patient['fingerprint_enrolled']) ? 'var(--cyan)' : 'var(--danger)' ?>"><?= !empty($patient['fingerprint_enrolled']) ? 'مرتبطة ✓' : 'غير مرتبطة' ?></span></div>
</div>
</div></div></body></html>
EOT;

// =========== digital_tests.php ===========
$files['digital_tests.php'] = <<<'EOT'
<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
$pid = (int)$_SESSION['patient_id'];
if (isset($_FILES['mf']) && $_FILES['mf']['error'] == 0) {
    if (!file_exists("uploads")) mkdir("uploads", 0777, true);
    $name = basename($_FILES["mf"]["name"]);
    $path = "uploads/" . time() . "_" . $name;
    if (move_uploaded_file($_FILES["mf"]["tmp_name"], $path)) {
        $stmt = $conn->prepare("INSERT INTO patient_files (patient_id, file_path, file_name) VALUES (?,?,?)");
        $stmt->bind_param("iss", $pid, $path, $name); $stmt->execute();
        header("Location: digital_tests.php"); exit;
    }
}
$pat = $conn->query("SELECT full_name FROM patients WHERE id=$pid")->fetch_assoc();
$full_name = htmlspecialchars($pat['full_name'] ?? 'مريض');
$res_a = $conn->query("SELECT * FROM analyses WHERE patient_id=$pid ORDER BY test_date DESC");
$res_f = $conn->query("SELECT * FROM patient_files WHERE patient_id=$pid ORDER BY uploaded_at DESC");
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>التحاليل</title>
<?php include 'includes/sidebar_styles.php'; ?>
<style>
.gr{display:grid;grid-template-columns:1.8fr 1fr;gap:20px;padding:25px}
.cd{background:var(--card);padding:25px;border-radius:20px;border:1px solid var(--border)}
table{width:100%;border-collapse:collapse}
th{text-align:right;color:var(--dim);padding:15px;border-bottom:1px solid var(--border);font-size:13px}
td{padding:15px;border-bottom:1px solid var(--border);font-size:14px}
.bg{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:bold}
.bo{background:rgba(0,255,136,.1);color:var(--success)}
.ba{background:rgba(255,77,109,.1);color:var(--danger)}
.up{border:2px dashed var(--border);border-radius:15px;padding:20px;text-align:center;cursor:pointer;margin-bottom:20px}
.up:hover{border-color:var(--cyan)}
</style></head><body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrapper">
<header class="top-header"><div style="font-weight:900;color:var(--cyan)">AETHERIS LABS</div></header>
<div class="gr">
<div class="cd">
<div style="font-weight:bold;color:var(--cyan);margin-bottom:20px">نتائج الفحوصات</div>
<table><thead><tr><th>الفحص</th><th>النتيجة</th><th>المعدل</th><th>الحالة</th></tr></thead><tbody>
<?php if($res_a && $res_a->num_rows>0): while($r=$res_a->fetch_assoc()):
  $ok = ($r['result_value']>=$r['normal_min'] && $r['result_value']<=$r['normal_max']);?>
<tr><td><?= htmlspecialchars($r['test_name']) ?></td>
<td style="color:var(--cyan);font-weight:bold"><?= $r['result_value'] ?></td>
<td style="color:var(--dim)"><?= $r['normal_min'].'-'.$r['normal_max'] ?></td>
<td><span class="bg <?= $ok?'bo':'ba' ?>"><?= $ok?'طبيعي':'تنبيه' ?></span></td></tr>
<?php endwhile; else: ?><tr><td colspan="4" style="text-align:center;padding:30px;color:var(--dim)">لا توجد بيانات</td></tr><?php endif; ?>
</tbody></table>
</div>
<div class="cd">
<div style="font-weight:bold;margin-bottom:15px">رفع وثيقة</div>
<form method="POST" enctype="multipart/form-data" id="uf">
<div class="up" onclick="document.getElementById('mf').click()">📎 انقر للرفع
<input type="file" name="mf" id="mf" style="display:none" onchange="document.getElementById('uf').submit()">
</div></form>
<div style="max-height:300px;overflow-y:auto">
<?php if($res_f) while($f=$res_f->fetch_assoc()): ?>
<div style="display:flex;justify-content:space-between;background:#020405;padding:10px;border-radius:8px;margin-bottom:5px;font-size:12px;border:1px solid var(--border)">
<span><?= htmlspecialchars($f['file_name']) ?></span>
<a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank" style="color:var(--cyan)">عرض</a>
</div><?php endwhile; ?>
</div>
</div></div></div></body></html>
EOT;

// =========== history.php ===========
$files['history.php'] = <<<'EOT'
<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
$pid = (int)$_SESSION['patient_id'];
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $g = $conn->real_escape_string($_POST['genetic'] ?? '');
    $s = $conn->real_escape_string($_POST['surgeries'] ?? '');
    $n = $conn->real_escape_string($_POST['notes'] ?? '');
    $exists = $conn->query("SELECT id FROM health_records WHERE patient_id=$pid")->num_rows;
    if ($exists) $conn->query("UPDATE health_records SET genetic_info='$g', past_surgeries='$s', notes='$n' WHERE patient_id=$pid");
    else $conn->query("INSERT INTO health_records (patient_id, genetic_info, past_surgeries, notes) VALUES ($pid,'$g','$s','$n')");
    header("Location: history.php?ok=1"); exit;
}
$r = $conn->query("SELECT p.*, hr.* FROM patients p LEFT JOIN health_records hr ON p.id=hr.patient_id WHERE p.id=$pid LIMIT 1");
$patient = $r && $r->num_rows ? $r->fetch_assoc() : [];
$full_name = htmlspecialchars($patient['full_name'] ?? 'مريض');
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>السجلات</title>
<?php include 'includes/sidebar_styles.php'; ?>
<style>
.cn{padding:40px}.gr{display:grid;grid-template-columns:1fr 1fr;gap:25px}
.pn{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:25px}
.ph{font-size:13px;color:var(--cyan);margin-bottom:20px;font-weight:bold;letter-spacing:2px}
label{display:block;color:var(--dim);font-size:12px;margin-bottom:8px}
textarea{width:100%;background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:15px;color:var(--text);font-size:14px;resize:none;margin-bottom:20px}
.btn{background:var(--cyan);color:#000;border:none;padding:12px 30px;border-radius:10px;font-weight:bold;cursor:pointer;width:100%;max-width:400px}
</style></head><body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrapper">
<header class="top-header">
<div style="font-weight:bold">AETHERIS <span style="color:var(--cyan)">LOGS</span></div>
<?php if(isset($_GET['ok'])): ?><div style="color:var(--success)">✅ تم الحفظ</div><?php endif; ?>
</header>
<div class="cn"><form method="POST"><div class="gr">
<div class="pn">
<div class="ph">التاريخ المرضي</div>
<label>الأمراض الوراثية</label><textarea name="genetic" rows="3"><?= htmlspecialchars($patient['genetic_info'] ?? '') ?></textarea>
<label>العمليات السابقة</label><textarea name="surgeries" rows="3"><?= htmlspecialchars($patient['past_surgeries'] ?? '') ?></textarea>
<label>ملاحظات</label><textarea name="notes" rows="4"><?= htmlspecialchars($patient['notes'] ?? '') ?></textarea>
</div>
<div class="pn">
<div class="ph">معلومات إضافية</div>
<label>فصيلة الدم</label><textarea readonly rows="1"><?= htmlspecialchars($patient['blood_type'] ?? '-') ?></textarea>
<label>الحساسية</label><textarea readonly rows="2"><?= htmlspecialchars($patient['allergies'] ?? '-') ?></textarea>
<label>الأمراض المزمنة</label><textarea readonly rows="2"><?= htmlspecialchars($patient['chronic_diseases'] ?? '-') ?></textarea>
</div></div>
<div style="text-align:center;margin-top:25px"><button type="submit" class="btn">حفظ</button></div>
</form></div></div></body></html>
EOT;

// =========== settings.php ===========
$files['settings.php'] = <<<'EOT'
<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
$pid = (int)$_SESSION['patient_id'];
$msg = "";
if ($_SERVER['REQUEST_METHOD']=='POST') {
    if (isset($_POST['up'])) {
        $n = $conn->real_escape_string($_POST['fn']);
        $p = $conn->real_escape_string($_POST['ph']);
        $conn->query("UPDATE patients SET full_name='$n', phone='$p' WHERE id=$pid");
        $msg = "تم التحديث";
    }
    if (isset($_POST['ps'])) {
        $np = $conn->real_escape_string($_POST['np']);
        if($np) { $conn->query("UPDATE patients SET password='$np' WHERE id=$pid"); $msg = "تم تحديث كلمة المرور"; }
    }
    if (isset($_POST['ae'])) {
        $en = $conn->real_escape_string($_POST['en']);
        $er = $conn->real_escape_string($_POST['er']);
        $ep = $conn->real_escape_string($_POST['ep']);
        $conn->query("INSERT INTO emergency_contacts (patient_id, contact_name, relationship, contact_phone) VALUES ($pid,'$en','$er','$ep')");
        $msg = "تمت الإضافة";
    }
}
$pat = $conn->query("SELECT * FROM patients WHERE id=$pid")->fetch_assoc() ?: [];
$full_name = htmlspecialchars($pat['full_name'] ?? 'مريض');
$contacts = $conn->query("SELECT * FROM emergency_contacts WHERE patient_id=$pid");
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>الإعدادات</title>
<?php include 'includes/sidebar_styles.php'; ?>
<style>
.cn{padding:40px}
.tb{display:flex;gap:10px;margin-bottom:25px;border-bottom:1px solid var(--border);padding-bottom:10px}
.tbn{background:transparent;border:none;color:var(--dim);padding:10px 20px;cursor:pointer;border-radius:8px;font-weight:bold}
.tbn.act{color:var(--cyan);background:rgba(0,212,255,.1)}
.tc{display:none}.tc.act{display:block}
.pn{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:30px;margin-bottom:25px}
.gr{display:grid;grid-template-columns:1fr 1fr;gap:20px}
label{display:block;color:var(--dim);font-size:12px;margin-bottom:8px}
input,select{width:100%;background:var(--inner);border:1px solid var(--border);border-radius:12px;padding:14px;color:var(--text);margin-bottom:15px}
.btn{background:var(--cyan);color:#000;border:none;padding:14px 30px;border-radius:10px;font-weight:bold;cursor:pointer}
</style></head><body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrapper">
<header class="top-header">
<div style="font-weight:bold">سِـمَـه <span style="color:var(--cyan)">SETTINGS</span></div>
<?php if($msg): ?><div style="color:var(--success)">✅ <?= $msg ?></div><?php endif; ?>
</header>
<div class="cn">
<div class="tb">
<button class="tbn act" onclick="sw('a',event)">الشخصية</button>
<button class="tbn" onclick="sw('b',event)">الأمان</button>
<button class="tbn" onclick="sw('c',event)">الطوارئ</button>
</div>
<div id="a" class="tc act">
<form method="POST" class="pn">
<div style="color:var(--cyan);margin-bottom:20px;font-weight:bold">معلومات الهوية</div>
<div class="gr">
<div><label>الاسم</label><input type="text" name="fn" value="<?= $full_name ?>"></div>
<div><label>الجوال</label><input type="text" name="ph" value="<?= htmlspecialchars($pat['phone'] ?? '') ?>"></div>
</div>
<button type="submit" name="up" class="btn">حفظ</button>
</form>
</div>
<div id="b" class="tc">
<form method="POST" class="pn">
<div style="color:var(--cyan);margin-bottom:20px;font-weight:bold">كلمة المرور</div>
<label>كلمة المرور الجديدة</label><input type="password" name="np" placeholder="جديدة" style="max-width:500px">
<button type="submit" name="ps" class="btn">تحديث</button>
</form>
</div>
<div id="c" class="tc"><div class="pn">
<div style="color:var(--cyan);margin-bottom:20px;font-weight:bold">جهات الطوارئ</div>
<div class="gr">
<?php if($contacts && $contacts->num_rows>0): while($c=$contacts->fetch_assoc()): ?>
<div style="background:var(--inner);padding:20px;border-radius:15px">
<div style="font-weight:bold"><?= htmlspecialchars($c['contact_name']) ?></div>
<div style="font-size:11px;color:var(--cyan)"><?= htmlspecialchars($c['relationship']) ?></div>
<div style="margin-top:10px;color:var(--dim)"><?= htmlspecialchars($c['contact_phone']) ?></div>
</div><?php endwhile; endif; ?>
</div>
<hr style="border-color:var(--border);margin:30px 0">
<form method="POST" class="gr">
<div><label>الاسم</label><input type="text" name="en" required></div>
<div><label>القرابة</label><select name="er" required><option>أب</option><option>أم</option><option>أخ</option><option>أخت</option></select></div>
<div><label>الجوال</label><input type="text" name="ep" required></div>
<div style="display:flex;align-items:flex-end;margin-bottom:15px"><button type="submit" name="ae" class="btn">إضافة</button></div>
</form>
</div></div>
</div></div>
<script>
function sw(id,e){
  document.querySelectorAll('.tc').forEach(t=>t.classList.remove('act'));
  document.querySelectorAll('.tbn').forEach(b=>b.classList.remove('act'));
  document.getElementById(id).classList.add('act');
  e.currentTarget.classList.add('act');
}
</script></body></html>
EOT;

// =========== zk_admin.php ===========
$files['zk_admin.php'] = <<<'EOT'
<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/zk_config.php';
$pid = (int)$_SESSION['patient_id'];
$me = $conn->query("SELECT * FROM patients WHERE id=$pid")->fetch_assoc();
$full_name = htmlspecialchars($me['full_name']);
$logs = $conn->query("SELECT * FROM attendance_logs WHERE patient_id=$pid ORDER BY log_time DESC LIMIT 20");
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>ZKTeco</title>
<?php include 'includes/sidebar_styles.php'; ?>
<style>
.cn{padding:40px;max-width:900px;width:100%}
.cd{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:30px;margin-bottom:20px}
button{background:var(--cyan);color:#000;border:none;padding:14px 25px;border-radius:8px;font-weight:bold;cursor:pointer;margin:5px}
.lg{background:var(--inner);padding:15px;border-radius:10px;margin-top:15px;max-height:300px;overflow-y:auto;font-family:monospace;font-size:12px;white-space:pre-wrap}
input{padding:12px;background:var(--inner);border:1px solid var(--border);color:#fff;border-radius:8px;margin:5px;width:200px}
.in{background:rgba(0,212,255,.05);border:1px solid var(--cyan);padding:15px;border-radius:10px;margin-bottom:20px;font-size:13px}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;text-align:right;border-bottom:1px solid var(--border)}
</style></head><body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrapper">
<header class="top-header">
<div style="font-weight:bold">🔌 ZKTeco</div>
<div style="font-size:11px;color:var(--dim)">IP: <?= ZK_DEVICE_IP ?>:<?= ZK_DEVICE_PORT ?></div>
</header>
<div class="cn">
<?php if(ZK_SIMULATION_MODE): ?>
<div class="in">🧪 وضع المحاكاة نشط — عدّل <code>includes/zk_config.php</code></div>
<?php endif; ?>
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">معلوماتك</h3>
<p>الاسم: <?= $full_name ?></p>
<p>البصمة: <?= $me['fingerprint_enrolled'] ? '✅ مرتبطة (User ID: '.$me['fingerprint_id'].')' : '❌ غير مرتبطة' ?></p>
</div>
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">إجراءات</h3>
<button onclick="tc()">🔌 اختبار الاتصال</button>
<button onclick="sl()">🔄 مزامنة</button>
<div class="lg" id="lg">جاهز...</div>
</div>
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">ربط البصمة</h3>
<p style="color:var(--dim);font-size:13px">سجل بصمتك على الجهاز ثم أدخل User ID:</p>
<input type="text" id="uid" placeholder="مثلاً: 1">
<button onclick="er()">ربط</button>
</div>
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">آخر السجلات</h3>
<table><tr><th>الوقت</th><th>النوع</th><th>User</th></tr>
<?php if($logs && $logs->num_rows>0): while($l=$logs->fetch_assoc()): ?>
<tr><td><?= $l['log_time'] ?></td><td style="color:var(--cyan)"><?= $l['log_type'] ?></td><td><?= $l['device_user_id'] ?></td></tr>
<?php endwhile; else: ?><tr><td colspan="3" style="text-align:center;color:var(--dim)">لا توجد</td></tr><?php endif; ?>
</table>
</div>
</div></div>
<script>
const lg=m=>{const l=document.getElementById('lg');l.innerHTML+='\n'+new Date().toLocaleTimeString()+' → '+m;l.scrollTop=l.scrollHeight;};
function tc(){fetch('api/zk_handler.php?action=test_connection').then(r=>r.json()).then(d=>lg(d.message));}
function sl(){fetch('api/zk_handler.php?action=sync_logs').then(r=>r.json()).then(d=>lg(d.message));}
function er(){const fd=new FormData();fd.append('device_user_id',document.getElementById('uid').value);
  fetch('api/zk_handler.php?action=enroll_fingerprint',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{lg(d.message);if(d.status==='success')setTimeout(()=>location.reload(),1500);});}
</script></body></html>
EOT;

// =========== index.php ===========
$files['index.php'] = <<<'EOT'
<?php
session_start();
$logged = isset($_SESSION['patient_id']);
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>سِـمَـه</title>
<style>
:root{--bg:#020405;--cyan:#00d4ff;--card:#0b1118;--border:#1a2634;--text:#fff;--dim:#8899a6}
*{box-sizing:border-box}body{background:var(--bg);color:var(--text);font-family:'Segoe UI',sans-serif;margin:0}
.cn{max-width:1200px;margin:0 auto;padding:0 20px}.cy{color:var(--cyan)}
header{border-bottom:1px solid var(--border);padding:20px 0;background:var(--bg);position:sticky;top:0;z-index:1000}
.nv{display:flex;justify-content:space-between;align-items:center}
nav{display:flex;gap:20px}nav a{color:var(--dim);text-decoration:none;font-size:14px;font-weight:600}
nav a:hover{color:var(--cyan)}
.hr{padding:80px 0;display:flex;align-items:center;min-height:80vh;gap:40px}
h1{font-size:60px;font-weight:900;margin:0;line-height:1.1}
.bc,.bo{padding:18px 35px;border-radius:6px;font-weight:900;text-decoration:none;display:inline-block;font-size:14px}
.bc{background:var(--cyan);color:#000;margin-right:10px}
.bo{background:transparent;color:var(--text);border:2px solid var(--cyan)}
.bc:hover{box-shadow:0 0 25px var(--cyan)}
.ht{flex:1.2;display:flex;justify-content:flex-end}
.ht img{width:100%;max-width:500px;mix-blend-mode:screen;animation:fl 6s infinite ease-in-out}
@keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-30px)}}
.lc{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:50px;margin:50px 0;display:flex;gap:40px;align-items:center}
.gr{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:60px}
.it{background:var(--card);border:1px solid var(--border);padding:40px 30px;border-radius:20px;text-align:center}
footer{background:#010203;border-top:1px solid var(--border);padding:60px 0 30px}
.fg{display:grid;grid-template-columns:2fr repeat(3,1fr);gap:30px}
.fc ul{list-style:none;padding:0}.fc li{color:var(--dim);font-size:14px;margin-bottom:10px}
</style></head><body>
<header><div class="cn nv">
<div style="font-weight:bold">AETHERIS <span class="cy">CLINICAL</span></div>
<nav><a href="#hr">الرئيسية</a>
<?php if($logged): ?><a href="dashboard.php">لوحة التحكم</a><a href="logout.php">خروج</a>
<?php else: ?><a href="auth.php">دخول</a><?php endif; ?></nav>
<div style="font-size:12px;color:var(--dim)">● سِـمَـه نشط</div>
</div></header>
<section id="hr" class="cn hr">
<div style="flex:1">
<h1>البصمة <br><span class="cy">الصحيّة الرقمية</span></h1>
<p style="color:var(--dim);max-width:500px;margin:25px 0;font-size:17px;line-height:1.7">نظام يربط هويتك الحيوية بملفك الطبي.</p>
<div>
<a href="<?= $logged ? 'dashboard.php' : 'auth.php' ?>" class="bc"><?= $logged ? 'لوحة التحكم' : 'تسجيل دخول' ?></a>
<a href="infor.php" class="bo">تفعيل البصمة</a>
</div>
</div>
<div class="ht"><img src="assets/images/heart.png" onerror="this.style.display='none'"></div>
</section>
<section class="cn gr">
<div class="it"><div style="font-size:50px;color:var(--cyan)">🛡️</div><h3>تشفير</h3><p style="color:var(--dim);font-size:14px">حماية مطلقة</p></div>
<div class="it"><div style="font-size:50px;color:var(--cyan)">🔒</div><h3>بصمة</h3><p style="color:var(--dim);font-size:14px">ZKTeco متكامل</p></div>
<div class="it"><div style="font-size:50px;color:var(--cyan)">☁️</div><h3>سحابة</h3><p style="color:var(--dim);font-size:14px">تخزين مشفر</p></div>
</section>
<footer><div class="cn fg">
<div><div style="font-weight:bold;font-size:18px">AETHERIS <span class="cy">CLINICAL</span></div></div>
<div class="fc"><h4>روابط</h4><ul><li>من نحن</li></ul></div>
<div class="fc"><h4>الدعم</h4><ul><li>اتصل بنا</li></ul></div>
<div class="fc"><h4>تواصل</h4><ul><li>الرياض</li></ul></div>
</div></footer></body></html>
EOT;

// =========== infor.php ===========
$files['infor.php'] = <<<'EOT'
<?php
require_once 'includes/db.php';
$pid = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$r = $conn->query("SELECT p.*, hr.chronic_diseases, hr.allergies FROM patients p LEFT JOIN health_records hr ON p.id=hr.patient_id WHERE p.id=$pid LIMIT 1");
$patient = $r ? $r->fetch_assoc() : null;
if (!$patient) { die("لا يوجد مريض"); }
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>الكشف</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',sans-serif}
body{background:#05070a;color:#fff;height:100vh;display:flex;justify-content:center;align-items:center;overflow:hidden}
.s{text-align:center}
h1{font-size:42px;font-weight:900;margin-bottom:10px}
.bc{position:relative;width:320px;height:320px;margin:60px auto;display:flex;justify-content:center;align-items:center}
.is{width:240px;height:240px;background:#0b1118;border-radius:50%;border:1px solid rgba(255,255,255,.1);display:flex;justify-content:center;align-items:center;cursor:pointer;font-size:80px;color:#00d4ff}
.btn{padding:14px 35px;border-radius:8px;font-weight:700;text-decoration:none;display:inline-block;margin:0 10px}
.bb{background:#0056b3;color:#fff}
.bd{background:rgba(255,62,62,.1);color:#ff3e3e;border:1px solid rgba(255,62,62,.2)}
</style></head><body>
<div class="s">
<h1>نظام الكشف المنقذ</h1>
<p style="color:#8899a6;margin-bottom:20px">المريض: <?= htmlspecialchars($patient['full_name']) ?></p>
<div class="bc"><div class="is">🔒</div></div>
<div>
<a href="auth.php" class="btn bb">إدخال يدوي</a>
<a href="index.php" class="btn bd">الرئيسية</a>
</div>
</div></body></html>
EOT;

// =========== logout.php ===========
$files['logout.php'] = <<<'EOT'
<?php
session_start();
if(isset($_POST['ok'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    header("Location: auth.php"); exit;
}
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>خروج</title>
<style>
body{background:#020405;color:#fff;font-family:'Segoe UI',sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.c{background:#0b1118;border:1px solid #1a2634;border-radius:20px;padding:50px;text-align:center;max-width:400px}
button{background:transparent;border:1px solid #ff4d6d;color:#ff4d6d;padding:15px 40px;border-radius:8px;font-weight:bold;cursor:pointer;width:100%;margin-top:20px}
button:hover{background:#ff4d6d;color:#000}
a{color:#00d4ff;text-decoration:none;display:block;margin-top:20px}
</style></head><body>
<div class="c">
<h2>🛡️ إنهاء الجلسة</h2>
<p style="color:#8899a6">سيتم فك الارتباط بقاعدة سِـمَـه</p>
<form method="POST"><button type="submit" name="ok">تأكيد الخروج</button></form>
<a href="dashboard.php">← إلغاء</a>
</div></body></html>
EOT;

// =========== Redirect files ===========
$files['profile.php']  = '<?php header("Location: dashboard.php"); exit;';
$files['analysis.php'] = '<?php header("Location: digital_tests.php"); exit;';
$files['save_all.php'] = '<?php header("Location: history.php"); exit;';

// =========== api/check_id.php ===========
$files['api/check_id.php'] = <<<'EOT'
<?php
require_once __DIR__ . '/../includes/db.php';
if (isset($_POST['national_id'])) {
    $id = $conn->real_escape_string($_POST['national_id']);
    $r = $conn->query("SELECT id FROM patients WHERE national_id='$id'");
    echo ($r && $r->num_rows > 0) ? "found" : "not_found";
}
EOT;

// ============================================================
// EXECUTE: Write all files
// ============================================================
echo "<!DOCTYPE html><html><head><style>body{font-family:monospace;background:#000;color:#0f0;padding:20px}.ok{color:#0f0}.er{color:#f00}.in{color:#0ff}h2{color:#fff}</style></head><body>";
echo "<h2>🚀 سِـمَـه Setup</h2>";

// Cleanup conflicts
foreach (['index.html', 'check_id.php'] as $f) {
    if (file_exists($base . '/' . $f)) {
        unlink($base . '/' . $f);
        echo "<div class='in'>✗ Removed: $f</div>";
    }
}

// Create files
$count = 0;
foreach ($files as $relPath => $content) {
    $fullPath = $base . '/' . $relPath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) { mkdir($dir, 0777, true); }
    if (file_put_contents($fullPath, $content) !== false) {
        echo "<div class='ok'>✓ Created: $relPath</div>";
        $count++;
    } else {
        echo "<div class='er'>✗ FAILED: $relPath</div>";
    }
}

// Create uploads dir
if (!is_dir($base . '/uploads')) mkdir($base . '/uploads', 0777, true);
if (!is_dir($base . '/assets/images')) mkdir($base . '/assets/images', 0777, true);

echo "<h2 style='color:#0f0'>✅ DONE! $count files created</h2>";
echo "<p><a href='index.php' style='color:#0ff;font-size:20px'>→ Open App</a></p>";
echo "<p><a href='auth.php' style='color:#0ff;font-size:20px'>→ Login Page</a></p>";
echo "<p style='color:#ff0'>⚠️ IMPORTANT: Delete this setup.php file after use!</p>";
echo "</body></html>";
?>