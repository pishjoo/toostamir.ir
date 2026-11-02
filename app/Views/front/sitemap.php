<section class="section container">
  <h1>نقشه سایت توس تعمیر</h1>
  <div class="sitemap-cols grid g-4">
    <nav><h2>صفحات اصلی</h2><ul>
      <li><a href="/">خانه</a></li><li><a href="/khedmat-tamirat-lavamzesh-khaneghi-dar-mashhad">خدمات</a></li>
      <li><a href="/forushgah">فروشگاه قطعات</a></li><li><a href="/tamirasal-khana-dar-mashhad-tos-tamir">تعمیرگاه</a></li>
      <li><a href="/maghale-at">مقالات</a></li><li><a href="/soalat-متداول">سؤالات متداول</a></li>
      <li><a href="/darkhast-tamir">درخواست تعمیر</a></li><li><a href="/peigiri-order">پیگیری سفارش</a></li>
      <li><a href="/peigiri-darkhast">پیگیری درخواست</a></li><li><a href="/terms-conditions">قوانین</a></li><li><a href="/privacy">حریم خصوصی</a></li>
    </ul></nav>
    <nav><h2>خدمات</h2><ul><?php foreach ($services as $s): ?><li><a href="/khedmat/<?= e($s['slug']) ?>"><?= e($s['name']) ?></a></li><?php endforeach; ?></ul></nav>
    <nav><h2>دسته‌های محصولات</h2><ul><?php foreach ($categories as $c): ?><li><a href="/forushgah?category=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?></ul></nav>
    <nav><h2>برندها</h2><ul><?php foreach ($brands as $b): ?><li><a href="/forushgah?brand[]=<?= (int)$b['id'] ?>"><?= e($b['name']) ?></a></li><?php endforeach; ?></ul></nav>
  </div>
  <?php if ($articles): ?><h2 style="margin-top:24px">آخرین مقالات</h2><ul class="sm-arts"><?php foreach ($articles as $a): ?>
    <li><a href="/maghaleh/<?= e($a['slug']) ?>"><?= e($a['title']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
</section>
