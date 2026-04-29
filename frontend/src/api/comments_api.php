<?php
require_once __DIR__ . '/../components/session.php';
require_once __DIR__ . '/../../../backend/DataService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$service = new DataService();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'delete') {
        $commentId = $data['comment_id'] ?? 0;
        if ($service->deleteComment($commentId, $userId)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete comment']);
        }
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
