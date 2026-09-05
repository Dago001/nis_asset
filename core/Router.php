<?php
/**
 * Router Class
 * Handles URL routing and request dispatching
 */
class Router {
    
    protected static $routes = [];
    protected static $params = [];
    protected static $notFoundCallback;
    
    /**
     * Add a route
     */
    public static function add($route, $destination, $method = 'GET') {
        // Remove leading slash for consistency
        $route = ltrim($route, '/');
        
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-zA-Z0-9\-_]+)', $route);
        $route = '/^' . $route . '$/';
        
        self::$routes[] = [
            'pattern' => $route,
            'destination' => $destination,
            'method' => strtoupper($method)
        ];
    }
    
    /**
     * Add GET route
     */
    public static function get($route, $destination) {
        self::add($route, $destination, 'GET');
    }
    
    /**
     * Add POST route
     */
    public static function post($route, $destination) {
        self::add($route, $destination, 'POST');
    }
    
    /**
     * Add PUT route
     */
    public static function put($route, $destination) {
        self::add($route, $destination, 'PUT');
    }
    
    /**
     * Add DELETE route
     */
    public static function delete($route, $destination) {
        self::add($route, $destination, 'DELETE');
    }
    
    /**
     * Set 404 callback
     */
    public static function set404($callback) {
        self::$notFoundCallback = $callback;
    }
    
    /**
     * Dispatch the request
     */
    public static function dispatch($url) {
        // Remove query string
        $url = parse_url($url, PHP_URL_PATH);
        
        // Remove base path if needed (adjust if your app is in a subfolder)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = str_replace('\\', '/', dirname($scriptName));
        $base = ($base === '/' || $base === '\\') ? '' : $base;
        if ($base !== '' && strpos($url, $base) === 0) {
            $url = substr($url, strlen($base));
        }
        
        // Trim slashes
        $url = trim($url, '/');
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Debug output (remove in production)
        // echo "Looking for route: '$url' with method $method<br>";
        
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $url, $matches)) {
                // Extract named parameters
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        self::$params[$key] = $value;
                    }
                }
                
                // Parse destination
                $destination = $route['destination'];
                
                if (is_callable($destination)) {
                    // Callable route
                    call_user_func_array($destination, self::$params);
                    return;
                } elseif (is_string($destination)) {
                    // Controller@method format
                    $parts = explode('@', $destination);
                    $controller = $parts[0];
                    $method = $parts[1];

                    // CGIS is meant to see everything, read-only — that's
                    // now enforced uniformly through Auth::can() (every
                    // controller already checks its own module.view/create/
                    // edit/delete permission, and Auth::can() refuses any
                    // non-view/export permission for a CGIS-only user) plus
                    // UserController/SettingsController's existing
                    // users.manage / Super-Admin-only gates. A hardcoded
                    // per-route allowlist here would just fight that.

                    $controllerFile = BASE_PATH . '/controllers/' . $controller . '.php';
                    
                    if (file_exists($controllerFile)) {
                        require_once $controllerFile;
                        
                        if (class_exists($controller)) {
                            $controllerObj = new $controller();
                            
                            if (method_exists($controllerObj, $method)) {
                                call_user_func_array([$controllerObj, $method], self::$params);
                                return;
                            }
                        }
                    }
                }
            }
        }
        
        // Helper to render 404 page
        $render404 = function() {
            http_response_code(404);
            if (self::$notFoundCallback) {
                call_user_func(self::$notFoundCallback);
            } elseif (defined('BASE_PATH') && file_exists(BASE_PATH . '/views/errors/404.php')) {
                require BASE_PATH . '/views/errors/404.php';
            } else {
                echo '404 Not Found';
            }
        };

        // Method, Controller class, or file not found
        if (isset($controllerFile)) {
            $render404();
            return;
        }

        // No route found
        $render404();
    }
    
    /**
     * Get route parameters
     */
    public static function params() {
        return self::$params;
    }
    
    /**
     * Redirect to a URL
     */
    public static function redirect($url) {
        $url = ltrim($url, '/');
        header('Location: ' . BASE_URL . '/' . $url);
        exit;
    }
}