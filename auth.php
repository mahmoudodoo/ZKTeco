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