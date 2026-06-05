<?php
require_once __DIR__ . '/../config/database.php';

abstract class BaseModel {
    protected PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }
}
