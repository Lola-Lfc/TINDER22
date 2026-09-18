<?php
require_once dirname(__DIR__, 2) . '/config.php';
cooker_require_admin();
if (!isset($isAdmin) || !$isAdmin) cooker_redirect('index.php?page=admin');
$sections = cooker_admin_sections();
$section = isset($_GET['section']) && is_string($_GET['section']) ? $_GET['section'] : 'users';
if (!isset($sections[$section])) { http_response_code(404); echo '<p class="notice">Panneau introuvable.</p>'; return; }
$definition = $sections[$section];
$adminFlash = $_SESSION['admin_flash'] ?? []; unset($_SESSION['admin_flash']);
$offset = isset($_GET['offset']) && is_string($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483500]]) : 0;
if ($offset === false) $offset = 0;
try {
    $db = cooker_database();
    $counts = [];
    foreach ($sections as $key => $item) $counts[$key] = (int)$db->query('SELECT COUNT(*) FROM ' . $item['table'])->fetchColumn();
    $rows = $db->query('SELECT ' . implode(', ', array_merge($definition['keys'], array_keys($definition['fields']), $section === 'users' ? ['photo'] : [])) . ' FROM ' . $definition['table'] . ' ORDER BY ' . implode(', ', $definition['keys']) . ' LIMIT 100 OFFSET ' . (int)$offset)->fetchAll(PDO::FETCH_ASSOC);
    $people = $db->query('SELECT idUser, prenomUser, nomEUser FROM USER ORDER BY prenomUser, idUser')->fetchAll(PDO::FETCH_ASSOC);
    $genres = $db->query('SELECT idGenr, libGenr FROM GENRE ORDER BY idGenr')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(503); error_log('Cooker admin list failed: ' . get_class($e));
    echo '<p class="notice">Administration momentanément indisponible.</p>'; return;
}
$personNames = [];
foreach ($people as $person) $personNames[$person['idUser']] = $person['prenomUser'] . ' ' . $person['nomEUser'];
$renderFields = function ($row, $creating, $prefix) use ($section, $definition, $people, $genres) {
    $fields = $creating && count($definition['keys']) === 2 ? array_merge(array_fill_keys($definition['keys'], 'Utilisateur'), $definition['fields']) : $definition['fields'];
    if ($section === 'users') $fields['passwordUser'] = $creating ? 'Mot de passe' : 'Nouveau mot de passe (facultatif)';
    foreach ($fields as $field => $label) {
        $value = $row[$field] ?? ''; $id = $prefix . '-' . $field;
        echo '<div class="field"><label for="' . cooker_escape($id) . '">' . cooker_escape($label) . '</label>';
        if ($field === 'idGenr' || in_array($field, $definition['keys'], true) || $field === 'likeL1') {
            echo '<select id="' . cooker_escape($id) . '" name="' . $field . '" required>';
            if ($field === 'likeL1') $options = [['id' => '0', 'label' => 'Pass'], ['id' => '1', 'label' => 'Like']];
            elseif ($field === 'idGenr') $options = array_map(fn($genre) => ['id' => $genre['idGenr'], 'label' => $genre['libGenr']], $genres);
            else $options = array_map(fn($person) => ['id' => $person['idUser'], 'label' => '#' . $person['idUser'] . ' — ' . $person['prenomUser'] . ' ' . $person['nomEUser']], $people);
            if ($field !== 'likeL1') echo '<option value="">Choisir…</option>';
            foreach ($options as $option) echo '<option value="' . cooker_escape($option['id']) . '"' . ((string)$value === (string)$option['id'] ? ' selected' : '') . '>' . cooker_escape($option['label']) . '</option>';
            echo '</select>';
        } elseif ($field === 'biographie' || $field === 'libComment') {
            echo '<textarea id="' . cooker_escape($id) . '" name="' . $field . '" maxlength="' . ($field === 'biographie' ? 150 : 300) . '"' . ($field === 'libComment' ? ' required' : '') . '>' . cooker_escape($value) . '</textarea>';
        } else {
            $type = $field === 'emailUser' ? 'email' : ($field === 'age' ? 'number' : ($field === 'passwordUser' ? 'password' : 'text'));
            $attributes = $field === 'age' ? ' min="18" max="120"' : ' maxlength="' . ($field === 'emailUser' ? 255 : ($field === 'passwordUser' ? 72 : ($field === 'libGenr' ? 30 : 50))) . '"';
            echo '<input id="' . cooker_escape($id) . '" name="' . $field . '" type="' . $type . '"' . $attributes . ($field !== 'passwordUser' || $creating ? ' required' : '') . ($field === 'passwordUser' ? ' autocomplete="new-password"' : ' value="' . cooker_escape($value) . '"') . '>';
        }
        echo '</div>';
    }
    if ($section === 'users') echo '<div class="field"><label for="' . cooker_escape($prefix) . '-photo">Photo (facultative)</label><input id="' . cooker_escape($prefix) . '-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"><span class="hint">JPG, PNG ou WebP, 5 Mo maximum. Sans sélection, la photo actuelle est conservée.</span></div>';
};
$hiddenFields = function ($row = []) use ($definition) {
    echo '<input type="hidden" name="admin" value="1"><input type="hidden" name="csrf" value="' . cooker_escape(cooker_csrf('admin')) . '">';
    foreach ($definition['keys'] as $key) if (isset($row[$key])) echo '<input type="hidden" name="' . $key . '" value="' . (int)$row[$key] . '">';
};
?>
<section class="admin-page" aria-labelledby="admin-title">
<h1 id="admin-title">Administration Cooker</h1><p class="subtitle">Gestion du site · Compte administrateur #4</p>
<nav class="admin-tabs" aria-label="Panneaux d’administration">
<?php foreach ($sections as $key => $item): ?><a href="<?= cooker_escape(cooker_url('index.php?page=admin&section=' . $key)) ?>" <?= $section === $key ? 'aria-current="page" class="active"' : '' ?>><?= cooker_escape($item['title']) ?> <span><?= $counts[$key] ?></span></a><?php endforeach; ?>
</nav>
<?php if (!empty($adminFlash['message'])): ?><p class="notice success-notice" role="status"><?= cooker_escape($adminFlash['message']) ?></p><?php endif; ?>
<?php if (!empty($adminFlash['error'])): ?><p class="notice" role="alert"><?= cooker_escape($adminFlash['error']) ?></p><?php endif; ?>
<div class="admin-heading"><h2><?= cooker_escape($definition['title']) ?></h2><span><?= $counts[$section] ?> élément(s)</span></div>
<details class="admin-create"><summary>Ajouter <?= $section === 'users' ? 'un utilisateur' : 'un élément' ?></summary>
<form class="admin-form" action="<?= cooker_escape(cooker_url('api/' . $section . '/create.php')) ?>" method="post" enctype="multipart/form-data">
<?php $hiddenFields(); $renderFields([], true, 'create'); ?><button class="submit-button" type="submit">Créer</button>
</form></details>
<?php if (!$rows): ?><p class="admin-empty">Aucun élément pour le moment.</p><?php else: ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Identifiant(s)</th><?php foreach ($definition['fields'] as $label): ?><th><?= cooker_escape($label) ?></th><?php endforeach; ?><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $number => $row): ?>
<tr><td><?php foreach ($definition['keys'] as $key): ?><div>#<?= (int)$row[$key] ?><?php if (count($definition['keys']) === 2): ?> — <?= cooker_escape($personNames[$row[$key]] ?? 'Compte supprimé') ?><?php endif; ?></div><?php endforeach; ?></td>
<?php foreach ($definition['fields'] as $field => $label): ?><td><?php if ($field === 'likeL1'): ?><?= $row[$field] ? '❤️ Like' : '❌ Pass' ?><?php elseif ($field === 'idGenr'): ?><?php foreach ($genres as $genre) if ($genre['idGenr'] === $row[$field]) echo cooker_escape($genre['libGenr']); ?><?php else: ?><?= cooker_escape($row[$field]) ?><?php endif; ?></td><?php endforeach; ?>
<td class="admin-row-actions">
<?php if ($section === 'users'): ?><a href="<?= cooker_escape(cooker_url(cooker_profile_path($row['idUser']))) ?>">Voir le profil</a><?php endif; ?>
<?php if ($definition['fields']): ?><details><summary>Modifier</summary><form class="admin-form" action="<?= cooker_escape(cooker_url('api/' . $section . '/update.php')) ?>" method="post" enctype="multipart/form-data"><?php $hiddenFields($row); $renderFields($row, false, 'edit-' . $number); ?><button class="submit-button" type="submit">Enregistrer</button></form></details><?php endif; ?>
<?php if ($section !== 'users' || (int)$row['idUser'] !== 4): ?>
<details class="admin-delete"><summary>Supprimer</summary><p><?= $section === 'users' ? 'Supprimer définitivement ce compte et ses likes, matchs et commentaires ?' : 'Supprimer définitivement cet élément ?' ?></p><form action="<?= cooker_escape(cooker_url('api/' . $section . '/delete.php')) ?>" method="post"><?php $hiddenFields($row); ?><button class="unmatch-button" type="submit">Confirmer la suppression</button></form></details>
<?php endif; ?>
</td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<nav class="admin-pagination" aria-label="Pagination">
<?php if ($offset > 0): ?><a href="<?= cooker_escape(cooker_url('index.php?page=admin&section=' . $section . '&offset=' . max(0, $offset - 100))) ?>">← Précédent</a><?php endif; ?>
<?php if ($offset + 100 < $counts[$section]): ?><a href="<?= cooker_escape(cooker_url('index.php?page=admin&section=' . $section . '&offset=' . ($offset + 100))) ?>">Suivant →</a><?php endif; ?>
</nav>
</section>
