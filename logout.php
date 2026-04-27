<?php
session_start();
if(isset($_POST['ok'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    header("Location: auth.php"); exit;
}
?>
<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>خروج</title>
<style>
body{background:#020405;color:#fff;font-family:'Segoe UI',sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.c{background:#0b1118;border:1px solid #1a2634;border-radius:20px;padding:50px;text-align:center;max-width:400px}
button{background:transparent;border:1px solid #ff4d6d;color:#ff4d6d;padding:15px 40px;border-radius:8px;font-weight:bold;cursor:pointer;width:100%;margin-top:20px}
button:hover{background:#ff4d6d;color:#000}
a{color:#00d4ff;text-decoration:none;display:block;margin-top:20px}
</style></head><body>
<div class="c">
<h2>🛡️ إنهاء الجلسة</h2>
<p style="color:#8899a6">سيتم فك الارتباط بقاعدة سِـمَـه</p>
<form method="POST"><button type="submit" name="ok">تأكيد الخروج</button></form>
<a href="dashboard.php">← إلغاء</a>
</div></body></html>