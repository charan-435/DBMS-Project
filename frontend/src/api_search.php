<?php
/**
 * API Endpoint for Live Search Suggestions
 * Returns JSON array of matching movies
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/DataService.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$service = new DataService();
// We use searchMovies with no other filters, page 1, 5 results
$data = $service->searchMovies($query, '', '', '', '', '', 'rating', 1, 5);

$suggestions = [];
foreach ($data['results'] as $mov) {
    $suggestions[] = [
        'id'    => $mov['movie_id'],
        'title' => $mov['title'],
        'year'  => $mov['release_year'],
        'lang'  => $mov['language'],
        'rating'=> $mov['rating_imdb']
    ];
}

echo json_encode($suggestions);
