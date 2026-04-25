<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// Dashboard Stats
$avgRating = $service->getAvgRating();
$totalRevenue = $service->getTotalRevenue();
$mostActiveGenre = $service->getMostActiveGenre();
$totalMovies = $service->getTotalMovies();

// Genre Trend (Action vs Romance by year)
$genreTrend = $service->getGenreTrend();

// Trending Movies
$trending = $service->getTrendingMovies(5);

// New: Top directors & recent acclaimed
$topDirsByCount = $service->getTopDirectorsByCount(5);
$recentAcclaimed = $service->getRecentHighRated(4, 2018);
$yearlyRevenue = $service->getYearlyRevenueTrend(2010);

// Fallbacks—show real data or empty state
if ($avgRating == 0) $avgRating = null;
$revenueFormatted = $totalRevenue > 0 ? '&#x20B9;' . formatRevenue($totalRevenue) : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="The Cinematic Lens - A premium film analytics dashboard exploring Indian cinema through data.">
  <title>The Cinematic Lens - Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">

      <!-- HERO BANNER -->
      <div class="hero-banner">
        <div class="hero-label">FEATURED PERSPECTIVE</div>
        <h1 class="hero-title">Cinema at a Glance</h1>
        <p class="hero-desc">
          Tracing the soul of Indian storytelling through two decades of metadata, box office triumphs, and the eternal clash of Action vs. Romance.
        </p>
        <div class="hero-actions">
          <a href="genres.php" class="btn-accent">Explore Trends &#x2197;</a>
          <a href="industry.php" class="btn-outline">Regional Insights</a>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card-header">
            <span class="stat-card-label">AVG IMDB RATING</span>
            <div class="stat-card-icon">&#x2B50;</div>
          </div>
          <div class="stat-card-value"><?= number_format($avgRating, 2) ?></div>
          <div class="stat-card-sub">&#x2191; +0.3 vs last decade</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-header">
            <span class="stat-card-label">TOTAL BOX OFFICE</span>
            <div class="stat-card-icon">&#x1F3AC;</div>
          </div>
          <div class="stat-card-value"><?= $revenueFormatted ?></div>
          <div class="stat-card-sub">&#x2191; Growth in OTT licensing</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-header">
            <span class="stat-card-label">MOST ACTIVE GENRE</span>
            <div class="stat-card-icon">&#x1F3AD;</div>
          </div>
          <div class="stat-card-value"><?= htmlspecialchars($mostActiveGenre['genre']) ?></div>
          <div class="stat-card-sub" style="color: var(--text-muted);"><?= $mostActiveGenre['count'] ?> RELEASES IN DATASET</div>
        </div>
      </div>

      <!-- STAT CARDS ROW 2: Total Movies -->
      <div class="stats-grid" style="margin-top: 0;">
        <div class="stat-card">
          <div class="stat-card-header">
            <span class="stat-card-label">TOTAL FILMS</span>
            <div class="stat-card-icon">&#x1F3AC;</div>
          </div>
          <div class="stat-card-value"><?= number_format($totalMovies) ?></div>
          <div class="stat-card-sub" style="color: var(--text-muted);">in the database</div>
        </div>

        <?php
          $maxRevYr = !empty($yearlyRevenue) ? max(array_column($yearlyRevenue, 'total_revenue')) : 1;
          $recentYr = !empty($yearlyRevenue) ? end($yearlyRevenue) : ['yr'=>'—','total_revenue'=>0,'movie_count'=>0];
        ?>
        <div class="stat-card">
          <div class="stat-card-header">
            <span class="stat-card-label">BEST REVENUE YEAR</span>
            <div class="stat-card-icon">&#x1F4C5;</div>
          </div>
          <?php
            $bestRevYr = $yearlyRevenue[0] ?? ['yr'=>'—','total_revenue'=>0];
            foreach ($yearlyRevenue as $yr) {
              if ($yr['total_revenue'] > $bestRevYr['total_revenue']) $bestRevYr = $yr;
            }
          ?>
          <div class="stat-card-value" style="font-size: 1.8rem;"><?= $bestRevYr['yr'] ?></div>
          <div class="stat-card-sub" style="color: var(--accent-green);">&#x20B9;<?= formatRevenue($bestRevYr['total_revenue']) ?> box office</div>
        </div>

        <div class="stat-card">
          <div class="stat-card-header">
            <span class="stat-card-label">MOST FILMS DIRECTED</span>
            <div class="stat-card-icon">&#x1F3AC;</div>
          </div>
          <?php $topD = $topDirsByCount[0] ?? ['director'=>'—','movie_count'=>0]; ?>
          <div class="stat-card-value" style="font-size: 1.1rem; line-height: 1.2;"><?= htmlspecialchars($topD['director']) ?></div>
          <div class="stat-card-sub" style="color: var(--text-muted);"><?= $topD['movie_count'] ?> films directed</div>
        </div>
      </div>

      <!-- MIDDLE ROW: Chart + Editorial -->
      <div class="middle-row">
        <!-- Bar Chart -->
        <div class="card">
          <div class="chart-label">PRODUCTION VELOCITY</div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <div class="chart-title">20-Year Trend: Action vs. Romance</div>
            <div class="chart-legend">
              <div class="legend-item"><span class="legend-dot" style="background: var(--accent-primary);"></span> Action</div>
              <div class="legend-item"><span class="legend-dot" style="background: var(--accent-green);"></span> Romance</div>
            </div>
          </div>

          <?php
            // Build year-grouped data
            $chartData = [];
            foreach ($genreTrend as $row) {
                $yr = (int)$row['yr'];
                $chartData[$yr] = $row;
            }
            // Pick evenly spaced years
            $years = array_keys($chartData);
            if (empty($years)) $years = [2006, 2008, 2010, 2012, 2014, 2016];
            $maxAction = max(array_column($genreTrend ?: [['action_count' => 1]], 'action_count'));
            $maxRomance = max(array_column($genreTrend ?: [['romance_count' => 1]], 'romance_count'));
            $maxVal = max($maxAction, $maxRomance, 1);
          ?>

          <div class="bar-chart">
            <?php foreach ($chartData as $yr => $data): 
              $actionH = round(($data['action_count'] / $maxVal) * 100);
              $romanceH = round(($data['romance_count'] / $maxVal) * 100);
            ?>
              <div class="bar-group" title="<?= $yr ?>">
                <div class="bar bar-action" style="height: <?= max($actionH, 3) ?>%;"></div>
                <div class="bar bar-romance" style="height: <?= max($romanceH, 3) ?>%;"></div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="chart-years">
            <?php 
              $dispYears = array_keys($chartData);
              $step = max(1, floor(count($dispYears) / 6));
              for ($i = 0; $i < count($dispYears); $i += $step) {
                  echo '<span>' . $dispYears[$i] . '</span>';
              }
            ?>
          </div>
        </div>

        <!-- Trending Movies List -->
        <div class="card" style="display: flex; flex-direction: column;">
          <div class="chart-label">BOX OFFICE HOTSTREAK</div>
          <div class="chart-title" style="margin-bottom: 1rem;">Trending Blockbusters</div>
          <div style="display: flex; flex-direction: column; gap: 0.8rem; flex: 1;">
            <?php if (!empty($trending)): ?>
              <?php foreach ($trending as $idx => $movie): ?>
              <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.8rem; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-muted); opacity: 0.5;">0<?= $idx + 1 ?></div>
                  <div>
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);"><?= htmlspecialchars($movie['title']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);"><?= htmlspecialchars($movie['director']) ?> &bull; <?= htmlspecialchars($movie['yr']) ?></div>
                  </div>
                </div>
                <div style="text-align: right;">
                  <div style="font-weight: 700; color: var(--accent-primary); font-size: 0.9rem;">&#x20B9;<?= formatRevenue($movie['revenue']) ?></div>
                  <div style="font-size: 0.75rem; color: var(--accent-green);">&#x2B50; <?= number_format($movie['rating_imdb'], 1) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No trending movies available.</div>
            <?php endif; ?>
          </div>
          <div style="text-align: center; margin-top: 1rem;">
            <a href="search.php" class="btn-outline" style="font-size: 0.7rem; padding: 0.5rem 1rem;">View All Movies</a>
          </div>
        </div>
      </div>

      <!-- BOTTOM ROW: Directors Leaderboard + Recent Acclaimed -->
      <div class="middle-row" style="margin-top: 1.5rem;">

        <!-- Top Directors by Film Count -->
        <div class="card">
          <div class="chart-label">DIRECTOR LEADERBOARD</div>
          <div class="chart-title" style="margin-bottom: 1rem;">Most Prolific Directors</div>
          <div style="display: flex; flex-direction: column; gap: 0.7rem;">
            <?php foreach ($topDirsByCount as $di => $d): ?>
            <div style="display: flex; align-items: center; gap: 0.75rem; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
              <div style="font-size: 1rem; font-weight: 800; color: var(--accent-primary); min-width: 24px; text-align: center;"><?= $di + 1 ?></div>
              <div style="flex: 1;">
                <div style="font-weight: 700; font-size: 0.9rem;"><?= htmlspecialchars($d['director']) ?></div>
                <div style="font-size: 0.72rem; color: var(--text-secondary);"><?= $d['movie_count'] ?> films &bull; &#x2605; <?= number_format($d['avg_rating'], 1) ?> avg</div>
              </div>
              <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent-green);">&#x20B9;<?= formatRevenue($d['total_revenue']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Recent Critically Acclaimed -->
        <div class="card">
          <div class="chart-label">RECENT GEMS</div>
          <div class="chart-title" style="margin-bottom: 1rem;">Critically Acclaimed (2018+)</div>
          <div style="display: flex; flex-direction: column; gap: 0.8rem;">
            <?php foreach ($recentAcclaimed as $ra): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.8rem; border-bottom: 1px solid var(--border-color);">
              <div>
                <div style="font-weight: 700; font-size: 0.92rem;"><?= htmlspecialchars($ra['title']) ?></div>
                <div style="font-size: 0.72rem; color: var(--text-secondary);"><?= htmlspecialchars($ra['director']) ?> &bull; <?= $ra['release_year'] ?></div>
                <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($ra['genre_name']) ?></div>
              </div>
              <div style="text-align: right; flex-shrink: 0; padding-left: 1rem;">
                <div style="font-weight: 800; color: var(--accent-primary); font-size: 1.1rem;">&#x2605; <?= number_format($ra['rating_imdb'], 1) ?></div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">&#x20B9;<?= formatRevenue($ra['revenue']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentAcclaimed)): ?>
              <div style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No recent data available.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>

    </div>
  </main>
</body>
</html>
