<?php
/**
 * توس تعمیر - Bootstrap (startup مشترک وب‌سایت و پنل مدیریت)
 */
declare(strict_types=1);

define('TT_START', microtime(true));
$ROOT = dirname(__DIR__);

require $ROOT . '/app/Helpers/functions.php';

$appCfg = require $ROOT . '/config/app.php';
$GLOBALS['TT_APP'] = $appCfg;

date_default_timezone_set($appCfg['timezone']);

/* خطاها: هرگز به کاربر نمایش داده نشوند مگر در حالت debug */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (!is_dir($appCfg['paths']['logs'])) @mkdir($appCfg['paths']['logs'], 0755, true);
ini_set('error_log', $appCfg['paths']['logs'] . '/php-error.log');
set_exception_handler(function (Throwable $e) use ($appCfg) {
    error_log('[EXC] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) http_response_code(500);
    if (($appCfg['debug'] ?? false) && PHP_SAPI !== 'cli') {
        echo '<pre>' . e($e->getMessage()) . '&#10;' . e($e->getTraceAsString()) . '</pre>';
    } else {
        echo 'خطای داخلی رخ داد. لطفاً بعداً تلاش کنید.';
    }
});

/* نشست امن */
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $secure = is_secure_request();
    session_name($appCfg['session']['name']);
    session_set_cookie_params([
        'lifetime' => $appCfg['session']['lifetime'],
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* هدرهای امنیتی عمومی */
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require $ROOT . '/core/Database.php';
require $ROOT . '/core/View.php';
require $ROOT . '/core/Router.php';
View::$viewPath = $appCfg['paths']['views'];

/* مدل‌ها */
foreach (glob($appCfg['paths']['app'] . '/Models/*.php') ?: [] as $f) require_once $f;
/* سرویس‌ها */
foreach (glob($appCfg['paths']['app'] . '/Services/*.php') ?: [] as $f) require_once $f;
/* کنترلرها */
foreach (array_merge(
    glob($appCfg['paths']['app'] . '/Controllers/*.php') ?: [],
    glob($appCfg['paths']['app'] . '/Controllers/Admin/*.php') ?: []
) as $f) require_once $f;
