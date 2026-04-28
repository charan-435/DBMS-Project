<?php
/**
 * API Endpoint for Live Search Suggestions
 * Returns JSON array of matching movies, directors, or actors
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/DataService.php';

$query = trim($_GET['q'] ?? '');
$type  = trim($_GET['type'] ?? 'all');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$service = new DataService();
$results = $service->searchEntities($query, $type, 6);

echo json_encode($results);
