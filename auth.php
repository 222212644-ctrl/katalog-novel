<?php
// ============================================================
// auth.php — Disertakan di semua halaman yang perlu login
// ============================================================
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['loggedin'])) {
        header('Location: login.php');
        exit;
    }
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['loggedin']);
}
