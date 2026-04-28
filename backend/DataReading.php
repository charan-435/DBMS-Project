<?php
set_time_limit(0);
require_once 'Database.php';

$db = Database::getConnection();

// 1. Clear out old data but KEEP the schema, views, and triggers intact
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

try {
    $db->exec("TRUNCATE TABLE Movie_Actors");
    $db->exec("TRUNCATE TABLE Movies");
    $db->exec("TRUNCATE TABLE Actors");
    $db->exec("TRUNCATE TABLE Directors");
    $db->exec("TRUNCATE TABLE Genres");
    $db->exec("TRUNCATE TABLE Users");
} catch (PDOException $e) {
    die("<h3>Database Error: Tables are missing!</h3>
         <p>You need to import the new schema first.</p>
         <p>Please open <a href='http://localhost/phpmyadmin'>phpMyAdmin</a>, select the <b>cinematic_lens_db</b> database, and import <b>backend/DB_Schema.sql</b>.</p>
         <p>After importing, come back here and refresh.</p>");
}

$db->exec("SET FOREIGN_KEY_CHECKS = 1");

// The table structures, views, and procedures are now managed by DB_Schema.sql.
// We no longer DROP and CREATE tables here to prevent schema drift.


// 3. Prepare SQL Statements
$stmtGenre = $db->prepare("INSERT IGNORE INTO Genres (genre_name) VALUES (?)");
$stmtGetGenre = $db->prepare("SELECT genre_id FROM Genres WHERE genre_name = ?");

$stmtDir = $db->prepare("INSERT INTO Directors (first_name, last_name) VALUES (?, ?)");
$stmtGetDir = $db->prepare("SELECT director_id FROM Directors WHERE first_name = ? AND last_name = ?");

$stmtActor = $db->prepare("INSERT INTO Actors (first_name, last_name) VALUES (?, ?)");
$stmtGetActor = $db->prepare("SELECT actor_id FROM Actors WHERE first_name = ? AND last_name = ?");

// Updated to insert language and rating
$stmtMovie = $db->prepare("
    INSERT INTO Movies (title, release_year, revenue, language, rating_imdb, director_id, genre_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmtMovieActor = $db->prepare("INSERT IGNORE INTO Movie_Actors (movie_id, actor_id) VALUES (?, ?)");

// 4. Read the CSV and populate
$file = fopen(__DIR__ . "/data/add_revenue.csv", "r");
if ($file !== false) {
    fgetcsv($file); // Skip the header row

    $db->beginTransaction();

    while (($data = fgetcsv($file)) !== false) {
        $title = $data[2];
        $release_date = $data[3];
        $language = $data[4];         // Extracting language
        $rating_imdb = $data[6];      // Extracting rating
        $revenue = floatval($data[8]) * 100; // Multiply by 100 as requested
        $director_full = $data[9];
        $cast_full = $data[10];
        $genre_name = $data[11];

        $release_year = (int)substr($release_date, 0, 4);

        // Process Genre
        $stmtGenre->execute([$genre_name]);
        $stmtGetGenre->execute([$genre_name]);
        $genre_id = $stmtGetGenre->fetchColumn();

        // Process Director
        $dir_parts = explode(' ', trim($director_full), 2);
        $dir_first = $dir_parts[0];
        $dir_last = isset($dir_parts[1]) ? $dir_parts[1] : '';

        $stmtGetDir->execute([$dir_first, $dir_last]);
        $director_id = $stmtGetDir->fetchColumn();
        if (!$director_id) {
            $stmtDir->execute([$dir_first, $dir_last]);
            $director_id = $db->lastInsertId();
        }

        // Process Movie (Now with language and rating!)
        $stmtMovie->execute([$title, $release_year, $revenue, $language, $rating_imdb, $director_id, $genre_id]);
        $movie_id = $db->lastInsertId();

        // Process Actors
        $actors = explode(',', $cast_full);
        foreach ($actors as $actor) {
            $actor = trim($actor);
            if (empty($actor)) continue;

            $act_parts = explode(' ', $actor, 2);
            $act_first = $act_parts[0];
            $act_last = isset($act_parts[1]) ? $act_parts[1] : '';

            $stmtGetActor->execute([$act_first, $act_last]);
            $actor_id = $stmtGetActor->fetchColumn();
            
            if (!$actor_id) {
                $stmtActor->execute([$act_first, $act_last]);
                $actor_id = $db->lastInsertId();
            }

            $stmtMovieActor->execute([$movie_id, $actor_id]);
        }
    }

    $db->commit();
    fclose($file);

    // --- IMPLICIT DATA CLEANING ---
    // Fill movies having 0 revenue with the average revenue of non-zero films
    try {
        $avgRes = $db->query("SELECT AVG(revenue) as avg_rev FROM Movies WHERE revenue > 0")->fetch(PDO::FETCH_ASSOC);
        $avgVal = $avgRes['avg_rev'] ?? 0;
        if ($avgVal > 0) {
            $db->prepare("UPDATE Movies SET revenue = ? WHERE revenue = 0")->execute([$avgVal]);
        }
    } catch (PDOException $e) {
        // Silently skip if cleaning fails
    }

    // Populate Default User
    $adminPwd = password_hash('password', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO Users (full_name, user_id, password) VALUES ('Administrator', 'admin', ?)")
       ->execute([$adminPwd]);

    echo "<h2 style='color:green;'>✅ Database recreated and populated!</h2>";
    echo "<p>Default User: <strong>admin</strong> | Password: <strong>password</strong></p>";
} else {
    echo "Failed to open add_revenue.csv.";
}
?>
