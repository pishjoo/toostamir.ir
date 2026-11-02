<?php
/**
 * توس تعمیر - Router ساده مبتنی بر regex
 */
class Router {
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $pattern, callable|array $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable|array $handler): void { $this->add('POST', $pattern, $handler); }

    private function add(string $method, string $pattern, callable|array $handler): void {
        // الگو: {slug} -> named capture
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[$method][] = ['#^' . $regex . '$#u', $handler];
    }

    public function dispatch(string $method, string $path): void {
        $path = '/' . trim($path, '/');
        if ($path === '/') $path = '/';
        foreach ($this->routes[$method] ?? [] as [$regex, $handler]) {
            if (preg_match($regex, $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                if (is_array($handler)) {
                    [$ctrl, $action] = $handler;
                    $instance = new $ctrl();
                    $instance->$action(...array_values($params));
                } else {
                    $handler(...array_values($params));
                }
                return;
            }
        }
        // 404
        http_response_code(404);
        if (class_exists('View')) {
            View::render('front/404', ['title' => 'صفحه یافت نشد - توس تعمیر'], 'layouts/main');
        } else {
            echo '404';
        }
    }
}
