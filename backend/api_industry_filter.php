<?php
/**
 * AJAX endpoint: returns top actors & directors for a given industry language.
 * GET params:
 *   type  = 'actors' | 'directors'
 *   lang  = 'all' | 'te' | 'ta' | 'hi' | 'kn' | 'ml'
 *   limit = int (default 5)
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/DataService.php';

$type  = $_GET['type']  ?? 'actors';
$lang  = $_GET['lang']  ?? 'all';
$limit = min(max((int)($_GET['limit'] ?? 5), 1), 20);

// Whitelist lang
$allowedLangs = ['all', 'te', 'ta', 'hi', 'kn', 'ml', 'en'];
if (!in_array($lang, $allowedLangs)) $lang = 'all';

$ds = new DataService();

try {
    if ($type === 'directors') {
        $rows = $ds->getTopDirectorsByRevenueForLanguage($lang, $limit);
        echo json_encode(['ok' => true, 'data' => $rows]);
    } else if ($type === 'films') {
        $rows = $ds->getTopRegionalMoviesForLanguage($lang, $limit);
        echo json_encode(['ok' => true, 'data' => $rows]);
    } else {
        $rows = $ds->getTopActorsByRevenueForLanguage($lang, $limit);
        echo json_encode(['ok' => true, 'data' => $rows]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
