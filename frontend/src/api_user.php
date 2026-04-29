<?php
ob_start();
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__ . '/components/session.php'; // Ensures logged in
require_once __DIR__ . '/../../backend/DataService.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No input provided.']);
    exit;
}

$service = new DataService();
$userId = $_SESSION['user_id'];
$action = $input['action'] ?? '';

if ($action === 'toggle_watchlist') {
    $movieId = $input['movie_id'] ?? null;
    if (!$movieId) {
        echo json_encode(['status' => 'error', 'message' => 'Movie ID missing.']);
        exit;
    }
    $res = $service->toggleWatchlist($userId, $movieId);
    ob_clean();
    if ($res && isset($res['status'])) {
        echo json_encode(['status' => 'success', 'action' => $res['status']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Operation failed']);
    }
    exit;
}

if ($action === 'post_comment') {
    $movieId = $input['movie_id'] ?? null;
    $content = $input['content'] ?? '';
    if (!$movieId || empty($content)) {
        echo json_encode(['status' => 'error', 'message' => 'Data missing.']);
        exit;
    }
    $res = $service->addComment($userId, $movieId, $content);
    ob_clean();
    echo json_encode($res ? ['status' => 'success'] : ['status' => 'error']);
    exit;
}

ob_clean();
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
