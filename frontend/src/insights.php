<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// New Crazy Insights
$flopMasterpieces = $service->getFlopMasterpieces(3);
$disasters = $service->getCommercialDisasters(3);
$oneHitWonders = $service->getOneHitWonders(3);
$versatileActors = $service->getActorGenreVersatility(5);
$actorDuos = $service->getRepeatCollaborators(3, 5);
$genreTrend = $service->getGenreTrend();
$langChamps = $service->getLanguageRevenueAverages(3);

// Additional insights
$langRatingComp = $service->getLanguageRatingComparison();
$decadeRatings  = $service->getDecadeRatings();
$topDirsByCount = $service->getTopDirectorsByCount(3);

$barColors = ['var(--accent-primary)', '#5cd6b6', '#6ea8fe', '#a68dff', '#ff8296'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Cinematic Lens - Insights</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .insight-card { display: flex; flex-direction: column; height: 100%; }
    .q-number { font-size: 0.7rem; font-weight: 800; color: var(--text-muted); letter-spacing: 0.12em; margin-bottom: 0.6rem; text-transform: uppercase; }
    .q-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.3; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
    .q-desc { font-size: 1.15rem; color: var(--accent-primary); margin-bottom: 1.5rem; flex: 1; line-height: 1.45; font-weight: 700; }
    .a-content { background: rgba(0, 0, 0, 0.3); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
    
    .big-stat { font-size: 2.2rem; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin-bottom: 0.25rem; }
    .big-stat-sub { font-size: 0.75rem; color: var(--accent-green); font-weight: 600; }

    .mini-table { width: 100%; border-collapse: collapse; }
    .mini-table th { text-align: left; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; padding-bottom: 0.6rem; border-bottom: 1px solid var(--border-color); letter-spacing: 0.05em; }
    .mini-table td { padding: 0.6rem 0; font-size: 0.85rem; border-bottom: 1px dashed var(--border-color); }
    .mini-table tr:last-child td { border-bottom: none; }

    /* Layout & Mask Fix */
    .insights-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); 
        gap: 1.5rem; 
        margin-bottom: 2rem;
    }
    .page-content::before, .page-content::after, 
    .main-content::before, .main-content::after { 
        display: none !important; 
    }
    .page-content { mask-image: none !important; -webkit-mask-image: none !important; }

    /* Card Consistency */
    .insight-card .a-content { 
        background: rgba(17, 18, 26, 0.6) !important;
        backdrop-filter: blur(12px);
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div class="insight-header" style="margin-bottom: 2rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">11 BIG QUESTIONS ANSWERED</p>
        <h1 style="font-size: 2.25rem; font-weight: 800;">Interactive <em style="color: var(--accent-primary); font-style: italic;">Insights</em></h1>
        <p class="mt-4" style="color: var(--text-secondary); font-size: 0.9rem; max-width: 600px;">
          Explore deep, data-driven answers to the industry's most exciting questions, calculated live from the metadata of over two decades of Indian cinema.
        </p>
      </div>

      <div class="insights-grid">
        
        <!-- CRAZY INSIGHT 1 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #1</div>
          <div class="q-title">The Flop Masterpieces</div>
          <div class="q-desc">Which universally acclaimed movies (IMDb ≥ 8.0) completely bombed at the box office?</div>
          <div class="a-content">
            <table class="mini-table">
              <tr><th>Movie</th><th style="text-align:right;">Revenue</th></tr>
               <?php foreach ($flopMasterpieces as $fm): ?>
               <tr>
                 <td style="font-weight: 600;">
                   <a href="movie_details.php?id=<?= $fm['movie_id'] ?>" style="color:inherit; text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($fm['title']) ?></a>
                   <br>
                   <span style="color:var(--text-secondary); font-size: 0.7rem;">
                     Directed by <a href="director_details.php?id=<?= $fm['director_id'] ?>" style="color:inherit; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?= htmlspecialchars($fm['director']) ?></a>
                     • ★ <?= number_format($fm['rating_imdb'], 1) ?>
                   </span>
                 </td>
                 <td style="text-align:right; color: #ef4444;">₹<?= formatRevenue($fm['revenue']) ?></td>
               </tr>
               <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- CRAZY INSIGHT 2 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #2</div>
          <div class="q-title">Commercial Disasters</div>
          <div class="q-desc">Which highly profitable movies were absolutely hated by audiences (IMDb < 5.0)?</div>
          <div class="a-content">
            <table class="mini-table">
              <tr><th>Movie</th><th style="text-align:right;">Revenue</th></tr>
               <?php foreach ($disasters as $cd): ?>
               <tr>
                 <td style="font-weight: 600;">
                   <a href="movie_details.php?id=<?= $cd['movie_id'] ?>" style="color:inherit; text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($cd['title']) ?></a>
                   <br>
                   <span style="color:var(--text-secondary); font-size: 0.7rem;">
                     Directed by <a href="director_details.php?id=<?= $cd['director_id'] ?>" style="color:inherit; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?= htmlspecialchars($cd['director']) ?></a>
                     • ★ <?= number_format($cd['rating_imdb'], 1) ?>
                   </span>
                 </td>
                 <td style="text-align:right; color: var(--accent-green); font-weight: bold;">&#x20B9;<?= formatRevenue($cd['revenue']) ?></td>
               </tr>
               <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- CRAZY INSIGHT 3 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #3</div>
          <div class="q-title">One-Hit Wonders</div>
          <div class="q-desc">Which actors appeared in exactly ONE movie, but it made massive box office revenue?</div>
          <div class="a-content">
             <table class="mini-table">
              <tr><th>Actor</th><th style="text-align:right;">Movie / Revenue</th></tr>
              <?php foreach ($oneHitWonders as $ohw): ?>
              <tr>
                <td style="font-weight: 600; color: var(--accent-primary);"><?= htmlspecialchars($ohw['actor']) ?></td>
                <td style="text-align:right;">
                  <div style="font-size: 0.8rem;"><?= htmlspecialchars($ohw['title']) ?></div>
                  <div style="color: var(--accent-green); font-size: 0.7rem; font-weight: bold;">&#x20B9;<?= formatRevenue($ohw['revenue']) ?></div>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- Q4 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #4</div>
          <div class="q-title">Language Champions</div>
          <div class="q-desc">Which regional language cinema generates the highest average revenue per film?</div>
          <div class="a-content">
             <?php 
               $maxL = max(array_column($langChamps ?: [['avg_revenue' => 1]], 'avg_revenue')); 
               foreach ($langChamps as $idx => $l): 
                 $w = round(($l['avg_revenue'] / $maxL) * 100);
             ?>
             <div style="margin-bottom: 0.5rem;">
               <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:0.2rem;">
                 <span class="font-bold"><?= strtoupper($l['language']) ?></span>
                 <span>&#x20B9;<?= formatRevenue($l['avg_revenue']) ?> / film</span>
               </div>
               <div class="region-bar-track"><div class="region-bar-fill" style="width:<?= max($w, 5) ?>%; background:<?= $barColors[$idx%count($barColors)] ?>;"></div></div>
             </div>
             <?php endforeach; ?>
          </div>
        </div>



        <!-- Q11: Versatile Actors -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #5</div>
          <div class="q-title">Genre Versatility</div>
          <div class="q-desc">Which actors have displayed the most range by working across diverse genres?</div>
          <div class="a-content">
            <table class="mini-table">
              <tr><th>Actor</th><th style="text-align:right;">Genres</th></tr>
               <?php foreach ($versatileActors as $va): ?>
               <tr>
                 <td style="font-weight: 600;">
                   <a href="actor_details.php?id=<?= $va['actor_id'] ?>" style="color:inherit; text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='inherit'"><?= htmlspecialchars($va['actor']) ?></a>
                 </td>
                 <td style="text-align:right;"><span class="sentiment-badge sentiment-high"><?= $va['genres_count'] ?> Genres</span></td>
               </tr>
               <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- Q12: Frequent Collaborators -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #6</div>
          <div class="q-title">Frequent Pairings</div>
          <div class="q-desc">Which actor duos are the most frequent collaborators on screen?</div>
          <div class="a-content">
            <?php foreach ($actorDuos as $ad): ?>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border-color);">
                <div>
                  <div style="font-weight:700; font-size:0.9rem;">
                    <a href="actor_details.php?id=<?= $ad['actor1_id'] ?>" style="color:var(--text-primary); text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-primary)'"><?= htmlspecialchars($ad['actor1']) ?></a>
                    <span style="color:var(--text-muted); margin: 0 4px;">&</span>
                    <a href="actor_details.php?id=<?= $ad['actor2_id'] ?>" style="color:var(--text-primary); text-decoration:none;" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='var(--text-primary)'"><?= htmlspecialchars($ad['actor2']) ?></a>
                  </div>
                </div>
                <div style="text-align:right;">
                  <div class="text-green font-bold" style="font-size: 0.8rem;"><?= $ad['films_together'] ?> Films</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Q2 -->
        <div class="card insight-card" style="grid-column: span 2;">
          <div class="q-number">INSIGHT #7</div>
          <div class="q-title">Genre Trends Over Time</div>
          <div class="q-desc">What is the production volume trend of Action versus Romance movies over the decades?</div>
          
          <?php
            $trendYears = [];
            $actionCounts = [];
            $romanceCounts = [];
            foreach ($genreTrend as $row) { 
                $trendYears[] = (int)$row['yr'];
                $actionCounts[] = (int)$row['action_count'];
                $romanceCounts[] = (int)$row['romance_count'];
            }
          ?>
          <div style="height: 300px; margin-top: 1rem;">
             <canvas id="genreTrendChart"></canvas>
          </div>
        </div>

        <!-- INSIGHT #8: Language Rating Showdown -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #8</div>
          <div class="q-title">Language Rating Showdown</div>
          <div class="q-desc">Which film industry produces the highest average IMDb score? Ranked by critical acclaim.</div>
          <div class="a-content">
            <table class="mini-table">
              <tr><th>Industry</th><th style="text-align:right;">Avg Rating</th><th style="text-align:right;">Peak</th></tr>
              <?php
                $lmap = ['hi'=>'Bollywood','ta'=>'Kollywood','te'=>'Tollywood','ml'=>'Mollywood','kn'=>'Sandalwood','en'=>'Hollywood'];
                foreach (array_slice($langRatingComp, 0, 5) as $lrc):
                  $lname = $lmap[strtolower($lrc['language'])] ?? strtoupper($lrc['language']);
              ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($lname) ?><br><span style="color:var(--text-secondary);font-size:0.7rem;"><?= $lrc['movie_count'] ?> films</span></td>
                <td style="text-align:right; color:var(--accent-primary); font-weight:700;">&#x2605; <?= number_format($lrc['avg_rating'],2) ?></td>
                <td style="text-align:right; color:var(--accent-green);">&#x2605; <?= number_format($lrc['max_rating'],1) ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- INSIGHT #9: Decade Breakdown -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #9</div>
          <div class="q-title">Decade Report Card</div>
          <div class="q-desc">How does each decade compare in average quality? Which era produced the most films?</div>
          <div class="a-content">
            <table class="mini-table">
              <tr><th>Decade</th><th style="text-align:right;">Avg Rating</th><th style="text-align:right;">Films</th></tr>
              <?php foreach ($decadeRatings as $dr): ?>
              <tr>
                <td style="font-weight:700; color:var(--accent-primary);"><?= $dr['decade'] ?>s</td>
                <td style="text-align:right;">&#x2605; <?= number_format($dr['avg_rating'],2) ?></td>
                <td style="text-align:right; color:var(--text-muted);"><?= $dr['movie_count'] ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- INSIGHT #10: Workhorse Directors -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #10</div>
          <div class="q-title">The Workhorses</div>
          <div class="q-desc">Directors who have the highest sheer volume of films — quantity vs. quality breakdown.</div>
          <div class="a-content">
            <table class="mini-table">
              <tr><th>Director</th><th style="text-align:right;">Films</th><th style="text-align:right;">Avg Rating</th></tr>
              <?php foreach ($topDirsByCount as $td): ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($td['director']) ?></td>
                <td style="text-align:right; color:var(--accent-primary); font-weight:700;"><?= $td['movie_count'] ?></td>
                <td style="text-align:right;">&#x2605; <?= number_format($td['avg_rating'],1) ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

      </div>

    </div>
  </main>

  <script>
    window.addEventListener('load', function() {
        const ctx = document.getElementById('genreTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($trendYears) ?>,
                datasets: [
                    {
                        label: 'Action',
                        data: <?= json_encode($actionCounts) ?>,
                        backgroundColor: 'rgba(245, 197, 24, 0.85)', // IMDb Yellow/Orange
                        borderColor: '#f5c518',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Romance',
                        data: <?= json_encode($romanceCounts) ?>,
                        backgroundColor: 'rgba(92, 214, 182, 0.85)', // accent-green
                        borderColor: '#5cd6b6',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#8b8d9e',
                            font: { size: 11, weight: 'bold' },
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e1f2a',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        titleColor: '#f0f0f5',
                        bodyColor: '#8b8d9e',
                        padding: 10
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: '#8b8d9e', 
                            font: { size: 10 },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#8b8d9e', font: { size: 10 } },
                        beginAtZero: true
                    }
                }
            }
        });
    });
  </script>
</body>
</html>
