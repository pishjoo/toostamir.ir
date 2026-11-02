<?php $res = $res; ?>
<section class="section container">
  <h1><?= $current_cat ? 'خرید و قیمت ' . e($current_cat['name']) : 'فروشگاه قطعات یدکی لوازم خانگی' ?></h1>
  <?php if ($current_cat && !empty($current_cat['description'])): ?><p class="lead"><?= e(mb_substr(strip_tags((string)$current_cat['description']),0,240)) ?></p><?php endif; ?>

  <div class="shop-grid grid g-sidebar">
    <aside class="filters card">
      <form method="get" action="/forushgah">
        <p class="field"><label for="fq">جستجو</label>
          <input id="fq" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="نام، SKU یا کد فنی"></p>
        <p class="field"><label for="fcat">دسته‌بندی</label>
          <select id="fcat" name="category" onchange="this.form.submit()">
            <option value="">همه دسته‌ها</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$filters['category']===(int)$c['id']?'selected':'' ?>><?= e(str_repeat('— ', max(0,(int)($c['depth'] ?? 0))) . $c['name']) ?></option>
            <?php endforeach; ?>
          </select></p>
        <fieldset class="field"><legend>برند</legend>
          <?php foreach (array_slice($brands,0,12) as $b): $sel = in_array((string)$b['id'], array_map('strval',(array)$filters['brand']), true); ?>
          <label class="chk"><input type="checkbox" name="brand[]" value="<?= (int)$b['id'] ?>" <?= $sel?'checked':'' ?>> <?= e($b['name']) ?></label>
          <?php endforeach; ?></fieldset>
        <div class="field"><label>محدوده قیمت (تومان)</label>
          <div class="price-row"><input type="number" name="min_price" min="0" step="500" placeholder="از" value="<?= e((string)$filters['min_price']) ?>">
          <input type="number" name="max_price" min="0" step="500" placeholder="تا" value="<?= e((string)$filters['max_price']) ?>"></div></div>
        <label class="chk"><input type="checkbox" name="in_stock" value="1" <?= $filters['in_stock']?'checked':'' ?>> فقط موجودها</label>
        <select name="sort" aria-label="مرتب‌سازی">
          <option value="">مرتبه‌سازی پیش‌فرض</option>
          <option value="newest" <?= $filters['sort']==='newest'?'selected':'' ?>>جدیدترین</option>
          <option value="cheap" <?= $filters['sort']==='cheap'?'selected':'' ?>>ارزان‌ترین</option>
          <option value="expensive" <?= $filters['sort']==='expensive'?'selected':'' ?>>گران‌ترین</option>
          <option value="popular" <?= $filters['sort']==='popular'?'selected':'' ?>>پرفروش‌ترین</option>
        </select>
        <button class="btn btn-primary btn-block btn-sm" type="submit">اعمال فیلتر</button>
        <a class="btn btn-outline btn-block btn-sm" href="/forushgah">حذف فیلترها</a>
      </form>
    </aside>

    <div class="shop-results">
      <p class="results-count"><?= fa_digits((string)$res['total']) ?> محصول یافت شد</p>
      <?php if (!$res['items']): ?>
        <div class="empty card"><h3>محصولی با این مشخصات پیدا نشد</h3>
          <p>اگر قطعه موردنظرتان را نمی‌بینید، نام یا کد فنی آن را برای ما در واتساپ بفرستید تا تأمین کنیم.</p>
          <a class="btn btn-primary btn-sm" href="/tamirasal-khana-dar-mashhad-tos-tamir">درخواست تأمین قطعه</a></div>
      <?php else: ?>
        <div class="grid g-3"><?php foreach ($res['items'] as $p) View::partial('partials/product-card', ['p' => $p]); ?></div>
        <nav aria-label="صفحه‌بندی"><?= $pagination ?></nav>
      <?php endif; ?>
    </div>
  </div>
</section>
