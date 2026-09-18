<?php
require_once dirname(__DIR__, 3) . '/config.php';
if (empty($_SESSION['USER_ID'])) cooker_redirect('views/backend/security/login.php');
if (!isset($isDiscovery) || !$isDiscovery) cooker_redirect('index.php?page=discover');
?>
<section class="discovery" aria-label="Découvrir des personnes avec qui cuisiner">
<h1 class="visually-hidden">Découvrir les profils</h1>
<?php if ($unavailable): ?>
<div class="signup-card"><p class="notice" role="alert">La découverte est momentanément indisponible. Réessaie plus tard.</p></div>
<?php else: ?>
<?php if (!empty($discoveryFlash['error'])): ?><p class="notice" role="alert"><?= cooker_escape($discoveryFlash['error']) ?></p><?php endif; ?>
<?php if (!empty($discoveryFlash['message'])): ?><p class="visually-hidden" role="status"><?= cooker_escape($discoveryFlash['message']) ?></p><?php endif; ?>
<?php if ($candidate): ?>
<article class="discovery-card" aria-labelledby="candidate-title" data-profile-id="<?= (int)$candidate['idUser'] ?>">
<div class="discovery-portrait">
<img class="discovery-photo <?= empty($candidate['photo']) ? 'placeholder-photo' : '' ?>" src="<?= cooker_escape(cooker_photo_url($candidate['photo'])) ?>" alt="Photo de <?= cooker_escape($candidate['prenomUser']) ?>">
<div class="discovery-identity"><h2 id="candidate-title"><?= cooker_escape($candidate['prenomUser']) ?>, <?= (int)$candidate['age'] ?> ans</h2><p><?= cooker_escape($candidate['libGenr']) ?></p></div>
</div>
<div class="discovery-body"><p class="discovery-bio"><?= cooker_escape($candidate['biographie'] ?: 'Cette personne n’a pas encore ajouté de biographie.') ?></p>
<form class="discovery-actions" action="<?= cooker_escape(cooker_url('api/likes/create.php')) ?>" method="post">
<input type="hidden" name="csrf" value="<?= cooker_escape(cooker_csrf('discover')) ?>">
<input type="hidden" name="idUserL2" value="<?= (int)$candidate['idUser'] ?>">
<button class="decision-button pass-button" type="submit" name="decision" value="pass" aria-label="Passer le profil de <?= cooker_escape($candidate['prenomUser']) ?>"><span class="decision-icon" aria-hidden="true">❌</span><span>Pass</span></button>
<button class="decision-button like-button" type="submit" name="decision" value="like" aria-label="Liker le profil de <?= cooker_escape($candidate['prenomUser']) ?>"><span class="decision-icon" aria-hidden="true">❤️</span><span>Like</span></button>
</form></div>
</article>
<?php else: ?>
<div class="signup-card discovery-empty"><img class="chef-hat" src="<?= cooker_escape(cooker_asset('hat')) ?>" alt=""><h2>Tu as vu tous les profils !</h2><p>Reviens bientôt pour découvrir de nouveaux binômes de cuisine.</p></div>
<?php endif; ?>
<?php endif; ?>
</section>
