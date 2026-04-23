<?php
require_once __DIR__ . '/backend/DataService.php';
$service = new DataService();
print_r($service->getTopDirectors(5));
print_r($service->getGoldenYear());
