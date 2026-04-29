<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$genres  = $service->getAllGenres();
$topActors = $service->getTopActorsDetailed(12);
$mostVersatile = $service->getActorGenreVersatility(4);
$revenueKings = $service->getTopActorsByRevenue(4);

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
  <title>The Cinematic Lens - Actors Leaderboard</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .actors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
      margin-bottom: 4rem;
    }
    .actor-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 1.75rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 280px;
    }
    .actor-card:hover {
      transform: translateY(-5px);
      border-color: var(--accent-primary);
      box-shadow: 0 10px 30px -10px rgba(129, 140, 248, 0.2);
    }
    .actor-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      position: relative;
      z-index: 2;
    }
    .actor-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .actor-name {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text-primary);
    }
    .actor-rank {
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
    .actor-stat-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.75rem;
      font-size: 0.85rem;
    }
    .actor-stat-label {
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-size: 0.7rem;
      font-weight: 700;
    }
    .actor-stat-value {
      color: var(--text-secondary);
      font-weight: 700;
    }
    .highlight-section {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 3rem;
    }
    @media (max-width: 768px) {
      .highlight-section { grid-template-columns: 1fr; }
    }

    /* Vault Styling */
    .vault-section {
        margin-top: 5rem;
        padding-top: 3rem;
        border-top: 1px solid var(--border-color);
        margin-bottom: 5rem;
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
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    .vault-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        min-height: 220px;
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
    }
    .page-link.active {
        background: var(--accent-primary);
        color: var(--bg-dark);
        border-color: var(--accent-primary);
    }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div class="insight-header">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">PERFORMANCE ANALYTICS</p>
        <h1>Actor<br>Leaderboard</h1>
        <p class="mt-4">From box office titans to critical darlings—exploring the actors who define Indian cinema.</p>
      </div>

      <!-- Highlights -->
      <div class="highlight-section">
        <!-- Box Office Kings -->
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.1rem; font-weight: 700;">Box Office Kings</h2>
            <span style="color: var(--accent-green);">&#x1F4B0;</span>
          </div>
          <?php foreach ($revenueKings as $index => $actor): 
            $c = $avatarColors[$index % count($avatarColors)];
          ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
               <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
               <div>
                 <div class="font-semibold text-sm">
                   <?php if (isset($actor['actor_id'])): ?>
                     <a href="actor_details.php?id=<?= $actor['actor_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($actor['actor']) ?></a>
                   <?php else: ?>
                     <?= htmlspecialchars($actor['actor']) ?>
                   <?php endif; ?>
                 </div>
                 <div class="text-xxs text-muted"><?= $actor['movie_count'] ?> BLOCKED BUSTERS</div>
               </div>
            </div>
            <div style="text-align: right;">
              <div class="text-sm font-bold text-accent">&#x20B9;<?= formatRevenue($actor['total_revenue']) ?></div>
              <div class="text-xxs text-muted">TOTAL REVENUE</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Most Versatile -->
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.1rem; font-weight: 700;">Genre Versatility</h2>
            <span style="color: var(--accent-primary);">&#x1F3AD;</span>
          </div>
          <?php foreach ($mostVersatile as $index => $actor): 
            $c = $avatarColors[($index + 2) % count($avatarColors)];
          ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
               <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
               <div>
                 <div class="font-semibold text-sm">
                   <a href="actor_details.php?id=<?= $actor['actor_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($actor['actor']) ?></a>
                 </div>
                 <div class="text-xxs text-muted"><?= $actor['genre_count'] ?> UNIQUE GENRES</div>
               </div>
            </div>
            <div style="text-align: right;">
              <div class="text-sm font-bold" style="color: var(--accent-green);"><?= isset($actor['avg_rating']) ? number_format($actor['avg_rating'], 1) : '—' ?></div>
              <div class="text-xxs text-muted">AVG RATING</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem;">Top Prolific <em style="color: var(--accent-primary); font-style: italic;">Actors</em></h2>
      
      <div class="actors-grid">
        <?php foreach ($topActors as $i => $actor): 
          $c = $avatarColors[$i % count($avatarColors)];
        ?>
        <div class="actor-card">
          <div class="actor-rank">#<?= $i + 1 ?></div>
          <div class="actor-header">
            <div class="actor-avatar" style="background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
            <div>
              <div class="actor-name"><?= htmlspecialchars($actor['name']) ?></div>
              <div class="text-xxs text-accent font-bold mt-1">ESTABLISHED STAR</div>
            </div>
          </div>
          
          <div class="actor-stat-row">
            <span class="actor-stat-label">Total Films</span>
            <span class="actor-stat-value"><?= $actor['movie_count'] ?></span>
          </div>
          <div class="actor-stat-row">
            <span class="actor-stat-label">Avg Rating</span>
            <span class="actor-stat-value"><?= number_format($actor['avg_rating'], 1) ?></span>
          </div>
          <div class="actor-stat-row">
            <span class="actor-stat-label">Total Revenue</span>
            <span class="actor-stat-value">&#x20B9;<?= formatRevenue($actor['total_revenue']) ?></span>
          </div>
          
          <div style="margin-top: auto; padding-top: 1.5rem; position: relative; z-index: 2;">
            <a href="actor_details.php?id=<?= $actor['actor_id'] ?>" class="btn-outline" style="width: 100%; text-align: center; display: block; font-size: 0.75rem;">View Career Profile</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Actor Vault Section -->
      <div class="vault-section">
        <div style="margin-bottom: 2rem;">
            <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">ACTOR DIRECTORY</p>
            <h2 style="font-size: 2rem; font-weight: 800;">The Actor <span style="color: var(--accent-primary);">Vault</span></h2>
            <p class="text-muted">Explore the complete database of actors across all genres and eras.</p>
        </div>

        <div class="filter-panel">
            <div class="filter-group" style="flex: 1;">
                <label>Search Name</label>
                <input type="text" id="f-search" placeholder="Search actors..." autocomplete="off">
            </div>
            <div class="filter-group">
                <label>Genre Association</label>
                <select id="f-genre">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= $g['genre_id'] ?>"><?= htmlspecialchars($g['genre_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="min-width: 100px;">
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
                    <option value="total_revenue">Total Revenue</option>
                    <option value="avg_rating">Avg Rating</option>
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

        <div id="vault-list" class="vault-grid">
            <!-- Dynamic Content -->
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
    const fMinRating = document.getElementById('f-min-rating');
    const fMinYear = document.getElementById('f-min-year');
    const fMaxYear = document.getElementById('f-max-year');
    const fSort = document.getElementById('f-sort');
    const fOrder = document.getElementById('f-order');
    const btnReset = document.getElementById('btn-reset');
    const vaultList = document.getElementById('vault-list');
    const pagination = document.getElementById('pagination');

    async function loadVault(page = 1) {
        currentPage = page;
        const q = new URLSearchParams({
            type: 'actor',
            page: page,
            search: fSearch.value,
            genre: fGenre.value,
            min_rating: fMinRating.value,
            min_year: fMinYear.value,
            max_year: fMaxYear.value,
            sort: fSort.value,
            order: fOrder.value
        });

        vaultList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">Syncing Actor Data...</div>';

        try {
            const res = await fetch(`api/people_api.php?${q}`);
            const data = await res.json();
            renderVault(data.results);
            renderPagination(data.total);
        } catch (e) {
            console.error(e);
        }
    }

    function renderVault(actors) {
        if (actors.length === 0) {
            vaultList.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">No actors found matching your filters.</div>';
            return;
        }

        vaultList.innerHTML = actors.map(actor => `
            <div class="vault-card">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-highlight); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">🎭</div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem;">${actor.name}</div>
                        <div style="font-size: 0.65rem; color: var(--accent-primary); font-weight: 700; text-transform: uppercase;">${actor.total_films} FILMS</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Avg Rating</span>
                    <span style="font-weight: 700; color: #f5c518;">★ ${parseFloat(actor.avg_rating).toFixed(1)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 1rem;">
                    <span style="color: var(--text-muted);">Revenue</span>
                    <span style="font-weight: 700; color: var(--accent-green);">${actor.revenue_fmt}</span>
                </div>
                <a href="actor_details.php?id=${actor.id}" class="btn-outline" style="width: 100%; text-align: center; display: block; font-size: 0.7rem; padding: 0.5rem; margin-top: auto;">VIEW PROFILE</a>
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

        if (start > 1) html += `<button class="page-link" onclick="loadVault(1)">1</button>${start > 2 ? '...' : ''}`;
        
        for (let i = start; i <= end; i++) {
            html += `<button class="page-link ${i === currentPage ? 'active' : ''}" onclick="loadVault(${i})">${i}</button>`;
        }

        if (end < totalPages) html += `${end < totalPages - 1 ? '...' : ''}<button class="page-link" onclick="loadVault(${totalPages})">${totalPages}</button>`;

        pagination.innerHTML = html;
    }

    [fSearch, fGenre, fMinRating, fMinYear, fMaxYear, fSort, fOrder].forEach(el => {
        el.addEventListener('input', () => {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => loadVault(1), 300);
        });
    });

    btnReset.addEventListener('click', () => {
        fSearch.value = '';
        fGenre.value = '';
        fMinRating.value = '';
        fMinYear.value = '';
        fMaxYear.value = '';
        fSort.value = 'total_revenue';
        fOrder.value = 'DESC';
        loadVault(1);
    });

    loadVault(1);
  </script>
</body>
</html>
