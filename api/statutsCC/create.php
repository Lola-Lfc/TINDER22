<?php
require_once dirname(__DIR__, 2) . '/config.php';
cooker_require_admin();
http_response_code(404);
exit('La table des statuts n’existe pas dans la base Cooker.');
