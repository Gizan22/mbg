<div class="page-loader" id="pageLoader">
  <div class="loader-spinner"></div>
  <div class="loader-text">Memuat...</div>
</div>
<div class="top-progress" id="topProgress"></div>
<script>
(function(){
  var loader = document.getElementById('pageLoader');
  var bar = document.getElementById('topProgress');

  function hideLoader(){
    if (loader) loader.classList.add('hide');
    if (bar) {
      bar.classList.add('done');
      bar.style.width = '100%';
      setTimeout(function(){ bar.style.display = 'none'; }, 500);
    }
  }

  // Fake progress while loading
  if (bar) {
    bar.style.width = '30%';
    setTimeout(function(){ bar.style.width = '60%'; }, 120);
    setTimeout(function(){ bar.style.width = '85%'; }, 280);
  }

  if (document.readyState === 'complete') {
    hideLoader();
  } else {
    window.addEventListener('load', function(){
      setTimeout(hideLoader, 180);
    });
    // Safety fallback
    setTimeout(hideLoader, 4000);
  }

  // Show loader when navigating internal links
  document.addEventListener('click', function(e){
    var a = e.target.closest('a');
    if (!a) return;
    var href = a.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || a.target === '_blank') return;
    if (href.startsWith('http') && href.indexOf(location.host) === -1) return;
    if (loader) {
      loader.classList.remove('hide');
      var t = loader.querySelector('.loader-text');
      if (t) t.textContent = 'Memuat halaman...';
    }
    if (bar) {
      bar.style.display = 'block';
      bar.classList.remove('done');
      bar.style.width = '15%';
      bar.style.opacity = '1';
    }
  });

  // Button loading on form submit
  document.addEventListener('submit', function(e){
    var form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    if (e.defaultPrevented) return;
    var btn = form.querySelector('[type="submit"], .btn-primary, .btn-login');
    if (btn) btn.classList.add('loading');
    if (loader) {
      // only show full overlay for non-modal forms
      if (!form.closest('.modal')) {
        loader.classList.remove('hide');
        var t = loader.querySelector('.loader-text');
        if (t) t.textContent = 'Memproses...';
      }
    }
  });

  window.showLoader = function(msg){
    if (loader) {
      loader.classList.remove('hide');
      var t = loader.querySelector('.loader-text');
      if (t) t.textContent = msg || 'Memuat...';
    }
  };
  window.hideLoader = hideLoader;
})();
</script>
