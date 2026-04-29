<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$genreStats = $service->getGenreStats(6);
$langStats = $service->getLanguageStats(5);
$topGrossing = $service->getTopGrossingMovies(5);

$topGenre = $genreStats[0] ?? ['primary_genre' => 'Unknown', 'movie_count' => 0, 'total_revenue' => 0, 'avg_rating' => 0];
$runnerUp = $genreStats[1] ?? ['primary_genre' => 'Unknown', 'movie_count' => 0, 'total_revenue' => 0, 'avg_rating' => 0];
$totalMoviesSum = array_sum(array_column($genreStats, 'movie_count'));
$topSharePercent = $totalMoviesSum > 0 ? round(($topGenre['movie_count'] / $totalMoviesSum) * 100) : 0;

$service2 = new DataService();
$genreRatingBoard = $service2->getGenreRatingLeaderboard(6);
$genreRevPerFilm  = $service2->getGenreRevenuePerFilm(6);
$bestYearPerGenre = $service2->getBestYearPerGenre();

$langMap = ['hi' => 'Bollywood (Hindi)', 'ta' => 'Kollywood (Tamil)', 'te' => 'Tollywood (Telugu)', 'ml' => 'Mollywood (Malayalam)', 'kn' => 'Sandalwood (Kannada)', 'en' => 'English'];

$allGenres = $service->getSingleGenres();
$topFilmsPerGenre = $service->getTopFilmPerGenre(20);

$barColors = ['var(--accent-primary)', '#5cd6b6', '#6ea8fe', '#a68dff', '#ff8296', '#fbbf24', '#22d3ee', '#f97316'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Cinematic Lens - Genres</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div style="margin-bottom: 1.5rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">MARKET INTELLIGENCE</p>
        <h1 style="font-size: 2.25rem; font-weight: 800;">Genre & Regional <em style="color: var(--accent-primary); font-style: italic;">Dynamics</em></h1>
      </div>

      <!-- Intelligence Cards -->
      <div class="intel-grid">
        <div class="card intel-card" style="border-top: 2px solid var(--accent-primary);">
          <h3>TOP GENRE</h3>
          <h2><?= htmlspecialchars($topGenre['primary_genre']) ?></h2>
          <div class="trend-up">~ <?= $topSharePercent ?>% Market Share</div>
        </div>
        <div class="card intel-card">
          <h3>RUNNER UP</h3>
          <h2><?= htmlspecialchars($runnerUp['primary_genre']) ?></h2>
          <div class="text-muted text-xs"><?= number_format($runnerUp['movie_count']) ?> Titles</div>
        </div>
        <div class="card intel-card">
          <h3>TOP RATED GENRE</h3>
          <?php 
            $topRated = $genreStats[0];
            foreach ($genreStats as $g) {
                if ($g['avg_rating'] > $topRated['avg_rating']) $topRated = $g;
            }
          ?>
          <h2><?= htmlspecialchars($topRated['primary_genre']) ?></h2>
          <div class="trend-up">&#x2605; <?= number_format($topRated['avg_rating'], 1) ?> Avg</div>
        </div>
        <div class="card intel-card">
          <h3>TOTAL ANALYZED</h3>
          <h2><?= number_format($totalMoviesSum) ?></h2>
          <div class="text-muted text-xs">Films in Dataset</div>
        </div>
      </div>

      <!-- Middle: Revenue Chart + Language Breakdown -->
      <div class="genres-middle">
        <!-- Genre Revenue Breakdown -->
        <div class="card">
          <div class="chart-label">REVENUE BREAKDOWN</div>
          <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem;">Genre Revenue Distribution</h2>
          
          <?php 
            $maxRevenue = max(array_column($genreStats, 'total_revenue'));
            if ($maxRevenue == 0) $maxRevenue = 1;
          ?>
          <?php foreach (array_slice($genreStats, 0, 5) as $index => $stat): 
            $width = round(($stat['total_revenue'] / $maxRevenue) * 100);
            $color = $barColors[$index % count($barColors)];
          ?>
          <div class="region-chart-item">
            <div class="flex-row">
              <span class="font-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($stat['primary_genre']) ?></span>
              <span class="font-bold <?= $index === 0 ? 'text-accent' : 'text-secondary' ?>" style="font-size: 0.85rem;">
                &#x20B9;<?= formatRevenue($stat['total_revenue']) ?>
              </span>
            </div>
            <div class="region-bar-track"><div class="region-bar-fill" style="width: <?= $width ?>%; background-color: <?= $color ?>;"></div></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Regional/Language Stats -->
        <div class="card" style="position: relative; overflow: hidden;">
          <div style="position: absolute; right: -60px; bottom: -60px; width: 200px; height: 200px; border-radius: 50%; border: 35px solid rgba(255,255,255,0.02);"></div>
          <div class="chart-label">REGIONAL CINEMA</div>
          <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem;">Language Distribution</h2>
          
          <?php if (!empty($langStats)):
            $maxLang = max(array_column($langStats, 'movie_count'));
            $totalLang=array_sum(array_column($langStats, 'movie_count'));
            if ($maxLang == 0) $maxLang = 1;
          ?>
            <?php foreach ($langStats as $index => $lang):
              $langName = $langMap[$lang['language']] ?? ucfirst($lang['language']);
              $width = round(($lang['movie_count'] / $maxLang) * 100);
              $color = $barColors[$index % count($barColors)];
            ?>
            <div class="region-chart-item">
              <div class="flex-row">
                <span class="font-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($langName) ?></span>
                <span class="text-muted" style="font-size: 0.8rem;"><?= $lang['movie_count'] ?> films</span>
              </div>
              <div class="region-bar-track"><div class="region-bar-fill" style="width: <?= $width ?>%; background-color: <?= $color ?>;"></div></div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted text-sm">Connect database for language data.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Dynamic Genre Network Comparison Graph -->
      <div class="card" style="margin-bottom: 2rem;">
        <div class="chart-label">TREND INTELLIGENCE</div>
        <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem;">Multi-Genre Production Overlay</h2>
        
        <!-- Genre Selection Controls -->
        <div style="margin-bottom: 1.5rem;">
          <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 1rem;" id="selected-genres-list">
            <!-- Selected genres go here -->
          </div>
            
          <div style="display: flex; gap: 0.75rem; align-items: center;">
            <select id="genre-picker" class="builder-input" style="padding: 0.65rem 1rem; flex: 1; max-width: 300px; border-radius: 20px; background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);">
              <option value="">-- Select Genre --</option>
              <?php foreach ($allGenres as $g): ?>
                <option value="<?= htmlspecialchars($g['genre_name']) ?>"><?= htmlspecialchars($g['genre_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn-outline" onclick="addGenre()">Add Genre</button>
            <button class="btn-accent" onclick="updateGenreChart()">Update Graph</button>
            <div style="display: flex; gap: 0.4rem; margin-left: 0.5rem;">
              <button id="btn-bar" class="btn-accent" style="font-size: 0.7rem; padding: 0.4rem 0.75rem; border-radius: 20px;" onclick="setChartType('bar')">📊 Bar</button>
              <button id="btn-line" class="btn-outline" style="font-size: 0.7rem; padding: 0.4rem 0.75rem; border-radius: 20px;" onclick="setChartType('line')">📈 Line</button>
            </div>
          </div>
        </div>
        
        <div style="height: 350px;">
           <canvas id="genreTrendChart"></canvas>
        </div>
      </div>

      <!-- Top Grossing Films Table -->
      <div class="card">
        <div class="trending-header">
          <div>
            <div class="trending-label">BOX OFFICE</div>
            <h2 class="trending-title">Top Grossing Films</h2>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th>Film Title</th>
              <th>Director</th>
              <th>Genre</th>
              <th>Revenue</th>
              <th>IMDB Score</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($topGrossing)): ?>
              <?php foreach ($topGrossing as $movie):
                $primaryGenre = trim(explode(',', $movie['genres'] ?? 'Unknown')[0]);
                $genreClass = getGenreClass($primaryGenre);
                $langLabel = $langMap[$movie['language'] ?? ''] ?? ucfirst($movie['language'] ?? '');
              ?>
              <tr>
                <td>
                  <div class="film-cell">
                    <div class="film-poster">&#x1F3AC;</div>
                    <div>
                      <div class="film-name">
                        <a href="movie_details.php?id=<?= $movie['movie_id'] ?>" style="color:inherit; text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($movie['title']) ?></a>
                      </div>
                      <div class="film-meta"><?= $langLabel ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <a href="director_details.php?id=<?= $movie['director_id'] ?>" style="color:inherit; text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($movie['director']) ?></a>
                </td>
                <td><span class="genre-badge <?= $genreClass ?>"><?= htmlspecialchars(strtoupper($primaryGenre)) ?></span></td>
                <td class="font-bold">&#x20B9;<?= formatRevenue($movie['revenue']) ?></td>
                <td>
                  <div class="imdb-score">
                    <span class="imdb-star">&#x2605;</span>
                    <?= number_format($movie['rating_imdb'], 1) ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-muted" style="text-align:center; padding: 2rem;">No data available. Import CSV into database.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Genre Rating Leaderboard + Revenue Per Film -->
      <div class="genres-middle" style="margin-top: 1.5rem;">

        <!-- Genre by Avg Rating -->
        <div class="card">
          <div class="chart-label">CRITICAL ACCLAIM</div>
          <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem;">Genre Rating Leaderboard</h2>
          <?php
            $maxRating = !empty($genreRatingBoard) ? max(array_column($genreRatingBoard, 'avg_rating')) : 10;
            if ($maxRating == 0) $maxRating = 10;
          ?>
          <?php foreach ($genreRatingBoard as $index => $gr): ?>
          <div class="region-chart-item">
            <div class="flex-row">
              <span class="font-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($gr['genre_name']) ?></span>
              <span style="font-size: 0.8rem;">
                <span style="color: var(--accent-primary); font-weight: 700;">&#x2605; <?= $gr['avg_rating'] ?></span>
                <span style="color: var(--text-muted); margin-left: 6px;">(<?= $gr['movie_count'] ?> films)</span>
              </span>
            </div>
            <div class="region-bar-track">
              <div class="region-bar-fill" style="width: <?= round(($gr['avg_rating'] / $maxRating) * 100) ?>%; background-color: <?= $barColors[$index % count($barColors)] ?>;"></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($genreRatingBoard)): ?>
            <p class="text-muted text-sm">No rated genre data found.</p>
          <?php endif; ?>
        </div>

        <!-- Revenue Per Film by Genre -->
        <div class="card">
          <div class="chart-label">BOX OFFICE EFFICIENCY</div>
          <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem;">Avg Revenue Per Film by Genre</h2>
          <?php
            $maxRPF = !empty($genreRevPerFilm) ? max(array_column($genreRevPerFilm, 'avg_revenue_per_film')) : 1;
            if ($maxRPF == 0) $maxRPF = 1;
          ?>
          <?php foreach ($genreRevPerFilm as $index => $rpf): ?>
          <div class="region-chart-item">
            <div class="flex-row">
              <span class="font-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($rpf['genre_name']) ?></span>
              <span class="font-bold <?= $index === 0 ? 'text-accent' : 'text-secondary' ?>" style="font-size: 0.85rem;">&#x20B9;<?= formatRevenue($rpf['avg_revenue_per_film']) ?>/film</span>
            </div>
            <div class="region-bar-track">
              <div class="region-bar-fill" style="width: <?= round(($rpf['avg_revenue_per_film'] / $maxRPF) * 100) ?>%; background-color: <?= $barColors[$index % count($barColors)] ?>;"></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($genreRevPerFilm)): ?>
            <p class="text-muted text-sm">No revenue data found.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Best Year Per Genre Table -->
      <?php if (!empty($bestYearPerGenre)): ?>
      <div class="card" style="margin-top: 1.5rem;">
        <div class="trending-header">
          <div>
            <div class="trending-label">PEAK PERFORMANCE</div>
            <h2 class="trending-title">Best Box Office Year Per Genre</h2>
          </div>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Genre</th>
              <th>Peak Year</th>
              <th>Films That Year</th>
              <th>Total Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($bestYearPerGenre, 0, 8) as $byg): ?>
            <tr>
              <td><span class="genre-badge genre-action"><?= htmlspecialchars(strtoupper($byg['genre_name'])) ?></span></td>
              <td><?= $byg['best_year'] ?></td>
              <td><?= $byg['movie_count'] ?> films</td>
              <td class="font-bold">&#x20B9;<?= formatRevenue($byg['total_revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php if (!empty($topFilmsPerGenre)): ?>
      <div class="card" style="margin-top: 1.5rem;">
        <div class="trending-header">
          <div>
            <div class="trending-label">GENRE MASTERPIECES</div>
            <h2 class="trending-title">Highest Rated Film Per Genre</h2>
          </div>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Genre</th>
              <th>Film</th>
              <th>Director</th>
              <th>Year</th>
              <th style="text-align:right;">IMDb</th>
              <th style="text-align:right;">Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topFilmsPerGenre as $tfg): ?>
            <tr>
              <td><span class="genre-badge genre-default" style="font-size: 0.65rem;"><?= htmlspecialchars(strtoupper($tfg['genre_name'])) ?></span></td>
              <td>
                <a href="movie_details.php?id=<?= $tfg['movie_id'] ?>" style="color:var(--text-primary); font-weight:600; text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-primary)'"><?= htmlspecialchars($tfg['title']) ?></a>
              </td>
              <td>
                <a href="director_details.php?id=<?= $tfg['director_id'] ?>" style="color:var(--text-muted); text-decoration:none; font-size:0.82rem;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-muted)'"><?= htmlspecialchars($tfg['director_name']) ?></a>
              </td>
              <td style="color:var(--text-muted); font-size:0.82rem;"><?= $tfg['release_year'] ?></td>
              <td style="text-align:right; color:#f5c518; font-weight:700;">&#x2605; <?= number_format($tfg['rating_imdb'], 1) ?></td>
              <td style="text-align:right; color:var(--accent-green); font-weight:600;">&#x20B9;<?= formatRevenue($tfg['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    let genreChart = null;
    let currentChartType = 'bar';
    let lastChartLabels = [];
    let lastChartDatasets = [];
    let selectedGenresList = ['Action', 'Romance'];
    const barColors = ['rgba(245, 197, 24, 0.85)', 'rgba(92, 214, 182, 0.85)', 'rgba(110, 168, 254, 0.85)', 'rgba(166, 141, 255, 0.85)', 'rgba(255, 130, 150, 0.85)', 'rgba(251, 191, 36, 0.85)', 'rgba(34, 211, 238, 0.85)'];
    const borderColors = ['#f5c518', '#5cd6b6', '#6ea8fe', '#a68dff', '#ff8296', '#fbbf24', '#22d3ee'];

    function renderGenreChips() {
        const container = document.getElementById('selected-genres-list');
        container.innerHTML = selectedGenresList.map((g, i) => `
            <div style="background: var(--accent-glow); color: var(--accent-primary); border: 1px solid var(--accent-primary); padding: 0.35rem 0.85rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; animation: slideDown 0.2s ease-out;">
                ${g} <span style="cursor:pointer; font-size: 1.1rem; line-height: 1; opacity: 0.7;" onclick="removeGenre('${g}')">&times;</span>
            </div>
        `).join('');
    }

    function addGenre() {
        const picker = document.getElementById('genre-picker');
        const val = picker.value;
        if (val && !selectedGenresList.includes(val) && selectedGenresList.length < 7) {
            selectedGenresList.push(val);
            picker.value = '';
            renderGenreChips();
        } else if (selectedGenresList.length >= 7) {
            alert('Maximum 7 genres allowed at once.');
        }
    }

    function removeGenre(g) {
        selectedGenresList = selectedGenresList.filter(x => x !== g);
        renderGenreChips();
    }

    function setChartType(type) {
        currentChartType = type;
        document.getElementById('btn-bar').className = type === 'bar' ? 'btn-accent' : 'btn-outline';
        document.getElementById('btn-bar').style.cssText = 'font-size:0.7rem;padding:0.4rem 0.75rem;border-radius:20px;';
        document.getElementById('btn-line').className = type === 'line' ? 'btn-accent' : 'btn-outline';
        document.getElementById('btn-line').style.cssText = 'font-size:0.7rem;padding:0.4rem 0.75rem;border-radius:20px;';
        if (lastChartLabels.length > 0) renderGenreChart(lastChartLabels, lastChartDatasets);
    }

    function renderGenreChart(years, rawDatasets) {
        const ctx = document.getElementById('genreTrendChart').getContext('2d');
        if (genreChart) genreChart.destroy();
        const isLine = currentChartType === 'line';
        const datasets = rawDatasets.map((ds, i) => ({
            ...ds,
            type: currentChartType,
            fill: isLine ? false : undefined,
            tension: isLine ? 0.4 : undefined,
            borderWidth: isLine ? 2.5 : 1,
            pointRadius: isLine ? 3 : undefined,
            pointHoverRadius: isLine ? 5 : undefined,
            backgroundColor: isLine ? borderColors[i % borderColors.length].replace(')', ',0.15)').replace('rgba','rgba').replace('#','rgba(').replace('rgba(','rgba(') : barColors[i % barColors.length],
        }));
        genreChart = new Chart(ctx, {
            type: currentChartType,
            data: { labels: years, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top', labels: { color: '#8b8d9e', font: { size: 11, weight: 'bold' }, usePointStyle: true, padding: 15 } },
                    tooltip: { backgroundColor: '#1e1f2a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#f0f0f5', bodyColor: '#8b8d9e', padding: 10,
                        callbacks: { afterLabel: ctx => { const prev = ctx.dataset.data[ctx.dataIndex - 1]; if (prev && prev > 0) { const chg = Math.round(((ctx.raw - prev) / prev) * 100); return 'vs prev year: ' + (chg >= 0 ? '+' : '') + chg + '%'; } return ''; } } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#8b8d9e', font: { size: 10 }, maxRotation: 45, minRotation: 45 } },
                    y: { title: { display: true, text: 'Movies Released', color: '#6b7280', font: { size: 10, weight: 'bold' } }, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8b8d9e', font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    async function updateGenreChart() {
        if (selectedGenresList.length === 0) { alert('Please select at least one genre to plot.'); return; }

        const resp = await fetch('api_explore.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_genre_comparison', genres: selectedGenresList })
        });
        const result = await resp.json();
        
        if (result.status !== 'success' || !result.data || result.data.length === 0) return;
        
        const data = result.data;
        const years = data.map(d => parseInt(d.yr));
        
        const datasets = selectedGenresList.map((genre, i) => {
            const countKey = 'count' + i;
            const counts = data.map(d => parseInt(d[countKey] || 0));
            return {
                label: genre,
                data: counts,
                backgroundColor: barColors[i % barColors.length],
                borderColor: borderColors[i % borderColors.length],
                borderWidth: 1,
                borderRadius: 4
            };
        });
        
        lastChartLabels = years;
        lastChartDatasets = datasets;
        renderGenreChart(years, datasets);
    }
    
    window.addEventListener('load', function() {
        renderGenreChips();
        updateGenreChart();
    });
  </script>
</body>
</html>
