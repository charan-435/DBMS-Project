<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../components/session.php';
require_once __DIR__ . '/../../../backend/DataService.php';

$service = new DataService();

$type   = $_GET['type'] ?? 'actor';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$search = $_GET['search'] ?? '';
$genre  = $_GET['genre'] ?? '';
$min_rating = $_GET['min_rating'] ?? '';
$min_year   = $_GET['min_year'] ?? '';
$max_year   = $_GET['max_year'] ?? '';
$lang       = $_GET['lang'] ?? '';
$sort   = $_GET['sort'] ?? 'total_revenue';
$order  = $_GET['order'] ?? 'DESC';

$filters = [
    'search' => $search,
    'genre'  => $genre,
    'min_rating' => $min_rating,
    'min_year'   => $min_year,
    'max_year'   => $max_year,
    'lang'       => $lang,
    'sort'   => $sort,
    'order'  => $order
];

$data = $service->getPeoplePaginated($type, $page, $limit, $filters);

// Format revenue
foreach ($data['results'] as &$r) {
    if ($r['total_revenue'] >= 1e7) {
        $r['revenue_fmt'] = '₹' . number_format($r['total_revenue'] / 1e7, 1) . ' Cr';
    } else {
        $r['revenue_fmt'] = '₹' . number_format($r['total_revenue'] / 1e5, 1) . ' L';
    }
}

echo json_encode($data);
