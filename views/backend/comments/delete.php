<?php
require_once dirname(__DIR__, 3) . '/config.php';
cooker_require_admin();
http_response_code(404);
exit('Ce panneau a été retiré de l’administration.');
