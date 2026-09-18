<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['USER_ID'])) {
    require __DIR__ . '/views/backend/security/signup.php';
    return;
}
$unavailable = false;
$user = null;
try {
    $user = cooker_current_user();
    if (!$user) cooker_redirect('views/backend/security/login.php');
} catch (Throwable $e) {
    http_response_code(503);
    $unavailable = true;
    error_log('Cooker profile failed: ' . get_class($e));
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mon compte — Cooker</title>
<link rel="icon" href="<?= cooker_escape(cooker_url('favicon.ico')) ?>">
<link rel="stylesheet" href="<?= cooker_escape(cooker_url('src/css/style.css')) ?>">
</head>
<body>
<header class="site-header"><div class="header-inner">
<a href="<?= cooker_escape(cooker_url('index.php')) ?>" aria-label="Cooker, accueil"><img class="logo" src="<?= cooker_escape(cooker_asset('logo')) ?>" alt="Cooker"></a>
<form action="<?= cooker_escape(cooker_url('api/security/disconnect.php')) ?>" method="post"><input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('logout')) ?>"><button class="header-cta" type="submit">Se déconnecter</button></form>
</div></header>
<main><section class="signup-card" aria-labelledby="profile-title">
<?php if ($unavailable): ?>
<h1 id="profile-title">Mon compte</h1><p class="notice" role="alert">Ton compte est momentanément indisponible. Réessaie plus tard.</p>
<?php else: ?>
<?php if ($user['photo'] && preg_match('~^src/(?:uploads|images)/[a-f0-9]{32}\.jpg$~', $user['photo'])): ?><img class="profile-photo" src="<?= cooker_escape(cooker_url($user['photo'])) ?>" alt="Photo de <?= cooker_escape($user['prenomUser']) ?>"><?php else: ?><img class="chef-hat" src="<?= cooker_escape(cooker_asset('hat')) ?>" alt=""><?php endif; ?>
<h1 id="profile-title">Bonjour <?= cooker_escape($user['prenomUser']) ?> !</h1><p class="subtitle">Tu es connecté à Cooker.</p>
<dl class="profile-details"><dt>Prénom</dt><dd><?= cooker_escape($user['prenomUser']) ?></dd><dt>Nom</dt><dd><?= cooker_escape($user['nomEUser']) ?></dd><dt>Âge</dt><dd><?= (int)$user['age'] ?> ans</dd><dt>Genre</dt><dd><?= cooker_escape($user['libGenr']) ?></dd><dt>Email</dt><dd><?= cooker_escape($user['emailUser']) ?></dd><dt>Biographie</dt><dd class="profile-bio"><?= cooker_escape($user['biographie'] ?: 'Aucune biographie pour le moment.') ?></dd></dl>
<?php endif; ?>
</section></main>
<footer>© 2026 Cooker — Cuisinez ensemble.</footer>
</body></html>
