<?php /** @var array $service */ $s = $service; ?>
<section class="section container">
  <div class="svc-hero grid g-2">
    <div>
      <h1><?= e($s['name'] ? ('تعمیر ' . $s['name']) . ' در مشهد' : ('تعمیر ' . $s['name'] . ' در مشهد')) ?></h1>
      <p class="lead"><?= e($s['excerpt']) ?></p>
      <p>
        <a class="btn btn-primary" href="/darkhast-tamir?service=<?= (int)$s['id'] ?>">همین حالا درخواست تعمیر بدهید</a>
        <a class="btn btn-outline" href="tel:<?= e(setting('contact_phone')) ?>">مشاوره تلفنی رایگان</a>
      </p>
    </div>
    <div class="hero-img">
      <?php if (!empty($s['image_path'])): ?><img src="<?= e($s['image_path']) ?>" alt="<?= e($s['alt_text'] ?: $s['name']) ?>" width="640" height="480"><?php
      else: ?><?= \TT\Placeholder::svg((string)$s['name'], 640, 480) ?><?php endif; ?>
    </div>
  </div>

  <?php if ($children): ?>
  <div class="grid g-4" style="margin-top:24px">
    <?php foreach ($children as $c): ?>
      <a class="card svc-card" href="/khedmat/<?= e($c['slug']) ?>"><h3><?= e($c['name']) ?></h3></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="prose" style="margin-top:32px"><?= nl2br(e((string)$s['description'])) ?></div>

  <?php
  $lists = [
    'مشکلات رایج' => $s['common_problems'] ?? '',
    'علائم خرابی' => $s['symptoms'] ?? '',
    'دلایل احتمالی خرابی' => $s['causes'] ?? '',
    'مراحل عیب‌یابی و تعمیر' => $s['diagnostic_steps'] ?? '',
  ];
  foreach ($lists as $ttl => $raw):
    if (trim((string)$raw) === '') continue;
    $items = preg_split('/\r\n|\r|\n|؛|;|،(?=\S{6,}؟?$)/u', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);
    $items = array_filter(array_map('trim', $items), fn($x) => $x !== '');
    if (!$items) continue; ?>
  <div class="card info-card">
    <h2><?= e($ttl) ?></h2>
    <ul><?php foreach ($items as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endforeach; ?>

  <?php if ($faqs): ?>
  <section class="faq-block">
    <h2>سؤالات متداول درباره <?= e($s['name']) ?></h2>
    <?php foreach ($faqs as $f): ?>
    <details class="card faq-item"><summary><?= e($f['question']) ?></summary><p><?= e($f['answer']) ?></p></details>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if (!empty($related)): ?>
  <section style="margin-top:32px">
    <h2>خدمات مرتبط</h2>
    <div class="grid g-4">
      <?php foreach ($related as $r): if ($r['id'] === $s['id']) continue; ?>
      <a class="card svc-card" href="/khedmat/<?= e($r['slug']) ?>"><h3><?= e($r['name']) ?></h3></a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <div class="cta-band card" style="text-align:center;margin-top:32px;padding:28px">
    <h2>خرابی دستگاه خود را به متخصص بسپارید</h2>
    <p><a class="btn btn-primary" href="/darkhast-tamir?service=<?= (int)$s['id'] ?>">ثبت درخواست تعمیر</a>
       <a class="btn btn-outline" href="https://wa.me/<?= e(setting('contact_whatsapp','989213624847')) ?>" target="_blank" rel="nofollow noopener">واتساپ</a></p>
  </div>
</section>
