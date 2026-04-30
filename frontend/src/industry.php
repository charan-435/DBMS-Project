<?php
require_once __DIR__ . '/components/session.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$ds = new DataService();

// ── Language helpers ───────────────────────────────────────────────────────────
function industryName(string $code): string {
    $map = ['te'=>'Tollywood','ta'=>'Kollywood','hi'=>'Bollywood','kn'=>'Sandalwood','ml'=>'Mollywood','en'=>'Hollywood'];
    return $map[strtolower(trim($code))] ?? strtoupper($code);
}
function industryColor(string $code): string {
    $map = ['te'=>'#f97316','ta'=>'#22d3ee','hi'=>'#6b7280','kn'=>'#4ade80','ml'=>'#a78bfa','en'=>'#f87171'];
    return $map[strtolower(trim($code))] ?? '#64647a';
}
function industryEmoji(string $code): string {
    $map = ['te'=>'🎬','ta'=>'🎭','hi'=>'🎪','kn'=>'🌿','ml'=>'🌊','en'=>'🎥'];
    return $map[strtolower(trim($code))] ?? '🎞';
}

// ── Data fetching ──────────────────────────────────────────────────────────────
$langStats        = $ds->getLanguageStats(8);
$langRatingComp   = $ds->getLanguageRatingComparison();
$topGrossing      = $ds->getTopGrossingMovies(30);
$topRegionalAll   = $ds->getTopRegionalMovies(50); // pre-filtered non-Hindi
$topActorRevenue  = $ds->getTopActorsByRevenueForLanguage('all', 10); // global top 10 for initial render
$profitableDirs   = $ds->getTopDirectorsByRevenueForLanguage('all', 10); // global top 10
$yearlyTrend      = $ds->getYearlyRevenueTrend(2005);
$highRevByLang    = $ds->getHighestRevenueByLanguage(6);
$langRevAvg       = $ds->getLanguageRevenueAverages(6);

// ── Regional KPIs ──────────────────────────────────────────────────────────────
$regionalLangs = ['te','ta','kn','ml'];
$bollywoodRevenue  = 0;
$regionalRevenue   = 0;
$topRegionalIndustry = ['label'=>'—','rev'=>0,'color'=>'#f97316','lang'=>'te'];

foreach ($langStats as $row) {
    $lang = strtolower(trim($row['language'] ?? ''));
    if ($lang === 'hi') {
        $bollywoodRevenue = $row['total_revenue'];
    } elseif (in_array($lang, $regionalLangs)) {
        $regionalRevenue += $row['total_revenue'];
        if ($row['total_revenue'] > $topRegionalIndustry['rev']) {
            $topRegionalIndustry = [
                'label' => industryName($lang),
                'rev'   => $row['total_revenue'],
                'color' => industryColor($lang),
                'lang'  => $lang,
            ];
        }
    }
}

$revenueGapPct = $bollywoodRevenue > 0
    ? round((($regionalRevenue - $bollywoodRevenue) / $bollywoodRevenue) * 100, 1)
    : 0;

$goldenYear = ['yr'=>'—','rev'=>0,'count'=>0];
foreach ($yearlyTrend as $yr) {
    if ($yr['total_revenue'] > $goldenYear['rev']) {
        $goldenYear = ['yr'=>$yr['yr'],'rev'=>$yr['total_revenue'],'count'=>$yr['movie_count']];
    }
}

$bestRatingLang = ['label'=>'—','rating'=>0];
foreach ($langRatingComp as $row) {
    $lang = strtolower($row['language'] ?? '');
    if (in_array($lang, $regionalLangs) && $row['avg_rating'] > $bestRatingLang['rating']) {
        $bestRatingLang = ['label'=>industryName($lang),'rating'=>$row['avg_rating']];
    }
}

// ── Top non-Hindi films — use pre-filtered regional data ──────────────────────
$topRegionalFilms = array_slice($topRegionalAll, 0, 20);

// ── Market share donut ─────────────────────────────────────────────────────────
$grandTotal  = array_sum(array_column($langStats, 'total_revenue')) ?: 1;
$donutSlices = [];
$othersRev   = 0;
$donutColors = ['te'=>'#f97316','ta'=>'#22d3ee','hi'=>'#6b7280','kn'=>'#4ade80','ml'=>'#a78bfa'];
foreach ($langStats as $row) {
    $lang = strtolower(trim($row['language'] ?? ''));
    if (isset($donutColors[$lang])) {
        $donutSlices[] = [
            'label' => industryName($lang),
            'pct'   => round(($row['total_revenue'] / $grandTotal) * 100, 1),
            'color' => $donutColors[$lang],
            'rev'   => $row['total_revenue'],
        ];
    } else {
        $othersRev += $row['total_revenue'];
    }
}
if ($othersRev > 0) {
    $donutSlices[] = ['label'=>'Others','pct'=>round(($othersRev/$grandTotal)*100,1),'color'=>'#374151','rev'=>$othersRev];
}

function donutArc(float $pct, float $offset, string $color): string {
    $r=80; $cx=100; $cy=100;
    $circ = 2 * M_PI * $r;
    $dash = $pct / 100 * $circ;
    $gap  = $circ - $dash;
    $rot  = -90 + ($offset / 100 * 360);
    return sprintf(
        '<circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="20"
                 stroke-dasharray="%.2f %.2f" stroke-dashoffset="0"
                 transform="rotate(%.2f %s %s)" stroke-linecap="butt"/>',
        $cx,$cy,$r,$color,$dash,$gap,$rot,$cx,$cy
    );
}
$donutSVG = ''; $donutOffset = 0;
foreach ($donutSlices as $s) { $donutSVG .= donutArc($s['pct'],$donutOffset,$s['color']); $donutOffset += $s['pct']; }

// ── Yearly trend chart data ────────────────────────────────────────────────────
$maxYearlyRev = max(array_column($yearlyTrend ?: [['total_revenue'=>1]],'total_revenue'));
$sw=520; $sh=160; $pad=56; $rpad=20; $bpad=28; // left pad big for y-axis

// Build polyline points
$n = count($yearlyTrend);
$trendPts = '';
foreach ($yearlyTrend as $i => $row) {
    $x = $pad + ($i / max($n-1,1)) * ($sw - $pad - $rpad);
    $y = $sh - $bpad - (($row['total_revenue'] / $maxYearlyRev) * ($sh - $bpad - 16));
    $trendPts .= "$x,$y ";
}
$trendPts = trim($trendPts);

// Area path
$areaPath = '';
if ($n >= 2) {
    $pts = [];
    foreach ($yearlyTrend as $i => $row) {
        $x = $pad + ($i / ($n-1)) * ($sw - $pad - $rpad);
        $y = $sh - $bpad - (($row['total_revenue'] / $maxYearlyRev) * ($sh - $bpad - 16));
        $pts[] = [$x,$y];
    }
    $areaPath = "M {$pts[0][0]},{$pts[0][1]}";
    foreach ($pts as $p) $areaPath .= " L {$p[0]},{$p[1]}";
    $areaPath .= " L {$pts[count($pts)-1][0]},{$sh} L {$pts[0][0]},{$sh} Z";
}

// Peak point
$peakIdx=0; $peakVal=0;
foreach ($yearlyTrend as $i=>$row) { if($row['total_revenue']>$peakVal){$peakVal=$row['total_revenue'];$peakIdx=$i;} }
$peakX = $n>=2 ? $pad + ($peakIdx/($n-1))*($sw-$pad-$rpad) : $pad;
$peakY = $sh - $bpad - (($peakVal/$maxYearlyRev)*($sh-$bpad-16));

// Y-axis ticks — 5 evenly spaced values
$yTicks = [];
for ($t=0; $t<=4; $t++) {
    $val  = ($t/4) * $maxYearlyRev;
    $yPos = $sh - $bpad - (($t/4)*($sh-$bpad-16));
    $yTicks[] = ['val'=>$val,'y'=>$yPos];
}

// ── Head-to-head table data ────────────────────────────────────────────────────
$h2hLangs = ['te','ta','kn','ml','hi'];
$h2hData  = [];
foreach ($langStats as $row) {
    $lang = strtolower(trim($row['language'] ?? ''));
    if (in_array($lang,$h2hLangs)) {
        $h2hData[$lang] = ['movies'=>(int)$row['movie_count'],'revenue'=>(float)$row['total_revenue'],'rating'=>(float)$row['avg_rating'],'max_rating'=>0,'best_film_rev'=>0];
    }
}
foreach ($langRatingComp as $row) {
    $lang = strtolower(trim($row['language']??''));
    if (isset($h2hData[$lang])) { $h2hData[$lang]['rating']=(float)$row['avg_rating']; $h2hData[$lang]['max_rating']=(float)$row['max_rating']; }
}
foreach ($highRevByLang as $row) {
    $lang = strtolower(trim($row['language']??''));
    if (isset($h2hData[$lang])) $h2hData[$lang]['best_film_rev']=(float)$row['max_revenue'];
}
$maxMovies  = max(array_column($h2hData,'movies') ?: [1]);
$maxRevenue = max(array_column($h2hData,'revenue') ?: [1]);

// ── Unique languages present in films/actors for filter ───────────────────────
$filmLangs = [];
foreach ($topRegionalFilms as $f) {
    $lang = strtolower(trim($f['language']??''));
    if ($lang && $lang!=='hi' && !isset($filmLangs[$lang])) $filmLangs[$lang] = industryName($lang);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Cinematic Lens - Industry Intelligence</title>
<link rel="stylesheet" href="css/style.css">
<style>
/* ══ Industry page — uses global style.css tokens ══ */
.ind-page { width:100%; }
.ind-page .sec-label {
    font-size:.65rem; font-weight:700; letter-spacing:.15em; color:var(--accent-primary);
    text-transform:uppercase; margin-bottom:1rem; margin-top:2rem;
}
/* Grid helpers — unique to industry layout */
.ind-g4  { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem; }
.ind-g2  { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem; }
.ind-g21 { display:grid; grid-template-columns:1fr 2fr; gap:1rem; margin-bottom:1.25rem; }
.ind-g12 { display:grid; grid-template-columns:2fr 1fr; gap:1rem; margin-bottom:1.25rem; }
/* KPI accent bar */
.ind-kpi { position:relative; }
.ind-kpi::after { content:''; position:absolute; bottom:0; left:0; right:0; height:2px; background:var(--accent-primary); transform:scaleX(0); transform-origin:left; transition:transform .35s; }
.ind-kpi:hover::after { transform:scaleX(1); }
.ind-kpi .kpi-icon { position:absolute; top:1.25rem; right:1.25rem; font-size:1.5rem; opacity:.18; }
.ind-kpi .kpi-label { font-size:.65rem; font-weight:700; letter-spacing:.15em; color:var(--text-muted); text-transform:uppercase; margin-bottom:.6rem; }
.ind-kpi .kpi-val { font-size:1.8rem; font-weight:800; line-height:1; margin-bottom:.35rem; }
.ind-kpi .kpi-sub { font-size:.7rem; color:var(--text-muted); }
/* Donut */
.ind-donut-wrap { display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap; }
.ind-donut-svg { flex-shrink:0; overflow:visible; }
.ind-legend { display:flex; flex-direction:column; gap:.5rem; min-width:0; flex:1; }
.ind-leg-row { display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
.ind-leg-left { display:flex; align-items:center; gap:.5rem; min-width:0; }
.ind-leg-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.ind-leg-lbl { font-size:.8rem; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ind-leg-pct { font-size:.8rem; color:var(--text-primary); flex-shrink:0; font-weight:600; }
.ind-leg-bar { height:3px; background:var(--border-color); border-radius:2px; margin-top:2px; overflow:hidden; }
.ind-leg-fill { height:3px; border-radius:2px; }
/* Trend chart */
.ind-trend-svg { width:100%; overflow:visible; display:block; }
/* Filter bar */
.ind-filter { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.ind-fbtn {
    padding:.4rem .85rem; border-radius:20px; font-size:.7rem; font-weight:700;
    letter-spacing:.06em; border:1px solid var(--border-color);
    background:var(--bg-card); color:var(--text-muted);
    cursor:pointer; transition:all .18s; font-family:inherit;
}
.ind-fbtn:hover { border-color:var(--border-hover); color:var(--text-primary); }
.ind-fbtn.active { background:var(--accent-primary); border-color:var(--accent-primary); color:var(--bg-dark); }
/* H2H table */
.ind-h2h { width:100%; border-collapse:collapse; }
.ind-h2h th { font-size:.65rem; letter-spacing:.1em; color:var(--text-muted); text-transform:uppercase; text-align:left; padding:0 .85rem .75rem 0; border-bottom:1px solid var(--border-color); white-space:nowrap; }
.ind-h2h th:not(:first-child) { text-align:center; }
.ind-h2h td { padding:.85rem .85rem .85rem 0; border-bottom:1px solid var(--border-color); vertical-align:middle; font-size:.82rem; }
.ind-h2h td:not(:first-child) { text-align:center; }
.ind-h2h tr:last-child td { border-bottom:none; }
.ind-h2h tr:hover td { background:rgba(255,255,255,.02); }
.ind-pill { display:inline-flex; align-items:center; gap:.4rem; font-size:.85rem; font-weight:700; }
.ind-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.ind-minibar { height:4px; background:var(--border-color); border-radius:3px; overflow:hidden; width:70px; margin:4px auto 0; }
.ind-minibar-fill { height:4px; border-radius:3px; }
/* Film / talent rows */
.ind-film-row[hidden], .ind-actor-row[hidden], .ind-dir-row[hidden] { display:none; }
.ind-talent-list { display:flex; flex-direction:column; }
.ind-talent-item { display:flex; align-items:center; gap:.75rem; padding:.75rem 0; border-bottom:1px solid var(--border-color); }
.ind-talent-item:last-child { border-bottom:none; }
.ind-talent-av { width:36px; height:36px; border-radius:var(--radius-sm); background:var(--bg-highlight); display:grid; place-items:center; font-size:1rem; flex-shrink:0; border:1px solid var(--border-color); }
.ind-talent-name { font-size:.85rem; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ind-talent-meta { font-size:.7rem; color:var(--text-muted); margin-top:2px; }
.ind-badge { font-size:.6rem; font-weight:700; letter-spacing:.08em; padding:3px 8px; border-radius:4px; white-space:nowrap; }
.ind-b-pri { background:var(--accent-glow); color:var(--accent-primary); }
.ind-b-grn { background:var(--accent-green-glow); color:var(--accent-green); }
.ind-b-org { background:rgba(251,146,60,.12); color:var(--accent-orange); }
.ind-b-blu { background:rgba(56,189,248,.1); color:var(--accent-blue); }
.ind-b-mut { background:rgba(100,100,122,.14); color:var(--text-muted); }
/* Bar chart */
.ind-bars { display:flex; flex-direction:column; gap:.85rem; }
.ind-bar-meta { display:flex; justify-content:space-between; margin-bottom:4px; align-items:baseline; }
.ind-bar-lbl { font-size:.78rem; color:var(--text-secondary); }
.ind-bar-val { font-size:.78rem; color:var(--text-primary); font-weight:600; }
.ind-bar-bg { height:5px; background:var(--border-color); border-radius:3px; overflow:hidden; }
.ind-bar-fill { height:5px; border-radius:3px; transition:width .6s ease; }
/* Trend strip */
.ind-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-top:1rem; padding-top:1rem; border-top:1px solid var(--border-color); }
.ind-strip-lbl { font-size:.6rem; letter-spacing:.12em; color:var(--text-muted); text-transform:uppercase; margin-bottom:4px; }
.ind-strip-val { font-size:1.3rem; font-weight:800; }
/* Animation */
@keyframes indUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
.ind-g4 .card { animation: indUp .45s ease both; }
.ind-g4 .card:nth-child(1){animation-delay:.04s}
.ind-g4 .card:nth-child(2){animation-delay:.09s}
.ind-g4 .card:nth-child(3){animation-delay:.14s}
.ind-g4 .card:nth-child(4){animation-delay:.19s}
/* Responsive */
@media(max-width:900px){ .ind-g4{grid-template-columns:1fr 1fr} .ind-g21,.ind-g12,.ind-g2{grid-template-columns:1fr} }
@media(max-width:560px){ .ind-g4,.ind-g2{grid-template-columns:1fr} }
</style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-content">
<?php include 'components/topbar.php'; ?>
<div class="page-content">
<div class="ind-page">

<!-- ══ PAGE HEADER ══════════════════════════════════════════════════════════ -->
<div style="margin-bottom:1.5rem">
  <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">INDUSTRY INTELLIGENCE</p>
  <h1 style="font-size:2.25rem;font-weight:800">Regional <em style="color:var(--accent-primary);font-style:italic">Powerhouses</em></h1>
  <p class="mt-4" style="color:var(--text-secondary);font-size:.9rem;max-width:600px">How Tollywood, Kollywood, Sandalwood and Mollywood are rewriting the rules of Indian cinema — market share, quality benchmarks, and the stars driving it all.</p>
</div>

<!-- ══ SECTION 1 — REGIONAL KPIs ══════════════════════════════════════════ -->
<p class="sec-label">At a Glance</p>
<div class="ind-g4">

    <div class="card ind-kpi">
        <div class="kpi-icon"><?= industryEmoji($topRegionalIndustry['lang']) ?></div>
        <div class="kpi-label">Top Regional Industry</div>
        <div class="kpi-val" style="color:<?= $topRegionalIndustry['color'] ?>"><?= $topRegionalIndustry['label'] ?></div>
        <div class="kpi-sub">₹<?= formatRevenue($topRegionalIndustry['rev']) ?> total revenue</div>
    </div>

    <div class="card ind-kpi">
        <div class="kpi-icon">⚔️</div>
        <div class="kpi-label">Regional vs Bollywood</div>
        <div class="kpi-val" style="color:<?= $revenueGapPct>=0?'var(--accent-green)':'#f87171' ?>">
            <?= ($revenueGapPct>=0?'+':'').$revenueGapPct ?>%
        </div>
        <div class="kpi-sub"><?= $revenueGapPct>=0 ? 'Regional leads Bollywood' : 'Bollywood leads regional' ?></div>
    </div>

    <div class="card ind-kpi">
        <div class="kpi-icon">⭐</div>
        <div class="kpi-label">Best Quality Industry</div>
        <div class="kpi-val" style="color:#fbbf24"><?= $bestRatingLang['label'] ?></div>
        <div class="kpi-sub">avg IMDb <?= $bestRatingLang['rating'] ?></div>
    </div>

    <div class="card ind-kpi">
        <div class="kpi-icon">🏆</div>
        <div class="kpi-label">Regional Golden Year</div>
        <div class="kpi-val" style="color:var(--accent-blue)"><?= $goldenYear['yr'] ?></div>
        <div class="kpi-sub">₹<?= formatRevenue($goldenYear['rev']) ?> · <?= number_format($goldenYear['count']) ?> films</div>
    </div>

</div>


<!-- ══ SECTION 2 — MARKET SHARE + TREND ════════════════════════════════════ -->
<p class="sec-label">Revenue Landscape</p>
<div class="ind-g21">

    <!-- Donut -->
    <div class="card">
        <div class="chart-label">MARKET SHARE</div>
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem">Revenue by Industry</h2>
        <div class="ind-donut-wrap">
            <svg class="ind-donut-svg" width="200" height="200" viewBox="0 0 200 200">
                <circle cx="100" cy="100" r="80" fill="none" stroke="#1d1d26" stroke-width="20"/>
                <?= $donutSVG ?>
                <text x="100" y="94"  text-anchor="middle" style="font-size:1.3rem;font-weight:800;fill:var(--text-primary)">₹<?= formatRevenue($grandTotal) ?></text>
                <text x="100" y="113" text-anchor="middle" style="font-size:.55rem;letter-spacing:.1em;fill:var(--text-muted);text-transform:uppercase">Total Revenue</text>
            </svg>
            <div class="ind-legend">
                <?php foreach ($donutSlices as $s): ?>
                <div class="ind-leg-row">
                    <div class="ind-leg-left">
                        <div class="ind-leg-dot" style="background:<?= $s['color'] ?>"></div>
                        <span class="ind-leg-lbl"><?= htmlspecialchars($s['label']) ?></span>
                    </div>
                    <span class="ind-leg-pct"><?= $s['pct'] ?>%</span>
                </div>
                <div class="ind-leg-bar">
                    <div class="ind-leg-fill" style="width:<?= $s['pct'] ?>%;background:<?= $s['color'] ?>"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Trend chart with Y-axis -->
    <div class="card">
        <div class="chart-label">TREND ANALYSIS</div>
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem">Box Office Revenue Trend</h2>
        <p style="font-size:.7rem;color:var(--text-muted);margin-bottom:1rem">All Industries Combined · <?= $yearlyTrend[0]['yr']??'2005' ?> to <?= end($yearlyTrend)['yr']??'Present' ?></p>
        <?php if (!empty($yearlyTrend) && $n >= 2): ?>
        <svg class="ind-trend-svg" viewBox="0 0 <?=$sw?> <?=$sh?>" height="<?=$sh?>" style="overflow:visible">
            <defs>
                <linearGradient id="tGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="#f97316" stop-opacity=".45"/>
                    <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
                </linearGradient>
            </defs>

            <!-- Y-axis gridlines + labels -->
            <?php foreach ($yTicks as $tick): ?>
            <line x1="<?=$pad?>" y1="<?=$tick['y']?>" x2="<?=$sw-$rpad?>" y2="<?=$tick['y']?>" stroke="rgba(255,255,255,.04)" stroke-width="1"/>
            <text x="<?=$pad-8?>" y="<?=$tick['y']+3?>" style="font-size:10px;fill:var(--text-muted);text-anchor:end">₹<?= formatRevenue($tick['val']) ?></text>
            <?php endforeach; ?>

            <!-- Y-axis vertical line -->
            <line x1="<?=$pad?>" y1="16" x2="<?=$pad?>" y2="<?=$sh-$bpad?>" stroke="#28282f" stroke-width="1"/>

            <!-- Area fill -->
            <?php if ($areaPath): ?>
            <path d="<?=$areaPath?>" fill="url(#tGrad)"/>
            <?php endif; ?>

            <!-- Line -->
            <polyline points="<?=$trendPts?>" fill="none" stroke="#f97316" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"/>

            <!-- Peak dot + label -->
            <circle cx="<?=$peakX?>" cy="<?=$peakY?>" r="5" fill="#f97316"/>
            <circle cx="<?=$peakX?>" cy="<?=$peakY?>" r="9" fill="none" stroke="#f97316" stroke-width="1.5" opacity=".4"/>
            <!-- Peak badge -->
            <rect x="<?=$peakX-38?>" y="<?=$peakY-38?>" width="76" height="28" rx="5" fill="#f97316"/>
            <text x="<?=$peakX?>" y="<?=$peakY-26?>" text-anchor="middle" font-family="'DM Mono',monospace" font-size="8.5" fill="#fff" font-weight="700"><?= $yearlyTrend[$peakIdx]['yr'] ?? '' ?></text>
            <text x="<?=$peakX?>" y="<?=$peakY-14?>" text-anchor="middle" font-family="'DM Mono',monospace" font-size="8" fill="rgba(255,255,255,.85)">₹<?= formatRevenue($peakVal) ?></text>

            <!-- X-axis year labels -->
            <?php foreach ($yearlyTrend as $i=>$row): ?>
            <?php
            $xp = $pad + ($i/($n-1))*($sw-$pad-$rpad);
            if ($i%3===0 || $i===$n-1):
            ?>
            <text x="<?=$xp?>" y="<?=$sh-4?>" style="font-size:10px;fill:var(--text-muted);text-anchor:middle"><?=$row['yr']?></text>
            <?php endif; endforeach; ?>
        </svg>

        <!-- Summary strip -->
        <div class="ind-strip">
            <?php
            $totalPeriod = array_sum(array_column($yearlyTrend,'total_revenue'));
            $avgPerYear  = $n > 0 ? $totalPeriod / $n : 0;
            $totalFilms  = array_sum(array_column($yearlyTrend,'movie_count'));
            ?>
            <div>
                <div class="ind-strip-lbl">Period Revenue</div>
                <div class="ind-strip-val" style="color:var(--accent-primary)">₹<?= formatRevenue($totalPeriod) ?></div>
            </div>
            <div>
                <div class="ind-strip-lbl">Avg Per Year</div>
                <div class="ind-strip-val" style="color:var(--accent-blue)">₹<?= formatRevenue($avgPerYear) ?></div>
            </div>
            <div>
                <div class="ind-strip-lbl">Films Tracked</div>
                <div class="ind-strip-val"><?= number_format($totalFilms) ?></div>
            </div>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted);text-align:center;padding:1.5rem 0;font-size:.85rem">No yearly trend data available.</p>
        <?php endif; ?>
    </div>

</div><!-- /g21 -->


<!-- ══ SECTION 3 — INDUSTRY HEAD-TO-HEAD ═══════════════════════════════════ -->
<p class="sec-label">Industry Head-to-Head</p>
<div style="margin-bottom:1.25rem">
<div class="card">
    <div class="chart-label">COMPARISON MATRIX</div>
    <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:.4rem">Industry Head-to-Head</h2>
    <p style="font-size:.7rem;color:var(--text-muted);margin-bottom:1rem">Tollywood · Kollywood · Sandalwood · Mollywood vs Bollywood</p>
    <?php if (!empty($h2hData)): ?>
    <div style="overflow-x:auto">
    <table class="ind-h2h">
        <thead>
            <tr>
                <th>Industry</th>
                <th>Film Volume</th>
                <th>Avg IMDb</th>
                <th>Total Revenue</th>
                <th>Revenue Share</th>
                <th>Best Single Film</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $h2hOrder = ['te','ta','kn','ml','hi'];
        foreach ($h2hOrder as $lang):
            if (!isset($h2hData[$lang])) continue;
            $d      = $h2hData[$lang];
            $col    = industryColor($lang);
            $name   = industryName($lang);
            $movPct = $maxMovies  > 0 ? round(($d['movies']  / $maxMovies) *100) : 0;
            $revPct = $maxRevenue > 0 ? round(($d['revenue'] / $maxRevenue)*100) : 0;
            $ratPct = round(($d['rating'] / 10) * 100);
            $sharePct = $grandTotal > 0 ? round(($d['revenue']/$grandTotal)*100,1) : 0;
            $circ = 2*M_PI*15; $filled = round($sharePct/100*$circ,1);
            $isReg = in_array($lang,['te','ta','kn','ml']);
        ?>
        <tr>
            <td>
                <div class="ind-pill">
                    <div class="ind-dot" style="background:<?=$col?>"></div>
                    <span><?=$name?></span>
                    <?php if ($isReg): ?>
                    <span style="font-size:.55rem;padding:2px 6px;border-radius:3px;background:var(--accent-glow);color:var(--accent-primary);font-weight:700;letter-spacing:.08em">REGIONAL</span>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <div style="font-size:.82rem;font-weight:600"><?= number_format($d['movies']) ?></div>
                <div class="ind-minibar"><div class="ind-minibar-fill" style="width:<?=$movPct?>%;background:<?=$col?>"></div></div>
            </td>
            <td>
                <span style="color:#f5c518;font-size:.75rem">★</span><span style="font-size:.82rem;font-weight:600;color:#fbbf24"><?= number_format($d['rating'],2) ?></span>
                <div class="ind-minibar"><div class="ind-minibar-fill" style="width:<?=$ratPct?>%;background:#fbbf24"></div></div>
            </td>
            <td>
                <div style="font-size:.82rem;font-weight:600;color:<?=$col?>">₹<?= formatRevenue($d['revenue']) ?></div>
                <div class="ind-minibar"><div class="ind-minibar-fill" style="width:<?=$revPct?>%;background:<?=$col?>"></div></div>
            </td>
            <td>
                <div style="display:flex;align-items:center;justify-content:center">
                    <div style="width:44px;height:44px;position:relative">
                        <svg width="44" height="44" viewBox="0 0 44 44">
                            <circle cx="22" cy="22" r="15" fill="none" stroke="var(--border-color)" stroke-width="5"/>
                            <circle cx="22" cy="22" r="15" fill="none" stroke="<?=$col?>" stroke-width="5"
                                stroke-dasharray="<?=$filled?> <?=$circ?>"
                                stroke-dashoffset="<?=round($circ*0.25,1)?>"
                                stroke-linecap="round"/>
                        </svg>
                        <div style="position:absolute;inset:0;display:grid;place-items:center;font-size:9px;color:var(--text-primary);font-weight:600"><?=$sharePct?>%</div>
                    </div>
                </div>
            </td>
            <td>
                <?php if ($d['best_film_rev']>0): ?>
                <span style="font-size:.82rem;font-weight:600;color:var(--accent-primary)">₹<?= formatRevenue($d['best_film_rev']) ?></span>
                <?php else: ?><span style="color:var(--text-muted)">—</span><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?><p style="color:var(--text-muted);text-align:center;padding:1.5rem 0;font-size:.85rem">No comparison data available.</p><?php endif; ?>
</div>
</div>


<!-- ══ SECTION 4 — TOP REGIONAL FILMS (with filter) ════════════════════════ -->
<p class="sec-label">Box Office Champions</p>
<div class="ind-g12">

    <div class="card">
        <!-- Header + filter bar -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px;flex-wrap:wrap">
            <div>
                <div class="chart-label">TOP REGIONAL FILMS</div>
                <div class="chart-title" style="margin-bottom:0">Top Grossing Regional Films</div>
                <p style="font-size:.7rem;color:var(--text-muted);margin-top:4px">Highest Revenue · Non-Hindi</p>
            </div>
            <div class="ind-filter" id="filmFilter">
                <button class="ind-fbtn active" data-filter="all" onclick="filterFilms('filmFilter','filmTableBody','all')">All</button>
                <?php foreach ($filmLangs as $code => $name): ?>
                <button class="ind-fbtn"
                    data-filter="<?= $code ?>"
                    onclick="filterFilms('filmFilter','filmTableBody','<?= $code ?>')">
                    <?= $name ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($topRegionalFilms)): ?>
        <div style="overflow-x:auto">
        <table class="data-table" id="filmTable">
            <thead>
                <tr>
                    <th>#  Title</th>
                    <th>Industry</th>
                    <th>IMDb</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody id="filmTableBody">
            <?php
            $thumbEmojis = ['🏆','🥇','🥈','🥉','🎬','🎭','⚡','🌟','🎞','🏅','🎪','🎨','🎩','🌠','💫','🎯','🌺','🔥','💎','⭐'];
            foreach (array_slice($topRegionalFilms, 0, 10) as $idx => $f):
                $lang   = strtolower(trim($f['language'] ?? ''));
                $icolor = industryColor($lang);
                $rev    = ($f['revenue']??0)>0 ? '₹'.formatRevenue($f['revenue']) : '—';
                $rat    = ($f['rating_imdb']??0)>0 ? number_format((float)$f['rating_imdb'],1) : '—';
            ?>
            <tr class="ind-film-row" data-lang="<?= $lang ?>">
                <td>
                    <div class="film-cell">
                        <span style="font-size:.7rem;color:var(--text-muted);width:20px;flex-shrink:0" class="film-rank-num"><?= $idx+1 ?></span>
                        <div style="width:32px;height:32px;border-radius:var(--radius-sm);display:grid;place-items:center;font-size:1rem;flex-shrink:0;background:<?=$icolor?>1a"><?= $thumbEmojis[$idx%20] ?></div>
                        <div>
                            <div class="film-name"><?= htmlspecialchars($f['title']) ?></div>
                            <div class="film-meta"><?= htmlspecialchars($f['director']??'') ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="genre-badge" style="background:<?=$icolor?>1a;color:<?=$icolor?>;border:1px solid <?=$icolor?>33">
                        <?= industryName($lang) ?>
                    </span>
                </td>
                <td>
                    <?php if ($rat!=='—'): ?><span style="color:#f5c518;font-size:.75rem">★</span><span style="color:#fbbf24;font-weight:600"><?=$rat?></span>
                    <?php else: ?><span style="color:var(--text-muted)">—</span><?php endif; ?>
                </td>
                <td><span style="color:var(--accent-primary);font-weight:600"><?=$rev?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?><p style="color:var(--text-muted);text-align:center;padding:1.5rem 0;font-size:.85rem">No regional film data found.</p><?php endif; ?>
    </div>

    <!-- Avg revenue per language + quality rankings -->
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="chart-label">REVENUE EFFICIENCY</div>
            <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem">Avg Revenue per Film</h2>
            <p style="font-size:.65rem;color:var(--text-muted);margin-bottom:.75rem">By Language · Min 5 Films</p>
            <?php if (!empty($langRevAvg)): ?>
            <?php $maxAvg = max(array_column($langRevAvg,'avg_revenue'))?:1; ?>
            <div class="ind-bars">
            <?php foreach ($langRevAvg as $row):
                $lang = strtolower(trim($row['language']));
                $col  = industryColor($lang);
                $pct  = round(($row['avg_revenue']/$maxAvg)*100);
            ?>
            <div>
                <div class="ind-bar-meta">
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="width:8px;height:8px;border-radius:50%;background:<?=$col?>;flex-shrink:0"></div>
                        <span class="ind-bar-lbl"><?= industryName($lang) ?> <span style="font-size:.6rem;color:var(--text-muted)">(<?=$row['movie_count']?> films)</span></span>
                    </div>
                    <span class="ind-bar-val">₹<?= formatRevenue($row['avg_revenue']) ?></span>
                </div>
                <div class="ind-bar-bg">
                    <div class="ind-bar-fill" style="width:<?=$pct?>%;background:linear-gradient(90deg,<?=$col?>,<?=$col?>88)"></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?><p style="color:var(--text-muted);text-align:center;padding:1.5rem 0;font-size:.85rem">No data.</p><?php endif; ?>
        </div>

        <div class="card">
            <div class="chart-label">CRITICAL ACCLAIM</div>
            <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem">Quality Rankings</h2>
            <p style="font-size:.65rem;color:var(--text-muted);margin-bottom:.75rem">Avg IMDb by Industry</p>
            <?php if (!empty($langRatingComp)): ?>
            <?php $maxLR = max(array_column($langRatingComp,'avg_rating'))?:10; ?>
            <div class="ind-bars">
            <?php foreach ($langRatingComp as $row):
                $lang = strtolower(trim($row['language']));
                $col  = industryColor($lang);
                $pct  = round(($row['avg_rating']/$maxLR)*100);
            ?>
            <div>
                <div class="ind-bar-meta">
                    <span class="ind-bar-lbl"><?= industryName($lang) ?></span>
                    <span class="ind-bar-val" style="color:#fbbf24">★ <?= number_format($row['avg_rating'],2) ?>
                        <span style="color:var(--text-muted);font-size:.6rem">(peak <?= number_format($row['max_rating'],1) ?>)</span>
                    </span>
                </div>
                <div class="ind-bar-bg">
                    <div class="ind-bar-fill" style="width:<?=$pct?>%;background:linear-gradient(90deg,#fbbf24,#f59e0b88)"></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?><p style="color:var(--text-muted);text-align:center;padding:1.5rem 0;font-size:.85rem">No data.</p><?php endif; ?>
        </div>
    </div>

</div><!-- /g12 -->


<!-- ══ SECTION 5 — POWER PLAYERS ════════════════════════════════════════════ -->
<p class="sec-label">Power Players</p>
<div class="ind-g2">

    <!-- Top Actors by Revenue — AJAX-filtered per industry -->
    <div class="card">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
            <div>
                <div class="chart-label">BOX OFFICE STARS</div>
                <div class="chart-title" style="margin-bottom:0">Top Actors by Revenue</div>
                <p style="font-size:.65rem;color:var(--text-muted);margin-top:3px" id="actorSubtitle">All Industries · Top 5</p>
            </div>
            <div class="ind-filter" id="actorFilter">
                <button class="ind-fbtn active" data-filter="all" onclick="filterPowerPlayers('actorFilter','actorList','actorSubtitle','actors','all','All Industries')">All</button>
                <?php foreach (['te'=>'Tollywood','ta'=>'Kollywood','kn'=>'Sandalwood','ml'=>'Mollywood','hi'=>'Bollywood'] as $code=>$lname): ?>
                <button class="ind-fbtn" data-filter="<?=$code?>" onclick="filterPowerPlayers('actorFilter','actorList','actorSubtitle','actors','<?=$code?>','<?=$lname?>')">
                    <?=$lname?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="ind-talent-list" id="actorList">
            <?php
            $actorBadges = [['REVENUE KING','ind-b-pri'],['BOX OFFICE STAR','ind-b-blu'],['CROWD PULLER','ind-b-org'],['BANKABLE STAR','ind-b-grn'],['RELIABLE DRAW','ind-b-mut']];
            $actorIcons  = ['🌟','💫','⚡','🎬','🏆'];
            foreach (array_slice($topActorRevenue, 0, 5) as $ti => $t):
                $b = $actorBadges[$ti] ?? ['ACTOR','ind-b-mut'];
            ?>
            <div class="ind-talent-item">
                <div class="ind-talent-av"><?= $actorIcons[$ti % 5] ?></div>
                <div style="flex:1;min-width:0">
                    <div class="ind-talent-name"><?= htmlspecialchars($t['actor']) ?></div>
                    <div class="ind-talent-meta"><?= number_format($t['movie_count']) ?> films · ★ <?= number_format((float)($t['avg_rating']??0),1) ?> avg</div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <span class="ind-badge <?=$b[1]?>"><?=$b[0]?></span>
                    <div style="font-size:.7rem;color:var(--accent-primary);margin-top:3px;font-weight:600">₹<?= formatRevenue($t['total_revenue']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Most Profitable Directors — AJAX-filtered per industry -->
    <div class="card">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem;flex-wrap:wrap">
            <div>
                <div class="chart-label">HIT MAKERS</div>
                <div class="chart-title" style="margin-bottom:0">Most Profitable Directors</div>
                <p style="font-size:.65rem;color:var(--text-muted);margin-top:3px" id="dirSubtitle">All Industries · Top 5</p>
            </div>
            <div class="ind-filter" id="dirFilter">
                <button class="ind-fbtn active" data-filter="all" onclick="filterPowerPlayers('dirFilter','dirList','dirSubtitle','directors','all','All Industries')">All</button>
                <?php foreach (['te'=>'Tollywood','ta'=>'Kollywood','kn'=>'Sandalwood','ml'=>'Mollywood','hi'=>'Bollywood'] as $code=>$lname): ?>
                <button class="ind-fbtn" data-filter="<?=$code?>" onclick="filterPowerPlayers('dirFilter','dirList','dirSubtitle','directors','<?=$code?>','<?=$lname?>')">
                    <?=$lname?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="ind-talent-list" id="dirList">
            <?php
            $dirBadges = [['BLOCKBUSTER MAKER','ind-b-pri'],['BOX OFFICE GURU','ind-b-blu'],['HIT FACTORY','ind-b-org'],['CROWD MAGNET','ind-b-grn'],['CONSISTENT','ind-b-mut']];
            $dirIcons  = ['💼','🎬','🎭','🌟','⚡'];
            foreach (array_slice($profitableDirs, 0, 5) as $ti => $t):
                $b = $dirBadges[$ti % 5];
            ?>
            <div class="ind-talent-item">
                <div class="ind-talent-av" style="border-radius:50%;border:2px solid var(--accent-green);background:var(--accent-green-glow)"><?= $dirIcons[$ti % 5] ?></div>
                <div style="flex:1;min-width:0">
                    <div class="ind-talent-name"><?= htmlspecialchars($t['director']) ?></div>
                    <div class="ind-talent-meta"><?= $t['movie_count'] ?> films directed</div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <span class="ind-badge <?=$b[1]?>"><?=$b[0]?></span>
                    <div style="font-size:.7rem;color:var(--accent-green);margin-top:3px;font-weight:600">₹<?= formatRevenue($t['avg_revenue']) ?> avg</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

</div><!-- /ind-page -->
</div><!-- /page-content -->
</main>

<!-- ══ VANILLA JS — Filter logic ════════════════════════════════════════════ -->
<script>
// ── Industry config for JS ────────────────────────────────────────────────
const indConfig = {
    'te': { name: 'Tollywood', color: '#f97316' },
    'ta': { name: 'Kollywood', color: '#22d3ee' },
    'hi': { name: 'Bollywood', color: '#6b7280' },
    'kn': { name: 'Sandalwood', color: '#4ade80' },
    'ml': { name: 'Mollywood', color: '#a78bfa' },
    'en': { name: 'Hollywood', color: '#f87171' }
};
function getIndName(code) { return indConfig[code.toLowerCase()] ? indConfig[code.toLowerCase()].name : code.toUpperCase(); }
function getIndColor(code) { return indConfig[code.toLowerCase()] ? indConfig[code.toLowerCase()].color : '#64647a'; }

// ── Film table filter (AJAX) ──────────────────────────────────────────────
const thumbEmojis = ['🏆','🥇','🥈','🥉','🎬','🎭','⚡','🌟','🎞','🏅','🎪','🎨','🎩','🌠','💫','🎯','🌺','🔥','💎','⭐'];

function filterFilms(filterId, tbodyId, lang) {
    const bar = document.getElementById(filterId);
    bar.querySelectorAll('.ind-fbtn').forEach(btn =>
        btn.classList.toggle('active', btn.dataset.filter === lang)
    );

    const tbody = document.getElementById(tbodyId);
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1.5rem 0;color:var(--text-muted);font-size:.8rem">Loading...</td></tr>';

    // Fetch top 10 films for the selected language
    const url = `../../backend/api_industry_filter.php?type=films&lang=${lang}&limit=10`;
    fetch(url)
        .then(r => r.json())
        .then(json => {
            if (!json.ok || !json.data.length) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1.5rem 0;color:var(--text-muted);font-size:.85rem">No regional film data found.</td></tr>';
                return;
            }

            tbody.innerHTML = json.data.map((f, idx) => {
                const fLang = (f.language || '').toLowerCase().trim();
                const iColor = getIndColor(fLang);
                const iName = getIndName(fLang);
                const rev = (parseFloat(f.revenue) || 0) > 0 ? '₹' + formatRev(f.revenue) : '—';
                const rat = (parseFloat(f.rating_imdb) || 0) > 0 ? parseFloat(f.rating_imdb).toFixed(1) : '—';
                const emoji = thumbEmojis[idx % 20];

                let ratHtml = rat !== '—' 
                    ? `<span style="color:#f5c518;font-size:.75rem">★</span><span style="color:#fbbf24;font-weight:600">${rat}</span>`
                    : `<span style="color:var(--text-muted)">—</span>`;

                return `
                <tr class="ind-film-row" data-lang="${fLang}">
                    <td>
                        <div class="film-cell">
                            <span style="font-size:.7rem;color:var(--text-muted);width:20px;flex-shrink:0" class="film-rank-num">${idx+1}</span>
                            <div style="width:32px;height:32px;border-radius:var(--radius-sm);display:grid;place-items:center;font-size:1rem;flex-shrink:0;background:${iColor}1a">${emoji}</div>
                            <div>
                                <div class="film-name">${escHtml(f.title)}</div>
                                <div class="film-meta">${escHtml(f.director || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="genre-badge" style="background:${iColor}1a;color:${iColor};border:1px solid ${iColor}33">
                            ${iName}
                        </span>
                    </td>
                    <td>${ratHtml}</td>
                    <td><span style="color:var(--accent-primary);font-weight:600">${rev}</span></td>
                </tr>`;
            }).join('');
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1.5rem 0;color:var(--text-muted);font-size:.85rem">Error loading data.</td></tr>';
        });
}

// ── Power Players — AJAX per-industry filter ──────────────────────────────
const actorBadges = [
    ['REVENUE KING',     'ind-b-pri'],
    ['BOX OFFICE STAR',  'ind-b-blu'],
    ['CROWD PULLER',     'ind-b-org'],
    ['BANKABLE STAR',    'ind-b-grn'],
    ['RELIABLE DRAW',    'ind-b-mut']
];
const actorIcons = ['🌟','💫','⚡','🎬','🏆'];
const dirBadges = [
    ['BLOCKBUSTER MAKER','ind-b-pri'],
    ['BOX OFFICE GURU',  'ind-b-blu'],
    ['HIT FACTORY',      'ind-b-org'],
    ['CROWD MAGNET',     'ind-b-grn'],
    ['CONSISTENT',       'ind-b-mut']
];
const dirIcons  = ['💼','🎬','🎭','🌟','⚡'];

function formatRev(n) {
    n = parseFloat(n) || 0;
    if (n >= 1e9)  return (n / 1e9).toFixed(1) + 'B';
    if (n >= 1e7)  return (n / 1e7).toFixed(1) + 'Cr';
    if (n >= 1e5)  return (n / 1e5).toFixed(1) + 'L';
    return n.toLocaleString();
}

function filterPowerPlayers(filterId, listId, subtitleId, type, lang, industryLabel) {
    // Toggle active button
    document.getElementById(filterId).querySelectorAll('.ind-fbtn')
        .forEach(btn => btn.classList.toggle('active', btn.dataset.filter === lang));

    // Show loading state
    const list     = document.getElementById(listId);
    const subtitle = document.getElementById(subtitleId);
    list.innerHTML = '<div style="text-align:center;padding:1.5rem 0;color:var(--text-muted);font-size:.8rem">Loading…</div>';
    subtitle.textContent = industryLabel + ' · Top 5';

    const url = `../../backend/api_industry_filter.php?type=${type}&lang=${lang}&limit=5`;
    fetch(url)
        .then(r => r.json())
        .then(json => {
            if (!json.ok || !json.data.length) {
                list.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:1.5rem 0;font-size:.85rem">No data for this industry.</p>';
                return;
            }
            const isActor = type === 'actors';
            const badges  = isActor ? actorBadges : dirBadges;
            const icons   = isActor ? actorIcons  : dirIcons;

            list.innerHTML = json.data.map((t, i) => {
                const badge = badges[i] || ['TALENT','ind-b-mut'];
                const icon  = icons[i % icons.length];
                if (isActor) {
                    return `
                    <div class="ind-talent-item">
                        <div class="ind-talent-av">${icon}</div>
                        <div style="flex:1;min-width:0">
                            <div class="ind-talent-name">${escHtml(t.actor)}</div>
                            <div class="ind-talent-meta">${Number(t.movie_count).toLocaleString()} films · ★ ${parseFloat(t.avg_rating||0).toFixed(1)} avg</div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                            <span class="ind-badge ${badge[1]}">${badge[0]}</span>
                            <div style="font-size:.7rem;color:var(--accent-primary);margin-top:3px;font-weight:600">₹${formatRev(t.total_revenue)}</div>
                        </div>
                    </div>`;
                } else {
                    return `
                    <div class="ind-talent-item">
                        <div class="ind-talent-av" style="border-radius:50%;border:2px solid var(--accent-green);background:var(--accent-green-glow)">${icon}</div>
                        <div style="flex:1;min-width:0">
                            <div class="ind-talent-name">${escHtml(t.director)}</div>
                            <div class="ind-talent-meta">${t.movie_count} films directed</div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                            <span class="ind-badge ${badge[1]}">${badge[0]}</span>
                            <div style="font-size:.7rem;color:var(--accent-green);margin-top:3px;font-weight:600">₹${formatRev(t.avg_revenue)} avg</div>
                        </div>
                    </div>`;
                }
            }).join('');
        })
        .catch(() => {
            list.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:1.5rem 0;font-size:.85rem">Error loading data.</p>';
        });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

</body>
</html>
