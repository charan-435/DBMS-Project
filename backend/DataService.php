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
            $stmt = $this->conn->query("SELECT SUM(budget) as total FROM Movies WHERE budget > 0");
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
                SELECT m.title, CONCAT(d.first_name, ' ', d.last_name) as director, 
                       g.genre_name as genres, m.rating_imdb, m.language, 
                       m.release_year as yr, m.budget as revenue
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE m.rating_imdb > 0 
                ORDER BY m.rating_imdb DESC, m.budget DESC
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
                       AVG(m.rating_imdb) as avg_rating, SUM(m.budget) as total_revenue
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
                SELECT m.title, m.rating_imdb, g.genre_name as genres, m.release_year as yr, m.budget as revenue
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
                       SUM(m.budget) as total_revenue, COUNT(m.movie_id) as movie_count,
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
                SELECT language, COUNT(movie_id) as movie_count, SUM(budget) as total_revenue,
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
                       m.budget as revenue, m.release_year as release_date, 
                       m.rating_imdb, g.genre_name as genres, m.language
                FROM Movies m
                JOIN Directors d ON m.director_id = d.director_id
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE m.budget > 0
                ORDER BY m.budget DESC LIMIT :limit
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
                       SUM(m.budget) as revenue, 
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
                SELECT language, AVG(budget) as avg_revenue, COUNT(movie_id) as movie_count 
                FROM Movies 
                WHERE language IS NOT NULL AND budget > 0
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

    public function getRuntimeVsRating() {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("
                SELECT 
                    CASE 
                        WHEN runtime_minutes < 90 THEN '< 90 mins'
                        WHEN runtime_minutes BETWEEN 90 AND 120 THEN '90 - 120 mins'
                        WHEN runtime_minutes BETWEEN 121 AND 150 THEN '121 - 150 mins'
                        ELSE '> 150 mins'
                    END as runtime_category,
                    AVG(rating_imdb) as avg_rating,
                    COUNT(movie_id) as movie_count
                FROM Movies
                WHERE runtime_minutes > 0 AND rating_imdb > 0
                GROUP BY runtime_category
                ORDER BY avg_rating DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    public function getHighGrossingGenres($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT g.genre_name as primary_genre, COUNT(m.movie_id) as club_count
                FROM Movies m
                JOIN Genres g ON m.genre_id = g.genre_id
                WHERE m.budget >= 100000000 
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
                    AVG(budget) as avg_revenue,
                    COUNT(movie_id) as movie_count
                FROM Movies
                WHERE rating_imdb > 0 AND budget > 0
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
                SELECT release_year as yr, SUM(budget) as total_revenue, COUNT(movie_id) as movie_count
                FROM Movies
                WHERE release_year IS NOT NULL AND budget > 0
                GROUP BY yr
                ORDER BY total_revenue DESC
                LIMIT 1
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch(PDOException $e) { return []; }
    }
}
?>
