<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$genres  = $service->getAllGenres();

// Get distinct languages for filter dropdown
$langs = [];
try {
    $db = Database::getConnection();
    $s  = $db->query("SELECT DISTINCT language FROM Movies WHERE language IS NOT NULL ORDER BY language");
    $langs = $s->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e) { $langs = []; }

$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Vault — All Movies | The Cinematic Lens</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* ── Vault header ── */
    .vault-header { margin-bottom: 1.5rem; }
    .vault-header h1 { font-size: 2.2rem; font-weight: 800; margin: 0 0 .3rem; }
    .vault-header p  { margin: 0; color: var(--text-muted); font-size: .9rem; }

    /* ── Filter panel ── */
    .filter-panel {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 1.25rem 1.5rem;
      margin-bottom: 1.5rem;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: flex-end;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: .35rem;
      min-width: 130px;
    }
    .filter-group label {
      font-size: .65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--text-muted);
    }
    .filter-group input,
    .filter-group select {
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      color: var(--text-primary);
      padding: .5rem .75rem;
      border-radius: 6px;
      font-size: .85rem;
      font-family: inherit;
      transition: border-color .2s;
      width: 100%;
    }
    .filter-group input:focus,
    .filter-group select:focus {
      outline: none;
      border-color: var(--accent-primary);
    }
    .filter-group.search-group { flex: 1; min-width: 220px; position: relative; }
    .filter-actions {
      display: flex;
      gap: .5rem;
      align-items: flex-end;
      margin-left: auto;
    }
    .btn-reset {
      padding: .5rem 1.1rem;
      background: transparent;
      border: 1px solid var(--border-color);
      color: var(--text-muted);
      border-radius: 6px;
      font-size: .8rem;
      cursor: pointer;
      transition: all .2s;
      font-family: inherit;
    }
    .btn-reset:hover { border-color: #ef4444; color: #ef4444; }

    /* ── Active chips ── */
    .active-filters {
      display: flex;
      flex-wrap: wrap;
      gap: .5rem;
      margin-bottom: 1rem;
      min-height: 0;
    }
    .filter-chip {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .25rem .7rem;
      background: rgba(126,175,232,.12);
      border: 1px solid rgba(126,175,232,.25);
      border-radius: 20px;
      font-size: .72rem;
      color: var(--accent-primary);
      font-weight: 600;
      cursor: pointer;
      transition: background .2s;
    }
    .filter-chip:hover { background: rgba(126,175,232,.22); }
    .filter-chip .chip-x { font-size: .8rem; opacity: .7; }

    /* ── Search dropdown ── */
    .search-dropdown {
      position: absolute;
      top: calc(100% + 4px);
      left: 0; right: 0;
      background: #1a1a24;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      z-index: 200;
      max-height: 300px;
      overflow-y: auto;
      box-shadow: 0 12px 30px rgba(0,0,0,.6);
      display: none;
    }
    .sd-item {
      padding: .65rem 1rem;
      border-bottom: 1px solid #252533;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background .15s;
    }
    .sd-item:last-child { border-bottom: none; }
    .sd-item:hover { background: #252533; }
    .sd-item .name { font-size: .85rem; font-weight: 600; color: #eee; }
    .sd-item .meta { font-size: .7rem; color: var(--text-muted); }
    .sd-badge {
      font-size: .58rem;
      background: var(--bg-dark);
      padding: 2px 6px;
      border-radius: 4px;
      color: var(--text-muted);
      text-transform: uppercase;
      font-weight: 700;
    }

    /* ── Table ── */
    .table-wrap { position: relative; }
    #loading-overlay {
      position: absolute; inset: 0;
      background: rgba(10,10,15,.7);
      display: none; align-items: center; justify-content: center;
      z-index: 10; border-radius: var(--radius-lg);
      backdrop-filter: blur(2px);
    }
    .spinner {
      width: 30px; height: 30px;
      border: 3px solid var(--border-color);
      border-top-color: var(--accent-primary);
      border-radius: 50%;
      animation: spin .8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Pagination ── */
    .pagination { display: flex; justify-content: center; gap: .5rem; margin-top: 1.5rem; }
    .page-link {
      padding: .45rem .95rem;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      color: var(--text-primary);
      text-decoration: none;
      border-radius: 4px;
      font-size: .82rem;
      cursor: pointer;
      transition: all .2s;
    }
    .page-link:hover, .page-link.active {
      background: var(--accent-primary);
      color: var(--bg-dark);
      border-color: var(--accent-primary);
    }
    .page-link.disabled { opacity: .4; pointer-events: none; cursor: default; }

    /* ── Results bar ── */
    .results-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: .75rem;
      font-size: .8rem;
      color: var(--text-muted);
    }
    .results-bar strong { color: var(--text-primary); }
  </style>
</head>
<body>
  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <!-- Header -->
      <div class="vault-header">
        <h1>The <em style="color:var(--accent-primary);font-style:italic;">Vault</em></h1>
        <p>Browse the complete film archive — <strong id="total-count-display">…</strong> titles</p>
      </div>

      <!-- Filter Panel -->
      <div class="filter-panel">

        <!-- Search -->
        <div class="filter-group search-group">
          <label>Search</label>
          <input type="text" id="f-search" placeholder="Title, director, actor…" autocomplete="off">
          <div id="search-dropdown" class="search-dropdown"></div>
        </div>

        <!-- Genre -->
        <div class="filter-group">
          <label>Genre</label>
          <select id="f-genre">
            <option value="">All Genres</option>
            <?php foreach ($genres as $g): ?>
              <option value="<?= $g['genre_id'] ?>"><?= htmlspecialchars($g['genre_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Language -->
        <div class="filter-group">
          <label>Language</label>
          <select id="f-lang">
            <option value="">All Languages</option>
            <?php foreach ($langs as $l): ?>
              <option value="<?= htmlspecialchars($l) ?>"><?= htmlspecialchars(getLanguageName($l)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Rating Range -->
        <div class="filter-group" style="min-width:100px;">
          <label>Min Rating ★</label>
          <select id="f-min-rating">
            <option value="">Any</option>
            <?php for($r=5;$r<=9;$r++): ?>
              <option value="<?= $r ?>"><?= $r ?>.0+</option>
            <?php endfor; ?>
          </select>
        </div>

        <!-- Year Range -->
        <div class="filter-group" style="min-width:90px;">
          <label>From Year</label>
          <input type="number" id="f-min-year" placeholder="e.g. 2000" min="1900" max="<?= $currentYear ?>" style="width:110px;">
        </div>
        <div class="filter-group" style="min-width:90px;">
          <label>To Year</label>
          <input type="number" id="f-max-year" placeholder="e.g. <?= $currentYear ?>" min="1900" max="<?= $currentYear ?>" style="width:110px;">
        </div>

        <!-- Sort -->
        <div class="filter-group" style="min-width:140px;">
          <label>Sort By</label>
          <select id="f-sort">
            <option value="release_year">Release Year</option>
            <option value="rating">IMDb Rating</option>
            <option value="revenue">Revenue</option>
            <option value="title">Movie Title</option>
          </select>
        </div>

        <!-- Order -->
        <div class="filter-group" style="min-width:90px;">
          <label>Order</label>
          <select id="f-order">
            <option value="DESC">High to Low</option>
            <option value="ASC">Low to High</option>
          </select>
        </div>

        <!-- Actions -->
        <div class="filter-actions">
          <button class="btn-reset" id="btn-reset-filters">✕ Clear All</button>
        </div>
      </div>

      <!-- Active Filter Chips -->
      <div id="active-filters" class="active-filters"></div>

      <!-- Results bar -->
      <div class="results-bar">
        <span>Showing <strong id="showing-range">—</strong> of <strong id="total-count-display2">—</strong> results</span>
        <span id="page-indicator"></span>
      </div>

      <!-- Table -->
      <div class="table-wrap card" style="padding:0; overflow:hidden;">
        <div id="loading-overlay"><div class="spinner"></div></div>
        <table class="data-table">
          <thead>
            <tr>
              <th style="padding-left:1.5rem;">Movie</th>
              <th>Director</th>
              <th>Genre</th>
              <th>Rating</th>
              <th style="text-align:right;padding-right:1.5rem;">Revenue</th>
            </tr>
          </thead>
          <tbody id="movies-table-body">
            <tr><td colspan="5" style="text-align:center;padding:4rem;color:var(--text-muted);">Loading…</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div id="pagination-container" class="pagination"></div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2026. DYNAMIC ARCHIVE SYNC ENABLED.</div>
    </div>
  </main>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // ── Element refs ──────────────────────────────────────────────
    const fSearch    = document.getElementById('f-search');
    const fGenre     = document.getElementById('f-genre');
    const fLang      = document.getElementById('f-lang');
    const fMinRating = document.getElementById('f-min-rating');
    const fMinYear   = document.getElementById('f-min-year');
    const fMaxYear   = document.getElementById('f-max-year');
    const fSort      = document.getElementById('f-sort');
    const fOrder     = document.getElementById('f-order');
    const btnReset   = document.getElementById('btn-reset-filters');
    const tableBody  = document.getElementById('movies-table-body');
    const pagCon     = document.getElementById('pagination-container');
    const overlay    = document.getElementById('loading-overlay');
    const totalEl    = document.getElementById('total-count-display');
    const total2El   = document.getElementById('total-count-display2');
    const rangeEl    = document.getElementById('showing-range');
    const pageInd    = document.getElementById('page-indicator');
    const dropdown   = document.getElementById('search-dropdown');
    const chipsCon   = document.getElementById('active-filters');

    let currentPage = 1;
    let searchTimer, dropTimer;
    const LIMIT = 15;

    // ── Build query string from all filters ───────────────────────
    function buildParams(page) {
      const p = new URLSearchParams();
      p.set('page', page);
      if (fSearch.value.trim())   p.set('search',     fSearch.value.trim());
      if (fGenre.value)           p.set('genre',       fGenre.value);
      if (fLang.value)            p.set('lang',        fLang.value);
      if (fMinRating.value)       p.set('min_rating',  fMinRating.value);
      if (fMinYear.value)         p.set('min_year',    fMinYear.value);
      if (fMaxYear.value)         p.set('max_year',    fMaxYear.value);
      p.set('sort', fSort.value || 'release_year');
      p.set('order', fOrder.value || 'DESC');
      return p.toString();
    }

    // ── Active filter chips ────────────────────────────────────────
    function renderChips() {
      const chips = [];
      if (fSearch.value.trim())  chips.push({ label: `"${fSearch.value.trim()}"`,  clear: () => { fSearch.value=''; } });
      if (fGenre.value)          chips.push({ label: fGenre.options[fGenre.selectedIndex].text, clear: () => { fGenre.value=''; } });
      if (fLang.value)           chips.push({ label: fLang.options[fLang.selectedIndex].text,   clear: () => { fLang.value=''; } });
      if (fMinRating.value)      chips.push({ label: `★ ${fMinRating.value}+`,                  clear: () => { fMinRating.value=''; } });
      if (fMinYear.value)        chips.push({ label: `From ${fMinYear.value}`,                  clear: () => { fMinYear.value=''; } });
      if (fMaxYear.value)        chips.push({ label: `To ${fMaxYear.value}`,                    clear: () => { fMaxYear.value=''; } });
      if (fSort.value && fSort.value !== 'release_year')
                                 chips.push({ label: `Sort: ${fSort.options[fSort.selectedIndex].text}`, clear: () => { fSort.value='release_year'; } });
      if (fOrder.value && fOrder.value !== 'DESC')
                                 chips.push({ label: `Order: ${fOrder.options[fOrder.selectedIndex].text}`, clear: () => { fOrder.value='DESC'; } });

      chipsCon.innerHTML = chips.map((c, i) =>
        `<span class="filter-chip" data-chip="${i}">${c.label} <span class="chip-x">✕</span></span>`
      ).join('');

      // Attach click to clear each chip
      chipsCon.querySelectorAll('.filter-chip').forEach(el => {
        el.addEventListener('click', () => {
          chips[parseInt(el.dataset.chip)].clear();
          updateVault(1);
        });
      });
    }

    // ── Main fetch & render ────────────────────────────────────────
    async function updateVault(page = 1) {
      currentPage = page;
      overlay.style.display = 'flex';
      renderChips();

      try {
        const qs = buildParams(page);
        const response = await fetch(`api/movies_api.php?${qs}`);
        const rawText  = await response.text();
        console.log('API raw (500ch):', rawText.substring(0, 500));

        let data;
        try { data = JSON.parse(rawText); }
        catch(e) {
          tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:3rem;color:#ef4444;">
            Non-JSON from server:<br><small style="font-family:monospace">${rawText.substring(0,300).replace(/</g,'&lt;')}</small>
          </td></tr>`;
          return;
        }

        if (data.error) {
          tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:3rem;color:#ef4444;">Server error: ${data.error}</td></tr>`;
          return;
        }

        // Stats
        const total   = data.total;
        const from    = total === 0 ? 0 : (page - 1) * LIMIT + 1;
        const to      = Math.min(page * LIMIT, total);
        totalEl.textContent  = total.toLocaleString();
        total2El.textContent = total.toLocaleString();
        rangeEl.textContent  = total === 0 ? '0' : `${from}–${to}`;
        pageInd.textContent  = `Page ${page} of ${Math.max(1, Math.ceil(total/LIMIT))}`;

        renderTable(data.results);
        renderPagination(total, page);

        // Sync URL
        const url = new URL(window.location);
        url.search = buildParams(page);
        window.history.replaceState({}, '', url);

      } catch(err) {
        console.error('Fetch error:', err);
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:3rem;color:#ef4444;">Connection error — ${err.message}</td></tr>`;
      } finally {
        overlay.style.display = 'none';
      }
    }

    // ── Table renderer ────────────────────────────────────────────
    function renderTable(movies) {
      if (!movies || movies.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:4rem;color:var(--text-muted);">
          No films match your filters. Try broadening your search.
        </td></tr>`;
        return;
      }
      tableBody.innerHTML = movies.map(m => `
        <tr>
          <td style="padding-left:1.5rem;">
            <div style="font-weight:700;">
              <a href="movie_details.php?id=${m.movie_id}" class="table-link">${m.title}</a>
            </div>
            <div style="font-size:.73rem;color:var(--text-muted);">${m.release_year} &bull; ${m.language.toUpperCase()}</div>
          </td>
          <td>
            <a href="director_details.php?id=${m.director_id}" class="table-link-muted">${m.director_name}</a>
          </td>
          <td><span class="genre-tag">${m.genre_name}</span></td>
          <td><span style="color:var(--accent-primary);font-weight:700;">★ ${parseFloat(m.rating_imdb).toFixed(1)}</span></td>
          <td style="text-align:right;padding-right:1.5rem;font-family:var(--font-mono);font-weight:600;color:var(--accent-green);">
            &#x20B9;${m.revenue_formatted}
          </td>
        </tr>
      `).join('');
    }

    // ── Pagination renderer ───────────────────────────────────────
    function renderPagination(total, current) {
      const totalPages = Math.ceil(total / LIMIT);
      if (totalPages <= 1) { pagCon.innerHTML = ''; return; }

      let html = `<a href="#" data-page="${Math.max(1,current-1)}" class="page-link ${current<=1?'disabled':''}">← Prev</a>`;
      const start = Math.max(1, current - 2);
      const end   = Math.min(totalPages, current + 2);
      if (start > 1) html += '<span class="page-link disabled">…</span>';
      for (let i = start; i <= end; i++)
        html += `<a href="#" data-page="${i}" class="page-link ${i===current?'active':''}">${i}</a>`;
      if (end < totalPages) html += '<span class="page-link disabled">…</span>';
      html += `<a href="#" data-page="${Math.min(totalPages,current+1)}" class="page-link ${current>=totalPages?'disabled':''}">Next →</a>`;
      pagCon.innerHTML = html;
    }

    // ── Autocomplete dropdown ─────────────────────────────────────
    async function showDropdown() {
      const q = fSearch.value.trim();
      if (q.length < 2) { dropdown.style.display = 'none'; return; }
      try {
        const res  = await fetch(`api/search_api.php?q=${encodeURIComponent(q)}&type=all`);
        const text = await res.text();
        let results;
        try { results = JSON.parse(text); } catch(e) { dropdown.style.display='none'; return; }
        if (!results.length) { dropdown.style.display = 'none'; return; }
        dropdown.innerHTML = results.map(r => {
          const href = (r.type === 'movie')
            ? `movie_details.php?id=${r.id}`
            : `${r.type}_details.php?id=${r.id}`;
          return `<div class="sd-item" onclick="location.href='${href}'">
            <div>
              <div class="name">${r.name}</div>
              ${r.meta ? `<div class="meta">${r.meta}</div>` : ''}
            </div>
            <span class="sd-badge">${r.type}</span>
          </div>`;
        }).join('');
        dropdown.style.display = 'block';
      } catch(e) { dropdown.style.display = 'none'; }
    }

    // ── Event listeners ───────────────────────────────────────────
    fSearch.addEventListener('input', () => {
      clearTimeout(searchTimer); searchTimer = setTimeout(() => updateVault(1), 350);
      clearTimeout(dropTimer);   dropTimer   = setTimeout(showDropdown, 300);
    });

    [fGenre, fLang, fMinRating, fSort, fOrder].forEach(el =>
      el.addEventListener('change', () => updateVault(1))
    );

    [fMinYear, fMaxYear].forEach(el => {
      el.addEventListener('change', () => updateVault(1));
      el.addEventListener('keyup', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => updateVault(1), 500);
      });
    });

    btnReset.addEventListener('click', () => {
      fSearch.value=''; fGenre.value=''; fLang.value='';
      fMinRating.value=''; fMinYear.value=''; fMaxYear.value='';
      fSort.value='release_year'; fOrder.value='DESC';
      updateVault(1);
    });

    pagCon.addEventListener('click', e => {
      const link = e.target.closest('.page-link');
      if (link && !link.classList.contains('disabled') && !link.classList.contains('active')) {
        e.preventDefault();
        updateVault(parseInt(link.dataset.page));
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });

    document.addEventListener('click', e => {
      if (!e.target.closest('.search-group')) dropdown.style.display = 'none';
    });

    // ── Inline styles for table links ─────────────────────────────
    const style = document.createElement('style');
    style.textContent = `
      .table-link { color: inherit; text-decoration: none; }
      .table-link:hover { color: var(--accent-primary); }
      .table-link-muted { color: var(--text-secondary); text-decoration: none; }
      .table-link-muted:hover { text-decoration: underline; }
    `;
    document.head.appendChild(style);

    // ── Initial load (Sync with URL params) ───────────────────────
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search'))     fSearch.value    = urlParams.get('search');
    if (urlParams.has('q'))          fSearch.value    = urlParams.get('q');
    if (urlParams.has('genre'))      fGenre.value     = urlParams.get('genre');
    if (urlParams.has('lang'))       fLang.value      = urlParams.get('lang');
    if (urlParams.has('min_rating')) fMinRating.value = urlParams.get('min_rating');
    if (urlParams.has('min_year'))   fMinYear.value   = urlParams.get('min_year');
    if (urlParams.has('max_year'))   fMaxYear.value   = urlParams.get('max_year');
    if (urlParams.has('sort'))       fSort.value      = urlParams.get('sort');
    if (urlParams.has('order'))      fOrder.value     = urlParams.get('order');
    
    currentPage = parseInt(urlParams.get('page')) || 1;
    updateVault(currentPage);
  });
  </script>
</body>
</html>
