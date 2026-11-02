<?php
/**
 * توس تعمیر - کنترلر پایه وب‌سایت (public)
 */
class FrontController {
    protected function view(string $tpl, array $data = [], string $layout = 'layouts/main'): void {
        View::render($tpl, $data, $layout);
    }

    /** داده‌های مشترک هدر/فوتر */
    protected function share(array $data = []): array {
        $data['nav_categories'] = Category::activeTree();
        $data['footer_pages']   = Page::all('is_published=1 AND show_in_footer=1', [], 'id ASC');
        $data['site_name']      = setting('site_name', 'توس تعمیر');
        $data['site_logo']      = setting('site_logo', '');
        return $data;
    }

    public function home(): void {
        $this->view('front/home', $this->share([
            'hero_title'    => setting('home_hero_title', 'تعمیر تخصصی لوازم خانگی و فروش قطعات اصلی'),
            'hero_text'     => setting('home_hero_text', 'با بیش از یک دهه تجربه، دستگاه شما را با گارانتی خدمات باز می‌گردانیم.'),
            'services'      => array_slice(Service::published(), 0, 8),
            'featured'      => Product::featured(8),
            'latest'        => Product::latest(4),
            'articles'      => Article::published(3),
            'brands'        => Brand::activeList(),
            'stats'         => [
                'repairs'  => num_input(setting('stat_repairs', '12000')),
                'parts'    => num_input(setting('stat_parts', '850')),
                'cities'   => num_input(setting('stat_cities', '۲۲'. '')) ?: 22,
                'years'    => num_input(setting('stat_years', '12')),
            ],
            'title' => setting('seo_home_title', 'توس تعمیر | تعمیرگاه تخصصی لوازم خانگی و فروشگاه قطعات'),
            'meta_description' => setting('seo_home_description', 'تعمیر تخصصی لباسشویی، ظرفشویی، یخچال، پکیج و فروش قطعات اصلی با ارسال سراسری.'),
        ]));
    }

    public function services(): void {
        $list = Service::published();
        $this->view('front/services', $this->share([
            'services' => $list,
            'title' => 'خدمات تعمیر - ' . setting('site_name', 'توس تعمیر'),
            'meta_description' => 'لیست خدمات تعمیر تخصصی لوازم خانگی: لباسشویی، ظرفشویی، یخچال، پکیج، کولر و برد الکترونیک.',
        ]));
    }

    public function service(string $slug): void {
        $s = Service::bySlug($slug);
        if (!$s || !$s['is_published']) abort404();
        Service::incViewIfColumn((int)$s['id']);
        $sub = Service::all('parent_id=? AND is_published=1', [(int)$s['id']], 'sort_order ASC');
        $this->view('front/service', $this->share([
            'service' => $s,
            'children' => $sub,
            'faqs' => Faq::forTarget('service', (int)$s['id']),
            'related' => array_slice(Service::published(), 0, 4),
            'title' => ($s['seo_title'] ?: $s['name'] . ' - ' . setting('site_name', 'توس تعمیر')),
            'meta_description' => $s['seo_description'] ?: mb_substr(strip_tags((string)$s['excerpt']), 0, 160),
            'canonical' => $s['canonical_url'] ?: null,
            'robots_meta' => $s['robots_meta'],
        ]));
    }

    public function shop(): void {
        $f = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'category' => (int)($_GET['category'] ?? 0),
            'brand' => (array)($_GET['brand'] ?? []),
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'in_stock' => !empty($_GET['in_stock']),
            'sort' => (string)($_GET['sort'] ?? ''),
            'page' => (int)($_GET['page'] ?? 1),
        ];
        $res = Product::search($f);
        $cat = $f['category'] ? Category::find($f['category']) : null;
        $this->view('front/shop', $this->share([
            'res' => $res,
            'filters' => $f,
            'categories' => Category::activeTree(),
            'brands' => Brand::activeList(),
            'current_cat' => $cat,
            'title' => $cat ? ('خرید ' . $cat['name'] . ' - ' . setting('site_name')) : 'فروشگاه قطعات - ' . setting('site_name'),
            'meta_description' => $cat && $cat['seo_description'] ? $cat['seo_description'] : 'فروشگاه قطعات اصلی لوازم خانگی؛ قیمت، موجودی و ارسال سریع.',
        ]));
    }

    public function product(string $slug): void {
        $p = Product::bySlug($slug);
        if (!$p) abort404();
        Product::incView((int)$p['id']);
        $images = Product::images((int)$p['id']);
        if (!$images && $p['og_image']) $images = [['image_path' => $p['og_image'], 'alt_text' => $p['alt_text']]];
        $related = Database::all('SELECT * FROM products WHERE is_published=1 AND id!=? AND (category_id=? OR brand_id=?) ORDER BY RAND() LIMIT 4',
            [(int)$p['id'], (int)$p['category_id'], (int)$p['brand_id']]);
        $this->view('front/product', $this->share([
            'product' => $p,
            'images' => $images,
            'related' => $related,
            'comments' => Comment::approvedFor('product', (int)$p['id']),
            'faqs' => Faq::forTarget('product', (int)$p['id']),
            'title' => $p['seo_title'] ?: ($p['name'] . ' - قیمت و خرید | ' . setting('site_name')),
            'meta_description' => $p['seo_description'] ?: mb_substr(strip_tags((string)$p['short_description']), 0, 160),
            'canonical' => $p['canonical_url'] ?: null,
            'robots_meta' => $p['robots_meta'],
            'schema_json' => SeoMeta::productSchema($p, $images[0]['image_path'] ?? null),
        ]));
    }

    public function articles(): void {
        $f = ['cat' => trim((string)($_GET['cat'] ?? '')), 'q' => trim((string)($_GET['q'] ?? '')), 'page' => (int)($_GET['page'] ?? 1)];
        $res = Article::paginate($f);
        $this->view('front/articles', $this->share([
            'res' => $res, 'filters' => $f,
            'cats' => ArticleCategory::all('', [], 'sort_order ASC'),
            'title' => 'مقالات و آموزش‌ها - ' . setting('site_name'),
            'meta_description' => 'مقالات آموزشی عیب‌یابی و نگهداری لوازم خانگی - بلاگ توس تعمیر',
        ]));
    }

    public function article(string $slug): void {
        $a = Article::bySlug($slug);
        if (!$a) abort404();
        Article::incView((int)$a['id']);
        $related = Database::all("SELECT slug,title,image_path,excerpt FROM articles WHERE status='published' AND id!=? AND category_id=? ORDER BY publish_at DESC LIMIT 3",
            [(int)$a['id'], (int)$a['category_id']]);
        $this->view('front/article', $this->share([
            'article' => $a,
            'related' => $related,
            'comments' => Comment::approvedFor('article', (int)$a['id']),
            'faqs' => Faq::forTarget('article', (int)$a['id']),
            'title' => $a['seo_title'] ?: ($a['title'] . ' - ' . setting('site_name')),
            'meta_description' => $a['seo_description'] ?: mb_substr(strip_tags((string)$a['excerpt']), 0, 160),
            'canonical' => $a['canonical_url'] ?: null,
            'robots_meta' => $a['robots_meta'],
            'schema_json' => SeoMeta::articleSchema($a),
        ]));
    }

    public function requestForm(): void {
        $this->view('front/request', $this->share([
            'services' => Service::published(),
            'title' => 'ثبت درخواست تعمیر - ' . setting('site_name'),
            'meta_description' => 'درخواست تعمیر لوازم خانگی خود را ثبت کنید؛ کارشناسان توس تعمیر با شما تماس می‌گیرند.',
        ]));
    }

    public function requestSubmit(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/darkhast-tamir');
        if (!csrf_verify()) { flash('error', 'اعتبار فرم نامعتبر بود. دوباره تلاش کنید.'); redirect('/darkhast-tamir'); }

        // تله‌فرم ضد ربات
        if (!empty($_POST['website'])) { redirect('/darkhast-tamir'); }
        // محدودیت نرخ بر اساس سشن/IP
        $last = $_SESSION['last_request_ts'] ?? 0;
        if (time() - $last < 20) { flash('error', 'لطفاً چند لحظه صبر کنید و دوباره تلاش کنید.'); redirect('/darkhast-tamir'); }

        $name  = clean_text($_POST['name'] ?? '');
        $mobile = normalize_mobile((string)($_POST['mobile'] ?? ''));
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $problem = clean_text($_POST['problem_description'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 3) $errors[] = 'نام و نام خانوادگی را کامل وارد کنید.';
        if (!valid_ir_mobile((string)($_POST['mobile'] ?? ''))) $errors[] = 'شماره موبایل معتبر نیست (۰۹xxxxxxxxx).';
        if ($serviceId <= 0 || !Service::find($serviceId)) $errors[] = 'خدمت موردنظر را انتخاب کنید.';
        if (mb_strlen($problem) < 10) $errors[] = 'شرح مشکل را با جزئیات بیشتر بنویسید (حداقل ۱۰ حرف).';

        if ($errors) { foreach ($errors as $er) flash('error', $er); $_SESSION['old_form'] = $_POST; redirect('/darkhast-tamir'); }

        $code = $this->uniqueTracking('TT-');
        $id = ServiceRequest::insert([
            'tracking_code' => $code,
            'service_id' => $serviceId,
            'device_type' => clean_text($_POST['device_type'] ?? '') ?: null,
            'device_brand' => clean_text($_POST['device_brand'] ?? '') ?: null,
            'device_model' => clean_text($_POST['device_model'] ?? '') ?: null,
            'name' => $name,
            'mobile' => $mobile,
            'problem_description' => $problem,
            'address' => clean_text($_POST['address'] ?? '') ?: null,
            'preferred_time' => clean_text($_POST['preferred_time'] ?? '') ?: null,
            'extra_notes' => clean_text($_POST['extra_notes'] ?? '') ?: null,
            'status' => 'new',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        // آپلود تصاویر (حداکثر ۳)
        if ($id) {
            for ($i = 0; $i < 3; $i++) {
                if (!empty($_FILES['images']['name'][$i])) {
                    $path = upload_image($_FILES['images'], $i);
                    if ($path) Database::run('INSERT INTO service_request_images (request_id, image_path) VALUES (?,?)', [$id, $path]);
                }
            }
        }
        unset($_SESSION['old_form']);
        $_SESSION['last_request_ts'] = time();
        audit_log('service_request_created', 'service_request', $id, 'tracking=' . $code);
        flash('success', 'درخواست شما با کد رهگیری ' . fa_digits($code) . ' ثبت شد. کارشناسان ما به‌زودی تماس می‌گیرند.');
        redirect('/پیگیری-درخواست?code=' . urlencode($code));
    }

    public function trackRequest(): void {
        $code = strtoupper(trim((string)($_GET['code'] ?? '')));
        $req = $code !== '' ? ServiceRequest::byTracking($code) : null;
        $history = $req ? Database::all('SELECT * FROM service_request_history WHERE request_id=? ORDER BY id ASC', [(int)$req['id']]) : [];
        $this->view('front/track', $this->share([
            'code' => $code, 'req' => $req, 'history' => $history,
            'title' => 'پیگیری درخواست تعمیر - ' . setting('site_name'),
            'meta_description' => 'وضعیت درخواست تعمیر خود را با کد رهگیری پیگیری کنید.',
            'robots_meta' => 'noindex,follow',
        ]));
    }

    public function page(string $slug): void {
        $p = Page::bySlug($slug);
        if (!$p || !$p['is_published']) abort404();
        $this->view('front/page', $this->share([
            'page' => $p,
            'title' => $p['seo_title'] ?: ($p['title'] . ' - ' . setting('site_name')),
            'meta_description' => $p['seo_description'] ?: mb_substr(strip_tags((string)$p['body']), 0, 160),
            'canonical' => $p['canonical_url'] ?: null,
            'robots_meta' => $p['robots_meta'],
        ]));
    }

    public function commentSubmit(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) json_out(['ok' => false, 'msg' => 'اعتبار نامعتبر'], 419);
        $type = (string)($_POST['target_type'] ?? '');
        $id = (int)($_POST['target_id'] ?? 0);
        $body = clean_text($_POST['body'] ?? '');
        $name = clean_text($_POST['name'] ?? '');
        if (!in_array($type, ['article', 'product', 'service'], true) || $id <= 0 || mb_strlen($body) < 3 || mb_strlen($name) < 2) {
            json_out(['ok' => false, 'msg' => 'اطلاعات نظرات ناقص است.'], 422);
        }
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
        Comment::insert(['target_type' => $type, 'target_id' => $id, 'name' => $name,
            'mobile' => valid_ir_mobile((string)($_POST['mobile'] ?? '')) ? normalize_mobile((string)$_POST['mobile']) : null,
            'body' => mb_substr($body, 0, 1500), 'rating' => $rating ?: null, 'is_approved' => 0]);
        json_out(['ok' => true, 'msg' => 'نظر شما ثبت شد و پس از تأیید نمایش داده می‌شود.']);
    }

    private function uniqueTracking(string $prefix): string {
        do { $code = $prefix . strtoupper(bin2hex(random_bytes(3))); }
        while (Order::byTracking($code) || ServiceRequest::byTracking($code));
        return $code;
    }
}

function abort404(): never {
    http_response_code(404);
    View::render('front/404', ['title' => 'صفحه یافت نشد'], 'layouts/main');
    exit;
}
