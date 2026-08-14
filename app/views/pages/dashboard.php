<?php
require_once dirname(__DIR__, 3) . '/config/url_config.php';
header("Location: " . BASE_URL . "?module=dashboard", true, 301);
exit();
