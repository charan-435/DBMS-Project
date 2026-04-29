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


// Trending Movies
$trending = $service->getTrendingMovies(4);

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
          Tracing the soul of Indian storytelling through two decades of metadata, box office triumphs, and genre performance insights.
        </p>
        <div class="hero-actions">
          <a href="genres.php" class="btn-accent">Explore Trends &#x2197;</a>
          <a href="insights.php" class="btn-outline">Genre Comparisons</a>
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

      <!-- MIDDLE ROW: Trending Movies (full width) -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="chart-label">BOX OFFICE HOTSTREAK</div>
        <div class="chart-title" style="margin-bottom: 1rem;">Trending Blockbusters</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.8rem;">
          <?php if (!empty($trending)): ?>
            <?php foreach ($trending as $idx => $movie): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.9rem 1rem; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid var(--border-color);">
              <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-muted); opacity: 0.5; min-width:28px;"><?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?></div>
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem;">
                    <a href="movie_details.php?id=<?= $movie['movie_id'] ?>" style="color: var(--text-primary); text-decoration: none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-primary)'"><?= htmlspecialchars($movie['title']) ?></a>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--text-secondary);">
                    <a href="director_details.php?id=<?= $movie['director_id'] ?>" style="color: inherit; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?= htmlspecialchars($movie['director']) ?></a> &bull; <?= htmlspecialchars($movie['yr']) ?>
                  </div>
                </div>
              </div>
              <div style="text-align: right; padding-left:0.5rem; flex-shrink:0;">
                <div style="font-weight: 700; color: var(--accent-primary); font-size: 0.9rem;">&#x20B9;<?= formatRevenue($movie['revenue']) ?></div>
                <div style="font-size: 0.75rem; color: var(--accent-green);">&#x2B50; <?= number_format($movie['rating_imdb'], 1) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="text-align: center; color: var(--text-muted); padding: 2rem 0; grid-column: 1/-1;">No trending movies available.</div>
          <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 1rem;">
          <a href="movies.php" class="btn-outline" style="font-size: 0.7rem; padding: 0.5rem 1rem;">View All Movies</a>
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
                <div style="font-weight: 700; font-size: 0.9rem;">
                  <a href="director_details.php?id=<?= $d['director_id'] ?>" style="color: inherit; text-decoration: none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($d['director']) ?></a>
                </div>
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
                <div style="font-weight: 700; font-size: 0.92rem;">
                  <a href="movie_details.php?id=<?= $ra['movie_id'] ?>" style="color: inherit; text-decoration: none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($ra['title']) ?></a>
                </div>
                <div style="font-size: 0.72rem; color: var(--text-secondary);">
                  <a href="director_details.php?id=<?= $ra['director_id'] ?>" style="color: inherit; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?= htmlspecialchars($ra['director']) ?></a> &bull; <?= $ra['yr'] ?>
                </div>
                <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($ra['genres'] ?? '') ?></div>
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
      </div> <!-- End of middle-row -->

      <!-- GOLDEN YEAR SPOTLIGHT -->
      <?php $goldenYear = $service->getGoldenYear(); ?>
      <?php if (!empty($goldenYear)): ?>
      <div class="card" style="margin-top: 1.5rem; background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(18, 18, 24, 0.5)); border: 1px solid var(--accent-glow);">
        <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
          <div style="font-size: 3rem; font-weight: 900; color: var(--accent-primary); opacity: 0.8;"><?= $goldenYear['yr'] ?></div>
          <div style="flex: 1; min-width: 250px;">
            <div class="text-accent text-xxs font-bold uppercase mb-1">CINEMATIC GOLDEN YEAR</div>
            <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 0.5rem;">A Landmark in Indian Cinema</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); max-width: 600px;">
              The year <?= $goldenYear['yr'] ?> stands as the most commercially successful period in our database, generating a staggering 
              <strong>&#x20B9;<?= formatRevenue($goldenYear['total_revenue']) ?></strong> across <strong><?= $goldenYear['movie_count'] ?></strong> tracked releases.
            </p>
          </div>
          <div style="margin-left: auto; padding-top: 0.5rem;">
            <a href="explore.php?q=<?= $goldenYear['yr'] ?>" class="btn-primary" style="font-size: 0.75rem; padding: 0.6rem 1.2rem; display: inline-block;">Analyze Year</a>
          </div>
        </div>
      </div>
      <?php endif; ?>


    </div>
  </main>
</body>
</html>
