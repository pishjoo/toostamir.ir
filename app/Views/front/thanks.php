<?php $kind = $kind ?? 'order'; $code = $code ?? ''; ?>
<section class="section container narrow" style="text-align:center;padding:64px 16px">
  <div class="thanks-box card">
    <div class="thanks-check" aria-hidden="true">✓</div>
    <?php if ($kind==='request'): ?>
      <h1>درخواست تعمیر شما ثبت شد</h1>
      <p>کد پیگیری شما: <strong class="tt-code"><?= fa_digits($code) ?></strong></p>
      <p>کارشناس توس تعمیر در ساعات کاری با شما تماس می‌گیرد. برای مشاهده روند درخواست از کد بالا استفاده کنید.</p>
      <p><a class="btn btn-primary" href="/peigiri-darkhast?code=<?= rawurlencode($code) ?>">پیگیری درخواست</a>
         <a class="btn btn-outline" href="/">بازگشت به خانه</a></p>
    <?php elseif ($kind==='paid'): ?>
      <h1>پرداخت با موفقیت انجام شد 🎉</h1>
      <p>کد رهگیری سفارش: <strong class="tt-code"><?= fa_digits($code) ?></strong></p>
      <p>سفارش شما در صف پردازش قرار گرفت و جزئیات ارسال متعاقباً پیامک می‌شود.</p>
      <p><a class="btn btn-primary" href="/peigiri-order?code=<?= rawurlencode($code) ?>">پیگیری سفارش</a>
         <a class="btn btn-outline" href="/forushgah">ادامه خرید</a></p>
    <?php else: ?>
      <h1>سفارش شما ثبت شد</h1>
      <p>کد رهگیری: <strong class="tt-code"><?= fa_digits($code) ?></strong></p>
      <p>برای پرداخت آنلاین یا مشاهده وضعیت، روی دکمه زیر بزنید.</p>
      <p><a class="btn btn-primary" href="/pay/<?= rawurlencode($code) ?>">پرداخت سفارش</a>
         <a class="btn btn-outline" href="/peigiri-order?code=<?= rawurlencode($code) ?>">پیگیری</a></p>
    <?php endif; ?>
  </div>
</section>
