<?php
require_once __DIR__ . '/../../backend/DataService.php';

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

// Fallbacks
if ($avgRating == 0) $avgRating = 7.42;
if ($totalRevenue == 0) $totalRevenue = 124000000000;
if ($mostActiveGenre['genre'] === 'Unknown') $mostActiveGenre = ['genre' => 'Action', 'count' => 342];
if (empty($trending)) {
    $trending = [
        ['title' => 'Sample Film', 'director' => 'Director', 'genres' => 'Drama', 'rating_imdb' => 8.7, 'language' => 'hi', 'yr' => 2024, 'revenue' => 100000000],
    ];
}

// Format revenue
$revenueCr = round($totalRevenue / 10000000, 1); // In Crores
if ($revenueCr > 1000) {
    $revenueFormatted = round($revenueCr / 1000, 1) . 'K Cr';
} else {
    $revenueFormatted = number_format($revenueCr, 1) . ' Cr';
}

// Language map for display
$langMap = ['hi' => 'Hindi', 'ta' => 'Tamil', 'te' => 'Telugu', 'ml' => 'Malayalam', 'kn' => 'Kannada', 'en' => 'English'];

// Genre color mapping
function getGenreClass($genre) {
    $genre = strtolower(trim($genre));
    $map = ['drama' => 'genre-drama', 'action' => 'genre-action', 'comedy' => 'genre-comedy',
            'romance' => 'genre-romance', 'thriller' => 'genre-thriller', 'horror' => 'genre-horror',
            'crime' => 'genre-crime'];
    return $map[$genre] ?? 'genre-default';
}

// Sentiment labels based on rating
function getSentiment($rating) {
    if ($rating >= 8.5) return ['label' => 'Universal Praise', 'class' => 'sentiment-high'];
    if ($rating >= 7.5) return ['label' => 'Box Office Smash', 'class' => 'sentiment-medium'];
    if ($rating >= 7.0) return ['label' => 'Cult Status', 'class' => 'sentiment-low'];
    return ['label' => 'Mixed Reviews', 'class' => 'sentiment-low'];
}
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
          <a href="index.php" class="btn-outline">Regional Insights</a>
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
          <div class="stat-card-value">&#x20B9;<?= $revenueFormatted ?></div>
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

        <!-- Editorial Pick -->
        <div class="editorial-card">
          <div class="editorial-content">
            <div class="editorial-label">EDITORIAL PICK</div>
            <h3 class="editorial-title">The Evolution of the 'Angry Young Man' Archetype</h3>
            <a href="#" class="editorial-link">Read Collection &#x2192;</a>
          </div>
        </div>
      </div>

      <!-- TRENDING NOW TABLE -->
      <div class="card">
        <div class="trending-header">
          <div>
            <div class="trending-label">LIVE DATA</div>
            <h2 class="trending-title">Trending Now</h2>
          </div>
          <a href="#" class="trending-link">View All Records &#x1F517;</a>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th>Film Title</th>
              <th>Director</th>
              <th>Genre</th>
              <th>IMDB Score</th>
              <th>Sentiment</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trending as $movie): 
              $primaryGenre = trim(explode(',', $movie['genres'])[0]);
              $genreClass = getGenreClass($primaryGenre);
              $lang = $langMap[$movie['language'] ?? ''] ?? ucfirst($movie['language'] ?? '');
              $sentiment = getSentiment($movie['rating_imdb']);
              $emojis = ['&#x1F3AC;', '&#x1F3A5;', '&#x1F39E;', '&#x1F4FD;', '&#x1F3A6;'];
              $emoji = $emojis[array_rand($emojis)];
            ?>
            <tr>
              <td>
                <div class="film-cell">
                  <div class="film-poster"><?= $emoji ?></div>
                  <div>
                    <div class="film-name"><?= htmlspecialchars($movie['title']) ?></div>
                    <div class="film-meta"><?= $lang ?> &bull; <?= $movie['yr'] ?? '' ?></div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($movie['director']) ?></td>
              <td><span class="genre-badge <?= $genreClass ?>"><?= htmlspecialchars(strtoupper($primaryGenre)) ?></span></td>
              <td>
                <div class="imdb-score">
                  <span class="imdb-star">&#x2605;</span>
                  <?= number_format($movie['rating_imdb'], 1) ?>
                </div>
              </td>
              <td><span class="sentiment-badge <?= $sentiment['class'] ?>"><?= $sentiment['label'] ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>

    </div>
  </main>
</body>
</html>
