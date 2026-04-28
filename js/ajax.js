/**
 * ajax.js — Funcționalități AJAX pentru Rețele culinare
 * - Încărcare dinamică rețete (filtrare fără reîncărcare)
 * - Încărcare recenzii asincron
 * - Adăugare recenzii via AJAX
 * - Incrementare vizualizări asincron
 */
// Calea către API — setată de fiecare pagină prin window.API_BASE.
// Fallback: 'api/' (pentru pagini la rădăcina site-ului).
var API_BASE = (typeof window !== 'undefined' && window.API_BASE) || 'api/';
// ==================== AJAX FILTRARE REȚETE ====================

/**
 * Încarcă rețetele filtrate via AJAX și înlocuiește conținutul.
 */
function loadRecipesAjax() {
    var searchInput = document.getElementById('search-input');
    var categorySelect = document.getElementById('category-filter');
    var favCheckbox = document.getElementById('favorites-only');
    
    var q = searchInput ? searchInput.value.trim() : '';
    var cat = categorySelect ? categorySelect.value : 'all';
    var onlyFavs = favCheckbox ? favCheckbox.checked : false;
    
    // Construim URL-ul cu parametrii
var url = API_BASE + 'recipes.php?q=' + encodeURIComponent(q) + '&cat=' + encodeURIComponent(cat);    
    // Arătăm un indicator de încărcare
    var container = document.getElementById('recipes-container');
    if (container) {
        container.innerHTML = '<div class="loading-spinner">⏳ Se încarcă rețetele...</div>';
    }
    
    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.ok && data.html) {
                // Actualizăm containerul cu noile rețete
                if (container) {
                    container.innerHTML = data.html;
                }
                
                // Actualizăm bannerul rezultatelor
                updateResultsBanner(data);
                
                // Reinițializăm butoanele de favorite
                if (typeof initFavButtons === 'function') {
                    initFavButtons();
                }
                
                // Aplicăm filtrul "doar favorite" client-side
                if (onlyFavs) {
                    applyFavFilterClient();
                }
                
                // Actualizăm badge-urile din hero (statistici)
                updateHeroStats(data.countPerCat);
            }
        })
        .catch(function(error) {
            console.error('Eroare AJAX:', error);
            if (container) {
                container.innerHTML = '<p class="error-message">⚠️ Eroare la încărcarea rețetelor. Reîncarcă pagina.</p>';
            }
        });
}

/**
 * Actualizează bannerul cu rezultatele căutării.
 */
function updateResultsBanner(data) {
    var banner = document.querySelector('.results-banner');
    if (!banner) return;
    
    var urlParams = new URLSearchParams(window.location.search);
    var searchQ = urlParams.get('q') || '';
    var filterCat = urlParams.get('cat') || 'all';
    
    if (data.hasFilters && data.totalVisible > 0) {
        var text = 'Am găsit <strong>' + data.totalVisible + '</strong> ';
        text += (data.totalVisible === 1 ? 'rețetă' : 'rețete');
        if (searchQ) {
            text += ' pentru „<em>' + escapeHtml(searchQ) + '</em>"';
        }
        if (filterCat !== 'all') {
            text += ' în categoria <strong>' + escapeHtml(filterCat) + '</strong>';
        }
        banner.innerHTML = text;
        banner.style.display = '';
    } else if (data.hasFilters && data.totalVisible === 0) {
        banner.innerHTML = 'Nicio rețetă nu corespunde filtrelor.';
        banner.style.display = '';
    } else {
        banner.style.display = 'none';
    }
}

/**
 * Actualizează statisticile din hero.
 */
function updateHeroStats(countPerCat) {
    if (!countPerCat) return;
    
    var heroStats = document.querySelector('.hero-stats');
    if (!heroStats) return;
    
    var categories = ['Mic dejun', 'Prânz', 'Cină'];
    var newHtml = '';
    
    categories.forEach(function(cat) {
        var count = countPerCat[cat] || 0;
        newHtml += '<span class="stat-chip">' + escapeHtml(cat) + ': <strong>' + count + '</strong></span>';
    });
    
    heroStats.innerHTML = newHtml;
}

/**
 * Aplică filtrul "doar favorite" client-side.
 */
function applyFavFilterClient() {
    var favs = typeof getFavs === 'function' ? getFavs() : [];
    var anyVisible = false;
    
    document.querySelectorAll('.recipe-item').forEach(function(item) {
        var recipe = item.getAttribute('data-recipe') || '';
        var show = favs.indexOf(recipe) !== -1;
        item.style.display = show ? '' : 'none';
        if (show) anyVisible = true;
    });
    
    // Ascunde categoriile goale
    document.querySelectorAll('.category-title').forEach(function(title) {
        var ul = title.nextElementSibling;
        if (!ul) return;
        
        var hasVisible = false;
        ul.querySelectorAll('.recipe-item').forEach(function(item) {
            if (item.style.display !== 'none') hasVisible = true;
        });
        
        title.style.display = hasVisible ? '' : 'none';
        ul.style.display = hasVisible ? '' : 'none';
    });
    
    var noResults = document.getElementById('no-results');
    if (noResults) {
        noResults.style.display = anyVisible ? 'none' : 'block';
    }
}

/**
 * Escape HTML pentru securitate.
 */
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ==================== AJAX RECENZII ====================

/**
 * Încarcă recenziile pentru o rețetă via AJAX.
 */
function loadReviewsAjax(recipeKey, containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '<div class="loading-spinner">⏳ Se încarcă recenziile...</div>';

fetch(API_BASE + 'comments.php?recipe=' + encodeURIComponent(recipeKey))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.ok) {
                renderReviews(data, container, recipeKey);
            } else {
                container.innerHTML = '<p class="error-message">Eroare la încărcarea recenziilor.</p>';
            }
        })
        .catch(function(error) {
            console.error('Eroare AJAX recenzii:', error);
            container.innerHTML = '<p class="error-message">Eroare la încărcarea recenziilor.</p>';
        });
}

/**
 * Randează recenziile în container.
 */
function renderReviews(data, container, recipeKey) {
    var summary = data.summary;
    var comments = data.comments;
    
    if (!comments) {
        container.innerHTML = '<p class="no-reviews">Nicio recenzie încă. Fii primul!</p>';
        return;
    }
    
    var html = '';
    
    // Header cu rating
    html += '<div class="reviews-header">';
    html += '<h3>Recenzii ';
    if (summary.count > 0) {
        html += '<span class="reviews-summary-inline">';
        html += renderStarsStatic(summary.avg);
        html += summary.avg + '/5';
        html += '<span class="rating-count">(' + summary.count + ')</span>';
        html += '</span>';
    }
    html += '</h3>';
    html += '</div>';
    
    // Distribuție stele
    if (summary.count > 0) {
        var dist = {5:0,4:0,3:0,2:0,1:0};
        comments.forEach(function(c) { dist[c.rating]++; });
        
        html += '<div class="rating-dist">';
        for (var s = 5; s >= 1; s--) {
            var pct = Math.round(dist[s] / summary.count * 100);
            html += '<div class="rating-dist-row">';
            html += '<span class="rating-dist-label">' + s + '★</span>';
            html += '<div class="rating-dist-bar-wrap" role="progressbar" aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100">';
            html += '<div class="rating-dist-bar" style="width:' + pct + '%"></div>';
            html += '</div>';
            html += '<span class="rating-dist-count">' + dist[s] + '</span>';
            html += '</div>';
        }
        html += '</div>';
    }
    
    // Formular (doar dacă există în pagină)
    var formHtml = document.querySelector('.review-form-details');
    if (formHtml) {
        html += formHtml.outerHTML;
    } else {
        html += '<details class="review-form-details">';
        html += '<summary>✏️ Adaugă o recenzie</summary>';
        html += '<form class="review-form" data-recipe="' + recipeKey + '" onsubmit="submitReviewAjax(event)">';
        html += '<div class="form-row">';
        html += '<label for="review-author">Nume *</label>';
        html += '<input type="text" id="review-author" name="author" class="input" placeholder="ex: Maria P." maxlength="60" required>';
        html += '</div>';
        html += '<div class="form-row">';
        html += '<label>Rating *</label>';
        html += '<div class="star-picker" role="radiogroup">';
        for (var i = 5; i >= 1; i--) {
            html += '<input type="radio" name="rating" id="star' + i + '" value="' + i + '" required>';
            html += '<label for="star' + i + '" title="' + i + ' stele">★</label>';
        }
        html += '</div>';
        html += '</div>';
        html += '<div class="form-row">';
        html += '<label for="review-body">Recenzia ta *</label>';
        html += '<textarea id="review-body" name="body" class="input textarea" placeholder="Ce ți-a plăcut?" rows="4" maxlength="800" required></textarea>';
        html += '<span class="char-counter">0 / 800</span>';
        html += '</div>';
        html += '<button type="submit" class="btn">Trimite recenzia</button>';
        html += '</form>';
        html += '</details>';
    }
    
    // Lista comentariilor
    html += '<ul class="comments-list" aria-label="Lista recenzii">';
    comments.forEach(function(c) {
        html += '<li class="comment-card">';
        html += '<div class="comment-header">';
        html += '<span class="comment-author">' + escapeHtml(c.author) + '</span>';
        html += '<span class="comment-stars">' + renderStarsStatic(c.rating) + '</span>';
        html += '<time class="comment-date">' + formatDateStatic(c.timestamp) + '</time>';
        html += '</div>';
        html += '<p class="comment-body">' + escapeHtml(c.body).replace(/\n/g, '<br>') + '</p>';
        html += '</li>';
    });
    html += '</ul>';
    
    container.innerHTML = html;
    
    // Reatașăm event listener pentru formular
    var form = container.querySelector('.review-form');
    if (form) {
        form.onsubmit = function(e) { submitReviewAjax(e, recipeKey); };
    }
}

/**
 * Trimite recenzia via AJAX.
 */
function submitReviewAjax(event, recipeKey) {
    event.preventDefault();
    
    var form = event.target;
    var author = form.querySelector('[name="author"]').value;
    var rating = form.querySelector('[name="rating"]:checked');
    var body = form.querySelector('[name="body"]').value;
    
    if (!rating) {
        showToastAjax('Te rugăm să alegi un rating.');
        return;
    }
    
    rating = rating.value;
    
    var formData = new FormData();
    formData.append('recipe', recipeKey);
    formData.append('author', author);
    formData.append('rating', rating);
    formData.append('body', body);
    
    fetch(API_BASE + 'comments.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.ok) {
            showToastAjax('✅ Recenzia ta a fost adăugată!');
            // Reîncărcăm recenziile
            loadReviewsAjax(recipeKey, 'reviews-ajax-container');
        } else if (data.errors) {
            var errorMsg = data.errors.join(', ');
            showToastAjax('❌ ' + errorMsg);
        }
    })
    .catch(function(error) {
        console.error('Eroare trimitere recenzie:', error);
        showToastAjax('❌ Eroare la trimiterea recenziei.');
    });
}

// ==================== FUNCȚII AJUTĂTOARE STATICE ====================

function renderStarsStatic(rating) {
    var html = '<span class="stars">';
    for (var i = 1; i <= 5; i++) {
        html += i <= rating ? '★' : '☆';
    }
    html += '</span>';
    return html;
}

function formatDateStatic(timestamp) {
    var d = new Date(timestamp * 1000);
    var months = ['', 'ian.', 'feb.', 'mar.', 'apr.', 'mai', 'iun.',
                  'iul.', 'aug.', 'sep.', 'oct.', 'nov.', 'dec.'];
    return d.getDate() + ' ' + months[d.getMonth() + 1] + ' ' + d.getFullYear();
}

function showToastAjax(msg) {
    var toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(function() { toast.classList.remove('show'); }, 2200);
}

// ==================== INCREMENTARE VIZUALIZĂRI AJAX ====================

/**
 * Trimite o cerere AJAX pentru a incrementa vizualizările unei rețete.
 */
function incrementViewsAjax(recipeKey) {
    if (!recipeKey) return;
    
    var formData = new FormData();
    formData.append('recipe', recipeKey);
    
   fetch(API_BASE + 'views.php', {
        method: 'POST',
        body: formData
    })
    .catch(function(error) {
        console.error('Eroare incrementare vizualizări:', error);
    });
}

// ==================== INIȚIALIZARE AJAX ====================

/**
 * Inițializează toate funcționalitățile AJAX.
 */
function initAjax() {
    // Pentru pagina index: înlocuim submit-ul formularului cu AJAX
    var filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadRecipesAjax();
            
            // Actualizăm URL-ul pentru istoric (fără reîncărcare)
            var searchInput = document.getElementById('search-input');
            var categorySelect = document.getElementById('category-filter');
            var q = searchInput ? searchInput.value.trim() : '';
            var cat = categorySelect ? categorySelect.value : 'all';
            
            var newUrl = window.location.pathname;
            var params = [];
            if (q) params.push('q=' + encodeURIComponent(q));
            if (cat !== 'all') params.push('cat=' + encodeURIComponent(cat));
            if (params.length) newUrl += '?' + params.join('&');
            
            window.history.pushState({}, '', newUrl);
        });
    }
    
    // Pentru pagina rețetei: încărcare recenzii AJAX
    var reviewsContainer = document.getElementById('reviews-ajax-container');
    if (reviewsContainer) {
        var recipeKey = reviewsContainer.getAttribute('data-recipe');
        if (recipeKey) {
            loadReviewsAjax(recipeKey, 'reviews-ajax-container');
        }
    }
    
    // Incrementare vizualizări AJAX (pentru paginile de rețete)
    var pageFavBtn = document.getElementById('page-fav-btn');
    if (pageFavBtn) {
        var recipeKey = pageFavBtn.getAttribute('data-recipe');
        if (recipeKey) {
            // Extragem cheia din path
            var key = recipeKey.replace('retete/', '').replace('.php', '');
            incrementViewsAjax(key);
        }
    }
}

// Pornim AJAX când DOM-ul e gata
document.addEventListener('DOMContentLoaded', function() {
    initAjax();
});