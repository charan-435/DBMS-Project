<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$movieId = $_GET['id'] ?? null;
if (!$movieId) { header("Location: movies.php"); exit; }

$service = new DataService();
$movie   = $service->getMovieWithCast($movieId);
if (!$movie) { die("Movie not found."); }

$directorFilms = $service->getDirectorFilms($movie['director_id'], 7);
$directorStats = $service->getTopDirectorsByCount(200);
$thisDir = null;
foreach ($directorStats as $d) {
    if ($d['director'] === $movie['director_name']) { $thisDir = $d; break; }
}

// Milestone insight
$filmsBefore = 0;
$dirCareer = $service->getDirectorCareerTrend($movie['director_id']);
foreach($dirCareer as $dc) {
    if ($dc['yr'] < $movie['release_year']) $filmsBefore += $dc['movie_count'];
    else if ($dc['yr'] == $movie['release_year']) break;
}
$nthFilm = $filmsBefore + 1;

$platformAvgRating = $service->getAvgRating();
$ratingPct  = $movie['rating_imdb'] > 0 ? round(($movie['rating_imdb'] / 10) * 100) : 0;
$ratingDiff = round($movie['rating_imdb'] - $platformAvgRating, 2);

// Revenue Benchmarks
$genreAvgRev = $service->getGenreAverageRevenue($movie['genre_id']);
$langAvgRev  = $service->getLanguageAverageRevenue($movie['language']);

function fmtRev($n) {
    if ($n >= 1e9) return '₹' . number_format($n / 1e9, 1) . 'B';
    if ($n >= 1e7) return '₹' . number_format($n / 1e7, 1) . 'Cr';
    if ($n >= 1e5) return '₹' . number_format($n / 1e5, 1) . 'L';
    return '₹' . number_format($n);
}

if ($movie['revenue'] > 5e9)     { $perfLabel = '🔥 Global Blockbuster'; $perfCls = '#f5c518'; }
elseif ($movie['revenue'] > 1e9) { $perfLabel = '💎 Commercial Success';  $perfCls = '#5cd6b6'; }
else                              { $perfLabel = '📈 Steady Performer';     $perfCls = '#7eafe8'; }

// Synergy Insight (Director + Lead Actor)
$synergy = null;
if (!empty($movie['cast'])) {
    $leadActorId = $movie['cast'][0]['actor_id'];
    $synergy = $service->getDirectorActorCollaboration($movie['director_id'], $leadActorId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($movie['title']) ?> — The Cinematic Lens</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  /* Hero */
  .movie-hero {
    background: linear-gradient(135deg, #1a1520 0%, #1e1f2a 70%, #0f1117 100%);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2.5rem;
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 2.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
  }
  .movie-hero::after {
    content: '';
    position: absolute;
    top: -40%; right: -10%;
    width: 450px; height: 450px;
    background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
    pointer-events: none;
  }
  .movie-poster {
    width: 200px; height: 300px;
    background: linear-gradient(135deg, #252635, #1a1b23);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 3.5rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    border: 1px solid var(--border-color);
    flex-shrink: 0;
  }
  .movie-title { font-size: 2.2rem; font-weight: 800; line-height: 1.15; margin-bottom: 0.75rem; }
  .badge-row { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
  .badge { padding: 0.28rem 0.75rem; border-radius: 20px; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
  .badge-genre { background: var(--accent-glow); color: var(--accent-primary); border: 1px solid rgba(129, 140, 248, 0.2); }
  .badge-lang  { background: var(--accent-green-glow);  color: var(--accent-green);   border: 1px solid rgba(52, 211, 153, 0.2);  }
  .badge-year  { background: rgba(255,255,255,0.06); color: var(--text-secondary);  border: 1px solid var(--border-color);    }

  /* Rating bar */
  .rating-display { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.25rem; }
  .rating-number { font-size: 3.2rem; font-weight: 800; color: #f5c518; line-height: 1; }
  .rating-meta { flex: 1; max-width: 240px; }
  .rating-bar-label { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 0.35rem; }
  .rating-track { height: 7px; background: var(--bg-highlight); border-radius: 4px; overflow: hidden; }
  .rating-fill { height: 100%; background: linear-gradient(90deg, #f5c518, #ffd000); border-radius: 4px; }
  .rating-vs { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem; }

  /* Stat banner */
  .stat-banner { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--border-color); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 1.5rem; }
  .stat-banner-item { background: var(--bg-card); text-align: center; padding: 1.2rem 0.5rem; }
  .sbi-val { font-size: 1.6rem; font-weight: 800; line-height: 1; }
  .sbi-lbl { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-top: 0.3rem; }

  /* Body grid */
  .body-grid { display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; }
  @media(max-width:1100px) { .body-grid { grid-template-columns: 1fr; } }

  /* Cards */
  .info-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 1.5rem; }
  .info-card h3 { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text-muted); margin-bottom: 1rem; padding-bottom: 0.6rem; border-bottom: 1px solid var(--border-color); }

  /* Cast */
  .cast-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
  .cast-chip { background: var(--bg-input); border: 1px solid var(--border-color); padding: 0.35rem 0.8rem; border-radius: 50px; font-size: 0.78rem; transition: all 0.2s; color: inherit; text-decoration: none; }
  .cast-chip:hover { border-color: var(--accent-primary); color: var(--accent-primary); transform: translateY(-1px); }

  /* Film rows */
  .film-row { display: flex; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--border-color); font-size: 0.82rem; gap: 0.75rem; }
  .film-row:last-child { border-bottom: none; }
  .film-row-title { flex: 1; color: var(--text-primary); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none; }
  .film-row-title:hover { color: var(--accent-primary); }
  .film-row-year { color: var(--text-muted); font-size: 0.72rem; flex-shrink: 0; }
  .film-row-rating { color: #f5c518; font-weight: 700; font-size: 0.78rem; flex-shrink: 0; }

  /* Stat rows */
  .stat-row { display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0; border-bottom: 1px solid var(--border-color); }
  .stat-row:last-child { border-bottom: none; }
  .stat-lbl { font-size: 0.75rem; color: var(--text-muted); }
  .stat-val { font-size: 0.85rem; font-weight: 700; }

  /* Analytics section */
  .analytics-section { margin-top: 2rem; }
  .analytics-section h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; color: var(--accent-primary); }
  .charts-layout { display: flex; flex-direction: column; gap: 2rem; }

  /* ── Chart Cards ─────────────────────────────────────────────────────────── */
  .chart-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem 1.5rem 1.25rem;
    box-sizing: border-box;
    overflow: visible;
    height: auto;
  }
  .chart-card h4 {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted);
    margin: 0 0 1rem 0;
    padding-bottom: 0.6rem;
    border-bottom: 1px solid var(--border-color);
  }
  
#dirFilmsWrap {
  min-height: 250px;
}

  /*
   * THE ONLY reliable way to size Chart.js (responsive:true, maintainAspectRatio:false):
   *   - Wrapper must be display:block (not flex/grid child without explicit size)
   *   - Wrapper must have an explicit px height
   *   - DO NOT set height on the canvas itself — Chart.js owns that
   */
  .chart-wrap {
    display: block;
    position: relative;
    width: 100%;
    height: 280px;
  }

  .chart-wrap-doughnut {
    display: block;
    position: relative;
    width: 280px;
    height: 280px;
    margin: 0 auto;
  }

  /* Back link */
  .btn-back { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--accent-primary); text-decoration: none; font-weight: 700; font-size: 0.82rem; margin-bottom: 1.25rem; transition: transform 0.2s; }
  .btn-back:hover { transform: translateX(-4px); }
</style>
</head>
<body>
  <?php include 'components/sidebar.php'; ?>
  <main class="main-content">
    <?php include 'components/topbar.php'; ?>
    <div class="page-content">
      <a href="javascript:history.back()" class="btn-back">← Back</a>

      <!-- ── HERO ── -->
      <div class="movie-hero">
        <div class="movie-poster">🎬</div>
        <div>
          <div class="badge-row">
            <span class="badge badge-genre"><?= htmlspecialchars($movie['genre_name']) ?></span>
            <span class="badge badge-lang"><?= strtoupper($movie['language']) ?></span>
            <span class="badge badge-year"><?= $movie['release_year'] ?></span>
          </div>
          <h1 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h1>

          <div class="rating-display">
            <div class="rating-number"><?= number_format($movie['rating_imdb'], 1) ?></div>
            <div class="rating-meta">
              <div class="rating-bar-label">IMDb Score</div>
              <div class="rating-track"><div class="rating-fill" style="width:<?= $ratingPct ?>%"></div></div>
              <div class="rating-vs">
                <?= $ratingDiff >= 0 ? '+' : '' ?><?= $ratingDiff ?> vs platform avg (<?= $platformAvgRating ?>)
              </div>
            </div>
          </div>

          <div class="insights-row">
            <div class="insight-card-mini">
              <div class="mini-label">CAREER MILESTONE</div>
              <div class="mini-val"><?= $nthFilm ?><span style="font-size: 0.8rem; opacity: 0.6;"> film</span></div>
              <div class="mini-desc">This film marks the <?= $nthFilm ?>-th theatrical release for director <?= htmlspecialchars($movie['director_name']) ?> in our dataset.</div>
            </div>

            <?php if ($synergy && $synergy['films_together'] > 1): ?>
            <div class="insight-card-mini" style="border-color: var(--accent-green);">
              <div class="mini-label">CREATIVE SYNERGY</div>
              <div class="mini-val"><?= $synergy['films_together'] ?><span style="font-size: 0.8rem; opacity: 0.6;"> collabs</span></div>
              <div class="mini-desc">This duo (<?= htmlspecialchars($movie['director_name']) ?> &amp; <?= htmlspecialchars($movie['cast'][0]['name']) ?>) averages ★ <?= number_format($synergy['avg_rating'], 1) ?> across their projects.</div>
            </div>
            <?php endif; ?>
          </div>

          <p style="color:var(--text-secondary); line-height:1.7; max-width:600px; font-size:0.86rem;">
            Directed by <a href="director_details.php?id=<?= $movie['director_id'] ?>" style="color:var(--accent-primary); font-weight:700; text-decoration: underline;"><?= htmlspecialchars($movie['director_name']) ?></a>,
            this <?= htmlspecialchars($movie['genre_name']) ?> production marks the **<?= $nthFilm ?><?= $nthFilm == 1 ? 'st' : ($nthFilm == 2 ? 'nd' : ($nthFilm == 3 ? 'rd' : 'th')) ?>** film in the director's storied career.
            With <?= count($movie['cast']) ?> credited cast members and a box office revenue of <?= fmtRev($movie['revenue']) ?>,
            <?php if ($genreAvgRev > 0): ?>
                the film performed **<?= round(($movie['revenue'] / $genreAvgRev) * 100) ?>%** relative to the genre average.
            <?php endif; ?>
          </p>
        </div>
      </div>

      <!-- ── STAT BANNER ── -->
      <div class="stat-banner">
        <div class="stat-banner-item">
          <div class="sbi-val" style="color:var(--accent-primary);"><?= fmtRev($movie['revenue']) ?></div>
          <div class="sbi-lbl">Box Office</div>
        </div>
        <div class="stat-banner-item">
          <div class="sbi-val" style="color:#f5c518;"><?= number_format($movie['rating_imdb'], 1) ?>/10</div>
          <div class="sbi-lbl">IMDb Rating</div>
        </div>
        <div class="stat-banner-item">
          <div class="sbi-val" style="color:var(--accent-green);"><?= count($movie['cast']) ?></div>
          <div class="sbi-lbl">Cast Members</div>
        </div>
        <div class="stat-banner-item">
          <div class="sbi-val" style="color:#a68dff;"><?= $thisDir ? $thisDir['movie_count'] : '—' ?></div>
          <div class="sbi-lbl">Director's Total Films</div>
        </div>
      </div>

      <!-- ── BODY ── -->
      <div class="body-grid">
        <!-- LEFT: Cast + Director Films -->
        <div>
          <div class="info-card">
            <h3>Cast</h3>
            <div class="cast-chips">
              <?php foreach ($movie['cast'] as $actor): ?>
                <a href="actor_details.php?id=<?= $actor['actor_id'] ?>" class="cast-chip" title="Appeared in <?= $actor['movie_count'] ?> films">
                  <?= htmlspecialchars($actor['name']) ?>
                  <span style="font-size: 0.6rem; opacity: 0.7; margin-left: 4px;">(<?= $actor['movie_count'] ?> films)</span>
                </a>
              <?php endforeach; ?>
              <?php if (empty($movie['cast'])): ?>
                <p style="color:var(--text-muted); font-size:0.82rem;">No cast information available.</p>
              <?php endif; ?>
            </div>
          </div>

          <?php if (count($directorFilms) > 1): ?>
          <div class="info-card">
            <h3>Other films by <?= htmlspecialchars($movie['director_name']) ?></h3>
            <?php foreach ($directorFilms as $df): ?>
              <?php if ($df['title'] === $movie['title']) continue; ?>
              <div class="film-row">
                <a href="movie_details.php?id=<?= $df['movie_id'] ?>" class="film-row-title"><?= htmlspecialchars($df['title']) ?></a>
                <span class="film-row-year"><?= $df['yr'] ?></span>
                <span class="film-row-rating">★ <?= number_format($df['rating_imdb'], 1) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- ── INLINE ANALYTICS ── -->
          <div class="analytics-section">
            <h2>Analytics</h2>
            <div class="charts-layout">

              <!-- 1. Rating gauge -->
              <div class="chart-card">
                <h4>Rating vs Platform Average</h4>
                <div class="chart-wrap" id="ratingWrap">
                  <canvas id="ratingChart"></canvas>
                </div>
              </div>

              <!-- 2. Director career doughnut -->
              <?php if ($thisDir): ?>
              <div class="chart-card">
                <h4>Director Career Stats</h4>
                <div class="chart-wrap-doughnut" id="directorWrap">
                  <canvas id="directorChart"></canvas>
                </div>
              </div>
              <?php endif; ?>

              <!-- 3. Revenue Benchmarks -->
              <div class="chart-card">
                <h4>Commercial Performance & Benchmarks</h4>
                <div class="chart-wrap" id="revenueWrap">
                  <canvas id="revenueChart"></canvas>
                </div>
              </div>

              <!-- 4. Director films ratings -->
              <?php if (count($directorFilms) > 1): ?>
              <div class="chart-card">
                <h4>Director's Films — Rating Comparison</h4>
                <?php
                  // 40px per film row + 40px padding, min 200px
                  $filmCount = count($directorFilms);
                  $dynHeight = max(200, $filmCount * 40 + 40);
                ?>
                <div class="chart-wrap" id="dirFilmsWrap" style="height:<?= $dynHeight ?>px;">
                  <canvas id="dirFilmsChart"></canvas>
                </div>
              </div>
              <?php endif; ?>

            </div>
          </div>
        </div>

        <!-- RIGHT: Details sidebar -->
        <div>
          <div class="info-card">
            <h3>Commercial</h3>
            <div class="stat-row">
              <span class="stat-lbl">Status</span>
              <span class="stat-val" style="color:<?= $perfCls ?>; font-size:0.8rem;"><?= $perfLabel ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">Revenue</span>
              <span class="stat-val" style="color:var(--accent-primary);"><?= fmtRev($movie['revenue']) ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">Exact Revenue</span>
              <span class="stat-val" style="font-size:0.72rem; color:var(--text-secondary);">₹<?= number_format($movie['revenue']) ?></span>
            </div>
          </div>

          <div class="info-card">
            <h3>Film Details</h3>
            <div class="stat-row">
              <span class="stat-lbl">Director</span>
              <span class="stat-val"><a href="movies.php?q=<?= urlencode($movie['director_name']) ?>" style="color:var(--accent-primary); text-decoration:none;"><?= htmlspecialchars($movie['director_name']) ?></a></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">Genre</span>
              <span class="stat-val"><?= htmlspecialchars($movie['genre_name']) ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">Language</span>
              <span class="stat-val"><?= strtoupper($movie['language']) ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">Release Year</span>
              <span class="stat-val"><?= $movie['release_year'] ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">IMDb Score</span>
              <span class="stat-val" style="color:#f5c518;">★ <?= number_format($movie['rating_imdb'], 1) ?></span>
            </div>
          </div>

          <?php if ($thisDir): ?>
          <div class="info-card">
            <h3>Director Stats</h3>
            <div class="stat-row">
              <span class="stat-lbl">Total Films</span>
              <span class="stat-val"><?= $thisDir['movie_count'] ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">Avg Rating</span>
              <span class="stat-val" style="color:#f5c518;">★ <?= number_format($thisDir['avg_rating'], 2) ?></span>
            </div>
            <div class="stat-row">
              <span class="stat-lbl">Total Revenue</span>
              <span class="stat-val" style="color:var(--accent-green);"><?= fmtRev($thisDir['total_revenue']) ?></span>
            </div>
          </div>
          <?php endif; ?>

          <div class="info-card">
            <h3>Browse Similar</h3>
            <a href="movies.php?genre=<?= urlencode($movie['genre_name']) ?>" style="display:block; padding:0.6rem 0; font-size:0.82rem; color:var(--accent-primary); text-decoration:none; border-bottom:1px solid var(--border-color);">🎭 More <?= htmlspecialchars($movie['genre_name']) ?> films →</a>
            <a href="movies.php?lang=<?= urlencode($movie['language']) ?>" style="display:block; padding:0.6rem 0; font-size:0.82rem; color:var(--accent-green); text-decoration:none; border-bottom:1px solid var(--border-color);">🌍 More <?= strtoupper($movie['language']) ?> films →</a>
            <a href="movies.php?min_year=<?= $movie['release_year'] - 2 ?>&max_year=<?= $movie['release_year'] + 2 ?>" style="display:block; padding:0.6rem 0; font-size:0.82rem; color:var(--text-secondary); text-decoration:none;">📅 Films from <?= $movie['release_year'] - 2 ?>–<?= $movie['release_year'] + 2 ?> →</a>
          </div>
        </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025.</div>
    </div>
  </main>

<script>
window.addEventListener('load', function () {

    // ── Shared tooltip ───────────────────────────────────────────────────────
    const TT = {
        backgroundColor: '#1e1f2a',
        borderColor: 'rgba(255,255,255,0.1)',
        borderWidth: 1,
        titleColor: '#f0f0f5',
        bodyColor: '#8b8d9e',
        padding: 10
    };

    // ── 1. Rating vs Platform Average ────────────────────────────────────────
    new Chart(document.getElementById('ratingChart'), {
        type: 'bar',
        data: {
            labels: ['This Film', 'Platform Avg'],
            datasets: [{
                data: [<?= $movie['rating_imdb'] ?>, <?= $platformAvgRating ?>],
                backgroundColor: ['rgba(245,197,24,0.85)', 'rgba(126,175,232,0.55)'],
                borderColor:     ['#f5c518', '#7eafe8'],
                borderWidth: 1,
                borderRadius: 8,
                barPercentage: 0.5,
                categoryPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
        onComplete: function () {
            this.resize();
        }
    },
            layout: {
  padding: {
    top: 30,
    right: 20,
    bottom: 10,
    left: 10
  }
},
            plugins: { legend: { display: false }, tooltip: TT },
            scales: {
                y: {
                    min: 0, max: 10,
                    grid:  { color: 'rgba(255,255,255,0.07)' },
                    ticks: { color: '#8b8d9e', font: { size: 11 }, stepSize: 2 }
                },
                x: {
                    grid:  { display: false },
                    ticks: { color: '#f0f0f5', font: { size: 12, weight: 'bold' } }
                }
            }
        }
    });

    // ── 2. Revenue Performance (Logarithmic) ────────────────────────────────
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: ['This Film', 'Genre Avg', 'Industry Avg'],
            datasets: [{
                data: [
                    Math.max(<?= $movie['revenue'] / 1e7 ?>, 0.1),
                    Math.max(<?= $genreAvgRev / 1e7 ?>, 0.1),
                    Math.max(<?= $langAvgRev / 1e7 ?>, 0.1)
                ],
                backgroundColor: [
                    'rgba(126,175,232,0.9)',
                    'rgba(166, 141, 255, 0.7)',
                    'rgba(92, 214, 182, 0.7)'
                ],
                borderWidth: 0,
                borderRadius: 6,
                barPercentage: 0.5,
                categoryPercentage: 0.6,
                clip: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
        onComplete: function () {
            this.resize();
        }
    },
            layout: {
  padding: {
    top: 30,
    right: 20,
    bottom: 10,
    left: 10
  }
},
            plugins: {
                legend: { display: false },
                tooltip: { ...TT, callbacks: { label: ctx => ' ₹' + ctx.raw + ' Cr' } }
            },
            scales: {
                y: {
                    type: 'logarithmic',
                    min: 0.1,
                    grid:  {display: false},
                    ticks: {
                        color: '#8b8d9e',
                        font:  { size: 11 },
                        callback: v => ({ 0.1:'₹0', 1:'₹1Cr', 10:'₹10Cr', 100:'₹100Cr', 1000:'₹1000Cr' })[v] ?? ''
                    }
                },
                x: {
                    grid:  { display: false },
                    ticks: { color: '#f0f0f5', font: { size: 11, weight: 'bold' } }
                }
            }
        }
    });

    <?php if ($thisDir): ?>
    // ── 3. Director Career Doughnut ──────────────────────────────────────────
    new Chart(document.getElementById('directorChart'), {
        type: 'doughnut',
        data: {
            labels: ['Avg Rating', 'Remaining'],
            datasets: [{
                data: [<?= $thisDir['avg_rating'] ?>, <?= 10 - $thisDir['avg_rating'] ?>],
                backgroundColor: ['rgba(245,197,24,0.9)', 'rgba(255,255,255,0.04)'],
                borderColor:     ['#f5c518', 'transparent'],
                borderWidth: [3, 0],
                hoverOffset: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
        onComplete: function () {
            this.resize();
        }
    },
            cutout: '70%',
            layout: { padding: 16 },
            plugins: {
                legend: { display: false },
                tooltip: { ...TT, callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.raw } }
            }
        }
    });
    document.getElementById('directorWrap').insertAdjacentHTML('beforeend',
        `<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                     text-align:center;pointer-events:none;line-height:1;">
            <div style="font-size:2.4rem;font-weight:900;color:#f5c518;"><?= number_format($thisDir['avg_rating'],1) ?></div>
            <div style="font-size:0.65rem;color:#8b8d9e;font-weight:700;text-transform:uppercase;
                        margin-top:0.35rem;letter-spacing:0.07em;">Career Avg</div>
         </div>`
    );
    <?php endif; ?>

    <?php
    $filmLabels = $filmRatings = $filmColors = [];
    foreach ($directorFilms as $df) {
        $filmLabels[]  = json_encode(mb_strimwidth($df['title'], 0, 20, '…'));
        $filmRatings[] = $df['rating_imdb'];
        $filmColors[]  = ($df['title'] === $movie['title'])
                       ? "'rgba(245,197,24,0.9)'"
                       : "'rgba(126,175,232,0.55)'";
    }
    $filmCount  = count($directorFilms);
    ?>
    <?php if ($filmCount > 1): ?>
    // ── 4. Director's Films Rating Comparison ────────────────────────────────
    new Chart(document.getElementById('dirFilmsChart'), {
        type: 'bar',
        data: {
            labels:   [<?= implode(',', $filmLabels) ?>],
            datasets: [{
                data:            [<?= implode(',', $filmRatings) ?>],
                backgroundColor: [<?= implode(',', $filmColors)  ?>],
                borderRadius: 5,
                barThickness: 24
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
        onComplete: function () {
            this.resize();
        }
    },
            indexAxis: 'y',
            layout: {
  padding: {
    top: 30,
    right: 20,
    bottom: 10,
    left: 10
  }
},
            plugins: { legend: { display: false }, tooltip: TT },
            scales: {
                x: {
                    min: 0, max: 10,
                    grid:  { color: 'rgba(255,255,255,0.07)' },
                    ticks: { color: '#8b8d9e', font: { size: 11 }, stepSize: 2 }
                },
                y: {
                    grid:  { display: false },
                    ticks: { color: '#f0f0f5', font: { size: 11, weight: '600' } }
                }
            }
        }
    });
    <?php endif; ?>

}); // end window.load
</script>
</body>
</html>