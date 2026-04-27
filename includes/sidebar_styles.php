<style>
:root{--bg:#020405;--cyan:#00d4ff;--card:#0b1118;--inner:#161f2b;--border:#1a2634;--text:#fff;--dim:#8899a6;--danger:#ff4d6d;--success:#00ff88}
*{box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',sans-serif;margin:0;display:flex;height:100vh;overflow:hidden}
.sidebar{width:280px;background:#010203;border-left:1px solid var(--border);display:flex;flex-direction:column;padding:30px 15px;flex-shrink:0}
.patient-info{text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid var(--border)}
.avatar-box{width:60px;height:60px;background:linear-gradient(45deg,var(--cyan),#005f73);border-radius:12px;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.5rem}
.nav-list{list-style:none;padding:0;flex-grow:1;margin:0}
.nav-item{display:block;padding:14px 18px;color:var(--dim);text-decoration:none;border-radius:8px;margin-bottom:6px;font-size:14px;transition:.3s}
.nav-item:hover,.nav-item.active{background:rgba(0,212,255,.1);color:var(--cyan)}
.logout-btn{color:var(--danger);border-top:1px solid var(--border);padding-top:20px;margin-top:10px}
.main-wrapper{flex-grow:1;display:flex;flex-direction:column;overflow-y:auto}
.top-header{height:75px;padding:0 40px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:rgba(2,4,5,.8);position:sticky;top:0;z-index:10}
button,input,select,textarea{font-family:inherit}
</style>