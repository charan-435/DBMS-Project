<?php

require_once __DIR__ . '/Database.php';

class DataService {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    /** Overall average IMDB rating */
    public function getAvgRating() {
        if (!$this->conn) return 0;
        try {
            $stmt = $this->conn->query("SELECT AVG(rating_imdb) as avg_rating FROM movies WHERE rating_imdb > 0");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($row['avg_rating'] ?? 0, 2);
        } catch(PDOException $e) { return 0; }
    }

    /** Total box office revenue */
    public function getTotalRevenue() {
        if (!$this->conn) return 0;
        try {
            $stmt = $this->conn->query("SELECT SUM(revenue) as total FROM movies WHERE revenue > 0");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['total'] ?? 0;
        } catch(PDOException $e) { return 0; }
    }

    /** Most active genre by movie count */
    public function getMostActiveGenre() {
        if (!$this->conn) return ['genre' => 'Unknown', 'count' => 0];
        try {
            $stmt = $this->conn->query("
                SELECT TRIM(SUBSTRING_INDEX(genres, ',', 1)) as genre, COUNT(*) as cnt
                FROM movies WHERE genres IS NOT NULL AND genres != 'Unknown'
                GROUP BY genre ORDER BY cnt DESC LIMIT 1
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['genre' => $row['genre'] ?? 'Unknown', 'count' => $row['cnt'] ?? 0];
        } catch(PDOException $e) { return ['genre' => 'Unknown', 'count' => 0]; }
    }

    /** Total movie count */
    public function getTotalMovies() {
        if (!$this->conn) return 0;
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as cnt FROM movies");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['cnt'] ?? 0;
        } catch(PDOException $e) { return 0; }
    }

    /** Yearly genre trend for Action vs Romance */
    public function getGenreTrend() {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("
                SELECT YEAR(release_date) as yr,
                    SUM(CASE WHEN genres LIKE '%Action%' THEN 1 ELSE 0 END) as action_count,
                    SUM(CASE WHEN genres LIKE '%Romance%' THEN 1 ELSE 0 END) as romance_count
                FROM movies
                WHERE release_date IS NOT NULL
                GROUP BY yr ORDER BY yr ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Trending movies - top rated recent films */
    public function getTrendingMovies($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT title, director, genres, rating_imdb, language, 
                       YEAR(release_date) as yr, revenue
                FROM movies
                WHERE rating_imdb > 0 AND director != 'Unknown' AND title IS NOT NULL
                ORDER BY rating_imdb DESC, revenue DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Top directors by average rating (with min 2 films) */
    public function getTopDirectors($limit = 10) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT director, COUNT(*) as movie_count, 
                       AVG(rating_imdb) as avg_rating, SUM(revenue) as total_revenue
                FROM movies
                WHERE director != 'Unknown' AND director IS NOT NULL AND rating_imdb > 0
                GROUP BY director HAVING COUNT(*) >= 2
                ORDER BY avg_rating DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Top films by a specific director */
    public function getDirectorFilms($director, $limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT title, rating_imdb, genres, YEAR(release_date) as yr, revenue
                FROM movies WHERE director = :dir AND rating_imdb > 0
                ORDER BY rating_imdb DESC LIMIT :limit
            ");
            $stmt->bindValue(':dir', $director);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Frequent collaborators (cast) for a director */
    public function getDirectorCollaborators($director, $limit = 4) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT cast FROM movies
                WHERE director = :dir AND cast != 'Unknown' AND cast IS NOT NULL
            ");
            $stmt->bindValue(':dir', $director);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $actors = [];
            foreach ($rows as $row) {
                $castList = explode(',', $row['cast']);
                foreach ($castList as $actor) {
                    $actor = trim($actor);
                    if ($actor && $actor !== 'Unknown') {
                        $actors[$actor] = ($actors[$actor] ?? 0) + 1;
                    }
                }
            }
            arsort($actors);
            $result = [];
            foreach (array_slice($actors, 0, $limit, true) as $name => $count) {
                $result[] = ['name' => $name, 'films' => $count];
            }
            return $result;
        } catch(PDOException $e) { return []; }
    }

    /** Genre stats with revenue and count */
    public function getGenreStats($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT TRIM(SUBSTRING_INDEX(genres, ',', 1)) as primary_genre,
                       SUM(revenue) as total_revenue, COUNT(*) as movie_count,
                       AVG(rating_imdb) as avg_rating
                FROM movies
                WHERE genres != 'Unknown' AND genres IS NOT NULL
                GROUP BY primary_genre ORDER BY total_revenue DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Language distribution */
    public function getLanguageStats($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT language, COUNT(*) as movie_count, SUM(revenue) as total_revenue,
                       AVG(rating_imdb) as avg_rating
                FROM movies WHERE language IS NOT NULL
                GROUP BY language ORDER BY movie_count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Top grossing movies */
    public function getTopGrossingMovies($limit = 10) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT title, director, revenue, release_date, rating_imdb, genres, language
                FROM movies WHERE revenue > 0
                ORDER BY revenue DESC LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }
    /** Q3: Actor-Director Duos */
    public function getActorDirectorCollaborations($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT director, cast, revenue, rating_imdb 
                FROM movies 
                WHERE director != 'Unknown' AND cast != 'Unknown' AND cast IS NOT NULL AND director IS NOT NULL
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $duos = [];
            foreach ($rows as $row) {
                $director = trim($row['director']);
                $castList = explode(',', $row['cast']);
                foreach ($castList as $actor) {
                    $actor = trim($actor);
                    if ($actor && $actor !== 'Unknown' && $actor !== $director) {
                        $key = $director . ' & ' . $actor;
                        if (!isset($duos[$key])) {
                            $duos[$key] = ['director' => $director, 'actor' => $actor, 'count' => 0, 'revenue' => 0, 'rating' => 0];
                        }
                        $duos[$key]['count']++;
                        $duos[$key]['revenue'] += $row['revenue'];
                        $duos[$key]['rating'] += $row['rating_imdb'];
                    }
                }
            }

            foreach ($duos as $k => $v) {
                $duos[$k]['avg_revenue'] = $v['count'] > 0 ? $v['revenue'] / $v['count'] : 0;
                $duos[$k]['avg_rating'] = $v['count'] > 0 ? $v['rating'] / $v['count'] : 0;
            }

            usort($duos, function($a, $b) {
                return $b['count'] <=> $a['count']; // Sort by frequency
            });

            return array_slice($duos, 0, $limit);
        } catch(PDOException $e) { return []; }
    }

    /** Q4: Language Champions */
    public function getLanguageRevenueAverages($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT language, AVG(revenue) as avg_revenue, COUNT(*) as movie_count 
                FROM movies 
                WHERE language IS NOT NULL AND revenue > 0
                GROUP BY language 
                HAVING COUNT(*) >= 5
                ORDER BY avg_revenue DESC 
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Q5: Runtime Sweet Spot */
    public function getRuntimeVsRating() {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("
                SELECT 
                    CASE 
                        WHEN runtime < 90 THEN '< 90 mins'
                        WHEN runtime BETWEEN 90 AND 120 THEN '90 - 120 mins'
                        WHEN runtime BETWEEN 121 AND 150 THEN '121 - 150 mins'
                        ELSE '> 150 mins'
                    END as runtime_category,
                    AVG(rating_imdb) as avg_rating,
                    COUNT(*) as movie_count
                FROM movies
                WHERE runtime > 0 AND rating_imdb > 0
                GROUP BY runtime_category
                ORDER BY avg_rating DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Q6: The 100-Crore Club */
    public function getHighGrossingGenres($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->prepare("
                SELECT TRIM(SUBSTRING_INDEX(genres, ',', 1)) as primary_genre, COUNT(*) as club_count
                FROM movies
                WHERE revenue >= 1000000000 AND genres != 'Unknown' AND genres IS NOT NULL
                GROUP BY primary_genre
                ORDER BY club_count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Q7: Prolific Performers */
    public function getTopActors($limit = 5) {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("SELECT cast FROM movies WHERE cast != 'Unknown' AND cast IS NOT NULL");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $actors = [];
            foreach ($rows as $row) {
                $castList = explode(',', $row['cast']);
                foreach ($castList as $actor) {
                    $actor = trim($actor);
                    if ($actor && $actor !== 'Unknown') {
                        $actors[$actor] = ($actors[$actor] ?? 0) + 1;
                    }
                }
            }
            arsort($actors);
            $result = [];
            foreach (array_slice($actors, 0, $limit, true) as $name => $count) {
                $result[] = ['name' => $name, 'count' => $count];
            }
            return $result;
        } catch(PDOException $e) { return []; }
    }

    /** Q8: Decade of Masterpieces */
    public function getDecadeRatings() {
        if (!$this->conn) return [];
        try {
            $stmt = $this->conn->query("
                SELECT FLOOR(YEAR(release_date) / 10) * 10 as decade, 
                       AVG(rating_imdb) as avg_rating,
                       COUNT(*) as movie_count
                FROM movies 
                WHERE release_date IS NOT NULL AND rating_imdb > 0
                GROUP BY decade 
                HAVING decade > 1900
                ORDER BY avg_rating DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Q9: Quality vs. Commercials */
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
                    COUNT(*) as movie_count
                FROM movies
                WHERE rating_imdb > 0 AND revenue > 0
                GROUP BY rating_category
                ORDER BY avg_revenue DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { return []; }
    }

    /** Q10: The Golden Year */
    public function getGoldenYear() {
        if (!$this->conn) return current([]);
        try {
            $stmt = $this->conn->query("
                SELECT YEAR(release_date) as yr, SUM(revenue) as total_revenue, COUNT(*) as movie_count
                FROM movies
                WHERE release_date IS NOT NULL AND revenue > 0
                GROUP BY yr
                ORDER BY total_revenue DESC
                LIMIT 1
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch(PDOException $e) { return []; }
    }
}
?>
