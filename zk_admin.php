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
.btn{background:var(--cyan);color:#000;border:none;padding:14px 25px;border-radius:8px;font-weight:bold;cursor:pointer;margin:5px;font-family:inherit;font-size:14px}
.btn:disabled{background:#333;color:#666;cursor:not-allowed}
.btn-danger{background:var(--danger);color:#fff}
.btn-secondary{background:transparent;color:var(--cyan);border:1px solid var(--cyan)}
.btn-big{padding:20px 40px;font-size:16px;width:100%}
.lg{background:var(--inner);padding:15px;border-radius:10px;margin-top:15px;max-height:300px;overflow-y:auto;font-family:monospace;font-size:12px;white-space:pre-wrap;line-height:1.6}
input{padding:12px;background:var(--inner);border:1px solid var(--border);color:#fff;border-radius:8px;margin:5px;width:200px;font-family:inherit}
.in{background:rgba(0,212,255,.05);border:1px solid var(--cyan);padding:15px;border-radius:10px;margin-bottom:20px;font-size:13px}
.warn{background:rgba(255,165,0,.05);border:1px solid orange;padding:15px;border-radius:10px;margin-bottom:20px;font-size:13px;color:orange}
.ok{background:rgba(0,255,136,.05);border:1px solid var(--success);padding:15px;border-radius:10px;margin-bottom:20px;font-size:13px;color:var(--success)}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;text-align:right;border-bottom:1px solid var(--border)}
.scan-area{padding:30px;border:2px dashed var(--border);border-radius:15px;text-align:center;margin:20px 0}
.scan-area.active{border-color:var(--cyan);animation:pulse 1.5s infinite}
.scan-area.success{border-color:var(--success)}
.scan-area.error{border-color:var(--danger)}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(0,212,255,.4)}50%{box-shadow:0 0 0 20px rgba(0,212,255,0)}}
.dot{display:inline-block;width:12px;height:12px;border-radius:50%;margin-left:8px}
.dot-on{background:var(--success);box-shadow:0 0 8px var(--success)}
.dot-off{background:var(--danger);box-shadow:0 0 8px var(--danger)}
.dot-checking{background:#ffa500;animation:blink 1s infinite}
@keyframes blink{50%{opacity:.3}}
.device-card{background:var(--inner);padding:15px;border-radius:10px;margin:10px 0;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--border)}
.device-card.selected{border-color:var(--success);box-shadow:0 0 15px rgba(0,255,136,.2)}
.spinner{display:inline-block;width:16px;height:16px;border:3px solid rgba(0,212,255,.3);border-top-color:var(--cyan);border-radius:50%;animation:spin 1s linear infinite;vertical-align:middle;margin-left:8px}
@keyframes spin{to{transform:rotate(360deg)}}
.subnet-list{font-family:monospace;font-size:12px;color:var(--dim);background:#000;padding:10px;border-radius:6px;margin:10px 0}
</style></head><body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-wrapper">
<header class="top-header">
<div style="font-weight:bold">🔌 ZKTeco</div>
<div style="font-size:11px;color:var(--dim)">
<span class="dot dot-checking" id="device-dot"></span>
<span id="device-status">جاري التحقق...</span>
</div>
</header>
<div class="cn">

<!-- Network Info Card -->
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">📡 معلومات الشبكة</h3>
<div id="network-info" style="color:var(--dim);font-size:13px">جاري الفحص...</div>
</div>

<!-- Device Discovery Card -->
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">🔍 البحث التلقائي عن الجهاز</h3>
<p style="color:var(--dim);font-size:13px">سيقوم النظام بفحص جميع الشبكات المتصلة (Ethernet, WiFi) للعثور على ZKTeco</p>

<button class="btn btn-big" onclick="discover()" id="discover-btn">
🔍 ابحث عن جهاز ZKTeco الآن
</button>

<div id="discover-result" style="margin-top:20px"></div>
</div>

<!-- Manual IP Entry -->
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">⚙️ إدخال IP يدوياً</h3>
<p>IP الحالي: <code id="current-ip"><?= ZK_DEVICE_IP ?></code></p>
<input type="text" id="manual-ip" placeholder="مثلاً: 192.168.1.201 أو 169.254.1.201">
<button class="btn-secondary btn" onclick="setIP()">حفظ واختبار</button>
<button class="btn-secondary btn" onclick="testConn()">🔌 اختبار الاتصال الحالي</button>
</div>

<!-- Patient Info -->
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">معلوماتك</h3>
<p>الاسم: <?= $full_name ?></p>
<p>البصمة: <?= $me['fingerprint_enrolled'] ? '✅ مرتبطة (User ID: <code>'.$me['fingerprint_id'].'</code>)' : '❌ غير مرتبطة' ?></p>
</div>

<!-- Enrollment -->
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">🔐 ربط البصمة من الجهاز</h3>
<?php if(!$me['fingerprint_enrolled']): ?>
<div class="in">
📋 <strong>الخطوات:</strong>
<ol style="margin:10px 0;padding-right:20px">
<li>تأكد أن الجهاز متصل (النقطة خضراء أعلاه)</li>
<li>سجّل بصمتك على جهاز ZKTeco أولاً (من قائمة الجهاز)</li>
<li>اضغط "بدء الربط" أدناه</li>
<li>ضع إصبعك على المستشعر — سيتم الربط تلقائياً</li>
</ol>
</div>
<div class="scan-area" id="enroll-area">
<div style="font-size:48px;margin-bottom:15px" id="enroll-icon">🔒</div>
<div id="enroll-text" style="font-weight:bold">اضغط لبدء الربط</div>
<div id="enroll-timer" style="font-size:12px;color:var(--dim);margin-top:10px"></div>
</div>
<button class="btn" id="enroll-btn" onclick="startEnroll()" disabled>🚀 بدء عملية الربط</button>
<button class="btn btn-secondary" id="cancel-enroll-btn" onclick="cancelEnroll()" style="display:none">إلغاء</button>
<?php else: ?>
<div class="ok">✅ بصمتك مرتبطة بحسابك بنجاح</div>
<button class="btn btn-danger" onclick="unlinkFP()">🗑️ فصل البصمة</button>
<?php endif; ?>
</div>

<!-- Logs -->
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">📊 آخر السجلات</h3>
<table><tr><th>الوقت</th><th>النوع</th><th>User ID</th></tr>
<?php if($logs && $logs->num_rows>0): while($l=$logs->fetch_assoc()): ?>
<tr><td><?= $l['log_time'] ?></td><td style="color:var(--cyan)"><?= $l['log_type'] ?></td><td><?= $l['device_user_id'] ?></td></tr>
<?php endwhile; else: ?><tr><td colspan="3" style="text-align:center;color:var(--dim)">لا توجد</td></tr><?php endif; ?>
</table>
</div>

<!-- Activity Log -->
<div class="cd">
<h3 style="color:var(--cyan);margin-top:0">📝 سجل العمليات</h3>
<div class="lg" id="lg">جاهز...</div>
</div>

</div></div>

<script>
let deviceOnline = false;
let enrollPoll = null;
let enrollCountdown = null;

const log = m => {
    const l = document.getElementById('lg');
    l.innerHTML += '\n[' + new Date().toLocaleTimeString() + '] ' + m;
    l.scrollTop = l.scrollHeight;
};

async function loadNetworkInfo() {
    try {
        const r = await fetch('api/zk_handler.php?action=network_info');
        const d = await r.json();
        let html = '';
        if (d.subnets && d.subnets.length > 0) {
            html = '<strong>الشبكات النشطة:</strong><div class="subnet-list">';
            d.subnets.forEach(s => {
                const isAPIPA = s.startsWith('169.254');
                html += `${s}.0/24` + (isAPIPA ? ' <span style="color:orange">(Ethernet مباشر)</span>' : '') + '\n';
            });
            html += '</div>';
        } else {
            html = '<span style="color:var(--danger)">❌ لا توجد شبكات نشطة</span>';
        }
        if (!d.sockets_enabled) {
            html += '<div class="warn" style="margin-top:10px">⚠️ PHP sockets غير مفعّل! فعّله من php.ini</div>';
        }
        document.getElementById('network-info').innerHTML = html;
    } catch (e) {
        document.getElementById('network-info').innerHTML = '<span style="color:var(--danger)">خطأ في فحص الشبكة</span>';
    }
}

async function checkDevice() {
    const dot = document.getElementById('device-dot');
    const status = document.getElementById('device-status');
    dot.className = 'dot dot-checking';
    status.textContent = 'جاري التحقق...';
    try {
        const r = await fetch('api/zk_handler.php?action=test_connection');
        const d = await r.json();
        if (d.status === 'success') {
            deviceOnline = true;
            dot.className = 'dot dot-on';
            status.textContent = 'متصل (' + d.ip + ')';
            const eb = document.getElementById('enroll-btn');
            if (eb) eb.disabled = false;
        } else {
            deviceOnline = false;
            dot.className = 'dot dot-off';
            status.textContent = 'غير متصل';
            const eb = document.getElementById('enroll-btn');
            if (eb) eb.disabled = true;
        }
    } catch (e) {
        deviceOnline = false;
        dot.className = 'dot dot-off';
        status.textContent = 'خطأ';
    }
}

async function discover() {
    const btn = document.getElementById('discover-btn');
    const result = document.getElementById('discover-result');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> جاري البحث في كل الشبكات...';
    result.innerHTML = '<div class="in">⏳ يبحث في الشبكة، قد يستغرق 10-20 ثانية...<br>جاري إرسال packets على جميع IPs (1-254) لكل subnet نشط</div>';
    log('🔍 بدء البحث التلقائي عن أجهزة ZKTeco...');
    
    try {
        const r = await fetch('api/zk_handler.php?action=discover');
        const d = await r.json();
        
        if (d.subnets_scanned) {
            log('📡 تم فحص الشبكات: ' + d.subnets_scanned.join(', '));
        }
        
        if (d.status === 'success') {
            log('✅ تم العثور على ' + d.devices.length + ' جهاز');
            d.devices.forEach(ip => log('   → ' + ip));
            
            let html = '<div class="ok">✅ تم العثور على ' + d.devices.length + ' جهاز ZKTeco!</div>';
            d.devices.forEach((ip, i) => {
                const selected = i === 0 ? ' selected' : '';
                html += `<div class="device-card${selected}">
                    <div>
                        <strong>📡 ${ip}</strong>
                        ${i === 0 ? '<span style="color:var(--success);margin-right:10px">✓ تم الحفظ تلقائياً</span>' : ''}
                    </div>
                    <button class="btn-secondary btn" onclick="useThisIP('${ip}')">استخدام هذا</button>
                </div>`;
            });
            result.innerHTML = html;
            document.getElementById('current-ip').textContent = d.saved;
            setTimeout(() => checkDevice(), 1000);
        } else {
            log('❌ ' + d.message);
            result.innerHTML = `<div class="warn">
                <strong>❌ لم يتم العثور على أي جهاز</strong><br><br>
                <strong>الشبكات التي تم فحصها:</strong><br>
                ${(d.subnets_scanned || []).join('<br>')}<br><br>
                <strong>تأكد من:</strong>
                <ul style="margin:10px 0;padding-right:20px">
                    <li>الجهاز مشغّل ومتصل بالكهرباء</li>
                    <li>كابل Ethernet موصول جيداً</li>
                    <li>الجهاز على نفس شبكة الكمبيوتر</li>
                    <li>إذا متصل مباشرة (Ethernet للكمبيوتر)، تأكد أن IP الجهاز في نفس النطاق (169.254.x.x)</li>
                </ul>
                ${d.hint ? '<em>' + d.hint + '</em>' : ''}
            </div>`;
        }
    } catch (e) {
        log('❌ خطأ: ' + e.message);
        result.innerHTML = '<div class="warn">خطأ: ' + e.message + '</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '🔍 ابحث عن جهاز ZKTeco الآن';
    }
}

async function useThisIP(ip) {
    log('💾 حفظ IP: ' + ip);
    const fd = new FormData();
    fd.append('ip', ip);
    const r = await fetch('api/zk_handler.php?action=set_device_ip', {method:'POST', body:fd});
    const d = await r.json();
    log((d.status==='success'?'✅ ':'⚠️ ') + d.message);
    document.getElementById('current-ip').textContent = ip;
    setTimeout(() => checkDevice(), 500);
}

async function testConn() {
    log('🔌 اختبار...');
    const r = await fetch('api/zk_handler.php?action=test_connection');
    const d = await r.json();
    log((d.status==='success'?'✅ ':'❌ ') + d.message);
    checkDevice();
}

async function setIP() {
    const ip = document.getElementById('manual-ip').value.trim();
    if (!ip) return alert('أدخل IP');
    await useThisIP(ip);
}

async function startEnroll() {
    if (!deviceOnline) { alert('الجهاز غير متصل!'); return; }
    const area = document.getElementById('enroll-area');
    const icon = document.getElementById('enroll-icon');
    const text = document.getElementById('enroll-text');
    const timer = document.getElementById('enroll-timer');
    const btn = document.getElementById('enroll-btn');
    const cancelBtn = document.getElementById('cancel-enroll-btn');
    btn.style.display = 'none';
    cancelBtn.style.display = 'inline-block';
    area.classList.add('active');
    icon.textContent = '👆';
    log('🚀 بدء عملية ربط البصمة...');
    
    const startResp = await fetch('api/zk_handler.php?action=start_fp_enroll');
    const startData = await startResp.json();
    if (startData.status === 'error') {
        text.textContent = '❌ ' + startData.message;
        area.classList.remove('active'); area.classList.add('error');
        log('❌ ' + startData.message);
        setTimeout(() => resetEnroll(), 3000);
        return;
    }
    text.textContent = '👆 ضع إصبعك على الجهاز الآن...';
    log('⏳ بانتظار البصمة...');
    
    let seconds = 30;
    timer.textContent = `الوقت المتبقي: ${seconds}ث`;
    enrollCountdown = setInterval(() => { seconds--; timer.textContent = `الوقت المتبقي: ${seconds}ث`; }, 1000);
    
    enrollPoll = setInterval(async () => {
        const r = await fetch('api/zk_handler.php?action=check_fp_enroll');
        const d = await r.json();
        if (d.status === 'success') {
            stopEnrollPolling();
            icon.textContent = '✅';
            area.classList.remove('active'); area.classList.add('success');
            text.textContent = d.message; timer.textContent = '';
            log('✅ ' + d.message);
            cancelBtn.style.display = 'none';
            setTimeout(() => location.reload(), 2000);
        } else if (d.status === 'timeout') {
            stopEnrollPolling(); icon.textContent = '⏰';
            area.classList.remove('active'); area.classList.add('error');
            text.textContent = d.message; log('⏰ ' + d.message);
            setTimeout(() => resetEnroll(), 3000);
        } else if (d.status === 'error') {
            stopEnrollPolling();
            area.classList.remove('active'); area.classList.add('error');
            text.textContent = '❌ ' + d.message; log('❌ ' + d.message);
            setTimeout(() => resetEnroll(), 3000);
        }
    }, 1500);
}

function stopEnrollPolling() {
    if (enrollPoll) { clearInterval(enrollPoll); enrollPoll = null; }
    if (enrollCountdown) { clearInterval(enrollCountdown); enrollCountdown = null; }
}

function cancelEnroll() {
    stopEnrollPolling();
    fetch('api/zk_handler.php?action=cancel_scan');
    log('⛔ تم إلغاء العملية');
    resetEnroll();
}

function resetEnroll() {
    const area = document.getElementById('enroll-area');
    if (!area) return;
    area.classList.remove('active', 'error', 'success');
    document.getElementById('enroll-icon').textContent = '🔒';
    document.getElementById('enroll-text').textContent = 'اضغط لبدء الربط';
    document.getElementById('enroll-timer').textContent = '';
    document.getElementById('enroll-btn').style.display = 'inline-block';
    document.getElementById('cancel-enroll-btn').style.display = 'none';
}

async function unlinkFP() {
    if (!confirm('هل تريد فصل البصمة عن حسابك؟')) return;
    const r = await fetch('api/zk_handler.php?action=unlink_fp');
    const d = await r.json();
    log((d.status==='success'?'✅ ':'❌ ') + d.message);
    if (d.status === 'success') setTimeout(() => location.reload(), 1000);
}

// Initialize
loadNetworkInfo();
checkDevice();
setInterval(checkDevice, 15000);
</script></body></html>