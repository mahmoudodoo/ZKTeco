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