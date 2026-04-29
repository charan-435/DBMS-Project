<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../backend/DataService.php';

$input = json_decode(file_get_contents('php://input'), true);
$people = $input['people'] ?? [];

if (empty($people)) {
    echo json_encode(['status' => 'error', 'message' => 'No people selected']);
    exit;
}

$directors = [];
$actors = [];

foreach ($people as $p) {
    if ($p['type'] === 'director') $directors[] = $p['id'];
    if ($p['type'] === 'actor') $actors[] = $p['id'];
}

$conn = Database::getConnection();

// Strategy: Find movies that match ALL directors and ALL actors
// Note: Usually a movie has only one director, but we handle multiple as an intersection.

$whereClauses = [];
$params = [];

if (!empty($directors)) {
    // Movies must match ALL specified directors (usually just 1)
    foreach ($directors as $i => $id) {
        $whereClauses[] = "m.director_id = :d$i";
        $params[":d$i"] = $id;
    }
}

$actorSubquery = "";
if (!empty($actors)) {
    $placeholders = [];
    foreach ($actors as $i => $id) {
        $placeholders[] = ":a$i";
        $params[":a$i"] = $id;
    }
    $inList = implode(',', $placeholders);
    $count = count($actors);
    $actorSubquery = "INNER JOIN (
        SELECT movie_id FROM Movie_Actors 
        WHERE actor_id IN ($inList) 
        GROUP BY movie_id 
        HAVING COUNT(DISTINCT actor_id) = $count
    ) ma_filter ON m.movie_id = ma_filter.movie_id";
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(' AND ', $whereClauses) : "";

$sql = "
    SELECT m.movie_id, m.title, m.release_year, m.rating_imdb, m.revenue,
           CONCAT(d.first_name, ' ', d.last_name) as director
    FROM Movies m
    JOIN Directors d ON m.director_id = d.director_id
    $actorSubquery
    $whereSql
    ORDER BY m.release_year DESC
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate stats
    $count = count($movies);
    $avgRating = 0;
    $totalRevenue = 0;
    if ($count > 0) {
        $ratings = array_column($movies, 'rating_imdb');
        $avgRating = array_sum($ratings) / $count;
        $totalRevenue = array_sum(array_column($movies, 'revenue'));
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'movies' => $movies,
            'stats' => [
                'count' => $count,
                'avg_rating' => round($avgRating, 2),
                'total_revenue' => $totalRevenue,
                'avg_revenue' => $count > 0 ? round($totalRevenue / $count, 2) : 0
            ]
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
