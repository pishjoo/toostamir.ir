<?php
/**
 * توس تعمیر - تولید خودکار متادیتا و Schema JSON-LD
 * هیچ داده ساختگی (امتیاز/نظر/مجوز) در schema قرار نمی‌گیرد مگر واقعی و تأییدشده.
 */
class SeoMeta {

    public static function organization(): array {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => ['Organization', 'LocalBusiness'],
            'name'     => setting('site_name', 'فنی و مهندسی توس تعمیر'),
            'url'      => site_url('/'),
            'telephone'=> setting('phone_primary', '09213624847'),
            'address'  => [
                '@type' => 'PostalAddress',
                'streetAddress' => setting('address_street', 'محمدیه ۱۰، علوی ثانی ۳، مجتمع تجاری بلور، واحد ۲'),
                'addressLocality' => setting('address_city', 'مشهد'),
                'addressRegion' => setting('address_province', 'خراسان رضوی'),
                'addressCountry' => 'IR',
            ],
            'areaServed' => setting('service_area', 'مشهد، الهیه'),
            'priceRange' => setting('schema_price_range', ''),
        ];
        if (setting('logo_path')) $data['logo'] = site_url(setting('logo_path'));
        if (setting('geo_lat') && setting('geo_lng')) {
            $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => setting('geo_lat'), 'longitude' => setting('geo_lng')];
        }
        $sameAs = array_values(array_filter([setting('social_instagram'), setting('social_telegram'), setting('social_whatsapp')]));
        if ($sameAs) $data['sameAs'] = $sameAs;
        if (setting('business_hours')) $data['openingHours'] = setting('business_hours');
        return self::clean($data);
    }

    public static function website(): array {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => setting('site_name', 'توس تعمیر'),
            'url' => site_url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => site_url('/فروشگاه?q={search_term_string}')],
                'query-input' => 'required name=search_term_string',
            ],
        ]);
    }

    public static function breadcrumbs(array $crumbs): array {
        $items = []; $n = 1;
        foreach ($crumbs as $name => $href) {
            $items[] = ['@type' => 'ListItem', 'position' => $n++, 'name' => $name, 'item' => is_int($href) ? $href : site_url((string)$href)];
        }
        return self::clean(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items]);
    }

    /** Product + Offer — فقط اطلاعات واقعی؛ بدون aggregateRating مگر نظرات تأییدشده وجود داشته باشد */
    public static function productSchema(array $p, ?string $image = null): array {
        $availability = match ($p['stock_status'] ?? '') {
            'in_stock' => 'https://schema.org/InStock',
            'low'      => 'https://schema.org/LimitedAvailability',
            default    => 'https://schema.org/OutOfStock',
        };
        $brandName = Database::scalar('SELECT name FROM brands WHERE id=?', [(int)($p['brand_id'] ?? 0)], null);
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $p['name'],
            'sku' => $p['sku'] ?? '',
            'mpn' => $p['part_code'] ?: null,
            'description' => trim(strip_tags((string)($p['short_description'] ?: $p['description'] ?? ''))),
            'image' => $image ? site_url($image) : null,
            'brand' => $brandName ? ['@type' => 'Brand', 'name' => $brandName] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => site_url('/mahsol/' . $p['slug']),
                'priceCurrency' => 'IRR',
                'price' => (string)(int)Product::effectivePrice($p),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $availability,
            ],
        ];
        // فقط در صورت وجود نظرات تأییدشده واقعی
        $cnt = (int)Database::scalar("SELECT COUNT(*) FROM comments WHERE target_type='product' AND target_id=? AND is_approved=1", [(int)$p['id']], 0);
        if ($cnt > 0) {
            $avg = (float)Database::scalar("SELECT AVG(rating) FROM comments WHERE target_type='product' AND target_id=? AND is_approved=1 AND rating IS NOT NULL", [(int)$p['id']], 0);
            if ($avg > 0) {
                $data['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => round($avg, 2), 'reviewCount' => $cnt];
            }
        }
        return self::clean($data);
    }

    public static function articleSchema(array $a): array {
        $author = $a['user_id'] ? Database::scalar('SELECT name FROM users WHERE id=?', [(int)$a['user_id']], null) : setting('site_name');
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => $a['schema_type'] ?: 'Article',
            'headline' => $a['title'],
            'description' => trim(strip_tags((string)($a['excerpt'] ?? ''))),
            'image' => $a['image_path'] ? site_url($a['image_path']) : null,
            'datePublished' => $a['publish_at'] ?: $a['created_at'],
            'dateModified' => $a['updated_at'],
            'author' => ['@type' => 'Organization', 'name' => $author ?: setting('site_name', 'توس تعمیر')],
            'publisher' => ['@type' => 'Organization', 'name' => setting('site_name', 'توس تعمیر')],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => site_url('/maghaleh/' . $a['slug'])],
        ]);
    }

    /** FAQPage: فقط وقتی سوالات واقعاً روی صفحه نمایش داده می‌شوند */
    public static function faqSchema(array $faqs): ?array {
        if (!$faqs) return null;
        $main = [];
        foreach ($faqs as $f) {
            $main[] = ['@type' => 'Question', 'name' => $f['question'],
                       'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string)$f['answer'])]];
        }
        return self::clean(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $main]);
    }

    public static function serviceSchema(array $s): array {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => $s['schema_type'] ?: 'Service',
            'name' => $s['name'],
            'description' => trim(strip_tags((string)($s['excerpt'] ?? ''))),
            'url' => site_url('/khedmat/' . $s['slug']),
            'provider' => self::organization(),
            'areaServed' => setting('service_area', 'مشهد، الهیه'),
        ]);
    }

    /** حذف کلیدهای null/خالی بازگشتی */
    private static function clean(array $d): array {
        foreach ($d as $k => $v) {
            if ($v === null || $v === '') unset($d[$k]);
            elseif (is_array($v)) { $d[$k] = self::clean($v); if ($d[$k] === []) unset($d[$k]); }
        }
        return $d;
    }

    public static function json(array $data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
