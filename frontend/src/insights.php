<?php
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// Q1: Director Excellence
$topDirectorList = $service->getTopDirectors(1);
$topDirector = $topDirectorList[0] ?? ['director' => 'Unknown', 'avg_rating' => 0];

// Q2: Genre Trends
$genreTrend = $service->getGenreTrend();

// Q3: Dynamic Duos
$duos = $service->getActorDirectorCollaborations(3);

// Q4: Language Champions
$langChamps = $service->getLanguageRevenueAverages(3);

// Q5: Runtime — removed (column does not exist in schema)
$runtimeStats = [];

// Q6: 100 Crore Club
$highGrossing = $service->getHighGrossingGenres(3);

// Q7: Prolific Performers
$topActors = $service->getTopActors(5);

// Q8: Decade of Masterpieces
$decades = $service->getDecadeRatings();
$topDecade = $decades[0] ?? ['decade' => 0, 'avg_rating' => 0];

// Q9: Quality vs Commercial
$qualVCom = $service->getRatingRevenueCorrelation();

// Q10: Golden Year
$goldenYear = $service->getGoldenYear();
if (empty($goldenYear)) $goldenYear = ['yr' => '—', 'total_revenue' => 0, 'movie_count' => 0];

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
    .q-number { font-size: 0.8rem; font-weight: 800; color: var(--accent-primary); letter-spacing: 0.1em; margin-bottom: 0.5rem; }
    .q-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.3; }
    .q-desc { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem; flex: 1; }
    .a-content { background: var(--bg-dark); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
    
    .big-stat { font-size: 2rem; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin-bottom: 0.25rem; }
    .big-stat-sub { font-size: 0.75rem; color: var(--accent-green); }

    .mini-table { width: 100%; border-collapse: collapse; }
    .mini-table th { text-align: left; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); }
    .mini-table td { padding: 0.5rem 0; font-size: 0.85rem; border-bottom: 1px dashed var(--border-color); }
    .mini-table tr:last-child td { border-bottom: none; }

    /* Layout & Mask Fix */
    .insights-grid { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
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
        background: rgba(15, 17, 23, 0.6) !important;
        backdrop-filter: blur(8px);
    }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div class="insight-header" style="margin-bottom: 2rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">9 BIG QUESTIONS ANSWERED</p>
        <h1 style="font-size: 2.25rem; font-weight: 800;">Interactive <em style="color: var(--accent-primary); font-style: italic;">Insights</em></h1>
        <p class="mt-4" style="color: var(--text-secondary); font-size: 0.9rem; max-width: 600px;">
          Explore deep, data-driven answers to the industry's most exciting questions, calculated live from the metadata of over two decades of Indian cinema.
        </p>
      </div>

      <div class="insights-grid">
        
        <!-- Q1 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #1</div>
          <div class="q-title">Director Excellence</div>
          <div class="q-desc">Which director has the highest average IMDb rating across their entire filmography?</div>
          <div class="a-content">
            <div class="big-stat"><?= htmlspecialchars($topDirector['director']) ?></div>
            <div class="big-stat-sub">&#x2605; <?= number_format($topDirector['avg_rating'], 1) ?> Average Rating</div>
          </div>
        </div>

        <!-- Q3 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #2</div>
          <div class="q-title">Dynamic Duos</div>
          <div class="q-desc">Which actor-director duos frequently collaborate, and what is their box office success?</div>
          <div class="a-content">
            <table class="mini-table">
              <tr><th>Duo</th><th>Films</th><th>Avg Revenue</th></tr>
              <?php foreach ($duos as $duo): ?>
              <tr>
                <td style="font-weight: 600;"><?= htmlspecialchars($duo['director']) ?><br><span style="color:var(--text-secondary); font-size: 0.75rem;">& <?= htmlspecialchars($duo['actor']) ?></span></td>
                <td><?= $duo['count'] ?></td>
                <td>&#x20B9;<?= number_format($duo['avg_revenue'] / 100000, 1) ?>Cr</td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>

        <!-- Q10 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #3</div>
          <div class="q-title">The Golden Year</div>
          <div class="q-desc">Which specific year saw the highest total combined box office revenue in cinema history?</div>
          <div class="a-content">
            <div class="big-stat"><?= $goldenYear['yr'] ?></div>
            <div class="big-stat-sub">&#x20B9;<?= number_format($goldenYear['total_revenue'] / 100000, 0) ?> Cr Total Across <?= $goldenYear['movie_count'] ?> Films</div>
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
                 <span>&#x20B9;<?= number_format($l['avg_revenue'] / 10000000, 1) ?>Cr / film</span>
               </div>
               <div class="region-bar-track"><div class="region-bar-fill" style="width:<?= max($w, 5) ?>%; background:<?= $barColors[$idx%count($barColors)] ?>;"></div></div>
             </div>
             <?php endforeach; ?>
          </div>
        </div>

        <!-- Q9 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #5</div>
          <div class="q-title">Quality vs. Commercials</div>
          <div class="q-desc">How does average box office compare between flop rated movies and masterpieces?</div>
          <div class="a-content">
            <?php foreach ($qualVCom as $q): ?>
              <div style="display:flex; justify-content:space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                <span style="font-size: 0.8rem; font-weight: 600;"><?= $q['rating_category'] ?></span>
                <span class="text-accent font-bold" style="font-size: 0.85rem;">&#x20B9;<?= number_format($q['avg_revenue']/10000000, 1) ?>Cr</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Q8 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #6</div>
          <div class="q-title">Decades of Masterpieces</div>
          <div class="q-desc">Which decade actually produced the consistently highest average-rated films?</div>
          <div class="a-content" style="background: linear-gradient(135deg, rgba(92,214,182,0.1), transparent);">
             <div class="big-stat" style="color: var(--accent-green);"><?= $topDecade['decade'] ?>s</div>
             <div class="text-muted" style="font-size: 0.8rem;">&#x2605; <?= number_format($topDecade['avg_rating'], 2) ?> Average Score</div>
          </div>
        </div>

        <!-- Q7 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #7</div>
          <div class="q-title">Prolific Performers</div>
          <div class="q-desc">Which actors have appeared in the most films across the entire dataset?</div>
          <div class="a-content">
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
              <?php foreach ($topActors as $act): ?>
                <span style="background: var(--bg-highlight); padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                   <?= htmlspecialchars($act['name']) ?> <span style="color: var(--text-muted); font-size: 0.65rem;">(<?= $act['count'] ?>)</span>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>


        <!-- Q6 -->
        <div class="card insight-card">
          <div class="q-number">INSIGHT #8</div>
          <div class="q-title">The 100-Crore Club</div>
          <div class="q-desc">Which genres are statistically most likely to produce movies grossing > ₹100 Cr?</div>
          <div class="a-content">
            <?php foreach ($highGrossing as $hg): ?>
              <div style="display:flex; justify-content:space-between; padding: 0.35rem 0; font-size: 0.85rem;">
                <span class="font-bold"><?= htmlspecialchars($hg['primary_genre']) ?></span>
                <span class="text-accent"><?= $hg['club_count'] ?> Blockbusters</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Q2 -->
        <div class="card insight-card" style="grid-column: span 2;">
          <div class="q-number">INSIGHT #9</div>
          <div class="q-title">Genre Trends Over Time</div>
          <div class="q-desc">What is the production volume trend of Action versus Romance movies over the decades?</div>
          
          <?php
            $chartData = [];
            foreach ($genreTrend as $row) { $chartData[(int)$row['yr']] = $row; }
            $maxAction = max(array_column($genreTrend ?: [['action_count' => 1]], 'action_count'));
            $maxRomance = max(array_column($genreTrend ?: [['romance_count' => 1]], 'romance_count'));
            $maxVal = max($maxAction, $maxRomance, 1);
          ?>
          <div style="height: 150px; display: flex; align-items: flex-end; gap: 4px; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-top: 1rem;">
            <?php foreach ($chartData as $yr => $data): 
              $ah = max(round(($data['action_count'] / $maxVal) * 100), 1);
              $rh = max(round(($data['romance_count'] / $maxVal) * 100), 1);
            ?>
              <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end; gap:2px; height:100%; position:relative;" title="<?= $yr ?>">
                 <div style="display:flex; gap:2px; align-items:flex-end; height:100%;">
                    <div style="flex:1; background: var(--accent-primary); height: <?= $ah ?>%; border-radius: 2px 2px 0 0;"></div>
                    <div style="flex:1; background: var(--accent-green); height: <?= $rh ?>%; border-radius: 2px 2px 0 0;"></div>
                 </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:0.65rem; color:var(--text-muted); margin-top:0.5rem; text-transform:uppercase;">
            <span>EACH BAR PAIR REPRESENTS ONE YEAR (ACTION = ORANGE, ROMANCE = GREEN)</span>
          </div>
        </div>

      </div>

      
  </main>
</body>
</html>
