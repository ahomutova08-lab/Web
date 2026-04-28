<?php
/**
 * api/rating.php — Obține rating-ul pentru o rețetă (AJAX)
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$recipe = $_GET['recipe'] ?? '';
if (!$recipe) {
    echo json_encode(['error' => 'Lipsește rețeta']);
    exit;
}

// Curățăm cheia
$recipe = preg_replace('/[^a-z0-9\-]/', '', strtolower($recipe));

$summary = getRatingSummary($recipe);

echo json_encode([
    'ok' => true,
    'avg' => $summary['avg'],
    'count' => $summary['count']
]);