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