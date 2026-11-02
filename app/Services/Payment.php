<?php
/**
 * توس تعمیر - معماری ماژولار درگاه‌های پرداخت
 * - هیچ اتصال جعلی/شبیه‌سازی‌شده: درگاه فقط با کلید واقعی فعال می‌شود.
 * - اعتبارسنجی callback، تطبیق مبلغ، جلوگیری از پرداخت تکراری.
 */

interface PaymentGatewayInterface {
    public function key(): string;
    /** @return array{ok:bool,url?:string,authority?:string,msg?:string} */
    public function purchase(array $order, int $amount): array;
    /** @return array{ok:bool,ref_id?:string,amount?:int,msg?:string} */
    public function callback(array $req): array;
    /** @return array{ok:bool,amount?:int,status?:string,msg?:string} */
    public function verify(array $payment): array;
}

abstract class BaseGateway implements PaymentGatewayInterface {
    protected array $cfg;
    protected array $gwRow;

    public function __construct(array $gwRow) {
        $this->gwRow = $gwRow;
        $c = json_decode((string)($gwRow['config'] ?? '{}'), true);
        $this->cfg = is_array($c) ? $c : [];
    }
    protected function sandbox(): bool { return (int)($this->gwRow['is_sandbox'] ?? 0) === 1; }
    protected function hasCredentials(): bool { return true; }

    protected static function httpPost(string $url, array $json, array $headers = []): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($json, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($body === false) return ['ok' => false, 'data' => [], 'http' => 0, 'err' => $err];
        $data = json_decode((string)$body, true) ?: [];
        return ['ok' => $code >= 200 && $code < 300, 'data' => $data, 'http' => $code, 'err' => ''];
    }
}

/* ------------------- زرین‌پال (v4 REST) ------------------- */
class ZarinpalGateway extends BaseGateway {
    public function key(): string { return 'zarinpal'; }
    protected function hasCredentials(): bool { return !empty($this->cfg['merchant_id']); }

    private function apiUrl(): string {
        if (!empty($this->cfg['api_url'])) return $this->cfg['api_url'];
        return $this->sandbox()
            ? 'https://sandbox.zarinpal.com/pg/v4/payment.request.json'
            : 'https://api.zarinpal.com/pg/v4/payment.request.json';
    }
    private function verifyUrl(): string {
        return str_replace('payment.request', 'payment.verify', $this->apiUrl());
    }
    private function payUrl(string $authority): string {
        $base = $this->sandbox() ? 'https://sandbox.zarinpal.com/pg/StartPay/' : 'https://www.zarinpal.com/pg/StartPay/';
        return $base . $authority;
    }

    public function purchase(array $order, int $amount): array {
        if (!$this->hasCredentials()) return ['ok' => false, 'msg' => 'کلید درگاه (مرچنت‌آیدی) تنظیم نشده است.'];
        // تبدیل تومان به ریال
        $rialAmount = (int)round($amount * 10);
        $resp = self::httpPost($this->apiUrl(), [
            'merchant_id' => $this->cfg['merchant_id'],
            'amount' => $rialAmount,
            'description' => 'سفارش ' . $order['tracking_code'] . ' - ' . setting('site_name', 'توس تعمیر'),
            'email' => $order['email'] ?: null,
            'mobile' => $order['mobile'] ?: null,
            'callback_url' => site_url('/pay/callback/zarinpal'),
            'additional_data' => 'ORDER:' . $order['tracking_code'],
        ]);
        $d = $resp['data']['data'] ?? [];
        if (($resp['data']['result']['code'] ?? 0) !== 100 || empty($d['authority'])) {
            return ['ok' => false, 'msg' => 'خطا در اتصال به درگاه: ' . ($resp['data']['result']['message'] ?? ('HTTP ' . $resp['http']))];
        }
        return ['ok' => true, 'authority' => (string)$d['authority'], 'url' => $this->payUrl((string)$d['authority'])];
    }

    public function callback(array $req): array {
        $authority = (string)($req['Authority'] ?? '');
        $status = (string)($req['Status'] ?? '');
        if ($status !== 'OK' || $authority === '') return ['ok' => false, 'msg' => 'پرداخت در بانک لغو شد.', 'authority' => $authority];
        $ver = $this->verify(['authority' => $authority, 'meta' => ['gateway_row' => $this->gwRow]]);
        return $ver;
    }

    public function verify(array $payment): array {
        $authority = (string)($payment['authority'] ?? '');
        $orderAmount = (int)($payment['order_amount'] ?? 0);
        $resp = self::httpPost($this->verifyUrl(), [
            'merchant_id' => $this->cfg['merchant_id'],
            'authority' => $authority,
            'amount' => (int)round($orderAmount * 10),
        ]);
        $d = $resp['data']['data'] ?? [];
        if (($resp['data']['result']['code'] ?? 0) !== 100) {
            return ['ok' => false, 'msg' => 'احراز پرداخت ناموفق: ' . ($resp['data']['result']['message'] ?? 'unknown')];
        }
        return ['ok' => true, 'ref_id' => (string)($d['card_ref_num'] ?? $d['ref_id'] ?? ''),
                'amount' => (int)round(((int)($d['account_verified'] ?? 0)) > 0 ? ($d['fee_detected'] ?? 0) : 0),
                'paid_amount_rial' => (int)($d['amount'] ?? 0),
                'card_pan' => (string)($d['card_pan'] ?? ''),
                'sha1_hash' => (string)($d['sha1_hash'] ?? '')];
    }
}

/* ------------------- آیدی‌پی ------------------- */
class IdpayGateway extends BaseGateway {
    public function key(): string { return 'idpay'; }
    protected function hasCredentials(): bool { return !empty($this->cfg['api_key']); }

    private function apiBase(): string { return $this->sandbox() ? 'https://api.idpay.ir/v1.1' : 'https://idpay.ir/pws/api/v1.1'; }
    private function payBase(): string { return $this->sandbox() ? 'https://idpay.ir/pws/sandbox/pay' : 'https://idpay.ir/pws/inwebservice/payment'; }

    public function purchase(array $order, int $amount): array {
        if (!$this->hasCredentials()) return ['ok' => false, 'msg' => 'کلید API آیدی‌پی تنظیم نشده است.'];
        $resp = self::httpPost($this->apiBase() . '/payment/request', [
            'order_id' => (int)($this->cfg['order_id'] ?? $order['id']),
            'amount' => $amount, // آیدی‌پی با تومان کار می‌کند
            'name' => $order['name'], 'mail' => $order['email'] ?: null, 'phone' => str_replace('0', '9', (string)$order['mobile'], 1),
            'description' => 'سفارش ' . $order['tracking_code'],
            'callback' => site_url('/pay/callback/idpay'),
        ], ['X-API-KEY: ' . $this->cfg['api_key']]);
        $d = $resp['data'] ?? [];
        if (empty($d['authority']) || empty($d['inwebservice_payment_link'])) {
            return ['ok' => false, 'msg' => 'خطای درگاه: ' . ($d['error_message'] ?? ('HTTP ' . $resp['http']))];
        }
        return ['ok' => true, 'authority' => (string)$d['authority'], 'url' => (string)$d['inwebservice_payment_link'] . '?authority=' . $d['authority']];
    }

    public function callback(array $req): array {
        $authority = (string)($req['authority'] ?? '');
        $status = (int)($req['status'] ?? 0);
        if ($status !== 1 || $authority === '') return ['ok' => false, 'msg' => 'پرداخت لغو یا ناموفق بود.', 'authority' => $authority];
        return $this->verify(['authority' => $authority, 'order_amount' => (int)($req['amount'] ?? 0)]);
    }

    public function verify(array $payment): array {
        $resp = self::httpPost($this->apiBase() . '/payment/verify', [
            'merchant_id' => $this->cfg['api_key'] ?? '',
            'authority' => (string)($payment['authority'] ?? ''),
        ]);
        $d = $resp['data'] ?? [];
        if (($d['status'] ?? -1) != 1) return ['ok' => false, 'msg' => 'اعتبارسنجی درگاه ناموفق بود.'];
        return ['ok' => true, 'ref_id' => (string)($d['transaction_detail']['reference_id'] ?? $d['ref_id'] ?? ''),
                'paid_amount' => (int)($d['transaction_detail']['amount'] ?? 0)];
    }
}

/* ------------------- نکست‌پی ------------------- */
class NextpayGateway extends BaseGateway {
    public function key(): string { return 'nextpay'; }
    protected function hasCredentials(): bool { return !empty($this->cfg['api_key']); }

    private function apiBase(): string { return $this->sandbox() ? 'https://sandbox.nextpay.org/api' : 'https://nextpay.org/api'; }

    public function purchase(array $order, int $amount): array {
        if (!$this->hasCredentials()) return ['ok' => false, 'msg' => 'کلید API نکست‌پی تنظیم نشده است.'];
        $resp = self::httpPost($this->apiBase() . '/add_transaction', [
            'api_key' => $this->cfg['api_key'],
            'amount' => $amount,
            'callback_uri' => site_url('/pay/callback/nextpay'),
            'user_id' => $order['mobile'] ?? '',
            'description' => 'سفارش ' . $order['tracking_code'],
        ]);
        $transId = $resp['data']['trans_id'] ?? '';
        if (!$transId) return ['ok' => false, 'msg' => 'اتصال به نکست‌پی ناموفق بود.'];
        return ['ok' => true, 'authority' => (string)$transId, 'url' => $this->apiBase() . '/payment/index?trans_id=' . $transId];
    }

    public function callback(array $req): array {
        $transId = (string)($req['trans_id'] ?? '');
        $amount = num_input((string)($req['amount'] ?? '0'));
        if ($transId === '' || $amount <= 0) return ['ok' => false, 'msg' => 'پاسخ نامعتبر از درگاه.', 'authority' => $transId];
        return $this->verify(['authority' => $transId, 'order_amount' => $amount]);
    }

    public function verify(array $payment): array {
        $resp = self::httpPost($this->apiBase() . '/verify_transaction', [
            'api_key' => $this->cfg['api_key'],
            'trans_id' => (string)($payment['authority'] ?? ''),
            'amount' => (int)($payment['order_amount'] ?? 0),
        ]);
        $code = (int)($resp['data']['code'] ?? -1);
        if ($code !== 0) return ['ok' => false, 'msg' => 'کد بازگشتی درگاه: ' . $code];
        return ['ok' => true, 'ref_id' => (string)($resp['data']['card_pan'] ?? $payment['authority'] ?? '')];
    }
}

/* ------------------- پرداخت در محل / دستی ------------------- */
class ManualGateway extends BaseGateway {
    public function key(): string { return 'manual'; }
    public function purchase(array $order, int $amount): array {
        return ['ok' => true, 'authority' => 'MANUAL-' . $order['tracking_code'], 'url' => site_url('/سفارش?code=' . urlencode($order['tracking_code'])) ];
    }
    public function callback(array $req): array { return ['ok' => false, 'msg' => 'این روش نیازی به پرداخت آنلاین ندارد.']; }
    public function verify(array $payment): array { return ['ok' => false, 'msg' => 'تأیید دستی توسط اپراتور انجام می‌شود.']; }
}

/* ------------------- کارخانه درگاه‌ها ------------------- */
class PaymentManager {
    private const CLASSES = [
        'zarinpal' => ZarinpalGateway::class,
        'idpay'    => IdpayGateway::class,
        'nextpay'  => NextpayGateway::class,
        'manual'   => ManualGateway::class,
    ];

    public static function activeGateways(): array {
        return Database::all('SELECT * FROM payment_gateways WHERE is_active=1 ORDER BY is_default DESC, sort_order ASC');
    }

    public static function get(string $key): ?PaymentGatewayInterface {
        $row = Database::one('SELECT * FROM payment_gateways WHERE gateway_key=? AND is_active=1', [$key]);
        if (!$row || !isset(self::CLASSES[$key])) return null;
        $cls = self::CLASSES[$key];
        return new $cls($row);
    }

    /** شروع پرداخت: ساخت رکورد payment + هدایت به درگاه */
    public static function start(int $orderId, string $gwKey): array {
        $order = Order::find($orderId);
        if (!$order) return ['ok' => false, 'msg' => 'سفارش یافت نشد.'];
        if (in_array($order['payment_status'], ['paid', 'refunded'], true)) {
            return ['ok' => false, 'msg' => 'این سفارش قبلاً پرداخت شده است.'];
        }
        $gw = self::get($gwKey);
        if (!$gw) return ['ok' => false, 'msg' => 'درگاه انتخابی فعال نیست.'];

        $amount = (int)$order['total_amount'];
        $res = $gw->purchase($order, $amount);
        if (!$res['ok']) return $res;

        $gwRow = Database::one('SELECT id FROM payment_gateways WHERE gateway_key=?', [$gwKey]);
        // ثبت authority جدید؛ اگر رکورد create وجود داشت update شود
        Database::run(
            'INSERT INTO payments (order_id, gateway_id, amount, authority, status, ip_address) VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE amount=VALUES(amount), status="created", ip_address=VALUES(ip_address)',
            [$orderId, (int)($gwRow['id'] ?? 0), $amount, (string)$res['authority'], 'created', $_SERVER['REMOTE_ADDR'] ?? null]);

        return ['ok' => true, 'redirect' => $res['url'] ?? null];
    }

    /**
     * مدیریت callback: اعتبار authority، تطبیق مبلغ، جلوگیری از پرداخت تکراری
     * وضعیت‌های سفارش پس از موفقیت: paid + history
     */
    public static function handleCallback(string $gwKey, array $req): array {
        $gw = self::get($gwKey);
        if (!$gw) return ['ok' => false, 'msg' => 'درگاه ناشناخته یا غیرفعال.'];
        $authority = (string)($req['authority'] ?? $req['Authority'] ?? $req['trans_id'] ?? '');
        if ($authority === '') return ['ok' => false, 'msg' => 'شناسه پرداخت نامعتبر.'];

        $payment = Database::one('SELECT p.*, o.total_amount, o.status ostatus, o.payment_status opay
                                 FROM payments p JOIN orders o ON o.id=p.order_id
                                 JOIN payment_gateways g ON g.id=p.gateway_id
                                 WHERE p.authority=? AND g.gateway_key=? LIMIT 1', [$authority, $gwKey]);
        if (!$payment) return ['ok' => false, 'msg' => 'تراکنش متناظر یافت نشد.'];
        if ($payment['opay'] === 'paid') return ['ok' => true, 'already' => true, 'order_id' => (int)$payment['order_id']];

        $ver = $gw->verify(array_merge($payment, ['order_amount' => (int)$payment['total_amount']]));
        if (!$ver['ok']) {
            Database::run("UPDATE payments SET status='failed', result_message=?, verified_at=NOW() WHERE id=?", [mb_substr((string)($ver['msg'] ?? ''), 0, 255), (int)$payment['id']]);
            Order::setStatus((int)$payment['order_id'], 'payment_failed', 'verify-failed');
            return ['ok' => false, 'msg' => (string)($ver['msg'] ?? 'پرداخت ناموفق.'), 'order_id' => (int)$payment['order_id']];
        }

        // تطبیق مبلغ (ریال/تومان وابسته به درگاه — فقط زمانی که درگاه مبلغ برگرداند)
        $reported = null;
        if (isset($ver['paid_amount_rial'])) $reported = (int)round(((int)$ver['paid_amount_rial']) / 10);
        elseif (isset($ver['paid_amount'])) $reported = (int)$ver['paid_amount'];
        if ($reported !== null && $reported !== (int)$payment['total_amount']) {
            Database::run("UPDATE payments SET status='failed', result_message=? WHERE id=?", ['amount-mismatch:' . $reported, (int)$payment['id']]);
            audit_log('payment_amount_mismatch', 'payment', (int)$payment['id'], 'expected=' . $payment['total_amount'] . ' got=' . $reported);
            return ['ok' => false, 'msg' => 'مبلغ بازگشتی با مبلغ سفارش هم‌خوان نیست.', 'order_id' => (int)$payment['order_id']];
        }

        // ثبت موفقیت به‌صورت اتمی و ضدتکراری
        Database::begin();
        try {
            $upd = Database::run("UPDATE payments SET status='success', ref_id=?, card_pan=?, sha1_hash=?, verified_at=NOW()
                                  WHERE id=? AND status<>'success'",
                [$ver['ref_id'] ?? null, $ver['card_pan'] ?? null, $ver['sha1_hash'] ?? null, (int)$payment['id']]);
            if ($upd->rowCount() === 0 && $payment['status'] !== 'created') { Database::rollBack(); return ['ok' => true, 'already' => true, 'order_id' => (int)$payment['order_id']]; }
            Database::run("UPDATE orders SET payment_status='paid', status='paid' WHERE id=? AND payment_status<>'paid'", [(int)$payment['order_id']]);
            Database::run('INSERT INTO order_status_history (order_id, from_status, to_status, note, changed_by) VALUES (?,?,?,?,NULL)',
                [(int)$payment['order_id'], $payment['ostatus'], 'paid', 'gateway=' . $gwKey]);
            Database::commit();
            audit_log('payment_success', 'payment', (int)$payment['id'], 'gw=' . $gwKey . ' ref=' . ($ver['ref_id'] ?? ''));
            return ['ok' => true, 'order_id' => (int)$payment['order_id']];
        } catch (Throwable $e) { Database::rollBack(); error_log('[pay-cb] ' . $e->getMessage()); return ['ok' => false, 'msg' => 'خطای داخلی در ثبت پرداخت.']; }
    }
}
