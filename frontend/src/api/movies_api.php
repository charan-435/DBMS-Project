<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => "PHP[$errno]: $errstr in $errfile:$errline", 'results' => [], 'total' => 0]);
    exit;
});

set_exception_handler(function($e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage(), 'results' => [], 'total' => 0]);
    exit;
});

header('Content-Type: application/json');

// ─── Inline DB config ─────────────────────────────────────────────
$host     = 'localhost';
$dbName   = 'cinematic_lens_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbName;charset=utf8",
        $username, $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['error' => 'DB: ' . $e->getMessage(), 'results' => [], 'total' => 0]);
    exit;
}

// ─── Parse request params ─────────────────────────────────────────
$page     = max(1, (int)($_GET['page']    ?? 1));
$limit    = 15;
$offset   = ($page - 1) * $limit;
$search   = trim($_GET['search']   ?? '');
$genreRaw = $_GET['genre']         ?? '';
$genreId  = (is_numeric($genreRaw) && $genreRaw !== '') ? (int)$genreRaw : null;

$langRaw  = trim($_GET['lang']     ?? '');
$minRating= is_numeric($_GET['min_rating'] ?? '') ? (float)$_GET['min_rating'] : null;
$maxRating= is_numeric($_GET['max_rating'] ?? '') ? (float)$_GET['max_rating'] : null;
$minYear  = is_numeric($_GET['min_year']   ?? '') ? (int)$_GET['min_year']     : null;
$maxYear  = is_numeric($_GET['max_year']   ?? '') ? (int)$_GET['max_year']     : null;

$sortRaw  = $_GET['sort']  ?? 'release_year';
$orderRaw = strtoupper($_GET['order'] ?? 'DESC');
if (!in_array($orderRaw, ['ASC', 'DESC'])) $orderRaw = 'DESC';

$sortFields = [
    'release_year' => 'm.release_year',
    'rating'       => 'm.rating_imdb',
    'revenue'      => 'm.revenue',
    'title'        => 'm.title',
];

$sortField = $sortFields[$sortRaw] ?? 'm.release_year';
$orderBy   = "$sortField $orderRaw";

// Secondary sort for consistency
if ($sortRaw !== 'title') {
    $orderBy .= ", m.title ASC";
}

// ─── Build WHERE ──────────────────────────────────────────────────
$where  = ["d.first_name NOT LIKE '%Unknown%'"];
$params = [];

if ($search !== '') {
    $where[]       = "(m.title LIKE :s1 OR d.first_name LIKE :s2 OR d.last_name LIKE :s3 OR CONCAT(d.first_name,' ',d.last_name) LIKE :s4)";
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
    $params[':s4'] = "%$search%";
}
if ($genreId !== null)   { $where[] = "m.genre_id = :gid";            $params[':gid']  = $genreId;    }
if ($langRaw !== '')     { $where[] = "m.language = :lang";            $params[':lang'] = $langRaw;    }
if ($minRating !== null) { $where[] = "m.rating_imdb >= :minr";        $params[':minr'] = $minRating;  }
if ($maxRating !== null) { $where[] = "m.rating_imdb <= :maxr";        $params[':maxr'] = $maxRating;  }
if ($minYear !== null)   { $where[] = "m.release_year >= :miny";       $params[':miny'] = $minYear;    }
if ($maxYear !== null)   { $where[] = "m.release_year <= :maxy";       $params[':maxy'] = $maxYear;    }

$whereSql = implode(' AND ', $where);

// ─── Count ────────────────────────────────────────────────────────
$cStmt = $pdo->prepare("SELECT COUNT(*) FROM Movies m
                         JOIN Directors d ON m.director_id = d.director_id
                         WHERE $whereSql");
foreach ($params as $k => $v) $cStmt->bindValue($k, $v);
$cStmt->execute();
$total = (int)$cStmt->fetchColumn();

// ─── Fetch page ───────────────────────────────────────────────────
$sql = "SELECT m.movie_id, m.title, m.release_year, m.revenue, m.rating_imdb, m.language,
               CONCAT(d.first_name,' ',d.last_name) AS director_name, d.director_id,
               g.genre_name, g.genre_id
        FROM Movies m
        JOIN Directors d ON m.director_id = d.director_id
        JOIN Genres    g ON m.genre_id    = g.genre_id
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Format revenue ───────────────────────────────────────────────
foreach ($rows as &$row) {
    $amt = (float)($row['revenue'] ?? 0);
    if ($amt <= 0) { $row['revenue_formatted'] = '—'; }
    else {
        $cr = $amt / 10000000;
        $row['revenue_formatted'] = ($cr >= 1000)
            ? number_format($cr / 1000, 1) . 'K Cr'
            : number_format($cr, 1) . ' Cr';
    }
}
unset($row);

ob_clean();
echo json_encode(['results' => $rows, 'total' => $total]);
