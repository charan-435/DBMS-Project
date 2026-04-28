<?php
ob_start();
error_reporting(0);
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
    ob_clean();
    echo json_encode($result);
    exit;
}

if ($action === 'get_trend_analysis') {
    $data = $service->getGenreTrend();
    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

ob_clean();
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
