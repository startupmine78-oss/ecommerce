<?php
require_once 'db.php';
require_once 'config/config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }
$pageTitle = 'Нэвтрэх — ShopMN';
include 'includes/header.php';
?>
<div class="auth-wrap" style="min-height:calc(100vh - 160px);display:flex;align-items:center;justify-content:center;padding:40px 20px;background:linear-gradient(135deg,#F4F6F8,#fff8f5);">
<div style="width:100%;max-width:460px;">
  <div style="text-align:center;margin-bottom:28px;">
    <a href="index.php" style="font-family:'Outfit',sans-serif;font-size:2.4rem;font-weight:900;color:var(--secondary);text-decoration:none;">Shop<span style="color:var(--primary);">MN</span></a>
    <p style="color:var(--text-light);margin-top:6px;font-size:0.9rem;">Монголын дэлхийн зах зээл</p>
  </div>
  <div style="background:white;border-radius:20px;padding:36px;box-shadow:0 20px 60px rgba(0,0,0,0.1);">
    <!-- Tabs -->
    <div style="display:grid;grid-template-columns:1fr 1fr;background:var(--bg);border-radius:12px;padding:4px;margin-bottom:28px;">
      <button class="tab-btn active" onclick="switchTab('login')" id="tab-login">Нэвтрэх</button>
      <button class="tab-btn" onclick="switchTab('register')" id="tab-register">Бүртгүүлэх</button>
    </div>

    <!-- LOGIN -->
    <div id="panel-login">
      <div id="login-step1">
        <h2 style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:6px;">Тавтай морил!</h2>
        <p style="color:var(--text-light);font-size:0.88rem;margin-bottom:24px;">Gmail хаягаа оруулж OTP авна уу</p>
        <div class="form-group">
          <label style="font-weight:700;font-size:0.9rem;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
            <img src="https://www.google.com/favicon.ico" width="16" height="16" alt="">Gmail хаяг
          </label>
          <input type="email" id="login-email" class="form-control" placeholder="example@gmail.com" autocomplete="email" onkeydown="if(event.key==='Enter')sendLoginOTP()">
        </div>
        <button onclick="sendLoginOTP()" class="btn-primary" style="width:100%;justify-content:center;padding:14px;" id="btn-send-login-otp">
          <span id="btn-login-otp-text">📨 OTP Код илгээх</span>
        </button>
      </div>
      <div id="login-step2" style="display:none;">
        <button onclick="backToEmail('login')" style="background:none;border:none;cursor:pointer;color:var(--text-light);font-size:0.9rem;margin-bottom:16px;display:flex;align-items:center;gap:6px;">← Буцах</button>
        <div style="text-align:center;margin-bottom:24px;">
          <div style="font-size:2.5rem;margin-bottom:8px;">📩</div>
          <h3 style="font-family:'Outfit',sans-serif;font-weight:800;margin-bottom:6px;">Кодоо оруулна уу</h3>
          <p style="color:var(--text-light);font-size:0.88rem;"><strong id="login-email-display"></strong> руу 6 оронтой код илгээлээ</p>
        </div>
        <div style="display:flex;gap:8px;justify-content:center;margin:24px 0;">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="login-otp-0" oninput="otpInput(this,0,'login-otp')" onkeydown="otpKeydown(event,0,'login-otp')" onpaste="otpPaste(event,0,'login-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="login-otp-1" oninput="otpInput(this,1,'login-otp')" onkeydown="otpKeydown(event,1,'login-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="login-otp-2" oninput="otpInput(this,2,'login-otp')" onkeydown="otpKeydown(event,2,'login-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="login-otp-3" oninput="otpInput(this,3,'login-otp')" onkeydown="otpKeydown(event,3,'login-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="login-otp-4" oninput="otpInput(this,4,'login-otp')" onkeydown="otpKeydown(event,4,'login-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="login-otp-5" oninput="otpInput(this,5,'login-otp')" onkeydown="otpKeydown(event,5,'login-otp')">
        </div>
        <div id="login-dev-hint" style="display:none;background:#FEF9C3;border:1px dashed #F59E0B;border-radius:10px;padding:12px;margin-bottom:14px;text-align:center;font-size:0.85rem;color:#92400E;">
          🛠 Dev mode: Таны код = <strong id="login-dev-code"></strong>
        </div>
        <div style="text-align:center;margin-bottom:14px;font-size:0.88rem;color:var(--text-light);">
          <span id="login-timer-wrap">⏰ <span id="login-timer">10:00</span>-д дуусна</span>
          <button id="login-resend-btn" onclick="sendLoginOTP(true)" style="display:none;background:none;border:none;cursor:pointer;color:var(--primary);font-weight:700;">🔄 Дахин илгээх</button>
        </div>
        <div id="login-otp-error" class="alert alert-error" style="display:none;margin-bottom:12px;"></div>
        <button onclick="verifyLoginOTP()" class="btn-primary" style="width:100%;justify-content:center;padding:14px;" id="btn-verify-login">✅ Баталгаажуулах</button>
      </div>
    </div>

    <!-- REGISTER -->
    <div id="panel-register" style="display:none;">
      <div id="reg-step1">
        <h2 style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:6px;">Шинэ бүртгэл</h2>
        <p style="color:var(--text-light);font-size:0.88rem;margin-bottom:24px;">Gmail хаягаа баталгаажуулна уу</p>
        <div class="form-group">
          <label style="font-weight:700;font-size:0.9rem;display:flex;align-items:center;gap:6px;margin-bottom:8px;">
            <img src="https://www.google.com/favicon.ico" width="16" height="16" alt="">Gmail хаяг
          </label>
          <input type="email" id="reg-email" class="form-control" placeholder="example@gmail.com" onkeydown="if(event.key==='Enter')sendRegOTP()">
        </div>
        <button onclick="sendRegOTP()" class="btn-primary" style="width:100%;justify-content:center;padding:14px;" id="btn-send-reg-otp">📨 OTP Баталгаажуулах</button>
      </div>
      <div id="reg-step2" style="display:none;">
        <button onclick="backToEmail('register')" style="background:none;border:none;cursor:pointer;color:var(--text-light);font-size:0.9rem;margin-bottom:16px;display:flex;align-items:center;gap:6px;">← Буцах</button>
        <div style="text-align:center;margin-bottom:20px;">
          <div style="font-size:2rem;margin-bottom:8px;">📩</div>
          <h3 style="font-family:'Outfit',sans-serif;font-weight:800;margin-bottom:4px;">Gmail баталгаажуулалт</h3>
          <p style="color:var(--text-light);font-size:0.88rem;"><strong id="reg-email-display"></strong></p>
        </div>
        <div style="display:flex;gap:8px;justify-content:center;margin:20px 0;">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="reg-otp-0" oninput="otpInput(this,0,'reg-otp')" onkeydown="otpKeydown(event,0,'reg-otp')" onpaste="otpPaste(event,0,'reg-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="reg-otp-1" oninput="otpInput(this,1,'reg-otp')" onkeydown="otpKeydown(event,1,'reg-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="reg-otp-2" oninput="otpInput(this,2,'reg-otp')" onkeydown="otpKeydown(event,2,'reg-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="reg-otp-3" oninput="otpInput(this,3,'reg-otp')" onkeydown="otpKeydown(event,3,'reg-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="reg-otp-4" oninput="otpInput(this,4,'reg-otp')" onkeydown="otpKeydown(event,4,'reg-otp')">
          <input type="text" maxlength="1" inputmode="numeric" class="otp-box" id="reg-otp-5" oninput="otpInput(this,5,'reg-otp')" onkeydown="otpKeydown(event,5,'reg-otp')">
        </div>
        <div id="reg-dev-hint" style="display:none;background:#FEF9C3;border:1px dashed #F59E0B;border-radius:10px;padding:12px;margin-bottom:12px;text-align:center;font-size:0.85rem;color:#92400E;">
          🛠 Dev: Код = <strong id="reg-dev-code"></strong>
        </div>
        <div style="text-align:center;margin-bottom:14px;font-size:0.88rem;color:var(--text-light);">
          ⏰ <span id="reg-timer">10:00</span>
          <button id="reg-resend-btn" onclick="sendRegOTP(true)" style="display:none;background:none;border:none;cursor:pointer;color:var(--primary);font-weight:700;"> Дахин</button>
        </div>
        <div id="reg-otp-error" class="alert alert-error" style="display:none;margin-bottom:12px;"></div>
        <button onclick="verifyRegOTP()" class="btn-primary" style="width:100%;justify-content:center;padding:14px;">✅ Баталгаажуулах</button>
      </div>
      <div id="reg-step3" style="display:none;">
        <div style="text-align:center;margin-bottom:20px;">
          <div style="font-size:2rem;">🎉</div>
          <h3 style="font-family:'Outfit',sans-serif;font-weight:800;margin-bottom:4px;">Gmail баталгаажлаа!</h3>
          <p style="color:var(--text-light);font-size:0.88rem;">Профайл мэдээллээ бөглөнө үү</p>
        </div>
        <form id="reg-form" onsubmit="submitRegister(event)">
          <input type="hidden" id="reg-verified-email" name="email">
          <div class="form-group">
            <label style="font-weight:700;font-size:0.9rem;">Нэр *</label>
            <input type="text" name="name" class="form-control" placeholder="Бат-Эрдэнэ" required>
          </div>
          <div class="form-group">
            <label style="font-weight:700;font-size:0.9rem;">Нууц үг * (8+ тэмдэгт)</label>
            <div style="position:relative;">
              <input type="password" name="password" id="reg-pass" class="form-control" placeholder="••••••••" required minlength="8">
              <button type="button" onclick="togglePass('reg-pass','eye-reg')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;"><span id="eye-reg">👁</span></button>
            </div>
          </div>
          <div class="form-group">
            <label style="font-weight:700;font-size:0.9rem;">Утас</label>
            <input type="tel" name="phone" class="form-control" placeholder="9911 2233">
          </div>
          <div id="reg-error" class="alert alert-error" style="display:none;margin-bottom:12px;"></div>
          <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px;" id="btn-reg-submit">🚀 Бүртгүүлэх</button>
        </form>
      </div>
    </div>

    <div style="text-align:center;margin-top:20px;font-size:0.82rem;color:var(--text-light);">
      Нэвтрэснээр та манай <a href="#" style="color:var(--primary);">үйлчилгээний нөхцөл</a>-ийг зөвшөөрнө
    </div>
  </div>
</div>
</div>

<style>
.tab-btn{background:none;border:none;padding:10px 16px;border-radius:10px;cursor:pointer;font-weight:700;font-size:0.92rem;transition:all .2s;color:var(--text-light);font-family:'DM Sans',sans-serif;}
.tab-btn.active{background:white;color:var(--text);box-shadow:0 2px 10px rgba(0,0,0,.1);}
.otp-box{width:52px;height:60px;border:2.5px solid var(--border);border-radius:12px;font-size:1.6rem;font-weight:800;text-align:center;outline:none;transition:all .2s;background:#f9f9f9;color:var(--text);}
.otp-box:focus{border-color:var(--primary);background:#fff8f5;transform:scale(1.05);box-shadow:0 0 0 3px rgba(255,107,53,.15);}
.otp-box.filled{border-color:var(--success);background:#f0fff8;}
.otp-box.error{border-color:var(--danger);background:#fff0f0;animation:shake .4s ease;}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
</style>

<script>
var timers={};
function switchTab(t){document.getElementById('panel-login').style.display=t==='login'?'':'none';document.getElementById('panel-register').style.display=t==='register'?'':'none';document.getElementById('tab-login').classList.toggle('active',t==='login');document.getElementById('tab-register').classList.toggle('active',t==='register');}
function otpInput(el,idx,prefix){el.value=el.value.replace(/\D/g,'');if(el.value){el.classList.add('filled');var n=document.getElementById(prefix+'-'+(idx+1));if(n)n.focus();}}
function otpKeydown(e,idx,prefix){if(e.key==='Backspace'&&!e.target.value&&idx>0){var p=document.getElementById(prefix+'-'+(idx-1));if(p){p.value='';p.classList.remove('filled');p.focus();}}}
function otpPaste(e,idx,prefix){e.preventDefault();var d=(e.clipboardData.getData('text').replace(/\D/g,'')).slice(0,6);[...d].forEach(function(c,i){var b=document.getElementById(prefix+'-'+i);if(b){b.value=c;b.classList.add('filled');}});var last=document.getElementById(prefix+'-'+Math.min(d.length,5));if(last)last.focus();}
function getOTPValue(prefix){return Array.from({length:6},function(_,i){return(document.getElementById(prefix+'-'+i)||{value:''}).value;}).join('');}
function clearOTP(prefix){for(var i=0;i<6;i++){var b=document.getElementById(prefix+'-'+i);if(b){b.value='';b.classList.remove('filled','error');}}}
function markOTPError(prefix){for(var i=0;i<6;i++){var b=document.getElementById(prefix+'-'+i);if(b){b.classList.add('error');setTimeout(function(el){return function(){el.classList.remove('error');};}(b),600);}}}
function startTimer(timerId,displayId,seconds,resendId,wrapId){clearInterval(timers[timerId]);var d=document.getElementById(displayId),r=document.getElementById(resendId),w=document.getElementById(wrapId);if(r)r.style.display='none';if(w)w.style.display='';var rem=seconds;function tick(){var m=String(Math.floor(rem/60)).padStart(2,'0'),s=String(rem%60).padStart(2,'0');if(d)d.textContent=m+':'+s;if(rem<=0){clearInterval(timers[timerId]);if(w)w.style.display='none';if(r)r.style.display='';}rem--;}tick();timers[timerId]=setInterval(tick,1000);}
function backToEmail(t){if(t==='login'){document.getElementById('login-step2').style.display='none';document.getElementById('login-step1').style.display='';}else{document.getElementById('reg-step2').style.display='none';document.getElementById('reg-step1').style.display='';}}
function togglePass(id,eyeId){var el=document.getElementById(id);el.type=el.type==='password'?'text':'password';document.getElementById(eyeId).textContent=el.type==='password'?'👁':'🙈';}

function sendLoginOTP(resend){
  var email=document.getElementById('login-email').value.trim();
  if(!email){document.getElementById('login-email').focus();return;}
  var btn=document.getElementById('btn-send-login-otp'),txt=document.getElementById('btn-login-otp-text');
  btn.disabled=true;txt.innerHTML='<span class="spinner"></span> Илгээж байна...';
  fetch('ajax/send_otp.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'email='+encodeURIComponent(email)+'&purpose=login'})
  .then(function(r){return r.json();}).then(function(data){
    btn.disabled=false;txt.innerHTML='📨 OTP Код илгээх';
    if(data.success){
      document.getElementById('login-step1').style.display='none';
      document.getElementById('login-step2').style.display='';
      document.getElementById('login-email-display').textContent=email;
      clearOTP('login-otp');
      var f=document.getElementById('login-otp-0');if(f)f.focus();
      startTimer('lt','login-timer',data.expires_in||600,'login-resend-btn','login-timer-wrap');
      if(data.dev_code){document.getElementById('login-dev-hint').style.display='';document.getElementById('login-dev-code').textContent=data.dev_code;}
      if(typeof Toast!=='undefined')Toast.show('OTP код имэйл рүү илгээлээ! 📧','info');
    }else{if(typeof Toast!=='undefined')Toast.show(data.error||'Алдаа гарлаа','error');}
  }).catch(function(){btn.disabled=false;txt.innerHTML='📨 OTP Код илгээх';});
}

function verifyLoginOTP(){
  var email=document.getElementById('login-email').value.trim();
  var code=getOTPValue('login-otp');
  var errEl=document.getElementById('login-otp-error');
  var btn=document.getElementById('btn-verify-login');
  if(code.length<6){if(typeof Toast!=='undefined')Toast.show('6 оронтой кодоо бүрэн оруулна уу','warning');return;}
  btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Шалгаж байна...';
  fetch('ajax/verify_otp.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'email='+encodeURIComponent(email)+'&code='+code+'&purpose=login'})
  .then(function(r){return r.json();}).then(function(data){
    if(data.success){btn.innerHTML='✅ Амжилттай!';btn.style.background='var(--success)';if(typeof Toast!=='undefined')Toast.show('Нэвтрэлт амжилттай! 🎉','success');setTimeout(function(){window.location.href=data.redirect||'index.php';},800);}
    else{btn.disabled=false;btn.innerHTML='✅ Баталгаажуулах';markOTPError('login-otp');errEl.textContent=data.error;errEl.style.display='flex';}
  });
}

function sendRegOTP(){
  var email=document.getElementById('reg-email').value.trim();
  if(!email)return;
  var btn=document.getElementById('btn-send-reg-otp');
  btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Илгээж байна...';
  fetch('ajax/send_otp.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'email='+encodeURIComponent(email)+'&purpose=register'})
  .then(function(r){return r.json();}).then(function(data){
    btn.disabled=false;btn.innerHTML='📨 OTP Баталгаажуулах';
    if(data.success){
      document.getElementById('reg-step1').style.display='none';
      document.getElementById('reg-step2').style.display='';
      document.getElementById('reg-email-display').textContent=email;
      clearOTP('reg-otp');
      var f=document.getElementById('reg-otp-0');if(f)f.focus();
      startTimer('rt','reg-timer',data.expires_in||600,'reg-resend-btn',null);
      if(data.dev_code){document.getElementById('reg-dev-hint').style.display='';document.getElementById('reg-dev-code').textContent=data.dev_code;}
      if(typeof Toast!=='undefined')Toast.show('Gmail баталгаажуулах код илгээлээ!','info');
    }else{if(typeof Toast!=='undefined')Toast.show(data.error||'Алдаа','error');}
  });
}

function verifyRegOTP(){
  var email=document.getElementById('reg-email').value.trim();
  var code=getOTPValue('reg-otp');
  var errEl=document.getElementById('reg-otp-error');
  var btn=document.querySelector('#reg-step2 .btn-primary');
  if(code.length<6)return;
  btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Шалгаж байна...';
  fetch('ajax/verify_otp.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'email='+encodeURIComponent(email)+'&code='+code+'&purpose=register'})
  .then(function(r){return r.json();}).then(function(data){
    if(data.success){document.getElementById('reg-step2').style.display='none';document.getElementById('reg-step3').style.display='';document.getElementById('reg-verified-email').value=email;if(typeof Toast!=='undefined')Toast.show('Gmail баталгаажлаа! 🎉','success');}
    else{btn.disabled=false;btn.innerHTML='✅ Баталгаажуулах';markOTPError('reg-otp');errEl.textContent=data.error;errEl.style.display='flex';}
  });
}

function submitRegister(e){
  e.preventDefault();
  var form=e.target,data=new FormData(form);
  var btn=document.getElementById('btn-reg-submit'),errEl=document.getElementById('reg-error');
  if(data.get('password').length<8){errEl.textContent='Нууц үг хамгийн багадаа 8 тэмдэгт байх ёстой.';errEl.style.display='flex';return;}
  btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Бүртгэж байна...';
  fetch('ajax/register.php',{method:'POST',body:data})
  .then(function(r){return r.json();}).then(function(res){
    if(res.success){btn.innerHTML='✅ Амжилттай!';btn.style.background='var(--success)';if(typeof Toast!=='undefined')Toast.show('Бүртгэл амжилттай! 🎉','success');setTimeout(function(){window.location.href=res.redirect||'index.php';},1000);}
    else{btn.disabled=false;btn.innerHTML='🚀 Бүртгүүлэх';errEl.textContent=res.error;errEl.style.display='flex';}
  });
}
</script>
<?php include 'includes/footer.php'; ?>