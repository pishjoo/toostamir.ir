<?php $s = $service; ?>
<section class="section container">
  <div class="svc-hero grid g-2">
    <div>
      <h1><?= e($s['name']) ?></h1>
      <p class="lead"><?= e($s['excerpt']) ?></p>
      <ul class="c-list"><li>⚙️ عیب‌یابی با دستگاه‌های تخصصی (اسیلوسکوپ، هیت‌گن)</li><li>🛡 گارانتی تعمیر برد تا ۳ ماه</li><li>📦 ارسال پستی برد در سراسر کشور</li></ul>
      <p><a class="btn btn-primary" href="/darkhast-tamir?service=<?= (int)$s['id'] ?>">ثبت درخواست تعمیر برد</a>
         <a class="btn btn-outline" href="tel:<?= e(setting('contact_phone')) ?>">مشاوره رایگان</a></p>
    </div>
    <div class="hero-img"><?php if(!empty($s['image_path'])): ?><img src="<?= e($s['image_path']) ?>" alt="<?= e($s['alt_text'] ?: $s['name']) ?>" width="640" height="480"><?php else: ?><?= \TT\Placeholder::svg('برد الکترونیک', 640, 480) ?><?php endif; ?></div>
  </div>

  <?php if ($brands): ?>
  <section class="card info-card"><h2>برندهای تحت پوشش</h2>
    <div class="brand-chips"><?php foreach ($brands as $b): ?><span class="badge"><?= e($b['name']) ?></span><?php endforeach; ?></div>
    <p class="muted">اگر برند دستگاه شما در لیست نیست، با ما تماس بگیرید؛ اکثر بردها قابل تعمیر هستند.</p>
  </section>
  <?php endif; ?>

  <div class="prose"><?= nl2br(e((string)$s['description'])) ?></div>

  <?php if ($faqs): ?><section class="faq-block"><h2>سؤالات متداول تعمیر برد</h2>
    <?php foreach ($faqs as $f): ?><details class="card faq-item"><summary><?= e($f['question']) ?></summary><p><?= e($f['answer']) ?></p></details><?php endforeach; ?></section><?php endif; ?>

  <?php if ($products): ?>
  <section style="margin-top:32px"><h2>قطعات و بردهای موجود</h2>
    <div class="grid g-4"><?php foreach ($products as $p) View::partial('partials/product-card', ['p' => $p]); ?></div></section>
  <?php endif; ?>

  <?php if ($articles): ?>
  <section style="margin-top:32px"><h2>مقالات مرتبط</h2><div class="grid g-3">
    <?php foreach ($articles as $a): ?><a class="card art-card" href="/maghaleh/<?= e($a['slug']) ?>"><h3><?= e($a['title']) ?></h3></a><?php endforeach; ?>
  </div></section>
  <?php endif; ?>
</section>
