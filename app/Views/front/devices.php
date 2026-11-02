<section class="section container">
  <div class="section-head"><h1>تعمیر انواع لوازم خانگی در مشهد</h1><p>دسته‌بندی خدمات بر اساس نوع دستگاه — برای هر دستگاه، راهنمای کامل علائم خرابی و هزینه تعمیر ارائه شده است.</p></div>
  <div class="grid g-4">
    <?php foreach ($groups as $g): ?>
    <a class="card cat-tile" href="<?= e($g['href']) ?>">
      <span class="svc-icon"><?= \TT\Icons::service((string)($g['icon'] ?? ''), (string)$g['name']) ?></span>
      <h3>تعمیر <?= e($g['name']) ?></h3>
      <p><?= fa_digits((string)$g['cnt']) ?> خدمت مرتبط</p>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$groups): ?><div class="empty card"><h3>خدمتی ثبت نشده است.</h3></div><?php endif; ?>
</section>
