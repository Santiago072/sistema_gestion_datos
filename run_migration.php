<?php
require 'config/database.php';
$db = getDB();
$sql = file_get_contents('migrations/create_jobs_table.sql');
$db->exec($sql);
echo "Table created\n";
