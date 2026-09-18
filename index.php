<?php
require_once __DIR__ . '/config.php';
$isHome = !isset($_GET['page']) && !array_key_exists('user', $_GET);
$isProfile = array_key_exists('user', $_GET);
$userId = $isProfile && is_string($_GET['user']) ? $_GET['user'] : '';
$isDiscovery = isset($_GET['page']) && $_GET['page'] === 'discover';
$isMatches = isset($_GET['page']) && $_GET['page'] === 'matches';
$isAdmin = isset($_GET['page']) && $_GET['page'] === 'admin';
if (($isProfile && (!preg_match('/^[1-9][0-9]*$/', $userId) || filter_var($userId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false))
    || (isset($_GET['page']) && !$isDiscovery && !$isMatches && !$isAdmin) || ($isProfile && ($isDiscovery || $isMatches || $isAdmin))) {
    http_response_code(404); exit('Page introuvable.');
}
if (empty($_SESSION['USER_ID'])) {
    if ($isProfile || $isDiscovery || $isMatches || $isAdmin) cooker_redirect('views/backend/security/login.php');
}
if ($isAdmin) cooker_require_admin();
$currentUser = null;
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
    if (!empty($_SESSION['USER_ID'])) {
        $currentUser = cooker_current_user();
        if (!$currentUser) cooker_redirect('views/backend/security/login.php');
        if ($isDiscovery) {
            $candidate = cooker_next_profile($currentUser['idUser']);
            $discoveryFlash = $_SESSION['discovery_flash'] ?? [];
            unset($_SESSION['discovery_flash']);
        } elseif ($isMatches) {
            $matches = cooker_user_matches($currentUser['idUser']);
            $matchesFlash = $_SESSION['matches_flash'] ?? [];
            unset($_SESSION['matches_flash']);
        } elseif ($isProfile) {
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
<title><?= $isAdmin ? 'Administration' : ($isHome ? 'Accueil' : ($isDiscovery ? 'Découvrir' : ($isMatches ? 'Mes matchs' : ($isOwner ? 'Mon profil' : 'Profil utilisateur')))) ?> — Cooker</title>
<link rel="icon" href="<?= cooker_escape(cooker_url('favicon.ico')) ?>">
<link rel="stylesheet" href="<?= cooker_escape(cooker_stylesheet_url()) ?>">
</head>
<body>
<header class="site-header member-header"><div class="header-inner">
<a href="<?= cooker_escape(cooker_url('index.php')) ?>" aria-label="Cooker, accueil"><img class="logo" src="<?= cooker_escape(cooker_asset('logo')) ?>" alt="Cooker"></a>
<?php if (!empty($_SESSION['USER_ID'])): ?>
<nav class="auth-nav" aria-label="Compte"><a href="<?= cooker_escape(cooker_url('index.php?page=discover')) ?>" <?= $isDiscovery ? 'aria-current="page" class="active"' : '' ?>>Découvrir</a><a href="<?= cooker_escape(cooker_url('index.php?page=matches')) ?>" <?= $isMatches ? 'aria-current="page" class="active"' : '' ?>>Mes matchs</a><a href="<?= cooker_escape(cooker_url(cooker_profile_path($_SESSION['USER_ID']))) ?>" <?= $isProfile && $isOwner ? 'aria-current="page"' : '' ?>>Mon profil</a><?php if ((int)$_SESSION['USER_ID'] === 4): ?><a href="<?= cooker_escape(cooker_url('index.php?page=admin')) ?>" <?= $isAdmin ? 'aria-current="page" class="active"' : '' ?>>Admin</a><?php endif; ?><form action="<?= cooker_escape(cooker_url('api/security/disconnect.php')) ?>" method="post"><input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('logout')) ?>"><button class="header-cta" type="submit">Se déconnecter</button></form></nav>
<?php else: ?>
<nav class="auth-nav" aria-label="Compte"><a href="<?= cooker_escape(cooker_url('views/backend/security/login.php')) ?>">Connexion</a><a class="header-cta" href="<?= cooker_escape(cooker_url('views/backend/security/signup.php')) ?>">Créer un compte</a></nav>
<?php endif; ?>
</div></header>
<main<?php if ($isHome): ?> class="home-main"<?php endif; ?>><?php if ($isAdmin): ?>
<?php require __DIR__ . '/views/backend/dashboard.php'; ?>
<?php elseif ($isHome): ?>
<section class="home-hero" aria-labelledby="home-title">
<div class="home-intro">
<p class="home-eyebrow">Une rencontre, un repas, un bon moment.</p>
<h1 id="home-title">À deux, la cuisine a meilleur goût.</h1>
<p class="home-description">Trouve quelqu’un avec qui cuisiner, partager les frais du repas et passer un bon moment autour de la table.</p>
<div class="home-actions">
<?php if (!empty($_SESSION['USER_ID'])): ?>
<a class="header-cta" href="<?= cooker_escape(cooker_url('index.php?page=discover')) ?>">Découvrir les profils</a>
<a class="home-secondary" href="<?= cooker_escape(cooker_url('index.php?page=matches')) ?>">Voir mes matchs</a>
<?php else: ?>
<a class="header-cta" href="<?= cooker_escape(cooker_url('views/backend/security/signup.php')) ?>">Commencer l’aventure</a>
<a class="home-secondary" href="<?= cooker_escape(cooker_url('views/backend/security/login.php')) ?>">J’ai déjà un compte</a>
<?php endif; ?>
</div>
</div>
<div class="home-illustration" aria-hidden="true"><div class="home-plate"><img src="<?= cooker_escape(cooker_asset('hat')) ?>" alt=""><span>Cuisinez ensemble.</span></div><span class="home-heart">♥</span><span class="home-note">Le plaisir se partage</span></div>
</section>
<section class="home-steps" aria-labelledby="home-steps-title">
<h2 id="home-steps-title">Comment ça marche ?</h2>
<div class="home-steps-grid">
<article><span class="home-step-number">1</span><h3>Présente-toi</h3><p>Crée ton profil, ajoute ta photo et quelques mots sur toi.</p></article>
<article><span class="home-step-number">2</span><h3>Trouve ton binôme</h3><p>Découvre les profils un par un et like les personnes que tu aimerais rencontrer.</p></article>
<article><span class="home-step-number">3</span><h3>C’est un match !</h3><p>Si le like est réciproque, retrouve ton binôme dans tes matchs. À vous de cuisiner ensemble !</p></article>
</div>
</section>
<?php elseif ($isDiscovery): ?>
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
