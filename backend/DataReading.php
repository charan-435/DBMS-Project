<?php
set_time_limit(0);
require_once 'Database.php';

$db = Database::getConnection();

// 1. Clear out the old tables
$db->exec("SET FOREIGN_KEY_CHECKS = 0");
$db->exec("DROP TABLE IF EXISTS Movie_Actors, Movies, Actors, Directors, Genres");
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

// 2. Create the tables (Now INCLUDING language and rating_imdb)
$db->exec("
    CREATE TABLE Genres (
        genre_id INT PRIMARY KEY AUTO_INCREMENT,
        genre_name VARCHAR(50) NOT NULL UNIQUE
    )
");

$db->exec("
    CREATE TABLE Directors (
        director_id INT PRIMARY KEY AUTO_INCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50)
    )
");

$db->exec("
    CREATE TABLE Actors (
        actor_id INT PRIMARY KEY AUTO_INCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50)
    )
");

$db->exec("
    CREATE TABLE Movies (
        movie_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        release_year SMALLINT NOT NULL,
        budget DECIMAL(15,2),
        language VARCHAR(20),       /* ADDED COLUMN */
        rating_imdb DECIMAL(3,1),   /* ADDED COLUMN */
        director_id INT NOT NULL,
        genre_id INT NOT NULL,
        FOREIGN KEY (director_id) REFERENCES Directors(director_id),
        FOREIGN KEY (genre_id) REFERENCES Genres(genre_id)
    )
");

$db->exec("
    CREATE TABLE Movie_Actors (
        movie_id INT NOT NULL,
        actor_id INT NOT NULL,
        PRIMARY KEY (movie_id, actor_id),
        FOREIGN KEY (movie_id) REFERENCES Movies(movie_id) ON DELETE CASCADE,
        FOREIGN KEY (actor_id) REFERENCES Actors(actor_id) ON DELETE CASCADE
    )
");

// 3. Prepare SQL Statements
$stmtGenre = $db->prepare("INSERT IGNORE INTO Genres (genre_name) VALUES (?)");
$stmtGetGenre = $db->prepare("SELECT genre_id FROM Genres WHERE genre_name = ?");

$stmtDir = $db->prepare("INSERT INTO Directors (first_name, last_name) VALUES (?, ?)");
$stmtGetDir = $db->prepare("SELECT director_id FROM Directors WHERE first_name = ? AND last_name = ?");

$stmtActor = $db->prepare("INSERT INTO Actors (first_name, last_name) VALUES (?, ?)");
$stmtGetActor = $db->prepare("SELECT actor_id FROM Actors WHERE first_name = ? AND last_name = ?");

// Updated to insert language and rating
$stmtMovie = $db->prepare("INSERT INTO Movies (title, release_year, budget, language, rating_imdb, director_id, genre_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtMovieActor = $db->prepare("INSERT IGNORE INTO Movie_Actors (movie_id, actor_id) VALUES (?, ?)");

// 4. Read the CSV and populate
$file = fopen('./data/add_revenue.csv', 'r');
if ($file !== false) {
    fgetcsv($file); // Skip the header row

    $db->beginTransaction();

    while (($data = fgetcsv($file)) !== false) {
        $title = $data[2];
        $release_date = $data[3];
        $language = $data[4];         // Extracting language
        $rating_imdb = $data[6];      // Extracting rating
        $revenue = $data[8];
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
    echo "<h2 style='color:green;'>✅ Database recreated and populated with missing columns!</h2>";
} else {
    echo "Failed to open add_revenue.csv.";
}
?>
