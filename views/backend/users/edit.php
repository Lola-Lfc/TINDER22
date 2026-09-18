<?php
require_once dirname(__DIR__, 3) . '/config.php';
if (empty($_SESSION['USER_ID'])) cooker_redirect('views/backend/security/login.php');
if (!isset($user, $flash)) cooker_redirect(cooker_profile_path($_SESSION['USER_ID']) . '?edit=1');
$values = $flash['values'] ?? $user;
$errors = $flash['errors'] ?? [];
$genres = cooker_database()->query('SELECT idGenr, libGenr FROM GENRE ORDER BY idGenr')->fetchAll(PDO::FETCH_ASSOC);
$errorFor = function ($field) use ($errors) {
    if (isset($errors[$field])) echo '<span class="field-error" id="error-' . $field . '">' . cooker_escape($errors[$field]) . '</span>';
};
$attributesFor = function ($field) use ($errors) {
    if (isset($errors[$field])) echo ' aria-invalid="true" aria-describedby="error-' . $field . '"';
};
?>
<h1 id="profile-title">Modifier mon profil</h1><p class="subtitle">Présente-toi à tes futurs binômes.</p>
<?php if ($errors): ?><p class="notice" role="alert" tabindex="-1" id="profile-errors"><?= cooker_escape($errors['form'] ?? 'Vérifie les champs indiqués ci-dessous.') ?></p><?php endif; ?>
<form id="profile-form" action="<?= cooker_escape(cooker_url('api/users/update.php')) ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('profile')) ?>">
<input type="hidden" name="idUser" value="<?= (int)$user['idUser'] ?>">
<div class="form-grid">
<?php foreach (['prenomUser' => ['Prénom', 'given-name'], 'nomEUser' => ['Nom', 'family-name']] as $field => $metadata): ?>
<div class="field"><label for="<?= $field ?>"><?= $metadata[0] ?></label><input id="<?= $field ?>" name="<?= $field ?>" autocomplete="<?= $metadata[1] ?>" maxlength="50" required value="<?= cooker_escape($values[$field]) ?>"<?php $attributesFor($field); ?>><?php $errorFor($field); ?></div>
<?php endforeach; ?>
<div class="field"><label for="age">Âge</label><input id="age" name="age" type="number" min="18" max="120" step="1" required value="<?= cooker_escape($values['age']) ?>"<?php $attributesFor('age'); ?>><?php $errorFor('age'); ?></div>
<div class="field"><label for="gender">Genre</label><select id="gender" name="idGenr" required<?php $attributesFor('idGenr'); ?>><option value="">Sélectionner…</option><?php foreach ($genres as $genre): ?><option value="<?= (int)$genre['idGenr'] ?>" <?= (string)$genre['idGenr'] === (string)$values['idGenr'] ? 'selected' : '' ?>><?= cooker_escape($genre['libGenr']) ?></option><?php endforeach; ?></select><?php $errorFor('idGenr'); ?></div>
</div>
<div class="field"><label for="photo">Photo de profil</label><img class="profile-photo" id="profile-preview" src="<?= cooker_escape(cooker_photo_url($user['photo'])) ?>" alt="Ta photo de profil"><label class="photo-picker" for="photo"><span><span class="camera">📷</span>Changer ma photo</span><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"<?php $attributesFor('photo'); ?>></label><span class="hint">JPG, PNG ou WebP · 5 Mo maximum. Sans nouvelle photo, la photo actuelle est conservée.</span><?php $errorFor('photo'); ?><span class="field-error" id="photo-error" role="alert"></span></div>
<div class="field"><label for="bio">Biographie</label><textarea id="bio" name="biographie" maxlength="150" rows="4" placeholder="Parle de ce que tu aimes cuisiner…"<?php $attributesFor('biographie'); ?>><?= cooker_escape($values['biographie']) ?></textarea><span class="hint bio-count"><span id="bio-count"><?= mb_strlen($values['biographie']) ?></span>/150</span><?php $errorFor('biographie'); ?></div>
<button class="submit-button" type="submit">Enregistrer les modifications</button>
<a class="cancel-link" href="<?= cooker_escape(cooker_url(cooker_profile_path($user['idUser']))) ?>">Annuler</a>
</form>
<script>
'use strict';
const bio = document.getElementById('bio');
bio.addEventListener('input', () => { document.getElementById('bio-count').textContent = Array.from(bio.value).length; });
const photo = document.getElementById('photo');
const preview = document.getElementById('profile-preview');
const originalPhoto = preview.src;
let previewUrl;
photo.addEventListener('change', () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    preview.src = originalPhoto;
    const error = document.getElementById('photo-error');
    photo.setCustomValidity(''); error.textContent = '';
    const file = photo.files[0];
    if (!file) return;
    if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        error.textContent = 'Choisis une photo JPG, PNG ou WebP de 5 Mo maximum.';
        photo.setCustomValidity(error.textContent); return;
    }
    previewUrl = URL.createObjectURL(file); preview.src = previewUrl;
});
const button = document.querySelector('#profile-form button[type="submit"]');
document.getElementById('profile-form').addEventListener('submit', () => { button.disabled = true; button.textContent = 'Enregistrement…'; });
window.addEventListener('pageshow', () => { button.disabled = false; button.textContent = 'Enregistrer les modifications'; });
document.getElementById('profile-errors')?.focus();
</script>
