<?php

require_once __DIR__ . '/Database.php';

class DataService {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getAvgRating() {
        if (!$this->conn) return 0;
        try {
            $stmt = $this->conn->query("SELECT AVG(rating_imdb) as avg_rating FROM Movies WHERE rating_imdb > 0");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($row['avg_rating'] ?? 0, 2);
        } catch(PDOException $e) { return 0; }
    }

    public function getTotalRevenue() {
        if (!$this->conn) return 0;
        try {
            $stmt = $this->conn->query("SELECT SUM(revenue) as total FROM Movies WHERE revenue > 0");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch(PDOException $e) { return 0; }
    }

    public function getMostActiveGenre() {
        if (!$this->conn) return ['genre' => 'Unknown', 'count' => 0];
        try {
            $stmt = $this->conn->query("
                SELECT g.genre_name as genre, COUNT(m.movie_id) as cnt
                FROM Movies m
                JOIN Genres g ON m.genre_id = g.genre_id
                GROUP BY g.genre_id 
                ORDER BY cnt DESC LIMIT 1
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['genre' => $row['genre'] ?? 'Unknown', 'count' => $row['cnt'] ?? 0];
        } catch(PDOException $e) { return ['genre' => 'Unknown', 'count' => 0]; }
    }

    public function getTotalMovies() {
        if (!$this->conn) return 0;
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as cnt FROM Movies");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['cnt'] ?? 0;
        } catch(PDOException $e) { return 0; }
    }

    public function getGenreTrend() {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("
                SELECT m.release_year as yr,
                    SUM(CASE WHEN g.genre_name LIKE '%Action%' THEN 1 ELSE 0 END) as action_count,
                    SUM(CASE WHEN g.genre_name LIKE '%Romance%' THEN 1 ELSE 0 END) as romance_count
                FROM Movies m
                JOIN Genres g ON m.genre_id = g.genre_id
                GROUP BY yr ORDER BY yr ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getTrendingMovies($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT m.movie_id, m.title, CONCAT(d.first_name, ' ', d.last_name) as director, 
                       g.genre_name as genres, m.rating_imdb, m.language, 
                       m.release_year as yr, m.revenue as revenue
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE m.rating_imdb > 0 
                ORDER BY m.rating_imdb DESC, m.revenue DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getTopDirectors($limit = 10) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT CONCAT(d.first_name, ' ', d.last_name) as director, COUNT(m.movie_id) as movie_count, 
                       AVG(m.rating_imdb) as avg_rating, SUM(m.revenue) as total_revenue
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                WHERE m.rating_imdb > 0
                GROUP BY d.director_id 
                HAVING COUNT(m.movie_id) >= 2
                ORDER BY avg_rating DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getDirectorFilms($director, $limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT m.title, m.rating_imdb, g.genre_name as genres, m.release_year as yr, m.revenue as revenue
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE CONCAT(d.first_name, ' ', d.last_name) = :dir AND m.rating_imdb > 0
                ORDER BY m.rating_imdb DESC LIMIT :limit
            ");
            $stmt->bindValue(':dir', $director);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getDirectorCollaborators($director, $limit = 4) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT CONCAT(a.first_name, ' ', a.last_name) as name, COUNT(ma.movie_id) as films
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Movie_Actors ma ON m.movie_id = ma.movie_id
                JOIN Actors a ON ma.actor_id = a.actor_id
                WHERE CONCAT(d.first_name, ' ', d.last_name) = :dir
                GROUP BY a.actor_id
                ORDER BY films DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':dir', $director);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getGenreStats($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT g.genre_name as primary_genre,
                       SUM(m.revenue) as total_revenue, COUNT(m.movie_id) as movie_count,
                       AVG(m.rating_imdb) as avg_rating
                FROM Movies m
                JOIN Genres g ON m.genre_id = g.genre_id
                GROUP BY g.genre_id 
                ORDER BY total_revenue DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getLanguageStats($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT language, COUNT(movie_id) as movie_count, SUM(revenue) as total_revenue,
                       AVG(rating_imdb) as avg_rating
                FROM Movies WHERE language IS NOT NULL
                GROUP BY language ORDER BY movie_count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getTopGrossingMovies($limit = 10) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT m.title, CONCAT(d.first_name, ' ', d.last_name) as director, 
                       m.revenue as revenue, m.release_year as release_date, 
                       m.rating_imdb, g.genre_name as genres, m.language
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE m.revenue > 0
                ORDER BY m.revenue DESC LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getActorDirectorCollaborations($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT CONCAT(d.first_name, ' ', d.last_name) as director, 
                       CONCAT(a.first_name, ' ', a.last_name) as actor, 
                       COUNT(m.movie_id) as count, 
                       SUM(m.revenue) as revenue, 
                       AVG(m.rating_imdb) as avg_rating
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Movie_Actors ma ON m.movie_id = ma.movie_id
                JOIN Actors a ON ma.actor_id = a.actor_id
                WHERE d.first_name NOT LIKE '%Unknown%' 
                  AND a.first_name NOT LIKE '%Unknown%'
                GROUP BY d.director_id, a.actor_id
                ORDER BY count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$row) {
                $row['avg_revenue'] = $row['count'] > 0 ? $row['revenue'] / $row['count'] : 0;
            }
            return $results;
        } catch(PDOException $e) { return []; }
    }

    public function getLanguageRevenueAverages($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT language, AVG(revenue) as avg_revenue, COUNT(movie_id) as movie_count 
                FROM Movies 
                WHERE language IS NOT NULL AND revenue > 0
                GROUP BY language 
                HAVING COUNT(movie_id) >= 5
                ORDER BY avg_revenue DESC 
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    // getRuntimeVsRating() removed — the runtime_minutes column does not exist in the schema.

    public function getHighGrossingGenres($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT g.genre_name as primary_genre, COUNT(m.movie_id) as club_count
                FROM Movies m
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE m.revenue >= 100000000 
                GROUP BY g.genre_id
                ORDER BY club_count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getTopActors($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT CONCAT(a.first_name, ' ', a.last_name) as name, COUNT(ma.movie_id) as count
                FROM Actors a
                JOIN Movie_Actors ma ON a.actor_id = ma.actor_id
                WHERE a.first_name NOT LIKE '%Unknown%'
                GROUP BY a.actor_id
                ORDER BY count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getDecadeRatings() {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("
                SELECT FLOOR(release_year / 10) * 10 as decade, 
                       AVG(rating_imdb) as avg_rating,
                       COUNT(movie_id) as movie_count
                FROM Movies 
                WHERE release_year IS NOT NULL AND rating_imdb > 0
                GROUP BY decade 
                HAVING decade > 1900
                ORDER BY avg_rating DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getRatingRevenueCorrelation() {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("
                SELECT 
                    CASE 
                        WHEN rating_imdb < 5.0 THEN 'Flop (< 5.0)'
                        WHEN rating_imdb BETWEEN 5.0 AND 7.9 THEN 'Average (5.0 - 7.9)'
                        WHEN rating_imdb >= 8.0 THEN 'Masterpiece (>= 8.0)'
                    END as rating_category,
                    AVG(revenue) as avg_revenue,
                    COUNT(movie_id) as movie_count
                FROM Movies
                WHERE rating_imdb > 0 AND revenue > 0
                GROUP BY rating_category
                ORDER BY avg_revenue DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getGoldenYear() {
        if (!$this->conn) return current([]);
        try {
            $stmt = $this->conn->query("
                SELECT release_year as yr, SUM(revenue) as total_revenue, COUNT(movie_id) as movie_count
                FROM Movies
                WHERE release_year IS NOT NULL AND revenue > 0
                GROUP BY yr
                ORDER BY total_revenue DESC
                LIMIT 1
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch(PDOException $e) { return []; }
    }

    // ── CRUD OPERATIONS ──────────────────────────────────────

    // Get all genres for dropdowns
    public function getAllGenres() {
        if (!$this->conn) return [];
        try {
            return $this->conn->query("SELECT genre_id, genre_name FROM Genres ORDER BY genre_name")->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    // Get all directors for dropdowns
    public function getAllDirectors() {
        if (!$this->conn) return [];
        try {
            return $this->conn->query("SELECT director_id, CONCAT(first_name, ' ', last_name) as name FROM Directors ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    // Get a single movie by ID
    public function getMovieById($id) {
        if (!$this->conn) return null;
        try {
            $stmt = $this->conn->prepare("
                SELECT m.*, CONCAT(d.first_name,' ',d.last_name) as director_name, g.genre_name
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE m.movie_id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return null; }
    }

    // Insert a new movie
    public function insertMovie($title, $year, $revenue, $language, $rating, $director_id, $genre_id) {
        if (!$this->conn) return false;
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO Movies (title, release_year, budget, language, rating_imdb, director_id, genre_id)
                VALUES (:title, :year, :revenue, :lang, :rating, :did, :gid)
            ");
            return $stmt->execute([
                'title' => $title, 'year' => $year, 'revenue' => $revenue,
                'lang' => $language, 'rating' => $rating, 'did' => $director_id, 'gid' => $genre_id
            ]);
        } catch(PDOException $e) { return false; }
    }

    // Update an existing movie
    public function updateMovie($id, $title, $year, $revenue, $language, $rating, $director_id, $genre_id) {
        if (!$this->conn) return false;
        try {
            $stmt = $this->conn->prepare("
                UPDATE Movies SET title=:title, release_year=:year, budget=:revenue, language=:lang,
                    rating_imdb=:rating, director_id=:did, genre_id=:gid
                WHERE movie_id = :id
            ");
            return $stmt->execute([
                'id' => $id, 'title' => $title, 'year' => $year, 'revenue' => $revenue,
                'lang' => $language, 'rating' => $rating, 'did' => $director_id, 'gid' => $genre_id
            ]);
        } catch(PDOException $e) { return false; }
    }

    // Delete a movie
    public function deleteMovie($id) {
        if (!$this->conn) return false;
        try {
            $stmt = $this->conn->prepare("DELETE FROM Movies WHERE movie_id = :id");
            return $stmt->execute(['id' => $id]);
        } catch(PDOException $e) { return false; }
    }

    // Insert a new genre
    public function insertGenre($name) {
        if (!$this->conn) return false;
        try {
            $stmt = $this->conn->prepare("INSERT INTO Genres (genre_name) VALUES (:name)");
            return $stmt->execute(['name' => $name]);
        } catch(PDOException $e) { return false; }
    }

    // Insert a new director
    public function insertDirector($first, $last) {
        if (!$this->conn) return false;
        try {
            $stmt = $this->conn->prepare("INSERT INTO Directors (first_name, last_name) VALUES (:f, :l)");
            return $stmt->execute(['f' => $first, 'l' => $last]);
        } catch(PDOException $e) { return false; }
    }

    // ── ADVANCED SEARCH WITH PAGINATION ──────────────────────

    public function searchMovies($query, $genre, $language, $minYear, $maxYear, $minRating, $sortBy, $page, $perPage) {
        if (!$this->conn) return ['results' => [], 'total' => 0];
        try {
            $where = ['1=1'];
            $params = [];

            if (!empty($query)) {
                $where[] = "m.title LIKE :q";
                $params['q'] = '%' . $query . '%';
            }
            if (!empty($genre)) {
                $where[] = "g.genre_name = :genre";
                $params['genre'] = $genre;
            }
            if (!empty($language)) {
                $where[] = "m.language = :lang";
                $params['lang'] = $language;
            }
            if (!empty($minYear)) {
                $where[] = "m.release_year >= :minY";
                $params['minY'] = $minYear;
            }
            if (!empty($maxYear)) {
                $where[] = "m.release_year <= :maxY";
                $params['maxY'] = $maxYear;
            }
            if (!empty($minRating)) {
                $where[] = "m.rating_imdb >= :minR";
                $params['minR'] = $minRating;
            }

            $orderMap = [
                'rating' => 'm.rating_imdb DESC',
                'revenue' => 'm.revenue DESC',
                'year' => 'm.release_year DESC',
                'title' => 'm.title ASC',
            ];
            $order = $orderMap[$sortBy] ?? 'm.rating_imdb DESC';

            $whereClause = implode(' AND ', $where);
            $offset = ($page - 1) * $perPage;

            // Count total
            $countSql = "SELECT COUNT(*) as total FROM Movies m JOIN Genres g ON m.genre_id = g.genre_id WHERE $whereClause";
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Fetch page
            $sql = "
                SELECT m.movie_id, m.title, m.release_year, m.revenue as revenue, m.language, m.rating_imdb,
                    CONCAT(d.first_name, ' ', d.last_name) as director_name, g.genre_name
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE $whereClause
                ORDER BY $order
                LIMIT $perPage OFFSET $offset
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return ['results' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => (int)$total];
        } catch(PDOException $e) { return ['results' => [], 'total' => 0]; }
    }

    // Get distinct languages for filter dropdown
    public function getDistinctLanguages() {
        if (!$this->conn) return [];
        try {
            return $this->conn->query("
                SELECT DISTINCT language FROM Movies WHERE language IS NOT NULL ORDER BY language
            ")->fetchAll(PDO::FETCH_COLUMN);
        } catch(PDOException $e) { return []; }
    }

    // ── COLLABORATION-SPECIFIC ───────────────────────────────

    public function getActorGenreVersatility($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT CONCAT(a.first_name, ' ', a.last_name) as actor,
                    COUNT(DISTINCT g.genre_id) as genres_count,
                    COUNT(ma.movie_id) as total_films,
                    GROUP_CONCAT(DISTINCT g.genre_name ORDER BY g.genre_name SEPARATOR ', ') as genres
                FROM Actors a
                JOIN Movie_Actors ma ON a.actor_id = ma.actor_id
                JOIN Movies m ON ma.movie_id = m.movie_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE a.first_name NOT LIKE '%Unknown%'
                GROUP BY a.actor_id
                HAVING COUNT(DISTINCT g.genre_id) >= 2
                ORDER BY genres_count DESC, total_films DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getRepeatCollaborators($minFilms = 3, $limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    CONCAT(a1.first_name,' ',a1.last_name) as actor1,
                    CONCAT(a2.first_name,' ',a2.last_name) as actor2,
                    COUNT(*) as films_together
                FROM Movie_Actors ma1
                JOIN Movie_Actors ma2 ON ma1.movie_id = ma2.movie_id AND ma1.actor_id < ma2.actor_id
                JOIN Actors a1 ON ma1.actor_id = a1.actor_id
                JOIN Actors a2 ON ma2.actor_id = a2.actor_id
                WHERE a1.first_name NOT LIKE '%Unknown%' AND a2.first_name NOT LIKE '%Unknown%'
                GROUP BY ma1.actor_id, ma2.actor_id
                HAVING COUNT(*) >= :minFilms
                ORDER BY films_together DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':minFilms', (int)$minFilms, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }
    // ── AUTHENTICATION ─────────────────────────────────────────
    
    public function signup($name, $userId, $password) {
        if (!$this->conn) return false;
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO Users (full_name, user_id, password) VALUES (:name, :uid, :pwd)");
            return $stmt->execute([
                'name' => $name,
                'uid' => $userId,
                'pwd' => $hashed
            ]);
        } catch(PDOException $e) { return false; }
    }

    public function login($userId, $password) {
        if (!$this->conn) return null;
        try {
            $stmt = $this->conn->prepare("SELECT * FROM Users WHERE user_id = :uid");
            $stmt->execute(['uid' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            return null;
        } catch(PDOException $e) { return null; }
    }
}
?>
