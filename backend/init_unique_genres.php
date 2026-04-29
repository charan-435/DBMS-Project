<?php
require_once __DIR__ . '/DataService.php';
$db = Database::getConnection();

echo "Starting DB normalization for Single Genres...\n";

// 1. Create tables
$db->exec("
    CREATE TABLE IF NOT EXISTS Unique_Genres (
        single_genre_id INT AUTO_INCREMENT PRIMARY KEY,
        genre_name VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS Movie_Unique_Genres (
        movie_id INT NOT NULL,
        single_genre_id INT NOT NULL,
        PRIMARY KEY (movie_id, single_genre_id),
        FOREIGN KEY (movie_id) REFERENCES Movies(movie_id) ON DELETE CASCADE,
        FOREIGN KEY (single_genre_id) REFERENCES Unique_Genres(single_genre_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Clear existing data in case script is re-run
$db->exec("TRUNCATE TABLE Movie_Unique_Genres;");
$db->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE Unique_Genres; SET FOREIGN_KEY_CHECKS = 1;");

// 2. Fetch movies and their genres
$stmt = $db->query("
    SELECT m.movie_id, g.genre_name
    FROM Movies m
    JOIN Genres g ON m.genre_id = g.genre_id
    WHERE g.genre_name IS NOT NULL AND g.genre_name != 'Unknown'
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Collect unique genres
$uniqueGenresSet = [];
foreach ($movies as $movie) {
    if (!$movie['genre_name']) continue;
    $parts = explode(',', $movie['genre_name']);
    foreach ($parts as $p) {
        $clean = trim($p);
        if ($clean && !isset($uniqueGenresSet[$clean])) {
            $uniqueGenresSet[$clean] = true;
        }
    }
}

// Insert into Unique_Genres and map IDs
$uniqueGenreIds = [];
$insertUG = $db->prepare("INSERT INTO Unique_Genres (genre_name) VALUES (:name)");
foreach (array_keys($uniqueGenresSet) as $gen) {
    $insertUG->execute(['name' => $gen]);
    $uniqueGenreIds[$gen] = $db->lastInsertId();
}

echo "Created " . count($uniqueGenreIds) . " unique genres.\n";

// 3. Map movies to unique genres
$insertMUG = $db->prepare("INSERT IGNORE INTO Movie_Unique_Genres (movie_id, single_genre_id) VALUES (:mid, :gid)");
$mapped = 0;
foreach ($movies as $movie) {
    if (!$movie['genre_name']) continue;
    $parts = explode(',', $movie['genre_name']);
    foreach ($parts as $p) {
        $clean = trim($p);
        if ($clean && isset($uniqueGenreIds[$clean])) {
            $insertMUG->execute([
                'mid' => $movie['movie_id'],
                'gid' => $uniqueGenreIds[$clean]
            ]);
            $mapped++;
        }
    }
}

echo "Mapped $mapped movie-genre pairs.\n";
echo "Done!\n";
