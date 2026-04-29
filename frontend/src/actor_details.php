<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit; }

$service = new DataService();
$actor = $service->getActorDetails($id);
if (!$actor) { die("Actor not found."); }

$films = $service->getActorFilms($id, 20);
$trend = $service->getActorCareerTrend($id);

// Prepare Chart Data
$years = array_column($trend, 'yr');
$revenues = array_column($trend, 'revenue');
$ratings = array_column($trend, 'rating');
$titles = array_column($trend, 'title');

// Genre Distribution
$genreStats = [];
foreach ($films as $f) {
    $g = $f['genres'];
    $genreStats[$g] = ($genreStats[$g] ?? 0) + 1;
}
arsort($genreStats);

// Extended stats
$bestRating = !empty($films) ? max(array_column($films, 'rating_imdb')) : 0;
$bestRevenue = !empty($films) ? max(array_column($films, 'revenue')) : 0;
$bestFilm = null;
$highestRevFilm = null;
foreach ($films as $f) {
    if ((float)$f['rating_imdb'] === (float)$bestRating && !$bestFilm) { $bestFilm = $f; }
    if ((float)$f['revenue'] === (float)$bestRevenue && !$highestRevFilm) { $highestRevFilm = $f; }
}
$careerSpan = ($actor['career_latest'] && $actor['career_start']) ? ($actor['career_latest'] - $actor['career_start'] + 1) : 0;
$avgRevPerFilm = $actor['total_films'] > 0 ? round($actor['total_revenue'] / $actor['total_films']) : 0;
$genreCount = count($genreStats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($actor['name']) ?> — Star Analytics</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .person-hero {
            background: linear-gradient(135deg, #11121a 0%, #161722 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 3rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 3rem;
            position: relative;
            overflow: hidden;
        }
        .person-hero::after {
            content: '🎭';
            position: absolute;
            right: -20px; bottom: -20px;
            font-size: 10rem;
            opacity: 0.03;
        }
        .person-avatar {
            width: 150px; height: 150px;
            background: var(--bg-highlight);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 4rem;
            border: 4px solid var(--border-color);
            box-shadow: 0 0 30px var(--accent-glow);
        }
        .person-info h1 { font-size: 3rem; font-weight: 800; margin-bottom: 0.5rem; }
        .person-meta { display: flex; gap: 2rem; margin-top: 1.5rem; }
        .pm-item { text-align: left; }
        .pm-val { font-size: 1.5rem; font-weight: 800; color: var(--accent-primary); display: block; }
        .pm-lbl { font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; }

        .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .chart-card { background: var(--bg-card); border-radius: var(--radius-lg); padding: 1.5rem; border: 1px solid var(--border-color); display: flex; flex-direction: column; min-height: 420px; }
        .chart-wrap { flex: 1; position: relative; min-height: 300px; }
        
        .film-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .film-card { 
            background: var(--bg-card); border-radius: var(--radius-md); padding: 1rem; 
            border: 1px solid var(--border-color); text-decoration: none; color: inherit;
            transition: all 0.2s;
        }
        .film-card:hover { border-color: var(--accent-primary); transform: translateY(-3px); }
        .fc-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 0.3rem; }
        .fc-meta { font-size: 0.75rem; color: var(--text-muted); display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    <main class="main-content">
        <?php include 'components/topbar.php'; ?>
        <div class="page-content">
            
            <div class="person-hero">
                <div class="person-avatar">🎭</div>
                <div class="person-info">
                    <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">ACTOR PROFILE</p>
                    <h1><?= htmlspecialchars($actor['name']) ?></h1>
                    <div class="person-meta">
                        <div class="pm-item"><span class="pm-val"><?= $actor['total_films'] ?></span><span class="pm-lbl">Total Films</span></div>
                        <div class="pm-item"><span class="pm-val">★ <?= number_format($actor['avg_rating'], 1) ?></span><span class="pm-lbl">Avg Rating</span></div>
                        <div class="pm-item"><span class="pm-val">₹<?= formatRevenue($actor['total_revenue']) ?></span><span class="pm-lbl">Total Box Office</span></div>
                        <div class="pm-item"><span class="pm-val"><?= $actor['career_start'] ?> – <?= $actor['career_latest'] ?></span><span class="pm-lbl">Active Years</span></div>
                        <div class="pm-item"><span class="pm-val"><?= $careerSpan ?> yrs</span><span class="pm-lbl">Career Span</span></div>
                        <div class="pm-item"><span class="pm-val">★ <?= number_format($bestRating, 1) ?></span><span class="pm-lbl">Best Film Rating</span></div>
                    </div>
                </div>
            </div>

            <div class="analytics-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="font-bold">Box Office Trajectory</h3>
                        <p class="text-xs text-muted">Revenue & Rating Performance over time</p>
                    </div>
                    <div class="chart-wrap"><canvas id="trajectoryChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="font-bold">Genre Footprint</h3>
                        <p class="text-xs text-muted">Creative range</p>
                    </div>
                    <div class="chart-wrap"><canvas id="genreChart"></canvas></div>
                </div>
            </div>

            <h2 class="font-bold mb-4">Complete Filmography</h2>

            <!-- Career Highlights Bar -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--border-color); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 1.25rem;">
                <div style="background: var(--bg-card); padding: 1rem; text-align: center;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: var(--accent-primary);"><?= $careerSpan ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.25rem;">Year Career Span</div>
                </div>
                <div style="background: var(--bg-card); padding: 1rem; text-align: center;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: #f5c518;">★ <?= number_format($bestRating, 1) ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.25rem;">Peak IMDb</div>
                </div>
                <div style="background: var(--bg-card); padding: 1rem; text-align: center;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: var(--accent-green);"><?= $genreCount ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.25rem;">Genres Explored</div>
                </div>
                <div style="background: var(--bg-card); padding: 1rem; text-align: center;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: #a68dff;">₹<?= formatRevenue($avgRevPerFilm) ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.25rem;">Avg Rev / Film</div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <?php if ($bestFilm): ?>
                <div style="flex: 1; min-width: 240px; background: var(--bg-card); border: 1px solid #f5c518; border-radius: var(--radius-md); padding: 0.8rem 1.1rem; display: flex; align-items: center; gap: 1rem;">
                    <span style="font-size: 1.6rem;">🏆</span>
                    <div>
                        <div style="font-size: 0.62rem; color: #f5c518; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">Best Rated Film</div>
                        <a href="movie_details.php?id=<?= $bestFilm['movie_id'] ?>" style="color: var(--text-primary); font-weight: 700; text-decoration: none; font-size: 0.9rem;"><?= htmlspecialchars($bestFilm['title']) ?></a>
                        <span style="color: #f5c518; font-size: 0.78rem; margin-left: 0.5rem;">★ <?= number_format($bestFilm['rating_imdb'], 1) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($highestRevFilm && (!$bestFilm || $highestRevFilm['movie_id'] !== $bestFilm['movie_id'])): ?>
                <div style="flex: 1; min-width: 240px; background: var(--bg-card); border: 1px solid var(--accent-green); border-radius: var(--radius-md); padding: 0.8rem 1.1rem; display: flex; align-items: center; gap: 1rem;">
                    <span style="font-size: 1.6rem;">💰</span>
                    <div>
                        <div style="font-size: 0.62rem; color: var(--accent-green); font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">Highest Grossing Film</div>
                        <a href="movie_details.php?id=<?= $highestRevFilm['movie_id'] ?>" style="color: var(--text-primary); font-weight: 700; text-decoration: none; font-size: 0.9rem;"><?= htmlspecialchars($highestRevFilm['title']) ?></a>
                        <span style="color: var(--accent-green); font-size: 0.78rem; margin-left: 0.5rem;">₹<?= formatRevenue($highestRevFilm['revenue']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="film-grid">
                <?php foreach ($films as $f): ?>
                    <a href="movie_details.php?id=<?= $f['movie_id'] ?>" class="film-card">
                        <div class="fc-title"><?= htmlspecialchars($f['title']) ?></div>
                        <div class="fc-meta">
                            <span><?= $f['yr'] ?> • <?= $f['genres'] ?></span>
                            <span class="text-accent font-bold">★ <?= number_format($f['rating_imdb'], 1) ?></span>
                        </div>
                        <div style="margin-top: 0.35rem; font-size: 0.7rem; color: var(--accent-green);">₹<?= formatRevenue($f['revenue']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Per-Film Rating & Revenue Charts -->
            <?php if (count($films) > 1):
                $sortedByRating = $films;
                usort($sortedByRating, fn($a, $b) => $b['rating_imdb'] <=> $a['rating_imdb']);
                $sortedByRev = $films;
                usort($sortedByRev, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
                $chartH = max(200, count($sortedByRating) * 36 + 40);
                $chartRevH = max(200, min(count($sortedByRev), 12) * 36 + 40);
            ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="font-bold">Filmography Rating Breakdown</h3>
                        <p class="text-xs text-muted">IMDb score per film — 🟢 ≥7.5 &nbsp; 🟡 ≥5.0 &nbsp; 🔴 &lt;5.0</p>
                    </div>
                    <div class="chart-wrap" style="height: <?= $chartH ?>px; margin-top: 1rem;"><canvas id="perFilmRatingChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="font-bold">Top Films by Box Office</h3>
                        <p class="text-xs text-muted">Revenue (top 12 films)</p>
                    </div>
                    <div class="chart-wrap" style="height: <?= $chartRevH ?>px; margin-top: 1rem;"><canvas id="perFilmRevChart"></canvas></div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <script>
        const ctxT = document.getElementById('trajectoryChart').getContext('2d');
        new Chart(ctxT, {
            type: 'line',
            data: {
                labels: <?= json_encode($years) ?>,
                datasets: [{
                    label: 'Revenue (Cr)',
                    data: <?= json_encode($revenues) ?>,
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52, 211, 153, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'IMDb Rating',
                    data: <?= json_encode($ratings) ?>,
                    borderColor: '#fb923c',
                    borderDash: [5, 5],
                    yAxisID: 'y1',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Revenue (₹)', color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8b8d9e' } },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'IMDb Rating', color: '#6b7280', font: { size: 10 } }, grid: { drawOnChartArea: false }, ticks: { color: '#8b8d9e', min: 0, max: 10 } },
                    x: { grid: { display: false }, ticks: { color: '#8b8d9e' } }
                },
                plugins: { 
                    legend: { labels: { color: '#8b8d9e' } },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                const movieTitles = <?= json_encode($titles) ?>;
                                return movieTitles[index] + ' (' + context[0].label + ')';
                            }
                        }
                    }
                }
            }
        });

        const ctxG = document.getElementById('genreChart').getContext('2d');
        new Chart(ctxG, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($genreStats)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($genreStats)) ?>,
                    backgroundColor: ['#34d399', '#818cf8', '#fb923c', '#38bdf8', '#a78bfa', '#fb7185'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'bottom', labels: { color: '#8b8d9e', boxWidth: 10, padding: 15 } } }
            }
        });
        <?php if (count($films) > 1):
            $sortedByRating = $films;
            usort($sortedByRating, fn($a, $b) => $b['rating_imdb'] <=> $a['rating_imdb']);
            $sortedByRev = array_slice($films, 0);
            usort($sortedByRev, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
            $sortedByRev = array_slice($sortedByRev, 0, 12);
        ?>
        new Chart(document.getElementById('perFilmRatingChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($f) => $f['title'] . ' (' . $f['yr'] . ')', $sortedByRating)) ?>,
                datasets: [{ label: 'IMDb Rating', data: <?= json_encode(array_column($sortedByRating, 'rating_imdb')) ?>,
                    backgroundColor: <?= json_encode(array_map(fn($f) => $f['rating_imdb'] >= 7.5 ? 'rgba(52,211,153,0.85)' : ($f['rating_imdb'] >= 5 ? 'rgba(245,197,24,0.85)' : 'rgba(239,68,68,0.85)'), $sortedByRating)) ?>,
                    borderRadius: 4, barPercentage: 0.7 }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e1f2a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#f0f0f5', bodyColor: '#8b8d9e', callbacks: { label: ctx => '★ ' + ctx.raw } } },
                scales: { x: { min: 0, max: 10, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8b8d9e', font: { size: 10 } }, title: { display: true, text: 'IMDb Rating', color: '#6b7280', font: { size: 10 } } }, y: { grid: { display: false }, ticks: { color: '#8b8d9e', font: { size: 9 } } } } }
        });
        new Chart(document.getElementById('perFilmRevChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($f) => $f['title'] . ' (' . $f['yr'] . ')', $sortedByRev)) ?>,
                datasets: [{ label: 'Revenue', data: <?= json_encode(array_column($sortedByRev, 'revenue')) ?>,
                    backgroundColor: 'rgba(129,140,248,0.8)', borderRadius: 4, barPercentage: 0.7 }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e1f2a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#f0f0f5', bodyColor: '#8b8d9e',
                    callbacks: { label: ctx => '₹' + (ctx.raw >= 1e7 ? (ctx.raw/1e7).toFixed(1)+'Cr' : ctx.raw.toLocaleString()) } } },
                scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8b8d9e', font: { size: 10 }, callback: v => v >= 1e7 ? (v/1e7).toFixed(0)+'Cr' : v }, title: { display: true, text: 'Revenue (₹)', color: '#6b7280', font: { size: 10 } } }, y: { grid: { display: false }, ticks: { color: '#8b8d9e', font: { size: 9 } } } } }
        });
        <?php endif; ?>
    </script>
</body>
</html>
