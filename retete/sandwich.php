<?php
require_once __DIR__ . '/../includes/db.php';

$RECIPE_KEY = 'sandwich';

$views = incrementViews($RECIPE_KEY);

$formErrors  = [];
$formSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $author = trim($_POST['author'] ?? '');
    $body   = trim($_POST['body']   ?? '');
    $rating = (int)($_POST['rating'] ?? 0);

    if (mb_strlen($author) < 2)   $formErrors[] = 'Numele trebuie să aibă cel puțin 2 caractere.';
    if (mb_strlen($author) > 60)  $formErrors[] = 'Numele este prea lung (max 60 caractere).';
    if (mb_strlen($body)   < 5)   $formErrors[] = 'Recenzia trebuie să aibă cel puțin 5 caractere.';
    if (mb_strlen($body)   > 800) $formErrors[] = 'Recenzia este prea lungă (max 800 caractere).';
    if ($rating < 1 || $rating > 5) $formErrors[] = 'Te rugăm să alegi un rating (1–5 stele).';

    if (empty($formErrors)) {
        addComment($RECIPE_KEY, $author, $body, $rating);
        $formSuccess = true;
        header('Location: ' . $_SERVER['PHP_SELF'] . '?added=1');
        exit;
    }
}

$comments = getComments($RECIPE_KEY);
$summary  = getRatingSummary($RECIPE_KEY);

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function renderStars(float $rating, bool $interactive = false): string {
    if ($interactive) {
        $html = '<div class="star-picker" role="radiogroup" aria-label="Rating">';
        for ($i = 5; $i >= 1; $i--) {
            $html .= '<input type="radio" name="rating" id="star'.$i.'" value="'.$i.'" required>';
            $html .= '<label for="star'.$i.'" title="'.$i.' stele" aria-label="'.$i.' '.($i === 1 ? 'stea' : 'stele').'">★</label>';
        }
        $html .= '</div>';
        return $html;
    }
    $html = '<span class="stars" aria-label="'.round($rating).' din 5 stele">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $rating ? '★' : '☆';
    }
    $html .= '</span>';
    return $html;
}

function formatDate(int $ts): string {
    $months = ['', 'ian.', 'feb.', 'mar.', 'apr.', 'mai', 'iun.',
                    'iul.', 'aug.', 'sep.', 'oct.', 'nov.', 'dec.'];
    return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta property="og:title"       content="Sandwich cald – Rețele culinare">
  <meta property="og:description" content="Sandwich cald, rapid și gustos. ⏱ 10 min · 📈 ușor · <?= $summary['count'] ?> recenzii.">
  <meta property="og:image"       content="https://media.istockphoto.com/id/1088403682/photo/hot-toasted-sandwich-fresh-from-the-plate-grill.webp?a=1&b=1&s=612x612&w=0&k=20&c=YHUH8gnVTkGqO05_MyqTlAUVYN_bApzuuWmbdkR8NqM=">
  <meta property="og:type"        content="article">

  <title>Sandwich cald - Rețele culinare</title>

  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/style-extra.css">
</head>
<body>

  <header class="site-header">
    <div class="header-inner container">
      <a href="../index.php" class="brand">Rețele culinare</a>
      <span class="views-badge" title="De câte ori a fost vizualizată această rețetă">
        👁 <?= number_format($views, 0, ',', '.') ?> vizualizări
      </span>
    </div>
  </header>

  <main class="container">
    <article class="recipe">

      <h1>Sandwich cald</h1>

      <div class="recipe-meta">
        <span class="chip">⏱️ 10 min</span>
        <span class="chip">📈 ușor</span>
        <span class="chip">🍽️ <span id="portion-value">2</span> porții</span>

        <?php if ($summary['count'] > 0): ?>
          <span class="chip chip-rating">
            <?= renderStars($summary['avg']) ?>
            <strong><?= $summary['avg'] ?></strong>
            <span class="rating-count">(<?= $summary['count'] ?> <?= $summary['count'] === 1 ? 'recenzie' : 'recenzii' ?>)</span>
          </span>
        <?php else: ?>
          <span class="chip chip-rating chip-rating-empty">☆ Fii primul care lasă o recenzie</span>
        <?php endif; ?>

        <button id="page-fav-btn" class="page-fav-btn" type="button" data-recipe="retete/sandwich.php">
          <span class="page-fav-star">☆</span>
          <span class="page-fav-label">Adaugă la favorite</span>
        </button>
      </div>

      <div class="recipe-hero">
        <div>
          <p>Sandwich-ul cald este o gustare rapidă și gustoasă, ideală pentru mic dejun sau pauze scurte.</p>
          <p><strong>Ingredientele se ajustează automat când schimbi porțiile.</strong></p>

          <div class="portion-controls">
            <button id="dec-portion" type="button" aria-label="Scade porții">−</button>
            <span class="portion-label">Porții: <span id="portion-live">2</span></span>
            <button id="inc-portion" type="button" aria-label="Crește porții">+</button>
          </div>

          <div class="action-row">
            <strong>Timer:</strong>
            <span id="timer-display">10:00</span>
            <button id="start-timer" class="btn" type="button">Start</button>
            <button id="reset-timer" class="btn" type="button">Reset</button>
          </div>

          <div class="note" style="margin-top:12px;">
            <strong>Sfat:</strong> Unge exteriorul pâinii cu puțin unt pentru o crustă crocantă.
          </div>
        </div>

        <div>
          <img
            src="https://media.istockphoto.com/id/1088403682/photo/hot-toasted-sandwich-fresh-from-the-plate-grill.webp?a=1&b=1&s=612x612&w=0&k=20&c=YHUH8gnVTkGqO05_MyqTlAUVYN_bApzuuWmbdkR8NqM="
            alt="Sandwich cald"
            class="recipe-img"
            width="612"
            loading="lazy"
          >
        </div>
      </div>

      <div class="recipe-grid">

        <section class="card">
          <h2>🥪 Ingrediente</h2>
          <ul id="ingredient-list">
            <li data-base="4"><span class="qty">4</span> felii pâine</li>
            <li data-base="100"><span class="qty">100</span> g șuncă</li>
            <li data-base="100"><span class="qty">100</span> g cașcaval</li>
            <li data-base="20"><span class="qty">20</span> g unt</li>
          </ul>
        </section>

        <section class="card">
          <h2>📝 Mod de preparare</h2>
          <ol class="steps">
            <li>Se așază ingredientele între feliile de pâine.</li>
            <li>Se rumenește pe grill sau în tigaie.</li>
          </ol>
        </section>

      </div>

      <section class="card">
        <h2>💡 Sfaturi</h2>
        <p>Pentru un plus de gust, poți adăuga 1–2 felii de roșie sau puțin muștar, iar cașcavalul se va topi mai uniform dacă ții focul mic.</p>
      </section>

       <!-- ════════════════════════════════════════════════════ -->
      <!-- SECȚIUNEA RECENZII (ÎNCĂRCATĂ VIA AJAX)             -->
      <!-- ════════════════════════════════════════════════════ -->
      <section class="card reviews-section" id="recenzii">
        <h2>💬 Recenzii</h2>
        <div id="reviews-ajax-container" data-recipe="<?= $RECIPE_KEY ?>">
          <div class="loading-spinner">⏳ Se încarcă recenziile...</div>
        </div>
      </section>
      <!-- /recenzii -->

    </article>
  </main>

  <footer class="site-footer">
    <div class="footer-inner container">
      <span>© <?= date('Y') ?> Rețele culinare</span>
      <span class="muted">realizat de Homutova Alexandra</span>
    </div>
  </footer>

  <button class="back-to-top" id="back-to-top" aria-label="Înapoi sus" type="button">↑</button>
  <div class="toast" id="toast"></div>

  <script src="../js/script.js"></script>
  <script>
    // Calea către folderul /api/ raportată la pagina curentă (suntem în /retete/)
    window.API_BASE = '../api/';
  </script>
  <script src="../js/ajax.js"></script>
</body>
</html>