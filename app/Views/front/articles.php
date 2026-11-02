<section class="section container">
  <div class="section-head"><h1>مقالات آموزشی توس تعمیر</h1><p>راهنمای عیب‌یابی، نگهداری و تعمیر لوازم خانگی — نوشته تیم فنی.</p></div>
  <form method="get" action="/maghale-at" class="art-filter">
    <select name="cat" onchange="this.form.submit()">
      <option value="">همه دسته‌های مقالات</option>
      <?php foreach ($cats as $c): ?><option value="<?= e($c['slug']) ?>" <?= ($filters['cat']===$c['slug'])?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select>
    <input type="search" name="q" placeholder="جستجو در مقالات…" value="<?= e($filters['q']) ?>"><button class="btn btn-navy btn-sm">جستجو</button>
  </form>
  <?php if (!$res['items']): ?><div class="empty card"><h3>مقاله‌ای یافت نشد</h3></div><?php else: ?>
  <div class="grid g-3">
    <?php foreach ($res['items'] as $a): ?>
    <article class="card art-card">
      <a href="/maghaleh/<?= e($a['slug']) ?>"><?php if(!empty($a['image_path'])): ?><img src="<?= e($a['image_path']) ?>" alt="<?= e($a['alt_text'] ?: $a['title']) ?>" loading="lazy" width="360" height="200"><?php else: ?><?= \TT\Placeholder::svg((string)$a['title'], 360, 200) ?><?php endif; ?></a>
      <h3><a href="/maghaleh/<?= e($a['slug']) ?>"><?= e($a['title']) ?></a></h3>
      <p><?= e(mb_substr(strip_tags((string)$a['excerpt']),0,120)) ?></p>
      <time><?= e(shamsi_date($a['publish_at'] ?: $a['created_at'])) ?></time>
    </article>
    <?php endforeach; ?>
  </div>
  <nav aria-label="صفحه‌بندی"><?= $pagination ?></nav>
  <?php endif; ?>
</section>
