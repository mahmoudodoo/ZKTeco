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