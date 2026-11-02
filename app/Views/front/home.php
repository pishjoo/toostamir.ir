<?php
/**
 * توس تعمیر - صفحه اصلی
 * ورودی‌ها: hero_title, hero_text, services, featured, latest, articles, brands, faqs, stats, ...
 */
$phone = setting('contact_phone', '09213624847');
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="kicker" style="background:rgba(0,184,217,.18);color:#7DE9FF">تعمیرگاه تخصصی + فروشگاه قطعات در مشهد</span>
      <h1><?= e($hero_title) ?></h1>
      <p><?= e($hero_text) ?></p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/darkhast-tamir">ثبت درخواست تعمیر رایگان</a>
        <a class="btn btn-outline" href="/forushgah">مشاهده فروشگاه قطعات</a>
        <a class="btn btn-outline" href="tel:<?= e($phone) ?>">📞 <?= fa_digits($phone) ?></a>
      </div>
      <div style="display:flex;gap:18px;margin-top:26px;color:#C9D6E8;font-size:.85rem;flex-wrap:wrap">
        <span>✓ گارانتی کتبی خدمات</span><span>✓ اعزام تکنسین در محل</span><span>✓ قطعات اصلی و اورجینال</span>
      </div>
    </div>
    <div class="hero-img">
      <?= \TT\Placeholder::svg('pcb', 640, 480) ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <span class="kicker">خدمات تخصصی ما</span>
      <h2>تعمیر همه لوازم خانگی، با تیم فنی مجرب توس تعمیر</h2>
      <p>عیب‌یابی دقیق در محل یا کارگاه، استفاده از قطعات اصلی و شفاف‌سازی هزینه پیش از شروع تعمیر.</p>
    </div>
    <div class="grid g-4">
      <?php foreach ($services as $s): ?>
        <a class="card svc-card" href="/khedmat/<?= e($s['slug']) ?>">
          <span class="svc-icon"><?= \TT\Icons::service($s['icon'] ?? '', $s['name']) ?></span>
          <span>
            <h3><?= e($s['name']) ?></h3>
            <p><?= e(mb_substr(strip_tags((string)$s['excerpt']), 0, 90)) ?>…</p>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:26px"><a class="btn btn-navy" href="/khedmat">همه خدمات را ببینید</a></div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="stats-band">
      <div class="stat"><b>+<?= fa_digits(number_format($stats['repairs'])) ?></b><span>تعمیر موفق</span></div>
      <div class="stat"><b><?= fa_digits(number_format($stats['parts'])) ?></b><span>قطعه یدکی موجود</span></div>
      <div class="stat"><b><?= fa_digits((string)$stats['cities']) ?></b><span>شهر تحت پوشش ارسال</span></div>
      <div class="stat"><b>+<?= fa_digits((string)$stats['years']) ?></b><span>سال تجربه فنی</span></div>
    </div>
  </div>
</section>

<section class="section" style="background:#fff;border-block:1px solid var(--line)">
  <div class="container">
    <div class="section-head">
      <span class="kicker">پرفروش‌ترین قطعات</span>
      <h2>فروشگاه اینترنتی قطعات یدکی اصل</h2>
      <p>قیمت‌ها به تومان و مطابق نرخ روز؛ ارسال سریع به سراسر ایران با بسته‌بندی ایمن.</p>
    </div>
    <div class="grid g-4">
      <?php foreach ($featured as $p) View::partial('partials/product-card', ['p' => $p]); ?>
    </div>
    <div style="text-align:center;margin-top:26px"><a class="btn btn-primary" href="/forushgah">ورود به فروشگاه</a></div>
  </div>
</section>

<section class="section">
  <div class="container grid" style="grid-template-columns:1fr;gap:34px">
    <div>
      <div class="section-head" style="text-align:right;margin-bottom:20px">
        <span class="kicker">چرا توس تعمیر؟</span>
        <h2>تضمین کیفیت، از عیب‌یابی تا تحویل</h2>
      </div>
      <div class="grid g-2">
        <div class="benefit card"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg><div><b>گارانتی خدمات</b><p class="hint" style="margin:0">تمام تعمیرات با مهلت گارانتی کتبی و پشتیبانی پس از سرویس انجام می‌شود.</p></div></div>
        <div class="benefit card"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg><div><b>اعزام سریع تکنسین</b><p class="hint" style="margin:0">درخواست شما ثبت و کارشناسان در کوتاه‌ترین زمان برای هماهنگی تماس می‌گیرند.</p></div></div>
        <div class="benefit card"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l7 4v6c0 5-3.5 8-7 10-3.5-2-7-5-7-10V6z"/></svg><div><b>قطعات اصلی</b><p class="hint" style="margin:0">قطعات با تأیید سازگاری مدل دستگاه و ضمانت اصالت عرضه می‌شوند.</p></div></div>
        <div class="benefit card"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg><div><b>قیمت شفاف</b><p class="hint" style="margin:0">هزینه عیب‌یابی و تعمیر پیش از شروع کار به‌صورت مکتوب اعلام می‌شود.</p></div></div>
      </div>
    </div>
    <div>
      <div class="section-head" style="text-align:right;margin-bottom:20px">
        <span class="kicker">برندهای تحت پشتیبانی</span>
        <h2>تعمیر و قطعات همه برندها</h2>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($brands as $b): ?>
          <a href="/forushgah?brand[]=<?= (int)$b['id'] ?>" class="badge badge-stock" style="font-size:.85rem;padding:8px 16px"><?= e($b['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($latest)): ?>
<section class="section" style="background:#fff;border-block:1px solid var(--line)">
  <div class="container">
    <div class="section-head"><span class="kicker">جدیدترین محصولات</span><h2>تازه‌های فروشگاه</h2></div>
    <div class="grid g-4"><?php foreach ($latest as $p) View::partial('partials/product-card', ['p' => $p]); ?></div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($articles)): ?>
<section class="section">
  <div class="container">
    <div class="section-head"><span class="kicker">بلاگ فنی</span><h2>مقالات و آموزش عیب‌یابی</h2></div>
    <div class="grid g-3">
      <?php foreach ($articles as $a): ?>
        <article class="card">
          <a href="/maghaleh/<?= e($a['slug']) ?>" style="display:block">
            <?php if (!empty($a['image_path'])): ?><img src="<?= e($a['image_path']) ?>" alt="<?= e($a['title']) ?>" loading="lazy" style="border-radius:10px;margin-bottom:12px"><?php else: ?><?= \TT\Placeholder::svg($a['title'], 640, 300) ?><?php endif; ?>
            <h3 style="font-size:1rem"><?= e($a['title']) ?></h3>
          </a>
          <p style="color:var(--muted);font-size:.86rem"><?= e(mb_substr(strip_tags((string)$a['excerpt']), 0, 120)) ?>…</p>
          <small style="color:var(--cyan-dark)"><?= e(shamsi_date($a['publish_at'] ?? $a['created_at'] ?? null)) ?></small>
        </article>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:22px"><a class="btn btn-navy" href="/maghale-at">همه مقالات</a></div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($faqs)): ?>
<section class="section" style="background:#fff;border-block:1px solid var(--line)">
  <div class="container" style="max-width:820px">
    <div class="section-head"><span class="kicker">سؤالات متداول</span><h2>پاسخ پرتکرارترین پرسش‌ها</h2></div>
    <?php foreach ($faqs as $q): ?>
      <details class="faq"><summary><?= e($q['question']) ?><span aria-hidden="true">＋</span></summary><p><?= nl2br(e($q['answer'])) ?></p></details>
    <?php endforeach; ?>
    <div style="text-align:center;margin-top:18px"><a href="/faq">مشاهده همه سؤالات ←</a></div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="card" style="background:linear-gradient(135deg,var(--navy),#0B3B52);color:#fff;text-align:center;padding:44px 24px">
      <h2 style="color:#fff">دستگاهتان خراب شده؟ همین حالا درخواست بدهید</h2>
      <p style="color:#C9D6E8;max-width:52ch;margin-inline:auto">فرم درخواست تعمیر را پر کنید؛ کارشناس توس تعمیر برای عیب‌یابی و اعلام هزینه با شما تماس می‌گیرد.</p>
      <div class="hero-cta" style="justify-content:center">
        <a class="btn btn-primary" href="/darkhast-tamir">ثبت درخواست تعمیر</a>
        <a class="btn btn-outline" href="https://wa.me/<?= e(setting('contact_whatsapp', '989213624847')) ?>" rel="nofollow noopener" target="_blank">مشاوره در واتساپ</a>
      </div>
    </div>
  </div>
</section>
