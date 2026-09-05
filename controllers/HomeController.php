<?php
/**
 * Home Controller
 *
 * The public landing point simply routes the visitor to the correct place.
 * (The former diagnostic "welcome" screen leaked server paths, the PHP
 * version and reflected the raw request URI — it has been removed.)
 */
class HomeController {

    public function index() {
        $base = defined('BASE_URL') ? BASE_URL : '';

        if (class_exists('Auth') && Auth::check()) {
            header('Location: ' . $base . '/dashboard');
        } else {
            header('Location: ' . $base . '/auth/login');
        }
        exit;
    }
}
