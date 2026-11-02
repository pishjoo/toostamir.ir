<?php
/**
 * توس تعمیر - View engine
 */
class View {
    public static string $viewPath = '';

    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): void {
        $__file = rtrim(self::$viewPath, '/') . '/' . $template . '.php';
        if (!is_file($__file)) { http_response_code(500); error_log('[View] missing ' . $__file); echo 'خطای داخلی'; return; }
        extract($data, EXTR_SKIP);
        ob_start();
        include $__file;
        $__content = ob_get_clean();
        $GLOBALS['__tt_content'] = $__content;
        if ($layout !== null) {
            $__layoutFile = rtrim(self::$viewPath, '/') . '/' . $layout . '.php';
            ob_start();
            include $__layoutFile;
            echo ob_get_clean();
        } else {
            echo $__content;
        }
    }

    /** fragment render inside layout (used by layouts)
     *  Note: must read $GLOBALS directly — when called from an included view file,
     *  the local scope may contain a $GLOBALS copy shadowed by extract(EXTR_SKIP). */
    public static function content(): string { return (string)($GLOBALS['__tt_content'] ?? ''); }

    public static function partial(string $template, array $data = []): void {
        $__file = rtrim(self::$viewPath, '/') . '/' . $template . '.php';
        if (!is_file($__file)) return;
        extract($data, EXTR_SKIP);
        include $__file;
    }
}
