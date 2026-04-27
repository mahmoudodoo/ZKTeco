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