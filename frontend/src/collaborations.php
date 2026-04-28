<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// Top actor-director duos
$duos = $service->getActorDirectorCollaborations(8);

// Actors spanning multiple genres
$versatile = $service->getActorGenreVersatility(6);

// Actors who frequently appear together
$repeatActors = $service->getRepeatCollaborators(2, 8);

// Top directors for the network
$topDirs = $service->getTopDirectorsByCount(5);

// New: Top actors by revenue + actors who work with most directors
$actorsByRevenue = $service->getTopActorsByRevenue(6);
$actorsDirDiversity = $service->getActorCollaborationCount(6);

$barColors = ['var(--accent-primary)', '#5cd6b6', '#6ea8fe', '#a68dff', '#ff8296', '#fbbf24', '#22d3ee', '#f97316'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Collaborations — The Cinematic Lens</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .collab-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .collab-grid-equal { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }

    .duo-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.85rem 0; border-bottom: 1px solid var(--border-color);
    }
    .duo-item:last-child { border-bottom: none; }
    .duo-left { display: flex; align-items: center; gap: 0.75rem; }
    .duo-avatar {
      width: 40px; height: 40px; border-radius: 50%; display: flex;
      align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
    }
    .duo-director { font-weight: 600; font-size: 0.88rem; }
    .duo-actor { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.1rem; }
    .duo-stats { text-align: right; }
    .duo-films { font-weight: 700; font-size: 1rem; }
    .duo-revenue { font-size: 0.7rem; color: var(--accent-green); }

    .versatile-item {
      padding: 0.85rem 0; border-bottom: 1px solid var(--border-color);
    }
    .versatile-item:last-child { border-bottom: none; }
    .versatile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; }
    .versatile-name { font-weight: 600; font-size: 0.88rem; }
    .versatile-count { font-weight: 700; color: var(--accent-primary); font-size: 0.85rem; }
    .versatile-genres { display: flex; flex-wrap: wrap; gap: 0.3rem; }
    .genre-chip {
      background: rgba(255,255,255,0.06); padding: 0.15rem 0.45rem;
      border-radius: 3px; font-size: 0.65rem; color: var(--text-secondary); font-weight: 500;
    }

    .pair-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);
    }
    .pair-item:last-child { border-bottom: none; }
    .pair-names { display: flex; align-items: center; gap: 0.5rem; }
    .pair-connector { color: var(--accent-primary); font-weight: 700; font-size: 0.75rem; }
    .pair-count {
      background: var(--accent-glow); color: var(--accent-primary); font-weight: 700;
      padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem;
    }

    .network-card { position: relative; min-height: 320px; }
    .network-node {
      position: absolute; text-align: center; transition: transform 0.3s;
    }
    .network-node:hover { transform: scale(1.1); }
    .network-circle {
      width: 50px; height: 50px; border-radius: 50%; margin: 0 auto 0.4rem;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.7rem; font-weight: 700; color: #fff; border: 2px solid rgba(255,255,255,0.1);
    }
    .network-label { font-size: 0.65rem; color: var(--text-secondary); max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .network-sublabel { font-size: 0.55rem; color: var(--text-muted); }

    .section-label {
      font-size: 0.65rem; font-weight: 700; letter-spacing: 0.15em;
      text-transform: uppercase; color: var(--accent-primary); margin-bottom: 0.3rem;
    }
    .card-title-collab { font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">

      <!-- Header -->
      <div style="margin-bottom: 1.5rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">RELATIONSHIP INTELLIGENCE</p>
        <h1 style="font-size: 2.25rem; font-weight: 800;">Collaboration <em style="color: var(--accent-primary); font-style: italic;">Networks</em></h1>
        <p class="mt-2" style="color: var(--text-secondary); font-size: 0.88rem; max-width: 600px;">
          Mapping the creative bonds that drive box office success — director-actor duos, actor pairs, and genre versatility across Indian cinema.
        </p>
      </div>

      <!-- Row 1: Director-Actor Duos + Network Visualization -->
      <div class="collab-grid">
        <!-- Dynamic Duos -->
        <div class="card">
          <div class="section-label">POWER PARTNERSHIPS</div>
          <div class="card-title-collab">Top Director-Actor Duos</div>

          <?php if (!empty($duos)): ?>
            <?php foreach ($duos as $i => $duo): ?>
            <div class="duo-item">
              <div class="duo-left">
                <div style="display:flex; flex-direction:column; justify-content:center; padding-left: 0.5rem; border-left: 3px solid <?= $barColors[$i % count($barColors)] ?>;">
                  <div class="duo-director" style="font-size: 1rem;"><?= htmlspecialchars($duo['director']) ?></div>
                  <div class="duo-actor">&amp; <?= htmlspecialchars($duo['actor']) ?></div>
                </div>
              </div>
              <div class="duo-stats">
                <div class="duo-films"><?= $duo['count'] ?> films</div>
                <?php if (isset($duo['avg_revenue'])): ?>
                  <div class="duo-revenue">&#x20B9;<?= formatRevenue($duo['avg_revenue']) ?> avg</div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No collaboration data available. Populate the database first.</p>
          <?php endif; ?>
        </div>

        <!-- Network Visualization -->
        <div class="card network-card">
          <div class="section-label">COLLABORATION MAP</div>
          <div class="card-title-collab">Director Network</div>

          <?php if (!empty($topDirs)):
            $positions = [
              ['left' => '40%', 'top' => '40%'],  // Center
              ['left' => '10%', 'top' => '15%'],
              ['left' => '70%', 'top' => '10%'],
              ['left' => '65%', 'top' => '65%'],
              ['left' => '8%',  'top' => '68%'],
            ];
            foreach ($topDirs as $di => $dir):
              $pos = $positions[$di % count($positions)];
              $color = $barColors[$di % count($barColors)];
              $nameParts = explode(' ', trim($dir['director']));
              $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
          ?>
          <div class="network-node" style="left: <?= $pos['left'] ?>; top: <?= $pos['top'] ?>;">
            <div class="network-label" style="font-size: 0.95rem; font-weight: 700; color: <?= $color ?>; max-width: none;"><?= htmlspecialchars($dir['director']) ?></div>
            <div class="network-sublabel"><?= $dir['movie_count'] ?> films</div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Row 2: Genre Versatility + Actor Pairs -->
      <div class="collab-grid-equal">
        <!-- Genre Versatility -->
        <div class="card">
          <div class="section-label">GENRE RANGE</div>
          <div class="card-title-collab">Most Versatile Actors</div>

          <?php if (!empty($versatile)): ?>
            <?php foreach ($versatile as $v): ?>
            <div class="versatile-item">
              <div class="versatile-header">
                <span class="versatile-name"><?= htmlspecialchars($v['actor']) ?></span>
                <span class="versatile-count"><?= $v['genres_count'] ?> genres · <?= $v['total_films'] ?> films</span>
              </div>
              <div class="versatile-genres">
                <?php foreach (explode(', ', $v['genres']) as $g): ?>
                  <span class="genre-chip"><?= htmlspecialchars($g) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No versatility data available.</p>
          <?php endif; ?>
        </div>

        <!-- Repeat Actor Pairs -->
        <div class="card">
          <div class="section-label">FREQUENT CO-STARS</div>
          <div class="card-title-collab">Actors Who Work Together</div>

          <?php if (!empty($repeatActors)): ?>
            <?php foreach ($repeatActors as $pair): ?>
            <div class="pair-item">
              <div class="pair-names">
                <span class="font-semibold" style="font-size:0.85rem;"><?= htmlspecialchars($pair['actor1']) ?></span>
                <span class="pair-connector">×</span>
                <span class="font-semibold" style="font-size:0.85rem;"><?= htmlspecialchars($pair['actor2']) ?></span>
              </div>
              <span class="pair-count"><?= $pair['films_together'] ?> films</span>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No repeat collaborators found (need actors appearing in 2+ films together).</p>
          <?php endif; ?>
        </div>
      </div>
      <!-- New Row: Actor Revenue Rankings + Director Diversity -->
      <div class="collab-grid-equal" style="margin-top: 0;">

        <!-- Top Actors by Total Box Office -->
        <div class="card">
          <div class="section-label">BOX OFFICE KINGS</div>
          <div class="card-title-collab">Actors by Total Revenue</div>
          <?php if (!empty($actorsByRevenue)): ?>
            <?php
              $maxActRev = max(array_column($actorsByRevenue, 'total_revenue'));
              if ($maxActRev == 0) $maxActRev = 1;
            ?>
            <?php foreach ($actorsByRevenue as $i => $ar): ?>
            <div style="padding: 0.7rem 0; border-bottom: 1px solid var(--border-color);">
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                <span style="font-weight: 600; font-size: 0.88rem;"><?= htmlspecialchars($ar['actor']) ?></span>
                <span style="font-weight: 700; color: var(--accent-green); font-size: 0.85rem;">&#x20B9;<?= formatRevenue($ar['total_revenue']) ?></span>
              </div>
              <div style="height: 3px; background: var(--border-color); border-radius: 2px;">
                <div style="height: 3px; width: <?= round(($ar['total_revenue'] / $maxActRev) * 100) ?>%; background: <?= $barColors[$i % count($barColors)] ?>; border-radius: 2px;"></div>
              </div>
              <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;"><?= $ar['movie_count'] ?> films &bull; &#x2605; <?= number_format($ar['avg_rating'], 1) ?> avg</div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No actor revenue data available.</p>
          <?php endif; ?>
        </div>

        <!-- Actors with Most Unique Directors -->
        <div class="card">
          <div class="section-label">CREATIVE RANGE</div>
          <div class="card-title-collab">Actors Across Most Directors</div>
          <?php if (!empty($actorsDirDiversity)): ?>
            <?php foreach ($actorsDirDiversity as $i => $ad): ?>
            <div class="duo-item">
              <div class="duo-left">
                <div style="display:flex; flex-direction:column; justify-content:center; padding-left: 0.5rem; border-left: 3px solid <?= $barColors[$i % count($barColors)] ?>;">
                  <div class="duo-director" style="font-size: 1rem;"><?= htmlspecialchars($ad['actor']) ?></div>
                  <div class="duo-actor"><?= $ad['total_films'] ?> films total</div>
                </div>
              </div>
              <div class="duo-stats">
                <div class="duo-films"><?= $ad['unique_directors'] ?></div>
                <div class="duo-revenue">unique directors</div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="text-align:center; padding:2rem;">No diversity data available.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2026. COLLABORATION INTELLIGENCE ENGINE.</div>
    </div>
  </main>
</body>
</html>
