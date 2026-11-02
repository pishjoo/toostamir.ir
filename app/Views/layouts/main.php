<?php
/**
 * توس تعمیر - لAYOUT اصلی وب‌سایت (RTL، فارسی، سئو)
 * متغیرها: $title, $meta_description, $canonical, $robots_meta, $og_image,
 *          $schema_json (array|null), $breadcrumbs (array|null), $content via View::content()
 */
$siteName = setting('site_name', 'توس تعمیر');
$domain   = rtrim(setting('site_domain', $GLOBALS['TT_APP']['domain']), '/');
$desc     = $meta_description ?? setting('site_description', 'تعمیر تخصصی لوازم خانگی و فروش قطعات یدکی در مشهد');
$canonical= $canonical ?? ($domain . ($_SERVER['REQUEST_URI'] ?? '/'));
$robots   = $robots_meta ?? 'index,follow';
$ogImg    = !empty($og_image) ? (str_starts_with($og_image, 'http') ? $og_image : $domain . $og_image)
            : (!empty(setting('seo_default_og_image')) ? $domain . setting('seo_default_og_image') : null);
$wa       = setting('contact_whatsapp', '989213624847');
$phone    = setting('contact_phone', '09213624847');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? $siteName) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($title ?? $siteName) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:locale" content="fa_IR">
<?php if ($ogImg): ?><meta property="og:image" content="<?= e($ogImg) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title ?? $siteName) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<?php if ($ogImg): ?><meta name="twitter:image" content="<?= e($ogImg) ?>"><?php endif; ?>
<?php if (!empty($favicon = setting('site_favicon', ''))): ?>
<link rel="icon" href="<?= e($favicon) ?>">
<?php else: ?>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%2310243A'/%3E%3Cpath d='M9 21l5-9 4 6 2-3 3 6z' stroke='%2300B8D9' stroke-width='2' fill='none' stroke-linejoin='round'/%3E%3C/svg%3E">
<?php endif; ?>
<link rel="preload" href="/assets/fonts/vazirmatn-var.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/style.css?v=1">
<?php if (!empty($schema_json)): ?>
<script type="application/ld+json"><?= json_encode($schema_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php if (!empty($breadcrumbs)): ?>
<script type="application/ld+json"><?= json_encode(SeoMeta::breadcrumbs($breadcrumbs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<?php if (($g = setting('google_search_console', '')) !== ''): ?>
<meta name="google-site-verification" content="<?= e($g) ?>">
<?php endif; ?>
<?php if (($ga = setting('google_analytics_id', '')) !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
<?php endif; ?>
<?php if (($gtm = setting('google_tag_manager', '')) !== ''): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtm) ?>');</script>
<?php endif; ?>
</head>
<body>

<a class="skip-link" href="#main">پرش به محتوا</a>

<header class="site-header" id="top">
  <div class="container header-inner">
    <button class="nav-toggle" aria-label="باز کردن منو" aria-expanded="false" data-nav-btn>
      <span></span><span></span><span></span>
    </button>
    <a class="brand" href="/">
      <?php if (!empty($site_logo ?? setting('site_logo', ''))): ?>
        <img src="<?= e(setting('site_logo')) ?>" alt="<?= e($siteName) ?>" width="140" height="44">
      <?php else: ?>
        <span class="brand-mark" aria-hidden="true">T</span>
        <span class="brand-text"><?= e($siteName) ?><small>فنی و مهندسی</small></span>
      <?php endif; ?>
    </a>
    <nav class="main-nav" id="mainNav" aria-label="منوی اصلی">
      <ul>
        <li><a href="/">خانه</a></li>
        <li><a href="/khedmat">خدمات تعمیر</a></li>
        <li><a href="/tamirboard">تعمیر برد</a></li>
        <li><a href="/forushgah">فروشگاه قطعات</a></li>
        <li><a href="/maghale-at">مقالات</a></li>
        <li><a href="/faq">سوالات</a></li>
        <li><a href="/darkhast-tamir" class="nav-cta">درخواست تعمیر</a></li>
      </ul>
    </nav>
    <div class="header-actions">
      <form class="header-search" action="/forushgah" method="get" role="search" aria-label="جستجوی محصولات">
        <input type="search" name="q" placeholder="جستجوی قطعه…" aria-label="جستجو" maxlength="80">
        <button type="submit" aria-label="جستجو">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </button>
      </form>
      <a class="icon-btn" href="/sabad-kharid" aria-label="سبد خرید" id="cartLink">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M2 3h3l2.6 12.4a1.5 1.5 0 0 0 1.5 1.1h8.7a1.5 1.5 0 0 0 1.4-1.1L22 7H6"/></svg>
        <?php $cc = 0; try { if (class_exists('Cart')) $cc = (int)(Cart::contents()['count'] ?? 0); } catch (Throwable) {} ?>
        <span class="cart-count" data-cart-count style="<?= $cc ? '' : 'display:none' ?>"><?= fa_digits((string)$cc) ?></span>
      </a>
      <a class="btn-call" href="tel:<?= e($phone) ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.13.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.45c.9.34 1.84.57 2.8.7A2 2 0 0 1 22 16.9z"/></svg>
        <span><?= fa_digits(e($phone)) ?></span>
      </a>
    </div>
  </div>
  <div class="mobile-dock" aria-hidden="false">
    <a href="tel:<?= e($phone) ?>" class="dock-item"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.13.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.45c.9.34 1.84.57 2.8.7A2 2 0 0 1 22 16.9z"/></svg>تماس</a>
    <a href="https://wa.me/<?= e($wa) ?>" class="dock-item wa" rel="noopener nofollow" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.1-1.3A10 10 0 1 0 12 2zm5.4 14.1c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1a11 11 0 0 1-5.4-4.8c-.6-1-1-2.3-.6-3.2.1-.4.5-1 .9-1.1.3-.1.7 0 .8.3l.7 1.4c.1.3.1.5-.1.7l-.4.5c-.1.2-.1.3 0 .5.5.9 1.5 1.9 2.4 2.3.2.1.4.1.5-.1l.5-.6c.2-.2.4-.2.6-.1l1.4.7c.3.1.4.5.3.8-.2.5-.6.9-1.2 1.2z"/></svg>واتساپ</a>
    <a href="/darkhast-tamir" class="dock-item primary">درخواست تعمیر</a>
    <a href="/sabad-kharid" class="dock-item">سبد خرید</a>
  </div>
</header>

<main id="main">
<?php if (function_exists('get_flashes')): foreach (get_flashes() as $fl): ?>
  <div class="container"><div class="flash flash-<?= e($fl['type']) ?>"><?= e($fl['msg'] ?? ($fl['message'] ?? '')) ?></div></div>
<?php endforeach; endif; ?>
<?php View::content(); ?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <section>
      <h3><?= e($siteName) ?></h3>
      <p><?= e(setting('footer_text', 'تعمیر تخصصی لوازم خانگی و فروش قطعات اصلی در مشهد')) ?></p>
      <p class="ft-addr">📍 <?= e(setting('contact_address', 'مشهد، الهیه، محمدیه ۱۰، علوی ثانی ۳، مجتمع تجاری بلور، واحد ۲')) ?></p>
      <p class="ft-hours">🕘 <?= e(setting('working_hours', 'شنبه تا پنجشنبه ۹ تا ۲۰')) ?></p>
    </section>
    <section>
      <h4>خدمات</h4>
      <ul>
        <li><a href="/khedmat/washing-machine-repair">تعمیر لباسشویی</a></li>
        <li><a href="/khedmat/dishwasher-repair">تعمیر ظرفشویی</a></li>
        <li><a href="/khedmat/refrigerator-repair">تعمیر یخچال و فریزر</a></li>
        <li><a href="/khedmat/wall-boiler-repair">تعمیر پکیج دیواری</a></li>
        <li><a href="/tamirboard">تعمیر برد الکترونیک</a></li>
      </ul>
    </section>
    <section>
      <h4>دسترسی سریع</h4>
      <ul>
        <li><a href="/forushgah">فروشگاه قطعات</a></li>
        <li><a href="/darkhast-tamir">درخواست تعمیر</a></li>
        <li><a href="/peigiri-order">پیگیری سفارش</a></li>
        <li><a href="/peigiri-darkhast">پیگیری درخواست</a></li>
        <li><a href="/tamirasal-khana-dar-mashhad-tos-tamir">تماس با ما</a></li>
      </ul>
    </section>
    <section>
      <h4>اطلاعات تماس</h4>
      <p><a href="tel:<?= e($phone) ?>"><?= fa_digits($phone) ?></a></p>
      <p><a href="https://wa.me/<?= e($wa) ?>" rel="noopener nofollow" target="_blank">ارتباط در واتساپ</a></p>
      <?php if (($ig = setting('instagram_url', '')) !== ''): ?><p><a href="<?= e($ig) ?>" rel="noopener nofollow" target="_blank">اینستاگرام</a></p><?php endif; ?>
      <?php if (($tg = setting('telegram_url', '')) !== ''): ?><p><a href="<?= e($tg) ?>" rel="noopener nofollow" target="_blank">تلگرام</a></p><?php endif; ?>
      <?php if (($map = setting('google_maps_link', '')) !== ''): ?><p><a href="<?= e($map) ?>" rel="noopener nofollow" target="_blank">مسیریابی گوگل مپ</a></p><?php endif; ?>
    </section>
  </div>
  <div class="footer-bottom container">
    <nav class="legal-links">
      <?php
      $fp = $footer_pages ?? [];
      foreach ($fp as $pg): ?><a href="/<?= e($pg['slug']) ?>"><?= e($pg['title']) ?></a><?php endforeach; ?>
    </nav>
    <p><?= e(setting('copyright_text', '© ' . shamsi_date(null) . ' توس تعمیر — تمامی حقوق محفوظ است.')) ?></p>
  </div>
</footer>

<button class="to-top" id="toTop" aria-label="بازگشت به بالا">↑</button>

<script src="/assets/js/main.js?v=1" defer></script>
</body>
</html>
