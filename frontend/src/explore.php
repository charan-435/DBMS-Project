<?php
require_once __DIR__ . '/components/session.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// Fetch all data
$topActors = $service->getTopActorsDetailed(30);
$topDirectors = $service->getTopDirectorsDetailed(30);
$yearlyData = $service->getYearlyMovieCount();
$genreDistro = $service->getGenreDistribution();
$ratingDistro = $service->getRatingDistribution();
$decadeMovies = $service->getTopMoviesByDecade(5);

// Compute max values for bar scaling
$maxGenreCount = !empty($genreDistro) ? max(array_column($genreDistro, 'count')) : 1;
$maxYearlyCount = !empty($yearlyData) ? max(array_column($yearlyData, 'count')) : 1;
$maxRatingCount = !empty($ratingDistro) ? max(array_column($ratingDistro, 'count')) : 1;
$totalMovies = array_sum(array_column($genreDistro, 'count'));

// Collect unique genre names for filter dropdowns
$uniqueGenres = [];
foreach ($topDirectors as $p) {
    foreach (explode(', ', $p['genres'] ?? '') as $gn) {
        $gn = trim($gn);
        if ($gn && !in_array($gn, $uniqueGenres)) $uniqueGenres[] = $gn;
    }
}
foreach ($topActors as $p) {
    foreach (explode(', ', $p['genres'] ?? '') as $gn) {
        $gn = trim($gn);
        if ($gn && !in_array($gn, $uniqueGenres)) $uniqueGenres[] = $gn;
    }
}
sort($uniqueGenres);

// Avatar gradient colors
$gradients = [
    ['#7eafe8', '#4e8fd8'], ['#5cd6b6', '#3bb89a'], ['#a68dff', '#8565e0'],
    ['#ff8296', '#e0566d'], ['#f0c987', '#d4a653'], ['#6ea8fe', '#4a8ae0'],
    ['#e8a57e', '#d4845a'], ['#7ee8d6', '#4ed4bc'], ['#c084fc', '#a855f7'],
    ['#fb923c', '#ea580c'], ['#38bdf8', '#0284c7'], ['#4ade80', '#16a34a'],
];

$ratingColors = [
    '9+'      => ['#4ade80', 'rgba(74, 222, 128, 0.15)'],
    '8-9'     => ['#5cd6b6', 'rgba(92, 214, 182, 0.15)'],
    '7-8'     => ['#7eafe8', 'rgba(126, 175, 232, 0.15)'],
    '6-7'     => ['#f0c987', 'rgba(240, 201, 135, 0.15)'],
    '5-6'     => ['#ff8296', 'rgba(255, 130, 150, 0.15)'],
    'Below 5' => ['#ef4444', 'rgba(239, 68, 68, 0.15)'],
];

$genreColors = ['#7eafe8', '#5cd6b6', '#a68dff', '#ff8296', '#f0c987', '#6ea8fe', '#e8a57e', '#7ee8d6', '#c084fc', '#fb923c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Explore cinematic trends — top directors, actors, genre breakdowns, rating analysis, and historical highlights.">
<title>Explore Trends — The Cinematic Lens</title>
<link rel="stylesheet" href="css/style.css">
<style>
  /* ====== EXPLORE TRENDS PAGE STYLES ====== */

  .explore-hero {
    position: relative; border-radius: var(--radius-xl); overflow: hidden;
    min-height: 200px; display: flex; flex-direction: column; justify-content: center;
    padding: 2.5rem 3rem; margin-bottom: 2rem;
    background: linear-gradient(135deg, #0f1a2e 0%, #1a1030 40%, #2a1a1e 70%, #0f1117 100%);
    border: 1px solid var(--border-color);
  }
  .explore-hero::before {
    content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(ellipse at 30% 50%, rgba(126, 175, 232, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 50%, rgba(166, 141, 255, 0.06) 0%, transparent 60%);
    animation: heroGlow 8s ease-in-out infinite alternate;
  }
  @keyframes heroGlow {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(-5%, 3%) scale(1.05); }
  }
  .explore-hero > * { position: relative; z-index: 2; }
  .explore-hero h1 {
    font-size: 2.8rem; font-weight: 800; line-height: 1.1; margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #f0f0f5, #7eafe8);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  }
  .explore-hero p { color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; max-width: 600px; }

  /* Tab Navigation */
  .tab-nav {
    display: flex; gap: 0.25rem; margin-bottom: 2rem;
    background: var(--bg-card); border-radius: var(--radius-md);
    padding: 0.35rem; border: 1px solid var(--border-color); overflow-x: auto;
  }
  .tab-btn {
    padding: 0.65rem 1.25rem; border-radius: var(--radius-sm); border: none;
    background: transparent; color: var(--text-secondary); cursor: pointer;
    font-family: inherit; font-size: 0.8rem; font-weight: 600;
    transition: all 0.3s ease; white-space: nowrap; position: relative;
  }
  .tab-btn:hover { color: var(--text-primary); background: var(--bg-highlight); }
  .tab-btn.active {
    background: linear-gradient(135deg, rgba(126,175,232,0.2), rgba(166,141,255,0.15));
    color: var(--accent-primary); box-shadow: 0 0 20px rgba(126,175,232,0.1);
  }
  .tab-btn.active::after {
    content: ''; position: absolute; bottom: 0; left: 25%; width: 50%; height: 2px;
    background: var(--accent-primary); border-radius: 2px;
  }
  .tab-panel { display: none; animation: tabFadeIn 0.4s ease-out; }
  .tab-panel.active { display: block; }
  @keyframes tabFadeIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* ======= FILTER BAR (NEW) ======= */
  .explore-filter-bar {
    background: var(--bg-card); border: 1px solid var(--border-color);
    border-radius: var(--radius-md); padding: 0.85rem 1.25rem;
    margin-bottom: 1.5rem; display: flex; gap: 0.65rem; align-items: center;
    flex-wrap: wrap; position: relative;
  }
  .explore-filter-bar::before {
    content: '⚙'; position: absolute; left: -0.5rem; top: 50%; transform: translateY(-50%);
    background: var(--accent-primary); color: var(--bg-dark); width: 22px; height: 22px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 0.65rem;
  }
  .filter-pill {
    display: flex; flex-direction: column; gap: 0.15rem;
  }
  .filter-pill label {
    font-size: 0.55rem; font-weight: 700; letter-spacing: 0.12em;
    text-transform: uppercase; color: var(--text-muted);
  }
  .filter-pill select, .filter-pill input {
    padding: 0.4rem 0.55rem; border-radius: 6px;
    border: 1px solid var(--border-color); background: var(--bg-input);
    color: var(--text-primary); font-family: inherit; font-size: 0.78rem;
    min-width: 100px; transition: border-color 0.2s;
  }
  .filter-pill select:focus, .filter-pill input:focus {
    outline: none; border-color: var(--accent-primary);
    box-shadow: 0 0 10px rgba(126,175,232,0.1);
  }
  .filter-pill-search input { min-width: 180px; }
  .filter-reset-btn {
    background: transparent; border: 1px solid var(--border-color);
    color: var(--text-muted); padding: 0.4rem 0.8rem; border-radius: 6px;
    font-size: 0.7rem; font-family: inherit; cursor: pointer;
    transition: all 0.2s; font-weight: 600; margin-top: auto;
  }
  .filter-reset-btn:hover {
    border-color: var(--accent-primary); color: var(--accent-primary);
  }
  .filter-count-label {
    margin-left: auto; font-size: 0.72rem; color: var(--text-secondary);
    font-weight: 600; margin-top: auto; white-space: nowrap;
  }
  .filter-count-label strong { color: var(--accent-primary); }
  .no-results-msg {
    text-align: center; padding: 3rem; color: var(--text-muted);
    font-size: 0.9rem; display: none;
  }
  .no-results-msg.visible { display: block; }
  .no-results-msg span { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }

  /* Person Cards Grid */
  .person-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;
  }
  .person-card {
    background: var(--bg-card); border: 1px solid var(--border-color);
    border-radius: var(--radius-lg); padding: 1.25rem;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: default; position: relative; overflow: hidden;
  }
  .person-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--accent-primary), #a68dff);
    opacity: 0; transition: opacity 0.3s;
  }
  .person-card:hover {
    border-color: rgba(126,175,232,0.3); transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3), 0 0 30px rgba(126,175,232,0.05);
  }
  .person-card:hover::before { opacity: 1; }
  .person-card.hidden-by-filter { display: none !important; }

  .person-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
  .person-avatar {
    width: 52px; height: 52px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.1rem; color: var(--bg-dark);
    flex-shrink: 0; position: relative;
  }
  .person-avatar::after {
    content: ''; position: absolute; inset: -2px; border-radius: inherit;
    background: inherit; opacity: 0.3; filter: blur(8px); z-index: -1;
  }
  .person-name { font-size: 1rem; font-weight: 700; margin-bottom: 0.15rem; }
  .person-genre {
    font-size: 0.7rem; color: var(--text-muted); line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden; text-overflow: ellipsis; max-width: 200px;
  }
  .person-stats {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;
    margin-bottom: 0.75rem;
  }
  .person-stat {
    text-align: center; padding: 0.6rem 0.4rem; border-radius: var(--radius-sm);
    background: var(--bg-highlight); transition: transform 0.2s;
  }
  .person-stat:hover { transform: scale(1.05); }
  .person-stat-value { font-size: 1rem; font-weight: 800; line-height: 1; margin-bottom: 0.2rem; }
  .person-stat-label {
    font-size: 0.55rem; text-transform: uppercase; letter-spacing: 0.1em;
    color: var(--text-muted); font-weight: 600;
  }
  .person-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 0.72rem;
  }
  .career-span { color: var(--text-muted); }
  .best-rating { color: #f5c518; font-weight: 700; }

  /* Section Headers */
  .section-header {
    display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem;
  }
  .section-label {
    font-size: 0.6rem; font-weight: 700; letter-spacing: 0.2em;
    text-transform: uppercase; color: var(--accent-primary); margin-bottom: 0.35rem;
  }
  .section-title { font-size: 1.5rem; font-weight: 800; }
  .section-subtitle { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem; }

  /* Genre Distribution */
  .genre-dist-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.5rem; }
  .genre-bar-list { display: flex; flex-direction: column; gap: 0.9rem; }
  .genre-bar-item { transition: opacity 0.3s, max-height 0.4s; }
  .genre-bar-item.hidden-by-filter { display: none !important; }
  .genre-bar-header {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;
  }
  .genre-bar-name { font-weight: 600; font-size: 0.88rem; }
  .genre-bar-count { font-size: 0.75rem; color: var(--text-secondary); }
  .genre-bar-track {
    height: 8px; background: var(--bg-highlight); border-radius: 4px;
    overflow: hidden; position: relative;
  }
  .genre-bar-fill {
    height: 100%; border-radius: 4px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
  }
  .genre-bar-fill::after {
    content: ''; position: absolute; right: 0; top: 0; bottom: 0; width: 20px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15));
    border-radius: 0 4px 4px 0;
  }

  .genre-donut-wrapper {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
  }
  .genre-legend { display: flex; flex-direction: column; gap: 0.5rem; width: 100%; margin-top: 1.5rem; }
  .genre-legend-item {
    display: flex; align-items: center; gap: 0.6rem;
    padding: 0.45rem 0.6rem; border-radius: var(--radius-sm); transition: background 0.2s;
  }
  .genre-legend-item:hover { background: var(--bg-highlight); }
  .genre-legend-item.hidden-by-filter { display: none !important; }
  .genre-legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
  .genre-legend-name { font-size: 0.78rem; font-weight: 500; flex: 1; }
  .genre-legend-pct { font-size: 0.72rem; font-weight: 700; color: var(--text-secondary); }

  /* Rating Distribution */
  .rating-dist-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem;
  }
  .rating-card {
    background: var(--bg-card); border: 1px solid var(--border-color);
    border-radius: var(--radius-lg); padding: 1.25rem; text-align: center;
    transition: all 0.3s; position: relative; overflow: hidden;
  }
  .rating-card:hover {
    transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.25);
  }
  .rating-card-bracket { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.3rem; }
  .rating-card-label {
    font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.12em;
    color: var(--text-muted); margin-bottom: 0.75rem; font-weight: 600;
  }
  .rating-card-count { font-size: 2rem; font-weight: 800; margin-bottom: 0.15rem; }
  .rating-card-sub { font-size: 0.68rem; color: var(--text-muted); }

  /* Yearly Trend */
  .yearly-chart-wrapper { position: relative; padding: 1rem 0; }
  .yearly-bars { display: flex; align-items: flex-end; gap: 3px; height: 200px; padding: 0; }
  .yearly-bar {
    flex: 1; border-radius: 3px 3px 0 0; min-width: 6px;
    transition: all 0.3s; cursor: pointer; position: relative;
  }
  .yearly-bar:hover { filter: brightness(1.3); box-shadow: 0 -4px 15px rgba(126,175,232,0.2); }
  .yearly-bar.dimmed { opacity: 0.15; }
  .yearly-bar-tooltip {
    display: none; position: absolute; bottom: 100%; left: 50%;
    transform: translateX(-50%); padding: 0.5rem 0.75rem;
    background: var(--bg-panel); border: 1px solid var(--border-color);
    border-radius: var(--radius-sm); font-size: 0.7rem;
    white-space: nowrap; z-index: 10;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4); margin-bottom: 6px; pointer-events: none;
  }
  .yearly-bar:hover .yearly-bar-tooltip { display: block; }
  .yearly-labels {
    display: flex; justify-content: space-between;
    font-size: 0.62rem; color: var(--text-muted); margin-top: 0.5rem;
  }

  /* Decade Timeline */
  .decade-timeline { display: flex; flex-direction: column; gap: 1.5rem; }
  .decade-section.hidden-by-filter { display: none !important; }
  .decade-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
  .decade-badge {
    background: linear-gradient(135deg, rgba(126,175,232,0.2), rgba(166,141,255,0.15));
    color: var(--accent-primary); padding: 0.4rem 1rem;
    border-radius: var(--radius-sm); font-weight: 800; font-size: 1rem;
    border: 1px solid rgba(126,175,232,0.2);
  }
  .decade-line { flex: 1; height: 1px; background: var(--border-color); }
  .decade-movies {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.75rem;
  }
  .decade-movie {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1rem; background: var(--bg-card);
    border: 1px solid var(--border-color); border-radius: var(--radius-md); transition: all 0.25s;
  }
  .decade-movie:hover {
    border-color: var(--border-hover); background: var(--bg-card-hover); transform: translateX(4px);
  }
  .decade-movie.hidden-by-filter { display: none !important; }
  .decade-movie-rank {
    font-size: 1.4rem; font-weight: 800; color: var(--text-muted);
    opacity: 0.4; min-width: 30px; text-align: center;
  }
  .decade-movie-info { flex: 1; }
  .decade-movie-title { font-weight: 700; font-size: 0.88rem; margin-bottom: 0.2rem; }
  .decade-movie-meta { font-size: 0.7rem; color: var(--text-secondary); }
  .decade-movie-rating { font-weight: 800; font-size: 0.95rem; color: #f5c518; flex-shrink: 0; }

  .count-badge {
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(126,175,232,0.15); color: var(--accent-primary);
    font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.5rem;
    border-radius: 10px; margin-left: 0.4rem;
  }

  /* Genre table rows */
  .data-table tbody tr.hidden-by-filter { display: none !important; }
</style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">

      <!-- HERO -->
      <div class="explore-hero">
        <div class="section-label">&#x1F9ED; DISCOVER</div>
        <h1>Explore Trends</h1>
        <p>Dive into the data behind cinema — uncover the top directors, breakout actors, genre shifts, rating distributions, and decade-defining masterpieces.</p>
      </div>

      <!-- TAB NAV -->
      <div class="tab-nav" id="tabNav">
        <button class="tab-btn active" data-tab="directors" id="tab-btn-directors">
          &#x1F3AC; Directors <span class="count-badge"><?= count($topDirectors) ?></span>
        </button>
        <button class="tab-btn" data-tab="actors" id="tab-btn-actors">
          &#x2B50; Actors <span class="count-badge"><?= count($topActors) ?></span>
        </button>
        <button class="tab-btn" data-tab="genres" id="tab-btn-genres">
          &#x1F3AD; Genres <span class="count-badge"><?= count($genreDistro) ?></span>
        </button>
        <button class="tab-btn" data-tab="ratings" id="tab-btn-ratings">
          &#x1F4CA; Ratings
        </button>
        <button class="tab-btn" data-tab="yearly" id="tab-btn-yearly">
          &#x1F4C8; Yearly Trends
        </button>
        <button class="tab-btn" data-tab="decades" id="tab-btn-decades">
          &#x1F4C5; Decade Highlights
        </button>
      </div>

      <!-- ===== TAB: DIRECTORS ===== -->
      <div class="tab-panel active" id="panel-directors">
        <div class="section-header">
          <div>
            <div class="section-label">DIRECTORIAL EXCELLENCE</div>
            <div class="section-title">Top Directors</div>
            <div class="section-subtitle">Ranked by average rating (min. 2 films)</div>
          </div>
        </div>

        <!-- FILTER BAR -->
        <div class="explore-filter-bar" id="filter-directors">
          <div class="filter-pill filter-pill-search">
            <label>Search Name</label>
            <input type="text" id="dir-search" placeholder="Type to search..." autocomplete="off">
          </div>
          <div class="filter-pill">
            <label>Genre</label>
            <select id="dir-genre">
              <option value="">All Genres</option>
              <?php foreach ($uniqueGenres as $ug): ?>
                <option value="<?= htmlspecialchars($ug) ?>"><?= htmlspecialchars($ug) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-pill">
            <label>Min Rating</label>
            <select id="dir-min-rating">
              <option value="">Any</option>
              <option value="9">9+</option>
              <option value="8">8+</option>
              <option value="7">7+</option>
              <option value="6">6+</option>
            </select>
          </div>
          <div class="filter-pill">
            <label>Sort By</label>
            <select id="dir-sort">
              <option value="rating">Avg Rating</option>
              <option value="revenue">Revenue</option>
              <option value="films">Film Count</option>
              <option value="name">Name</option>
            </select>
          </div>
          <button class="filter-reset-btn" onclick="resetFilter('directors')">Reset</button>
          <div class="filter-count-label"><strong id="dir-visible-count"><?= count($topDirectors) ?></strong> of <?= count($topDirectors) ?> shown</div>
        </div>

        <div class="person-grid" id="directors-grid">
          <?php foreach ($topDirectors as $i => $d):
            $g = $gradients[$i % count($gradients)];
            $initials = strtoupper(substr($d['name'], 0, 1));
            $parts = explode(' ', $d['name']);
            if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
          ?>
          <div class="person-card"
               data-name="<?= htmlspecialchars(strtolower($d['name'])) ?>"
               data-genres="<?= htmlspecialchars(strtolower($d['genres'] ?? '')) ?>"
               data-rating="<?= $d['avg_rating'] ?>"
               data-revenue="<?= $d['total_revenue'] ?>"
               data-films="<?= $d['total_films'] ?>">
            <div class="person-header">
              <div class="person-avatar" style="background: linear-gradient(135deg, <?= $g[0] ?>, <?= $g[1] ?>);">
                <?= $initials ?>
              </div>
              <div>
                <div class="person-name"><?= htmlspecialchars($d['name']) ?></div>
                <div class="person-genre"><?= htmlspecialchars($d['genres'] ?? '') ?></div>
              </div>
            </div>
            <div class="person-stats">
              <div class="person-stat">
                <div class="person-stat-value text-accent"><?= $d['total_films'] ?></div>
                <div class="person-stat-label">Films</div>
              </div>
              <div class="person-stat">
                <div class="person-stat-value" style="color: #f5c518;">&#x2605; <?= $d['avg_rating'] ?></div>
                <div class="person-stat-label">Avg Rating</div>
              </div>
              <div class="person-stat">
                <div class="person-stat-value text-green">&#x20B9;<?= formatRevenue($d['total_revenue']) ?></div>
                <div class="person-stat-label">Revenue</div>
              </div>
            </div>
            <div class="person-footer">
              <span class="career-span">&#x1F4C5; <?= $d['career_start'] ?> — <?= $d['career_latest'] ?></span>
              <span class="best-rating">&#x1F3C6; Best: <?= $d['best_rating'] ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="no-results-msg" id="dir-no-results"><span>&#x1F50D;</span>No directors match your filters.</div>
      </div>

      <!-- ===== TAB: ACTORS ===== -->
      <div class="tab-panel" id="panel-actors">
        <div class="section-header">
          <div>
            <div class="section-label">STAR POWER</div>
            <div class="section-title">Top Actors</div>
            <div class="section-subtitle">Ranked by total box office revenue (min. 2 films)</div>
          </div>
        </div>

        <!-- FILTER BAR -->
        <div class="explore-filter-bar" id="filter-actors">
          <div class="filter-pill filter-pill-search">
            <label>Search Name</label>
            <input type="text" id="act-search" placeholder="Type to search..." autocomplete="off">
          </div>
          <div class="filter-pill">
            <label>Min Films</label>
            <select id="act-min-films">
              <option value="">Any</option>
              <option value="5">5+</option>
              <option value="10">10+</option>
              <option value="20">20+</option>
              <option value="50">50+</option>
            </select>
          </div>
          <div class="filter-pill">
            <label>Min Rating</label>
            <select id="act-min-rating">
              <option value="">Any</option>
              <option value="8">8+</option>
              <option value="7">7+</option>
              <option value="6">6+</option>
              <option value="5">5+</option>
            </select>
          </div>
          <div class="filter-pill">
            <label>Sort By</label>
            <select id="act-sort">
              <option value="revenue">Revenue</option>
              <option value="rating">Avg Rating</option>
              <option value="films">Film Count</option>
              <option value="name">Name</option>
            </select>
          </div>
          <button class="filter-reset-btn" onclick="resetFilter('actors')">Reset</button>
          <div class="filter-count-label"><strong id="act-visible-count"><?= count($topActors) ?></strong> of <?= count($topActors) ?> shown</div>
        </div>

        <div class="person-grid" id="actors-grid">
          <?php foreach ($topActors as $i => $a):
            $g = $gradients[$i % count($gradients)];
            $initials = strtoupper(substr($a['name'], 0, 1));
            $parts = explode(' ', $a['name']);
            if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
          ?>
          <div class="person-card"
               data-name="<?= htmlspecialchars(strtolower($a['name'])) ?>"
               data-genres="<?= htmlspecialchars(strtolower($a['genres'] ?? '')) ?>"
               data-rating="<?= $a['avg_rating'] ?>"
               data-revenue="<?= $a['total_revenue'] ?>"
               data-films="<?= $a['total_films'] ?>">
            <div class="person-header">
              <div class="person-avatar" style="background: linear-gradient(135deg, <?= $g[0] ?>, <?= $g[1] ?>);">
                <?= $initials ?>
              </div>
              <div>
                <div class="person-name"><?= htmlspecialchars($a['name']) ?></div>
                <div class="person-genre"><?= htmlspecialchars($a['genres'] ?? '') ?></div>
              </div>
            </div>
            <div class="person-stats">
              <div class="person-stat">
                <div class="person-stat-value text-accent"><?= $a['total_films'] ?></div>
                <div class="person-stat-label">Films</div>
              </div>
              <div class="person-stat">
                <div class="person-stat-value" style="color: #f5c518;">&#x2605; <?= $a['avg_rating'] ?></div>
                <div class="person-stat-label">Avg Rating</div>
              </div>
              <div class="person-stat">
                <div class="person-stat-value text-green">&#x20B9;<?= formatRevenue($a['total_revenue']) ?></div>
                <div class="person-stat-label">Revenue</div>
              </div>
            </div>
            <div class="person-footer">
              <span class="career-span">&#x1F4C5; <?= $a['career_start'] ?> — <?= $a['career_latest'] ?></span>
              <span class="best-rating">&#x1F3C6; Best: <?= $a['best_rating'] ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="no-results-msg" id="act-no-results"><span>&#x1F50D;</span>No actors match your filters.</div>
      </div>

      <!-- ===== TAB: GENRES ===== -->
      <div class="tab-panel" id="panel-genres">
        <div class="section-header">
          <div>
            <div class="section-label">GENRE BREAKDOWN</div>
            <div class="section-title">Genre Distribution</div>
            <div class="section-subtitle"><?= $totalMovies ?> films across <?= count($genreDistro) ?> genres</div>
          </div>
        </div>

        <!-- FILTER BAR -->
        <div class="explore-filter-bar" id="filter-genres">
          <div class="filter-pill filter-pill-search">
            <label>Search Genre</label>
            <input type="text" id="genre-search" placeholder="Filter genres..." autocomplete="off">
          </div>
          <div class="filter-pill">
            <label>Min Films</label>
            <select id="genre-min-count">
              <option value="">Any</option>
              <option value="10">10+</option>
              <option value="50">50+</option>
              <option value="100">100+</option>
              <option value="200">200+</option>
            </select>
          </div>
          <div class="filter-pill">
            <label>Min Rating</label>
            <select id="genre-min-rating">
              <option value="">Any</option>
              <option value="7">7+</option>
              <option value="6">6+</option>
              <option value="5">5+</option>
            </select>
          </div>
          <div class="filter-pill">
            <label>Sort By</label>
            <select id="genre-sort">
              <option value="count">Film Count</option>
              <option value="rating">Avg Rating</option>
              <option value="revenue">Revenue</option>
              <option value="name">Name</option>
            </select>
          </div>
          <button class="filter-reset-btn" onclick="resetFilter('genres')">Reset</button>
          <div class="filter-count-label"><strong id="genre-visible-count"><?= count($genreDistro) ?></strong> of <?= count($genreDistro) ?> shown</div>
        </div>

        <div class="genre-dist-grid">
          <div class="card" style="padding: 1.5rem;">
            <div class="chart-label" style="margin-bottom: 1.25rem;">FILMS PER GENRE</div>
            <div class="genre-bar-list" id="genre-bar-list">
              <?php foreach ($genreDistro as $gi => $g):
                $pct = round(($g['count'] / $maxGenreCount) * 100);
                $color = $genreColors[$gi % count($genreColors)];
              ?>
              <div class="genre-bar-item"
                   data-genre="<?= htmlspecialchars(strtolower($g['genre_name'])) ?>"
                   data-count="<?= $g['count'] ?>"
                   data-rating="<?= $g['avg_rating'] ?>"
                   data-revenue="<?= $g['total_revenue'] ?>">
                <div class="genre-bar-header">
                  <span class="genre-bar-name"><?= htmlspecialchars($g['genre_name']) ?></span>
                  <span class="genre-bar-count"><?= $g['count'] ?> films · &#x2605; <?= $g['avg_rating'] ?></span>
                </div>
                <div class="genre-bar-track">
                  <div class="genre-bar-fill" style="width: <?= $pct ?>%; background: linear-gradient(90deg, <?= $color ?>, <?= $color ?>aa);"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="card genre-donut-wrapper" style="padding: 1.5rem;">
            <div class="chart-label" style="margin-bottom: 0.75rem; text-align: center;">GENRE SHARE</div>
            <div style="font-size: 4rem; font-weight: 800; color: var(--accent-primary); text-align: center; line-height: 1;" id="genre-total-count">
              <?= count($genreDistro) ?>
            </div>
            <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.15em; text-align: center; margin-bottom: 1rem;">
              Genres Tracked
            </div>
            <div class="genre-legend" id="genre-legend">
              <?php foreach ($genreDistro as $gi => $g):
                $color = $genreColors[$gi % count($genreColors)];
                $sharePct = $totalMovies > 0 ? round(($g['count'] / $totalMovies) * 100, 1) : 0;
              ?>
              <div class="genre-legend-item"
                   data-genre="<?= htmlspecialchars(strtolower($g['genre_name'])) ?>"
                   data-count="<?= $g['count'] ?>"
                   data-rating="<?= $g['avg_rating'] ?>">
                <div class="genre-legend-dot" style="background: <?= $color ?>;"></div>
                <span class="genre-legend-name"><?= htmlspecialchars($g['genre_name']) ?></span>
                <span class="genre-legend-pct"><?= $sharePct ?>%</span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Genre Table -->
        <div class="card" style="margin-top: 1.5rem; padding: 0;">
          <div style="padding: 1.25rem 1.5rem 0;">
            <div class="chart-label">GENRE PERFORMANCE MATRIX</div>
            <div class="chart-title" style="margin-bottom: 0;">Revenue & Rating by Genre</div>
          </div>
          <table class="data-table" id="genre-table">
            <thead>
              <tr>
                <th>Genre</th><th>Films</th><th>Avg Rating</th><th>Total Revenue</th><th>Share</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($genreDistro as $gi => $g):
                $color = $genreColors[$gi % count($genreColors)];
                $sharePct = $totalMovies > 0 ? round(($g['count'] / $totalMovies) * 100, 1) : 0;
              ?>
              <tr data-genre="<?= htmlspecialchars(strtolower($g['genre_name'])) ?>"
                  data-count="<?= $g['count'] ?>"
                  data-rating="<?= $g['avg_rating'] ?>">
                <td>
                  <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <div style="width: 8px; height: 8px; border-radius: 2px; background: <?= $color ?>;"></div>
                    <span class="font-bold"><?= htmlspecialchars($g['genre_name']) ?></span>
                  </div>
                </td>
                <td><?= $g['count'] ?></td>
                <td><div class="imdb-score"><span class="imdb-star">&#x2605;</span> <?= $g['avg_rating'] ?></div></td>
                <td class="font-bold">&#x20B9;<?= formatRevenue($g['total_revenue']) ?></td>
                <td>
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 60px; height: 4px; background: var(--bg-highlight); border-radius: 2px; overflow: hidden;">
                      <div style="width: <?= $sharePct ?>%; height: 100%; background: <?= $color ?>; border-radius: 2px;"></div>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary);"><?= $sharePct ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===== TAB: RATINGS ===== -->
      <div class="tab-panel" id="panel-ratings">
        <div class="section-header">
          <div>
            <div class="section-label">QUALITY ANALYSIS</div>
            <div class="section-title">Rating Distribution</div>
            <div class="section-subtitle">How films are distributed across IMDb rating buckets</div>
          </div>
        </div>

        <!-- FILTER BAR -->
        <div class="explore-filter-bar" id="filter-ratings">
          <div class="filter-pill">
            <label>Highlight Bracket</label>
            <select id="rating-highlight">
              <option value="">All Brackets</option>
              <?php foreach ($ratingDistro as $r): ?>
                <option value="<?= htmlspecialchars($r['bracket']) ?>"><?= $r['bracket'] ?> (<?= $r['count'] ?> films)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-pill">
            <label>View</label>
            <select id="rating-view">
              <option value="cards">Cards</option>
              <option value="compare">Side-by-Side</option>
            </select>
          </div>
          <button class="filter-reset-btn" onclick="resetFilter('ratings')">Reset</button>
        </div>

        <div class="rating-dist-grid" id="rating-cards-grid">
          <?php foreach ($ratingDistro as $r):
            $bracket = $r['bracket'];
            $colors = $ratingColors[$bracket] ?? ['#7eafe8', 'rgba(126,175,232,0.15)'];
            $totalRated = array_sum(array_column($ratingDistro, 'count'));
            $pct = $totalRated > 0 ? round(($r['count'] / $totalRated) * 100, 1) : 0;
          ?>
          <div class="rating-card" data-bracket="<?= htmlspecialchars($bracket) ?>" style="border-color: <?= $colors[0] ?>22;">
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: <?= $colors[0] ?>;"></div>
            <div class="rating-card-bracket" style="color: <?= $colors[0] ?>;">
              <?php if ($bracket === '9+'): ?>&#x2605;<?php endif; ?> <?= $bracket ?>
            </div>
            <div class="rating-card-label">IMDb Rating</div>
            <div class="rating-card-count" style="color: <?= $colors[0] ?>;"><?= number_format($r['count']) ?></div>
            <div class="rating-card-sub">films · <?= $pct ?>% of total</div>
            <div style="margin-top: 0.75rem; height: 6px; background: var(--bg-highlight); border-radius: 3px; overflow: hidden;">
              <div style="width: <?= ($r['count'] / $maxRatingCount) * 100 ?>%; height: 100%; background: <?= $colors[0] ?>; border-radius: 3px; transition: width 0.8s;"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Rating Spectrum -->
        <div class="card" style="margin-top: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
          <div style="flex: 1; min-width: 200px;">
            <div class="chart-label">QUALITY INSIGHT</div>
            <div class="chart-title" style="font-size: 1.1rem;">Rating Spectrum</div>
            <p style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.3rem; line-height: 1.5;">
              Visual representation of the full rating distribution. Hover over each segment to see the count.
            </p>
          </div>
          <div style="flex: 2; min-width: 300px;">
            <div style="display: flex; height: 32px; border-radius: var(--radius-sm); overflow: hidden; gap: 2px;">
              <?php foreach ($ratingDistro as $r):
                $bracket = $r['bracket'];
                $colors = $ratingColors[$bracket] ?? ['#7eafe8', 'rgba(126,175,232,0.15)'];
                $w = $totalRated > 0 ? ($r['count'] / $totalRated) * 100 : 0;
              ?>
              <div style="width: <?= $w ?>%; background: <?= $colors[0] ?>; transition: all 0.3s; cursor: pointer;"
                   title="<?= $bracket ?>: <?= $r['count'] ?> films"
                   onmouseover="this.style.filter='brightness(1.3)'" onmouseout="this.style.filter='none'"></div>
              <?php endforeach; ?>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
              <?php foreach ($ratingDistro as $r):
                $colors = $ratingColors[$r['bracket']] ?? ['#7eafe8'];
              ?>
              <span style="font-size: 0.6rem; color: <?= $colors[0] ?>; font-weight: 600;"><?= $r['bracket'] ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== TAB: YEARLY TRENDS ===== -->
      <div class="tab-panel" id="panel-yearly">
        <div class="section-header">
          <div>
            <div class="section-label">PRODUCTION TIMELINE</div>
            <div class="section-title">Yearly Movie Production</div>
            <div class="section-subtitle">Number of films released per year since 1990</div>
          </div>
        </div>

        <!-- FILTER BAR -->
        <div class="explore-filter-bar" id="filter-yearly">
          <div class="filter-pill">
            <label>From Year</label>
            <input type="number" id="yearly-from" min="1990" max="2030" placeholder="1990" style="width: 80px;">
          </div>
          <div class="filter-pill">
            <label>To Year</label>
            <input type="number" id="yearly-to" min="1990" max="2030" placeholder="2025" style="width: 80px;">
          </div>
          <div class="filter-pill">
            <label>Min Films</label>
            <select id="yearly-min-count">
              <option value="">Any</option>
              <option value="50">50+</option>
              <option value="100">100+</option>
              <option value="200">200+</option>
              <option value="300">300+</option>
            </select>
          </div>
          <div class="filter-pill">
            <label>Highlight</label>
            <select id="yearly-highlight">
              <option value="">None</option>
              <option value="top5">Top 5 Years</option>
              <option value="above-avg">Above Average</option>
            </select>
          </div>
          <button class="filter-reset-btn" onclick="resetFilter('yearly')">Reset</button>
          <div class="filter-count-label"><strong id="yearly-visible-count"><?= count($yearlyData) ?></strong> years shown</div>
        </div>

        <div class="card" style="padding: 1.5rem;">
          <div class="chart-label" style="margin-bottom: 0.75rem;">FILMS PER YEAR</div>
          <div class="yearly-chart-wrapper">
            <div class="yearly-bars" id="yearly-bars">
              <?php foreach ($yearlyData as $yi => $y):
                $h = round(($y['count'] / $maxYearlyCount) * 100);
                $hue = 200 + ($yi * 5) % 60;
              ?>
              <div class="yearly-bar"
                   data-year="<?= $y['yr'] ?>"
                   data-count="<?= $y['count'] ?>"
                   data-rating="<?= $y['avg_rating'] ?>"
                   data-revenue="<?= $y['total_revenue'] ?>"
                   style="height: <?= max($h, 4) ?>%; background: linear-gradient(to top, hsl(<?= $hue ?>, 60%, 45%), hsl(<?= $hue ?>, 70%, 65%));">
                <div class="yearly-bar-tooltip">
                  <div style="font-weight: 700; margin-bottom: 2px;"><?= $y['yr'] ?></div>
                  <div><?= $y['count'] ?> films · &#x2605; <?= $y['avg_rating'] ?></div>
                  <div style="color: var(--accent-green);">&#x20B9;<?= formatRevenue($y['total_revenue']) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="yearly-labels">
              <?php
                $step = max(1, floor(count($yearlyData) / 8));
                for ($i = 0; $i < count($yearlyData); $i += $step):
              ?>
              <span><?= $yearlyData[$i]['yr'] ?></span>
              <?php endfor; ?>
              <?php if (!empty($yearlyData)): ?>
              <span><?= end($yearlyData)['yr'] ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="stats-grid" style="margin-top: 1.5rem;" id="yearly-stats">
          <?php
            $totalFilmsYearly = array_sum(array_column($yearlyData, 'count'));
            $avgPerYear = count($yearlyData) > 0 ? round($totalFilmsYearly / count($yearlyData), 1) : 0;
            $peakYear = !empty($yearlyData) ? $yearlyData[array_search(max(array_column($yearlyData, 'count')), array_column($yearlyData, 'count'))] : ['yr' => '—', 'count' => 0];
            $totalYearlyRevenue = array_sum(array_column($yearlyData, 'total_revenue'));
          ?>
          <div class="stat-card">
            <div class="stat-card-header">
              <span class="stat-card-label">AVG PER YEAR</span>
              <div class="stat-card-icon">&#x1F4CA;</div>
            </div>
            <div class="stat-card-value" id="yearly-avg"><?= $avgPerYear ?></div>
            <div class="stat-card-sub" style="color: var(--text-muted);">films per year</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <span class="stat-card-label">PEAK YEAR</span>
              <div class="stat-card-icon">&#x1F525;</div>
            </div>
            <div class="stat-card-value" id="yearly-peak"><?= $peakYear['yr'] ?></div>
            <div class="stat-card-sub" style="color: var(--accent-green);" id="yearly-peak-count"><?= $peakYear['count'] ?> films released</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-header">
              <span class="stat-card-label">TOTAL REVENUE</span>
              <div class="stat-card-icon">&#x1F4B0;</div>
            </div>
            <div class="stat-card-value" style="font-size: 1.5rem;">&#x20B9;<?= formatRevenue($totalYearlyRevenue) ?></div>
            <div class="stat-card-sub" style="color: var(--text-muted);">across all years</div>
          </div>
        </div>
      </div>

      <!-- ===== TAB: DECADES ===== -->
      <div class="tab-panel" id="panel-decades">
        <div class="section-header">
          <div>
            <div class="section-label">HISTORICAL HIGHLIGHTS</div>
            <div class="section-title">Decade Highlights</div>
            <div class="section-subtitle">Top-rated films from each era</div>
          </div>
        </div>

        <!-- FILTER BAR -->
        <div class="explore-filter-bar" id="filter-decades">
          <div class="filter-pill">
            <label>Decade</label>
            <select id="decade-filter">
              <option value="">All Decades</option>
              <?php foreach (array_keys($decadeMovies) as $dec): ?>
                <option value="<?= htmlspecialchars($dec) ?>"><?= $dec ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-pill filter-pill-search">
            <label>Search Title / Director</label>
            <input type="text" id="decade-search" placeholder="Search movies..." autocomplete="off">
          </div>
          <div class="filter-pill">
            <label>Min Rating</label>
            <select id="decade-min-rating">
              <option value="">Any</option>
              <option value="9">9+</option>
              <option value="8">8+</option>
              <option value="7">7+</option>
            </select>
          </div>
          <button class="filter-reset-btn" onclick="resetFilter('decades')">Reset</button>
        </div>

        <div class="decade-timeline" id="decade-timeline">
          <?php foreach ($decadeMovies as $decade => $movies): ?>
          <div class="decade-section" data-decade="<?= htmlspecialchars($decade) ?>">
            <div class="decade-header">
              <div class="decade-badge"><?= $decade ?></div>
              <div class="decade-line"></div>
            </div>
            <div class="decade-movies">
              <?php foreach ($movies as $mi => $m): ?>
              <div class="decade-movie"
                   data-title="<?= htmlspecialchars(strtolower($m['title'])) ?>"
                   data-director="<?= htmlspecialchars(strtolower($m['director'])) ?>"
                   data-genre="<?= htmlspecialchars(strtolower($m['genre_name'])) ?>"
                   data-rating="<?= $m['rating_imdb'] ?>">
                <div class="decade-movie-rank"><?= $mi + 1 ?></div>
                <div class="decade-movie-info">
                  <div class="decade-movie-title"><?= htmlspecialchars($m['title']) ?></div>
                  <div class="decade-movie-meta">
                    <?= htmlspecialchars($m['director']) ?> · <?= htmlspecialchars($m['genre_name']) ?>
                    <?php if ($m['revenue'] > 0): ?> · &#x20B9;<?= formatRevenue($m['revenue']) ?><?php endif; ?>
                  </div>
                </div>
                <div class="decade-movie-rating">&#x2605; <?= number_format($m['rating_imdb'], 1) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($decadeMovies)): ?>
          <div class="card" style="text-align: center; padding: 3rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">&#x1F4C5;</div>
            <h3>No decade data available</h3>
            <p class="text-muted">Run data import to populate movies across decades.</p>
          </div>
          <?php endif; ?>
        </div>
        <div class="no-results-msg" id="decade-no-results"><span>&#x1F4C5;</span>No movies match your decade filters.</div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. EXPLORE TRENDS — DATA ANALYTICS ENGINE.</div>
    </div>
  </main>

  <script>
    // ======= FILTER ENGINE =======

    // --- Directors Filter ---
    function filterDirectors() {
      const search = document.getElementById('dir-search').value.toLowerCase();
      const genre = document.getElementById('dir-genre').value.toLowerCase();
      const minRating = parseFloat(document.getElementById('dir-min-rating').value) || 0;
      const sortBy = document.getElementById('dir-sort').value;

      const grid = document.getElementById('directors-grid');
      const cards = [...grid.querySelectorAll('.person-card')];
      let visible = 0;

      cards.forEach(card => {
        const name = card.dataset.name;
        const genres = card.dataset.genres;
        const rating = parseFloat(card.dataset.rating);

        const matchSearch = !search || name.includes(search);
        const matchGenre = !genre || genres.includes(genre);
        const matchRating = rating >= minRating;

        if (matchSearch && matchGenre && matchRating) {
          card.classList.remove('hidden-by-filter');
          visible++;
        } else {
          card.classList.add('hidden-by-filter');
        }
      });

      // Sort visible cards
      const sortedCards = cards.filter(c => !c.classList.contains('hidden-by-filter'));
      sortedCards.sort((a, b) => {
        if (sortBy === 'rating') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        if (sortBy === 'revenue') return parseFloat(b.dataset.revenue) - parseFloat(a.dataset.revenue);
        if (sortBy === 'films') return parseInt(b.dataset.films) - parseInt(a.dataset.films);
        return a.dataset.name.localeCompare(b.dataset.name);
      });
      sortedCards.forEach(c => grid.appendChild(c));
      cards.filter(c => c.classList.contains('hidden-by-filter')).forEach(c => grid.appendChild(c));

      document.getElementById('dir-visible-count').textContent = visible;
      document.getElementById('dir-no-results').classList.toggle('visible', visible === 0);
    }

    // --- Actors Filter ---
    function filterActors() {
      const search = document.getElementById('act-search').value.toLowerCase();
      const minFilms = parseInt(document.getElementById('act-min-films').value) || 0;
      const minRating = parseFloat(document.getElementById('act-min-rating').value) || 0;
      const sortBy = document.getElementById('act-sort').value;

      const grid = document.getElementById('actors-grid');
      const cards = [...grid.querySelectorAll('.person-card')];
      let visible = 0;

      cards.forEach(card => {
        const name = card.dataset.name;
        const films = parseInt(card.dataset.films);
        const rating = parseFloat(card.dataset.rating);

        const matchSearch = !search || name.includes(search);
        const matchFilms = films >= minFilms;
        const matchRating = rating >= minRating;

        if (matchSearch && matchFilms && matchRating) {
          card.classList.remove('hidden-by-filter');
          visible++;
        } else {
          card.classList.add('hidden-by-filter');
        }
      });

      const sortedCards = cards.filter(c => !c.classList.contains('hidden-by-filter'));
      sortedCards.sort((a, b) => {
        if (sortBy === 'revenue') return parseFloat(b.dataset.revenue) - parseFloat(a.dataset.revenue);
        if (sortBy === 'rating') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        if (sortBy === 'films') return parseInt(b.dataset.films) - parseInt(a.dataset.films);
        return a.dataset.name.localeCompare(b.dataset.name);
      });
      sortedCards.forEach(c => grid.appendChild(c));
      cards.filter(c => c.classList.contains('hidden-by-filter')).forEach(c => grid.appendChild(c));

      document.getElementById('act-visible-count').textContent = visible;
      document.getElementById('act-no-results').classList.toggle('visible', visible === 0);
    }

    // --- Genres Filter ---
    function filterGenres() {
      const search = document.getElementById('genre-search').value.toLowerCase();
      const minCount = parseInt(document.getElementById('genre-min-count').value) || 0;
      const minRating = parseFloat(document.getElementById('genre-min-rating').value) || 0;
      const sortBy = document.getElementById('genre-sort').value;
      let visible = 0;

      // Filter bars
      const barList = document.getElementById('genre-bar-list');
      const bars = [...barList.querySelectorAll('.genre-bar-item')];
      bars.forEach(bar => {
        const genre = bar.dataset.genre;
        const count = parseInt(bar.dataset.count);
        const rating = parseFloat(bar.dataset.rating);
        const match = (!search || genre.includes(search)) && count >= minCount && rating >= minRating;
        bar.classList.toggle('hidden-by-filter', !match);
        if (match) visible++;
      });

      // Sort bars
      const sortedBars = bars.filter(b => !b.classList.contains('hidden-by-filter'));
      sortedBars.sort((a, b) => {
        if (sortBy === 'count') return parseInt(b.dataset.count) - parseInt(a.dataset.count);
        if (sortBy === 'rating') return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        if (sortBy === 'revenue') return parseFloat(b.dataset.revenue) - parseFloat(a.dataset.revenue);
        return a.dataset.genre.localeCompare(b.dataset.genre);
      });
      sortedBars.forEach(b => barList.appendChild(b));
      bars.filter(b => b.classList.contains('hidden-by-filter')).forEach(b => barList.appendChild(b));

      // Filter legend items
      document.querySelectorAll('#genre-legend .genre-legend-item').forEach(item => {
        const genre = item.dataset.genre;
        const count = parseInt(item.dataset.count);
        const rating = parseFloat(item.dataset.rating);
        const match = (!search || genre.includes(search)) && count >= minCount && rating >= minRating;
        item.classList.toggle('hidden-by-filter', !match);
      });

      // Filter table rows
      document.querySelectorAll('#genre-table tbody tr').forEach(row => {
        const genre = row.dataset.genre;
        const count = parseInt(row.dataset.count);
        const rating = parseFloat(row.dataset.rating);
        const match = (!search || genre.includes(search)) && count >= minCount && rating >= minRating;
        row.classList.toggle('hidden-by-filter', !match);
      });

      document.getElementById('genre-visible-count').textContent = visible;
      document.getElementById('genre-total-count').textContent = visible;
    }

    // --- Ratings Filter ---
    function filterRatings() {
      const highlight = document.getElementById('rating-highlight').value;
      document.querySelectorAll('#rating-cards-grid .rating-card').forEach(card => {
        if (!highlight || card.dataset.bracket === highlight) {
          card.style.opacity = '1';
          card.style.transform = 'scale(1)';
          card.style.boxShadow = highlight ? '0 0 25px rgba(126,175,232,0.2)' : '';
        } else {
          card.style.opacity = '0.25';
          card.style.transform = 'scale(0.95)';
          card.style.boxShadow = '';
        }
      });
    }

    // --- Yearly Filter ---
    function filterYearly() {
      const fromYear = parseInt(document.getElementById('yearly-from').value) || 0;
      const toYear = parseInt(document.getElementById('yearly-to').value) || 9999;
      const minCount = parseInt(document.getElementById('yearly-min-count').value) || 0;
      const highlightMode = document.getElementById('yearly-highlight').value;

      const bars = [...document.querySelectorAll('#yearly-bars .yearly-bar')];
      let visibleBars = [];

      bars.forEach(bar => {
        const yr = parseInt(bar.dataset.year);
        const count = parseInt(bar.dataset.count);
        const inRange = yr >= fromYear && yr <= toYear && count >= minCount;

        if (inRange) {
          bar.style.display = '';
          bar.classList.remove('dimmed');
          visibleBars.push(bar);
        } else {
          bar.classList.add('dimmed');
        }
      });

      // Highlight logic
      if (highlightMode === 'top5') {
        const sorted = [...visibleBars].sort((a, b) => parseInt(b.dataset.count) - parseInt(a.dataset.count));
        const top5years = sorted.slice(0, 5).map(b => b.dataset.year);
        visibleBars.forEach(bar => {
          bar.classList.toggle('dimmed', !top5years.includes(bar.dataset.year));
        });
      } else if (highlightMode === 'above-avg') {
        const avgCount = visibleBars.reduce((sum, b) => sum + parseInt(b.dataset.count), 0) / (visibleBars.length || 1);
        visibleBars.forEach(bar => {
          bar.classList.toggle('dimmed', parseInt(bar.dataset.count) < avgCount);
        });
      }

      document.getElementById('yearly-visible-count').textContent = visibleBars.length;

      // Update summary stats for visible range
      if (visibleBars.length > 0) {
        const counts = visibleBars.map(b => parseInt(b.dataset.count));
        const avg = (counts.reduce((s, c) => s + c, 0) / counts.length).toFixed(1);
        const maxIdx = counts.indexOf(Math.max(...counts));
        document.getElementById('yearly-avg').textContent = avg;
        document.getElementById('yearly-peak').textContent = visibleBars[maxIdx].dataset.year;
        document.getElementById('yearly-peak-count').textContent = counts[maxIdx] + ' films released';
      }
    }

    // --- Decades Filter ---
    function filterDecades() {
      const decade = document.getElementById('decade-filter').value;
      const search = document.getElementById('decade-search').value.toLowerCase();
      const minRating = parseFloat(document.getElementById('decade-min-rating').value) || 0;
      let anyVisible = false;

      document.querySelectorAll('#decade-timeline .decade-section').forEach(section => {
        const sectionDecade = section.dataset.decade;
        if (decade && sectionDecade !== decade) {
          section.classList.add('hidden-by-filter');
          return;
        }
        section.classList.remove('hidden-by-filter');

        let sectionHasVisible = false;
        section.querySelectorAll('.decade-movie').forEach(movie => {
          const title = movie.dataset.title;
          const director = movie.dataset.director;
          const rating = parseFloat(movie.dataset.rating);
          const matchSearch = !search || title.includes(search) || director.includes(search);
          const matchRating = rating >= minRating;

          if (matchSearch && matchRating) {
            movie.classList.remove('hidden-by-filter');
            sectionHasVisible = true;
            anyVisible = true;
          } else {
            movie.classList.add('hidden-by-filter');
          }
        });

        if (!sectionHasVisible) section.classList.add('hidden-by-filter');
      });

      document.getElementById('decade-no-results').classList.toggle('visible', !anyVisible);
    }

    // --- Reset helper ---
    function resetFilter(tab) {
      if (tab === 'directors') {
        document.getElementById('dir-search').value = '';
        document.getElementById('dir-genre').value = '';
        document.getElementById('dir-min-rating').value = '';
        document.getElementById('dir-sort').value = 'rating';
        filterDirectors();
      } else if (tab === 'actors') {
        document.getElementById('act-search').value = '';
        document.getElementById('act-min-films').value = '';
        document.getElementById('act-min-rating').value = '';
        document.getElementById('act-sort').value = 'revenue';
        filterActors();
      } else if (tab === 'genres') {
        document.getElementById('genre-search').value = '';
        document.getElementById('genre-min-count').value = '';
        document.getElementById('genre-min-rating').value = '';
        document.getElementById('genre-sort').value = 'count';
        filterGenres();
      } else if (tab === 'ratings') {
        document.getElementById('rating-highlight').value = '';
        document.getElementById('rating-view').value = 'cards';
        filterRatings();
      } else if (tab === 'yearly') {
        document.getElementById('yearly-from').value = '';
        document.getElementById('yearly-to').value = '';
        document.getElementById('yearly-min-count').value = '';
        document.getElementById('yearly-highlight').value = '';
        filterYearly();
      } else if (tab === 'decades') {
        document.getElementById('decade-filter').value = '';
        document.getElementById('decade-search').value = '';
        document.getElementById('decade-min-rating').value = '';
        filterDecades();
      }
    }

    // --- Wire up event listeners ---
    ['dir-search', 'dir-genre', 'dir-min-rating', 'dir-sort'].forEach(id => {
      const el = document.getElementById(id);
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', filterDirectors);
    });
    ['act-search', 'act-min-films', 'act-min-rating', 'act-sort'].forEach(id => {
      const el = document.getElementById(id);
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', filterActors);
    });
    ['genre-search', 'genre-min-count', 'genre-min-rating', 'genre-sort'].forEach(id => {
      const el = document.getElementById(id);
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', filterGenres);
    });
    ['rating-highlight', 'rating-view'].forEach(id => {
      document.getElementById(id).addEventListener('change', filterRatings);
    });
    ['yearly-from', 'yearly-to', 'yearly-min-count', 'yearly-highlight'].forEach(id => {
      const el = document.getElementById(id);
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', filterYearly);
    });
    ['decade-filter', 'decade-search', 'decade-min-rating'].forEach(id => {
      const el = document.getElementById(id);
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', filterDecades);
    });

    // ======= TAB SWITCHING =======
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const panel = document.getElementById('panel-' + btn.dataset.tab);
        if (panel) {
          panel.classList.add('active');
          // Animate visible cards
          panel.querySelectorAll('.person-card:not(.hidden-by-filter), .decade-movie:not(.hidden-by-filter), .rating-card').forEach((card, i) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(15px)';
            setTimeout(() => {
              card.style.transition = 'all 0.35s ease-out';
              card.style.opacity = '1';
              card.style.transform = 'translateY(0)';
            }, i * 40);
          });
          panel.querySelectorAll('.genre-bar-fill').forEach((bar, i) => {
            const w = bar.style.width; bar.style.width = '0%';
            setTimeout(() => { bar.style.width = w; }, i * 80);
          });
          panel.querySelectorAll('.yearly-bar').forEach((bar, i) => {
            const h = bar.style.height; bar.style.height = '0%';
            setTimeout(() => {
              bar.style.transition = 'height 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
              bar.style.height = h;
            }, i * 20);
          });
        }
      });
    });

    // Initial animation
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('#panel-directors .person-card').forEach((card, i) => {
        card.style.opacity = '0'; card.style.transform = 'translateY(15px)';
        setTimeout(() => {
          card.style.transition = 'all 0.35s ease-out';
          card.style.opacity = '1'; card.style.transform = 'translateY(0)';
        }, 100 + i * 50);
      });
    });
  </script>

</body>
</html>
