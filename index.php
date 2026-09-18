<?php
require_once __DIR__ . '/config.php';
$isProfile = array_key_exists('user', $_GET);
$userId = $isProfile && is_string($_GET['user']) ? $_GET['user'] : '';
$isDiscovery = isset($_GET['page']) && $_GET['page'] === 'discover';
$isMatches = isset($_GET['page']) && $_GET['page'] === 'matches';
if (($isProfile && (!preg_match('/^[1-9][0-9]*$/', $userId) || filter_var($userId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false))
    || (isset($_GET['page']) && !$isDiscovery && !$isMatches) || ($isProfile && ($isDiscovery || $isMatches))) {
    http_response_code(404); exit('Page introuvable.');
}
if (empty($_SESSION['USER_ID'])) {
    if ($isProfile || $isDiscovery || $isMatches) cooker_redirect('views/backend/security/login.php');
    require __DIR__ . '/views/backend/security/signup.php'; return;
}
$unavailable = false;
$user = null;
$isOwner = false;
$editing = false;
$flash = [];
$candidate = null;
$discoveryFlash = [];
$matches = [];
$matchesFlash = [];
try {
    $currentUser = cooker_current_user();
    if (!$currentUser) cooker_redirect('views/backend/security/login.php');
    if (!$isProfile && !$isDiscovery && !$isMatches) cooker_redirect('index.php?page=discover');
    if ($isDiscovery) {
        $candidate = cooker_next_profile($currentUser['idUser']);
        $discoveryFlash = $_SESSION['discovery_flash'] ?? [];
        unset($_SESSION['discovery_flash']);
    } elseif ($isMatches) {
        $matches = cooker_user_matches($currentUser['idUser']);
        $matchesFlash = $_SESSION['matches_flash'] ?? [];
        unset($_SESSION['matches_flash']);
    } else {
        $isOwner = $userId === (string)$currentUser['idUser'];
        $user = $isOwner ? $currentUser : cooker_public_profile($currentUser['idUser'], $userId);
        if (!$user) { http_response_code(404); exit('Profil introuvable.'); }
        $editing = isset($_GET['edit']) && $_GET['edit'] === '1';
        if ($editing && !$isOwner) { http_response_code(403); exit('Tu peux uniquement modifier ton propre profil.'); }
        if ($isOwner) {
            $flash = $_SESSION['profile_flash'] ?? [];
            unset($_SESSION['profile_flash']);
            if (!empty($flash['errors'])) $editing = true;
        }
    }
} catch (Throwable $e) {
    http_response_code(503); $unavailable = true;
    error_log('Cooker profile failed: ' . get_class($e));
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $isDiscovery ? 'Découvrir' : ($isMatches ? 'Mes matchs' : ($isOwner ? 'Mon profil' : 'Profil utilisateur')) ?> — Cooker</title>
<link rel="icon" href="<?= cooker_escape(cooker_url('favicon.ico')) ?>">
<link rel="stylesheet" href="<?= cooker_escape(cooker_stylesheet_url()) ?>">
</head>
<body>
<header class="site-header member-header"><div class="header-inner">
<a href="<?= cooker_escape(cooker_url('index.php')) ?>" aria-label="Cooker, accueil"><img class="logo" src="<?= cooker_escape(cooker_asset('logo')) ?>" alt="Cooker"></a>
<nav class="auth-nav" aria-label="Compte"><a href="<?= cooker_escape(cooker_url('index.php?page=discover')) ?>" <?= $isDiscovery ? 'aria-current="page" class="active"' : '' ?>>Découvrir</a><a href="<?= cooker_escape(cooker_url('index.php?page=matches')) ?>" <?= $isMatches ? 'aria-current="page" class="active"' : '' ?>>Mes matchs</a><a href="<?= cooker_escape(cooker_url(cooker_profile_path($_SESSION['USER_ID']))) ?>" <?= $isProfile && $isOwner ? 'aria-current="page"' : '' ?>>Mon profil</a><form action="<?= cooker_escape(cooker_url('api/security/disconnect.php')) ?>" method="post"><input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('logout')) ?>"><button class="header-cta" type="submit">Se déconnecter</button></form></nav>
</div></header>
<main><?php if ($isDiscovery): ?>
<?php require __DIR__ . '/views/backend/likes/list.php'; ?>
<?php elseif ($isMatches): ?>
<?php require __DIR__ . '/views/backend/matchs/list.php'; ?>
<?php else: ?>
<section class="signup-card profile-card" aria-labelledby="profile-title">
<?php if ($unavailable): ?>
<h1 id="profile-title">Mon profil</h1><p class="notice" role="alert">Ton profil est momentanément indisponible. Réessaie plus tard.</p>
<?php elseif ($editing): ?>
<?php require __DIR__ . '/views/backend/users/edit.php'; ?>
<?php else: ?>
<img class="profile-photo" src="<?= cooker_escape(cooker_photo_url($user['photo'])) ?>" alt="Photo de <?= cooker_escape($user['prenomUser']) ?>">
<h1 id="profile-title"><?= cooker_escape($user['prenomUser']) ?>, <?= (int)$user['age'] ?> ans</h1>
<p class="subtitle"><?= $isOwner ? 'Ton profil Cooker' : (!empty($user['isMatched']) ? 'Ton binôme de cuisine' : 'Profil Cooker') ?></p>
<?php if (!empty($flash['success'])): ?><p class="notice success-notice" role="status">Ton profil a bien été mis à jour.</p><?php endif; ?>
<dl class="profile-details"><dt>Prénom</dt><dd><?= cooker_escape($user['prenomUser']) ?></dd><?php if ($isOwner): ?><dt>Nom</dt><dd><?= cooker_escape($user['nomEUser']) ?></dd><?php endif; ?><dt>Âge</dt><dd><?= (int)$user['age'] ?> ans</dd><dt>Genre</dt><dd><?= cooker_escape($user['libGenr']) ?></dd><?php if ($isOwner): ?><dt>Email</dt><dd><?= cooker_escape($user['emailUser']) ?></dd><?php endif; ?><dt>Biographie</dt><dd class="profile-bio"><?= cooker_escape($user['biographie'] ?: 'Aucune biographie pour le moment.') ?></dd></dl>
<?php if ($isOwner): ?>
<a class="submit-button button-link" href="<?= cooker_escape(cooker_url(cooker_profile_path($user['idUser'])) . '&edit=1') ?>">Modifier mon profil</a>
<?php elseif (!empty($user['isMatched'])): ?>
<form class="unmatch-form" action="<?= cooker_escape(cooker_url('api/matchs/delete.php')) ?>" method="post">
<input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('unmatch')) ?>">
<input type="hidden" name="idUser" value="<?= (int)$user['idUser'] ?>">
<button class="submit-button unmatch-button" type="submit">Unmatch</button>
</form>
<a class="cancel-link" href="<?= cooker_escape(cooker_url('index.php?page=matches')) ?>">Retour à mes matchs</a>
<?php else: ?>
<a class="cancel-link" href="<?= cooker_escape(cooker_url('index.php?page=discover')) ?>">Retour à la découverte</a>
<?php endif; ?>
<?php endif; ?>
</section>
<?php endif; ?></main>
<footer>© 2026 Cooker — Cuisinez ensemble.</footer>
</body></html>
