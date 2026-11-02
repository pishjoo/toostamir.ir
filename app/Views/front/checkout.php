<?php $old = (array)($_SESSION['old_form'] ?? []); unset($_SESSION['old_form']); ?>
<section class="section container">
  <h1>تکمیل سفارش</h1>
  <form method="post" action="/takmil-safarish" class="grid g-sidebar checkout-grid" id="checkoutForm">
    <?= csrf_field() ?>
    <div class="card co-form">
      <h2>اطلاعات گیرنده و ارسال</h2>
      <div class="form-grid">
        <p class="field"><label>نام گیرنده *</label><input required name="name" maxlength="80" value="<?= e((string)($old['name'] ?? '')) ?>"></p>
        <p class="field"><label>موبایل *</label><input required name="mobile" inputmode="numeric" maxlength="15" placeholder="09xxxxxxxxx" value="<?= e((string)($old['mobile'] ?? '')) ?>"></p>
        <p class="field"><label>ایمیل (اختیاری)</label><input type="email" name="email" maxlength="120"></p>
        <p class="field"><label>استان</label><input name="province" maxlength="40" value="<?= e((string)($old['province'] ?? setting('address_province','خراسان رضوی'))) ?>"></p>
        <p class="field"><label>شهر</label><input name="city" maxlength="40" value="<?= e((string)($old['city'] ?? 'مشهد')) ?>"></p>
        <p class="field"><label>کد پستی (۱۰ رقم)</label><input name="postal_code" inputmode="numeric" maxlength="12" value="<?= e((string)($old['postal_code'] ?? '')) ?>"></p>
        <p class="field field-full"><label>آدرس کامل *</label><textarea required name="address" rows="3" maxlength="400"><?= e((string)($old['address'] ?? '')) ?></textarea></p>
        <p class="field field-full"><label>یادداشت سفارش</label><textarea name="notes" rows="2" maxlength="500"></textarea></p>
      </div>
      <h2>روش ارسال</h2>
      <div class="ship-list">
        <?php foreach ($shipping_methods as $i => $sm): ?>
        <label class="chk ship-opt">
          <input type="radio" name="shipping_method_id" value="<?= (int)$sm['id'] ?>" data-cost="<?= (int)$sm['cost'] ?>" <?= $i===0?'checked':'' ?>>
          <?= e($sm['name']) ?> — <?= (int)$sm['cost']>0 ? show_price((int)$sm['cost']).' تومان' : 'رایگان' ?>
        </label>
        <?php endforeach; ?>
      </div>
      <h2>انتخاب درگاه پرداخت</h2>
      <div class="gw-list">
        <?php $any=false; foreach ($gateways as $g): if ($g['gateway_key']==='manual') continue; $any=true; ?>
        <label class="chk gw-opt"><input type="radio" name="gateway" value="<?= e($g['gateway_key']) ?>" data-gw="<?= e($g['gateway_key']) ?>">
          <?= e($g['name']) ?><?php if ((int)$g['is_sandbox']===1): ?> <span class="badge">تست</span><?php endif; ?></label>
        <?php endforeach; ?>
        <label class="chk gw-opt"><input type="radio" name="gateway" value="manual" data-gw="manual" <?= $any?'':'checked' ?>> پرداخت در محل / پس از هماهنگی تلفنی</label>
      </div>
      <button class="btn btn-primary btn-block" type="submit" id="coSubmit">ثبت نهایی سفارش</button>
    </div>

    <aside class="summary-box card">
      <h2>خلاصه سفارش</h2>
      <ul class="sum-items"><?php foreach ($cart['items'] as $it): ?>
        <li><span><?= e($it['name']) ?> (<?= fa_digits((string)$it['quantity']) ?>)</span><b><?= show_price((int)$it['line_total']) ?></b></li>
      <?php endforeach; ?></ul>
      <div class="coupon-row">
        <input id="couponInput" placeholder="کد تخفیف" value="<?= e($couponCode) ?>" maxlength="40">
        <button type="button" class="btn btn-navy btn-sm" id="couponBtn">اعمال</button>
      </div>
      <div class="sum-row"><span>جمع کالاها</span><b id="subRow"><?= show_price($cart['subtotal']) ?> تومان</b></div>
      <div class="sum-row"><span>تخفیف</span><b id="discRow" class="txt-ok">۰ تومان</b></div>
      <div class="sum-row"><span>ارسال</span><b id="shipRow">—</b></div>
      <div class="sum-row sum-total"><span>مبلغ قابل پرداخت</span><b id="totalRow"><?= show_price($cart['subtotal']) ?> تومان</b></div>
      <p class="muted" id="gwNote">پرداخت آنلاین از طریق درگاه‌های معتبر؛ در صورت انتخاب «پرداخت در محل» کارشناس با شما تماس می‌گیرد.</p>
    </aside>
  </form>
</section>
<script>
(function(){
  var CSRF = document.querySelector('input[name=_token]');
  var sub = <?= (int)$cart['subtotal'] ?>, discount = 0, ship = 0;
  var fa = function(n){ return n.toLocaleString('fa-IR'); };
  function fmt(x){ return fa(x) + ' تومان'; }
  function recalc(){
    var s = document.querySelector('input[name=shipping_method_id]:checked');
    ship = s ? parseInt(s.dataset.cost||'0',10) : 0;
    document.getElementById('shipRow').textContent = ship>0 ? fmt(ship) : 'رایگان';
    document.getElementById('discRow').textContent = fmt(discount);
    document.getElementById('totalRow').textContent = fmt(Math.max(0, sub - discount) + ship);
  }
  document.querySelectorAll('input[name=shipping_method_id]').forEach(function(r){ r.addEventListener('change', recalc); });
  document.getElementById('couponBtn').addEventListener('click', function(){
    var fd = new FormData(); fd.append('_token', CSRF.value); fd.append('code', document.getElementById('couponInput').value);
    fetch('/cart/coupon', {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json();}).then(function(j){
        alert(j.msg); if(j.ok){ discount = parseInt(j.discount||'0',10); recalc(); }
      }).catch(function(){ alert('خطا در ارتباط با سرور'); });
  });
  recalc();
})();
</script>
