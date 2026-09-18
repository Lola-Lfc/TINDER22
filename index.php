<?php
// This existing entry point also acts as a router for the PHP preview server.
if (PHP_SAPI === 'cli-server') {
    $requestedFile = realpath($_SERVER['DOCUMENT_ROOT'] . rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
    if ($requestedFile && is_file($requestedFile) && $requestedFile !== __FILE__) return false;
}
require_once __DIR__ . '/config.php';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = rtrim(cooker_url(), '/');
$relativePath = substr($path, strlen($base));
$isProfile = preg_match('~^/user/([1-9][0-9]*)/?$~', $relativePath, $route) === 1;
if (!$isProfile && !in_array($relativePath, ['', '/', '/index.php'], true)) {
    http_response_code(404); exit('Page introuvable.');
}
if (empty($_SESSION['USER_ID'])) {
    if ($isProfile) cooker_redirect('views/backend/security/login.php');
    require __DIR__ . '/views/backend/security/signup.php'; return;
}
$unavailable = false;
$user = null;
$isOwner = false;
$editing = false;
$flash = [];
try {
    $currentUser = cooker_current_user();
    if (!$currentUser) cooker_redirect('views/backend/security/login.php');
    if (!$isProfile) cooker_redirect(cooker_profile_path($currentUser['idUser']));
    // Only this MVP's connected account is exposed by the profile route.
    if ($route[1] !== (string)$currentUser['idUser']) {
        http_response_code(404); exit('Profil introuvable.');
    }
    $user = $currentUser;
    $isOwner = true;
    $editing = isset($_GET['edit']) && $_GET['edit'] === '1';
    $flash = $_SESSION['profile_flash'] ?? [];
    unset($_SESSION['profile_flash']);
    if (!empty($flash['errors'])) $editing = true;
} catch (Throwable $e) {
    http_response_code(503); $unavailable = true;
    error_log('Cooker profile failed: ' . get_class($e));
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mon profil — Cooker</title>
<link rel="icon" href="<?= cooker_escape(cooker_url('favicon.ico')) ?>">
<link rel="stylesheet" href="<?= cooker_escape(cooker_stylesheet_url()) ?>">
</head>
<body>
<header class="site-header"><div class="header-inner">
<a href="<?= cooker_escape(cooker_url('index.php')) ?>" aria-label="Cooker, accueil"><img class="logo" src="<?= cooker_escape(cooker_asset('logo')) ?>" alt="Cooker"></a>
<nav class="auth-nav" aria-label="Compte"><a href="<?= cooker_escape(cooker_url(cooker_profile_path($_SESSION['USER_ID']))) ?>" aria-current="page">Mon profil</a><form action="<?= cooker_escape(cooker_url('api/security/disconnect.php')) ?>" method="post"><input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('logout')) ?>"><button class="header-cta" type="submit">Se déconnecter</button></form></nav>
</div></header>
<main><section class="signup-card profile-card" aria-labelledby="profile-title">
<?php if ($unavailable): ?>
<h1 id="profile-title">Mon profil</h1><p class="notice" role="alert">Ton profil est momentanément indisponible. Réessaie plus tard.</p>
<?php elseif ($editing): ?>
<?php require __DIR__ . '/views/backend/users/edit.php'; ?>
<?php else: ?>
<img class="profile-photo" src="<?= cooker_escape(cooker_photo_url($user['photo'])) ?>" alt="Photo de <?= cooker_escape($user['prenomUser']) ?>">
<h1 id="profile-title"><?= cooker_escape($user['prenomUser']) ?>, <?= (int)$user['age'] ?> ans</h1>
<p class="subtitle">Ton profil Cooker</p>
<?php if (!empty($flash['success'])): ?><p class="notice success-notice" role="status">Ton profil a bien été mis à jour.</p><?php endif; ?>
<dl class="profile-details"><dt>Prénom</dt><dd><?= cooker_escape($user['prenomUser']) ?></dd><dt>Nom</dt><dd><?= cooker_escape($user['nomEUser']) ?></dd><dt>Âge</dt><dd><?= (int)$user['age'] ?> ans</dd><dt>Genre</dt><dd><?= cooker_escape($user['libGenr']) ?></dd><dt>Email</dt><dd><?= cooker_escape($user['emailUser']) ?></dd><dt>Biographie</dt><dd class="profile-bio"><?= cooker_escape($user['biographie'] ?: 'Aucune biographie pour le moment.') ?></dd></dl>
<a class="submit-button button-link" href="<?= cooker_escape(cooker_url(cooker_profile_path($user['idUser'])) . '?edit=1') ?>">Modifier mon profil</a>
<?php endif; ?>
</section></main>
<footer>© 2026 Cooker — Cuisinez ensemble.</footer>
</body></html>
