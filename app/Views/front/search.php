<section class="section container">
  <h1>نتایج جستجو برای «<?= e($q) ?>»</h1>
  <?php if (!$products && !$articles && !$services): ?>
    <div class="empty card"><h3>موردی یافت نشد</h3>
      <p>پیشنهاد: کلمه کوتاه‌تر یا نام قطعه/دستگاه را امتحان کنید؛ یا درخواست تعمیر ثبت کنید.</p>
      <form method="get" action="/jostejo" class="search-again"><input type="search" name="q" value="<?= e($q) ?>" placeholder="جستجو…"><button class="btn btn-primary btn-sm">جستجو</button></form>
    </div>
  <?php endif; ?>

  <?php if ($services): ?><h2>خدمات (<?= fa_digits((string)count($services)) ?>)</h2><div class="grid g-4">
    <?php foreach ($services as $s): ?><a class="card svc-card" href="/khedmat/<?= e($s['slug']) ?>"><h3><?= e($s['name']) ?></h3><p><?= e(mb_substr(strip_tags((string)$s['excerpt']),0,80)) ?></p></a><?php endforeach; ?></div><?php endif; ?>

  <?php if ($products): ?><h2>محصولات (<?= fa_digits((string)count($products)) ?>)</h2><div class="grid g-4">
    <?php foreach ($products as $p) View::partial('partials/product-card', ['p' => $p]); ?></div><?php endif; ?>

  <?php if ($articles): ?><h2>مقالات (<?= fa_digits((string)count($articles)) ?>)</h2><div class="grid g-3">
    <?php foreach ($articles as $a): ?><a class="card art-card" href="/maghaleh/<?= e($a['slug']) ?>"><h3><?= e($a['title']) ?></h3><p><?= e(mb_substr(strip_tags((string)$a['excerpt']),0,90)) ?></p></a><?php endforeach; ?></div><?php endif; ?>
</section>
