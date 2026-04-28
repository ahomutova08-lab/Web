<?php
/**
 * cartofi-copti.php — Pagina rețetei „Cartofi copți"
 *
 * Elemente PHP adăugate față de versiunea HTML:
 *  1. Contor de vizualizări per rețetă
 *  2. Sistem de recenzii cu rating (1–5 stele) — stocare JSON
 *  3. Rating mediu + număr total recenzii afișate lângă titlu
 *  4. Formular de adăugare recenzie cu validare PHP
 *  5. Metadate Open Graph dinamice
 *  6. Recenzii încărcate via AJAX
 */

require_once __DIR__ . '/../includes/db.php';

// ── Cheia unică a rețetei (folosită pentru vizualizări + comentarii) ──
$RECIPE_KEY = 'cartofi-copti';

// ── 1) Incrementează vizualizările ────────────────────────────
$views = incrementViews($RECIPE_KEY);

// ── 2) Procesare formular de recenzie (POST) - fallback pentru non-JS ──
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
        // PRG pattern: redirect după POST pentru a evita retrimiterea formularului
        header('Location: ' . $_SERVER['PHP_SELF'] . '?added=1');
        exit;
    }
}

// ── 3) Citește comentariile și summary-ul (folosit pentru meta tags) ──
$comments = getComments($RECIPE_KEY);
$summary  = getRatingSummary($RECIPE_KEY);

// ── 4) Helper escape ──────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Returnează HTML cu stele pline/goale.
 * Ex: renderStars(3.5) → ★★★½☆
 */
function renderStars(float $rating, bool $interactive = false): string {
    if ($interactive) {
        // Stele interactive pentru formular (input radio ascuns)
        $html = '<div class="star-picker" role="radiogroup" aria-label="Rating">';
        for ($i = 5; $i >= 1; $i--) {
            $html .= '<input type="radio" name="rating" id="star'.$i.'" value="'.$i.'" required>';
            $html .= '<label for="star'.$i.'" title="'.$i.' stele" aria-label="'.$i.' '.($i === 1 ? 'stea' : 'stele').'">★</label>';
        }
        $html .= '</div>';
        return $html;
    }

    // Stele statice
    $html = '<span class="stars" aria-label="'.round($rating).' din 5 stele">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $rating ? '★' : '☆';
    }
    $html .= '</span>';
    return $html;
}

/**
 * Formatează timestamp-ul în română.
 */
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

  <!-- Open Graph dinamic -->
  <meta property="og:title"       content="Cartofi copți – Rețele culinare">
  <meta property="og:description" content="Cartofi copți la cuptor, aurii și crocanți – garnitura perfectă. ⏱ 45 min · 📈 ușor · <?= $summary['count'] ?> recenzii.">
  <meta property="og:image"       content="https://images.unsplash.com/photo-1641898378503-327316fdde77?w=900">
  <meta property="og:type"        content="article">

  <title>Cartofi copți - Rețele culinare</title>

  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/style-extra.css">
</head>
<body>

  <header class="site-header">
    <div class="header-inner container">
      <a href="../index.php" class="brand">Rețele culinare</a>

      <!-- Vizualizări rețetă (generat PHP) -->
      <span class="views-badge" title="De câte ori a fost vizualizată această rețetă">
        👁 <?= number_format($views, 0, ',', '.') ?> vizualizări
      </span>
    </div>
  </header>

  <main class="container">
    <article class="recipe">

      <h1>Cartofi copți</h1>

      <!-- Meta chips + favorit -->
      <div class="recipe-meta">
        <span class="chip">⏱️ 45 min</span>
        <span class="chip">📈 ușor</span>
        <span class="chip">🍽️ <span id="portion-value">4</span> porții</span>

        <!-- Rating mediu PHP (afișat dacă există recenzii) -->
        <?php if ($summary['count'] > 0): ?>
          <span class="chip chip-rating">
            <?= renderStars($summary['avg']) ?>
            <strong><?= $summary['avg'] ?></strong>
            <span class="rating-count">(<?= $summary['count'] ?> <?= $summary['count'] === 1 ? 'recenzie' : 'recenzii' ?>)</span>
          </span>
        <?php else: ?>
          <span class="chip chip-rating chip-rating-empty">☆ Fii primul care lasă o recenzie</span>
        <?php endif; ?>

        <button
          id="page-fav-btn"
          class="page-fav-btn"
          type="button"
          data-recipe="retete/cartofi-copti.php"
        >
          <span class="page-fav-star">☆</span>
          <span class="page-fav-label">Adaugă la favorite</span>
        </button>
      </div>

      <!-- Hero: descriere + controale | imagine -->
      <div class="recipe-hero">
        <div>
          <p>Cartofi copți la cuptor, aurii și crocanți – garnitura perfectă pentru orice friptură sau pește.</p>
          <p><strong>Ingredientele se ajustează automat când schimbi porțiile.</strong></p>

          <div class="portion-controls">
            <button id="dec-portion" type="button" aria-label="Scade porții">−</button>
            <span class="portion-label">Porții: <span id="portion-live">4</span></span>
            <button id="inc-portion" type="button" aria-label="Crește porții">+</button>
          </div>

          <div class="action-row">
            <strong>Timer:</strong>
            <span id="timer-display">35:00</span>
            <button id="start-timer" class="btn" type="button">Start</button>
            <button id="reset-timer" class="btn" type="button">Reset</button>
          </div>
        </div>

        <div>
          <img
            src="https://images.unsplash.com/photo-1641898378503-327316fdde77?w=900&auto=format&fit=crop&q=60"
            alt="Cartofi copți aurii și crocanți"
            class="recipe-img"
            width="900"
            loading="lazy"
          >
        </div>
      </div>

      <!-- Grilă ingrediente + instrucțiuni -->
      <div class="recipe-grid">

        <section class="card">
          <h2>🥔 Ingrediente</h2>
          <ul id="ingredient-list">
            <li data-base="800"><span class="qty">800</span> g cartofi</li>
            <li data-base="60"><span class="qty">60</span> ml ulei de măsline</li>
            <li data-base="4"><span class="qty">4</span> căței usturoi</li>
            <li data-base="1"><span class="qty">1</span> linguriță rozmarin uscat</li>
            <li data-base="1"><span class="qty">1</span> linguriță cimbru uscat</li>
            <li>sare și piper după gust</li>
          </ul>
        </section>

        <section class="card">
          <h2>📝 Instrucțiuni</h2>
          <ol class="steps">
            <li>Preîncălzește cuptorul la 200°C.</li>
            <li>Spală cartofii și taie-i în cuburi potrivite.</li>
            <li>Amestecă într-un bol cartofii cu ulei, usturoiul pisat și condimentele.</li>
            <li>Întinde-i într-o tavă tapetată cu hârtie de copt.</li>
            <li>Coace 35-40 minute, amestecând la jumătatea timpului.</li>
          </ol>
        </section>

      </div>

      <section class="card">
        <h2>💡 Sfaturi</h2>
        <p>Pentru extra crocanță, poți adăuga puțină făină de mălai peste cartofi înainte de coacere.</p>
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