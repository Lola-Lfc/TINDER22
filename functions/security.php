<?php
// Check if user have access to ressource, take level needed and return boolean
function check_access($level) {
    if(isset($_SESSION['id_user'])){
        $user_level = sql_select("MEMBRE", 'numStat', "numMemb = " . $_SESSION['id_user'])[0]['numStat'];
        if($user_level <= $level){
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

// Fonctions communes aux formulaires Cooker.
function cooker_url($path = '') {
    $base = preg_replace('~/(?:views/.*|api/.*|index\.php(?:/.*)?)$~', '', $_SERVER['SCRIPT_NAME']);
    return $base . '/' . ltrim($path, '/');
}
// Versionner le CSS pour éviter de réutiliser une ancienne feuille en cache.
function cooker_stylesheet_url() {
    return cooker_url('src/css/style.css') . '?v=' . filemtime(ROOT . '/src/css/style.css');
}
function cooker_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function cooker_csrf($scope = 'signup') {
    $key = $scope . '_csrf';
    if (empty($_SESSION[$key])) $_SESSION[$key] = bin2hex(random_bytes(32));
    return $_SESSION[$key];
}

function cooker_redirect($path) {
    header('Location: ' . cooker_url($path), true, 303);
    exit;
}
function cooker_database() {
    global $DB;
    if (!isset($DB)) sql_connect();
    $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $DB->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $DB;
}
function cooker_current_user() {
    if (empty($_SESSION['USER_ID'])) return null;
    $query = cooker_database()->prepare('SELECT u.idUser, u.idGenr, u.prenomUser, u.nomEUser, u.age, u.photo, u.biographie, u.emailUser, g.libGenr FROM USER u JOIN GENRE g ON g.idGenr = u.idGenr WHERE u.idUser = ?');
    $query->execute([$_SESSION['USER_ID']]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
    if (!$user) unset($_SESSION['USER_ID']);
    return $user ?: null;
}

function cooker_signup_values($input) {
    $values = [];
    foreach (['prenomUser', 'nomEUser', 'age', 'idGenr', 'biographie', 'emailUser'] as $field) {
        $values[$field] = isset($input[$field]) && is_string($input[$field]) ? trim($input[$field]) : '';
    }
    $values['emailUser'] = strtolower($values['emailUser']);
    return $values;
}
function cooker_signup_errors($values, $password) {
    $errors = [];
    foreach (['prenomUser' => 'prénom', 'nomEUser' => 'nom'] as $field => $label) {
        if ($values[$field] === '' || mb_strlen($values[$field]) > 50) $errors[$field] = 'Indique ton ' . $label . ' (50 caractères maximum).';
    }
    if (filter_var($values['age'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 18, 'max_range' => 120]]) === false) $errors['age'] = 'Indique un âge entre 18 et 120 ans.';
    if (!filter_var($values['emailUser'], FILTER_VALIDATE_EMAIL) || strlen($values['emailUser']) > 255) $errors['emailUser'] = 'Indique une adresse email valide.';
    if (mb_strlen($values['biographie']) > 150) $errors['biographie'] = 'La biographie doit contenir 150 caractères maximum.';
    if (!is_string($password) || mb_strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) $errors['passwordUser'] = 'Choisis un mot de passe de 8 caractères minimum, limité à 72 octets.';
    // The unchanged database uses utf8mb3, which cannot store supplementary characters.
    foreach (['prenomUser', 'nomEUser', 'biographie'] as $field) {
        if (!mb_check_encoding($values[$field], 'UTF-8') || preg_match('/[\x{10000}-\x{10FFFF}]/u', $values[$field])) {
            $errors[$field] = 'Ce champ contient un caractère non pris en charge. Utilise du texte sans emoji.';
        }
    }
    return $errors;
}

function cooker_profile_path($id) {
    return 'index.php?user=' . (int)$id;
}
function cooker_profile_errors($values) {
    $errors = cooker_signup_errors(array_merge($values, ['emailUser' => 'profile@example.com']), 'placeholder');
    unset($errors['emailUser'], $errors['passwordUser']);
    return $errors;
}
function cooker_uploaded_image($upload) {
    if ($upload === null) return null;
    if (!is_array($upload) || !isset($upload['error']) || !is_int($upload['error'])) throw new InvalidArgumentException('La photo envoyée est invalide.');
    if ($upload['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($upload['error'] !== UPLOAD_ERR_OK || !isset($upload['size']) || $upload['size'] > 5 * 1024 * 1024) throw new InvalidArgumentException('Envoie une photo de 5 Mo maximum.');
    if (!isset($upload['tmp_name']) || !is_string($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) throw new InvalidArgumentException('La photo envoyée est invalide.');
    $info = @getimagesize($upload['tmp_name']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    if (!$info || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || $info[0] > 4096 || $info[1] > 4096) throw new InvalidArgumentException('Choisis une image JPG, PNG ou WebP de 4096 × 4096 pixels maximum.');
    $image = @imagecreatefromstring(file_get_contents($upload['tmp_name']));
    if (!$image) throw new InvalidArgumentException('Cette image ne peut pas être lue.');
    return $image;
}
function cooker_store_photo($image) {
    $path = 'src/images/' . bin2hex(random_bytes(16)) . '.jpg';
    if (!imagejpeg($image, ROOT . '/' . $path, 85)) {
        if (is_file(ROOT . '/' . $path)) unlink(ROOT . '/' . $path);
        throw new RuntimeException('Photo storage failed');
    }
    return $path;
}
function cooker_photo_url($path) {
    return is_string($path) && preg_match('~^src/(?:uploads|images)/[a-f0-9]{32}\.jpg$~', $path) ? cooker_url($path) : cooker_asset('hat');
}

function cooker_next_profile($userId) {
    $query = cooker_database()->prepare('SELECT u.idUser, u.prenomUser, u.age, u.photo, u.biographie, g.libGenr FROM USER u JOIN GENRE g ON g.idGenr = u.idGenr WHERE u.idUser <> ? AND NOT EXISTS (SELECT 1 FROM LIKES l WHERE l.idUserL1 = ? AND l.idUserL2 = u.idUser) ORDER BY u.idUser LIMIT 1');
    $query->execute([(int)$userId, (int)$userId]);
    return $query->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Called while holding the pair lock and the Like transaction.
function cooker_create_reciprocal_match($db, $actor, $target) {
    $likes = $db->prepare('SELECT COUNT(*) FROM LIKES WHERE likeL1 = 1 AND ((idUserL1 = ? AND idUserL2 = ?) OR (idUserL1 = ? AND idUserL2 = ?))');
    $likes->execute([$actor, $target, $target, $actor]);
    if ((int)$likes->fetchColumn() !== 2) return null;
    $existing = $db->prepare('SELECT 1 FROM MATCHS WHERE (idUserM1 = ? AND idUserM2 = ?) OR (idUserM1 = ? AND idUserM2 = ?) LIMIT 1');
    $existing->execute([$actor, $target, $target, $actor]);
    if ($existing->fetchColumn()) return null;
    $insert = $db->prepare('INSERT INTO MATCHS (idUserM1, idUserM2) VALUES (?, ?)');
    $insert->execute([min($actor, $target), max($actor, $target)]);
    $profile = $db->prepare('SELECT idUser, prenomUser, photo FROM USER WHERE idUser = ?');
    $profile->execute([$target]);
    return $profile->fetch(PDO::FETCH_ASSOC) ?: null;
}

function cooker_user_matches($userId) {
    $query = cooker_database()->prepare('SELECT u.idUser, u.prenomUser, u.age, u.photo, u.biographie, g.libGenr FROM USER u JOIN GENRE g ON g.idGenr = u.idGenr WHERE u.idUser <> ? AND EXISTS (SELECT 1 FROM MATCHS m WHERE (m.idUserM1 = ? AND m.idUserM2 = u.idUser) OR (m.idUserM2 = ? AND m.idUserM1 = u.idUser)) ORDER BY u.prenomUser, u.idUser');
    $query->execute([(int)$userId, (int)$userId, (int)$userId]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function cooker_public_profile($actor, $target) {
    $query = cooker_database()->prepare('SELECT u.idUser, u.prenomUser, u.age, u.photo, u.biographie, g.libGenr, EXISTS (SELECT 1 FROM MATCHS m WHERE (m.idUserM1 = ? AND m.idUserM2 = u.idUser) OR (m.idUserM2 = ? AND m.idUserM1 = u.idUser)) AS isMatched FROM USER u JOIN GENRE g ON g.idGenr = u.idGenr WHERE u.idUser = ?');
    $query->execute([(int)$actor, (int)$actor, (int)$target]);
    return $query->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Administration : identity comes exclusively from the server-side session.
function cooker_require_admin() {
    if (empty($_SESSION['USER_ID'])) cooker_redirect('views/backend/security/login.php');
    if ((int)$_SESSION['USER_ID'] !== 4) { http_response_code(403); exit('Accès réservé à l’administration.'); }
    try {
        if (!cooker_current_user()) cooker_redirect('views/backend/security/login.php');
    } catch (Throwable $e) {
        error_log('Cooker admin authentication failed: ' . get_class($e));
        http_response_code(503); exit('Administration momentanément indisponible.');
    }
}
function cooker_admin_sections() {
    return [
        'users' => ['title' => 'Utilisateurs', 'table' => 'USER', 'keys' => ['idUser'], 'fields' => ['prenomUser' => 'Prénom', 'nomEUser' => 'Nom', 'emailUser' => 'Email', 'age' => 'Âge', 'idGenr' => 'Genre', 'biographie' => 'Biographie']],
        'likes' => ['title' => 'Likes / Pass', 'table' => 'LIKES', 'keys' => ['idUserL1', 'idUserL2'], 'fields' => ['likeL1' => 'Décision']],
        'matchs' => ['title' => 'Matchs', 'table' => 'MATCHS', 'keys' => ['idUserM1', 'idUserM2'], 'fields' => []],
    ];
}
function cooker_admin_text($input, $key) {
    if (isset($input[$key]) && !is_string($input[$key])) throw new InvalidArgumentException('Champ invalide.');
    return trim($input[$key] ?? '');
}
function cooker_admin_id($input, $key) {
    $id = filter_var(cooker_admin_text($input, $key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($id === false) throw new InvalidArgumentException('Identifiant invalide.');
    return $id;
}
function cooker_admin_request($section, $action) {
    cooker_require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: POST'); http_response_code(405); exit('Méthode non autorisée.'); }
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals(cooker_csrf('admin'), $_POST['csrf'])) { http_response_code(403); exit('Formulaire invalide.'); }
    $sections = cooker_admin_sections();
    if (!isset($sections[$section])) { http_response_code(404); exit('Panneau introuvable.'); }
    $definition = $sections[$section];
    if ($section === 'matchs' && $action === 'update') { http_response_code(422); exit('Pour changer les personnes d’un match, supprime-le puis crée une nouvelle paire.'); }
    $db = cooker_database(); $lock = null; $image = null; $photo = null;
    try {
        $keys = [];
        if ($action !== 'create' || count($definition['keys']) === 2) {
            foreach ($definition['keys'] as $key) $keys[$key] = cooker_admin_id($_POST, $key);
        }
        if ($section === 'users' && $action === 'delete' && $keys['idUser'] === 4) throw new InvalidArgumentException('Le compte administrateur ne peut pas être supprimé.');
        if (count($keys) === 2) {
            $pair = array_values($keys);
            if ($pair[0] === $pair[1]) throw new InvalidArgumentException('Sélectionne deux personnes différentes.');
            if ($section === 'matchs') $keys = array_combine($definition['keys'], [min($pair), max($pair)]);
            $lock = 'cooker_pair_' . min($pair) . '_' . max($pair);
        } elseif ($section === 'users') $lock = 'cooker_signup_users';
        if ($lock) {
            $acquire = $db->prepare('SELECT GET_LOCK(?, 5)'); $acquire->execute([$lock]);
            if ((int)$acquire->fetchColumn() !== 1) throw new RuntimeException('Admin lock unavailable');
        }
        $db->beginTransaction();
        $where = implode(' AND ', array_map(fn($key) => "$key = ?", array_keys($keys)));
        $keyValues = array_values($keys);
        if ($section === 'matchs') {
            $where = '(idUserM1 = ? AND idUserM2 = ?) OR (idUserM1 = ? AND idUserM2 = ?)';
            $keyValues = [$pair[0], $pair[1], $pair[1], $pair[0]];
        }
        $existing = null;
        if ($action !== 'create') {
            $find = $db->prepare('SELECT * FROM ' . $definition['table'] . ' WHERE ' . $where . ' FOR UPDATE'); $find->execute($keyValues);
            $existing = $find->fetch(PDO::FETCH_ASSOC);
            if (!$existing) throw new InvalidArgumentException('Cet élément n’existe plus.');
        }
        if ($action === 'delete') {
            $query = $db->prepare('DELETE FROM ' . $definition['table'] . ' WHERE ' . $where); $query->execute($keyValues);
        } else {
            $values = [];
            if ($section === 'users') {
                $values = cooker_signup_values($_POST);
                $password = $_POST['passwordUser'] ?? '';
                $errors = cooker_signup_errors($values, $action === 'update' && $password === '' ? 'placeholder' : $password);
                if ($errors) throw new InvalidArgumentException(implode(' ', $errors));
                $genre = $db->prepare('SELECT idGenr FROM GENRE WHERE idGenr = ?'); $genre->execute([$values['idGenr']]);
                if (!$genre->fetchColumn()) throw new InvalidArgumentException('Sélectionne un genre existant.');
                $email = $db->prepare('SELECT idUser FROM USER WHERE LOWER(emailUser) = ? AND idUser <> ? LIMIT 1'); $email->execute([$values['emailUser'], $keys['idUser'] ?? 0]);
                if ($email->fetchColumn()) throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
                if ($password !== '') $values['passwordUser'] = password_hash($password, PASSWORD_DEFAULT);
                $image = cooker_uploaded_image($_FILES['photo'] ?? null);
                if ($image) { $photo = cooker_store_photo($image); $values['photo'] = $photo; }
            } elseif ($section === 'likes') {
                $choice = cooker_admin_text($_POST, 'likeL1');
                if (!in_array($choice, ['0', '1'], true)) throw new InvalidArgumentException('Choisis Like ou Pass.');
                $values['likeL1'] = (int)$choice;
            }
            if (count($keys) === 2) {
                foreach ($keys as $id) {
                    $person = $db->prepare('SELECT idUser FROM USER WHERE idUser = ?'); $person->execute([$id]);
                    if (!$person->fetchColumn()) throw new InvalidArgumentException('Sélectionne des utilisateurs existants.');
                }
            }
            if ($action === 'create') {
                if ($section === 'matchs') {
                    $duplicate = $db->prepare('SELECT 1 FROM MATCHS WHERE ' . $where); $duplicate->execute($keyValues);
                    if ($duplicate->fetchColumn()) throw new InvalidArgumentException('Ce match existe déjà.');
                }
                $values = array_merge($keys, $values);
                $query = $db->prepare('INSERT INTO ' . $definition['table'] . ' (' . implode(', ', array_keys($values)) . ') VALUES (' . implode(', ', array_fill(0, count($values), '?')) . ')'); $query->execute(array_values($values));
            } elseif ($values) {
                $query = $db->prepare('UPDATE ' . $definition['table'] . ' SET ' . implode(', ', array_map(fn($field) => "$field = ?", array_keys($values))) . ' WHERE ' . $where); $query->execute(array_merge(array_values($values), $keyValues));
            }
            if ($section === 'likes' && $values['likeL1'] === 1) cooker_create_reciprocal_match($db, $pair[0], $pair[1]);
        }
        $db->commit();
        $_SESSION['admin_flash'] = ['message' => $action === 'delete' ? 'Élément supprimé.' : ($action === 'create' ? 'Élément créé.' : 'Modifications enregistrées.')];
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        if ($photo && is_file(ROOT . '/' . $photo)) unlink(ROOT . '/' . $photo);
        $message = $e instanceof InvalidArgumentException ? $e->getMessage() : ($e instanceof PDOException && $e->getCode() === '23000' ? 'Cette relation existe déjà ou référence un élément invalide.' : 'Impossible d’enregistrer cette opération. Réessaie plus tard.');
        $_SESSION['admin_flash'] = ['error' => $message];
        error_log('Cooker admin operation failed: ' . get_class($e));
    } finally {
        if ($image) imagedestroy($image);
        if ($lock) { try { $release = $db->prepare('SELECT RELEASE_LOCK(?)'); $release->execute([$lock]); } catch (Throwable $e) {} }
    }
    cooker_redirect('index.php?page=admin&section=' . $section);
}
