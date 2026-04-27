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