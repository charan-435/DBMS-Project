<?php
require_once __DIR__ . '/../../backend/DataService.php';
$db = Database::getConnection();
$stmt = $db->query("SHOW CREATE TABLE Movies");
$movies = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->query("SHOW CREATE TABLE Genres");
$genres = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Movies:\n" . $movies['Create Table'] . "\n\n";
echo "Genres:\n" . $genres['Create Table'] . "\n\n";
