<?php
/**
 * api/views.php — Incrementează vizualizările pentru o rețetă (AJAX)
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$recipe = $_POST['recipe'] ?? $_GET['recipe'] ?? '';
if (!$recipe) {
    echo json_encode(['error' => 'Lipsește rețeta']);
    exit;
}

// Curățăm cheia
$recipe = preg_replace('/[^a-z0-9\-]/', '', strtolower($recipe));

$views = incrementViews($recipe);

echo json_encode([
    'ok' => true,
    'views' => $views,
    'recipe' => $recipe
]);