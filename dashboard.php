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