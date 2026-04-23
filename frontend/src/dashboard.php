<?php
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

// Fallbacks — show real data or empty state
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

     
   
       
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>

    </div>
  </main>
</body>
</html>
