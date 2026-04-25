<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
});

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    ob_clean();
    echo json_encode([]);
    exit;
}

// ─── Inline DB ──────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=cinematic_lens_db;charset=utf8",
        'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([]);
    exit;
}

$type = $_GET['type'] ?? 'all';
$q    = "%$query%";
$results = [];

// Movies
if ($type === 'all' || $type === 'movies') {
    $s = $pdo->prepare("SELECT movie_id AS id, title AS name, release_year AS meta, 'movie' AS type
                         FROM Movies WHERE title LIKE :q LIMIT 6");
    $s->bindValue(':q', $q);
    $s->execute();
    $results = array_merge($results, $s->fetchAll(PDO::FETCH_ASSOC));
}

// Directors
if ($type === 'all' || $type === 'directors') {
    $s = $pdo->prepare("SELECT director_id AS id, CONCAT(first_name,' ',last_name) AS name, '' AS meta, 'director' AS type
                         FROM Directors
                         WHERE (first_name LIKE :d1 OR last_name LIKE :d2 OR CONCAT(first_name,' ',last_name) LIKE :d3)
                           AND first_name NOT LIKE '%Unknown%'
                         LIMIT 4");
    $s->bindValue(':d1', $q);
    $s->bindValue(':d2', $q);
    $s->bindValue(':d3', $q);
    $s->execute();
    $results = array_merge($results, $s->fetchAll(PDO::FETCH_ASSOC));
}

// Actors
if ($type === 'all' || $type === 'actors') {
    $s = $pdo->prepare("SELECT actor_id AS id, CONCAT(first_name,' ',last_name) AS name, '' AS meta, 'actor' AS type
                         FROM Actors
                         WHERE (first_name LIKE :a1 OR last_name LIKE :a2 OR CONCAT(first_name,' ',last_name) LIKE :a3)
                           AND first_name NOT LIKE '%Unknown%'
                         LIMIT 4");
    $s->bindValue(':a1', $q);
    $s->bindValue(':a2', $q);
    $s->bindValue(':a3', $q);
    $s->execute();
    $results = array_merge($results, $s->fetchAll(PDO::FETCH_ASSOC));
}

ob_clean();
echo json_encode($results);
