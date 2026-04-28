<?php
/**
 * api/comments.php — Endpoint AJAX pentru recenzii
 *
 * GET  ?recipe=<key>  → returnează { ok, summary:{avg,count}, comments:[...] }
 * POST recipe, author, rating, body → adaugă recenzia, întoarce { ok, comment }
 *                                      sau { ok:false, errors:[...] } la validare
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Helper: curăță cheia rețetei (slug) ───────────────────────
function cleanRecipeKey(string $raw): string {
    return preg_replace('/[^a-z0-9\-]/', '', strtolower($raw));
}

// ─────────────────────────────────────────────────────────────
//  POST — adăugare recenzie
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipeKey = cleanRecipeKey($_POST['recipe'] ?? '');
    $author    = trim($_POST['author'] ?? '');
    $body      = trim($_POST['body']   ?? '');
    $rating    = (int)($_POST['rating'] ?? 0);

    $errors = [];
    if ($recipeKey === '')              $errors[] = 'Lipsește rețeta.';
    if (mb_strlen($author) < 2)         $errors[] = 'Numele trebuie să aibă cel puțin 2 caractere.';
    if (mb_strlen($author) > 60)        $errors[] = 'Numele este prea lung (max 60 caractere).';
    if (mb_strlen($body) < 5)           $errors[] = 'Recenzia trebuie să aibă cel puțin 5 caractere.';
    if (mb_strlen($body) > 800)         $errors[] = 'Recenzia este prea lungă (max 800 caractere).';
    if ($rating < 1 || $rating > 5)     $errors[] = 'Te rugăm să alegi un rating (1–5 stele).';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $comment = addComment($recipeKey, $author, $body, $rating);
    $summary = getRatingSummary($recipeKey);

    echo json_encode([
        'ok'      => true,
        'comment' => $comment,
        'summary' => $summary,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────────────────────────────────────────
//  GET — listează recenziile pentru o rețetă
// ─────────────────────────────────────────────────────────────
$recipeKey = cleanRecipeKey($_GET['recipe'] ?? '');

if ($recipeKey === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Lipsește rețeta.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$comments = getComments($recipeKey);
$summary  = getRatingSummary($recipeKey);

echo json_encode([
    'ok'       => true,
    'recipe'   => $recipeKey,
    'summary'  => $summary,
    'comments' => $comments,
], JSON_UNESCAPED_UNICODE);
