<?php
//PDO connection
function sql_connect(){
    global $DB;

    //connect BDD with PDO using SQL_HOST, SQL_USER, SQL_PWD, SQL_DB
    // Avec encodage UTF8
    $port = getenv('DB_PORT');
    if ($port !== false && $port !== '' && (!ctype_digit($port) || (int)$port < 1 || (int)$port > 65535)) {
        throw new RuntimeException('DB_PORT doit être un port MySQL valide.');
    }
    $dsn = 'mysql:host=' . SQL_HOST . ($port !== false && $port !== '' ? ';port=' . $port : '') . ';charset=utf8;dbname=' . SQL_DB;
    $DB = new PDO($dsn, SQL_USER, SQL_PWD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
?>