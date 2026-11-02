<?php
/** مدل محصول + جستجو و فیلتر فروشگاه */
class Product extends BaseModel {
    protected static string $table = 'products';
    protected static array $fillable = ['category_id','brand_id','name','slug','sku','part_code','short_description','description','price','discount_price','stock_quantity','stock_status','weight','dimensions','compatible_models','is_published','seo_title','seo_description','seo_keywords','canonical_url','robots_meta','og_image','schema_type','alt_text'];

    public const EFFECTIVE_PRICE = '(CASE WHEN p.discount_price IS NOT NULL AND p.discount_price > 0 THEN p.discount_price ELSE p.price END)';

    public static function bySlug(string $slug): ?array {
        return Database::one('SELECT p.* FROM products p WHERE p.slug=? AND p.is_published=1 LIMIT 1', [$slug]);
    }
    public static function effectivePrice(array $p): int {
        return ($p['discount_price'] !== null && (int)$p['discount_price'] > 0) ? (int)$p['discount_price'] : (int)$p['price'];
    }
    public static function images(int $productId): array {
        return Database::all('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC, id ASC', [$productId]);
    }
    public static function incView(int $id): void {
        Database::run('UPDATE products SET views_count = views_count + 1 WHERE id=?', [$id]);
    }

    /**
     * فیلترها: q, category, brand[], min_price, max_price, in_stock, sort(new|cheap|expensive|popular), page, per_page
     * @return array{items:array,total:int,pages:int,page:int}
     */
    public static function search(array $f): array {
        $where = ['p.is_published = 1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.part_code LIKE ? OR p.short_description LIKE ?)';
            $like = '%' . str_replace(['%','_'], ['\%','\_'], $f['q']) . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($f['category'])) {
            // شامل زیردسته‌ها
            $catIds = self::categoryWithChildren((int)$f['category']);
            $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($catIds), '?')) . ')';
            $params = array_merge($params, $catIds);
        }
        if (!empty($f['brand']) && is_array($f['brand'])) {
            $bids = array_values(array_filter(array_map('intval', $f['brand'])));
            if ($bids) {
                $where[] = 'p.brand_id IN (' . implode(',', array_fill(0, count($bids), '?')) . ')';
                $params = array_merge($params, $bids);
            }
        }
        if (isset($f['min_price']) && $f['min_price'] !== '' && $f['min_price'] !== null) { $where[] = self::EFFECTIVE_PRICE . ' >= ?'; $params[] = num_input((string)$f['min_price']); }
        if (isset($f['max_price']) && $f['max_price'] !== '' && $f['max_price'] !== null) { $where[] = self::EFFECTIVE_PRICE . ' <= ?'; $params[] = num_input((string)$f['max_price']); }
        if (!empty($f['in_stock'])) { $where[] = "p.stock_status='in_stock'"; }

        $W = implode(' AND ', $where);
        $total = (int)Database::scalar("SELECT COUNT(*) FROM products p WHERE $W", $params, 0);

        $order = match ($f['sort'] ?? '') {
            'cheap'    => self::EFFECTIVE_PRICE . ' ASC',
            'expensive'=> self::EFFECTIVE_PRICE . ' DESC',
            'popular'  => 'p.views_count DESC, p.sold_count DESC',
            default    => 'p.created_at DESC, p.id DESC',
        };
        $perPage = max(6, min(48, (int)($f['per_page'] ?? setting('shop_per_page', '12'))));
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min((int)($f['page'] ?? 1), $pages));
        $items = Database::all(
            "SELECT p.*, c.name cat_name, b.name brand_name FROM products p
             LEFT JOIN categories c ON c.id=p.category_id
             LEFT JOIN brands b ON b.id=p.brand_id
             WHERE $W ORDER BY $order LIMIT $perPage OFFSET " . (($page - 1) * $perPage),
            $params);
        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function featured(int $limit = 8): array {
        return Database::all('SELECT p.*, c.name cat_name, b.name brand_name FROM products p
            LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id
            WHERE p.is_published=1 ORDER BY p.sold_count DESC, p.views_count DESC, p.id DESC LIMIT ' . max(1,$limit));
    }

    public static function latest(int $limit = 8): array {
        return Database::all('SELECT p.*, c.name cat_name, b.name brand_name FROM products p
            LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id
            WHERE p.is_published=1 ORDER BY p.created_at DESC, p.id DESC LIMIT ' . max(1,$limit));
    }

    /** شناسه دسته + همه فرزندان مستقیم (یک سطح کافی است) */
    public static function categoryWithChildren(int $catId): array {
        $ids = [$catId];
        $kids = Database::all('SELECT id FROM categories WHERE parent_id=?', [$catId]);
        foreach ($kids as $k) $ids[] = (int)$k['id'];
        return $ids;
    }

    /** کسر موجودیت در تراکنش سفارش */
    public static function decrementStock(int $productId, int $qty): bool {
        $st = Database::run('UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0), sold_count = sold_count + ? WHERE id=?', [$qty, $qty, $productId]);
        return $st->rowCount() > 0;
    }
}
