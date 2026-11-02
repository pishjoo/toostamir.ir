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
        if ($layout !== null) {
            $__layoutFile = rtrim(self::$viewPath, '/') . '/' . $layout . '.php';
            ob_start();
            include $__layoutFile;
            echo ob_get_clean();
        } else {
            echo $__content;
        }
    }

    /** fragment render inside layout (used by layouts) */
    public static function content(): string { global $__content; echo $__content; }

    public static function partial(string $template, array $data = []): void {
        $__file = rtrim(self::$viewPath, '/') . '/' . $template . '.php';
        if (!is_file($__file)) return;
        extract($data, EXTR_SKIP);
        include $__file;
    }
}
