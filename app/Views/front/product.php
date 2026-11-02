<?php
$p = $product; $price = Product::effectivePrice($p);
$hasOff = (int)$p['discount_price'] > 0 && (int)$p['discount_price'] < (int)$p['price'];
$out = ($p['stock_status'] ?? '') === 'out_of_stock' || (int)$p['stock_quantity'] <= 0;
$attrs = Database::all('SELECT name, value FROM product_attributes WHERE product_id=? ORDER BY sort_order ASC, id ASC', [(int)$p['id']]);
$compat = Database::all('SELECT device_type, brand_name, model_name, note FROM product_compatibilities WHERE product_id=?', [(int)$p['id']]);
$brandName = '';
if (!empty($p['brand_id'])) { $b = Database::one('SELECT name FROM brands WHERE id=?', [(int)$p['brand_id']]); $brandName = (string)($b['name'] ?? ''); }
?>
<section class="section container">
  <div class="pd-grid grid g-2">
    <div class="gallery">
      <?php $main = $images[0]['image_path'] ?? ''; ?>
      <div class="gallery-main">
        <?php if ($main): ?><img id="galMain" src="<?= e($main) ?>" alt="<?= e($p['alt_text'] ?: $p['name']) ?>" width="560" height="420"><?php
        else: ?><?= \TT\Placeholder::svg((string)$p['name'], 560, 420) ?><?php endif; ?>
      </div>
      <?php if (count($images) > 1): ?>
      <div class="thumbs">
        <?php foreach ($images as $i => $im): ?>
        <button type="button" class="thumb" data-full="<?= e($im['image_path']) ?>" aria-label="تصویر <?= fa_digits((string)($i+1)) ?>">
          <img src="<?= e($im['image_path']) ?>" alt="<?= e($im['alt_text'] ?: $p['name']) ?>" loading="lazy" width="80" height="60">
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="pd-info">
      <h1><?= e($p['name']) ?></h1>
      <ul class="pd-meta">
        <?php if (!empty($p['sku'])): ?><li>کد کالا: <strong><?= e($p['sku']) ?></strong></li><?php endif; ?>
        <?php if (!empty($p['part_code'])): ?><li>کد فنی: <strong><?= e($p['part_code']) ?></strong></li><?php endif; ?>
        <?php if ($brandName !== ''): ?><li>برند: <strong><?= e($brandName) ?></strong></li><?php endif; ?>
        <li>وضعیت: <strong class="<?= $out ? 'txt-danger' : 'txt-ok' ?>"><?= $out ? 'ناموجود (استعلام بگیرید)' : 'موجود — ارسال از مشهد' ?></strong></li>
      </ul>
      <div class="pd-price">
        <span class="p-price"><?= show_price($price) ?> <small>تومان</small></span>
        <?php if ($hasOff): ?><span class="p-old"><?= show_price((int)$p['price']) ?></span>
          <span class="badge badge-off"><?= fa_digits((string)round((1-$price/max(1,(int)$p['price']))*100)) ?>٪ تخفیف</span><?php endif; ?>
      </div>
      <?php if (!empty($p['short_description'])): ?><p class="lead"><?= e($p['short_description']) ?></p><?php endif; ?>

      <?php if (!$out): ?>
      <form method="post" action="/cart/add" class="add-form qty-form" data-ajax-add>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
        <label class="qty-lbl" for="qtyInput">تعداد</label>
        <div class="qty-box">
          <button type="button" data-qty="-1" aria-label="کم کردن">−</button>
          <input id="qtyInput" type="number" name="qty" value="1" min="1" max="99" inputmode="numeric">
          <button type="button" data-qty="1" aria-label="اضافه کردن">+</button>
        </div>
        <button type="submit" class="btn btn-primary">افزودن به سبد خرید</button>
      </form>
      <?php else: ?>
      <p><a class="btn btn-navy" href="/tamirasal-khana-dar-mashhad-tos-tamir">استعلام قیمت و موجودی</a></p>
      <?php endif; ?>
      <p class="pd-actions">
        <a class="btn btn-outline btn-sm" href="/darkhast-tamir">نیاز به تعمیر دارید؟ درخواست بدهید</a>
        <a class="btn btn-outline btn-sm" href="https://wa.me/<?= e(setting('contact_whatsapp','989213624847')) ?>?text=<?= rawurlencode('سلام، درباره محصول ' . $p['name']) ?>" target="_blank" rel="nofollow noopener">پرسش در واتساپ</a>
      </p>
    </div>
  </div>

  <div class="tabs card" style="margin-top:32px">
    <input type="radio" name="ptab" id="tabDesc" checked><label for="tabDesc">توضیحات</label>
    <div class="tab-pane prose"><?= sanitize_html((string)($p['description'] ?? '')) ?><?php if (trim((string)($p['description']??''))===''): ?><p><?= e((string)($p['short_description'] ?? '')) ?></p><?php endif; ?></div>

    <?php if ($attrs): ?>
    <input type="radio" name="ptab" id="tabSpec"><label for="tabSpec">مشخصات فنی</label>
    <div class="tab-pane">
      <table class="spec-table"><tbody>
        <?php foreach ($attrs as $a): ?><tr><th><?= e($a['name']) ?></th><td><?= e($a['value']) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <?php endif; ?>

    <?php if ($compat || !empty($p['compatible_models'])): ?>
    <input type="radio" name="ptab" id="tabComp"><label for="tabComp">سازگاری</label>
    <div class="tab-pane">
      <?php if ($compat): ?><table class="spec-table"><thead><tr><th>دستگاه</th><th>برند</th><th>مدل</th></tr></thead><tbody>
        <?php foreach ($compat as $c): ?><tr><td><?= e((string)$c['device_type']) ?></td><td><?= e((string)$c['brand_name']) ?></td><td><?= e((string)$c['model_name']) ?></td></tr><?php endforeach; ?>
      </tbody></table><?php endif; ?>
      <?php if (!empty($p['compatible_models'])): ?><p>مدل‌های سازگار: <?= e((string)$p['compatible_models']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($faqs): ?>
    <input type="radio" name="ptab" id="tabFaq"><label for="tabFaq">سؤالات متداول</label>
    <div class="tab-pane"><?php foreach ($faqs as $f): ?>
      <details class="faq-item"><summary><?= e($f['question']) ?></summary><p><?= e($f['answer']) ?></p></details>
    <?php endforeach; ?></div>
    <?php endif; ?>
  </div>

  <section class="card comment-box" style="margin-top:24px;padding:20px">
    <h2>ثبت نظر<?= $comments ? ' — ' . fa_digits((string)count($comments)) . ' نظر تأییدشده' : '' ?></h2>
    <?php foreach ($comments as $c): ?>
      <div class="cmnt"><strong><?= e($c['name']) ?></strong><?php if(!empty($c['rating'])): ?> · امتیاز <?= fa_digits((string)$c['rating']) ?>/۵<?php endif; ?>
      <p><?= e($c['body']) ?></p><time><?= e(shamsi_date($c['created_at'])) ?></time></div>
    <?php endforeach; ?>
    <form class="form-grid" data-comment-form data-target-type="product" data-target-id="<?= (int)$p['id'] ?>">
      <?= csrf_field() ?>
      <p class="field"><label>نام شما *</label><input required name="name" maxlength="80"></p>
      <p class="field"><label>متن نظر *</label><textarea required name="body" rows="3" maxlength="1500"></textarea></p>
      <button class="btn btn-primary btn-sm" type="submit">ارسال نظر</button>
      <span class="form-note">نظر شما پس از بررسی نمایش داده می‌شود.</span>
    </form>
  </section>

  <?php if ($related): ?>
  <section style="margin-top:32px">
    <h2>محصولات مرتبط</h2>
    <div class="grid g-4"><?php foreach ($related as $r) View::partial('partials/product-card', ['p' => $r]); ?></div>
  </section>
  <?php endif; ?>
</section>
