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
                        <div class="pm-item"><span class="pm-val"><?= $actor['career_start'] ?> - <?= $actor['career_latest'] ?></span><span class="pm-lbl">Active Years</span></div>
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
            <div class="film-grid">
                <?php foreach ($films as $f): ?>
                    <a href="movie_details.php?id=<?= $f['movie_id'] ?>" class="film-card">
                        <div class="fc-title"><?= htmlspecialchars($f['title']) ?></div>
                        <div class="fc-meta">
                            <span><?= $f['yr'] ?> • <?= $f['genres'] ?></span>
                            <span class="text-accent font-bold">★ <?= number_format($f['rating_imdb'], 1) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

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
                    y: { type: 'linear', display: true, position: 'left', grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8b8d9e' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#8b8d9e' } },
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
    </script>
</body>
</html>
