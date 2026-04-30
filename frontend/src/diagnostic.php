<?php
require_once __DIR__ . '/../../backend/Database.php';
$db = Database::getConnection();
$res = $db->query("SELECT language, COUNT(*) as cnt FROM Movies GROUP BY language")->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($res);
