<?php
require_once __DIR__ . '/components/session.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

// Filter inputs
$searchQuery = trim($_GET['q'] ?? '');
$filterGenre = trim($_GET['genre'] ?? '');
$filterLang  = trim($_GET['lang'] ?? '');
$filterMinYear  = trim($_GET['min_year'] ?? '');
$filterMaxYear  = trim($_GET['max_year'] ?? '');
$filterMinRating = trim($_GET['min_rating'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'title');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;


$allGenres = $service->getAllGenres();
$allLangs  = $service->getDistinctLanguages();


$hasFilter = !empty($searchQuery) || !empty($filterGenre) || !empty($filterLang) || !empty($filterMinYear) || !empty($filterMaxYear) || !empty($filterMinRating);

$data = $service->searchMovies($searchQuery, $filterGenre, $filterLang, $filterMinYear, $filterMaxYear, $filterMinRating, $sortBy, $page, $perPage);
$results = $data['results'];
$total = $data['total'];
$totalPages = ceil($total / $perPage);


$singleMovie = null;
$cast = [];
$industryAvgRating = 0;
$industryAvgBudget = 1;

if (!empty($searchQuery) && !$hasFilter) {

}


function paginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'search.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search<?= $searchQuery ? ': ' . htmlspecialchars($searchQuery) : '' ?> — The Cinematic Lens</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<style>
    .filter-bar {
      background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg);
      padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
    }
    .filter-row { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; }
    .filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
    .filter-group label {
      font-size: 0.6rem; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; color: var(--text-muted);
    }
    .filter-group select, .filter-group input {
      padding: 0.5rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color);
      background: var(--bg-input); color: var(--text-primary); font-family: inherit; font-size: 0.8rem;
      min-width: 110px;
    }
    .filter-group select:focus, .filter-group input:focus { outline: none; border-color: var(--accent-primary); }
    .btn-filter {
      background: var(--accent-primary); color: var(--bg-dark); border: none; padding: 0.55rem 1.2rem;
      border-radius: 6px; font-weight: 700; font-size: 0.75rem; cursor: pointer; font-family: inherit;
      text-transform: uppercase; letter-spacing: 0.08em; transition: all 0.2s;
    }
    .btn-filter:hover { background: var(--accent-hover); }
    .btn-clear {
      background: transparent; color: var(--text-muted); border: 1px solid var(--border-color);
      padding: 0.55rem 1rem; border-radius: 6px; font-size: 0.75rem; cursor: pointer;
      font-family: inherit; transition: all 0.2s; text-decoration: none;
    }
    .btn-clear:hover { border-color: var(--text-secondary); color: var(--text-secondary); }

    .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .results-count { font-size: 0.82rem; color: var(--text-secondary); }
    .results-count strong { color: var(--text-primary); }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 1.5rem; }
    .page-btn {
      padding: 0.45rem 0.75rem; border-radius: 6px; border: 1px solid var(--border-color);
      background: var(--bg-card); color: var(--text-secondary); text-decoration: none;
      font-size: 0.8rem; font-weight: 500; transition: all 0.2s;
    }
    .page-btn:hover { border-color: var(--accent-primary); color: var(--accent-primary); }
    .page-btn.active { background: var(--accent-primary); color: var(--bg-dark); border-color: var(--accent-primary); font-weight: 700; }
    .page-btn.disabled { opacity: 0.3; pointer-events: none; }

    .empty-state {
      text-align: center; padding: 60px 20px;
      background: var(--bg-card); border-radius: var(--radius-lg);
      border: 1px dashed var(--border-color);
    }
</style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">

      <div style="margin-bottom: 1rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">ADVANCED SEARCH</p>
        <h1 style="font-size: 2rem; font-weight: 800;">Search <em style="color: var(--accent-primary); font-style: italic;">Films</em></h1>
      </div>

     
      <form action="search.php" method="GET" class="filter-bar">
        <div class="filter-row">
          <div class="filter-group" style="flex: 2;">
            <label>Title</label>
            <input type="text" name="q" placeholder="Search by title..." value="<?= htmlspecialchars($searchQuery) ?>">
          </div>
          <div class="filter-group">
            <label>Genre</label>
            <select name="genre">
              <option value="">All Genres</option>
              <?php foreach ($allGenres as $g): ?>
                <option value="<?= htmlspecialchars($g['genre_name']) ?>" <?= $filterGenre === $g['genre_name'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($g['genre_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label>Language</label>
            <select name="lang">
              <option value="">All</option>
              <?php foreach ($allLangs as $l): ?>
                <option value="<?= htmlspecialchars($l) ?>" <?= $filterLang === $l ? 'selected' : '' ?>>
                  <?= getLanguageName($l) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label>Year From</label>
            <input type="number" name="min_year" min="1900" max="2030" placeholder="2006" value="<?= htmlspecialchars($filterMinYear) ?>" style="width:80px;">
          </div>
          <div class="filter-group">
            <label>Year To</label>
            <input type="number" name="max_year" min="1900" max="2030" placeholder="2025" value="<?= htmlspecialchars($filterMaxYear) ?>" style="width:80px;">
          </div>
          <div class="filter-group">
            <label>Min Rating</label>
            <input type="number" name="min_rating" min="0" max="10" step="0.1" placeholder="0" value="<?= htmlspecialchars($filterMinRating) ?>" style="width:70px;">
          </div>
          <div class="filter-group">
            <label>Sort By</label>
            <select name="sort">
              <option value="rating" <?= $sortBy==='rating'?'selected':'' ?>>Rating</option>
              <option value="revenue" <?= $sortBy==='revenue'?'selected':'' ?>>Revenue</option>
              <option value="year" <?= $sortBy==='year'?'selected':'' ?>>Year</option>
              <option value="title" <?= $sortBy==='title'?'selected':'' ?>>Title</option>
            </select>
          </div>
          <div class="filter-group" style="justify-content: flex-end;">
            <label>&nbsp;</label>
            <button type="submit" class="btn-filter">&#x1F50D; Search</button>
          </div>
          <div class="filter-group" style="justify-content: flex-end;">
            <label>&nbsp;</label>
            <a href="search.php" class="btn-clear">Clear</a>
          </div>
        </div>
      </form>


        <div class="results-header">
          <div class="results-count">
            Showing <strong><?= count($results) ?></strong> of <strong><?= number_format($total) ?></strong> results
            <?php if ($totalPages > 1): ?> · Page <?= $page ?> of <?= $totalPages ?><?php endif; ?>
          </div>
        </div>

        <?php if (!empty($results)): ?>
        <div class="card" style="padding: 0;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Film</th>
                <th>Director</th>
                <th>Genre</th>
                <th>Year</th>
                <th>Rating</th>
                <th>Revenue</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $mov): ?>
              <tr>
                <td>
                  <div class="film-cell">
                    <div class="film-poster">&#x1F3AC;</div>
                    <div>
                      <div class="film-name"><?= htmlspecialchars($mov['title']) ?></div>
                      <div class="film-meta"><?= getLanguageName($mov['language'] ?? '') ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($mov['director_name'] ?? '') ?></td>
                <td><span class="genre-badge <?= getGenreClass($mov['genre_name'] ?? '') ?>"><?= strtoupper($mov['genre_name'] ?? '') ?></span></td>
                <td><?= $mov['release_year'] ?></td>
                <td>
                  <div class="imdb-score">
                    <span class="imdb-star">&#x2605;</span>
                    <?= number_format($mov['rating_imdb'] ?? 0, 1) ?>
                  </div>
                </td>
                <td class="font-bold">&#x20B9;<?= formatRevenue($mov['revenue'] ?? 0) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <a href="<?= paginationUrl(max(1, $page - 1)) ?>"
             class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">&#x2190; Prev</a>

          <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($p = $start; $p <= $end; $p++):
          ?>
            <a href="<?= paginationUrl($p) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
          <?php endfor; ?>

          <a href="<?= paginationUrl(min($totalPages, $page + 1)) ?>"
             class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next &#x2192;</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
          <div class="empty-state">
            <div style="font-size: 40px; margin-bottom: 15px;">&#x1F50D;</div>
            <h2 style="margin-bottom: 10px;">No results found</h2>
            <p class="text-muted">Try adjusting your filters or search for a different title.</p>
          </div>
        <?php endif; ?>


      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. ADVANCED SEARCH ENGINE.</div>
    </div>
  </main>
</body>
</html>