<?php
/**
 * توس تعمیر - Helpers مشترک (امن، سئو، تاریخ شمسی، ارقام فارسی)
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 2));

/* ---------- امن‌سازی خروجی ---------- */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clean_text(?string $s): string {
    $s = str_replace(["\r\n", "\r"], "\n", (string)$s);
    return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s));
}

/** پاک‌سازی HTML غنی‌شده (مقالات/توضیحات) با allowlist */
function sanitize_html(?string $html): string {
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><a><img><table><thead><tbody><tr><th><td><blockquote><code><pre><span><div>';
    $html = strip_tags((string)$html, $allowed);
    // حذف رویدادهای on* و javascript:
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*(javascript|data|vbscript):[^"\']*\2/i', '$1="#"', $html);
    return $html;
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) return '';
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_verify(): bool {
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($t) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}
function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        http_response_code(419);
        flash('error', 'اعتبار فرم منقضی شده است. لطفاً دوباره تلاش کنید.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit;
    }
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function get_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Settings ---------- */
function settings_all(bool $refresh = false): array {
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        foreach (Database::all("SELECT skey,svalue FROM settings") as $r) $cache[$r['skey']] = $r['svalue'];
    }
    return $cache;
}
function setting(string $key, string $default = ''): string {
    $s = settings_all();
    $v = $s[$key] ?? null;
    return ($v === null || $v === '') ? $default : (string)$v;
}
function save_setting(string $key, string $value, string $group = 'general'): void {
    Database::run("INSERT INTO settings(skey,svalue,sgroup) VALUES(?,?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue), sgroup=VALUES(sgroup)",
        [$key, $value, $group]);
    settings_all(true);
}

/* ---------- URL & base ---------- */
function site_url(string $path = ''): string {
    $base = rtrim(setting('site_domain', ''), '/');
    if ($base === '') {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    return $base . '/' . ltrim($path, '/');
}
function url(string $path = '/'): string {
    return '/' . ltrim($path, '/');
}

/* ---------- ارقام فارسی / تبدیل به عدد ---------- */
function to_english_digits(string $s): string {
    return str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩','،'],
                       ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9',','], $s);
}
function fa_digits(string $s): string {
    return str_replace(['0','1','2','3','4','5','6','7','8','9'],
                       ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], (string)$s);
}
function num_input(?string $s): int {
    $s = to_english_digits(str_replace([',', '،', ' ', '٬'], '', (string)$s));
    $s = preg_replace('/[^\d\-]/', '', $s);
    return (int)$s;
}
function money($n): string {
    return number_format((float)(int)$n);
}
function show_price($n, bool $fa = true): string {
    $s = money($n);
    return $fa ? fa_digits($s) : $s;
}

/* ---------- تاریخ شمسی (هجری جلالی رسمی - الگوریتم چرخه ۱۲۸ ساله B با لنگرهای نجومی) ---------- */
/** کبیسه بودن سال جلالی بر اساس الگوریتم استاندارد jdf (چرخه‌های ۳۳ ساله با کبیسه ششم در بلوک آخر) */
function jalali_is_leap(int $jy): bool {
    static $breaks = [-37,19,47,75,104,132,160,189,217,245,274,302,330,359,387,415,444,472,500,529,557,585,614,642,670,699,727,755,784,812,840,869,897,925,954,982,1010,1039,1067,1095,1124,1152,1180,1209,1237,1265,1294,1322,1350,1379,1407,1435,1464,1492,1520,1549,1577,1605,1634,1662,1690,1719,1747,1775,1804,1832,1860,1889,1917,1945,1974,2002,2030,2059,2087,2115,2144,2172,2200,2229,2257,2285,2314,2342,2370,2399,2427,2455,2484,2512,2540,2569,2597,2625,2654,2682,2710,2739,2767,2795,2824];
    return in_array($jy, $breaks, true);
}

function jalali_year_length(int $jy): int { return jalali_is_leap($jy) ? 366 : 365; }

function jalali_month_lengths(int $jy): array {
    $m = [31,31,31,31,31,31,30,30,30,30,30,29];
    if (jalali_is_leap($jy)) $m[11] = 30;
    return $m;
}

/** تبدیل میلادی به جلالی؛ مبدأ: ۱ فروردین ۱۳۹۹ = ۲۰ مارس ۲۰۲۰ (تأییدشده) */
function greg_to_jalali(int $gy, int $gm, int $gd): array {
    $days = (int)floor(gmmktime_utc($gy, $gm, $gd) / 86400);   // روز از epoch میلادی
    $anchor = (int)floor(gmmktime_utc(2020, 3, 20) / 86400);   // 1399-01-01
    $diff = $days - $anchor;
    $jy = 1399;
    while ($diff < 0) { $jy--; $diff += jalali_year_length($jy); }
    while (true) { $len = jalali_year_length($jy); if ($diff < $len) break; $diff -= $len; $jy++; }
    $jm = 1;
    $mlen = jalali_month_lengths($jy);
    while ($diff >= $mlen[$jm - 1]) { $diff -= $mlen[$jm - 1]; $jm++; }
    return [$jy, $jm, $diff + 1];
}

function jalali_year_length(int $jy): int { return jalali_is_leap($jy) ? 366 : 365; }

function jalali_month_lengths(int $jy): array {
    $m = [31,31,31,31,31,31,30,30,30,30,30,29];
    if (jalali_is_leap($jy)) $m[11] = 30;
    return $m;
}

/** تبدیل میلادی به جلالی با لنگرهای تأییدشده (۱ فروردین ۱۳۸۰ = ۲۱ مارس ۲۰۰۱ و ۱۴۰۴/۱/۱ = ۲۰۲۵/۳/۲۰) */
function greg_to_jalali(int $gy, int $gm, int $gd): array {
    $days = (int)floor(gmmktime_utc($gy, $gm, $gd) / 86400);          // days since 1970-01-01
    $anchor = (int)floor(gmmktime_utc(2001, 3, 21) / 86400);          // 1380-01-01
    $diff = $days - $anchor;
    $jy = 1380;
    while ($diff < 0) { $jy--; $diff += jalali_year_length($jy); }
    while (true) { $len = jalali_year_length($jy); if ($diff < $len) break; $diff -= $len; $jy++; }
    $jm = 1;
    $mlen = jalali_month_lengths($jy);
    while ($diff >= $mlen[$jm - 1]) { $diff -= $mlen[$jm - 1]; $jm++; }
    return [$jy, $jm, $diff + 1];
}

function gmmktime_utc(int $y, int $m, int $d): int {
    $ts = gmmktime(12, 0, 0, $m, $d, $y);
    return (int)$ts;
}

function shamsi_date(?string $datetime = null, bool $withTime = false): string {
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if ($ts === false) return '';
    [$jy, $jm, $jd] = greg_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $out = fa_digits(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
    if ($withTime) $out .= ' - ' . fa_digits(date('H:i', $ts));
    return $out;
}

/* ---------- اعتبارسنجی ---------- */
function valid_ir_mobile(string $m): bool {
    $m = to_english_digits(trim($m));
    return (bool)preg_match('/^09\d{9}$/', $m) || (bool)preg_match('/^(\+98|98|0098)9\d{9}$/', $m);
}
function normalize_mobile(string $m): string {
    $m = to_english_digits(preg_replace('/[\s\-()]/', '', trim($m)));
    if (str_starts_with($m, '+98')) $m = '0' . substr($m, 3);
    elseif (preg_match('/^98\d+/', $m)) $m = '0' . substr($m, 2);
    elseif (preg_match('/^0098\d+/', $m)) $m = '0' . substr($m, 5);
    elseif (preg_match('/^9\d{9}$/', $m)) $m = '0' . $m;
    return $m;
}

function slugify(string $text): string {
    $text = to_english_digits(mb_strtolower(trim($text), 'UTF-8'));
    // keep persian letters in slug? For URLs we prefer latin; transliterate common words handled by admin input.
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim($text, '-');
    return mb_substr($text, 0, 180) ?: ('p-' . bin2hex(random_bytes(4)));
}

function unique_slug(string $table, string $slugCol, string $base, ?int $ignoreId = null, string $extraWhere = '', array $extraParams = []): string {
    $slug = slugify($base);
    $candidate = $slug; $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE `$slugCol` = ?";
        $p = [$candidate];
        if ($ignoreId !== null) { $sql .= " AND id != ?"; $p[] = $ignoreId; }
        if ($extraWhere !== '') { $sql .= " AND $extraWhere"; $p = array_merge($p, $extraParams); }
        if ((int)Database::value($sql, $p) === 0) return $candidate;
        $candidate = $slug . '-' . $i++;
    }
}

/* ---------- tracking codes ---------- */
function gen_tracking_code(string $prefix = 'TT'): string {
    do {
        $code = $prefix . strtoupper(bin2hex(random_bytes(3))); // TT + 6 hex
    } while (false);
    return $code;
}

/* ---------- misc ---------- */
function old(array $data, string $key, string $default = ''): string { return (string)($data[$key] ?? $default); }
function is_secure_request(): bool { return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; }
function json_out($data, int $code = 200): never { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
