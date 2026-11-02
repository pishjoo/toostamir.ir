<?php
/** کارت محصول — استفاده‌شده در خانه، فروشگاه، محصولات مرتبط
 *  ورودی: $p (آرایه محصول) */
$price = Product::effectivePrice($p);
$hasOff = (int)$p['discount_price'] > 0 && (int)$p['discount_price'] < (int)$p['price'];
$out = ($p['stock_status'] ?? 'in_stock') === 'out_of_stock' || (int)($p['stock_quantity'] ?? 0) <= 0;
$img = $p['og_image'] ?? ($p['image_path'] ?? '');
?>
<article class="p-card card">
  <a class="p-img" href="/mahsol/<?= e($p['slug']) ?>" title="<?= e($p['name']) ?>">
    <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($p['alt_text'] ?? $p['name']) ?>" loading="lazy" width="320" height="240"><?php
    else: ?><?= \TT\Placeholder::svg((string)($p['category_name'] ?? '') . ' ' . (string)$p['name'], 320, 240) ?><?php endif; ?>
    <?php if ($hasOff): ?><span class="badge badge-off"><?= fa_digits((string)round((1 - $price / max(1,(int)$p['price'])) * 100)) ?>٪ تخفیف</span><?php endif; ?>
    <?php if ($out): ?><span class="badge badge-out">ناموجود</span><?php endif; ?>
  </a>
  <div class="p-body">
    <h3 class="p-name"><a href="/mahsol/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h3>
    <?php if (!empty($p['sku'])): ?><p class="p-sku">کد: <?= e($p['sku']) ?></p><?php endif; ?>
    <div class="p-price-row">
      <span class="p-price"><?= show_price($price) ?> <small>تومان</small></span>
      <?php if ($hasOff): ?><span class="p-old"><?= show_price((int)$p['price']) ?></span><?php endif; ?>
    </div>
    <?php if (!$out): ?>
    <form method="post" action="/cart/add" class="add-form" data-ajax-add>
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
      <input type="hidden" name="qty" value="1">
      <button type="submit" class="btn btn-primary btn-sm btn-block">افزودن به سبد</button>
    </form>
    <?php else: ?>
      <a class="btn btn-outline btn-sm btn-block" href="/tamirasal-khana-dar-mashhad-tos-tamir">استعلام موجودی</a>
    <?php endif; ?>
  </div>
</article>
