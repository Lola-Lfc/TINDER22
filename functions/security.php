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
    $base = preg_replace('~/(?:views/.*|api/.*|index\.php)$~', '', $_SERVER['SCRIPT_NAME']);
    return $base . '/' . ltrim($path, '/');
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
    $query = cooker_database()->prepare('SELECT u.idUser, u.prenomUser, u.nomEUser, u.age, u.photo, u.biographie, u.emailUser, g.libGenr FROM USER u JOIN GENRE g ON g.idGenr = u.idGenr WHERE u.idUser = ?');
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
    return $errors;
}
