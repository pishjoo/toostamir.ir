<?php $cart = $cart; ?>
<section class="section container">
  <h1>سبد خرید</h1>
  <?php if (!$cart['items']): ?>
    <div class="empty card"><h3>سبد خرید شما خالی است</h3>
      <p><a class="btn btn-primary" href="/forushgah">رفتن به فروشگاه قطعات</a></p></div>
  <?php else: ?>
  <form method="post" action="/cart/update" class="cart-wrap grid g-sidebar">
    <?= csrf_field() ?>
    <div class="card cart-items">
      <?php foreach ($cart['items'] as $it): ?>
      <div class="cart-item">
        <label class="ci-check"><input type="checkbox" name="remove[<?= (int)$it['id'] ?>]" value="1"> حذف</label>
        <div class="ci-main">
          <a href="/mahsol/<?= e($it['slug']) ?>"><?= e($it['name']) ?></a>
          <small><?= fa_digits((string)$it['quantity']) ?> × <?= show_price((int)$it['unit_price']) ?> تومان</small>
        </div>
        <p class="field qty-mini"><input type="number" name="items[<?= (int)$it['id'] ?>]" value="<?= (int)$it['quantity'] ?>" min="0" max="99" aria-label="تعداد"></p>
        <strong class="ci-line"><?= show_price((int)$it['line_total']) ?></strong>
      </div>
      <?php endforeach; ?>
      <div class="ci-actions">
        <button class="btn btn-outline btn-sm" type="submit">به‌روزرسانی سبد</button>
        <a class="btn btn-navy btn-sm" href="/takmil-safarish">ادامه فرآیند خرید</a>
      </div>
    </div>
    <aside class="summary-box card">
      <h2>خلاصه سبد</h2>
      <div class="sum-row"><span>جمع کالاها (<?= fa_digits((string)$cart['count']) ?> قلم)</span><b><?= show_price($cart['subtotal']) ?> تومان</b></div>
      <div class="sum-row sum-total"><span>مبلغ قابل پرداخت</span><b><?= show_price($cart['subtotal']) ?> تومان</b></div>
      <p class="muted">هزینه ارسال در مرحله بعد محاسبه می‌شود.</p>
      <a class="btn btn-primary btn-block" href="/takmil-safarish">تکمیل سفارش</a>
    </aside>
  </form>
  <?php endif; ?>
</section>
