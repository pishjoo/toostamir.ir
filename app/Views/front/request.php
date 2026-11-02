<?php $old = $old ?? []; $pre = (int)($_GET['service'] ?? 0); ?>
<section class="section container narrow">
  <div class="section-head"><h1>درخواست تعمیر لوازم خانگی</h1>
    <p>فرم زیر را تکمیل کنید؛ کارشناس توس تعمیر در ساعات کاری با شما تماس می‌گیرد. مشاوره تلفنی رایگان: <a href="tel:<?= e(setting('contact_phone')) ?>"><?= fa_digits(setting('contact_phone')) ?></a></p></div>

  <form class="card req-form" method="post" action="/darkhast-tamir" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="text" name="website" value="" hidden aria-hidden="true" tabindex="-1" autocomplete="off">
    <div class="form-grid">
      <p class="field"><label for="rname">نام و نام خانوادگی *</label>
        <input id="rname" required name="name" maxlength="80" value="<?= e((string)($old['name'] ?? '')) ?>"></p>
      <p class="field"><label for="rmob">موبایل *</label>
        <input id="rmob" required name="mobile" inputmode="numeric" placeholder="09xxxxxxxxx" maxlength="15" value="<?= e((string)($old['mobile'] ?? '')) ?>"></p>
      <p class="field"><label for="rsvc">خدمت موردنظر *</label>
        <select id="rsvc" required name="service_id">
          <option value="">انتخاب کنید…</option>
          <?php foreach ($services as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= ($pre===(int)$s['id'])?'selected':'' ?>><?= e($s['name']) ?></option>
          <?php endforeach; ?>
        </select></p>
      <p class="field"><label for="rbrand">برند دستگاه</label>
        <input id="rbrand" name="device_brand" maxlength="60" value="<?= e((string)($old['device_brand'] ?? '')) ?>" placeholder="مثلاً اسنوا، ال‌جی، بوش"></p>
      <p class="field"><label for="rmodel">مدل دستگاه</label>
        <input id="rmodel" name="device_model" maxlength="80" value="<?= e((string)($old['device_model'] ?? '')) ?>"></p>
      <p class="field"><label for="rtime">زمان ترجیحی بازدید</label>
        <select id="rtime" name="preferred_time">
          <option value="">هر زمان</option>
          <option value="morning">صبح (۹–۱۲)</option><option value="noon">ظهر (۱۲–۱۵)</option>
          <option value="afternoon">بعدازظهر (۱۵–۱۸)</option><option value="evening">شب (۱۸–۲۰)</option>
        </select></p>
      <p class="field field-full"><label for="rprob">شرح مشکل *</label>
        <textarea id="rprob" required name="problem_description" rows="5" maxlength="2000" placeholder="چه اتفاقی افتاده؟ چه خطایی نمایش داده می‌شود؟ از کی شروع شده؟"><?= e((string)($old['problem_description'] ?? '')) ?></textarea></p>
      <p class="field field-full"><label for="raddr">آدرس (برای تعمیر در محل)</label>
        <textarea id="raddr" name="address" rows="2" maxlength="300"><?= e((string)($old['address'] ?? '')) ?></textarea></p>
      <p class="field field-full"><label>تصاویر مشکوک دستگاه (اختیاری — حداکثر ۳ فایل، JPG/PNG/WebP هرکدام تا ۴ مگابایت)</label>
        <span class="hp-field-label">اگر ربات هستید این فیلد را پر نکنید:</span><input class="hp-field" name="phone2" tabindex="-1" autocomplete="off">
        <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple></p>
    </div>
    <button class="btn btn-primary btn-block" type="submit">ثبت درخواست و دریافت کد رهگیری</button>
    <p class="form-note">با ثبت فرم، <a href="/terms-conditions">قوانین</a> را می‌پذیرید. اطلاعات شما صرفاً برای هماهنگی تعمیر استفاده می‌شود.</p>
  </form>
</section>
