<?php

namespace App\Core;

use App\Services\AccountControlService;
use App\Services\AccountDeletionService;

class Router
{
    private $routes = [];
    private array $redirects = [];

    public function get($uri, $action)
    {
        $this->routes['GET'][$uri] = $action;
    }

    public function post($uri, $action)
    {
        $this->routes['POST'][$uri] = $action;
    }

    /** Keep one canonical GET route while preserving a legacy bookmark. */
    public function redirect(string $from, string $to, int $status = 301): void
    {
        $this->redirects[$from] = ['to' => $to, 'status' => $status];
    }

    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $configuredUrl = (string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost/ani-mart/public');
        $basePath = rtrim((string) parse_url($configuredUrl, PHP_URL_PATH), '/');

        if ($basePath !== '' && ($uri === $basePath || str_starts_with($uri, $basePath . '/'))) {
            $uri = substr($uri, strlen($basePath));
        } elseif ($uri === '/ani-mart/public' || str_starts_with($uri, '/ani-mart/public/')) {
            // Static assets from older local builds may still call this path.
            // InfinityFree rewrites it into public/ without changing REQUEST_URI.
            $uri = substr($uri, strlen('/ani-mart/public'));
        }

        if ($uri === '') {
            $uri = '/';
        }

        if ($method === 'GET' && isset($this->redirects[$uri])) {
            $target = $basePath . $this->redirects[$uri]['to'];
            $query = $_SERVER['QUERY_STRING'] ?? '';
            if ($query !== '') {
                $target .= '?' . $query;
            }
            header('Location: ' . $target, true, $this->redirects[$uri]['status']);
            exit;
        }

        $sessionUser = Session::get('user');
        if (!empty($sessionUser['id'])) {
            try {
                if ((new AccountControlService())->isSuspended((int) $sessionUser['id'])) {
                    Session::remove('user');
                    Session::remove('active_shop_id');
                    Session::flash('error', 'Your account is suspended. Contact Ani-Mart support for assistance.');
                    header('Location: ' . $basePath . '/login');
                    exit;
                }
                if ((new AccountDeletionService())->isDeleted((int) $sessionUser['id'])) {
                    Session::remove('user');
                    Session::remove('active_shop_id');
                    Session::flash('error', 'This account has been deleted.');
                    header('Location: ' . $basePath . '/login');
                    exit;
                }
            } catch (\Throwable $e) {
                error_log('Account status check failed: ' . $e->getMessage());
            }
        }

        $action = $this->routes[$method][$uri] ?? null;

        $params = [];

        if (!$action) {
            foreach ($this->routes[$method] ?? [] as $route => $routeAction) {
                $pattern =
                    preg_replace(
                        '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                        '([^/]+)',
                        $route
                    );

                $pattern = '#^' . $pattern . '$#';

                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches);

                    $action = $routeAction;
                    $params = array_map('urldecode', $matches);

                    break;
                }
            }
        }

        if (!$action) {
            http_response_code(404);
            echo "404 - Page Not Found";
            return;
        }

        [$controller, $methodAction] = $action;

        if (!class_exists($controller)) {
            throw new \RuntimeException("Controller not found: {$controller}");
        }

        $controllerInstance = new $controller();

        if (!method_exists($controllerInstance, $methodAction)) {
            throw new \RuntimeException("Method not found: {$methodAction}");
        }

        return call_user_func_array(
            [$controllerInstance, $methodAction],
            $params
        );
    }
}
