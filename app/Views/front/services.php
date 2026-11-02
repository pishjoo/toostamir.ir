<section class="section container">
  <div class="section-head"><h1>خدمات تعمیر تخصصی لوازم خانگی در مشهد</h1>
    <p>تعمیرگاه توس تعمیر با تیم فنی مجرب، خدمات عیب‌یابی و تعمیر انواع لوازم خانگی را در مشهد و به‌ویژه محدوده الهیه ارائه می‌دهد.</p></div>
  <div class="grid g-4">
    <?php foreach ($services as $s): if ((int)($s['parent_id'] ?? 0) !== 0) continue; ?>
    <a class="card svc-card" href="/khedmat/<?= e($s['slug']) ?>">
      <span class="svc-icon"><?= \TT\Icons::service((string)($s['icon'] ?? ''), (string)$s['name']) ?></span>
      <h3><?= e($s['name']) ?></h3>
      <p><?= e(mb_substr(strip_tags((string)($s['excerpt'] ?? '')), 0, 90)) ?></p>
      <span class="svc-more">جزئیات خدمت ←</span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="cta-band card" style="margin-top:32px;text-align:center;padding:28px">
    <h2>دستگاه شما در این لیست نیست؟</h2>
    <p>شرح مشکل را برای ما بفرستید؛ کارشناسان توس تعمیر راهنمایی می‌کنند.</p>
    <p><a class="btn btn-primary" href="/darkhast-tamir">ثبت درخواست تعمیر</a>
       <a class="btn btn-outline" href="/tamirasal-khana-dar-mashhad-tos-tamir">تماس با ما</a></p>
  </div>
</section>
