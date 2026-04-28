<?php
/**
 * comments.php — Handler AJAX pentru comentarii
 *
 * GET  ?recipe=cartofi-copti          → returnează JSON cu comentariile + summary
 * POST recipe=..., author=..., body=..., rating=... → adaugă comentariu, returnează JSON
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/includes/db.php';

// ─── Sanitizare cheie rețetă ──────────────────────────────────
$recipe = preg_replace('/[^a-z0-9\-]/', '', strtolower(
    $_GET['recipe'] ?? $_POST['recipe'] ?? ''
));

if (!$recipe) {
    http_response_code(400);
    echo json_encode(['error' => 'Lipsește cheia rețetei.']);
    exit;
}

// ─── GET: returnează comentariile ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $comments = getComments($recipe);
    $summary  = getRatingSummary($recipe);

    echo json_encode([
        'ok'       => true,
        'summary'  => $summary,
        'comments' => $comments,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── POST: adaugă comentariu ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = trim($_POST['author'] ?? '');
    $body   = trim($_POST['body']   ?? '');
    $rating = (int)($_POST['rating'] ?? 0);

    // Validare
    $errors = [];
    if (mb_strlen($author) < 2)  $errors[] = 'Numele trebuie să aibă cel puțin 2 caractere.';
    if (mb_strlen($author) > 60) $errors[] = 'Numele este prea lung (max 60 caractere).';
    if (mb_strlen($body)   < 5)  $errors[] = 'Recenzia trebuie să aibă cel puțin 5 caractere.';
    if (mb_strlen($body)   > 800) $errors[] = 'Recenzia este prea lungă (max 800 caractere).';
    if ($rating < 1 || $rating > 5) $errors[] = 'Rating invalid (1–5).';

    if ($errors) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }

    $comment = addComment($recipe, $author, $body, $rating);
    $summary = getRatingSummary($recipe);

    echo json_encode([
        'ok'      => true,
        'comment' => $comment,
        'summary' => $summary,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Altceva ──────────────────────────────────────────────────
http_response_code(405);
echo json_encode(['error' => 'Metodă nepermisă.']);