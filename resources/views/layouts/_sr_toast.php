<style>
#sr-toasts{position:fixed;bottom:24px;right:20px;z-index:999999;display:flex;flex-direction:column;gap:10px;max-width:360px;width:calc(100vw - 40px);pointer-events:none;}
.sr-t{pointer-events:all;display:flex;align-items:flex-start;gap:12px;padding:14px 16px 17px;border-radius:18px;
    background:rgba(255,255,255,.97);border:1px solid rgba(0,0,0,.06);
    box-shadow:0 12px 40px rgba(0,0,0,.13),0 2px 8px rgba(0,0,0,.07);
    backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
    position:relative;overflow:hidden;
    animation:sr-in .38s cubic-bezier(.34,1.56,.64,1) forwards;}
[data-theme="dark"] .sr-t,[data-bs-theme="dark"] .sr-t{background:rgba(15,23,42,.96);border-color:rgba(255,255,255,.08);box-shadow:0 12px 40px rgba(0,0,0,.5),0 2px 8px rgba(0,0,0,.3);}
.sr-t.sr-out{animation:sr-out .28s ease forwards;}
@keyframes sr-in{from{opacity:0;transform:translateX(110%) scale(.9)}to{opacity:1;transform:none}}
@keyframes sr-out{from{opacity:1;transform:none}to{opacity:0;transform:translateX(110%) scale(.88)}}
.sr-t-icon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0;margin-top:1px;}
.sr-t-body{flex:1;min-width:0;}
.sr-t-title{font-size:.8rem;font-weight:800;color:#0f172a;line-height:1.2;margin-bottom:2px;letter-spacing:.01em;}
[data-theme="dark"] .sr-t-title,[data-bs-theme="dark"] .sr-t-title{color:#f1f5f9;}
.sr-t-msg{font-size:.73rem;color:#64748b;line-height:1.45;}
[data-theme="dark"] .sr-t-msg,[data-bs-theme="dark"] .sr-t-msg{color:#94a3b8;}
.sr-t-x{flex-shrink:0;width:22px;height:22px;display:flex;align-items:center;justify-content:center;
    background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.8rem;padding:0;border-radius:7px;margin-top:-1px;}
.sr-t-x:hover{background:rgba(0,0,0,.07);color:#475569;}
[data-theme="dark"] .sr-t-x:hover,[data-bs-theme="dark"] .sr-t-x:hover{background:rgba(255,255,255,.1);}
.sr-t-bar{position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 18px 18px;animation:sr-bar linear forwards;}
@keyframes sr-bar{from{width:100%}to{width:0%}}
.sr-t.sr-success .sr-t-icon{background:rgba(16,185,129,.12);color:#10b981;}
.sr-t.sr-success .sr-t-bar{background:linear-gradient(90deg,#10b981,#34d399);}
.sr-t.sr-error   .sr-t-icon{background:rgba(239,68,68,.12);color:#ef4444;}
.sr-t.sr-error   .sr-t-bar{background:linear-gradient(90deg,#ef4444,#f87171);}
.sr-t.sr-warning .sr-t-icon{background:rgba(245,158,11,.12);color:#f59e0b;}
.sr-t.sr-warning .sr-t-bar{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.sr-t.sr-info    .sr-t-icon{background:rgba(37,99,235,.12);color:#2563eb;}
.sr-t.sr-info    .sr-t-bar{background:linear-gradient(90deg,#2563eb,#3b82f6);}
</style>
<div id="sr-toasts"></div>
<script>
(function(){
  if(window.srToast)return; // already loaded (admin layout inlines it)
  const IC={success:'bi-check-circle-fill',error:'bi-x-circle-fill',warning:'bi-exclamation-triangle-fill',info:'bi-info-circle-fill'};
  const TT={success:'Sucesso',error:'Erro',warning:'Aviso',info:'Info'};
  function show(type,msg,title,ms){
    ms=ms||4000;
    const c=document.getElementById('sr-toasts');
    if(!c)return;
    const el=document.createElement('div');
    el.className='sr-t sr-'+type;
    el.innerHTML=
      '<div class="sr-t-icon"><i class="bi '+(IC[type]||IC.info)+'"></i></div>'+
      '<div class="sr-t-body">'+
        '<div class="sr-t-title">'+(title||TT[type]||'')+'</div>'+
        (msg?'<div class="sr-t-msg">'+msg+'</div>':'')+
      '</div>'+
      '<button class="sr-t-x" onclick="srToastDismiss(this.closest(\'.sr-t\'))"><i class="bi bi-x"></i></button>'+
      '<div class="sr-t-bar" style="animation-duration:'+ms+'ms"></div>';
    c.appendChild(el);
    setTimeout(()=>srToastDismiss(el),ms);
  }
  window.srToastDismiss=function(el){
    if(!el||el.classList.contains('sr-out'))return;
    el.classList.add('sr-out');
    setTimeout(()=>el.remove(),280);
  };
  window.srToast=show;
  window.toastr={options:{},success:(m,t)=>show('success',m,t),error:(m,t)=>show('error',m,t),warning:(m,t)=>show('warning',m,t),info:(m,t)=>show('info',m,t)};
})();
</script>
