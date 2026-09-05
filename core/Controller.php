<?php
/**
 * Base Controller Class
 */
class Controller {
    
    /**
     * Load a view
     */
    protected function view($view, $data = []) {
        extract($data);
        
        $viewFile = BASE_PATH . '/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View not found: " . $view);
        }
    }
    
    /**
     * Load a model
     */
    protected function model($model) {
        $modelFile = BASE_PATH . '/models/' . $model . '.php';
        
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        } else {
            die("Model not found: " . $model);
        }
    }
    
    /**
     * Redirect to a URL
     */
    protected function redirect($url, $messages = []) {
        foreach ($messages as $type => $message) {
            Session::set($type, $message);
        }
        
        Router::redirect($url);
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Check if request is AJAX
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get request input
     */
    protected function input($key = null, $default = null) {
        if ($key === null) {
            return $_REQUEST;
        }
        
        return $_REQUEST[$key] ?? $default;
    }
    
    /**
     * Get file upload
     */
    protected function file($key) {
        return $_FILES[$key] ?? null;
    }
}