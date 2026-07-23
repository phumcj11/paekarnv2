<?php
namespace App\Core;

/**
 * Light-weight Router  —  รองรับ:
 *   $r->get('/property/{slug}', [Ctrl::class, 'show']);
 *   $r->post('/booking',         [Ctrl::class, 'store'])->middleware('auth');
 */
class Router
{
    /** @var array<int, array{method:string,pattern:string,regex:string,params:array,handler:array|callable,middleware:array}> */
    private array $routes = [];
    private ?int $lastIdx = null;
    private string $groupPrefix = '';
    /** @var string[] */
    private array $groupMiddleware = [];

    public function get(string $path, $handler): self    { return $this->add('GET', $path, $handler); }
    public function post(string $path, $handler): self   { return $this->add('POST', $path, $handler); }
    public function put(string $path, $handler): self    { return $this->add('PUT', $path, $handler); }
    public function delete(string $path, $handler): self { return $this->add('DELETE', $path, $handler); }

    public function group(string $prefix, array $middleware, callable $cb): void
    {
        $prevPrefix = $this->groupPrefix;
        $prevMw     = $this->groupMiddleware;
        $this->groupPrefix    = rtrim($prevPrefix . $prefix, '/');
        $this->groupMiddleware = array_merge($prevMw, $middleware);
        $cb($this);
        $this->groupPrefix    = $prevPrefix;
        $this->groupMiddleware = $prevMw;
    }

    public function middleware(string|array $name): self
    {
        if ($this->lastIdx === null) return $this;
        $this->routes[$this->lastIdx]['middleware']
            = array_merge($this->routes[$this->lastIdx]['middleware'], (array)$name);
        return $this;
    }

    private function add(string $method, string $path, $handler): self
    {
        $full = '/' . trim($this->groupPrefix . '/' . ltrim($path, '/'), '/');
        if ($full === '/') $full = '/';

        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_]\w*)(?::([^}]+))?\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            $pattern  = $m[2] ?? '[^/]+';
            return '(' . $pattern . ')';
        }, $full);

        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $full,
            'regex'      => '#^' . $regex . '$#u',
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => $this->groupMiddleware,
        ];
        $this->lastIdx = array_key_last($this->routes);
        return $this;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // method override สำหรับฟอร์ม HTML
        if ($method === 'POST' && !empty($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = $this->currentPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (!preg_match($route['regex'], $uri, $matches)) continue;

            array_shift($matches);
            $vars = array_combine($route['params'], $matches) ?: [];

            // run middleware
            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw);
            }

            $this->invoke($route['handler'], $vars);
            return;
        }

        // 404
        http_response_code(404);
        View::render('errors/404');
    }

    private function invoke($handler, array $vars): void
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = new $class();
            $instance->$method(...array_values($vars));
            return;
        }
        if (is_callable($handler)) {
            $handler(...array_values($vars));
            return;
        }
        throw new \RuntimeException('Invalid route handler');
    }

    private function runMiddleware(string $mw): void
    {
        switch ($mw) {
            case 'auth':
                if (!Auth::check()) {
                    Session::flash('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
                    Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
                    redirect(url('/login'));
                }
                break;
            case 'guest':
                if (Auth::check()) redirect(url('/'));
                break;
            case 'admin':
                if (!Auth::check() || Auth::user()['role'] !== 'admin') {
                    http_response_code(403);
                    View::render('errors/403');
                    exit;
                }
                break;
            case 'owner':
                if (!Auth::check()) {
                    Session::flash('error', 'กรุณาเข้าสู่ระบบสำหรับเจ้าของแพ');
                    Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
                    redirect(url('/owner/login'));
                }
                if (!in_array(Auth::user()['role'], ['owner','admin'])) {
                    http_response_code(403);
                    View::render('errors/403', ['message' => 'หน้านี้สำหรับเจ้าของแพเท่านั้น']);
                    exit;
                }
                break;
            case 'provider':
                if (!Auth::check()) {
                    Session::flash('error', 'กรุณาเข้าสู่ระบบสำหรับผู้ให้บริการ');
                    Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
                    redirect(url('/provider/login'));
                }
                if (!in_array(Auth::user()['role'], ['provider', 'admin'], true)) {
                    http_response_code(403);
                    View::render('errors/403', ['message' => 'หน้านี้สำหรับผู้ให้บริการเท่านั้น']);
                    exit;
                }
                break;
            case 'csrf':
                if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
                    if (!Csrf::verify($_POST['_csrf'] ?? null)) {
                        $path = $this->currentPath();
                        if (in_array($path, Csrf::publicAuthPostPaths(), true)) {
                            Session::flash(
                                'error',
                                'เซสชันหมดอายุหรือเปิดฟอร์มค้างนานเกินไป กรุณากรอกและกดส่งใหม่อีกครั้ง'
                            );
                            redirect(Csrf::publicAuthRedirectFor($path));
                        }
                        http_response_code(419);
                        View::render('errors/403', ['message' => 'CSRF token mismatch']);
                        exit;
                    }
                }
                break;
        }
    }

    private function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';

        // ลบ base path (เช่น /paekan_v1 หรือ /paekan_v1/public) ออก
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base   = preg_replace('#/public/index\.php$#', '', $script);
        $base   = preg_replace('#/index\.php$#', '', $base);

        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        // กรณีเข้าตรง /public/...
        $uri = preg_replace('#^/public#', '', $uri) ?: '/';
        if ($uri === '' ) $uri = '/';
        return $uri;
    }
}
