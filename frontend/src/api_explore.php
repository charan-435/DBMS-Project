<?php
/**
 * API Endpoint for Explore Data Dashboard
 * Handles dynamic insight building requests.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/DataService.php';

// Only allow POST or GET requests
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_GET;
}

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No input provided.']);
    exit;
}

$service = new DataService();

$action = $input['action'] ?? 'build_insight';

if ($action === 'build_insight') {
    $params = [
        'dimension' => $input['dimension'] ?? 'genre_name',
        'metric_func' => $input['metric_func'] ?? 'COUNT',
        'metric_field' => $input['metric_field'] ?? 'movie_id',
        'sort_dir' => $input['sort_dir'] ?? 'DESC',
        'limit' => $input['limit'] ?? 50,
        'filters' => $input['filters'] ?? []
    ];

    $result = $service->buildDynamicInsight($params);
    echo json_encode($result);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
