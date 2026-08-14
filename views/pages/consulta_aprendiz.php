<?php
require_once dirname(__DIR__, 2) . '/config/url_config.php';
header("Location: " . BASE_URL . "?module=consulta", true, 301);
exit();
