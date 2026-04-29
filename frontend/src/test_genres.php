<?php
require_once __DIR__ . '/../../backend/DataService.php';
$ds = new DataService();
$genres = $ds->getAllGenres();
echo json_encode(array_slice($genres, 0, 30));
