<?php
require_once __DIR__ . '/../includes/db.php';

$RECIPE_KEY = 'pui-la-cuptor';

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

  <meta property="og:title"       content="Pui la cuptor – Rețele culinare">
  <meta property="og:description" content="Pui la cuptor, gustos și sățios. ⏱ 60 min · 📈 mediu · <?= $summary['count'] ?> recenzii.">
  <meta property="og:image"       content="https://plus.unsplash.com/premium_photo-1661419883163-bb4df1c10109?w=900">
  <meta property="og:type"        content="article">

  <title>Pui la cuptor - Rețele culinare</title>

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

      <h1>Pui la cuptor</h1>

      <div class="recipe-meta">
        <span class="chip">⏱️ 60 min</span>
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

        <button id="page-fav-btn" class="page-fav-btn" type="button" data-recipe="retete/pui-la-cuptor.php">
          <span class="page-fav-star">☆</span>
          <span class="page-fav-label">Adaugă la favorite</span>
        </button>
      </div>

      <div class="recipe-hero">
        <div>
          <p>Puiul la cuptor este un preparat gustos și sățios, ideal pentru un prânz sau o cină consistentă.</p>
          <p><strong>Ingredientele se ajustează automat când schimbi porțiile.</strong></p>

          <div class="portion-controls">
            <button id="dec-portion" type="button" aria-label="Scade porții">−</button>
            <span class="portion-label">Porții: <span id="portion-live">4</span></span>
            <button id="inc-portion" type="button" aria-label="Crește porții">+</button>
          </div>

          <div class="action-row">
            <strong>Timer:</strong>
            <span id="timer-display">60:00</span>
            <button id="start-timer" class="btn" type="button">Start</button>
            <button id="reset-timer" class="btn" type="button">Reset</button>
          </div>

          <div class="note" style="margin-top:12px;">
            <strong>Sfat:</strong> Pentru o crustă rumenă și crocantă, unge carnea cu puțin ulei înainte de coacere.
          </div>
        </div>

        <div>
          <img
            src="https://plus.unsplash.com/premium_photo-1661419883163-bb4df1c10109?w=900&auto=format&fit=crop&q=60"
            alt="Pui la cuptor"
            class="recipe-img"
            width="900"
            loading="lazy"
          >
        </div>
      </div>

      <div class="recipe-grid">
        <section class="card">
          <h2>🍗 Ingrediente</h2>
          <ul id="ingredient-list">
            <li data-base="1000"><span class="qty">1000</span> g carne de pui (gambe)</li>
            <li data-base="40"><span class="qty">40</span> ml ulei</li>
            <li data-base="10"><span class="qty">10</span> g sare</li>
            <li>condimente după gust</li>
          </ul>
        </section>

        <section class="card">
          <h2>📝 Mod de preparare</h2>
          <ol class="steps">
            <li>Puiul se condimentează.</li>
            <li>Se așază într-o tavă și se unge cu ulei.</li>
            <li>Se coace la 180°C timp de 50–60 minute.</li>
          </ol>
        </section>
      </div>

      <section class="card">
        <h2>💡 Sfaturi</h2>
        <p>Pentru un pui mai suculent, întoarce bucățile la jumătatea timpului și poți adăuga 2–3 linguri de apă în tavă.</p>
      </section>

      <!-- SECȚIUNEA RECENZII -->
      <section class="card reviews-section" id="recenzii">

        <h2>💬 Recenzii
          <?php if ($summary['count'] > 0): ?>
            <span class="reviews-summary-inline">
              <?= renderStars($summary['avg']) ?>
              <?= $summary['avg'] ?>/5
              <span class="rating-count">(<?= $summary['count'] ?>)</span>
            </span>
          <?php endif; ?>
        </h2>

        <?php if ($summary['count'] > 0):
          $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
          foreach ($comments as $c) $dist[$c['rating']]++;
        ?>
          <div class="rating-dist" aria-label="Distribuție ratinguri">
            <?php for ($s = 5; $s >= 1; $s--): ?>
              <?php $pct = round($dist[$s] / $summary['count'] * 100); ?>
              <div class="rating-dist-row">
                <span class="rating-dist-label"><?= $s ?>★</span>
                <div class="rating-dist-bar-wrap" role="progressbar"
                     aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                  <div class="rating-dist-bar" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="rating-dist-count"><?= $dist[$s] ?></span>
              </div>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['added'])): ?>
          <div class="form-success" role="alert">
            ✅ Recenzia ta a fost adăugată. Mulțumim!
          </div>
        <?php endif; ?>

        <details class="review-form-details" <?= !empty($formErrors) ? 'open' : '' ?>>
          <summary>✏️ Adaugă o recenzie</summary>

          <form class="review-form" method="post" action="#recenzii" novalidate>

            <?php if (!empty($formErrors)): ?>
              <div class="form-errors" role="alert">
                <strong>Te rugăm să corectezi:</strong>
                <ul>
                  <?php foreach ($formErrors as $err): ?>
                    <li><?= e($err) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <div class="form-row">
              <label for="review-author">Nume <span aria-hidden="true">*</span></label>
              <input
                type="text"
                id="review-author"
                name="author"
                class="input"
                placeholder="ex: Maria P."
                maxlength="60"
                required
                value="<?= e($_POST['author'] ?? '') ?>"
                autocomplete="name"
              >
            </div>

            <div class="form-row">
              <label>Rating <span aria-hidden="true">*</span></label>
              <?= renderStars(0, true) ?>
            </div>

            <div class="form-row">
              <label for="review-body">Recenzia ta <span aria-hidden="true">*</span></label>
              <textarea
                id="review-body"
                name="body"
                class="input textarea"
                placeholder="Ce ți-a plăcut? Ai modificat ceva?"
                rows="4"
                maxlength="800"
                required
              ><?= e($_POST['body'] ?? '') ?></textarea>
              <span class="char-counter" id="char-counter">0 / 800</span>
            </div>

            <button type="submit" name="submit_review" class="btn">Trimite recenzia</button>

          </form>
        </details>

        <?php if (empty($comments)): ?>
          <p class="no-reviews">Nicio recenzie încă. Fii primul!</p>
        <?php else: ?>
          <ul class="comments-list" aria-label="Lista recenzii">
            <?php foreach ($comments as $c): ?>
              <li class="comment-card">
                <div class="comment-header">
                  <span class="comment-author"><?= e($c['author']) ?></span>
                  <span class="comment-stars"><?= renderStars($c['rating']) ?></span>
                  <time class="comment-date" datetime="<?= date('c', $c['timestamp']) ?>">
                    <?= formatDate($c['timestamp']) ?>
                  </time>
                </div>
                <p class="comment-body"><?= nl2br(e($c['body'])) ?></p>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

      </section>

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
    (function () {
      var ta      = document.getElementById('review-body');
      var counter = document.getElementById('char-counter');
      if (!ta || !counter) return;
      function update() {
        var len = ta.value.length;
        counter.textContent = len + ' / 800';
        counter.classList.toggle('counter-warn', len > 700);
      }
      ta.addEventListener('input', update);
      update();
    })();
  </script>
</body>
</html>