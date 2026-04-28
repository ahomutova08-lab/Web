<?php
/**
 * index.php — Pagina principală „Rețele culinare"
 *
 * Elemente PHP adăugate față de versiunea HTML:
 *  1. Căutare + filtrare pe server (GET ?q=&cat=&favs=)
 *  2. Contor de vizualizări totale ale site-ului
 *  3. Metadate Open Graph dinamice (pentru share pe social media)
 *  4. Suport AJAX pentru încărcare dinamică rețete
 */

require_once __DIR__ . '/includes/db.php';

// Dacă e cerere AJAX, returnăm doar conținutul rețetelor
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    require_once __DIR__ . '/api/recipes.php';
    exit;
}

// ── 1) Incrementează vizualizările paginii principale ─────────
$siteViews = incrementViews('index');

// ── 2) Citește parametrii de filtrare din GET ─────────────────
$searchQ   = trim($_GET['q']    ?? '');
$filterCat = trim($_GET['cat']  ?? 'all');
$onlyFavs  = isset($_GET['favs']) && $_GET['favs'] === '1';

// ── 3) Lista completă de rețete (sursa de adevăr pe server) ───
// Structură: [slug_href, categorie, titlu afișat, cheie_favorit]
$allRecipes = [
    // Mic dejun
    ['retete/clatite.php',        'Mic dejun', 'Clătite clasice',   'retete/clatite.php'],
    ['retete/overnight-oats.php', 'Mic dejun', 'Overnight oats',    'retete/overnight-oats.php'],
    ['retete/omleta.php',         'Mic dejun', 'Omletă cu legume',  'retete/omleta.php'],
    ['retete/sandwich.php',       'Mic dejun', 'Sandwich cald',     'retete/sandwich.php'],
    // Prânz
    ['retete/supa-legume.php',    'Prânz',     'Supă de legume',    'retete/supa-legume.php'],
    ['retete/paste-carbonara.php','Prânz',     'Paste Carbonara',   'retete/paste-carbonara.php'],
    ['retete/orez-legume.php',    'Prânz',     'Orez cu legume',    'retete/orez-legume.php'],
    ['retete/pui-la-cuptor.php',  'Prânz',     'Pui la cuptor',     'retete/pui-la-cuptor.php'],
    // Cină
    ['retete/salata-caesar.php',  'Cină',      'Salată Caesar',     'retete/salata-caesar.php'],
    ['retete/peste-gratar.php',   'Cină',      'Pește la grătar',   'retete/peste-gratar.php'],
    ['retete/cartofi-copti.php',  'Cină',      'Cartofi copți',     'retete/cartofi-copti.php'],
    ['retete/tocanita.php',       'Cină',      'Tocăniță de legume','retete/tocanita.php'],
];

// ── 4) Filtrare pe server ─────────────────────────────────────
// Favoritele sunt stocate în localStorage (client), deci filtrarea
// „doar favorite" rămâne în JavaScript — pe server filtrăm text + categorie.
function matchesFilter(array $r, string $q, string $cat): bool {
    if ($cat !== 'all' && $r[1] !== $cat) return false;
    if ($q !== '' && mb_stripos($r[2], $q) === false) return false;
    return true;
}

$filtered = array_filter($allRecipes, fn($r) => matchesFilter($r, $searchQ, $filterCat));

// ── 5) Grupare după categorie ─────────────────────────────────
$categories = ['Mic dejun', 'Prânz', 'Cină'];
$grouped = [];
foreach ($categories as $cat) {
    $grouped[$cat] = array_values(array_filter($filtered, fn($r) => $r[1] === $cat));
}

// ── 6) Număr total de rețete vizibile ─────────────────────────
$totalVisible = count($filtered);

// ── 7) Număr de rețete per categorie (pentru badge-uri) ───────
$countPerCat = [];
foreach ($categories as $cat) {
    $countPerCat[$cat] = count(array_filter($allRecipes, fn($r) => $r[1] === $cat));
}

// ── Helper: escape HTML ───────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Open Graph (dinamic, generat PHP) -->
  <meta property="og:title"       content="Rețele culinare – Rețete pentru fiecare zi">
  <meta property="og:description" content="Descoperă, gătești și salvezi rețetele tale preferate. <?= $totalVisible ?> rețete disponibile.">
  <meta property="og:type"        content="website">

  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/style-extra.css">
  <title>Rețele culinare</title>
</head>
<body>

<header class="site-header">
  <div class="header-inner container">
    <a class="brand" href="index.php">Rețele culinare</a>

    <!-- Badge vizualizări site (generat PHP) -->
    <span class="views-badge" title="Vizualizări totale ale site-ului">
      👁 <?= number_format($siteViews, 0, ',', '.') ?> vizualizări
    </span>
  </div>
</header>

<main class="container">

  <div class="hero">
    <h1>Rețete pentru fiecare zi</h1>
    <p class="hero-sub">Descoperă, gătește și salvează rețetele tale preferate.</p>

    <!-- Statistici generate server-side -->
    <div class="hero-stats">
      <?php foreach ($categories as $cat): ?>
        <span class="stat-chip">
          <?= e($cat) ?>: <strong><?= $countPerCat[$cat] ?></strong>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── Formular de filtrare (GET, procesare PHP) ── -->
  <form class="controls" method="get" action="index.php" id="filter-form" role="search">

    <input
      type="text"
      id="search-input"
      name="q"
      class="input"
      placeholder="🔍  Caută rețete..."
      aria-label="Caută rețete"
      value="<?= e($searchQ) ?>"
      autocomplete="off"
    >

    <!-- Buton submit explicit (accesibilitate + Enter pe mobile) -->
    <button type="submit" class="btn">Caută</button>

    <select id="category-filter" name="cat" class="select" aria-label="Filtrează după categorie">
      <option value="all"      <?= $filterCat === 'all'       ? 'selected' : '' ?>>Toate categoriile</option>
      <option value="Mic dejun"<?= $filterCat === 'Mic dejun' ? 'selected' : '' ?>>Mic dejun</option>
      <option value="Prânz"   <?= $filterCat === 'Prânz'     ? 'selected' : '' ?>>Prânz</option>
      <option value="Cină"    <?= $filterCat === 'Cină'      ? 'selected' : '' ?>>Cină</option>
    </select>

    <?php if ($searchQ !== '' || $filterCat !== 'all'): ?>
      <!-- Buton ștergere filtre — apare doar când sunt filtre active -->
      <a href="index.php" class="btn btn-soft" aria-label="Șterge filtrele">✕ Resetează</a>
    <?php endif; ?>

    <!-- Filtrul „doar favorite" rămâne client-side (localStorage) -->
    <label class="fav-filter-label" id="fav-label">
      <input type="checkbox" id="favorites-only">
      <span class="fav-filter-star">☆</span>
      <span>Doar favorite</span>
    </label>

  </form>

  <!-- ── Banner rezultate (afișat dinamic via JS) ── -->
  <div class="results-banner" role="status" aria-live="polite" style="display: none;"></div>

  <!-- ── Container pentru rețete (încărcat dinamic) ── -->
  <div id="recipes-container">
    <!-- Conținutul original ca fallback pentru non-JS -->
    <?php foreach ($categories as $cat): ?>
      <?php $items = $grouped[$cat]; ?>

      <?php if (!empty($items) || ($searchQ === '' && $filterCat === 'all')): ?>

        <h2 class="category-title" data-category="<?= e($cat) ?>">
          <?= e($cat) ?>
        </h2>

        <ul class="recipes" id="<?= strtolower(str_replace(['â','ă','î','ș','ț',' '], ['a','a','i','s','t','-'], $cat)) ?>-list">
          <?php foreach ($items as [$href, $itemCat, $title, $favKey]): ?>
            <?php
              // Numărul de recenzii și rating mediu (PHP)
              $slug    = basename(parse_url($href, PHP_URL_PATH), '.php');
              $slug    = basename($slug, '.html');
              $summary = getRatingSummary($slug);
            ?>
            <li
              class="recipe-item"
              data-category="<?= e($itemCat) ?>"
              data-name="<?= e(mb_strtolower($title)) ?>"
              data-recipe="<?= e($favKey) ?>"
            >
              <a href="<?= e($href) ?>"><?= e($title) ?></a>

              <!-- Rating mic (afișat dacă există recenzii) -->
              <?php if ($summary['count'] > 0): ?>
                <span class="mini-rating" title="<?= $summary['avg'] ?>/5 din <?= $summary['count'] ?> recenzii">
                  ★ <?= $summary['avg'] ?>
                  <span class="mini-rating-count">(<?= $summary['count'] ?>)</span>
                </span>
              <?php endif; ?>

              <button class="fav-btn" type="button" data-recipe="<?= e($favKey) ?>" aria-label="Adaugă la favorite">☆</button>
            </li>
          <?php endforeach; ?>
        </ul>

      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <p class="no-results" id="no-results"
     <?= $totalVisible > 0 ? 'style="display:none"' : '' ?>>
    Nicio rețetă nu corespunde filtrelor selectate.
  </p>

</main>

<footer class="site-footer">
  <div class="footer-inner container">
    <div class="muted">© <?= date('Y') ?> Rețele culinare, realizat de Homutova Alexandra</div>
  </div>
</footer>

<button class="back-to-top" id="back-to-top" aria-label="Înapoi sus" type="button">↑</button>
<div class="toast" id="toast"></div>

<script src="js/script.js"></script>
<script>
  // Suntem la rădăcină — folderul /api/ e direct alături
  window.API_BASE = 'api/';
</script>
<script src="js/ajax.js"></script>
</body>
</html>