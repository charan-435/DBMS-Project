<?php
require_once __DIR__ . '/components/session.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$ds = new DataService();

// Optimized Data Fetching 
$industryKPIs  = $ds->getIndustryKPIData();
$totalMovies   = $industryKPIs['total_movies'] ?? 0;
$totalRevenue  = $industryKPIs['total_revenue'] ?? 0;
$avgRating     = $industryKPIs['avg_rating'] ?? 0;
$topGenre      = $industryKPIs['top_genre'] ?? ['genre' => 'Unknown', 'count' => 0];

$highestRated   = $ds->getHighestRatedOverall(4);
$profitableDirs = $ds->getMostProfitableDirectors(4);
$langRatingComp = $ds->getLanguageRatingComparison();
$yearlyRevTrend = $ds->getYearlyRevenueTrend(2010);
$top_films      = $ds->getTopRegionalMovies(5);

// ── Helper: language code → industry name ─────────────────────────────────────
function industryName(string $code): string {
    $map = [
        'te' => 'Tollywood', 'ta' => 'Kollywood', 'hi' => 'Bollywood',
        'kn' => 'Sandalwood', 'ml' => 'Mollywood', 'en' => 'Hollywood',
    ];
    return $map[strtolower(trim($code))] ?? strtoupper($code);
}

// ── 2. Market Share donut calculation ──────────────────────────────────────────
$langStats    = $ds->getLanguageStats(6);
$grandTotal   = array_sum(array_column($langStats, 'total_revenue')) ?: 1;
$donutColors  = ['#818cf8', '#34d399', '#fb923c', '#38bdf8', '#a78bfa', '#fb7185'];
$market_shares = [];
$othersTotal  = 0;

foreach ($langStats as $i => $row) {
    if ($i < 3) {
        $market_shares[] = [
            'label' => industryName($row['language']),
            'pct'   => round(($row['total_revenue'] / $grandTotal) * 100, 1),
            'color' => $donutColors[$i],
        ];
    } else {
        $othersTotal += $row['total_revenue'];
    }
}
if ($othersTotal > 0) {
    $market_shares[] = [
        'label' => 'Others',
        'pct'   => round(($othersTotal / $grandTotal) * 100, 1),
        'color' => '#6b7280',
    ];
}

// ── SVG Donut arc helper ──────────────────────────────────────────────────────
function donutArc(float $pct, float $offset, string $color): string {
    $r = 70; $cx = 90; $cy = 90;
    $circ = 2 * M_PI * $r;
    $dash = $pct / 100 * $circ;
    $gap  = $circ - $dash;
    $rot  = -90 + ($offset / 100 * 360);
    return sprintf(
        '<circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s"
                 stroke-width="18" stroke-dasharray="%.2f %.2f"
                 stroke-dashoffset="0" transform="rotate(%.2f %s %s)"
                 stroke-linecap="butt"/>',
        $cx, $cy, $r, $color, $dash, $gap, $rot, $cx, $cy
    );
}

$arcs = ''; $offsetArc = 0;
foreach ($market_shares as $s) {
    $arcs   .= donutArc($s['pct'], $offsetArc, $s['color']);
    $offsetArc += $s['pct'];
}

// ── Superstars logic ─────────────────────────────────────────────────────────
$topActors = $ds->getTopActors(4);
$badgeDefs = [
    ['tag' => 'REVENUE MAGNET',  'cls' => 'badge-orange'],
    ['tag' => 'REGIONAL LEAD',   'cls' => 'badge-cyan'],
    ['tag' => 'BOX OFFICE KING', 'cls' => 'badge-gray'],
    ['tag' => 'SUPERSTAR',       'cls' => 'badge-gray'],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Industry Analytics — The Cinematic Lens</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .donut-container { display: flex; align-items: center; justify-content: center; gap: 2rem; padding: 1rem; }
    .legend-item { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.85rem; }
    .legend-color { width: 12px; height: 12px; border-radius: 3px; }
    .hero-label { font-size: 0.6rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: var(--accent-primary); margin-bottom: 0.5rem; }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <!-- Header -->
      <div style="margin-bottom: 1.5rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">PLATFORM INTELLIGENCE</p>
        <h1 style="font-size: 2.25rem; font-weight: 800;">Indian Cinema <em style="color: var(--accent-primary); font-style: italic;">At a Glance</em></h1>
        <p class="mt-2" style="color: var(--text-secondary); font-size: 0.88rem; max-width: 600px;">
          Comprehensive data visualization of industry trends, market share, and commercial performance across regional languages.
        </p>
      </div>

      <!-- KPI Strip -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-label">Total Films</div>
            <div class="stat-card-icon">🎬</div>
          </div>
          <div class="stat-card-value"><?= number_format($totalMovies) ?></div>
          <div class="stat-card-sub">In Catalog</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-label">Gross Revenue</div>
            <div class="stat-card-icon">💰</div>
          </div>
          <div class="stat-card-value">₹<?= formatRevenue($totalRevenue) ?></div>
          <div class="stat-card-sub" style="color: var(--accent-green);">Cumulative Collection</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header">
            <div class="stat-card-label">Top Genre</div>
            <div class="stat-card-icon">🏆</div>
          </div>
          <div class="stat-card-value"><?= htmlspecialchars($topGenre['genre']) ?></div>
          <div class="stat-card-sub" style="color: var(--accent-primary);"><?= number_format($topGenre['count']) ?> Productions</div>
        </div>
      </div>

      <!-- Market & Trends -->
      <div class="analytics-grid">
        <!-- Market Share -->
        <div class="card">
          <div class="hero-label">Market Intelligence</div>
          <h2 class="chart-title" style="margin-bottom: 1.5rem;">Revenue Share by Industry</h2>
          <div class="donut-container">
            <svg width="140" height="140" viewBox="0 0 180 180">
              <circle cx="90" cy="90" r="70" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="18"/>
              <?= $arcs ?>
            </svg>
            <div class="legend">
              <?php foreach ($market_shares as $s): ?>
                <div class="legend-item">
                  <div class="legend-color" style="background: <?= $s['color'] ?>;"></div>
                  <span class="text-secondary"><?= $s['label'] ?></span>
                  <span class="font-bold" style="margin-left: auto;"><?= $s['pct'] ?>%</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Yearly Trend -->
        <div class="card">
          <div class="hero-label">Growth Analysis</div>
          <h2 class="chart-title" style="margin-bottom: 1rem;">Annual Volume (2010+)</h2>
          <?php if (!empty($yearlyRevTrend)): 
            $vals = array_column($yearlyRevTrend, 'movie_count');
            $maxV = max($vals) ?: 1;
            $midV = round($maxV / 2);
          ?>
            <div style="display: flex; height: 160px; margin-top: 1.5rem;">
              <!-- Y Axis labels -->
              <div style="display: flex; flex-direction: column; justify-content: space-between; font-size: 0.65rem; color: var(--text-muted); padding-right: 0.75rem; text-align: right; width: 40px; border-right: 1px solid var(--border-color); padding-bottom: 1px;">
                <span><?= $maxV ?></span>
                <span><?= $midV ?></span>
                <span>0</span>
              </div>
              
              <!-- Bars container -->
              <div style="flex: 1; display: flex; align-items: flex-end; gap: 4px; padding-left: 0.5rem; position: relative;">
                <!-- Midline -->
                <div style="position: absolute; left: 0; right: 0; bottom: 50%; height: 1px; background: rgba(255,255,255,0.03); z-index: 1;"></div>
                
                <?php foreach ($yearlyRevTrend as $yrt): 
                  $h = ($yrt['movie_count'] / $maxV) * 100;
                ?>
                  <div class="bar-item" style="flex: 1; height: <?= max($h, 5) ?>%; background: var(--accent-primary); border-radius: 4px 4px 0 0; opacity: 0.8; transition: all 0.2s; position: relative; z-index: 2;" title="<?= $yrt['yr'] ?>: <?= $yrt['movie_count'] ?> films"></div>
                <?php endforeach; ?>
              </div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.65rem; color: var(--text-muted); margin-top: 0.5rem; padding-left: 40px;">
              <span><?= $yearlyRevTrend[0]['yr'] ?></span>
              <span><?= end($yearlyRevTrend)['yr'] ?></span>
            </div>
          <?php else: ?>
            <p class="text-muted">No trend data available.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Detailed Tables -->
      <div class="analytics-grid">
        <!-- Top Films -->
        <div class="card">
          <div class="hero-label">Box Office</div>
          <h2 class="chart-title">Regional Chart-toppers</h2>
          <table class="data-table" style="margin-top: 1rem;">
            <thead>
              <tr><th>Film</th><th>Industry</th><th style="text-align:right;">Collection</th></tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($top_films, 0, 5) as $f): ?>
                <tr>
                  <td>
                    <div class="film-name"><a href="movie_details.php?id=<?= $f['movie_id'] ?>" class="text-primary hover:text-accent"><?= htmlspecialchars($f['title']) ?></a></div>
                    <div class="film-meta" style="font-size: 0.7rem;"><a href="director_details.php?id=<?= $f['director_id'] ?>" class="text-muted"><?= htmlspecialchars($f['director']) ?></a></div>
                  </td>
                  <td><span class="genre-badge genre-default" style="font-size: 0.6rem;"><?= industryName($f['language']) ?></span></td>
                  <td style="text-align:right;" class="text-green font-bold">₹<?= formatRevenue($f['revenue']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Superstars -->
        <div class="card">
          <div class="hero-label">Talent Metrics</div>
          <h2 class="chart-title">Leading Regional Stars</h2>
          <div class="talent-list" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
            <?php foreach ($topActors as $i => $t): 
              $badge = $badgeDefs[$i] ?? ['tag' => 'STAR', 'cls' => 'badge-gray'];
            ?>
              <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                <div>
                  <div class="font-semibold"><a href="actor_details.php?id=<?= $t['actor_id'] ?>" class="text-primary hover:text-accent"><?= htmlspecialchars($t['name']) ?></a></div>
                  <div class="text-muted text-xxs uppercase tracking-widest"><?= $t['count'] ?> Career Productions</div>
                </div>
                <span class="sentiment-badge sentiment-medium" style="font-size: 0.6rem;"><?= $badge['tag'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      
    </div>
  </main>
</body>
</html>
