/**
 * script.js - Rețele culinare
 * Module: Favorite (index + pagină rețetă sincronizate) · Filtrare · Back to top · Porții · Checklist · Timer
 *
 * Ideea generală:
 * - salvăm „favoritele” în localStorage (sau în memorie ca fallback pe Safari / fișiere locale)
 * - pe index: butoane mici ☆/★ + filtru (caută/categorie/doar favorite)
 * - pe pagina rețetei: buton mare de favorit + toast
 * - funcții comune: back-to-top, porții (scalează ingredientele), checklist pași, timer
 */

// ==================== FAVORITE — STORAGE ====================

// Cheia din localStorage unde se păstrează lista de favorite
var FAV_KEY  = 'rc-favorites';

// Fallback în memorie: util când localStorage nu e disponibil (ex: Safari cu file://)
var _memFavs = null;

/**
 * Citește lista de favorite.
 * - Dacă avem fallback în memorie (_memFavs), returnăm o copie.
 * - Altfel încercăm să citim din localStorage.
 * - Dacă apare eroare (ex: acces interzis), inițializăm fallback-ul și returnăm listă goală.
 */
function getFavs() {
  if (_memFavs !== null) return _memFavs.slice();
  try {
    var val = localStorage.getItem(FAV_KEY);
    return val ? JSON.parse(val) : [];
  } catch(e) { 
    _memFavs = []; 
    return []; 
  }
}

/**
 * Salvează lista de favorite.
 * - În mod normal: localStorage.
 * - Dacă nu se poate: folosim fallback în memorie.
 */
function saveFavs(arr) {
  try { 
    localStorage.setItem(FAV_KEY, JSON.stringify(arr)); 
    _memFavs = null; 
  } catch(e) { 
    _memFavs = arr.slice(); 
  }
}

/**
 * Verifică dacă o rețetă (cheie/slug) este în lista de favorite.
 */
function isFav(recipe) { 
  return getFavs().indexOf(recipe) !== -1; 
}

/**
 * Comută starea de favorit (add/remove).
 * - dacă nu există -> push
 * - dacă există -> remove
 * apoi persistă lista.
 */
function toggleFav(recipe) {
  var favs = getFavs();
  var idx  = favs.indexOf(recipe);
  if (idx === -1) favs.push(recipe);
  else favs.splice(idx, 1);
  saveFavs(favs);
}

// ==================== FAVORITE — BUTOANE MIC (index) ====================

/**
 * Actualizează UI pentru butonul mic de favorit de pe index.
 * - schimbă simbolul ☆/★
 * - setează aria-label pentru accesibilitate
 * - adaugă/șterge clasa .active (pentru stilizare)
 */
function updateFavBtn(btn, active) {
  btn.textContent = active ? '★' : '☆';
  btn.setAttribute('aria-label', active ? 'Șterge din favorite' : 'Adaugă la favorite');
  if (active) btn.classList.add('active');
  else btn.classList.remove('active');
}

/**
 * Inițializează toate butoanele mici .fav-btn de pe index.
 * Așteaptă ca fiecare buton să aibă atributul data-recipe (cheia rețetei).
 * La click:
 * - prevenim navigarea (dacă butonul e în link) și propagarea
 * - togglăm favorit
 * - updatăm UI
 * - dacă e activ „doar favorite”, reaplicăm filtrele
 */
function initFavButtons() {
  document.querySelectorAll('.fav-btn').forEach(function(btn) {
    var recipe = btn.getAttribute('data-recipe');
    if (!recipe) return;

    // stare inițială în funcție de localStorage
    updateFavBtn(btn, isFav(recipe));

    btn.addEventListener('click', function(e) {
      e.preventDefault(); 
      e.stopPropagation();

      toggleFav(recipe);
      updateFavBtn(btn, isFav(recipe));

      // dacă filtrul „favorite-only” e pornit, recalculează ce rămâne vizibil
      var cb = document.getElementById('favorites-only');
      if (cb && cb.checked) applyFilters();
    });
  });
}

// ==================== FAVORITE — BUTON MARE (pagina rețetei) ====================

/**
 * Inițializează butonul mare de favorite de pe pagina unei rețete.
 * Elemente așteptate:
 * - buton cu id="page-fav-btn" și data-recipe="cheie"
 * - în interior (opțional) .page-fav-star și .page-fav-label
 * La click: togglare + toast.
 */
function initPageFavBtn() {
  var btn = document.getElementById('page-fav-btn');
  if (!btn) return;

  var recipe = btn.getAttribute('data-recipe');
  if (!recipe) return;

  var star  = btn.querySelector('.page-fav-star');
  var label = btn.querySelector('.page-fav-label');

  /**
   * Redă (render) starea vizuală în funcție de „active”.
   */
  function render(active) {
    if (star)  star.textContent  = active ? '★' : '☆';
    if (label) label.textContent = active ? 'Salvat la favorite' : 'Adaugă la favorite';
    if (active) btn.classList.add('active');
    else        btn.classList.remove('active');
  }

  // Stare inițială din storage
  render(isFav(recipe));

  // La click: toggle + re-render + mesaj scurt (toast)
  btn.addEventListener('click', function() {
    toggleFav(recipe);
    var active = isFav(recipe);
    render(active);
    showToast(active ? '★ Adăugat la favorite!' : 'Șters din favorite');
  });
}

// ==================== TOAST ====================

/**
 * Afișează un mesaj de tip toast (mic popup).
 * Necesită un element cu id="toast" în pagină.
 * Clasa .show (CSS) controlează vizibilitatea.
 */
function showToast(msg) {
  var toast = document.getElementById('toast');
  if (!toast) return;

  toast.textContent = msg;
  toast.classList.add('show');

  // după 2.2 secunde îl ascundem
  setTimeout(function() { 
    toast.classList.remove('show'); 
  }, 2200);
}

// ==================== FILTRARE (index) ====================

/**
 * Aplică filtrele pe lista de rețete din index:
 * - text search (#search-input) comparat cu data-name
 * - categorie (#category-filter) comparată cu data-category
 * - only favorites (#favorites-only) comparat cu data-recipe
 *
 * În plus:
 * - ascunde titlurile de categorii care rămân fără elemente vizibile
 * - afișează „no results” dacă nu rămâne nimic vizibil
 */
function applyFilters() {
  var searchEl   = document.getElementById('search-input');
  var categoryEl = document.getElementById('category-filter');
  var cb         = document.getElementById('favorites-only');

  // normalizăm inputul (lowercase + trim)
  var search   = searchEl   ? searchEl.value.toLowerCase().trim() : '';
  var category = categoryEl ? categoryEl.value : 'all';
  var onlyFavs = cb         ? cb.checked : false;

  // lista de favorite (pentru filtrarea „only favs”)
  var favs     = getFavs();
  var anyVisible = false;

  // filtrare efectivă pe fiecare item
  document.querySelectorAll('.recipe-item').forEach(function(item) {
    var name   = (item.getAttribute('data-name')     || '').toLowerCase();
    var cat    = (item.getAttribute('data-category') || '');
    var recipe = (item.getAttribute('data-recipe')   || '');
    var show   = true;

    // 1) căutare text
    if (search && name.indexOf(search) === -1) show = false;

    // 2) categorie selectată
    if (show && category !== 'all' && cat !== category) show = false;

    // 3) doar favorite
    if (show && onlyFavs && favs.indexOf(recipe) === -1) show = false;

    // afișăm/ascundem itemul
    item.style.display = show ? '' : 'none';
    if (show) anyVisible = true;
  });

  // Ascunde titluri categorii goale (presupune structură: .category-title urmat de <ul>)
  document.querySelectorAll('.category-title').forEach(function(title) {
    var ul = title.nextElementSibling;
    if (!ul) return;

    var hasVisible = false;
    ul.querySelectorAll('.recipe-item').forEach(function(item) {
      if (item.style.display !== 'none') hasVisible = true;
    });

    // ascundem atât titlul, cât și lista dacă nu mai au elemente vizibile
    title.style.display = hasVisible ? '' : 'none';
    ul.style.display    = hasVisible ? '' : 'none';
  });

  // Mesajul „nu există rezultate”
  var noResults = document.getElementById('no-results');
  if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
}

// ==================== CHECKBOX VIZUAL (index) ====================

/**
 * Face checkbox-ul „doar favorite” mai „vizual”:
 * - când e bifat: label primește clasa .checked și steaua devine ★
 * - când e debifat: revine la ☆
 * După schimbare, reaplică filtrele.
 */
function initFavCheckbox() {
  var label = document.getElementById('fav-label');
  var cb    = document.getElementById('favorites-only');
  var star  = label ? label.querySelector('.fav-filter-star') : null;
  if (!label || !cb) return;

  cb.addEventListener('change', function() {
    if (cb.checked) { 
      label.classList.add('checked'); 
      if (star) star.textContent = '★'; 
    } else { 
      label.classList.remove('checked'); 
      if (star) star.textContent = '☆'; 
    }
    applyFilters();
  });
}

// ==================== BACK TO TOP ====================

/**
 * Buton „înapoi sus”.
 * - apare după scrollY > 300 (clasa .show)
 * - la click: scroll lin (smooth) la top
 */
function initBackToTop() {
  var btn = document.getElementById('back-to-top');
  if (!btn) return;

  window.addEventListener('scroll', function() { 
    btn.classList.toggle('show', window.scrollY > 300); 
  });

  btn.addEventListener('click', function() { 
    window.scrollTo({ top: 0, behavior: 'smooth' }); 
  });
}

// ==================== PORȚII + INGREDIENTE ====================

/**
 * Funcționalitate porții:
 * - pornește de la valoarea din #portion-value (BASE)
 * - la + / - modifică „current”
 * - calculează factor = current / BASE
 * - scalează fiecare ingredient:
 *   * fiecare <li> trebuie să aibă data-base="cantitatea pentru BASE porții"
 *   * în <li> trebuie să existe un span.qty unde afișăm cantitatea curentă
 * - rotunjire: dacă nu e întreg, rotunjim la 1 zecimală
 * - înlocuim punct cu virgulă la afișare (RO)
 */
function initPortions() {
  var portionValue   = document.getElementById('portion-value');
  var portionLive    = document.getElementById('portion-live');
  var incBtn         = document.getElementById('inc-portion');
  var decBtn         = document.getElementById('dec-portion');
  var ingredientList = document.getElementById('ingredient-list');

  // dacă lipsește ceva important, nu inițializăm (evităm erori în console)
  if (!portionValue || !incBtn || !decBtn || !ingredientList) return;

  // BASE = porțiile inițiale (din HTML)
  var BASE    = parseInt(portionValue.textContent, 10) || 4;
  var current = BASE;

  // colectăm ingredientele care au data-base și span.qty
  var items   = [];
  ingredientList.querySelectorAll('li[data-base]').forEach(function(li) {
    var base    = parseFloat(li.getAttribute('data-base'));
    var qtySpan = li.querySelector('.qty');
    if (!isNaN(base) && qtySpan) items.push({ base: base, qtySpan: qtySpan });
  });

  /**
   * Actualizează:
   * - numărul de porții în UI
   * - ingredientele (cantitățile scalate)
   */
  function update() {
    portionValue.textContent = current;
    if (portionLive) portionLive.textContent = current;

    var factor = current / BASE;

    items.forEach(function(item) {
      var raw = item.base * factor;

      // dacă raw e întreg -> păstrăm întreg, altfel -> 1 zecimală
      var val = (raw % 1 === 0) ? raw : Math.round(raw * 10) / 10;

      // afișare cu virgulă
      item.qtySpan.textContent = String(val).replace('.', ',');
    });
  }

  // + porții
  incBtn.addEventListener('click', function() { 
    current++; 
    update(); 
  });

  // - porții (nu coborâm sub 1)
  decBtn.addEventListener('click', function() { 
    if (current > 1) { 
      current--; 
      update(); 
    } 
  });
}

// ==================== CHECKLIST PAȘI ====================

/**
 * Checklist pentru pașii rețetei:
 * - caută lista: <ol class="steps">
 * - pentru fiecare <li>:
 *   * la click: toggle clasa .done
 *   * salvăm în localStorage indecșii pașilor făcuți
 *
 * Cheia de storage e derivată din numele fișierului (pathname).
 * Exemplu: /retete/cartofi-copti.html -> steps-cartofi-copti.html
 */
function initStepChecklist() {
  var stepsList = document.querySelector('ol.steps');
  if (!stepsList) return;

  var recipeKey  = window.location.pathname.split('/').pop() || 'reteta';
  var storageKey = 'steps-' + recipeKey;

  // citim indecșii salvați anterior
  var doneSteps  = [];
  try { 
    doneSteps = JSON.parse(localStorage.getItem(storageKey) || '[]'); 
  } catch(e) {}

  var steps = Array.from(stepsList.querySelectorAll('li'));

  steps.forEach(function(step, index) {
    // marcăm pașii deja făcuți
    if (doneSteps.indexOf(index) !== -1) step.classList.add('done');

    // click pe pas -> îl marcăm/ demarcăm
    step.addEventListener('click', function() {
      step.classList.toggle('done');

      // reconstruim lista de indecși „done”
      var updated = steps
        .map(function(li, idx) { return li.classList.contains('done') ? idx : null; })
        .filter(function(v) { return v !== null; });

      // salvăm lista
      try { 
        localStorage.setItem(storageKey, JSON.stringify(updated)); 
      } catch(e) {}
    });
  });
}

// ==================== TIMER ====================

/**
 * Timer simplu (start/pauză + reset):
 * Elemente așteptate:
 * - #timer-display  (text inițial ex: "25:00")
 * - #start-timer
 * - #reset-timer
 *
 * Funcționare:
 * - parseTime: "MM:SS" -> secunde totale
 * - fmt: secunde -> "MM:SS"
 * - start:
 *   * dacă rulează: pauză (clearInterval)
 *   * dacă e oprit: pornește și scade 1/s
 * - la 0: oprește și alert
 * - reset: revine la valoarea inițială
 */
function initTimer() {
  var display  = document.getElementById('timer-display');
  var startBtn = document.getElementById('start-timer');
  var resetBtn = document.getElementById('reset-timer');
  if (!display || !startBtn || !resetBtn) return;

  // transformă "MM:SS" în secunde
  function parseTime(str) {
    var parts = (str || '25:00').split(':').map(function(x) { return parseInt(x, 10); });
    return (parts[0] || 0) * 60 + (parts[1] || 0);
  }

  // formatează secunde în "MM:SS"
  function fmt(s) { 
    return String(Math.floor(s/60)).padStart(2,'0') + ':' + String(s%60).padStart(2,'0'); 
  }

  var total = parseTime(display.textContent.trim());
  var remaining = total, interval = null, running = false;

  // afișăm formatat încă de la început
  display.textContent = fmt(remaining);

  startBtn.addEventListener('click', function() {
    if (running) {
      // Pauză
      clearInterval(interval); 
      interval = null; 
      running = false; 
      startBtn.textContent = 'Start';
    } else {
      // Start
      running = true; 
      startBtn.textContent = 'Pauză';

      interval = setInterval(function() {
        if (remaining <= 0) { 
          clearInterval(interval); 
          running = false; 
          startBtn.textContent = 'Start'; 
          alert('Timpul a expirat!'); 
          return; 
        }
        remaining--; 
        display.textContent = fmt(remaining);
      }, 1000);
    }
  });

  resetBtn.addEventListener('click', function() {
    clearInterval(interval); 
    running = false; 
    remaining = total;

    display.textContent = fmt(remaining); 
    startBtn.textContent = 'Start';
  });
}

// ==================== INIT ====================

/**
 * Inițializare generală după ce DOM-ul e încărcat:
 * - index: favorite mici + checkbox + listeners pentru search/categorie
 * - pagină rețetă: buton mare favorit
 * - comune (pe orice pagină care are elementele): back to top, porții, checklist, timer
 */
document.addEventListener('DOMContentLoaded', function() {
  // Index
  initFavButtons();
  initFavCheckbox();

  var searchInput = document.getElementById('search-input');
  var categorySel = document.getElementById('category-filter');

  if (searchInput) searchInput.addEventListener('input',  applyFilters);
  if (categorySel) categorySel.addEventListener('change', applyFilters);

  // Pagina rețetei
  initPageFavBtn();

  // Comune
  initBackToTop();
  initPortions();
  initStepChecklist();
  initTimer();
});