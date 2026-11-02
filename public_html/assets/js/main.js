/* توس تعمیر — اسکریپت اصلی سمت کاربر */
(function () {
  'use strict';

  /* منوی موبایل */
  var navBtn = document.querySelector('[data-nav-btn]');
  var nav = document.getElementById('mainNav');
  if (navBtn && nav) {
    navBtn.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      navBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* بازگشت به بالا */
  var toTop = document.getElementById('toTop');
  if (toTop) {
    window.addEventListener('scroll', function () {
      toTop.classList.toggle('show', window.scrollY > 500);
    }, { passive: true });
    toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  /* شمارنده سبد خرید — مقدار توسط PHP رندر می‌شود (no extra request) */
  function toFa(s) { return String(s).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[+d]; }); }

  /* افزودن به سبد (AJAX) */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-add-cart]');
    if (!btn) return;
    ev.preventDefault();
    var pid = btn.getAttribute('data-product');
    var qtyEl = document.querySelector('[data-qty-for="' + pid + '"]');
    var qty = qtyEl ? parseInt(qtyEl.value || '1', 10) : 1;
    var token = btn.getAttribute('data-csrf');
    btn.disabled = true;
    var old = btn.textContent;
    btn.textContent = 'در حال افزودن…';
    var body = new URLSearchParams({ product_id: pid, qty: isNaN(qty) ? '1' : String(qty), _token: token });
    fetch('/cart/add', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: body })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
      .then(function (res) {
        if (res.j && res.j.ok) {
          btn.textContent = 'به سبد اضافه شد ✓';
          if (typeof res.j.count !== 'undefined') {
            var el = document.querySelector('[data-cart-count]');
            if (el) { el.textContent = toFa(String(res.j.count)); el.style.display = 'grid'; }
          }
          setTimeout(function () { btn.textContent = old; btn.disabled = false; }, 1600);
        } else {
          btn.textContent = (res.j && res.j.msg) ? res.j.msg : 'خطا در افزودن';
          setTimeout(function () { btn.textContent = old; btn.disabled = false; }, 2200);
        }
      })
      .catch(function () { btn.textContent = 'خطای ارتباط'; setTimeout(function () { btn.textContent = old; btn.disabled = false; }, 2000); });
  });

  /* گالری محصول */
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('.thumbs img');
    if (!t) return;
    var main = document.getElementById('galleryMain');
    if (main) { main.src = t.src; main.alt = t.alt || main.alt; }
    document.querySelectorAll('.thumbs img').forEach(function (i) { i.classList.remove('active'); });
    t.classList.add('active');
  });

  /* ثبت نظر (AJAX) */
  document.addEventListener('submit', function (ev) {
    var f = ev.target.closest('[data-comment-form]');
    if (!f) return;
    ev.preventDefault();
    var status = f.querySelector('[data-comment-status]');
    var btn = f.querySelector('button[type="submit"]');
    btn.disabled = true;
    var body = new FormData(f);
    fetch('/comment/store', { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (status) { status.textContent = j.msg || ''; status.className = 'flash flash-' + (j.ok ? 'success' : 'error'); }
        if (j.ok) f.reset();
      })
      .catch(function () { if (status) status.textContent = 'خطای ارتباط با سرور.'; })
      .finally(function () { btn.disabled = false; });
  });

  /* اعمال فیلترهای فروشگاه (فرم خودکار) */
  document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form && el.form.submit(); });
  });

  /* تأیید حذف */
  document.addEventListener('submit', function (ev) {
    var f = ev.target.matches('[data-confirm]') ? ev.target : ev.target.closest('form[data-confirm]');
    if (f && !window.confirm(f.getAttribute('data-confirm'))) ev.preventDefault();
  });

  /* تبدیل ارقام فارسی در ورودی‌های عددی/موبایل هنگام ارسال */
  document.addEventListener('focusin', function (ev) {
    var i = ev.target;
    if (i.matches && i.matches('input[inputmode="numeric"], input[name="mobile"], input[name="postal_code"]')) {
      i.value = i.value.replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); });
    }
  });
})();
