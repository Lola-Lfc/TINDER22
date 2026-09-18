<?php
require_once dirname(__DIR__, 2) . '/config.php';
if (isset($_POST['admin'])) cooker_admin_request('matchs', 'delete');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Méthode non autorisée.');
}
if (empty($_SESSION['USER_ID'])) cooker_redirect('views/backend/security/login.php');
if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(cooker_csrf('unmatch'), $_POST['csrf'])) {
    http_response_code(403); exit('Formulaire invalide. Recharge le profil et réessaie.');
}
$target = isset($_POST['idUser']) && is_string($_POST['idUser']) ? filter_var($_POST['idUser'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) : false;
if ($target === false || $target === (int)$_SESSION['USER_ID']) {
    http_response_code(422); exit('Profil invalide.');
}
$db = null;
$lock = null;
try {
    $actor = cooker_current_user();
    if (!$actor) cooker_redirect('views/backend/security/login.php');
    $actorId = (int)$actor['idUser'];
    $db = cooker_database();
    $pairLock = 'cooker_pair_' . min($actorId, $target) . '_' . max($actorId, $target);
    $acquire = $db->prepare('SELECT GET_LOCK(?, 5)');
    $acquire->execute([$pairLock]);
    if ((int)$acquire->fetchColumn() !== 1) throw new RuntimeException('Pair lock unavailable');
    $lock = $pairLock;
    // Session identity is required in both directions: other users’ pairs cannot be deleted.
    $delete = $db->prepare('DELETE FROM MATCHS WHERE (idUserM1 = ? AND idUserM2 = ?) OR (idUserM1 = ? AND idUserM2 = ?)');
    $delete->execute([$actorId, $target, $target, $actorId]);
    $_SESSION['matches_flash'] = ['message' => $delete->rowCount() > 0 ? 'Le match a été supprimé.' : 'Ce match n’est plus disponible.'];
} catch (Throwable $e) {
    error_log('Cooker unmatch failed: ' . get_class($e));
    $_SESSION['matches_flash'] = ['error' => 'Impossible de supprimer ce match pour le moment. Réessaie.'];
} finally {
    if ($lock && $db) {
        try { $release = $db->prepare('SELECT RELEASE_LOCK(?)'); $release->execute([$lock]); } catch (Throwable $e) { /* Connection may be closed. */ }
    }
}
cooker_redirect('index.php?page=matches');
