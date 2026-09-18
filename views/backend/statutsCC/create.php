<?php
require_once dirname(__DIR__, 3) . '/config.php';
cooker_require_admin();
cooker_redirect('index.php?page=admin&section=users');
