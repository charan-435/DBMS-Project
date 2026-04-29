<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$userId = $_SESSION['user_id'];

$watchlist = $service->getWatchlist($userId);
$userComments = $service->getUserComments($userId);

function fmtRev($n)
{
    if ($n >= 1e9) return '₹' . number_format($n / 1e9, 1) . 'B';
    if ($n >= 1e7) return '₹' . number_format($n / 1e7, 1) . 'Cr';
    return '₹' . number_format($n / 1e5, 1) . 'L';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My List — The Cinematic Lens</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .mylist-grid { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; }
    @media(max-width: 1000px) { .mylist-grid { grid-template-columns: 1fr; } }
    
    .watchlist-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
    
    .movie-mini-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        transition: transform 0.2s, border-color 0.2s;
    }
    .movie-mini-card:hover { transform: translateY(-3px); border-color: var(--accent-primary); }
    
    .comment-item {
        background: rgba(255,255,255,0.02);
        border-left: 3px solid var(--accent-primary);
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 0 8px 8px 0;
    }
  </style>
</head>
<body>
  <?php include 'components/sidebar.php'; ?>
  <main class="main-content">
    <?php include 'components/topbar.php'; ?>
    <div class="page-content">
      <div class="hero-label">Personal Portfolio</div>
      <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 2rem;">My <span style="color: var(--accent-primary);">List</span></h1>

      <div class="mylist-grid">
        <!-- WATCHLIST -->
        <div>
          <div class="chart-label" style="margin-bottom: 1rem;">WATCHLIST (<?= count($watchlist) ?>)</div>
          <?php if (empty($watchlist)): ?>
            <div class="card" style="text-align: center; padding: 4rem 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;">🎬</div>
                <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Your watchlist is empty</h2>
                <p class="text-muted">Explore movies and add them to your list to keep track of what to watch next.</p>
                <a href="movies.php" class="btn-accent" style="display: inline-block; margin-top: 1.5rem; text-decoration: none;">Browse Movies</a>
            </div>
          <?php else: ?>
            <div class="watchlist-container">
                <?php foreach ($watchlist as $m): ?>
                    <div class="movie-mini-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <span class="genre-badge genre-default" style="font-size: 0.6rem;"><?= htmlspecialchars($m['genre_name']) ?></span>
                            <span style="color: #f5c518; font-weight: 700; font-size: 0.8rem;">★ <?= number_format($m['rating_imdb'], 1) ?></span>
                        </div>
                        <h3 style="font-size: 1rem; font-weight: 700; margin: 0.25rem 0;"><a href="movie_details.php?id=<?= $m['movie_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($m['title']) ?></a></h3>
                        <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted);">
                            <span><?= $m['release_year'] ?></span>
                            <span><?= fmtRev($m['revenue']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- RECENT COMMENTS -->
        <div>
          <div class="chart-label" style="margin-bottom: 1rem;">YOUR RECENT ACTIVITY</div>
          <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 1.25rem;">My Comments</h3>
            <?php if (empty($userComments)): ?>
                <p class="text-muted" style="font-size: 0.85rem; text-align: center; padding: 2rem 0;">You haven't posted any comments yet.</p>
            <?php else: ?>
                <?php foreach ($userComments as $c): ?>
                    <div class="comment-item">
                        <div style="font-size: 0.7rem; color: var(--accent-primary); font-weight: 700; margin-bottom: 0.35rem;">
                            <a href="movie_details.php?id=<?= $c['movie_id'] ?>" style="color: inherit; text-decoration: none;"><?= strtoupper(htmlspecialchars($c['title'])) ?></a>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; margin-bottom: 0.5rem;"><?= htmlspecialchars($c['content']) ?></div>
                        <div style="font-size: 0.65rem; color: var(--text-muted); text-align: right;"><?= date('M d, Y', strtotime($c['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </main>
</body>
</html>
