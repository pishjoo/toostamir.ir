<?php
/**
 * توس تعمیر - مدل سبد خرید (ماندگار در دیتابیس + شناسه امن در کوکی)
 */
class Cart {
    private const COOKIE = 'ttcart';

    /** توکن سبد این کاربر/مهمان */
    public static function token(): string {
        $tok = (string)($_COOKIE[self::COOKIE] ?? '');
        if (!preg_match('/^[a-f0-9]{32,64}$/', $tok)) $tok = '';
        if ($tok === '') {
            $tok = bin2hex(random_bytes(24));
            if (!headers_sent()) {
                setcookie(self::COOKIE, $tok, [
                    'expires'  => time() + 60 * 60 * 24 * 30,
                    'path'     => '/',
                    'secure'   => is_secure_request(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            $_COOKIE[self::COOKIE] = $tok;
        }
        return $tok;
    }

    public static function cartId(): int {
        $tok = self::token();
        $row = Database::one('SELECT id FROM carts WHERE session_token=?', [$tok]);
        if ($row) return (int)$row['id'];
        Database::run('INSERT INTO carts (session_token) VALUES (?)', [$tok]);
        return (int)Database::pdo()->lastInsertId();
    }

    /** @return array{items:array,subtotal:int,count:int} */
    public static function contents(): array {
        $cid = self::cartId();
        $items = Database::all(
            'SELECT ci.id, ci.product_id, ci.quantity, ci.unit_price, p.name, p.slug, p.sku, p.stock_quantity, p.stock_status
             FROM cart_items ci JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id=? ORDER BY ci.id ASC', [$cid]);
        // هم‌سان‌سازی قیمت با روز و حذف محصولات منتشرنشده
        $subtotal = 0; $count = 0; $out = [];
        foreach ($items as $it) {
            $p = Database::one('SELECT id,name,slug,sku,price,discount_price,is_published,stock_quantity,stock_status FROM products WHERE id=?', [(int)$it['product_id']]);
            if (!$p || !$p['is_published']) {
                Database::run('DELETE FROM cart_items WHERE id=?', [(int)$it['id']]);
                continue;
            }
            $unit = Product::effectivePrice($p);
            $qty = max(1, min((int)$it['quantity'], 99));
            if ($unit !== (int)$it['unit_price'] || $qty !== (int)$it['quantity']) {
                Database::run('UPDATE cart_items SET unit_price=?, quantity=? WHERE id=?', [$unit, $qty, (int)$it['id']]);
            }
            $line = $unit * $qty;
            $subtotal += $line; $count += $qty;
            $out[] = ['id'=>(int)$it['id'],'product_id'=>(int)$p['id'],'name'=>$p['name'],'slug'=>$p['slug'],'sku'=>$p['sku'],
                      'unit_price'=>$unit,'quantity'=>$qty,'line_total'=>$line,
                      'stock_status'=>$p['stock_status'],'stock_quantity'=>(int)$p['stock_quantity']];
        }
        return ['items'=>$out,'subtotal'=>$subtotal,'count'=>$count];
    }

    public static function add(int $productId, int $qty = 1): array {
        $p = Database::one('SELECT id,name,price,discount_price,is_published,stock_quantity FROM products WHERE id=? LIMIT 1', [$productId]);
        if (!$p || !$p['is_published']) return ['ok'=>false,'msg'=>'محصول یافت نشد یا غیرفعال است.'];
        $qty = max(1, min(num_input((string)$qty), 99));
        $unit = Product::effectivePrice($p);
        $cid = self::cartId();
        $existing = Database::one('SELECT id, quantity FROM cart_items WHERE cart_id=? AND product_id=?', [$cid, $productId]);
        $newQty = $qty + ($existing ? (int)$existing['quantity'] : 0);
        if ((int)$p['stock_quantity'] <= 0 && $p['stock_status'] === 'out_of_stock') {
            return ['ok'=>false,'msg'=>'این محصول در حال حاضر موجود نیست. برای اطلاع از شارژ تماس بگیرید.'];
        }
        $newQty = min($newQty, max(1, (int)$p['stock_quantity']));
        if ($existing) {
            Database::run('UPDATE cart_items SET quantity=?, unit_price=? WHERE id=?', [$newQty, $unit, (int)$existing['id']]);
        } else {
            Database::run('INSERT INTO cart_items (cart_id, product_id, quantity, unit_price) VALUES (?,?,?,?)', [$cid, $productId, $newQty, $unit]);
        }
        return ['ok'=>true,'msg'=>'به سبد خرید اضافه شد.','cart'=>self::contents()];
    }

    public static function updateQty(int $itemId, int $qty): void {
        $cid = self::cartId();
        $qty = max(0, min($qty, 99));
        if ($qty === 0) { Database::run('DELETE FROM cart_items WHERE id=? AND cart_id=?', [$itemId, $cid]); return; }
        Database::run(
            'UPDATE cart_items ci JOIN products p ON p.id=ci.product_id
             SET ci.quantity = LEAST(?, GREATEST(p.stock_quantity,1)) WHERE ci.id=? AND ci.cart_id=?',
            [$qty, $itemId, $cid]);
    }

    public static function remove(int $itemId): void {
        Database::run('DELETE FROM cart_items WHERE id=? AND cart_id=?', [$itemId, self::cartId()]);
    }

    public static function clear(): void {
        Database::run('DELETE FROM cart_items WHERE cart_id=?', [self::cartId()]);
    }
}
