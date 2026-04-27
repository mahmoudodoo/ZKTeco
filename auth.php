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
:root{--bg:#020405;--cyan:#00d4ff;--card:#0b1118;--border:#1a2634;--text:#fff;--dim:#8899a6;--danger:#ff4d6d;--success:#00ff88}
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
.btn:disabled{background:#333;color:#666;cursor:not-allowed}
.fp{margin-top:20px;padding:25px;border:2px dashed var(--border);border-radius:15px;cursor:pointer;color:var(--dim);transition:.3s;position:relative;overflow:hidden}
.fp.ready{border-color:var(--cyan);color:var(--cyan)}
.fp.scanning{border-color:var(--cyan);color:var(--cyan);animation:pulse 1.5s infinite}
.fp.disabled{cursor:not-allowed;opacity:.5}
.fp.success{border-color:var(--success);color:var(--success)}
.fp.error{border-color:var(--danger);color:var(--danger)}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(0,212,255,.4)}50%{box-shadow:0 0 0 15px rgba(0,212,255,0)}}
.fp-icon{font-size:48px;margin-bottom:10px}
.tg{color:var(--cyan);cursor:pointer;text-decoration:underline}
.al{padding:10px;border-radius:8px;margin-bottom:15px;font-size:13px}
.ae{background:rgba(255,77,109,.1);color:var(--danger);border:1px solid var(--danger)}
.ao{background:rgba(0,255,136,.1);color:var(--success);border:1px solid var(--success)}
#zk{font-size:12px;padding:5px 12px;border-radius:20px;display:flex;align-items:center;gap:5px}
.on{background:rgba(0,255,136,.1);color:var(--success)}
.off{background:rgba(255,77,109,.1);color:var(--danger)}
.spin{display:inline-block;width:8px;height:8px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:sp 1s linear infinite}
@keyframes sp{to{transform:rotate(360deg)}}
.timer{font-size:11px;color:var(--dim);margin-top:8px}
.cancel-btn{margin-top:10px;background:transparent;border:1px solid var(--danger);color:var(--danger);padding:8px 20px;border-radius:8px;font-size:12px;cursor:pointer}
</style></head><body id="b">
<header><div class="cont">
<div style="font-weight:900">AETHERIS <span class="cyan">CLINICAL</span></div>
<div id="zk" class="off">● فحص الجهاز...</div>
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
<div style="margin:20px 0;color:var(--dim);font-size:12px">— أو استخدم البصمة —</div>
<div class="fp disabled" id="fp" onclick="startBio()">
<div class="fp-icon" id="fp-icon">🔒</div>
<div id="ft" style="font-weight:bold;font-size:14px">جهاز ZKTeco غير متصل</div>
<div class="timer" id="timer"></div>
</div>
<button class="cancel-btn" id="cancelBtn" style="display:none" onclick="cancelScan()">إلغاء</button>
</div>
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
let deviceOnline = false;
let pollInterval = null;
let countdownInterval = null;

// Check device on load
async function checkDevice() {
    try {
        const r = await fetch('api/zk_handler.php?action=test_connection');
        const d = await r.json();
        const t = document.getElementById('zk');
        const fp = document.getElementById('fp');
        const ft = document.getElementById('ft');
        
        if (d.status === 'success') {
            deviceOnline = true;
            t.className = 'on';
            t.innerHTML = '● ZKTeco متصل';
            fp.classList.remove('disabled');
            fp.classList.add('ready');
            ft.textContent = 'اضغط واتبع التعليمات';
        } else {
            deviceOnline = false;
            t.className = 'off';
            t.innerHTML = '● ZKTeco غير متصل';
            fp.classList.add('disabled');
            fp.classList.remove('ready');
            ft.textContent = 'جهاز ZKTeco غير متصل';
        }
    } catch (e) {
        deviceOnline = false;
    }
}

// Auto-recheck every 10 seconds
checkDevice();
setInterval(checkDevice, 10000);

function vr(){
    const v=document.getElementById('ic').value;
    if(v.length<10){alert('10 أرقام');return;}
    document.getElementById('p1').style.display='none';
    document.getElementById('p2').style.display='block';
    document.getElementById('ci').value=v;
}

async function startBio() {
    if (!deviceOnline) {
        alert('الجهاز غير متصل. يرجى التأكد من توصيل ZKTeco.');
        return;
    }
    
    const fp = document.getElementById('fp');
    const ft = document.getElementById('ft');
    const icon = document.getElementById('fp-icon');
    const timer = document.getElementById('timer');
    const cancelBtn = document.getElementById('cancelBtn');
    
    fp.classList.remove('ready', 'error', 'success');
    fp.classList.add('scanning');
    icon.textContent = '👆';
    cancelBtn.style.display = 'inline-block';
    
    // Start scan session
    try {
        const startResp = await fetch('api/zk_handler.php?action=start_fp_login');
        const startData = await startResp.json();
        
        if (startData.status === 'error') {
            ft.textContent = '❌ ' + startData.message;
            fp.classList.remove('scanning');
            fp.classList.add('error');
            cancelBtn.style.display = 'none';
            setTimeout(() => resetFP(), 3000);
            return;
        }
        
        ft.textContent = '👆 ضع إصبعك على ZKTeco...';
        
        let secondsLeft = 30;
        timer.textContent = `الوقت المتبقي: ${secondsLeft}ث`;
        countdownInterval = setInterval(() => {
            secondsLeft--;
            timer.textContent = `الوقت المتبقي: ${secondsLeft}ث`;
        }, 1000);
        
        // Poll for scan
        pollInterval = setInterval(async () => {
            try {
                const r = await fetch('api/zk_handler.php?action=check_fp_login');
                const d = await r.json();
                
                if (d.status === 'success') {
                    stopPolling();
                    icon.textContent = '✅';
                    fp.classList.remove('scanning');
                    fp.classList.add('success');
                    ft.textContent = d.message;
                    timer.textContent = '';
                    cancelBtn.style.display = 'none';
                    setTimeout(() => location.href = d.redirect, 1200);
                } else if (d.status === 'not_registered') {
                    stopPolling();
                    icon.textContent = '⚠️';
                    fp.classList.remove('scanning');
                    fp.classList.add('error');
                    ft.textContent = d.message;
                    cancelBtn.style.display = 'none';
                    setTimeout(() => resetFP(), 4000);
                } else if (d.status === 'timeout') {
                    stopPolling();
                    icon.textContent = '⏰';
                    fp.classList.remove('scanning');
                    fp.classList.add('error');
                    ft.textContent = d.message;
                    cancelBtn.style.display = 'none';
                    setTimeout(() => resetFP(), 3000);
                } else if (d.status === 'error') {
                    stopPolling();
                    fp.classList.remove('scanning');
                    fp.classList.add('error');
                    ft.textContent = '❌ ' + d.message;
                    cancelBtn.style.display = 'none';
                    setTimeout(() => resetFP(), 3000);
                }
                // 'waiting' = keep polling
            } catch (e) {
                console.error(e);
            }
        }, 1500);
        
    } catch (e) {
        fp.classList.remove('scanning');
        fp.classList.add('error');
        ft.textContent = '❌ خطأ في الاتصال';
    }
}

function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
}

function cancelScan() {
    stopPolling();
    fetch('api/zk_handler.php?action=cancel_scan');
    resetFP();
}

function resetFP() {
    const fp = document.getElementById('fp');
    const icon = document.getElementById('fp-icon');
    const ft = document.getElementById('ft');
    const timer = document.getElementById('timer');
    fp.classList.remove('scanning', 'error', 'success');
    fp.classList.add('ready');
    icon.textContent = '🔒';
    ft.textContent = 'اضغط واتبع التعليمات';
    timer.textContent = '';
    document.getElementById('cancelBtn').style.display = 'none';
}

function tc(){document.getElementById('cf').style.display=document.getElementById('ns').value==='غير ذلك'?'block':'none';}
function tg(m){const b=document.getElementById('b');
  if(m==='s'){b.classList.add('s');document.getElementById('lg').style.display='none';document.getElementById('sg').style.display='block';}
  else{b.classList.remove('s');document.getElementById('lg').style.display='block';document.getElementById('sg').style.display='none';}}
</script></body></html>