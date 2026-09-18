<?php
require_once dirname(__DIR__, 2) . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}
if (!empty($_SESSION['USER_ID'])) cooker_redirect('index.php');
$email = isset($_POST['emailUser']) && is_string($_POST['emailUser']) ? strtolower(trim($_POST['emailUser'])) : '';
$password = $_POST['passwordUser'] ?? '';
$error = null;
if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(cooker_csrf('login'), $_POST['csrf'])) {
    $error = 'Le formulaire a expiré. Réessaie avec ce nouveau formulaire.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || !is_string($password) || $password === '' || strlen($password) > 72 || strpos($password, "\0") !== false) {
    $error = 'Email ou mot de passe incorrect.';
} else {
    try {
        $query = cooker_database()->prepare('SELECT idUser, passwordUser FROM USER WHERE LOWER(emailUser) = ? LIMIT 1');
        $query->execute([$email]);
        $user = $query->fetch(PDO::FETCH_ASSOC);
        // Verify even for unknown emails to avoid an immediate timing distinction.
        $hash = $user['passwordUser'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
        $valid = password_verify($password, $hash);
        if ($user && $valid) {
            session_regenerate_id(true);
            $_SESSION = ['USER_ID' => (int)$user['idUser']];
            cooker_redirect('index.php');
        }
        $error = 'Email ou mot de passe incorrect.';
    } catch (Throwable $e) {
        error_log('Cooker login failed: ' . get_class($e));
        $error = 'La connexion est momentanément indisponible. Réessaie plus tard.';
    }
}
$_SESSION['login_flash'] = ['email' => $email, 'error' => $error];
cooker_redirect('views/backend/security/login.php');
