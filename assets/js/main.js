// DiligentWorks JS
(function(){
  const navToggle = document.querySelector('.nav-toggle');
  const navList = document.querySelector('.nav-list');
  if(navToggle && navList){
    navToggle.addEventListener('click', ()=>{
      const open = navList.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', String(open));
    });
  }
  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click', e=>{
      const id = a.getAttribute('href');
      if(id.length>1){
        const el = document.querySelector(id);
        if(el){
          e.preventDefault();
          el.scrollIntoView({behavior:'smooth', block:'start'});
          navList && navList.classList.remove('open');
          navToggle && navToggle.setAttribute('aria-expanded','false');
        }
      }
    });
  });
  // Year
  const yearEl = document.getElementById('year');
  if(yearEl){ yearEl.textContent = new Date().getFullYear(); }
  // Form timestamp (basic anti-bot)
  const ts = document.getElementById('_form_ts');
  if(ts){ ts.value = String(Date.now()); }

  // Contact form submit (AJAX with graceful fallback)
  const form = document.getElementById('contact-form');
  const status = document.getElementById('form-status');
  if(form){
    form.addEventListener('submit', async (e)=>{
      // Try AJAX submit; if it fails, let normal submit proceed.
      if(window.fetch){
        e.preventDefault();
        status && (status.textContent = 'Sending…');
        status && status.classList.remove('success','error');
        try{
          const data = new FormData(form);
          const res = await fetch(form.action, {method:'POST', body:data, headers:{'Accept':'application/json'}});
          const isJson = (res.headers.get('content-type')||'').includes('application/json');
          const payload = isJson ? await res.json() : null;
          if(res.ok && payload && payload.success){
            status && (status.textContent = payload.message || 'Thanks! We will be in touch.');
            status && status.classList.add('success');
            form.reset();
          }else{
            status && (status.textContent = (payload && payload.message) || 'Something went wrong. Please try again or email us.');
            status && status.classList.add('error');
          }
        }catch(err){
          status && (status.textContent = 'Network error. Please try again.');
          status && status.classList.add('error');
        }
      }
    });
  }
})();
