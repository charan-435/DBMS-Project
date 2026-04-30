<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$genres  = $service->getAllGenres();
$topDirectors = $service->getTopDirectorsDetailed(12);

// Get distinct languages
$langs = [];
try {
    $db = Database::getConnection();
    $s  = $db->query("SELECT DISTINCT language FROM Movies WHERE language IS NOT NULL ORDER BY language");
    $langs = $s->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e) { $langs = []; }

$avatarColors = [
    ['#e8a57e', '#d4845a'], ['#5cd6b6', '#3bb89a'],
    ['#6ea8fe', '#4a8ae0'], ['#a68dff', '#8565e0']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Cinematic Lens - Director Leaderboard</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .directors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }
    .director-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .director-card:hover {
      transform: translateY(-5px);
      border-color: var(--accent-primary);
      box-shadow: 0 10px 30px -10px rgba(129, 140, 248, 0.2);
    }
    .director-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      position: relative;
      z-index: 2;
    }
    .director-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .director-name {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text-primary);
    }
    .director-rank {
      position: absolute;
      top: -0.5rem;
      right: 0.5rem;
      font-size: 4rem;
      font-weight: 900;
      color: var(--accent-primary);
      opacity: 0.08;
      pointer-events: none;
      z-index: 1;
    }
    .director-stat-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.75rem;
      font-size: 0.85rem;
    }
    .director-stat-label {
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-size: 0.7rem;
      font-weight: 700;
    }
    .director-stat-value {
      color: var(--text-secondary);
      font-weight: 700;
    }

    /* Vault Styling */
    .vault-section {
        margin-top: 5rem;
        padding-top: 3rem;
        border-top: 1px solid var(--border-color);
    }
    .filter-panel {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.25rem 1.5rem;
        margin-bottom: 2rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: .35rem;
        min-width: 150px;
    }
    .filter-group label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
    }
    .filter-group input, .filter-group select {
        background: var(--bg-input);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: .5rem .75rem;
        border-radius: 6px;
        font-size: .85rem;
    }
    .vault-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1.25rem;
    }
    .vault-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        transition: all 0.2s;
    }
    .vault-card:hover {
        border-color: var(--accent-primary);
        transform: translateY(-2px);
    }
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 3rem;
    }
    .page-link {
        padding: 0.5rem 1rem;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }
    .page-link.active {
        background: var(--accent-primary);
        color: var(--bg-dark);
        border-color: var(--accent-primary);
    }
    .page-link.disabled { opacity: .4; pointer-events: none; }

    /* Chips & Overlay */
    .active-filters { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .filter-chip {
      display: inline-flex; align-items: center; gap: .4rem; padding: .25rem .7rem;
      background: rgba(126,175,232,.1); border: 1px solid rgba(126,175,232,.2);
      border-radius: 20px; font-size: .7rem; color: var(--accent-primary);
      font-weight: 600; cursor: pointer;
    }
    .vault-grid-wrap { position: relative; min-height: 400px; }
    #loading-overlay {
      position: absolute; inset: 0; background: rgba(10,10,15,0.7);
      display: none; align-items: center; justify-content: center;
      z-index: 10; border-radius: var(--radius-lg); backdrop-filter: blur(2px);
    }
    .spinner { width: 30px; height: 30px; border: 3px solid var(--border-color); border-top-color: var(--accent-primary); border-radius: 50%; animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div class="insight-header">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">DIRECTORIAL EXCELLENCE</p>
        <h1>Director<br>Leaderboard</h1>
        <p class="mt-4">The visionaries behind the lens—ranking the highest-rated and most commercially successful directors.</p>
      </div>

      <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem;">Top Rated <em style="color: var(--accent-primary); font-style: italic;">Actors</em></h2>
      
      <div class="directors-grid">
        <?php foreach ($topDirectors as $i => $director): 
          $c = $avatarColors[$i % count($avatarColors)];
        ?>
        <div class="director-card">
          <div class="director-rank">#<?= $i + 1 ?></div>
          <div class="director-header">
            <div class="director-avatar" style="background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
            <div>
              <div class="director-name"><?= htmlspecialchars($director['name']) ?></div>
              <div class="text-xxs text-accent font-bold mt-1">MASTER AUTEUR</div>
            </div>
          </div>
          
          <div class="director-stat-row">
            <span class="director-stat-label">Total Films</span>
            <span class="director-stat-value"><?= $director['total_films'] ?></span>
          </div>
          <div class="director-stat-row">
            <span class="director-stat-label">Avg Rating</span>
            <span class="director-stat-value"><?= number_format($director['avg_rating'], 1) ?></span>
          </div>
          <div class="director-stat-row">
            <span class="director-stat-label">Total Revenue</span>
            <span class="director-stat-value">&#x20B9;<?= formatRevenue($director['total_revenue']) ?></span>
          </div>
          
          <div style="margin-top: 1.5rem; position: relative; z-index: 2;">
            <a href="director_details.php?id=<?= $director['director_id'] ?>" class="btn-outline" style="width: 100%; text-align: center; display: block; font-size: 0.75rem;">View Directorial Profile</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Director Vault Section -->
      <div class="vault-section">
        <div style="margin-bottom: 2rem;">
            <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">DIRECTOR DIRECTORY</p>
            <h2 style="font-size: 2rem; font-weight: 800;">The Director <span style="color: var(--accent-primary);">Vault</span></h2>
            <p class="text-muted">Browse all directors in our database and filter by their cinematic contributions.</p>
        </div>

        <div class="filter-panel">
            <div class="filter-group" style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.1rem;">
                    <label>Search Name</label>
                    <a href="javascript:void(0)" onclick="openPowerAnalytics()" class="text-accent" style="font-size: 0.6rem; font-weight: 700; text-transform: uppercase; text-decoration: none;">Power Analytics ↗</a>
                </div>
                <input type="text" id="f-search" placeholder="Search directors...">
            </div>
            <div class="filter-group">
                <label>Genre</label>
                <select id="f-genre">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= $g['genre_id'] ?>"><?= htmlspecialchars($g['genre_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Language</label>
                <select id="f-lang">
                    <option value="">All Languages</option>
                    <?php foreach ($langs as $l): ?>
                        <option value="<?= htmlspecialchars($l) ?>"><?= htmlspecialchars(getLanguageName($l)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="min-width: 90px;">
                <label>Min Rating ★</label>
                <select id="f-min-rating">
                    <option value="">Any</option>
                    <option value="5">5.0+</option>
                    <option value="6">6.0+</option>
                    <option value="7">7.0+</option>
                    <option value="8">8.0+</option>
                </select>
            </div>
            <div class="filter-group" style="min-width: 80px;">
                <label>From Year</label>
                <input type="number" id="f-min-year" placeholder="1990" min="1950" max="2026">
            </div>
            <div class="filter-group" style="min-width: 80px;">
                <label>To Year</label>
                <input type="number" id="f-max-year" placeholder="2026" min="1950" max="2026">
            </div>
            <div class="filter-group">
                <label>Sort By</label>
                <select id="f-sort">
                    <option value="avg_rating" selected>Avg Rating</option>
                    <option value="total_revenue">Total Revenue</option>
                    <option value="total_films">Movie Count</option>
                    <option value="name">Name</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Order</label>
                <select id="f-order">
                    <option value="DESC">Descending</option>
                    <option value="ASC">Ascending</option>
                </select>
            </div>
            <button class="btn-primary" id="btn-reset" style="height: 38px;">RESET</button>
        </div>

        <div id="active-filters" class="active-filters"></div>

        <div class="vault-grid-wrap">
            <div id="loading-overlay"><div class="spinner"></div></div>
            <div id="vault-list" class="vault-grid">
                <!-- Dynamic Content -->
            </div>
        </div>

        <div id="pagination" class="pagination">
            <!-- Dynamic Content -->
        </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2026. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>
    </div>
  </main>

  <script>
    let currentPage = 1;
    const fSearch = document.getElementById('f-search');
    const fGenre = document.getElementById('f-genre');
    const fLang = document.getElementById('f-lang');
    const fMinRating = document.getElementById('f-min-rating');
    const fMinYear = document.getElementById('f-min-year');
    const fMaxYear = document.getElementById('f-max-year');
    const fSort = document.getElementById('f-sort');
    const fOrder = document.getElementById('f-order');
    const btnReset = document.getElementById('btn-reset');
    const vaultList = document.getElementById('vault-list');
    const pagination = document.getElementById('pagination');
    const overlay = document.getElementById('loading-overlay');
    const chipsCon = document.getElementById('active-filters');

    window.openPowerAnalytics = () => {
        const name = fSearch.value.trim();
        if(!name) { alert("Please enter a name to analyze."); return; }
        window.location.href = `explore.php?q=${encodeURIComponent(name)}`;
    };

    function renderChips() {
      const chips = [];
      if (fSearch.value.trim()) chips.push({ label: `"${fSearch.value.trim()}"`, clear: () => fSearch.value = '' });
      if (fGenre.value) chips.push({ label: fGenre.options[fGenre.selectedIndex].text, clear: () => fGenre.value = '' });
      if (fLang.value) chips.push({ label: fLang.options[fLang.selectedIndex].text, clear: () => fLang.value = '' });
      if (fMinRating.value) chips.push({ label: `★ ${fMinRating.value}+`, clear: () => fMinRating.value = '' });
      if (fMinYear.value) chips.push({ label: `From ${fMinYear.value}`, clear: () => fMinYear.value = '' });
      if (fMaxYear.value) chips.push({ label: `To ${fMaxYear.value}`, clear: () => fMaxYear.value = '' });
      
      chipsCon.innerHTML = chips.map((c, i) => 
        `<span class="filter-chip" data-idx="${i}">${c.label} ✕</span>`
      ).join('');
      
      chipsCon.querySelectorAll('.filter-chip').forEach(el => {
        el.onclick = () => {
          chips[el.dataset.idx].clear();
          loadVault(1);
        };
      });
    }

    async function loadVault(page = 1) {
        currentPage = page;
        overlay.style.display = 'flex';
        renderChips();

        const q = new URLSearchParams({
            type: 'director',
            page: page,
            search: fSearch.value,
            genre: fGenre.value,
            lang: fLang.value,
            min_rating: fMinRating.value,
            min_year: fMinYear.value,
            max_year: fMaxYear.value,
            sort: fSort.value,
            order: fOrder.value
        });

        // Sync URL
        const url = new URL(window.location);
        url.search = q.toString();
        window.history.replaceState({}, '', url);

        try {
            const res = await fetch(`api/people_api.php?${q}`);
            const data = await res.json();
            renderVault(data.results);
            renderPagination(data.total);
        } catch (e) {
            console.error(e);
            vaultList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #ef4444;">Error loading data.</div>';
        } finally {
            overlay.style.display = 'none';
        }
    }

    function renderVault(directors) {
        if (directors.length === 0) {
            vaultList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">No directors found matching your filters.</div>';
            return;
        }

        vaultList.innerHTML = directors.map(director => `
            <div class="vault-card">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-highlight); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">🎥</div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem;">${director.name}</div>
                        <div style="font-size: 0.65rem; color: var(--accent-primary); font-weight: 700; text-transform: uppercase;">${director.total_films} FILMS</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Avg Rating</span>
                    <span style="font-weight: 700; color: #f5c518;">★ ${parseFloat(director.avg_rating).toFixed(1)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 1rem;">
                    <span style="color: var(--text-muted);">Revenue</span>
                    <span style="font-weight: 700; color: var(--accent-green);">${director.revenue_fmt}</span>
                </div>
                <a href="director_details.php?id=${director.id}" class="btn-outline" style="width: 100%; text-align: center; display: block; font-size: 0.7rem; padding: 0.5rem;">VIEW PROFILE</a>
            </div>
        `).join('');
    }

    function renderPagination(total) {
        const totalPages = Math.ceil(total / 12);
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let html = '';
        const start = Math.max(1, currentPage - 2);
        const end = Math.min(totalPages, currentPage + 2);

        html += `<button class="page-link ${currentPage === 1 ? 'disabled' : ''}" onclick="loadVault(${currentPage - 1})">←</button>`;
        
        if (start > 1) html += `<button class="page-link" onclick="loadVault(1)">1</button>${start > 2 ? '...' : ''}`;
        
        for (let i = start; i <= end; i++) {
            html += `<button class="page-link ${i === currentPage ? 'active' : ''}" onclick="loadVault(${i})">${i}</button>`;
        }

        if (end < totalPages) html += `${end < totalPages - 1 ? '...' : ''}<button class="page-link" onclick="loadVault(${totalPages})">${totalPages}</button>`;
        
        html += `<button class="page-link ${currentPage === totalPages ? 'disabled' : ''}" onclick="loadVault(${currentPage + 1})">→</button>`;

        pagination.innerHTML = html;
    }

    [fSearch, fGenre, fLang, fMinRating, fMinYear, fMaxYear, fSort, fOrder].forEach(el => {
        el.addEventListener('input', () => {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => loadVault(1), 350);
        });
    });
 
    btnReset.addEventListener('click', () => {
        fSearch.value = '';
        fGenre.value = '';
        fLang.value = '';
        fMinRating.value = '';
        fMinYear.value = '';
        fMaxYear.value = '';
        fSort.value = 'avg_rating';
        fOrder.value = 'DESC';
        loadVault(1);
    });

    // Sync from URL on load
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) fSearch.value = urlParams.get('search');
    if (urlParams.has('genre')) fGenre.value = urlParams.get('genre');
    if (urlParams.has('lang')) fLang.value = urlParams.get('lang');
    if (urlParams.has('min_rating')) fMinRating.value = urlParams.get('min_rating');
    if (urlParams.has('min_year')) fMinYear.value = urlParams.get('min_year');
    if (urlParams.has('max_year')) fMaxYear.value = urlParams.get('max_year');
    if (urlParams.has('sort')) fSort.value = urlParams.get('sort');
    if (urlParams.has('order')) fOrder.value = urlParams.get('order');
    currentPage = parseInt(urlParams.get('page')) || 1;

    loadVault(currentPage);
  </script>
</body>
</html>
