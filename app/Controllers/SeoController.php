<?php
/**
 * توس تعمیر - کنترلرهای سئو: sitemap پویا، robots.txt، ریدایرکت‌ها
 */
class SeoController {

    public function sitemap(): void {
        $urls = [];
        $add = function (string $loc, ?string $lastmod = null, string $freq = 'weekly', string $prio = '0.6') use (&$urls) {
            $urls[] = ['loc' => $loc, 'lastmod' => $lastmod, 'freq' => $freq, 'prio' => $prio];
        };
        $base = site_url('/');
        $add($base, date('Y-m-d'), 'daily', '1.0');
        foreach (['khedmat', 'darkhast-tamir', 'tamirboard'] as $p) $add($base . $p);
        $add($base . 'maghale-at', null, 'daily', '0.7');
        $add($base . 'faq', null, 'monthly', '0.4');

        foreach (Database::all("SELECT slug,updated_at FROM services WHERE is_published=1 AND COALESCE(robots_meta,'') NOT LIKE '%noindex%'") as $s)
            $add($base . 'khedmat/' . $s['slug'], substr((string)$s['updated_at'], 0, 10), 'weekly', '0.8');
        foreach (Database::all("SELECT slug,updated_at FROM products WHERE is_published=1 AND COALESCE(robots_meta,'') NOT LIKE '%noindex%'") as $p)
            $add($base . 'mahsol/' . $p['slug'], substr((string)$p['updated_at'], 0, 10), 'weekly', '0.9');
        foreach (Database::all("SELECT slug,updated_at FROM articles WHERE status='published' AND (publish_at IS NULL OR publish_at<=NOW()) AND COALESCE(robots_meta,'') NOT LIKE '%noindex%'") as $a)
            $add($base . 'maghaleh/' . $a['slug'], substr((string)$a['updated_at'], 0, 10), 'monthly', '0.7');
        foreach (Database::all("SELECT slug,updated_at FROM pages WHERE is_published=1 AND COALESCE(robots_meta,'') NOT LIKE '%noindex%'") as $g)
            $add($base . $g['slug'], substr((string)$g['updated_at'], 0, 10), 'monthly', '0.3');
        // دسته‌های فروشگاه با صفحه‌بندی ساده (تا ۵ صفحه)
        foreach (Database::all("SELECT slug FROM categories WHERE is_active=1") as $c) {
            $add($base . 'forushgah?category_slug=' . $c['slug'], null, 'weekly', '0.6');
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            if ($u['loc'] === '') continue;
            echo '<url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
            if ($u['lastmod']) echo '<lastmod>' . $u['lastmod'] . '</lastmod>';
            echo '<changefreq>' . $u['freq'] . '</changefreq><priority>' . $u['prio'] . '</priority></url>' . "\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(): void {
        header('Content-Type: text/plain; charset=utf-8');
        $mode = setting('seo_mode', 'all');
        $disallow = match ($mode) {
            'none'   => "Disallow: /\n",
            'static' => "Disallow: /forushgah\nDisallow: /mahsol/\n",
            default  => "Disallow: /panel\nDisallow: /pay/\nDisallow: /*?q=\nDisallow: /*&page=\nDisallow: /sabad-kharid\nDisallow: /takmil-safaresh\nDisallow: /peigiri-\n",
        };
        echo "User-agent: *\n" . $disallow;
        if (setting('seo_mode', 'all') !== 'none') echo 'Allow: /\n';
        echo 'Sitemap: ' . site_url('/sitemap.xml') . "\n";
        exit;
    }
}
