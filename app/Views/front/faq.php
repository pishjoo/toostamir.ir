<section class="section container">
  <div class="section-head"><h1>سؤالات متداول</h1><p>پاسخ رایج‌ترین پرسش‌های مشتریان توس تعمیر درباره خدمات و خرید قطعات.</p></div>
  <?php
  $byCat = [];
  foreach ($faqs as $f) $byCat[$f['category'] ?? 'عمومی'][] = $f;
  foreach ($byCat as $cat => $items): ?>
  <h2 class="faq-cat"><?= e((string)$cat) ?></h2>
  <?php foreach ($items as $f): ?><details class="card faq-item"><summary><?= e($f['question']) ?></summary><div class="prose"><?= nl2br(e($f['answer'])) ?></div></details><?php endforeach; ?>
  <?php endforeach; ?>
  <?php if (!$faqs): ?><div class="empty card"><h3>هنوز سؤالی ثبت نشده است.</h3></div><?php endif; ?>
  <div class="cta-band card" style="text-align:center;margin-top:28px;padding:24px"><h2>پاسخ سؤالتان را پیدا نکردید؟</h2>
    <p><a class="btn btn-primary" href="/darkhast-tamir">ثبت درخواست</a> <a class="btn btn-outline" href="tel:<?= e(setting('contact_phone')) ?>">تماس</a></p></div>
</section>
