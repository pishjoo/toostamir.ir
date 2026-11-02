<section class="section container narrow">
  <h1>پرداخت سفارش <?= fa_digits($order['tracking_code']) ?></h1>
  <div class="card pay-card">
    <p>مبلغ قابل پرداخت: <strong><?= show_price((int)$order['total_amount']) ?> تومان</strong></p>
    <p class="muted">وضعیت پرداخت فعلی: <?= e($order['payment_status']==='paid'?'پرداخت‌شده':'پرداخت‌نشده') ?></p>
    <form method="post" action="/pay/start" class="pay-form">
      <?= csrf_field() ?>
      <input type="hidden" name="tracking" value="<?= e($order['tracking_code']) ?>">
      <div class="gw-list">
        <?php foreach ($gateways as $g): if ($g['gateway_key']==='manual') continue; ?>
        <label class="chk gw-opt"><input type="radio" required name="gateway" value="<?= e($g['gateway_key']) ?>">
          <?= e($g['name']) ?><?php if ((int)$g['is_sandbox']===1): ?> <span class="badge">تست</span><?php endif; ?></label>
        <?php endforeach; ?>
        <label class="chk gw-opt"><input type="radio" name="gateway" value="manual"> بدون پرداخت آنلاین (هماهنگی تلفنی)</label>
      </div>
      <button class="btn btn-primary btn-block" type="submit">ادامه پرداخت</button>
    </form>
    <p><a class="btn btn-outline btn-sm" href="/peigiri-order?code=<?= e(rawurlencode($order['tracking_code'])) ?>">پیگیری سفارش</a></p>
  </div>
</section>
