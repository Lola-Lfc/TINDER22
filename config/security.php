<?php
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'use_strict_mode' => true]);
}
if (!empty($_SESSION['USER_ID'])) define('ID_USER', (int) $_SESSION['USER_ID']);
