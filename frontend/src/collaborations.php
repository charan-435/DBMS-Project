<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// Top actor-director duos
$duos = $service->getActorDirectorCollaborations(8);

// Actors spanning multiple genres
$versatile = $service->getActorGenreVersatility(6);

// Actors who frequently appear together
$repeatActors = $service->getRepeatCollaborators(2, 8);

// Top directors for the network
$topDirs = $service->getTopDirectorsByCount(5);

// New: Top actors by revenue + actors who work with most directors
$actorsByRevenue = $service->getTopActorsByRevenue(6);
$actorsDirDiversity = $service->getActorCollaborationCount(6);

$barColors = ['var(--accent-primary)', '#5cd6b6', '#6ea8fe', '#a68dff', '#ff8296', '#fbbf24', '#22d3ee', '#f97316'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Collaborations — The Cinematic Lens</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .collab-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .collab-grid-equal { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }

    .duo-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.85rem 0; border-bottom: 1px solid var(--border-color);
    }
    .duo-item:last-child { border-bottom: none; }
    .duo-left { display: flex; align-items: center; gap: 0.75rem; }
    .duo-avatar {
      width: 40px; height: 40px; border-radius: 50%; display: flex;
      align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
    }
    .duo-director { font-weight: 600; font-size: 0.88rem; }
    .duo-actor { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.1rem; }
    .duo-stats { text-align: right; }
    .duo-films { font-weight: 700; font-size: 1rem; }
    .duo-revenue { font-size: 0.7rem; color: var(--accent-green); }

    .versatile-item {
      padding: 0.85rem 0; border-bottom: 1px solid var(--border-color);
    }
    .versatile-item:last-child { border-bottom: none; }
    .versatile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
    .versatile-name { font-weight: 600; font-size: 0.88rem; }
    .versatile-count { font-weight: 700; color: var(--accent-primary); font-size: 0.85rem; }
    .versatile-genres { display: flex; flex-wrap: wrap; gap: 0.3rem; }
    .genre-chip {
      background: rgba(255,255,255,0.06); padding: 0.15rem 0.45rem;
      border-radius: 3px; font-size: 0.65rem; color: var(--text-secondary); font-weight: 500;
    }

    .pair-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);
    }
    .pair-item:last-child { border-bottom: none; }
    .pair-names { display: flex; align-items: center; gap: 0.5rem; }
    .pair-connector { color: var(--accent-primary); font-weight: 700; font-size: 0.75rem; }
    .pair-count {
      background: var(--accent-glow); color: var(--accent-primary); font-weight: 700;
      padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem;
    }

    .network-card { position: relative; min-height: 320px; }
    .network-node {
      position: absolute; text-align: center; transition: transform 0.3s;
    }
    .network-node:hover { transform: scale(1.1); }
    .network-circle {
      width: 50px; height: 50px; border-radius: 50%; margin: 0 auto 0.4rem;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.7rem; font-weight: 700; color: #fff; border: 2px solid rgba(255,255,255,0.1);
    }
    .network-label { font-size: 0.65rem; color: var(--text-secondary); max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .network-sublabel { font-size: 0.55rem; color: var(--text-muted); }

    .section-label {
      font-size: 0.65rem; font-weight: 700; letter-spacing: 0.15em;
      text-transform: uppercase; color: var(--accent-primary); margin-bottom: 0.3rem;
    }
    .card-title-collab { font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">

      <!-- Header -->
      <div style="margin-bottom: 1.5rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">RELATIONSHIP INTELLIGENCE</p>
        <h1 style="font-size: 2.25rem; font-weight: 800;">Collaboration <em style="color: var(--accent-primary); font-style: italic;">Networks</em></h1>
        <p class="mt-2" style="color: var(--text-secondary); font-size: 0.88rem; max-width: 600px;">
          Mapping the creative bonds that drive box office success — director-actor duos, actor pairs, and genre versatility across Indian cinema.
        </p>
      </div>

      <!-- Row 1: Director-Actor Duos + Network Visualization -->
      <div class="collab-grid">
        <!-- Dynamic Duos -->
        <div class="card">
          <div class="section-label">POWER PARTNERSHIPS</div>
          <div class="card-title-collab">Top Director-Actor Duos</div>

          <?php if (!empty($duos)): ?>
            <?php foreach ($duos as $i => $duo): ?>
            <div class="duo-item">
              <div class="duo-left">
                <div style="display:flex; flex-direction:column; justify-content:center; padding-left: 0.5rem; border-left: 3px solid <?= $barColors[$i % count($barColors)] ?>;">
                  <div class="duo-director" style="font-size: 1rem;"><?= htmlspecialchars($duo['director']) ?></div>
                  <div class="duo-actor">&amp; <?= htmlspecialchars($duo['actor']) ?></div>
                </div>
              </div>
              <div class="duo-stats">
                <div class="duo-films"><?= $duo['count'] ?> films</div>
                <?php if (isset($duo['avg_revenue'])): ?>
                  <div class="duo-revenue">&#x20B9;<?= formatRevenue($duo['avg_revenue']) ?> avg</div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No collaboration data available. Populate the database first.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Row 2: Actor Pairs -->
      <div style="margin-bottom: 1.5rem;">
        <!-- Repeat Actor Pairs -->
        <div class="card">
          <div class="section-label">FREQUENT CO-STARS</div>
          <div class="card-title-collab">Actors Who Work Together</div>

          <?php if (!empty($repeatActors)): ?>
            <?php foreach ($repeatActors as $pair): ?>
            <div class="pair-item">
              <div class="pair-names">
                <span class="font-semibold" style="font-size:0.85rem;"><?= htmlspecialchars($pair['actor1']) ?></span>
                <span class="pair-connector">×</span>
                <span class="font-semibold" style="font-size:0.85rem;"><?= htmlspecialchars($pair['actor2']) ?></span>
              </div>
              <span class="pair-count"><?= $pair['films_together'] ?> films</span>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No repeat collaborators found (need actors appearing in 2+ films together).</p>
          <?php endif; ?>
        </div>
      </div>
      <!-- New Row: Actor Revenue Rankings + Director Diversity -->
      <div class="collab-grid-equal" style="margin-top: 0;">

        <!-- Top Actors by Total Box Office -->
        <div class="card">
          <div class="section-label">BOX OFFICE KINGS</div>
          <div class="card-title-collab">Actors by Total Revenue</div>
          <?php if (!empty($actorsByRevenue)): ?>
            <?php
              $maxActRev = max(array_column($actorsByRevenue, 'total_revenue'));
              if ($maxActRev == 0) $maxActRev = 1;
            ?>
            <?php foreach ($actorsByRevenue as $i => $ar): ?>
            <div style="padding: 0.7rem 0; border-bottom: 1px solid var(--border-color);">
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                <span style="font-weight: 600; font-size: 0.88rem;"><?= htmlspecialchars($ar['actor']) ?></span>
                <span style="font-weight: 700; color: var(--accent-green); font-size: 0.85rem;">&#x20B9;<?= formatRevenue($ar['total_revenue']) ?></span>
              </div>
              <div style="height: 3px; background: var(--border-color); border-radius: 2px;">
                <div style="height: 3px; width: <?= round(($ar['total_revenue'] / $maxActRev) * 100) ?>%; background: <?= $barColors[$i % count($barColors)] ?>; border-radius: 2px;"></div>
              </div>
              <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;"><?= $ar['movie_count'] ?> films &bull; &#x2605; <?= number_format($ar['avg_rating'], 1) ?> avg</div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No actor revenue data available.</p>
          <?php endif; ?>
        </div>

        <!-- Actors with Most Unique Directors -->
        <div class="card">
          <div class="section-label">CREATIVE RANGE</div>
          <div class="card-title-collab">Actors Across Most Directors</div>
          <?php if (!empty($actorsDirDiversity)): ?>
            <?php foreach ($actorsDirDiversity as $i => $ad): ?>
            <div class="duo-item">
              <div class="duo-left">
                <div style="display:flex; flex-direction:column; justify-content:center; padding-left: 0.5rem; border-left: 3px solid <?= $barColors[$i % count($barColors)] ?>;">
                  <div class="duo-director" style="font-size: 1rem;"><?= htmlspecialchars($ad['actor']) ?></div>
                  <div class="duo-actor"><?= $ad['total_films'] ?> films total</div>
                </div>
              </div>
              <div class="duo-stats">
                <div class="duo-films"><?= $ad['unique_directors'] ?></div>
                <div class="duo-revenue">unique directors</div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No diversity data available.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Custom Collaboration Analyzer -->
      <div class="card" style="margin-top: 1.5rem; border: 1px solid var(--accent-glow);">
        <div class="section-label">CUSTOM ANALYZER</div>
        <div class="card-title-collab">Analyze Specific Collaborations</div>
        <p class="text-secondary" style="font-size: 0.85rem; margin-bottom: 1.25rem;">
          Select multiple actors and/or directors to find movies they worked on together and view collective statistics.
        </p>

        <div style="position: relative; margin-bottom: 1rem;">
          <input type="text" id="person-search" class="builder-input" placeholder="Type name (e.g. Aamir Khan, Rajkumar Hirani)..." style="width: 100%; padding: 0.8rem; border-radius: 8px;">
          <div id="search-dropdown" class="card" style="position: absolute; top: 110%; left: 0; right: 0; z-index: 1000; display: none; padding: 0.5rem; background: #11121a; border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.5);"></div>
        </div>

        <div id="selected-list" style="display: flex; flex-wrap: wrap; gap: 0.6rem; margin-bottom: 1.5rem;">
          <!-- Selected chips go here -->
        </div>

        <div style="display: flex; gap: 1rem;">
          <button id="btn-analyze" class="btn-outline" style="flex: 1; padding: 0.8rem; font-weight: 700; border-color: var(--accent-primary); color: var(--accent-primary);">Run Analysis</button>
          <button id="btn-clear" class="btn-outline" style="padding: 0.8rem; border-color: #ef4444; color: #ef4444;">Clear</button>
        </div>

        <!-- Progress/Status -->
        <div id="analysis-status" style="display: none; margin-top: 1rem; text-align: center; font-size: 0.8rem; color: var(--text-muted);">
          Analyzing collaboration data...
        </div>

        <!-- Results Section -->
        <div id="analysis-results-box" style="display: none; margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 2rem;">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div class="card" style="background: rgba(255,255,255,0.02); text-align: center; padding: 1rem;">
              <div class="section-label" style="margin-bottom: 0.2rem;">TOTAL FILMS</div>
              <div id="stat-count" style="font-size: 1.5rem; font-weight: 800;">0</div>
            </div>
            <div class="card" style="background: rgba(255,255,255,0.02); text-align: center; padding: 1rem;">
              <div class="section-label" style="margin-bottom: 0.2rem;">AVG RATING</div>
              <div id="stat-rating" style="font-size: 1.5rem; font-weight: 800; color: var(--accent-primary);">0.0</div>
            </div>
            <div class="card" style="background: rgba(255,255,255,0.02); text-align: center; padding: 1rem;">
              <div class="section-label" style="margin-bottom: 0.2rem;">COMBINED REVENUE</div>
              <div id="stat-revenue" style="font-size: 1.3rem; font-weight: 800; color: var(--accent-green);">&#x20B9;0</div>
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1rem; font-weight: 700;">Collaboration History</h3>
            <button id="btn-download-csv" class="btn-outline" style="font-size: 0.7rem; padding: 0.4rem 1rem; background: var(--accent-primary); color: #000; border: none; font-weight: 800;">DOWNLOAD CSV</button>
          </div>

          <table class="data-table" id="results-table">
            <thead>
              <tr><th>Movie Title</th><th>Year</th><th>Director</th><th style="text-align:right;">Revenue</th><th style="text-align:right;">Rating</th></tr>
            </thead>
            <tbody>
              <!-- Results dynamic -->
            </tbody>
          </table>
        </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2026. COLLABORATION INTELLIGENCE ENGINE.</div>
    </div>
  </main>

  <script>
    const searchInput = document.getElementById('person-search');
    const dropdown = document.getElementById('search-dropdown');
    const selectedList = document.getElementById('selected-list');
    const btnAnalyze = document.getElementById('btn-analyze');
    const btnClear = document.getElementById('btn-clear');
    const resultsBox = document.getElementById('analysis-results-box');
    const btnDownload = document.getElementById('btn-download-csv');

    let selectedPeople = [];
    let currentData = null;

    // Search functionality
    let searchTimeout;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      const q = searchInput.value.trim();
      if (q.length < 2) {
        dropdown.style.display = 'none';
        return;
      }

      searchTimeout = setTimeout(async () => {
        try {
          const resp = await fetch(`api/search_api.php?q=${encodeURIComponent(q)}&type=all`);
          const results = await resp.json();
          renderDropdown(results.filter(r => r.type === 'actor' || r.type === 'director'));
        } catch (e) { console.error(e); }
      }, 300);
    });

    function renderDropdown(items) {
      if (items.length === 0) {
        dropdown.innerHTML = '<div style="padding: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">No people found.</div>';
      } else {
        dropdown.innerHTML = items.map(item => `
          <div class="search-result-item" style="padding: 0.6rem; cursor: pointer; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;" 
               onclick="selectPerson(${item.id}, '${item.name.replace(/'/g, "\\'")}', '${item.type}')">
            <span style="font-size: 0.85rem; font-weight: 600;">${item.name}</span>
            <span style="font-size: 0.65rem; background: ${item.type==='director'?'#4f46e5':'#ec4899'}; color: #fff; padding: 1px 5px; border-radius: 3px; text-transform: uppercase;">${item.type}</span>
          </div>
        `).join('');
      }
      dropdown.style.display = 'block';
    }

    function selectPerson(id, name, type) {
      if (selectedPeople.find(p => p.id === id && p.type === type)) {
        searchInput.value = '';
        dropdown.style.display = 'none';
        return;
      }
      selectedPeople.push({id, name, type});
      renderSelected();
      searchInput.value = '';
      dropdown.style.display = 'none';
    }

    function removePerson(id, type) {
      selectedPeople = selectedPeople.filter(p => !(p.id === id && p.type === type));
      renderSelected();
    }

    function renderSelected() {
      selectedList.innerHTML = selectedPeople.map(p => `
        <div style="background: var(--accent-glow); color: var(--accent-primary); border: 1px solid var(--accent-primary); padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
          ${p.name} <span style="font-size: 0.6rem; opacity: 0.7;">(${p.type.toUpperCase()})</span>
          <span style="cursor: pointer; font-size: 1rem; line-height: 1;" onclick="removePerson(${p.id}, '${p.type}')">&times;</span>
        </div>
      `).join('');
    }

    btnClear.addEventListener('click', () => {
      selectedPeople = [];
      renderSelected();
      resultsBox.style.display = 'none';
      currentData = null;
    });

    btnAnalyze.addEventListener('click', async () => {
      if (selectedPeople.length === 0) {
        alert('Please select at least one person.');
        return;
      }

      document.getElementById('analysis-status').style.display = 'block';
      resultsBox.style.display = 'none';

      try {
        const resp = await fetch('api/collaboration_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ people: selectedPeople })
        });
        const result = await resp.json();
        document.getElementById('analysis-status').style.display = 'none';

        if (result.status === 'success') {
          currentData = result.data;
          renderResults(result.data);
        } else {
          alert('Error: ' + result.message);
        }
      } catch (e) {
        document.getElementById('analysis-status').style.display = 'none';
        alert('Analysis failed. Please try again.');
        console.error(e);
      }
    });

    function renderResults(data) {
      document.getElementById('stat-count').textContent = data.stats.count;
      document.getElementById('stat-rating').textContent = data.stats.avg_rating;
      document.getElementById('stat-revenue').innerHTML = '&#x20B9;' + formatRevenueJS(data.stats.total_revenue);

      const tbody = document.querySelector('#results-table tbody');
      if (data.movies.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--text-muted);">No movies found for this specific combination.</td></tr>';
      } else {
        tbody.innerHTML = data.movies.map(m => `
          <tr>
            <td style="font-weight: 700;"><a href="movie_details.php?id=${m.movie_id}" style="color: inherit; text-decoration: none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'">${m.title}</a></td>
            <td>${m.release_year}</td>
            <td>${m.director}</td>
            <td style="text-align:right; font-weight:600; color: var(--accent-green);">&#x20B9;${formatRevenueJS(m.revenue)}</td>
            <td style="text-align:right;"><span style="color: var(--accent-primary);">&#x2605;</span> ${parseFloat(m.rating_imdb).toFixed(1)}</td>
          </tr>
        `).join('');
      }
      resultsBox.style.display = 'block';
    }

    function formatRevenueJS(val) {
      if (val >= 10000000) return (val/10000000).toFixed(1) + ' Cr';
      if (val >= 100000) return (val/100000).toFixed(1) + ' L';
      return val.toLocaleString();
    }

    btnDownload.addEventListener('click', () => {
      if (!currentData || currentData.movies.length === 0) return;
      
      let csv = 'Movie Title,Release Year,Director,Revenue,IMDb Rating\n';
      currentData.movies.forEach(m => {
        csv += `"${m.title}",${m.release_year},"${m.director}",${m.revenue},${m.rating_imdb}\n`;
      });

      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.setAttribute('href', url);
      a.setAttribute('download', `collaboration_data_${new Date().getTime()}.csv`);
      a.click();
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
      if (e.target !== searchInput && e.target !== dropdown) {
        dropdown.style.display = 'none';
      }
    });
  </script>
</body>
</html>
