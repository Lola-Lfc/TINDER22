<?php
require_once dirname(__DIR__, 3) . '/config.php';
if (empty($_SESSION['USER_ID'])) cooker_redirect('views/backend/security/login.php');
if (!isset($isMatches) || !$isMatches) cooker_redirect('index.php?page=matches');
?>
<section class="matches-page" aria-labelledby="matches-title">
<h1 id="matches-title">Mes matchs</h1>
<p class="subtitle">Tes futurs binômes de cuisine.</p>
<?php if (!empty($matchesFlash['message'])): ?><p class="notice success-notice" role="status"><?= cooker_escape($matchesFlash['message']) ?></p><?php endif; ?>
<?php if (!empty($matchesFlash['error'])): ?><p class="notice" role="alert"><?= cooker_escape($matchesFlash['error']) ?></p><?php endif; ?>
<?php if ($unavailable): ?>
<p class="notice" role="alert">Tes matchs sont momentanément indisponibles. Réessaie plus tard.</p>
<?php elseif (!$matches): ?>
<div class="signup-card matches-empty"><img class="chef-hat" src="<?= cooker_escape(cooker_asset('hat')) ?>" alt=""><h2>Pas encore de match</h2><p>Découvre les profils et like les personnes avec qui tu aimerais cuisiner. Un like réciproque crée un match.</p><a class="submit-button button-link" href="<?= cooker_escape(cooker_url('index.php?page=discover')) ?>">Découvrir les profils</a></div>
<?php else: ?>
<div class="matches-grid">
<?php foreach ($matches as $match): ?>
<article class="match-card" aria-labelledby="match-name-<?= (int)$match['idUser'] ?>" data-match-user-id="<?= (int)$match['idUser'] ?>">
<img class="match-card-photo <?= empty($match['photo']) ? 'placeholder-photo' : '' ?>" src="<?= cooker_escape(cooker_photo_url($match['photo'])) ?>" alt="Photo de <?= cooker_escape($match['prenomUser']) ?>">
<div class="match-card-body"><h2 id="match-name-<?= (int)$match['idUser'] ?>"><?= cooker_escape($match['prenomUser']) ?>, <?= (int)$match['age'] ?> ans</h2><p class="match-card-gender"><?= cooker_escape($match['libGenr']) ?></p><p class="match-card-bio"><?= cooker_escape($match['biographie'] ?: 'Aucune biographie pour le moment.') ?></p><a class="submit-button button-link" href="<?= cooker_escape(cooker_url(cooker_profile_path($match['idUser']))) ?>" aria-label="Voir le profil de <?= cooker_escape($match['prenomUser']) ?>">Voir le profil</a></div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
