<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// Get filter options
$allGenres = $service->getAllGenres();
$allLangs = $service->getDistinctLanguages();

// Get current filter values
$query = $_GET['q'] ?? '';
$genre = $_GET['genre'] ?? '';
$lang = $_GET['lang'] ?? '';
$minYear = $_GET['min_year'] ?? '';
$maxYear = $_GET['max_year'] ?? '';
$minRating = $_GET['min_rating'] ?? '';
$sortBy = $_GET['sort'] ?? 'title'; // Default to Title ASC
$page = (int)($_GET['page'] ?? 1);
$perPage = 24;

$data = $service->searchMovies($query, $genre, $lang, $minYear, $maxYear, $minRating, $sortBy, $page, $perPage);
$movies = $data['results'];
$total = $data['total'];
$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Movies — The Cinematic Lens</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
  .browser-layout { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; margin-top: 1.5rem; }
  @media (max-width: 1024px) { .browser-layout { grid-template-columns: 1fr; } }
  
  .filter-sidebar { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; position: sticky; top: 1.5rem; height: fit-content; }
  .filter-group { margin-bottom: 1.5rem; }
  .filter-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; }
  .filter-input { width: 100%; padding: 0.6rem; background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 6px; font-size: 0.85rem; }
  
  .movie-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; }
  .movie-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; transition: all 0.3s; cursor: pointer; text-decoration: none; color: inherit; }
  .movie-card:hover { transform: translateY(-5px); border-color: var(--accent-primary); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
  .card-poster { height: 280px; background: #1f1f27; display: flex; align-items: center; justify-content: center; font-size: 3rem; border-bottom: 1px solid var(--border-color); }
  .card-info { padding: 1rem; }
  .card-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 0.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .card-meta { display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); }
  
  .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 3rem; }
  .page-link { padding: 0.5rem 1rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-primary); text-decoration: none; transition: all 0.2s; }
  .page-link.active { background: var(--accent-primary); color: var(--bg-dark); border-color: var(--accent-primary); font-weight: 800; }
  .page-link:hover:not(.active) { border-color: var(--accent-primary); }
  
  .results-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
</style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <header style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.2rem; font-weight: 800;">Browse <em style="color: var(--accent-primary); font-style: italic;">Library</em></h1>
        <p class="text-muted">Explore our complete collection of films with advanced filtering and sorting.</p>
      </header>
      
      <div class="browser-layout">
        <!-- Sidebar Filters -->
        <aside>
          <form class="filter-sidebar" method="GET" action="movies.php">
            <h3 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Filters</h3>
            
            <div class="filter-group">
              <label>Genre</label>
              <select name="genre" class="filter-input">
                <option value="">All Genres</option>
                <?php foreach ($allGenres as $g): ?>
                  <option value="<?= htmlspecialchars($g['genre_name']) ?>" <?= ($genre == $g['genre_name']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['genre_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="filter-group">
              <label>Language</label>
              <select name="lang" class="filter-input">
                <option value="">All Languages</option>
                <?php foreach ($allLangs as $l): ?>
                  <option value="<?= htmlspecialchars($l) ?>" <?= ($lang == $l) ? 'selected' : '' ?>>
                    <?= strtoupper($l) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="filter-group">
              <label>Year Range</label>
              <div style="display: flex; gap: 0.5rem;">
                <input type="number" name="min_year" class="filter-input" placeholder="From" value="<?= $minYear ?>">
                <input type="number" name="max_year" class="filter-input" placeholder="To" value="<?= $maxYear ?>">
              </div>
            </div>
            
            <div class="filter-group">
              <label>Min IMDb Rating</label>
              <input type="range" name="min_rating" min="0" max="10" step="0.5" value="<?= $minRating ?: 0 ?>" style="width: 100%; accent-color: var(--accent-primary);" oninput="this.nextElementSibling.innerText = this.value">
              <div style="text-align: center; font-size: 0.8rem; font-weight: 700; color: var(--accent-primary);"><?= $minRating ?: 0 ?></div>
            </div>
            
            <div class="filter-group">
              <label>Sort By</label>
              <select name="sort" class="filter-input">
                <option value="title" <?= ($sortBy == 'title') ? 'selected' : '' ?>>Title (A-Z)</option>
                <option value="rating" <?= ($sortBy == 'rating') ? 'selected' : '' ?>>Rating (High-Low)</option>
                <option value="revenue" <?= ($sortBy == 'revenue') ? 'selected' : '' ?>>Revenue (High-Low)</option>
                <option value="year" <?= ($sortBy == 'year') ? 'selected' : '' ?>>Newest First</option>
              </select>
            </div>
            
            <button type="submit" class="btn-accent" style="width: 100%; padding: 0.7rem; border-radius: 6px; font-weight: 800;">Apply Filters</button>
            <a href="movies.php" style="display: block; text-align: center; font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem; text-decoration: none;">Clear All</a>
          </form>
        </aside>
        
        <!-- Results Area -->
        <div class="results-area">
          <div class="results-info">
            <div style="font-size: 0.9rem; color: var(--text-muted);">
              Showing <span style="color: var(--text-primary); font-weight: 700;"><?= count($movies) ?></span> of <?= number_format($total) ?> movies
            </div>
          </div>
          
          <div class="movie-grid">
            <?php foreach ($movies as $movie): ?>
              <a href="movie_details.php?id=<?= $movie['movie_id'] ?>" class="movie-card">
                <div class="card-poster">🎬</div>
                <div class="card-info">
                  <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>
                  <div class="card-meta">
                    <span><?= $movie['release_year'] ?></span>
                    <span style="color: var(--accent-primary); font-weight: 800;">★ <?= number_format($movie['rating_imdb'], 1) ?></span>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          
          <?php if (empty($movies)): ?>
            <div style="text-align: center; padding: 5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg);">
              <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
              <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 0.5rem;">No movies found</h3>
              <p class="text-muted">Try adjusting your filters or search query.</p>
            </div>
          <?php endif; ?>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="pagination">
              <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                if ($page > 1): 
              ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">Prev</a>
              <?php endif; ?>
              
              <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
              <?php endfor; ?>
              
              <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Next</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. BROWSE MODULE.</div>
    </div>
  </main>

</body>
</html>
