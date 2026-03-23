<?php
/**
 * db.php — Strat de date (fișiere JSON)
 * Înlocuiește o bază de date reală cu fișiere JSON în /data/
 * Producție: înlocuiți cu PDO + MySQL/SQLite.
 */

define('DATA_DIR', __DIR__ . '/../data/');

// ─── VIZUALIZĂRI ──────────────────────────────────────────────

/**
 * Citește contorul de vizualizări pentru o cheie .
 */
function getViews(string $key): int {
    $file = DATA_DIR . 'views.json';
    if (!file_exists($file)) return 0;
    $data = json_decode(file_get_contents($file), true) ?? [];
    return (int)($data[$key] ?? 0);
}

/**
 * Incrementează vizualizările pentru o cheie.
 * Returnează noul număr.
 */
function incrementViews(string $key): int {
    $file = DATA_DIR . 'views.json';
    $data = file_exists($file)
        ? (json_decode(file_get_contents($file), true) ?? [])
        : [];
    $data[$key] = ($data[$key] ?? 0) + 1;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    return $data[$key];
}

// ─── COMENTARII ───────────────────────────────────────────────

/**
 * Citește toate comentariile pentru o rețetă.
 * @return array  Array de comentarii, cel mai nou primul.
 */
function getComments(string $recipeKey): array {
    $file = DATA_DIR . "comments_{$recipeKey}.json";
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true) ?? [];
    // Cel mai nou primul
    usort($data, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
    return $data;
}

/**
 * Adaugă un comentariu nou.
 * @return array  Comentariul adăugat (cu id + timestamp).
 */
function addComment(string $recipeKey, string $author, string $body, int $rating): array {
    $file = DATA_DIR . "comments_{$recipeKey}.json";
    $data = file_exists($file)
        ? (json_decode(file_get_contents($file), true) ?? [])
        : [];

    $comment = [
        'id'        => uniqid('c', true),
        'author'    => htmlspecialchars(trim($author), ENT_QUOTES, 'UTF-8'),
        'body'      => htmlspecialchars(trim($body),   ENT_QUOTES, 'UTF-8'),
        'rating'    => max(1, min(5, (int)$rating)),
        'timestamp' => time(),
    ];

    $data[] = $comment;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $comment;
}

/**
 * Calculează media ratingului și numărul total de recenzii.
 * @return array  ['avg' => float, 'count' => int]
 */
function getRatingSummary(string $recipeKey): array {
    $comments = getComments($recipeKey);
    if (empty($comments)) return ['avg' => 0.0, 'count' => 0];
    $sum = array_sum(array_column($comments, 'rating'));
    return [
        'avg'   => round($sum / count($comments), 1),
        'count' => count($comments),
    ];
}