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
                      <div class="film-name"><?= htmlspecialchars($movie['title']) ?></div>
                      <div class="film-meta"><?= $langLabel ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($movie['director']) ?></td>
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

      <div class="page-footer">THE CINEMATIC LENS &copy; 2026. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>
    </div>
  </main>
</body>
</html>
