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
/** تبدیل عدد یا رشته به ارقام فارسی (برای جاوااسکریپت صفحه پرداخت/سبد) */
function fa($n): string { return fa_digits(number_format((float)(int)num_input((string)$n))); }

/* ---------- تاریخ شمسی (تقویم رسمی ایران - چرخه ۲۸۲۰ ساله با جدول کبیسه قطعی) ---------- */
/**
 * سال‌های کبیسه مطابق تقویم رسمی ایران (الگوی فاصله ۴/۵ ساله؛ شکست چرخه: پس از ۱۳۷۹،
 * کبیسه بعدی ۱۳۸۳ و الگویی مشابه در انتهای هر چرخه ۲۸۲۰ ساله).
 * لنگرهای نجومی تأییدشده (۱ فروردین): ۱۳۸۰=۲۰۰۱-۰۳-۲۱، ۱۳۹۹=۲۰۲۰-۰۳-۲۰،
 * ۱۴۰۳=۲۰۲۴-۰۳-۲۰، ۱۴۰۷=۲۰۲۸-۰۳-۲۰، ۱۴۱۱=۲۰۳۲-۰۳-۲۰.
 */
function jalali_is_leap(int $jy): bool {
    // کبیسه‌های تقویم رسمی ایران بر اساس جدول قطعی نوروز (۱ فروردین) برای بازه ۱۳۶۰ تا ۱۴۲۲:
    // الگو: پنج‌بار فاصله ۴ ساله و سپس یک فاصله ۵ ساله؛ شکست چرخه پس از ۱۴۲۲ (نوروز ۱۴۲۳ = ۲۱ مارس).
    static $leaps = [
        1363,1367,1371,1375,1379, 1383,1387,1391,1395,1399, 1403,1407,1411,1415,1419,
    ];
    if ($jy >= 1363 && $jy <= 1422) return in_array($jy, $leaps, true) || $jy === 1422; // ۱۴۲۲ کبیسه است (نوروز ۱۴۲۳ = ۲۰ مارس)
    if ($jy > 1422 && $jy < 1427) return false; // ۱۴۲۳ تا ۱۴۲۶ کبیسه نیستند (شکست چرخه: نوروز ۱۴۲۷ = ۲۱ مارس)
    // خارج از بازه مستند: چرخه ۲۸۲۰ ساله (الگوریتم نجومی) نسبت به لنگر ۱۳۹۹
    $cyclePos = ((($jy - 1399) % 2820) + 2820) % 2820;
    $k = $cyclePos % 128;
    $leapsIn128 = [1,5,9,13,17,25,29,33,37,45,49,53,57,65,69,73,77,85,89,93,97,105,109,113,117,125];
    $isLeapInBlock = in_array($k, $leapsIn128, true);
    $block = intdiv($cyclePos, 128);
    // بلوک‌های ۱۱ و ۲۱ در چرخه ۲۸۲۰ دارای یک کبیسه اضافی در انتهای بلوک هستند
    if (($block === 11 || $block === 21) && $k === 127) $isLeapInBlock = true;
    return $isLeapInBlock;
}

function jalali_year_length(int $jy): int { return jalali_is_leap($jy) ? 366 : 365; }

function jalali_month_lengths(int $jy): array {
    $m = [31,31,31,31,31,31,30,30,30,30,30,29];
    if (jalali_is_leap($jy)) $m[11] = 30;
    return $m;
}

/** روز (نسبت به epoch میلادی) شروع ۱ فروردین سال جلالی - لنگر قطعی ۲۰ مارس ۲۰۲۰ = ۱۳۹۹/۰۱/۰۱ */
function days_to_jalali_new_year(int $jy): int {
    static $anchorDays = 18341; // floor(gmmktime(2020-03-20 12:00 UTC)/86400)
    static $anchorJy = 1399;
    static $lenCache = [];
    $days = $anchorDays;
    if ($jy >= $anchorJy) {
        for ($y = $anchorJy; $y < $jy; $y++) {
            $days += $lenCache[$y] ?? ($lenCache[$y] = jalali_year_length($y));
        }
    } else {
        for ($y = $anchorJy - 1; $y >= $jy; $y--) {
            $days -= $lenCache[$y] ?? ($lenCache[$y] = jalali_year_length($y));
        }
    }
    return $days;
}

/** تبدیل میلادی به جلالی */
function greg_to_jalali(int $gy, int $gm, int $gd): array {
    $days = (int)floor(gmmktime_utc($gy, $gm, $gd) / 86400);
    // تعیین سال جلالی بر اساس لنگر شروع هر سال
    $jy = 1399;
    while ($days < days_to_jalali_new_year($jy)) { $jy--; }
    while ($days >= days_to_jalali_new_year($jy + 1)) { $jy++; }
    $diff = $days - days_to_jalali_new_year($jy);
    $jm = 1;
    $mlen = jalali_month_lengths($jy);
    while ($diff >= $mlen[$jm - 1]) { $diff -= $mlen[$jm - 1]; $jm++; }
    return [$jy, $jm, $diff + 1];
}

/** تبدیل جلالی به میلادی (برای انتشار زمان‌بندی‌شده مقالات) - خروجی [Y,m,d] */
function jalali_to_greg(int $jy, int $jm, int $jd): array {
    $days = days_to_jalali_new_year($jy) + ($jd - 1);
    $mlen = jalali_month_lengths($jy);
    for ($m = 1; $m < $jm; $m++) $days += $mlen[$m - 1];
    return array_map('intval', explode('-', gmdate('Y-m-d', $days * 86400 + 12 * 3600)));
}

function gmmktime_utc(int $y, int $m, int $d): int {
    return (int)gmmktime(12, 0, 0, $m, $d, $y);
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

/* ---------- redirect ---------- */
function redirect(string $path): never {
    if (!preg_match('#^(https?:)?//#', $path)) $path = '/' . ltrim($path, '/');
    header('Location: ' . $path);
    exit;
}

/* ---------- آپلود امن تصویر ---------- */
/**
 * @param array $files  عنصر $_FILES (تک‌فایل یا چندتایی)
 * @param int|null $index ایندکس در حالت چندتایی
 * @return string|null مسیر نسبی مثل /uploads/products/xxx.jpg یا null در خطا
 */
function upload_image(array $files, ?int $index = null, string $subdir = 'misc'): ?string {
    static $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    static $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    static $maxBytes = 3145728; // 3MB

    $name = $index === null ? ($files['name'] ?? '') : ($files['name'][$index] ?? '');
    $tmp  = $index === null ? ($files['tmp_name'] ?? '') : ($files['tmp_name'][$index] ?? '');
    $err  = $index === null ? ($files['error'] ?? UPLOAD_ERR_NO_FILE) : ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
    $size = $index === null ? ($files['size'] ?? 0) : ($files['size'][$index] ?? 0);

    if ($name === '' || (int)$err !== UPLOAD_ERR_OK || !is_uploaded_file((string)$tmp)) return null;
    if ((int)$size <= 0 || (int)$size > $maxBytes) { flash('error', 'حجم تصویر «' . $name . '» بیش از ۳ مگابایت است.'); return null; }

    // بررسی MIME واقعی (finfo) + getimagesize — جلوگیری از فایل اجرایی/ جعلی
    $mime = null;
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) { $mime = finfo_file($fi, $tmp); finfo_close($fi); }
    }
    if ($mime === null) $mime = mime_content_type($tmp) ?: null;
    if (!in_array((string)$mime, $allowedMime, true)) { flash('error', 'نوع فایل «' . $name . '» مجاز نیست (فقط jpg/png/webp/gif).'); return null; }
    $imgInfo = @getimagesize($tmp);
    if ($imgInfo === false || empty($imgInfo[0]) || empty($imgInfo[1])) return null;

    $ext = match (true) {
        $mime === 'image/png'  => 'png',
        $mime === 'image/webp' => 'webp',
        $mime === 'image/gif'  => 'gif',
        default => 'jpg',
    };
    $subdir = preg_replace('/[^a-z0-9_\-]/i', '', $subdir) ?: 'misc';
    $cfgDir = $GLOBALS['TT_APP']['paths']['root'] . '/public_html/uploads/' . $subdir;
    if (!is_dir($cfgDir)) @mkdir($cfgDir, 0755, true);
    $fname = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $cfgDir . '/' . $fname)) return null;

    $rel = '/uploads/' . $subdir . '/' . $fname;
    try {
        Database::run('INSERT INTO media (path, original_name, mime_type, size_bytes, width, height, uploaded_by) VALUES (?,?,?,?,?,?,?)',
            [$rel, mb_substr((string)$name, 0, 200), $mime, (int)$size, (int)$imgInfo[0], (int)$imgInfo[1], $_SESSION['admin_uid'] ?? null]);
    } catch (Throwable $ex) { error_log('[media] ' . $ex->getMessage()); }
    return $rel;
}

/** حذف فیزیکی + رکورد media */
function delete_upload(?string $relPath): void {
    if (!$relPath || !str_starts_with($relPath, '/uploads/')) return;
    $full = realpath($GLOBALS['TT_APP']['paths']['root'] . '/public_html' . $relPath);
    $base = realpath($GLOBALS['TT_APP']['paths']['root'] . '/public_html/uploads');
    if ($full && $base && str_starts_with($full, $base) && is_file($full)) @unlink($full);
    try { Database::run('DELETE FROM media WHERE path=?', [$relPath]); } catch (Throwable) {}
}

/* ---------- pagination ---------- */
function paginate_links(int $page, int $pages, string $baseUrl): string {
    if ($pages <= 1) return '';
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $out = '<nav class="pagination-wrap" aria-label="صفحه‌بندی"><ul class="pagination">';
    $win = [];
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) $win[] = $i;
    if ($page > 2) $out .= '<li><a class="pg" href="' . e($baseUrl . $sep . 'page=' . ($page - 1)) . '">›</a></li>';
    if (!in_array(1, $win, true)) $out .= '<li><a class="pg" href="' . e($baseUrl . $sep . 'page=1') . '">۱</a></li><li><span class="pg-dots">…</span></li>';
    foreach ($win as $i) {
        $out .= $i === $page
            ? '<li><span class="pg cur" aria-current="page">' . fa_digits((string)$i) . '</span></li>'
            : '<li><a class="pg" href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . fa_digits((string)$i) . '</a></li>';
    }
    if (!in_array($pages, $win, true)) $out .= '<li><span class="pg-dots">…</span></li><li><a class="pg" href="' . e($baseUrl . $sep . 'page=' . $pages) . '">' . fa_digits((string)$pages) . '</a></li>';
    if ($page < $pages) $out .= '<li><a class="pg" href="' . e($baseUrl . $sep . 'page=' . ($page + 1)) . '">‹</a></li>';
    return $out . '</ul></nav>';
}

/* ---------- misc ---------- */
function old(array $data, string $key, string $default = ''): string { return (string)($data[$key] ?? $default); }
function arr_old(string $key): array { return (array)($_SESSION['old_form'][$key] ?? []); }
function is_secure_request(): bool { return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; }
function json_out($data, int $code = 200): never { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

/* ---------- ثبت رویدادهای ممیزی ---------- */
function audit_log(string $action, ?string $entityType = null, int|string|null $entityId = null, ?string $description = null): void {
    try {
        Database::run(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES (?,?,?,?,?,?)',
            [
                $_SESSION['admin_uid'] ?? null,
                mb_substr($action, 0, 60),
                $entityType !== null ? mb_substr($entityType, 0, 60) : null,
                $entityId !== null ? mb_substr((string)$entityId, 0, 60) : null,
                $description !== null ? mb_substr($description, 0, 500) : null,
                mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            ]);
    } catch (Throwable $e) { error_log('[audit] ' . $e->getMessage()); }
}

/* ---------- دسترسی پنل مدیریت ---------- */
function admin_perm_slugs(): array {
    static $perms = null;
    if ($perms === null) {
        $roleId = (int)Database::scalar('SELECT role_id FROM users WHERE id=?', [(int)($_SESSION['admin_uid'] ?? 0)], 0);
        $perms = $roleId ? User::permissionsFor($roleId) : [];
    }
    return $perms;
}
function has_permission(string $slug): bool {
    if (empty($_SESSION['admin_uid'])) return false;
    $perms = admin_perm_slugs();
    return in_array('system.full', $perms, true) || in_array($slug, $perms, true);
}
