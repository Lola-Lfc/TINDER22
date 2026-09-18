<?php
require_once dirname(__DIR__, 3) . '/config.php';
if (!empty($_SESSION['USER_ID'])) cooker_redirect(cooker_profile_path($_SESSION['USER_ID']));
$flash = $_SESSION['signup_flash'] ?? [];
unset($_SESSION['signup_flash']);
$values = $flash['values'] ?? cooker_signup_values([]);
$errors = $flash['errors'] ?? [];
$genres = [];
$storageReady = false;
try {
    sql_connect();
    $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $genres = $DB->query('SELECT idGenr, libGenr FROM GENRE ORDER BY idGenr')->fetchAll(PDO::FETCH_ASSOC);
    $columns = $DB->query('SHOW COLUMNS FROM USER')->fetchAll(PDO::FETCH_COLUMN);
    $storageReady = in_array('emailUser', $columns, true) && in_array('passwordUser', $columns, true);
} catch (Throwable $e) { error_log('Cooker signup: database unavailable'); }
function field_error($field) {
    global $errors;
    if (isset($errors[$field])) echo '<span class="field-error" id="error-' . $field . '">' . cooker_escape($errors[$field]) . '</span>';
}
function field_attributes($field) {
    global $errors;
    if (isset($errors[$field])) echo ' aria-invalid="true" aria-describedby="error-' . $field . '"';
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Créer un compte — Cooker</title>
<link rel="icon" href="<?= cooker_escape(cooker_url('favicon.ico')) ?>">
<link rel="stylesheet" href="<?= cooker_escape(cooker_stylesheet_url()) ?>">
</head>
<body>
<header class="site-header"><div class="header-inner">
<a href="<?= cooker_escape(cooker_url('index.php')) ?>" aria-label="Cooker, accueil"><img class="logo" src="<?= cooker_escape(cooker_asset('logo')) ?>" alt="Cooker"></a>
<nav class="auth-nav" aria-label="Compte"><a href="<?= cooker_escape(cooker_url('views/backend/security/login.php')) ?>">Connexion</a><span class="header-cta" aria-current="page">Créer un compte</span></nav>
</div></header>
<main><section class="signup-card" aria-labelledby="signup-title">
<img class="chef-hat" src="<?= cooker_escape(cooker_asset('hat')) ?>" alt="">
<?php if (!empty($flash['success'])): ?>
<h1 id="signup-title">Ton compte est créé !</h1>
<p class="subtitle" role="status">Bienvenue <?= cooker_escape($flash['firstName']) ?> dans la communauté des cuisiniers.</p>
<p class="success-note">Ton profil a bien été enregistré. Connecte-toi pour accéder à ton compte.</p>
<a class="submit-button button-link" href="<?= cooker_escape(cooker_url('views/backend/security/login.php')) ?>">Se connecter</a>
<?php else: ?>
<h1 id="signup-title">Crée ton compte</h1><p class="subtitle">Rejoins la communauté des cuisiniers.</p>
<?php if (!$genres || !$storageReady): ?><p class="notice" role="alert">L’inscription est momentanément indisponible. Réessaie plus tard.</p><?php endif; ?>
<?php if ($errors): ?><div class="notice" role="alert" tabindex="-1" id="form-errors"><?= cooker_escape($errors['form'] ?? 'Vérifie les champs indiqués ci-dessous.') ?></div><?php endif; ?>
<form action="<?= cooker_escape(cooker_url('api/security/signup.php')) ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf()) ?>">
<div class="form-grid">
<div class="field"><label for="firstName">Prénom</label><input id="firstName" name="prenomUser" autocomplete="given-name" placeholder="Emma" maxlength="50" required value="<?= cooker_escape($values['prenomUser']) ?>"<?php field_attributes('prenomUser'); ?>><?php field_error('prenomUser'); ?></div>
<div class="field"><label for="lastName">Nom</label><input id="lastName" name="nomEUser" autocomplete="family-name" placeholder="Leroy" maxlength="50" required value="<?= cooker_escape($values['nomEUser']) ?>"<?php field_attributes('nomEUser'); ?>><?php field_error('nomEUser'); ?></div>
<div class="field"><label for="age">Âge</label><input id="age" name="age" type="number" min="18" max="120" step="1" placeholder="22" required value="<?= cooker_escape($values['age']) ?>"<?php field_attributes('age'); ?>><?php field_error('age'); ?></div>
<div class="field"><label for="gender">Genre</label><select id="gender" name="idGenr" required<?php field_attributes('idGenr'); ?>><option value="">Sélectionner…</option><?php foreach ($genres as $genre): ?><option value="<?= (int) $genre['idGenr'] ?>" <?= (string) $genre['idGenr'] === $values['idGenr'] ? 'selected' : '' ?>><?= cooker_escape($genre['libGenr']) ?></option><?php endforeach; ?></select><?php field_error('idGenr'); ?></div>
</div>
<div class="field"><label for="photo">Photo de profil <span class="optional">(facultative)</span></label><label class="photo-picker" for="photo"><img id="photo-preview" alt="Aperçu de ta photo" hidden><span id="photo-prompt"><span class="camera">📷</span>Clique pour ajouter une photo</span><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"<?php field_attributes('photo'); ?>></label><span class="hint">JPG, PNG ou WebP · 5 Mo maximum</span><?php field_error('photo'); ?><span id="photo-error" class="field-error" role="alert"></span></div>
<div class="field"><label for="bio">Biographie <span class="optional">(facultative)</span></label><textarea id="bio" name="biographie" maxlength="150" rows="3" placeholder="Parle de ce que tu aimes cuisiner, de ton budget ou de tes disponibilités…"<?php field_attributes('biographie'); ?>><?= cooker_escape($values['biographie']) ?></textarea><span class="hint bio-count"><span id="bio-count"><?= mb_strlen($values['biographie']) ?></span>/150</span><?php field_error('biographie'); ?></div>
<div class="field"><label for="email">Email</label><input id="email" name="emailUser" type="email" autocomplete="email" placeholder="emma@exemple.fr" maxlength="255" required value="<?= cooker_escape($values['emailUser']) ?>"<?php field_attributes('emailUser'); ?>><?php field_error('emailUser'); ?></div>
<div class="field"><label for="password">Mot de passe</label><input id="password" name="passwordUser" type="password" autocomplete="new-password" minlength="8" maxlength="72" required<?php field_attributes('passwordUser'); ?>><span class="hint">8 caractères minimum (72 octets maximum).</span><?php field_error('passwordUser'); ?></div>
<button class="submit-button" type="submit" <?= !$genres || !$storageReady ? 'disabled' : '' ?>>Créer mon compte 🍳</button>
</form>
<p class="auth-switch">Déjà un compte ? <a href="<?= cooker_escape(cooker_url('views/backend/security/login.php')) ?>">Se connecter</a></p>
<?php endif; ?>
</section></main>
<footer>© 2026 Cooker — Cuisinez ensemble.</footer>
<script>
'use strict';
const bio = document.getElementById('bio');
const counter = document.getElementById('bio-count');
if (bio) bio.addEventListener('input', () => { counter.textContent = Array.from(bio.value).length; });
const photo = document.getElementById('photo');
let previewUrl;
if (photo) photo.addEventListener('change', () => {
    const file = photo.files[0];
    const preview = document.getElementById('photo-preview');
    const prompt = document.getElementById('photo-prompt');
    const error = document.getElementById('photo-error');
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    preview.hidden = true;
    prompt.hidden = false;
    error.textContent = '';
    photo.setCustomValidity('');
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        error.textContent = 'Choisis une photo JPG, PNG ou WebP de 5 Mo maximum.';
        photo.setCustomValidity(error.textContent);
        return;
    }
    previewUrl = URL.createObjectURL(file);
    preview.src = previewUrl;
    preview.hidden = false;
    prompt.hidden = true;
});
const form = document.querySelector('form');
if (form) form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Création en cours…';
});
window.addEventListener('pageshow', () => {
    const button = form?.querySelector('button[type="submit"]');
    if (button && button.textContent === 'Création en cours…') { button.disabled = false; button.textContent = 'Créer mon compte 🍳'; }
});
document.getElementById('form-errors')?.focus();
</script>
</body></html>
