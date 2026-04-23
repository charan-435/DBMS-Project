<?php
require_once __DIR__ . '/components/session.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$ds = new DataService();

// ── Helper: language code → industry name ─────────────────────────────────────
function industryName(string $code): string {
    $map = [
        'te' => 'Tollywood', 'ta' => 'Kollywood', 'hi' => 'Bollywood',
        'kn' => 'Sandalwood', 'ml' => 'Mollywood', 'en' => 'Hollywood',
    ];
    return $map[strtolower(trim($code))] ?? strtoupper($code);
}

// ── 1. KPI bar ────────────────────────────────────────────────────────────────
$totalRevenue = $ds->getTotalRevenue();
$totalMovies  = $ds->getTotalMovies();
$avgRating    = $ds->getAvgRating();
$topGenre     = $ds->getMostActiveGenre();

// ── 2. Market Share donut ─────────────────────────────────────────────────────
$langStats    = $ds->getLanguageStats(6);
$grandTotal   = array_sum(array_column($langStats, 'total_revenue')) ?: 1;
$donutColors  = ['#f97316', '#22d3ee', '#6b7280', '#4ade80', '#a78bfa', '#f87171'];
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
$market_total = '₹' . formatRevenue($grandTotal);

// ── 3. Genre Trend (Action vs Romance yearly count) ───────────────────────────
$genreTrend  = $ds->getGenreTrend();
$trend       = [];
$max_trend   = 1;
foreach ($genreTrend as $row) {
    if (!isset($row['yr']) || $row['yr'] === null) continue;
    $yr = (int)$row['yr'];
    if ($yr < 2010 || $yr > 2024) continue;
    $a = (int)$row['action_count'];
    $r = (int)$row['romance_count'];
    $trend[$yr] = [$a, $r];
    $max_trend  = max($max_trend, $a, $r);
}
ksort($trend);
$trend_years = array_keys($trend);
$trend_empty = count($trend) === 0;

// ── 4. Top Regional Films (non-Hindi, from getTopGrossingMovies) ──────────────
$allTopMovies = $ds->getTopGrossingMovies(20);
$top_films    = [];
foreach ($allTopMovies as $row) {
    if (strtolower(trim($row['language'] ?? '')) === 'hi') continue;
    $top_films[] = $row;
    if (count($top_films) >= 5) break;
}

// ── 5. Regional Superstars ────────────────────────────────────────────────────
$topActors = $ds->getTopActors(4);
$badgeDefs = [
    ['tag' => 'REVENUE MAGNET',  'cls' => 'badge-orange'],
    ['tag' => 'REGIONAL LEAD',   'cls' => 'badge-cyan'],
    ['tag' => 'BOX OFFICE KING', 'cls' => 'badge-gray'],
    ['tag' => 'SUPERSTAR',       'cls' => 'badge-gray'],
];

// ── 6. Rating–Revenue correlation ─────────────────────────────────────────────
$ratingRevenue = $ds->getRatingRevenueCorrelation();

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

$arcs = ''; $offset = 0;
foreach ($market_shares as $s) {
    $arcs   .= donutArc($s['pct'], $offset, $s['color']);
    $offset += $s['pct'];
}

// ── SVG polyline point builder ────────────────────────────────────────────────
function chartPoints(array $vals, float $max, int $w = 460, int $h = 160, int $pad = 28): string {
    $keys = array_keys($vals);
    $n    = count($keys);
    if ($n < 2) return '';
    $pts  = '';
    foreach ($keys as $i => $k) {
        $x    = $pad + ($i / ($n - 1)) * ($w - $pad * 2);
        $y    = $h - $pad - ($vals[$k] / $max) * ($h - $pad * 2);
        $pts .= "$x,$y ";
    }
    return trim($pts);
}

$action_arr  = array_map(fn($v) => $v[0], $trend);
$romance_arr = array_map(fn($v) => $v[1], $trend);
$action_pts  = chartPoints($action_arr,  $max_trend);
$romance_pts = chartPoints($romance_arr, $max_trend);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Industry Intelligence — Regional Powerhouses</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── Industry Page Scoped Styles ─────────────────────────────────────────── */

/* Page header */
.industry-wrapper .eyebrow {
    font-size: 10px; letter-spacing: .18em; color: #64647a;
    text-transform: uppercase; display: flex; align-items: center; gap: 8px;
    margin-bottom: 8px;
}
.industry-wrapper .eyebrow::before { content: ''; width: 24px; height: 2px; background: #f97316; }
.industry-wrapper h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(32px, 4vw, 52px);
    letter-spacing: .01em; line-height: .95;
    margin-bottom: 12px; color: #eeeef5;
}
.industry-wrapper .subtitle { color: #64647a; font-size: 13px; line-height: 1.65; max-width: 500px; margin-bottom: 28px; }

/* KPI strip — HORIZONTAL */
.industry-wrapper .kpi-strip {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 14px;
    margin-bottom: 20px;
    width: 100% !important;
    min-width: min-content;
}
.industry-wrapper { 
    width: 100% !important;
    max-width: none !important;
    box-sizing: border-box;
}
.industry-wrapper .kpi {
    flex: 1 1 0% !important;
    min-width: 0 !important;
    background: #111115; border: 1px solid #1f1f27;
    border-radius: 14px; padding: 18px 20px;
    position: relative; overflow: hidden;
    animation: indFadeUp .4s ease both;
}
.industry-wrapper .kpi::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0;
    height: 2px; background: #f97316;
    transform: scaleX(0); transform-origin: left; transition: transform .3s;
}
.industry-wrapper .kpi:hover::after { transform: scaleX(1); }
.industry-wrapper .kpi-label { font-size: 9px; letter-spacing: .14em; color: #64647a; text-transform: uppercase; margin-bottom: 6px; }
.industry-wrapper .kpi-value { font-family: 'Bebas Neue', sans-serif; font-size: 28px; letter-spacing: .02em; line-height: 1; color: #eeeef5; }
.industry-wrapper .kpi-sub { font-size: 10px; color: #64647a; margin-top: 3px; font-family: 'DM Mono', monospace; }
.industry-wrapper .kpi-icon { position: absolute; top: 16px; right: 16px; font-size: 18px; opacity: .28; }

/* Grid rows */
.industry-wrapper .row { display: grid; gap: 16px; margin-bottom: 16px; }
.industry-wrapper .row-21 { grid-template-columns: 1fr 1.8fr; }
.industry-wrapper .row-11 { grid-template-columns: 1.4fr 1fr; }

/* Card */
.industry-wrapper .card {
    background: #111115 !important; border: 1px solid #1f1f27 !important;
    border-radius: 14px !important; padding: 22px 24px !important;
    position: relative; overflow: hidden;
    animation: indFadeUp .45s ease both;
}
.industry-wrapper .card::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(ellipse 55% 35% at 85% 5%, rgba(249,115,22,.042) 0%, transparent 65%);
    pointer-events: none;
}
.industry-wrapper .card:hover { border-color: #28282f !important; }
.industry-wrapper .card-title { font-size: 15px; font-weight: 600; margin-bottom: 3px; color: #eeeef5; }
.industry-wrapper .card-sub { font-size: 9px; letter-spacing: .13em; color: #64647a; text-transform: uppercase; margin-bottom: 20px; }
.industry-wrapper .live-badge {
    position: absolute; top: 20px; right: 20px;
    display: flex; align-items: center; gap: 4px;
    font-size: 8px; letter-spacing: .1em; color: #4ade80; text-transform: uppercase;
}
.industry-wrapper .live-badge::before {
    content: ''; width: 5px; height: 5px; border-radius: 50%;
    background: #4ade80; animation: indPulse 2s infinite;
}

/* Donut */
.industry-wrapper .donut-wrap { display: flex; flex-direction: column; align-items: center; }
.industry-wrapper .donut-center-label { font-family: 'Bebas Neue', sans-serif; font-size: 24px; fill: #eeeef5; }
.industry-wrapper .donut-center-sub { font-size: 8.5px; letter-spacing: .12em; fill: #64647a; text-transform: uppercase; }
.industry-wrapper .legend { width: 100%; margin-top: 16px; display: flex; flex-direction: column; gap: 9px; }
.industry-wrapper .legend-row { display: flex; align-items: center; justify-content: space-between; }
.industry-wrapper .legend-left { display: flex; align-items: center; gap: 8px; }
.industry-wrapper .legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.industry-wrapper .legend-label { font-size: 12px; color: #64647a; }
.industry-wrapper .legend-pct { font-family: 'DM Mono', monospace; font-size: 12px; color: #eeeef5; }

/* Trend chart */
.industry-wrapper .chart-legend {
    display: flex; gap: 16px; margin-bottom: 12px;
    font-size: 10px; letter-spacing: .1em; color: #64647a; text-transform: uppercase;
}
.industry-wrapper .chart-legend span { display: flex; align-items: center; gap: 5px; }
.industry-wrapper .chart-legend i { display: inline-block; width: 18px; height: 2px; border-radius: 2px; }
.industry-wrapper .chart-svg { width: 100%; overflow: visible; }
.industry-wrapper .chart-year { font-family: 'DM Mono', monospace; font-size: 8.5px; fill: #64647a; text-anchor: middle; }
.industry-wrapper .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 160px; gap: 8px; }
.industry-wrapper .empty-icon { font-size: 28px; opacity: .28; }
.industry-wrapper .empty-text { font-size: 11px; color: #64647a; text-align: center; line-height: 1.6; }

/* Banner */
.industry-wrapper .banner {
    background: linear-gradient(120deg, #0f0a04 0%, #1a1106 50%, #120d06 100%);
    border: 1px solid #3b2010; border-radius: 14px;
    padding: 22px 26px; display: flex; align-items: center; gap: 18px;
    margin-bottom: 16px; position: relative; overflow: hidden;
    animation: indFadeUp .5s ease both;
}
.industry-wrapper .banner::after {
    content: ''; position: absolute; right: -60px; top: -60px;
    width: 200px; height: 200px; border-radius: 50%;
    background: radial-gradient(circle, rgba(249,115,22,.1) 0%, transparent 70%);
    pointer-events: none;
}
.industry-wrapper .banner-icon { width: 44px; height: 44px; border-radius: 10px; background: rgba(249,115,22,.13); border: 1px solid rgba(249,115,22,.26); display: grid; place-items: center; font-size: 20px; flex-shrink: 0; }
.industry-wrapper .banner-title { font-family: 'Bebas Neue', sans-serif; font-size: 21px; color: #f97316; letter-spacing: .04em; margin-bottom: 3px; }
.industry-wrapper .banner-body { font-size: 12.5px; color: #b89070; line-height: 1.5; flex: 1; }
.industry-wrapper .btn { background: #f97316; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; cursor: pointer; flex-shrink: 0; white-space: nowrap; transition: background .18s, transform .15s; }
.industry-wrapper .btn:hover { background: #ea6f0a; transform: translateY(-1px); }

/* Films table */
.industry-wrapper .tbl-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.industry-wrapper table { width: 100%; border-collapse: collapse; }
.industry-wrapper thead th { font-size: 9px; letter-spacing: .12em; color: #64647a; text-transform: uppercase; text-align: left; padding: 0 0 10px; border-bottom: 1px solid #1f1f27; }
.industry-wrapper thead th:not(:first-child) { text-align: right; }
.industry-wrapper tbody tr { border-bottom: 1px solid #1f1f27; transition: background .15s; }
.industry-wrapper tbody tr:last-child { border-bottom: none; }
.industry-wrapper tbody tr:hover { background: rgba(255,255,255,.022); }
.industry-wrapper td { padding: 12px 0; vertical-align: middle; color: #eeeef5; }
.industry-wrapper td:not(:first-child) { text-align: right; }
.industry-wrapper .film-cell { display: flex; align-items: center; gap: 10px; }
.industry-wrapper .film-thumb { width: 30px; height: 30px; border-radius: 6px; background: #28282f; display: grid; place-items: center; font-size: 13px; flex-shrink: 0; }
.industry-wrapper .film-name { font-size: 13px; font-weight: 500; }
.industry-wrapper .film-dir { font-size: 10px; color: #64647a; margin-top: 1px; }
.industry-wrapper .lang-tag { font-size: 10px; color: #64647a; font-family: 'DM Mono', monospace; }
.industry-wrapper .rating-val { font-family: 'DM Mono', monospace; font-size: 12px; color: #fbbf24; }
.industry-wrapper .star { color: #fbbf24; font-size: 9px; margin-right: 2px; }
.industry-wrapper .gross-val { font-family: 'DM Mono', monospace; font-size: 12px; color: #f97316; font-weight: 600; }
.industry-wrapper .no-data { font-size: 11px; color: #64647a; padding: 20px 0; text-align: center; }

/* Right column stack */
.industry-wrapper .col-stack { display: flex; flex-direction: column; gap: 16px; }

/* Superstars */
.industry-wrapper .talent-list { display: flex; flex-direction: column; }
.industry-wrapper .talent-item { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid #1f1f27; }
.industry-wrapper .talent-item:last-child { border-bottom: none; }
.industry-wrapper .talent-avatar { width: 36px; height: 36px; border-radius: 8px; background: #28282f; display: grid; place-items: center; font-size: 14px; flex-shrink: 0; border: 1px solid #28282f; }
.industry-wrapper .talent-info { flex: 1; min-width: 0; }
.industry-wrapper .talent-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #eeeef5; }
.industry-wrapper .talent-meta { font-size: 10px; color: #64647a; font-family: 'DM Mono', monospace; margin-top: 1px; }
.industry-wrapper .talent-badge { font-size: 8px; font-weight: 700; letter-spacing: .1em; padding: 3px 7px; border-radius: 4px; flex-shrink: 0; white-space: nowrap; }
.industry-wrapper .badge-orange { background: rgba(249,115,22,.14); color: #f97316; }
.industry-wrapper .badge-cyan { background: rgba(34,211,238,.11); color: #22d3ee; }
.industry-wrapper .badge-gray { background: rgba(100,100,122,.18); color: #9ca3af; }

/* Rating × Revenue bars */
.industry-wrapper .corr-list { display: flex; flex-direction: column; gap: 14px; margin-top: 4px; }
.industry-wrapper .corr-meta { display: flex; justify-content: space-between; margin-bottom: 5px; }
.industry-wrapper .corr-cat { font-size: 11px; color: #64647a; }
.industry-wrapper .corr-val { font-family: 'DM Mono', monospace; font-size: 10px; color: #eeeef5; }
.industry-wrapper .corr-bar-bg { height: 4px; background: #28282f; border-radius: 2px; overflow: hidden; }
.industry-wrapper .corr-bar-fill { height: 4px; border-radius: 2px; background: linear-gradient(90deg, #f97316, #fbbf24); }

/* Animations */
@keyframes indFadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes indPulse { 0%,100% { opacity: 1; } 50% { opacity: .3; } }

.industry-wrapper .kpi:nth-child(1) { animation-delay: .04s; }
.industry-wrapper .kpi:nth-child(2) { animation-delay: .09s; }
.industry-wrapper .kpi:nth-child(3) { animation-delay: .14s; }
.industry-wrapper .kpi:nth-child(4) { animation-delay: .19s; }
</style>
</head>
<body>

<?php include 'components/sidebar.php'; ?>

<main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
        <div class="industry-wrapper">

    <!-- Header -->
    <p class="eyebrow">Industry Intelligence</p>
    <h1>Regional Powerhouses</h1>
    <p class="subtitle">A deep-dive into the shifting tectonic plates of Indian cinema, where regional storytelling is redefining global box office benchmarks.</p>

    <!-- KPI Strip — getTotalMovies, getTotalRevenue, getAvgRating, getMostActiveGenre -->
    <div class="kpi-strip">
        <div class="kpi">
            <div class="kpi-icon">🎬</div>
            <div class="kpi-label">Total Films</div>
            <div class="kpi-value"><?= number_format($totalMovies) ?></div>
            <div class="kpi-sub">in database</div>
        </div>
        <div class="kpi">
            <div class="kpi-icon">💰</div>
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value">₹<?= formatRevenue($totalRevenue) ?></div>
            <div class="kpi-sub">cumulative box office</div>
        </div>
        <div class="kpi">
            <div class="kpi-icon">⭐</div>
            <div class="kpi-label">Avg IMDB Rating</div>
            <div class="kpi-value"><?= $avgRating ?></div>
            <div class="kpi-sub">across all titles</div>
        </div>
        <div class="kpi">
            <div class="kpi-icon">🏆</div>
            <div class="kpi-label">Top Genre</div>
            <div class="kpi-value" style="font-size:22px"><?= htmlspecialchars($topGenre['genre']) ?></div>
            <div class="kpi-sub"><?= number_format($topGenre['count']) ?> films</div>
        </div>
    </div>

    <!-- Row 1: Market Share + Genre Trend -->
    <!-- getLanguageStats(6) + getGenreTrend() -->
    <div class="row row-21">

        <!-- Donut: Market Share -->
        <div class="card">
            <div class="card-title">Market Share</div>
            <div class="card-sub">Revenue Distribution by Industry</div>
          
            <div class="donut-wrap">
                <svg width="180" height="180" viewBox="0 0 180 180" style="overflow:visible">
                    <circle cx="90" cy="90" r="70" fill="none" stroke="#1d1d24" stroke-width="18"/>
                    <?= $arcs ?>
                    <text x="90" y="85" text-anchor="middle" class="donut-center-label"><?= $market_total ?></text>
                    <text x="90" y="101" text-anchor="middle" class="donut-center-sub">CR Revenue</text>
                </svg>
                <div class="legend">
                    <?php foreach ($market_shares as $s): ?>
                    <div class="legend-row">
                        <div class="legend-left">
                            <div class="legend-dot" style="background:<?= $s['color'] ?>"></div>
                            <span class="legend-label"><?= htmlspecialchars($s['label']) ?></span>
                        </div>
                        <span class="legend-pct"><?= $s['pct'] ?>%</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($market_shares)): ?>
                        <p class="no-data">No revenue data found in DB.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Line chart: Genre Trend -->
        <div class="card">
            <div class="card-title">Genre Trend</div>
            <div class="card-sub">Action vs Romance — Yearly Film Count</div>

            <?php if (!$trend_empty): ?>
            <div class="chart-legend">
                <span><i style="background:#f97316"></i>Action</span>
                <span><i style="background:#22d3ee"></i>Romance</span>
            </div>
            <?php
            $sw = 460; $sh = 160; $pad = 28; $n = count($trend);
            $xFor = fn(int $i): float => $pad + ($i / ($n - 1)) * ($sw - $pad * 2);
            ?>
            <svg class="chart-svg" viewBox="0 0 <?= $sw ?> <?= $sh ?>" height="160" preserveAspectRatio="none">
                <?php foreach ([.25, .5, .75, 1] as $g): ?>
                <line x1="<?= $pad ?>" y1="<?= $sh - $pad - $g * ($sh - $pad*2) ?>"
                      x2="<?= $sw - $pad ?>" y2="<?= $sh - $pad - $g * ($sh - $pad*2) ?>"
                      stroke="#1a1a22" stroke-width="1"/>
                <?php endforeach; ?>

                <?php if ($romance_pts): ?>
                <polyline points="<?= $romance_pts ?>" fill="none" stroke="#22d3ee" stroke-width="1.8" stroke-linejoin="round" opacity=".75"/>
                <?php endif; ?>
                <?php if ($action_pts): ?>
                <polyline points="<?= $action_pts ?>" fill="none" stroke="#f97316" stroke-width="2.2" stroke-linejoin="round"/>
                <?php endif; ?>

                <?php foreach ($trend_years as $i => $yr): ?>
                <?php if ($i % 3 === 0): ?>
                <text x="<?= $xFor($i) ?>" y="<?= $sh - 4 ?>" class="chart-year"><?= $yr ?></text>
                <?php endif; ?>
                <?php endforeach; ?>
            </svg>

            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📉</div>
                <div class="empty-text">
                    Trend data unavailable.<br>
                    <small style="font-family:var(--font-mono); font-size:9px; opacity:.55">
                        Fix: change <code>release_year</code> → <code>YEAR(release_date)</code> in DataService::getGenreTrend()
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /row-1 -->

    <!-- Strategic Overtake Banner — derived from $market_shares[0] -->
    <div class="banner">
        <div class="banner-icon">📈</div>
        <div>
            <div class="banner-title">Strategic Overtake</div>
            <p class="banner-body">
                Regional cinema now commands
                <strong style="color:var(--orange)"><?= $market_shares[0]['pct'] ?? '—' ?>%</strong>
                of total tracked revenue.
                <?= htmlspecialchars($market_shares[0]['label'] ?? 'Regional') ?>
                leads the database with the highest cumulative box office, signalling a structural shift in Indian cinema.
            </p>
        </div>
        <button class="btn">Download<br>Deep-Dive</button>
    </div>

    <!-- Row 2: Top Films + (Superstars + Rating×Revenue stacked) -->
    <!-- getTopGrossingMovies(20) + getTopActors(4) + getRatingRevenueCorrelation() -->
    <div class="row row-11">

        <!-- Top Regional Films table -->
        <div class="card">
            <div class="tbl-header">
                <div>
                    <div class="card-title">Top Regional Films</div>
                    <div class="card-sub" style="margin-bottom:0">Highest Grossing · Non-Hindi</div>
                </div>
                <span class="live-badge" style="position:static">Live</span>
            </div>

            <?php if (!empty($top_films)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Industry</th>
                        <th>IMDB</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $emojis = ['🎬','🎭','⚡','🏺','🗡️'];
                foreach ($top_films as $idx => $f):
                    $rev = (isset($f['revenue']) && $f['revenue'] > 0)
                        ? '₹' . formatRevenue($f['revenue'])
                        : '—';
                    $rating = (isset($f['rating_imdb']) && $f['rating_imdb'] > 0)
                        ? number_format((float)$f['rating_imdb'], 1)
                        : '—';
                ?>
                <tr>
                    <td>
                        <div class="film-cell">
                            <div class="film-thumb"><?= $emojis[$idx % 5] ?></div>
                            <div>
                                <div class="film-name"><?= htmlspecialchars($f['title']) ?></div>
                                <div class="film-dir"><?= htmlspecialchars($f['director'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="lang-tag"><?= industryName($f['language'] ?? '') ?></span></td>
                    <td>
                        <?php if ($rating !== '—'): ?>
                            <span class="star">★</span><span class="rating-val"><?= $rating ?></span>
                        <?php else: ?>
                            <span class="lang-tag">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="gross-val"><?= $rev ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p class="no-data">No non-Hindi films found in the database.</p>
            <?php endif; ?>
        </div>

        <!-- Right column: Superstars + Rating×Revenue stacked -->
        <div class="col-stack">

            <!-- Superstars — getTopActors(4) -->
            <div class="card">
                <div class="card-title">Regional Superstars</div>
                <div class="card-sub">Most Prolific Actors · DB Ranked</div>
                <div class="talent-list">
                <?php
                $avatars = ['🌟','💫','🎬','🎞️'];
                foreach ($topActors as $ti => $t):
                    $badge = $badgeDefs[$ti] ?? ['tag' => 'STAR', 'cls' => 'badge-gray'];
                ?>
                <div class="talent-item">
                    <div class="talent-avatar"><?= $avatars[$ti % 4] ?></div>
                    <div class="talent-info">
                        <div class="talent-name"><?= htmlspecialchars($t['name']) ?></div>
                        <div class="talent-meta"><?= number_format($t['count']) ?> films in DB</div>
                    </div>
                    <span class="talent-badge <?= $badge['cls'] ?>"><?= $badge['tag'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($topActors)): ?>
                    <p class="no-data">No actor data found.</p>
                <?php endif; ?>
                </div>
            </div>

            <!-- Rating × Revenue — getRatingRevenueCorrelation() -->
            <div class="card">
                <div class="card-title">Rating × Revenue</div>
                <div class="card-sub">Avg Revenue by Rating Tier</div>
                <?php
                $maxRev = max(array_column($ratingRevenue ?: [['avg_revenue'=>1]], 'avg_revenue'));
                ?>
                <div class="corr-list">
                <?php foreach ($ratingRevenue as $rc):
                    $barPct  = $maxRev > 0 ? round(($rc['avg_revenue'] / $maxRev) * 100) : 0;
                    $revLbl  = '₹' . formatRevenue($rc['avg_revenue']);
                ?>
                <div>
                    <div class="corr-meta">
                        <span class="corr-cat"><?= htmlspecialchars($rc['rating_category']) ?></span>
                        <span class="corr-val"><?= $revLbl ?> <span style="color:var(--muted)">(<?= number_format($rc['movie_count']) ?>)</span></span>
                    </div>
                    <div class="corr-bar-bg">
                        <div class="corr-bar-fill" style="width:<?= $barPct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($ratingRevenue)): ?>
                    <p class="no-data">No correlation data found.</p>
                <?php endif; ?>
                </div>
            </div>

        </div><!-- /col-stack -->
    </div><!-- /row-2 -->

        </div><!-- /industry-wrapper -->
        <footer>
            &copy; 2024 Cinemetrics India &bull; Digital Curator Intelligence Portal &bull; Powered by DataService.php
        </footer>
    </div><!-- /page-content -->
</main>

</body>
</html>