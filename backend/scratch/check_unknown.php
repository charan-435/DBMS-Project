<?php
require_once 'Database.php';
$conn = Database::getConnection();
$stmt = $conn->query("SELECT genre_name FROM Genres WHERE genre_name LIKE '%Unknown%'");
$genres = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Unknown Genres: " . implode(', ', $genres) . "\n";

$stmt = $conn->query("SELECT first_name, last_name FROM Directors WHERE first_name LIKE '%Unknown%' OR last_name LIKE '%Unknown%' LIMIT 5");
$directors = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Unknown Directors: " . count($directors) . "\n";

$stmt = $conn->query("SELECT first_name, last_name FROM Actors WHERE first_name LIKE '%Unknown%' OR last_name LIKE '%Unknown%' LIMIT 5");
$actors = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Unknown Actors: " . count($actors) . "\n";
?>
