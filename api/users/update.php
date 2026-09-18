<?php
require_once dirname(__DIR__, 2) . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Méthode non autorisée.');
}
if (empty($_SESSION['USER_ID'])) cooker_redirect('views/backend/security/login.php');
if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(cooker_csrf('profile'), $_POST['csrf'])) {
    http_response_code(403); exit('Formulaire invalide. Recharge ton profil et réessaie.');
}
// The submitted ID can never grant access to a different account.
if (isset($_POST['idUser']) && (!is_string($_POST['idUser']) || $_POST['idUser'] !== (string)$_SESSION['USER_ID'])) {
    http_response_code(403); exit('Tu peux uniquement modifier ton propre profil.');
}
$values = cooker_signup_values($_POST);
$errors = cooker_profile_errors($values);
$image = null;
$newPhoto = null;
try {
    $user = cooker_current_user();
    if (!$user) cooker_redirect('views/backend/security/login.php');
    $db = cooker_database();
    $genre = $db->prepare('SELECT idGenr FROM GENRE WHERE idGenr = ?');
    $genre->execute([$values['idGenr']]);
    if (!ctype_digit($values['idGenr']) || !$genre->fetchColumn()) $errors['idGenr'] = 'Sélectionne un genre dans la liste.';
    try { $image = cooker_uploaded_image($_FILES['photo'] ?? null); }
    catch (InvalidArgumentException $e) { $errors['photo'] = $e->getMessage(); }
    if (!$errors) {
        if ($image) $newPhoto = cooker_store_photo($image);
        $update = $db->prepare('UPDATE USER SET prenomUser = ?, nomEUser = ?, age = ?, idGenr = ?, biographie = ?' . ($newPhoto ? ', photo = ?' : '') . ' WHERE idUser = ?');
        $parameters = [$values['prenomUser'], $values['nomEUser'], $values['age'], $values['idGenr'], $values['biographie']];
        if ($newPhoto) $parameters[] = $newPhoto;
        $parameters[] = (int)$_SESSION['USER_ID'];
        $update->execute($parameters);
    }
} catch (Throwable $e) {
    if ($newPhoto && is_file(ROOT . '/' . $newPhoto)) unlink(ROOT . '/' . $newPhoto);
    $errors['form'] = 'Impossible de modifier ton profil pour le moment. Réessaie plus tard.';
    error_log('Cooker profile update failed: ' . get_class($e));
} finally {
    if ($image) imagedestroy($image);
}
$_SESSION['profile_flash'] = $errors ? ['errors' => $errors, 'values' => $values] : ['success' => true];
cooker_redirect(cooker_profile_path($_SESSION['USER_ID']) . ($errors ? '&edit=1' : ''));
