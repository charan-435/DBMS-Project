<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$topActors = $service->getTopActorsDetailed(12);
$mostVersatile = $service->getActorGenreVersatility(4);
$revenueKings = $service->getTopActorsByRevenue(4);

$avatarColors = [
    ['#e8a57e', '#d4845a'], ['#5cd6b6', '#3bb89a'],
    ['#6ea8fe', '#4a8ae0'], ['#a68dff', '#8565e0']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Cinematic Lens - Actors Leaderboard</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .actors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }
    .actor-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .actor-card:hover {
      transform: translateY(-5px);
      border-color: var(--accent-primary);
      box-shadow: 0 10px 30px -10px rgba(129, 140, 248, 0.2);
    }
    .actor-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      position: relative;
      z-index: 2;
    }
    .actor-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .actor-name {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text-primary);
    }
    .actor-rank {
      position: absolute;
      top: -0.5rem;
      right: 0.5rem;
      font-size: 4rem;
      font-weight: 900;
      color: var(--accent-primary);
      opacity: 0.08;
      pointer-events: none;
      z-index: 1;
    }
    .actor-stat-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.75rem;
      font-size: 0.85rem;
    }
    .actor-stat-label {
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-size: 0.7rem;
      font-weight: 700;
    }
    .actor-stat-value {
      color: var(--text-secondary);
      font-weight: 700;
    }
    .highlight-section {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 3rem;
    }
    @media (max-width: 768px) {
      .highlight-section { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div class="insight-header">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">PERFORMANCE ANALYTICS</p>
        <h1>Actor<br>Leaderboard</h1>
        <p class="mt-4">From box office titans to critical darlings—exploring the actors who define Indian cinema.</p>
      </div>

      <!-- Highlights -->
      <div class="highlight-section">
        <!-- Box Office Kings -->
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.1rem; font-weight: 700;">Box Office Kings</h2>
            <span style="color: var(--accent-green);">&#x1F4B0;</span>
          </div>
          <?php foreach ($revenueKings as $index => $actor): 
            $c = $avatarColors[$index % count($avatarColors)];
          ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
               <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
               <div>
                 <div class="font-semibold text-sm">
                   <?php if (isset($actor['actor_id'])): ?>
                     <a href="actor_details.php?id=<?= $actor['actor_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($actor['actor']) ?></a>
                   <?php else: ?>
                     <?= htmlspecialchars($actor['actor']) ?>
                   <?php endif; ?>
                 </div>
                 <div class="text-xxs text-muted"><?= $actor['movie_count'] ?> BLOCKED BUSTERS</div>
               </div>
            </div>
            <div style="text-align: right;">
              <div class="text-sm font-bold text-accent">&#x20B9;<?= formatRevenue($actor['total_revenue']) ?></div>
              <div class="text-xxs text-muted">TOTAL REVENUE</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Most Versatile -->
        <div class="card">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.1rem; font-weight: 700;">Genre Versatility</h2>
            <span style="color: var(--accent-primary);">&#x1F3AD;</span>
          </div>
          <?php foreach ($mostVersatile as $index => $actor): 
            $c = $avatarColors[($index + 2) % count($avatarColors)];
          ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
               <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
               <div>
                 <div class="font-semibold text-sm">
                   <a href="actor_details.php?id=<?= $actor['actor_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($actor['actor']) ?></a>
                 </div>
                 <div class="text-xxs text-muted"><?= $actor['genre_count'] ?> UNIQUE GENRES</div>
               </div>
            </div>
            <div style="text-align: right;">
              <div class="text-sm font-bold" style="color: var(--accent-green);"><?= isset($actor['avg_rating']) ? number_format($actor['avg_rating'], 1) : '—' ?></div>
              <div class="text-xxs text-muted">AVG RATING</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem;">Top Prolific <em style="color: var(--accent-primary); font-style: italic;">Actors</em></h2>
      
      <div class="actors-grid">
        <?php foreach ($topActors as $i => $actor): 
          $c = $avatarColors[$i % count($avatarColors)];
        ?>
        <div class="actor-card">
          <div class="actor-rank">#<?= $i + 1 ?></div>
          <div class="actor-header">
            <div class="actor-avatar" style="background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
            <div>
              <div class="actor-name"><?= htmlspecialchars($actor['name']) ?></div>
              <div class="text-xxs text-accent font-bold mt-1">ESTABLISHED STAR</div>
            </div>
          </div>
          
          <div class="actor-stat-row">
            <span class="actor-stat-label">Total Films</span>
            <span class="actor-stat-value"><?= $actor['movie_count'] ?></span>
          </div>
          <div class="actor-stat-row">
            <span class="actor-stat-label">Avg Rating</span>
            <span class="actor-stat-value"><?= number_format($actor['avg_rating'], 1) ?></span>
          </div>
          <div class="actor-stat-row">
            <span class="actor-stat-label">Total Revenue</span>
            <span class="actor-stat-value">&#x20B9;<?= formatRevenue($actor['total_revenue']) ?></span>
          </div>
          
          <div style="margin-top: 1.5rem; position: relative; z-index: 2;">
            <a href="actor_details.php?id=<?= $actor['actor_id'] ?>" class="btn-outline" style="width: 100%; text-align: center; display: block; font-size: 0.75rem;">View Career Profile</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2026. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>
    </div>
  </main>
</body>
</html>
