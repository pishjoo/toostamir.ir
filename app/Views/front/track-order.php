<?php
$stMap = ['pending_payment'=>'در انتظار پرداخت','paid'=>'پرداخت شد','processing'=>'در حال پردازش','packed'=>'بسته‌بندی','shipped'=>'ارسال شد','delivered'=>'تحویل داده شد','cancelled'=>'لغو شده'];
$payMap = ['unpaid'=>'پرداخت‌نشده','paid'=>'پرداخت‌شده','failed'=>'ناموفق','refunded'=>'بازگشت داده شد'];
?>
<section class="section container narrow">
  <h1>پیگیری سفارش</h1>
  <form class="card track-form" method="get" action="/peigiri-order">
    <p class="field"><label for="tcode">کد رهگیری سفارش (TO-XXXXXXXX)</label>
      <input id="tcode" name="code" value="<?= e($code) ?>" maxlength="20" placeholder="TO-..." autocomplete="off"></p>
    <button class="btn btn-primary" type="submit">پیگیری</button>
  </form>
  <?php if ($order): $cur=(string)$order['status']; ?>
  <div class="card req-result">
    <h2>سفارش <?= e($order['tracking_code']) ?></h2>
    <p>تاریخ ثبت: <strong><?= e(shamsi_date($order['created_at'], true)) ?></strong> · مبلغ کل: <strong><?= show_price((int)$order['total_amount']) ?> تومان</strong>
       · وضعیت پرداخت: <strong><?= e($payMap[(string)$order['payment_status']] ?? (string)$order['payment_status']) ?></strong></p>
    <div class="status-pill pill-<?= e($cur) ?>"><?= e($stMap[$cur] ?? $cur) ?></div>
    <ol class="steps steps-track">
      <?php $flow=['pending_payment','paid','processing','packed','shipped','delivered']; $ci=array_search($cur,$flow);
      foreach ($flow as $i=>$st): $done=$ci!==false&&$i<=$ci; ?>
      <li class="<?= $done?'is-done':'' ?>"><?= e($stMap[$st]) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php if (!empty($order['carrier']) || !empty($order['shipment_tracking'])): ?>
    <p>📦 carrier: <strong><?= e((string)$order['carrier']) ?></strong> — کد رهگیری مرسوله: <strong><?= e((string)$order['shipment_tracking']) ?></strong></p>
    <?php endif; ?>
    <h3>اقلام سفارش</h3>
    <table class="spec-table"><tbody>
      <?php foreach ($items as $it): ?><tr><td><?= e($it['product_name']) ?> × <?= fa_digits((string)$it['quantity']) ?></td><td><?= show_price((int)$it['line_total']) ?> تومان</td></tr><?php endforeach; ?>
      <tr><th>ارسال</th><td><?= show_price((int)$order['shipping_cost']) ?> تومان</td></tr>
      <?php if ((int)$order['discount_amount']>0): ?><tr><th>تخفیف</th><td class="txt-ok"><?= show_price((int)$order['discount_amount']) ?> تومان</td></tr><?php endif; ?>
      <tr><th>مبلغ کل</th><td><b><?= show_price((int)$order['total_amount']) ?> تومان</b></td></tr>
    </tbody></table>
    <?php if ($history): ?><ul class="timeline"><?php foreach ($history as $h): ?>
      <li><time><?= e(shamsi_date($h['created_at'], true)) ?></time><span><?= e($stMap[(string)$h['to_status']] ?? (string)$h['to_status']) ?></span>
      <?php if(!empty($h['note'])): ?><p><?= e($h['note']) ?></p><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($order['payment_status']!=='paid' && $order['status']==='pending_payment'): ?>
    <p><a class="btn btn-primary btn-sm" href="/pay/<?= e($order['tracking_code']) ?>">پرداخت سفارش</a></p><?php endif; ?>
  </div>
  <?php elseif ($code !== ''): ?>
  <div class="empty card"><h3>سفارشی با این کد پیدا نشد.</h3></div>
  <?php endif; ?>
</section>
