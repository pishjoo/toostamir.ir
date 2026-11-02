<?php
$stMap = ['new'=>'دریافت شد','assigned'=>'اعزام تکنسین','in_progress'=>'در حال تعمیر','waiting_parts'=>'منتظر قطعه','completed'=>'تکمیل شد','cancelled'=>'لغو شده'];
?>
<section class="section container narrow">
  <h1>پیگیری درخواست تعمیر</h1>
  <form class="card track-form" method="get" action="/peigiri-darkhast">
    <p class="field"><label for="tcode">کد رهگیری (مثال TT-XXXXXXXX)</label>
      <input id="tcode" name="code" value="<?= e($code) ?>" maxlength="20" placeholder="TT-..." autocomplete="off"></p>
    <button class="btn btn-primary" type="submit">پیگیری</button>
  </form>
  <?php if ($req): $cur=(string)$req['status']; $order=array_keys($stMap); $ci=array_search($cur,$order); ?>
  <div class="card req-result">
    <h2>درخواست <?= e($req['tracking_code']) ?></h2>
    <p>خدمت: <strong><?= e(Database::one('SELECT name FROM services WHERE id=?',[(int)$req['service_id']])['name'] ?? '-') ?></strong>
      · دستگاه: <strong><?= e(trim(($req['device_brand']??'').' '.($req['device_model']??' ')) ?: '—') ?></strong>
      · تاریخ ثبت: <strong><?= e(shamsi_date($req['created_at'], true)) ?></strong></p>
    <div class="status-pill pill-<?= e($cur) ?>"><?= e($stMap[$cur] ?? $cur) ?></div>
    <ol class="steps steps-track">
      <?php foreach ($order as $i=>$st): if($st==='cancelled') continue; $done=$ci!==false&&$i<=$ci; ?>
      <li class="<?= $done?'is-done':'' ?>"><?= e($stMap[$st]) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php if ($history): ?><h3>روند پردازش</h3><ul class="timeline">
      <?php foreach ($history as $h): ?><li><time><?= e(shamsi_date($h['created_at'], true)) ?></time>
        <span><?= e($stMap[$h['to_status']] ?? (string)$h['to_status']) ?></span>
        <?php if(!empty($h['note'])): ?><p><?= e($h['note']) ?></p><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
    <p class="muted">در صورت نیاز به هماهنگی بیشتر با <?= fa_digits(setting('contact_phone')) ?> تماس بگیرید.</p>
  </div>
  <?php elseif ($code !== ''): ?>
  <div class="empty card"><h3>درخواستی با این کد پیدا نشد.</h3><p>کد را بررسی کنید یا با ما تماس بگیرید.</p></div>
  <?php endif; ?>
</section>
