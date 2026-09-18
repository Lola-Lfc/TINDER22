<?php
require_once dirname(__DIR__, 3) . '/config.php';
if (!empty($_SESSION['USER_ID'])) cooker_redirect(cooker_profile_path($_SESSION['USER_ID']));
$flash = $_SESSION['login_flash'] ?? [];
unset($_SESSION['login_flash']);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion — Cooker</title>
<link rel="icon" href="<?= cooker_escape(cooker_url('favicon.ico')) ?>">
<link rel="stylesheet" href="<?= cooker_escape(cooker_stylesheet_url()) ?>">
</head>
<body>
<header class="site-header"><div class="header-inner">
<a href="<?= cooker_escape(cooker_url('index.php')) ?>" aria-label="Cooker, accueil"><img class="logo" src="<?= cooker_escape(cooker_asset('logo')) ?>" alt="Cooker"></a>
<nav class="auth-nav" aria-label="Compte"><a class="active" href="<?= cooker_escape(cooker_url('views/backend/security/login.php')) ?>" aria-current="page">Connexion</a><a class="header-cta" href="<?= cooker_escape(cooker_url('views/backend/security/signup.php')) ?>">Créer un compte</a></nav>
</div></header>
<main class="login-main"><section class="signup-card login-card" aria-labelledby="login-title">
<img class="chef-hat" src="<?= cooker_escape(cooker_asset('hat')) ?>" alt="">
<h1 id="login-title">Bon retour !</h1><p class="subtitle">Connecte-toi pour retrouver tes binômes.</p>
<?php if (!empty($flash['error'])): ?><p class="notice" role="alert"><?= cooker_escape($flash['error']) ?></p><?php endif; ?>
<form action="<?= cooker_escape(cooker_url('api/security/login.php')) ?>" method="post">
<input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('login')) ?>">
<div class="field"><label for="email">Email</label><input id="email" name="emailUser" type="email" autocomplete="email" placeholder="toi@exemple.fr" maxlength="255" required value="<?= cooker_escape($flash['email'] ?? '') ?>"></div>
<div class="field"><label for="password">Mot de passe</label><input id="password" name="passwordUser" type="password" autocomplete="current-password" placeholder="••••••••" maxlength="72" required></div>
<button class="submit-button" type="submit">Se connecter</button>
</form>
<p class="auth-switch">Pas encore de compte ? <a href="<?= cooker_escape(cooker_url('views/backend/security/signup.php')) ?>">S’inscrire</a></p>
</section></main>
<footer>© 2026 Cooker — Cuisinez ensemble.</footer>
</body></html>
