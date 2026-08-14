<?php
require_once dirname(__DIR__, 2) . '/config/database.php';

abstract class BaseModel {
    protected PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }
}
