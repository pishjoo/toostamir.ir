<section class="section container narrow" style="text-align:center;padding:56px 16px">
  <?php if (!empty($ok)): ?>
  <div class="thanks-box card"><div class="thanks-check">✓</div>
    <h1>پرداخت موفق بود</h1>
    <p>کد رهگیری: <strong class="tt-code"><?= fa_digits((string)($code ?? '')) ?></strong></p>
    <p><a class="btn btn-primary" href="/peigiri-order?code=<?= rawurlencode((string)($code ?? '')) ?>">پیگیری سفارش</a></p>
  </div>
  <?php else: ?>
  <div class="card fail-box" style="padding:32px">
    <h1 style="color:#D64545">پرداخت ناموفق بود</h1>
    <p><?= e((string)($message ?? 'عملیات پرداخت تکمیل نشد.')) ?></p>
    <p>نگران نباشید؛ اگر مبلغ از حساب شما کسر شده، حداکثر تا ۷۲ ساعت بازگردانده می‌شود.</p>
    <p><a class="btn btn-primary" href="/pay/<?= rawurlencode((string)($code ?? '')) ?>">تلاش مجدد برای پرداخت</a>
       <a class="btn btn-outline" href="/tamirasal-khana-dar-mashhad-tos-tamir">تماس با پشتیبانی</a></p>
  </div>
  <?php endif; ?>
</section>
