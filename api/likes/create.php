<?php
require_once dirname(__DIR__, 2) . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Méthode non autorisée.');
}
if (empty($_SESSION['USER_ID'])) cooker_redirect('views/backend/security/login.php');
if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(cooker_csrf('discover'), $_POST['csrf'])) {
    http_response_code(403); exit('Formulaire invalide. Recharge la découverte et réessaie.');
}
$target = isset($_POST['idUserL2']) && is_string($_POST['idUserL2']) ? filter_var($_POST['idUserL2'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) : false;
$decision = $_POST['decision'] ?? null;
if ($target === false || !is_string($decision) || !in_array($decision, ['pass', 'like'], true) || $target === (int)$_SESSION['USER_ID']) {
    http_response_code(422); exit('Profil ou choix invalide.');
}
$db = null;
$lock = null;
try {
    $currentUser = cooker_current_user();
    if (!$currentUser) cooker_redirect('views/backend/security/login.php');
    $actor = (int)$currentUser['idUser'];
    $db = cooker_database();
    // Serialize reciprocal decisions before starting the transaction, including concurrent Likes.
    $pairLock = 'cooker_pair_' . min($actor, $target) . '_' . max($actor, $target);
    $acquire = $db->prepare('SELECT GET_LOCK(?, 5)');
    $acquire->execute([$pairLock]);
    if ((int)$acquire->fetchColumn() !== 1) throw new RuntimeException('Pair lock unavailable');
    $lock = $pairLock;
    $db->beginTransaction();
    // Actor is always the connected account. Duplicate submissions keep the first decision.
    $query = cooker_database()->prepare('INSERT INTO LIKES (idUserL1, idUserL2, likeL1) SELECT ?, idUser, ? FROM USER WHERE idUser = ? AND idUser <> ? ON DUPLICATE KEY UPDATE idUserL1 = idUserL1');
    $query->execute([(int)$currentUser['idUser'], $decision === 'like' ? 1 : 0, $target, (int)$currentUser['idUser']]);
    $inserted = $query->rowCount() > 0;
    $match = $decision === 'like' ? cooker_create_reciprocal_match($db, $actor, $target) : null;
    $db->commit();
    $_SESSION['discovery_flash'] = ['message' => $inserted ? ($decision === 'like' ? 'Like enregistré.' : 'Profil passé.') : 'Ce profil a déjà été vu ou n’est plus disponible.'];
    if ($match) $_SESSION['discovery_flash']['match'] = $match;
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) $db->rollBack();
    error_log('Cooker discovery decision failed: ' . get_class($e));
    $_SESSION['discovery_flash'] = ['error' => 'Impossible d’enregistrer ton choix pour le moment. Réessaie.'];
} finally {
    if ($lock && $db) {
        try { $release = $db->prepare('SELECT RELEASE_LOCK(?)'); $release->execute([$lock]); } catch (Throwable $e) { /* Connection may be closed. */ }
    }
}
cooker_redirect('index.php?page=discover');
