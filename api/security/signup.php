<?php
require_once dirname(__DIR__, 2) . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}
function signup_redirect($flash) {
    $_SESSION['signup_flash'] = $flash;
    header('Location: ' . cooker_url('views/backend/security/signup.php'), true, 303);
    exit;
}
$values = cooker_signup_values($_POST);
if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(cooker_csrf(), $_POST['csrf'])) {
    signup_redirect(['values' => $values, 'errors' => ['form' => 'Le formulaire a expiré. Réessaie avec ce nouveau formulaire.']]);
}
$password = $_POST['passwordUser'] ?? '';
$errors = cooker_signup_errors($values, $password);
$photo = null;
$image = null;
$upload = $_FILES['photo'] ?? null;
if ($upload && (!isset($upload['error']) || is_array($upload['error']))) {
    $errors['photo'] = 'La photo envoyée est invalide.';
} elseif ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) {
        $errors['photo'] = 'Envoie une photo de 5 Mo maximum.';
    } elseif (!is_uploaded_file($upload['tmp_name'])) {
        $errors['photo'] = 'La photo envoyée est invalide.';
    } else {
        $info = @getimagesize($upload['tmp_name']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        if (!$info || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $info[0] > 4096 || $info[1] > 4096) {
            $errors['photo'] = 'Choisis une image JPG, PNG ou WebP de 4096 × 4096 pixels maximum.';
        } else {
            $image = @imagecreatefromstring(file_get_contents($upload['tmp_name']));
            if (!$image) $errors['photo'] = 'Cette image ne peut pas être lue.';
        }
    }
}
$lock = null;
try {
    sql_connect();
    $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $DB->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $genre = $DB->prepare('SELECT idGenr FROM GENRE WHERE idGenr = ?');
    $genre->execute([$values['idGenr']]);
    if (!ctype_digit($values['idGenr']) || !$genre->fetchColumn()) $errors['idGenr'] = 'Sélectionne un genre dans la liste.';
    if ($errors) {
        if ($image) imagedestroy($image);
        signup_redirect(['values' => $values, 'errors' => $errors]);
    }
    // Serialize signups for an email: the existing schema has no unique email index.
    $lock = 'cooker_signup_users';
    $request = $DB->prepare('SELECT GET_LOCK(?, 5)');
    $request->execute([$lock]);
    if ((int) $request->fetchColumn() !== 1) throw new RuntimeException('Signup lock unavailable');
    $duplicate = $DB->prepare('SELECT idUser FROM USER WHERE LOWER(emailUser) = ? LIMIT 1');
    $duplicate->execute([$values['emailUser']]);
    if ($duplicate->fetchColumn()) {
        $errors['emailUser'] = 'Un compte existe déjà avec cette adresse email.';
    } else {
        if ($image) {
            $photo = 'src/images/' . bin2hex(random_bytes(16)) . '.jpg';
            // Re-encode instead of retaining arbitrary uploaded file contents.
            if (!imagejpeg($image, ROOT . '/' . $photo, 85)) throw new RuntimeException('Photo storage failed');
        }
        $insert = $DB->prepare('INSERT INTO USER (idGenr, nomEUser, prenomUser, emailUser, passwordUser, photo, age, biographie) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$values['idGenr'], $values['nomEUser'], $values['prenomUser'], $values['emailUser'], password_hash($password, PASSWORD_DEFAULT), $photo, $values['age'], $values['biographie']]);
    }
} catch (Throwable $e) {
    if ($photo && is_file(ROOT . '/' . $photo)) unlink(ROOT . '/' . $photo);
    error_log('Cooker signup failed: ' . get_class($e));
    $errors['form'] = 'Impossible de créer ton compte pour le moment. Réessaie plus tard.';
} finally {
    if ($image) imagedestroy($image);
    if ($lock && isset($DB)) {
        try { $release = $DB->prepare('SELECT RELEASE_LOCK(?)'); $release->execute([$lock]); } catch (Throwable $e) { /* Connection may already be closed. */ }
    }
}
if ($errors) signup_redirect(['values' => $values, 'errors' => $errors]);
$_SESSION['signup_csrf'] = bin2hex(random_bytes(32));
signup_redirect(['success' => true, 'firstName' => $values['prenomUser']]);
