<?php
/**
 * api/recipes.php — Endpoint AJAX pentru rețete
 * GET: returnează lista filtrată de rețete
 * POST: (nu folosit momentan)
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Lista completă de rețete (sursa de adevăr)
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

$categories = ['Mic dejun', 'Prânz', 'Cină'];

// Citește parametrii de filtrare
$searchQ   = trim($_GET['q']    ?? '');
$filterCat = trim($_GET['cat']  ?? 'all');

// Funcție de filtrare
function matchesFilterAjax(array $r, string $q, string $cat): bool {
    if ($cat !== 'all' && $r[1] !== $cat) return false;
    if ($q !== '' && mb_stripos($r[2], $q) === false) return false;
    return true;
}

$filtered = array_filter($allRecipes, fn($r) => matchesFilterAjax($r, $searchQ, $filterCat));

// Grupare după categorie
$grouped = [];
foreach ($categories as $cat) {
    $grouped[$cat] = array_values(array_filter($filtered, fn($r) => $r[1] === $cat));
}

// Număr total vizibile
$totalVisible = count($filtered);

// Număr per categorie (pentru badge-uri)
$countPerCat = [];
foreach ($categories as $cat) {
    $countPerCat[$cat] = count(array_filter($allRecipes, fn($r) => $r[1] === $cat));
}

// Adaugă rating summary pentru fiecare rețetă
foreach ($grouped as $cat => $recipes) {
    foreach ($recipes as $i => $recipe) {
        $slug = basename(parse_url($recipe[0], PHP_URL_PATH), '.php');
        $slug = basename($slug, '.html');
        $summary = getRatingSummary($slug);
        $grouped[$cat][$i]['rating'] = $summary;
    }
}

// Construiește HTML-ul pentru rețete (folosit de AJAX)
$html = '';

foreach ($categories as $cat) {
    $items = $grouped[$cat];
    
    if (!empty($items)) {
        $html .= '<h2 class="category-title" data-category="' . htmlspecialchars($cat) . '">' . htmlspecialchars($cat) . '</h2>';
        $html .= '<ul class="recipes" id="' . strtolower(str_replace(['â','ă','î','ș','ț',' '], ['a','a','i','s','t','-'], $cat)) . '-list">';
        
        foreach ($items as $item) {
            $href = $item[0];
            $title = $item[2];
            $favKey = $item[3];
            $rating = $item['rating'] ?? ['count' => 0, 'avg' => 0];
            
            $html .= '<li class="recipe-item" data-category="' . htmlspecialchars($cat) . '" data-name="' . htmlspecialchars(mb_strtolower($title)) . '" data-recipe="' . htmlspecialchars($favKey) . '">';
            $html .= '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($title) . '</a>';
            
            if ($rating['count'] > 0) {
                $html .= '<span class="mini-rating" title="' . $rating['avg'] . '/5 din ' . $rating['count'] . ' recenzii">';
                $html .= '★ ' . $rating['avg'];
                $html .= '<span class="mini-rating-count">(' . $rating['count'] . ')</span>';
                $html .= '</span>';
            }
            
            $html .= '<button class="fav-btn" type="button" data-recipe="' . htmlspecialchars($favKey) . '" aria-label="Adaugă la favorite">☆</button>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    }
}

if ($totalVisible === 0) {
    $html = '<p class="no-results" id="no-results" style="display:block;">Nicio rețetă nu corespunde filtrelor selectate.</p>';
}

// Returnează JSON cu HTML și metadate
echo json_encode([
    'ok' => true,
    'html' => $html,
    'totalVisible' => $totalVisible,
    'countPerCat' => $countPerCat,
    'hasFilters' => ($searchQ !== '' || $filterCat !== 'all')
], JSON_UNESCAPED_UNICODE);