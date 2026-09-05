<?php
/**
 * View Class
 * Handles view rendering and template management
 */
class View {
    
    protected $data = [];
    protected $layout;
    
    /**
     * Set view data
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Get view data
     */
    public function get($key, $default = null) {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Set layout
     */
    public function layout($layout) {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Render view
     */
    public function render($view, $data = []) {
        $this->data = array_merge($this->data, $data);
        
        // Extract data to variables
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include view file
        $viewFile = BASE_PATH . '/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new Exception("View not found: {$view}");
        }
        
        // Get view content
        $content = ob_get_clean();
        
        // Render with layout if specified
        if ($this->layout) {
            $layoutFile = BASE_PATH . '/views/layouts/' . $this->layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }
    
    /**
     * Render partial view
     */
    public function partial($view, $data = []) {
        extract($data);
        
        $viewFile = BASE_PATH . '/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }
    
    /**
     * Escape output
     */
    public static function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate URL
     */
    public static function url($path = '') {
        return BASE_URL . '/' . ltrim($path, '/');
    }
    
    /**
     * Include asset
     */
    public static function asset($path) {
        return BASE_URL . '/assets/' . ltrim($path, '/');
    }
}