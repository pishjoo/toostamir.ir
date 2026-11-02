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
        $faqs = Faq::general(6);
        $this->view('front/home', $this->share([
            'hero_title'    => setting('home_hero_title', 'تعمیر تخصصی لوازم خانگی و فروش قطعات اصلی'),
            'hero_text'     => setting('home_hero_text', 'با بیش از یک دهه تجربه، دستگاه شما را با گارانتی خدمات باز می‌گردانیم.'),
            'services'      => array_slice(Service::published(), 0, 8),
            'featured'      => Product::featured(8),
            'latest'        => Product::latest(4),
            'articles'      => Article::published(3),
            'brands'        => Brand::activeList(),
            'faqs'          => $faqs,
            'faq_schema'    => SeoMeta::faqSchema($faqs),
            'stats'         => [
                'repairs'  => num_input(setting('stat_repairs', '12000')),
                'parts'    => num_input(setting('stat_parts', '850')),
                'cities'   => num_input(setting('stat_cities', '22')) ?: 22,
                'years'    => num_input(setting('stat_years', '12')),
            ],
            'title' => setting('seo_home_title', 'توس تعمیر | تعمیرگاه تخصصی لوازم خانگی و فروشگاه قطعات'),
            'meta_description' => setting('seo_home_description', 'تعمیر تخصصی لباسشویی، ظرفشویی، یخچال، پکیج و فروش قطعات اصلی با ارسال سراسری.'),
            'schema_json' => SeoMeta::organization(),
        ]));
    }

    public function services(): void {
        $list = Service::published();
        $this->view('front/services', $this->share([
            'services' => $list,
            'title' => 'خدمات تعمیر - ' . setting('site_name', 'توس تعمیر'),
            'meta_description' => 'لیست خدمات تعمیر تخصصی لوازم خانگی: لباسشویی، ظرفشویی، یخچال، پکیج، کولر و برد الکترونیک.',
            'breadcrumbs' => ['خانه' => '/', 'خدمات' => '/khedmat'],
        ]));
    }

    public function service(string $slug): void {
        $s = Service::bySlug($slug);
        if (!$s || !$s['is_published']) abort404();
        $sub = Service::all('parent_id=? AND is_published=1', [(int)$s['id']], 'sort_order ASC');
        $faqs = Faq::forTarget('service', (int)$s['id']);
        $this->view('front/service', $this->share([
            'service' => $s,
            'children' => $sub,
            'faqs' => $faqs,
            'faq_schema' => SeoMeta::faqSchema($faqs),
            'related' => array_slice(Service::published(), 0, 4),
            'title' => ($s['seo_title'] ?: $s['name'] . ' - ' . setting('site_name', 'توس تعمیر')),
            'meta_description' => $s['seo_description'] ?: mb_substr(strip_tags((string)$s['excerpt']), 0, 160),
            'canonical' => $s['canonical_url'] ?: null,
            'robots_meta' => $s['robots_meta'],
            'og_image' => $s['og_image'] ?: $s['image_path'] ?: null,
            'schema_json' => SeoMeta::serviceSchema($s),
            'breadcrumbs' => ['خانه' => '/', 'خدمات' => '/khedmat', $s['name'] => '/khedmat/' . $s['slug']],
        ]));
    }

    /** صفحه اختصاصی تعمیر برد */
    public function board(): void {
        $boards = Database::all("SELECT * FROM services WHERE is_published=1 AND (is_board_service=1 OR slug LIKE '%board%' OR name LIKE '%برد%') ORDER BY sort_order ASC");
        $primary = null;
        foreach ($boards as $b) if ($b['slug'] === 'tamir-board-elektronik') { $primary = $b; break; }
        $primary = $primary ?: ($boards[0] ?? null);
        $faqs = $primary ? Faq::forTarget('service', (int)$primary['id']) : Faq::general(6);
        $this->view('front/board', $this->share([
            'service' => $primary,
            'boards' => $boards,
            'faqs' => $faqs,
            'faq_schema' => SeoMeta::faqSchema($faqs),
            'title' => setting('board_seo_title', 'تعمیر تخصصی برد الکترونیک لوازم خانگی در مشهد | توس تعمیر'),
            'meta_description' => setting('board_seo_description', 'تعمیر برد لباسشویی، یخچال، کولر گازی و پکیج با تجهیزات SMD و گارانتی؛ عیب‌یابی دقیق و قیمت شفاف.'),
            'schema_json' => $primary ? SeoMeta::serviceSchema($primary) : SeoMeta::organization(),
            'breadcrumbs' => ['خانه' => '/', 'تعمیر برد' => '/tamirboard'],
        ]));
    }

    public function shop(): void {
        $f = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'category' => (int)($_GET['category'] ?? 0),
            'category_slug' => trim((string)($_GET['category_slug'] ?? '')),
            'brand' => (array)($_GET['brand'] ?? []),
            'min_price' => $_GET['min_price'] ?? '',
            'max_price' => $_GET['max_price'] ?? '',
            'in_stock' => !empty($_GET['in_stock']),
            'sort' => (string)($_GET['sort'] ?? ''),
            'page' => (int)($_GET['page'] ?? 1),
        ];
        if ($f['category'] === 0 && $f['category_slug'] !== '') {
            $c = Category::bySlug($f['category_slug']);
            if ($c) $f['category'] = (int)$c['id'];
        }
        $res = Product::search($f);
        $cat = $f['category'] ? Category::find($f['category']) : null;
        $baseQuery = http_build_query(array_filter(['q' => $f['q'], 'category' => $f['category'] ?: null,
            'min_price' => $f['min_price'] ?: null, 'max_price' => $f['max_price'] ?: null,
            'in_stock' => $f['in_stock'] ?: null, 'sort' => $f['sort'] ?: null]));
        $this->view('front/shop', $this->share([
            'res' => $res,
            'filters' => $f,
            'categories' => Category::activeTree(),
            'brands' => Brand::activeList(),
            'current_cat' => $cat,
            'pagination' => paginate_links($res['page'], $res['pages'], '/forushgah' . ($baseQuery !== '' ? '?' . $baseQuery : '')),
            'title' => $cat ? ('خرید ' . $cat['name'] . ' - ' . setting('site_name')) : 'فروشگاه قطعات یدکی - ' . setting('site_name'),
            'meta_description' => $cat && $cat['seo_description'] ? $cat['seo_description'] : 'فروشگاه قطعات اصلی لوازم خانگی؛ قیمت، موجودی و ارسال سریع به سراسر ایران.',
            'breadcrumbs' => ['خانه' => '/', 'فروشگاه' => '/forushgah'] + ($cat ? [$cat['name'] => '/forushgah?category=' . $cat['id']] : []),
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
        $faqs = Faq::forTarget('product', (int)$p['id']);
        $this->view('front/product', $this->share([
            'product' => $p,
            'images' => $images,
            'related' => $related,
            'comments' => Comment::approvedFor('product', (int)$p['id']),
            'faqs' => $faqs,
            'faq_schema' => SeoMeta::faqSchema($faqs),
            'title' => $p['seo_title'] ?: ($p['name'] . ' - قیمت و خرید | ' . setting('site_name')),
            'meta_description' => $p['seo_description'] ?: mb_substr(strip_tags((string)$p['short_description']), 0, 160),
            'canonical' => $p['canonical_url'] ?: null,
            'robots_meta' => $p['robots_meta'],
            'og_image' => $p['og_image'] ?: ($images[0]['image_path'] ?? null),
            'schema_json' => SeoMeta::productSchema($p, $images[0]['image_path'] ?? null),
            'breadcrumbs' => ['خانه' => '/', 'فروشگاه' => '/forushgah', $p['name'] => '/mahsol/' . $p['slug']],
        ]));
    }

    public function articles(): void {
        $f = ['cat' => trim((string)($_GET['cat'] ?? '')), 'q' => trim((string)($_GET['q'] ?? '')), 'page' => (int)($_GET['page'] ?? 1)];
        $res = Article::paginate($f);
        $this->view('front/articles', $this->share([
            'res' => $res, 'filters' => $f,
            'cats' => ArticleCategory::all('', [], 'sort_order ASC'),
            'pagination' => paginate_links($res['page'], $res['pages'], '/maghale-at' . ($f['cat'] !== '' ? '?cat=' . urlencode($f['cat']) : '')),
            'title' => 'مقالات و آموزش‌ها - ' . setting('site_name'),
            'meta_description' => 'مقالات آموزشی عیب‌یابی و نگهداری لوازم خانگی - بلاگ توس تعمیر',
            'breadcrumbs' => ['خانه' => '/', 'مقالات' => '/maghale-at'],
        ]));
    }

    public function article(string $slug): void {
        $a = Article::bySlug($slug);
        if (!$a) abort404();
        Article::incView((int)$a['id']);
        $related = Database::all("SELECT slug,title,image_path,excerpt FROM articles WHERE status='published' AND id!=? AND category_id=? ORDER BY publish_at DESC LIMIT 3",
            [(int)$a['id'], (int)$a['category_id']]);
        $faqs = Faq::forTarget('article', (int)$a['id']);
        $this->view('front/article', $this->share([
            'article' => $a,
            'tags' => ArticleTag::forArticle((int)$a['id']),
            'related' => $related,
            'comments' => Comment::approvedFor('article', (int)$a['id']),
            'faqs' => $faqs,
            'faq_schema' => SeoMeta::faqSchema($faqs),
            'title' => $a['seo_title'] ?: ($a['title'] . ' - ' . setting('site_name')),
            'meta_description' => $a['seo_description'] ?: mb_substr(strip_tags((string)$a['excerpt']), 0, 160),
            'canonical' => $a['canonical_url'] ?: null,
            'robots_meta' => $a['robots_meta'],
            'og_image' => $a['og_image'] ?: $a['image_path'] ?: null,
            'schema_json' => SeoMeta::articleSchema($a),
            'breadcrumbs' => ['خانه' => '/', 'مقالات' => '/maghale-at', $a['title'] => '/maghaleh/' . $a['slug']],
        ]));
    }

    public function faqPage(): void {
        $faqs = Faq::general();
        $this->view('front/faq', $this->share([
            'faqs' => $faqs,
            'faq_schema' => SeoMeta::faqSchema($faqs),
            'title' => 'سؤالات متداول - ' . setting('site_name', 'توس تعمیر'),
            'meta_description' => 'پاسخ رایج‌ترین سؤالات درباره تعمیر لوازم خانگی، هزینه بازدید، گارانتی و ارسال قطعات.',
            'breadcrumbs' => ['خانه' => '/', 'سؤالات متداول' => '/faq'],
        ]));
    }

    public function requestForm(): void {
        $this->view('front/request', $this->share([
            'services' => Service::published(),
            'old' => (array)($_SESSION['old_form'] ?? []),
            'title' => 'ثبت درخواست تعمیر - ' . setting('site_name'),
            'meta_description' => 'درخواست تعمیر لوازم خانگی خود را ثبت کنید؛ کارشناسان توس تعمیر با شما تماس می‌گیرند.',
            'breadcrumbs' => ['خانه' => '/', 'درخواست تعمیر' => '/darkhast-tamir'],
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
                    $path = upload_image($_FILES['images'], $i, 'requests');
                    if ($path) Database::run('INSERT INTO service_request_images (request_id, image_path) VALUES (?,?)', [$id, $path]);
                }
            }
        }
        unset($_SESSION['old_form']);
        $_SESSION['last_request_ts'] = time();
        $_SESSION['last_request_tracking'] = $code;
        audit_log('service_request_created', 'service_request', $id, 'tracking=' . $code);
        flash('success', 'درخواست شما با کد رهگیری ' . fa_digits($code) . ' ثبت شد. کارشناسان ما به‌زودی تماس می‌گیرند.');
        redirect('/peigiri-darkhast?code=' . urlencode($code));
    }

    public function trackRequest(): void {
        $code = strtoupper(trim((string)($_GET['code'] ?? ($_SESSION['last_request_tracking'] ?? ''))));
        $req = $code !== '' ? ServiceRequest::byTracking($code) : null;
        $history = $req ? Database::all('SELECT * FROM service_request_history WHERE request_id=? ORDER BY id ASC', [(int)$req['id']]) : [];
        $this->view('front/track', $this->share([
            'code' => $code, 'req' => $req, 'history' => $history,
            'title' => 'پیگیری درخواست تعمیر - ' . setting('site_name'),
            'meta_description' => 'وضعیت درخواست تعمیر خود را با کد رهگیری پیگیری کنید.',
            'robots_meta' => 'noindex,follow',
        ]));
    }

    public function contact(): void {
        $sent = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) { flash('error', 'اعتبار فرم منقضی شده است.'); redirect('/tamirasal-khana-dar-mashhad-tos-tamir'); }
            $name = clean_text($_POST['name'] ?? '');
            $mobile = (string)($_POST['mobile'] ?? '');
            $message = clean_text($_POST['message'] ?? '');
            if (mb_strlen($name) < 2 || mb_strlen($message) < 5) {
                flash('error', 'نام و پیام را کامل وارد کنید.');
            } elseif ($mobile !== '' && !valid_ir_mobile($mobile)) {
                flash('error', 'شماره موبایل معتبر نیست.');
            } else {
                Database::run('INSERT INTO audit_logs (user_id, action, entity_type, description, ip_address) VALUES (NULL,?,?,?,?,?)',
                    ['contact_message', 'form', mb_substr($name . ' | ' . normalize_mobile($mobile) . ' | ' . $message, 0, 500), $_SERVER['REMOTE_ADDR'] ?? null]);
                flash('success', 'پیام شما ثبت شد؛ در اسرع وقت پاسخ می‌دهیم.');
                $sent = true;
            }
        }
        $this->view('front/contact', $this->share([
            'sent' => $sent,
            'title' => setting('contact_seo_title', 'تماس با ما - تعمیر لوازم خانگی در مشهد | توس تعمیر'),
            'meta_description' => setting('contact_seo_description', 'آدرس، تلفن و راه‌های ارتباطی توس تعمیر؛ مشاوره رایگان تعمیر لباسشویی، یخچال، پکیج و کولر گازی در مشهد.'),
            'schema_json' => SeoMeta::organization(),
            'breadcrumbs' => ['خانه' => '/', 'تماس با ما' => '/tamirasal-khana-dar-mashhad-tos-tamir'],
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
            'breadcrumbs' => ['خانه' => '/', $p['title'] => '/' . $p['slug']],
        ]));
    }


    public function devices(): void {
        $devices = DeviceCategory::activeList();
        $groups = [];
        foreach ($devices as $d) {
            $svc = !empty($d['service_id']) ? Service::find((int)$d['service_id']) : null;
            $href = $svc ? '/khedmat/' . $svc['slug'] : '/forushgah?category_slug=' . urlencode($d['slug']);
            if (!$svc) { $c = Category::bySlug($d['slug']); if ($c) $href = '/forushgah?category=' . (int)$c['id']; }
            $cnt = Product::count('is_published=1 AND category_id IN (SELECT id FROM categories WHERE slug=? OR parent_id=(SELECT id FROM categories WHERE slug=? LIMIT 1))', [$d['slug'], $d['slug']]);
            $groups[] = ['name' => $d['name'], 'icon' => (string)($d['icon'] ?? ''), 'href' => $href, 'cnt' => max($cnt, 0)];
        }
        $this->view('front/devices', $this->share([
            'groups' => $groups,
            'title' => 'دسته‌بندی دستگاه‌ها و تعمیرات - ' . setting('site_name'),
            'meta_description' => 'تعمیر و قطعات یدکی انواع لوازم خانگی در مشهد: لباسشویی، ظرفشویی، یخچال، پکیج، مایکروویو، جاروبرقی و برد الکترونیکی.',
            'breadcrumbs' => ['خانه' => '/', 'دستگاه‌ها' => '/dastebandi-dastgahha'],
        ]));
    }

    public function search(): void {
        $q = trim((string)($_GET['q'] ?? ''));
        $res = $q !== '' ? Product::search(['q' => $q, 'page' => max(1, (int)($_GET['page'] ?? 1)), 'sort' => '', 'brand' => [], 'min_price' => '', 'max_price' => '', 'in_stock' => false, 'category' => 0]) : ['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
        $arts = $q !== '' ? Article::paginate(['q' => $q, 'cat' => '', 'page' => 1])['items'] : [];
        $svcs = $q !== '' ? array_filter(Service::published(), fn($s) => mb_strpos(mb_strtolower($s['name'] . ' ' . (string)$s['excerpt']), mb_strtolower($q)) !== false) : [];
        $base = '/jostejo?q=' . urlencode($q);
        $this->view('front/search', $this->share([
            'q' => $q, 'res' => $res, 'articles' => $arts, 'services' => array_values($svcs),
            'pagination' => paginate_links($res['page'], $res['pages'], $base),
            'title' => ($q !== '' ? 'نتیجه جستجو برای «' . $q . '»' : 'جستجو') . ' - ' . setting('site_name'),
            'meta_description' => 'جستجو در محصولات، مقالات و خدمات توس تعمیر',
            'robots_meta' => 'noindex,follow',
            'breadcrumbs' => ['خانه' => '/', 'جستجو' => '/jostejo'],
        ]));
    }

    public function sitemapPage(): void {
        $this->view('front/sitemap', $this->share([
            'services' => Service::published(),
            'categories' => Category::activeTree(),
            'brands' => Brand::activeList(),
            'articles' => Article::published(200),
            'pages' => Page::all('is_published=1', [], 'id ASC'),
            'products' => Database::all('SELECT slug,name FROM products WHERE is_published=1 ORDER BY id DESC LIMIT 500'),
            'title' => 'نقشه سایت - ' . setting('site_name'),
            'meta_description' => 'نقشه سایت توس تعمیر؛ فهرست خدمات، محصولات، مقالات و صفحات.',
            'breadcrumbs' => ['خانه' => '/', 'نقشه سایت' => '/naghshe-sayt'],
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
        // محدودیت نرخ نظر از طریق سشن
        $cnt = (int)($_SESSION['comment_count'] ?? 0);
        if ($cnt >= 5 && (time() - (int)($_SESSION['comment_first_ts'] ?? 0)) < 600) {
            json_out(['ok' => false, 'msg' => 'تعداد نظرات شما زیاد است؛ کمی بعد تلاش کنید.'], 429);
        }
        if ($cnt === 0 || (time() - (int)($_SESSION['comment_first_ts'] ?? 0)) > 600) { $_SESSION['comment_first_ts'] = time(); $_SESSION['comment_count'] = 0; }
        $_SESSION['comment_count'] = $cnt + 1;

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
