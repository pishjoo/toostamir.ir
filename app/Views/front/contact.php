<section class="section container">
  <div class="section-head"><h1>تماس با توس تعمیر — الهیه مشهد</h1>
    <p>قبل از مراجعه، تماس بگیرید تا از حضور تکنسین مطمئن شوید.</p></div>
  <div class="grid g-2 contact-grid">
    <div class="card contact-info">
      <h2>اطلاعات تماس</h2>
      <ul class="c-list">
        <li>📞 <a href="tel:<?= e(setting('contact_phone','09213624847')) ?>"><?= fa_digits(setting('contact_phone','09213624847')) ?></a></li>
        <li>💬 <a rel="nofollow noopener" target="_blank" href="https://wa.me/<?= e(setting('contact_whatsapp','989213624847')) ?>">گفتگو در واتساپ</a></li>
        <li>📍 <?= e(setting('contact_address','مشهد، الهیه، محمدیه ۱۰، علوی ثانی ۳، مجتمع تجاری بلور، واحد ۲')) ?></li>
        <li>🕘 <?= e(setting('working_hours','شنبه تا پنجشنبه ۹ تا ۲۰')) ?></li>
        <?php if (($em=setting('contact_email',''))!==''): ?><li>✉️ <a href="mailto:<?= e($em) ?>"><?= e($em) ?></a></li><?php endif; ?>
      </ul>
      <?php if (($map=setting('google_maps_embed',''))!==''): ?>
      <div class="map-box"><iframe src="<?= e($map) ?>" loading="lazy" title="نقشه توس تعمیر" style="border:0;width:100%;height:280px"></iframe></div>
      <?php elseif (($link=setting('google_maps_link',''))!==''): ?>
      <p><a class="btn btn-outline btn-sm" rel="noopener" target="_blank" href="<?= e($link) ?>">مسیریابی در گوگل مپ</a></p>
      <?php endif; ?>
    </div>
    <form class="card contact-form" method="post" action="/tamirasal-khana-dar-mashhad-tos-tamir">
      <h2>ارسال پیام</h2>
      <?= csrf_field() ?>
      <p class="field"><label>نام *</label><input required name="name" maxlength="80"></p>
      <p class="field"><label>موبایل</label><input name="mobile" inputmode="numeric" maxlength="15"></p>
      <p class="field"><label>پیام *</label><textarea required name="message" rows="4" maxlength="1000"></textarea></p>
      <button class="btn btn-primary btn-block" type="submit">ارسال پیام</button>
      <?php if (!empty($sent)): ?><p class="txt-ok">✓ پیام شما ثبت شد.</p><?php endif; ?>
    </form>
  </div>
</section>
