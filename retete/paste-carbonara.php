<?php
require_once __DIR__ . '/../includes/db.php';

$RECIPE_KEY = 'paste-carbonara';

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

  <meta property="og:title"       content="Paste Carbonara – Rețele culinare">
  <meta property="og:description" content="Paste Carbonara cremoase și savuroase. ⏱ 25 min · 📈 mediu · <?= $summary['count'] ?> recenzii.">
  <meta property="og:image"       content="https://images.unsplash.com/photo-1608756687911-aa1599ab3bd9?w=900">
  <meta property="og:type"        content="article">

  <title>Paste Carbonara - Rețele culinare</title>

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

      <h1>Paste Carbonara</h1>

      <div class="recipe-meta">
        <span class="chip">⏱️ 25 min</span>
        <span class="chip">📈 mediu</span>
        <span class="chip">🍽️ <span id="portion-value">4</span> porții</span>

        <?php if ($summary['count'] > 0): ?>
          <span class="chip chip-rating">
            <?= renderStars($summary['avg']) ?>
            <strong><?= $summary['avg'] ?></strong>
            <span class="rating-count">(<?= $summary['count'] ?> <?= $summary['count'] === 1 ? 'recenzie' : 'recenzii' ?>)</span>
          </span>
        <?php else: ?>
          <span class="chip chip-rating chip-rating-empty">☆ Fii primul care lasă o recenzie</span>
        <?php endif; ?>

        <button id="page-fav-btn" class="page-fav-btn" type="button" data-recipe="retete/paste-carbonara.php">
          <span class="page-fav-star">☆</span>
          <span class="page-fav-label">Adaugă la favorite</span>
        </button>
      </div>

      <div class="recipe-hero">
        <div>
          <p>Pastele Carbonara sunt un preparat cremos și savuros, ideal pentru un prânz sau o cină rapidă.</p>
          <p><strong>Ingredientele se ajustează automat când schimbi porțiile.</strong></p>

          <div class="portion-controls">
            <button id="dec-portion" type="button" aria-label="Scade porții">−</button>
            <span class="portion-label">Porții: <span id="portion-live">4</span></span>
            <button id="inc-portion" type="button" aria-label="Crește porții">+</button>
          </div>

          <div class="action-row">
            <strong>Timer:</strong>
            <span id="timer-display">25:00</span>
            <button id="start-timer" class="btn" type="button">Start</button>
            <button id="reset-timer" class="btn" type="button">Reset</button>
          </div>
        </div>

        <div>
          <img
            src="https://images.unsplash.com/photo-1608756687911-aa1599ab3bd9?w=900&auto=format&fit=crop&q=60"
            alt="Paste Carbonara"
            class="recipe-img"
            width="900"
            loading="lazy"
          >
        </div>
      </div>

      <div class="recipe-grid">

        <section class="card">
          <h2>🍝 Ingrediente</h2>
          <ul id="ingredient-list">
            <li data-base="200"><span class="qty">200</span> g paste</li>
            <li data-base="100"><span class="qty">100</span> g bacon</li>
            <li data-base="2"><span class="qty">2</span> ouă</li>
            <li data-base="50"><span class="qty">50</span> g parmezan</li>
            <li>sare și piper după gust</li>
          </ul>
        </section>

        <section class="card">
          <h2>📝 Instrucțiuni</h2>
          <ol class="steps">
            <li>Pastele se fierb în apă cu sare conform instrucțiunilor de pe ambalaj.</li>
            <li>Baconul se prăjește într-o tigaie până devine ușor crocant.</li>
            <li>Ouăle se bat și se amestecă bine cu parmezanul ras.</li>
            <li>Pastele scurse se adaugă peste bacon, se ia tigaia de pe foc.</li>
            <li>Se adaugă amestecul de ouă și parmezan, amestecând rapid pentru un sos cremos.</li>
          </ol>
        </section>

      </div>

      <section class="card">
        <h2>💡 Sfaturi</h2>
        <p>Amestecă ouăle rapid cu pastele fierbinți departe de foc pentru un sos cremos, fără a le găti excesiv. Puțină apă de la paste ajută la legarea sosului.</p>
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