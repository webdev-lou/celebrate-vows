<?php
/**
 * Session Management
 * Handles admin authentication sessions
 */

require_once __DIR__ . '/../api/config.php';

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(ADMIN_SESSION_NAME);
            session_start();
        }
    }

    public static function isLoggedIn()
    {
        self::start();
        return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
    }

    public static function login($userId, $username)
    {
        self::start();
        $_SESSION['admin_id'] = $userId;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        session_regenerate_id(true);
    }

    public static function logout()
    {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function getAdminId()
    {
        self::start();
        return $_SESSION['admin_id'] ?? null;
    }

    public static function getAdminUsername()
    {
        self::start();
        return $_SESSION['admin_username'] ?? null;
    }

    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function generateCSRFToken()
    {
        self::start();
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function validateCSRFToken($token)
    {
        self::start();
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
