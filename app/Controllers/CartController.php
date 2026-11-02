<?php
/**
 * توس تعمیر - کنترلر سبد خرید، پرداخت و پیگیری سفارش (سمت کاربر)
 */
class CartController {

    public function view(): void {
        $cart = Cart::contents();
        View::render('front/cart', [
            'cart' => $cart,
            'shipping_methods' => Database::all('SELECT * FROM shipping_methods WHERE is_active=1 ORDER BY sort_order ASC'),
            'title' => 'سبد خرید - ' . setting('site_name', 'توس تعمیر'),
            'meta_description' => 'مرور و تکمیل سبد خرید قطعات یدکی.',
            'robots_meta' => 'noindex,follow',
        ], 'layouts/main');
    }

    public function add(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) json_out(['ok' => false, 'msg' => 'اعتبار فرم نامعتبر.'], 419);
        $res = Cart::add((int)($_POST['product_id'] ?? 0), num_input((string)($_POST['qty'] ?? '1')) ?: 1);
        json_out($res, $res['ok'] ? 200 : 422);
    }

    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) json_out(['ok' => false, 'msg' => 'اعتبار فرم نامعتبر.'], 419);
        $items = $_POST['items'] ?? [];
        if (is_array($items)) {
            foreach ($items as $id => $qty) Cart::updateQty((int)$id, num_input((string)$qty));
        }
        if (!empty($_POST['remove'])) {
            foreach ((array)$_POST['remove'] as $rid) {
                if (is_string($rid) && str_starts_with($rid, 'slug:')) continue;
                Cart::remove((int)$rid);
            }
        }
        redirect('/سبد-خرید');
    }

    public function removeItem(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) redirect('/سبد-خرید');
        Cart::remove((int)($_POST['item_id'] ?? 0));
        redirect('/سبد-خرید');
    }

    public function coupon(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) json_out(['ok' => false, 'msg' => 'اعتبار نامعتبر.'], 419);
        $cart = Cart::contents();
        $v = Coupon::validate((string)($_POST['code'] ?? ''), $cart['subtotal'], null);
        if ($v['ok']) { $_SESSION['coupon_code'] = mb_strtoupper(trim((string)$_POST['code'])); }
        json_out(['ok' => $v['ok'], 'msg' => $v['msg'], 'discount' => $v['discount'] ?? 0]);
    }

    public function checkout(): void {
        $cart = Cart::contents();
        if (!$cart['items']) { flash('error', 'سبد خرید شما خالی است.'); redirect('/فروشگاه'); }
        View::render('front/checkout', [
            'cart' => $cart,
            'couponCode' => (string)($_SESSION['coupon_code'] ?? ''),
            'shipping_methods' => Database::all('SELECT * FROM shipping_methods WHERE is_active=1 ORDER BY sort_order ASC'),
            'gateways' => PaymentManager::activeGateways(),
            'title' => 'تکمیل سفارش - ' . setting('site_name', 'توس تعمیر'),
            'robots_meta' => 'noindex,follow',
        ], 'layouts/main');
    }

    public function placeOrder(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/سبد-خرید');
        require_csrf();
        $cart = Cart::contents();
        if (!$cart['items']) { flash('error', 'سبد خرید خالی است.'); redirect('/فروشگاه'); }

        $name = clean_text($_POST['name'] ?? '');
        $mobileRaw = (string)($_POST['mobile'] ?? '');
        $mobile = normalize_mobile($mobileRaw);
        $address = clean_text($_POST['address'] ?? '');
        $postal = to_english_digits(trim((string)($_POST['postal_code'] ?? '')));
        $errors = [];
        if (mb_strlen($name) < 3) $errors[] = 'نام گیرنده را کامل وارد کنید.';
        if (!valid_ir_mobile($mobileRaw)) $errors[] = 'شماره موبایل معتبر نیست.';
        if (mb_strlen($address) < 10) $errors[] = 'آدرس کامل ارسال را بنویسید.';
        if ($postal !== '' && !preg_match('/^\d{10}$/', $postal)) $errors[] = 'کد پستی باید ۱۰ رقم باشد.';
        $shipId = (int)($_POST['shipping_method_id'] ?? 0);
        if (!$shipId || !Database::one('SELECT id FROM shipping_methods WHERE id=? AND is_active=1', [$shipId])) $errors[] = 'روش ارسال را انتخاب کنید.';

        if ($errors) { foreach ($errors as $er) flash('error', $er); $_SESSION['old_form'] = $_POST; redirect('/تکمیل-سفارش'); }

        $customerId = Customer::findOrCreate($name, $mobile, filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null);
        unset($_SESSION['old_form']);

        $res = Order::createFromCart([
            'customer_id' => $customerId,
            'name' => $name, 'mobile' => $mobile,
            'email' => filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'province' => clean_text($_POST['province'] ?? '') ?: setting('address_province', 'خراسان رضوی'),
            'city' => clean_text($_POST['city'] ?? '') ?: 'مشهد',
            'address' => $address, 'postal_code' => $postal ?: null,
            'shipping_method_id' => $shipId,
            'coupon_code' => (string)($_SESSION['coupon_code'] ?? ''),
            'notes' => clean_text($_POST['notes'] ?? '') ?: null,
        ], Cart::cartId());
        unset($_SESSION['coupon_code']);

        if (!$res['ok']) { flash('error', $res['msg']); redirect('/تکمیل-سفارش'); }

        $_SESSION['last_order_tracking'] = $res['tracking'];
        audit_log('order_placed', 'order', (int)$res['id'], 'total=' . $res['total']);
        flash('success', 'سفارش شما با کد رهگیری ' . fa_digits($res['tracking']) . ' ثبت شد.');

        // انتخاب درگاه
        $gwKey = (string)($_POST['gateway'] ?? '');
        if ($gwKey === 'manual' || $gwKey === '') {
            redirect('/پیگیری-سفارش?code=' . urlencode($res['tracking']));
        }
        $pay = PaymentManager::start((int)$res['id'], $gwKey);
        if ($pay['ok'] && !empty($pay['redirect'])) {
            header('Location: ' . $pay['redirect']); exit;
        }
        flash($pay['ok'] ? 'success' : 'error', $pay['msg'] ?? 'آماده‌سازی پرداخت...');
        redirect('/پرداخت/' . $res['tracking']);
    }

    /** صفحه پرداخت مجدد برای سفارش پرداخت‌نشده */
    public function payPage(string $tracking): void {
        $order = Order::byTracking($tracking);
        if (!$order) abort404();
        if ($order['payment_status'] === 'paid') { flash('success', 'این سفارش قبلاً پرداخت شده است.'); redirect('/پیگیری-سفارش?code=' . urlencode($tracking)); }
        View::render('front/pay', [
            'order' => $order,
            'gateways' => PaymentManager::activeGateways(),
            'title' => 'پرداخت سفارش ' . fa_digits($tracking),
            'robots_meta' => 'noindex,follow',
        ], 'layouts/main');
    }

    public function payStart(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/');
        require_csrf();
        $tracking = strtoupper(trim((string)($_POST['tracking'] ?? '')));
        $order = Order::byTracking($tracking);
        if (!$order) { flash('error', 'سفارش یافت نشد.'); redirect('/'); }
        $res = PaymentManager::start((int)$order['id'], (string)($_POST['gateway'] ?? ''));
        if ($res['ok'] && !empty($res['redirect'])) { header('Location: ' . $res['redirect']); exit; }
        flash('error', $res['msg'] ?? 'امکان پرداخت آنلاین در حال حاضر وجود ندارد.');
        redirect('/پرداخت/' . $tracking);
    }

    /** callback درگاه‌ها (GET از سمت بانک) */
    public function callback(string $gwKey): void {
        $res = PaymentManager::handleCallback($gwKey, $_GET);
        $tracking = '';
        if (!empty($res['order_id'])) {
            $o = Order::find((int)$res['order_id']);
            $tracking = (string)($o['tracking_code'] ?? '');
        }
        if ($res['ok']) flash('success', 'پرداخت با موفقیت انجام شد. سفارش شما در حال پردازش است.');
        else flash('error', (string)($res['msg'] ?? 'پرداخت ناموفق بود.'));
        redirect($tracking ? '/پیگیری-سفارش?code=' . urlencode($tracking) : '/');
    }

    /** پیگیری سفارش خرید */
    public function trackOrder(): void {
        $code = strtoupper(trim((string)($_GET['code'] ?? ($_SESSION['last_order_tracking'] ?? ''))));
        $order = $code !== '' ? Order::byTracking($code) : null;
        View::render('front/track-order', [
            'code' => $code,
            'order' => $order,
            'items' => $order ? Order::items((int)$order['id']) : [],
            'history' => $order ? Order::history((int)$order['id']) : [],
            'title' => 'پیگیری سفارش - ' . setting('site_name', 'توس تعمیر'),
            'meta_description' => 'وضعیت سفارش قطعات خود را با کد رهگیری پیگیری کنید.',
            'robots_meta' => 'noindex,follow',
        ], 'layouts/main');
    }
}
