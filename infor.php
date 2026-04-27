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