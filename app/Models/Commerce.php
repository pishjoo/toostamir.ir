<?php
/** مدل‌های سفارش، مشتری، درخواست تعمیر، نظر و پرداخت */
class Customer extends BaseModel {
    protected static string $table = 'customers';
    protected static array $fillable = ['name','mobile','email','password_hash','is_guest'];

    public static function findOrCreate(string $name, string $mobile, ?string $email): int {
        $mobile = normalize_mobile($mobile);
        $c = Database::one('SELECT id FROM customers WHERE mobile=? LIMIT 1', [$mobile]);
        if ($c) { return (int)$c['id']; }
        return self::insert(['name' => clean_text($name), 'mobile' => $mobile, 'email' => $email ? clean_text($email) : null, 'is_guest' => 0]);
    }
}

class Order extends BaseModel {
    protected static string $table = 'orders';
    protected static array $fillable = ['tracking_code','customer_id','name','mobile','email','province','city','address',
        'postal_code','subtotal','discount_amount','shipping_cost','total_amount','coupon_id','shipping_method_id',
        'carrier','shipment_tracking','status','payment_status','notes','internal_note','ip_address'];

    public const STATUSES = [
        'pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'processing' => 'در حال پردازش',
        'shipped' => 'ارسال شده', 'delivered' => 'تحویل شده', 'cancelled' => 'لغو شده', 'refunded' => 'بازگشت داده شده',
    ];
    public const PAYMENTS = ['unpaid' => 'پرداخت نشده', 'paid' => 'موفق', 'failed' => 'ناموفق'];

    public static function byTracking(string $code): ?array { return static::findBy('tracking_code', strtoupper(trim($code))); }
    public static function items(int $orderId): array {
        return Database::all('SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC', [$orderId]);
    }
    public static function setStatus(int $orderId, string $status, string $note = ''): void {
        if (!isset(self::STATUSES[$status])) return;
        $old = Database::scalar('SELECT status FROM orders WHERE id=?', [$orderId], '');
        Database::run('UPDATE orders SET status=? WHERE id=?', [$status, $orderId]);
        Database::run('INSERT INTO order_status_history (order_id, from_status, to_status, note, changed_by) VALUES (?,?,?,?,?)',
            [$orderId, $old ?: null, $status, $note !== '' ? mb_substr($note, 0, 500) : null, $_SESSION['admin_uid'] ?? null]);
    }
}

class ServiceRequest extends BaseModel {
    protected static string $table = 'service_requests';
    protected static array $fillable = ['tracking_code','service_id','device_type','device_brand','device_model','name',
        'mobile','problem_description','address','preferred_time','extra_notes','status','internal_note','ip_address'];

    public const STATUSES = [
        'new' => 'جدید', 'assigned' => 'اعطا شده به تکنسین', 'diagnosis' => 'در حال عیب‌یابی',
        'repairing' => 'در حال تعمیر', 'done' => 'تعمیر شد', 'returned' => 'تحویل مشتری', 'cancelled' => 'لغو شده',
    ];

    public static function byTracking(string $code): ?array {
        return Database::one('SELECT r.*, s.name service_name FROM service_requests r LEFT JOIN services s ON s.id=r.service_id
                              WHERE r.tracking_code=? LIMIT 1', [strtoupper(trim($code))]);
    }
    public static function images(int $requestId): array {
        return Database::all('SELECT * FROM service_request_images WHERE request_id=? ORDER BY id ASC', [$requestId]);
    }
    public static function setStatus(int $id, string $status, string $note = ''): void {
        if (!isset(self::STATUSES[$status])) return;
        $old = Database::scalar('SELECT status FROM service_requests WHERE id=?', [$id], '');
        Database::run('UPDATE service_requests SET status=? WHERE id=?', [$status, $id]);
        Database::run('INSERT INTO service_request_history (request_id, from_status, to_status, note, changed_by) VALUES (?,?,?,?,?)',
            [$id, $old ?: null, $status, $note !== '' ? mb_substr($note, 0, 500) : null, $_SESSION['admin_uid'] ?? null]);
    }
}

class Comment extends BaseModel {
    protected static string $table = 'comments';
    protected static array $fillable = ['target_type','target_id','name','mobile','body','rating','is_approved'];
    public static function approvedFor(string $type, int $id): array {
        return static::all("target_type=? AND target_id=? AND is_approved=1", [$type, $id], 'created_at DESC');
    }
}

class Payment extends BaseModel {
    protected static string $table = 'payments';
    protected static array $fillable = ['order_id','gateway_id','amount','authority','ref_id','card_pan','sha1_hash','status','result_message','ip_address'];
}

class Coupon extends BaseModel {
    protected static string $table = 'coupons';
    protected static array $fillable = ['code','type','value','max_discount','min_order_amount','usage_limit','per_user_limit','used_count','starts_at','expires_at','is_active'];

    /** @return array{ok:bool,msg:string,coupon?:array,discount?:int} */
    public static function validate(string $code, int $subtotal, ?int $customerId): array {
        $code = mb_strtoupper(trim($code));
        $c = Database::one('SELECT * FROM coupons WHERE code=? AND is_active=1 LIMIT 1', [$code]);
        if (!$c) return ['ok'=>false,'msg'=>'کد تخفیف معتبر نیست.'];
        $now = date('Y-m-d H:i:s');
        if ($c['starts_at'] && $c['starts_at'] > $now) return ['ok'=>false,'msg'=>'این کوپن هنوز فعال نشده است.'];
        if ($c['expires_at'] && $c['expires_at'] < $now) return ['ok'=>false,'msg'=>'مهلت استفاده از این کوپن تمام شده است.'];
        if ((int)$c['usage_limit'] > 0 && (int)$c['used_count'] >= (int)$c['usage_limit']) return ['ok'=>false,'msg'=>'ظرفیت این کوپن تکمیل شده است.'];
        if ((int)$c['min_order_amount'] > 0 && $subtotal < (int)$c['min_order_amount']) {
            return ['ok'=>false,'msg'=>'حداقل مبلغ سفارش برای این کوپن ' . show_price($c['min_order_amount']) . ' تومان است.'];
        }
        if ((int)$c['per_user_limit'] > 0 && $customerId) {
            $used = (int)Database::scalar('SELECT COUNT(*) FROM coupon_usages WHERE coupon_id=? AND customer_id=?', [(int)$c['id'], $customerId], 0);
            if ($used >= (int)$c['per_user_limit']) return ['ok'=>false,'msg'=>'سقف استفاده شخصی از این کوپن پر شده است.'];
        }
        $discount = 0;
        if ($c['type'] === 'percent') {
            $discount = (int)floor($subtotal * (int)$c['value'] / 100);
            if ((int)$c['max_discount'] > 0) $discount = min($discount, (int)$c['max_discount']);
        } else {
            $discount = min((int)$c['value'], $subtotal);
        }
        return ['ok'=>true,'msg'=>'کد تخفیف اعمال شد.','coupon'=>$c,'discount'=>$discount];
    }
    public static function consume(int $couponId, int $orderId, ?int $customerId): void {
        Database::run('UPDATE coupons SET used_count = used_count + 1 WHERE id=?', [$couponId]);
        Database::run('INSERT INTO coupon_usages (coupon_id, order_id, customer_id, used_at) VALUES (?,?,?,NOW())', [$couponId, $orderId, $customerId]);
    }
}

class Article extends BaseModel {
    protected static string $table = 'articles';
    protected static array $fillable = ['category_id','user_id','title','slug','excerpt','body','image_path','alt_text','status','publish_at','seo_title','seo_description','seo_keywords','canonical_url','robots_meta','og_image','schema_type','focus_keyword','ai_generated'];
    public static function published(int $limit = 0, int $offset = 0): array {
        $sql = "SELECT a.*, c.name cat_name FROM articles a LEFT JOIN article_categories c ON c.id=a.category_id
                WHERE a.status='published' AND (a.publish_at IS NULL OR a.publish_at <= NOW()) ORDER BY a.publish_at DESC, a.id DESC";
        if ($limit > 0) $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        return Database::all($sql);
    }
    public static function bySlug(string $slug): ?array {
        return Database::one("SELECT a.*, c.name cat_name, c.slug cat_slug FROM articles a
            LEFT JOIN article_categories c ON c.id=a.category_id
            WHERE a.slug=? AND a.status='published' LIMIT 1", [$slug]);
    }
    public static function paginate(array $f): array {
        $where = ["a.status='published'", '(a.publish_at IS NULL OR a.publish_at <= NOW())'];
        $params = [];
        if (!empty($f['cat'])) { $where[] = 'c.slug = ?'; $params[] = $f['cat']; }
        if (!empty($f['q'])) { $where[] = '(a.title LIKE ? OR a.excerpt LIKE ?)'; $l='%'.str_replace(['%','_'],['\%','\_'],$f['q']).'%'; $params[] = $l; $params[] = $l; }
        $W = implode(' AND ', $where);
        $total = (int)Database::scalar("SELECT COUNT(*) FROM articles a LEFT JOIN article_categories c ON c.id=a.category_id WHERE $W", $params, 0);
        $perPage = 9; $pages = max(1,(int)ceil($total/$perPage)); $page = max(1,min((int)($f['page']??1),$pages));
        $items = Database::all("SELECT a.*, c.name cat_name FROM articles a LEFT JOIN article_categories c ON c.id=a.category_id
            WHERE $W ORDER BY a.publish_at DESC, a.id DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage), $params);
        return ['items'=>$items,'total'=>$total,'pages'=>$pages,'page'=>$page];
    }
    public static function incView(int $id): void { Database::run('UPDATE articles SET views_count = views_count + 1 WHERE id=?', [$id]); }
}
