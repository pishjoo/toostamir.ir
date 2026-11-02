<?php
/**
 * توس تعمیر - نقطه ورود وب‌سایت (Front Controller)
 * toostamir.ir
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/* ---------- مسیرهای تمیز (SEO-Friendly) ----------
 * ساختار URL:
 *  /                          خانه
 *  /khedmat                   لیست خدمات
 *  /khedmat/{slug}            صفحه خدمت
 *  /tamirboard                تعمیر برد الکترونیک (صفحه تخصصی)
 *  /forushgah                 فروشگاه (+ ?category_slug=&brand[]=&min_price=&max_price=&in_stock=&sort=&q=&page=)
 *  /mahsol/{slug}             محصول
 *  /maghale-at               مقالات
 *  /maghaleh/{slug}           مقاله
 *  /faq                       سؤالات متداول
 *  /darkhast-tamir            فرم درخواست تعمیر (GET/POST)
 *  /peigiri-darkhast          پیگیری درخواست تعمیر
 *  /tamirasal-khana-dar-mashhad-tos-tamir  تماس با ما
 *  /sabad-kharid              سبد خرید
 *  /takmil-safarish           تسویه‌حساب
 *  /order/place               ثبت نهایی سفارش (POST)
 *  /pay/{tracking}            صفحه پرداخت
 *  /pay/start                 شروع پرداخت (POST)
 *  /pay/callback/{gw}         بازگشت از درگاه
 *  /peigiri-order             پیگیری سفارش
 *  /comment/store             ثبت نظر (POST AJAX)
 *  /sitemap.xml  /robots.txt
 *  /{page-slug}               صفحات قانونی و درباره ما
 */

$router = new Router();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
$method = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'POST' : 'GET';

/* GET */
$router->get('/',                        [FrontController::class, 'home']);
$router->get('/khedmat',                 [FrontController::class, 'services']);
$router->get('/khedmat-tamirat-lavamzesh-khaneghi-dar-mashhad', [FrontController::class, 'services']);
$router->get('/tamirboard',              [FrontController::class, 'board']);
$router->get('/dvazdah-bazar-device-ha', [FrontController::class, 'devices']);
$router->get('/daste-bandi-dastgah-ha',  [FrontController::class, 'devices']);
$router->get('/forushgah',               [FrontController::class, 'shop']);
$router->get('/jostejo',                 [FrontController::class, 'search']);
$router->get('/maghale-at',              [FrontController::class, 'articles']);
$router->get('/faq',                     [FrontController::class, 'faqPage']);
$router->get('/soalat-pasokh',           [FrontController::class, 'faqPage']);
$router->get('/sitemap-pages',           [FrontController::class, 'sitemapPage']);
$router->get('/darkhast-tamir',          [FrontController::class, 'requestForm']);
$router->get('/peigiri-darkhast',        [FrontController::class, 'trackRequest']);
$router->get('/tamirasal-khana-dar-mashhad-tos-tamir', [FrontController::class, 'contact']);
$router->get('/sabad-kharid',            [CartController::class, 'view']);
$router->get('/takmil-safarish',         [CartController::class, 'checkout']);
$router->get('/peigiri-order',           [CartController::class, 'trackOrder']);
$router->get('/khedmat/{slug}',          [FrontController::class, 'service']);
$router->get('/mahsol/{slug}',           [FrontController::class, 'product']);
$router->get('/maghaleh/{slug}',         [FrontController::class, 'article']);
$router->get('/pay/{tracking}',          [CartController::class, 'payPage']);
$router->get('/sitemap.xml',             [SeoController::class, 'sitemap']);
$router->get('/robots.txt',              [SeoController::class, 'robots']);

/* POST */
$router->post('/darkhast-tamir',         [FrontController::class, 'requestSubmit']);
$router->post('/peigiri-darkhast',       [FrontController::class, 'trackRequest']);
$router->post('/cart/add',               [CartController::class, 'add']);
$router->post('/cart/update',            [CartController::class, 'update']);
$router->post('/cart/remove',            [CartController::class, 'removeItem']);
$router->post('/cart/coupon',            [CartController::class, 'coupon']);
$router->post('/order/place',            [CartController::class, 'placeOrder']);
$router->post('/pay/start',              [CartController::class, 'payStart']);
$router->post('/comment/store',          [FrontController::class, 'commentSubmit']);
$router->get('/pay/callback/{gw}',       [CartController::class, 'callback']);
$router->post('/pay/callback/{gw}',      [CartController::class, 'callback']);

/* fallback: صفحات داینامیک با slug */
if ($method === 'GET') {
    $seg = trim($path, '/');
    if ($seg !== '' && !str_contains($seg, '/') && preg_match('/^[a-z0-9\-]{2,80}$/i', $seg)) {
        // روتر 404 می‌دهد؛ قبلش pages را چک کنیم
        $p = Page::bySlug($seg);
        if ($p && (int)$p['is_published'] === 1) {
            (new FrontController())->page($seg);
            exit;
        }
    }
}

$router->dispatch($method, $path);
