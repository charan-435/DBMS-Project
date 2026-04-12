<?php
require_once __DIR__ . '/../../backend/DataService.php';

$service = new DataService();
$top10Directors = $service->getTopDirectors(10);
$auteurs = array_slice($top10Directors, 0, 4);

// Fallback
if (empty($top10Directors)) {
    $top10Directors = [
        ['director' => 'Christopher Nolan', 'avg_rating' => 8.7, 'movie_count' => 12, 'total_revenue' => 5200000000],
        ['director' => 'Denis Villeneuve', 'avg_rating' => 8.3, 'movie_count' => 10, 'total_revenue' => 2000000000],
        ['director' => 'Martin Scorsese', 'avg_rating' => 8.2, 'movie_count' => 25, 'total_revenue' => 1500000000],
        ['director' => 'Greta Gerwig', 'avg_rating' => 8.1, 'movie_count' => 4, 'total_revenue' => 1600000000],
    ];
    $auteurs = $top10Directors;
}

$topDirector = $auteurs[0];

// Get top films and collaborators for the top director
$topFilms = $service->getDirectorFilms($topDirector['director'], 3);
$collaborators = $service->getDirectorCollaborators($topDirector['director'], 2);

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
  <title>The Cinematic Lens - Directors</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div class="directors-grid">
        <!-- Left Column -->
        <div class="left-col">
          <div class="insight-header">
            <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">EDITORIAL INSIGHTS</p>
            <h1>Director<br>Analysis</h1>
            <p class="mt-4">Synthesizing cinematic impact through weighted ratings, collaboration depth, and genre versatility.</p>
          </div>

          <div class="card auteurs-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
              <h2 style="font-size: 1.1rem; font-weight: 700;">Auteurs</h2>
              <span style="color: var(--text-muted); cursor: pointer;">&#x2261;</span>
            </div>
            
            <?php foreach ($auteurs as $index => $dir): 
                $isTop = $index === 0;
                $c = $avatarColors[$index % count($avatarColors)];
            ?>
            <div class="auteur-item" <?= $isTop ? 'style="background-color: var(--bg-highlight); padding: 0.85rem; border-radius: ' . 'var(--radius-sm)' . '; margin: 0 -0.5rem; position: relative; border-left: 3px solid var(--accent-primary);"' : '' ?>>
              <div class="auteur-info">
                <div class="auteur-avatar" style="background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
                <div>
                  <div class="font-semibold" style="font-size: 0.9rem;"><?= htmlspecialchars($dir['director']) ?></div>
                  <div class="text-xxs text-muted mt-1"><?= $dir['movie_count'] ?> FILMS</div>
                </div>
              </div>
              <div class="auteur-rating <?= $isTop ? 'text-accent' : '' ?>" style="<?= $isTop ? 'font-size: 1.2rem;' : '' ?>">
                <?= number_format($dir['avg_rating'], 1) ?> <span>AVG</span>
              </div>
            </div>
            <?php endforeach; ?>

            <div style="margin-top: auto; padding-top: 1rem;">
              <button class="btn-primary">View Full Index</button>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-col">
          <!-- Top Chart Card -->
          <div class="card chart-card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <h2 style="font-size: 1.1rem; font-weight: 700;">Top 10 Directors by Rating</h2>
              <div style="background-color: var(--accent-glow); padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.65rem; color: var(--accent-primary); font-weight: 600;">IMDB WEIGHTED AVG</div>
            </div>
            <div style="flex: 1;"></div>
            <div style="display: flex; justify-content: space-between; font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
              <?php foreach ($top10Directors as $dir):
                  $nameParts = explode(' ', trim($dir['director']));
                  $lastName = end($nameParts);
              ?>
                  <span><?= htmlspecialchars(substr($lastName, 0, 10)) ?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Profile Card -->
          <div class="card profile-card">
            <div class="profile-image" style="background: linear-gradient(135deg, #2a1a0e, #1a1020);">
              <div class="profile-score" style="right: 1rem; bottom: 1rem; transform: none;">
                <?= number_format($topDirector['avg_rating'], 1) ?> <span>SCORE</span>
              </div>
            </div>
            <div class="profile-details">
              <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.25rem;"><?= htmlspecialchars($topDirector['director']) ?></h1>
              <p class="text-accent uppercase tracking-wider text-xxs font-bold">TOP RATED AUTEUR</p>
              <div class="profile-stats">
                <div class="stat-item">
                  <h4>Total Films</h4>
                  <p><?= $topDirector['movie_count'] ?></p>
                </div>
                <div class="stat-item">
                  <h4>Total Revenue</h4>
                  <p>$<?= number_format($topDirector['total_revenue'] / 1000000, 1) ?>M</p>
                </div>
                <div class="stat-item">
                  <h4>Avg Rating</h4>
                  <p><?= number_format($topDirector['avg_rating'], 1) ?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Row -->
          <div class="bottom-grid">
            <!-- Key Filmography -->
            <div class="card">
              <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 700;">Key Filmography</h3>
                <span style="color: var(--accent-green);">&#x1F3AC;</span>
              </div>
              <?php if (!empty($topFilms)): ?>
                <?php foreach ($topFilms as $film): ?>
                <div class="film-item">
                  <div class="film-header">
                    <div>
                      <div class="font-semibold text-sm"><?= htmlspecialchars($film['title']) ?></div>
                      <div class="text-xxs text-muted"><?= $film['yr'] ?? '' ?> &bull; <?= strtoupper($film['genres'] ?? '') ?></div>
                    </div>
                    <div style="color: var(--accent-green);" class="font-bold"><?= number_format($film['rating_imdb'], 1) ?></div>
                  </div>
                  <div class="progress-track mt-2"><div class="progress-fill" style="width: <?= ($film['rating_imdb'] * 10) ?>%;"></div></div>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-muted text-sm">No films found in database.</p>
              <?php endif; ?>
            </div>

            <!-- Frequent Collaborators -->
            <div class="card">
              <div style="display: flex; justify-content: space-between; margin-bottom: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 700;">Frequent<br>Collaborators</h3>
                <span style="color: var(--accent-primary);">&#x1F465;</span>
              </div>
              <div style="display: flex; gap: 0.75rem;">
                <?php if (!empty($collaborators)): ?>
                  <?php foreach ($collaborators as $i => $collab): 
                    $cc = $avatarColors[$i % count($avatarColors)];
                  ?>
                  <div style="flex: 1; text-align: center; background-color: var(--bg-dark); padding: 1rem 0.5rem; border-radius: var(--radius-md);">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, <?= $cc[0] ?>, <?= $cc[1] ?>); margin: 0 auto 0.5rem auto;"></div>
                    <div class="font-semibold text-xs"><?= htmlspecialchars($collab['name']) ?></div>
                    <div class="text-xxs text-accent mt-1"><?= $collab['films'] ?> FILMS</div>
                  </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="text-muted text-sm">Connect database to see collaborators.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>
    </div>
  </main>
</body>
</html>
