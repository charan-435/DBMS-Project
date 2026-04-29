<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit; }

$service = new DataService();
$director = $service->getDirectorDetails($id);
if (!$director) { die("Director not found."); }

$films = $service->getDirectorFilms($id, 20);
$trend = $service->getDirectorCareerTrend($id);

// Prepare Chart Data
$years = array_column($trend, 'yr');
$revenues = array_column($trend, 'revenue');
$ratings = array_column($trend, 'rating');
$titles = array_column($trend, 'title');

// Genre Distribution (calculated from films)
$genreStats = [];
foreach ($films as $f) {
    $g = $f['genres'];
    $genreStats[$g] = ($genreStats[$g] ?? 0) + 1;
}
arsort($genreStats);

// Extended stats
$collaborators = $service->getDirectorCollaborators($director['name'], 6);
$bestRating = !empty($films) ? max(array_column($films, 'rating_imdb')) : 0;
$bestFilm = null;
foreach ($films as $f) {
    if ((float)$f['rating_imdb'] === (float)$bestRating) { $bestFilm = $f; break; }
}
$careerSpan = ($director['career_latest'] && $director['career_start']) ? ($director['career_latest'] - $director['career_start'] + 1) : 0;
$avgRevPerFilm = $director['total_films'] > 0 ? round($director['total_revenue'] / $director['total_films']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($director['name']) ?> — Career Analytics</title>
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
            content: '🎥';
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
                <div class="person-avatar">🎥</div>
                <div class="person-info">
                    <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">DIRECTOR PROFILE</p>
                    <h1><?= htmlspecialchars($director['name']) ?></h1>
                    <div class="person-meta">
                        <div class="pm-item"><span class="pm-val"><?= $director['total_films'] ?></span><span class="pm-lbl">Total Films</span></div>
                        <div class="pm-item"><span class="pm-val">★ <?= number_format($director['avg_rating'], 1) ?></span><span class="pm-lbl">Avg Rating</span></div>
                        <div class="pm-item"><span class="pm-val">₹<?= formatRevenue($director['total_revenue']) ?></span><span class="pm-lbl">Total Box Office</span></div>
                        <div class="pm-item"><span class="pm-val"><?= $director['career_start'] ?> – <?= $director['career_latest'] ?></span><span class="pm-lbl">Active Years</span></div>
                        <div class="pm-item"><span class="pm-val">★ <?= number_format($bestRating, 1) ?></span><span class="pm-lbl">Best Film Rating</span></div>
                        <div class="pm-item"><span class="pm-val">₹<?= formatRevenue($avgRevPerFilm) ?></span><span class="pm-lbl">Avg Rev / Film</span></div>
                    </div>
                </div>
            </div>

            <div class="analytics-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="font-bold">Career Trajectory</h3>
                        <p class="text-xs text-muted">Revenue & Rating Performance over time</p>
                    </div>
                    <div class="chart-wrap"><canvas id="trajectoryChart"></canvas></div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="font-bold">Genre Distribution</h3>
                        <p class="text-xs text-muted">Creative versatility</p>
                    </div>
                    <div class="chart-wrap"><canvas id="genreChart"></canvas></div>
                </div>
            </div>

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
                    <div style="font-size: 1.4rem; font-weight: 800; color: var(--accent-green);"><?= count($genreStats) ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.25rem;">Genres Worked In</div>
                </div>
                <div style="background: var(--bg-card); padding: 1rem; text-align: center;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: #a68dff;">₹<?= formatRevenue($avgRevPerFilm) ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.25rem;">Avg Rev / Film</div>
                </div>
            </div>

            <?php if ($bestFilm): ?>
            <div style="background: var(--bg-card); border: 1px solid var(--accent-primary); border-radius: var(--radius-md); padding: 0.8rem 1.1rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 1.6rem;">🏆</span>
                <div>
                    <div style="font-size: 0.62rem; color: var(--accent-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem;">Best Rated Film</div>
                    <a href="movie_details.php?id=<?= $bestFilm['movie_id'] ?>" style="color: var(--text-primary); font-weight: 700; text-decoration: none; font-size: 0.95rem;"><?= htmlspecialchars($bestFilm['title']) ?></a>
                    <span style="color: #f5c518; font-size: 0.8rem; margin-left: 0.6rem;">★ <?= number_format($bestFilm['rating_imdb'], 1) ?></span>
                    <span style="color: var(--text-muted); font-size: 0.75rem; margin-left: 0.4rem;">(<?= $bestFilm['yr'] ?>)</span>
                </div>
            </div>
            <?php endif; ?>

            <h2 class="font-bold mb-4">Complete Filmography</h2>
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

            <?php if (!empty($collaborators)): ?>
            <h2 class="font-bold" style="margin-top: 2rem; margin-bottom: 1rem;">Frequent Collaborating Actors</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 2rem;">
                <?php foreach ($collaborators as $c): ?>
                <a href="actor_details.php?id=<?= $c['actor_id'] ?>" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.7rem 1.1rem; display: flex; align-items: center; gap: 0.75rem; transition: all 0.2s; text-decoration: none; color: inherit;" onmouseover="this.style.borderColor='var(--accent-primary)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--border-color)';this.style.transform='translateY(0)'">
                    <span style="font-size: 1.4rem;">🎭</span>
                    <div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-primary);"><?= htmlspecialchars($c['name']) ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);"><?= $c['films'] ?> film<?= $c['films'] > 1 ? 's' : '' ?> together</div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Per-Film Rating Comparison Chart -->
            <?php if (count($films) > 1): ?>
            <div class="chart-card" style="margin-top: 1rem;">
                <div class="chart-header">
                    <h3 class="font-bold">Per-Film Rating Comparison</h3>
                    <p class="text-xs text-muted">IMDb scores across all films — sorted by rating</p>
                </div>
                <?php
                    $sortedFilms = $films;
                    usort($sortedFilms, fn($a, $b) => $b['rating_imdb'] <=> $a['rating_imdb']);
                    $chartH = max(200, count($sortedFilms) * 38 + 40);
                ?>
                <div class="chart-wrap" style="height: <?= $chartH ?>px; margin-top: 1rem;">
                    <canvas id="perFilmChart"></canvas>
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
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129, 140, 248, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'IMDb Rating',
                    data: <?= json_encode($ratings) ?>,
                    borderColor: '#f5c518',
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
                    backgroundColor: ['#818cf8', '#34d399', '#fb923c', '#38bdf8', '#a78bfa', '#fb7185'],
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
            usort($films, fn($a, $b) => $b['rating_imdb'] <=> $a['rating_imdb']);
        ?>
        const ctxPF = document.getElementById('perFilmChart').getContext('2d');
        new Chart(ctxPF, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($f) => $f['title'] . ' (' . $f['yr'] . ')', $films)) ?>,
                datasets: [{
                    label: 'IMDb Rating',
                    data: <?= json_encode(array_column($films, 'rating_imdb')) ?>,
                    backgroundColor: <?= json_encode(array_map(fn($f) => $f['rating_imdb'] >= 7.5 ? 'rgba(52,211,153,0.8)' : ($f['rating_imdb'] >= 5 ? 'rgba(245,197,24,0.8)' : 'rgba(239,68,68,0.8)'), $films)) ?>,
                    borderRadius: 4,
                    barPercentage: 0.7
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#1e1f2a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, titleColor: '#f0f0f5', bodyColor: '#8b8d9e', callbacks: { label: ctx => '★ ' + ctx.raw } }
                },
                scales: {
                    x: { min: 0, max: 10, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8b8d9e', font: { size: 10 } }, title: { display: true, text: 'IMDb Rating', color: '#6b7280', font: { size: 10 } } },
                    y: { grid: { display: false }, ticks: { color: '#8b8d9e', font: { size: 10 } } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
