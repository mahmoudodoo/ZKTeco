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