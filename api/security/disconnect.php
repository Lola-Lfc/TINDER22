<?php
require_once dirname(__DIR__, 2) . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}
if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(cooker_csrf('logout'), $_POST['csrf'])) {
    http_response_code(403);
    exit('Formulaire invalide. Retourne sur ton compte et réessaie.');
}
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $params['path'], 'domain' => $params['domain'], 'secure' => $params['secure'], 'httponly' => $params['httponly'], 'samesite' => $params['samesite'] ?? 'Lax']);
session_destroy();
cooker_redirect('views/backend/security/login.php');
