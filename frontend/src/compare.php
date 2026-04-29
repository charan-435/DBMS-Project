<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();

$m1_id = $_GET['m1'] ?? null;
$m2_id = $_GET['m2'] ?? null;

$movie1 = $m1_id ? $service->getMovieWithCast($m1_id) : null;
$movie2 = $m2_id ? $service->getMovieWithCast($m2_id) : null;

// Handle edge cases where ID is invalid
if ($m1_id && !$movie1) $m1_id = null;
if ($m2_id && !$movie2) $m2_id = null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>The Cinematic Lens - Compare Movies</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .compare-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 2rem;
      position: relative;
    }
    .selection-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }
    .search-box {
      position: relative;
    }
    .search-suggestions {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 0 0 8px 8px;
      z-index: 1000;
      display: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .suggestion-item {
      padding: 12px 15px;
      cursor: pointer;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .suggestion-item:hover {
      background: var(--bg-highlight);
    }
    .builder-input {
      width: 100%; padding: 0.75rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);
      background: var(--bg-input); color: var(--text-primary); font-family: inherit; font-size: 0.85rem;
      transition: all 0.2s ease;
    }
    .builder-input:focus { outline: none; border-color: var(--accent-primary); box-shadow: 0 0 10px var(--accent-glow); }
    
    .comparison-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 2rem;
    }
    .comparison-table tr {
      border-bottom: 1px solid var(--border-color);
    }
    .comparison-table td {
      padding: 1.5rem;
      text-align: center;
    }
    .comparison-table .label-cell {
      background: rgba(255,255,255,0.02);
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.1em;
      width: 200px;
    }
    .winner {
      color: var(--accent-green) !important;
      font-weight: 800;
      background: rgba(52, 211, 153, 0.03);
    }
    .movie-header-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 2rem;
      text-align: center;
      transition: border-color 0.3s;
    }
    .movie-title {
      font-size: 1.8rem;
      font-weight: 800;
      margin-bottom: 1rem;
    }
    .vs-overlay {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      width: 50px;
      height: 50px;
      background: var(--accent-primary);
      color: var(--bg-dark);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      z-index: 10;
      box-shadow: 0 0 20px var(--accent-glow);
    }
    @media (max-width: 768px) {
      .compare-container, .selection-row { grid-template-columns: 1fr; }
      .vs-overlay { display: none; }
    }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div class="insight-header">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">ANALYTICAL TOOLS</p>
        <h1>Movie<br>Comparison</h1>
        <p class="mt-4">Head-to-head analysis of box office performance, critical reception, and industry impact.</p>
      </div>

      <div class="selection-row">
        <div class="search-box">
          <label class="text-xxs font-bold text-muted uppercase mb-2 block">First Movie</label>
          <input type="text" class="builder-input" id="search-m1" placeholder="Search for a movie..." value="<?= $movie1 ? htmlspecialchars($movie1['title']) : '' ?>" autocomplete="off">
          <div class="search-suggestions" id="suggest-m1"></div>
        </div>
        <div class="search-box">
          <label class="text-xxs font-bold text-muted uppercase mb-2 block">Second Movie</label>
          <input type="text" class="builder-input" id="search-m2" placeholder="Search for a movie..." value="<?= $movie2 ? htmlspecialchars($movie2['title']) : '' ?>" autocomplete="off">
          <div class="search-suggestions" id="suggest-m2"></div>
        </div>
      </div>

      <?php if ($movie1 || $movie2): ?>
      <div class="compare-container">
        <?php if ($movie1 && $movie2): ?>
          <div class="vs-overlay">VS</div>
        <?php endif; ?>

        <!-- Movie 1 Column -->
        <div>
          <?php if ($movie1): ?>
          <div class="movie-header-card" style="border-top: 4px solid var(--accent-primary);">
            <div class="text-accent text-xxs font-bold mb-2 uppercase"><?= getLanguageName($movie1['language']) ?> RELEASE</div>
            <div class="movie-title"><?= htmlspecialchars($movie1['title']) ?></div>
            <div class="text-muted text-sm"><?= htmlspecialchars($movie1['release_year']) ?> &bull; <?= htmlspecialchars($movie1['genre_name']) ?></div>
          </div>
          <?php else: ?>
          <div class="movie-header-card" style="opacity: 0.5; border-style: dashed;">
            <p>Select a movie to compare</p>
          </div>
          <?php endif; ?>
        </div>

        <!-- Movie 2 Column -->
        <div>
          <?php if ($movie2): ?>
          <div class="movie-header-card" style="border-top: 4px solid var(--accent-green);">
            <div style="color: var(--accent-green);" class="text-xxs font-bold mb-2 uppercase"><?= getLanguageName($movie2['language']) ?> RELEASE</div>
            <div class="movie-title"><?= htmlspecialchars($movie2['title']) ?></div>
            <div class="text-muted text-sm"><?= htmlspecialchars($movie2['release_year']) ?> &bull; <?= htmlspecialchars($movie2['genre_name']) ?></div>
          </div>
          <?php else: ?>
          <div class="movie-header-card" style="opacity: 0.5; border-style: dashed;">
            <p>Select a movie to compare</p>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($movie1 && $movie2): ?>
      <!-- Comparison Table -->
      <table class="comparison-table card">
        <tr>
          <td class="<?= ($movie1['rating_imdb'] > $movie2['rating_imdb']) ? 'winner' : '' ?>" style="font-size: 1.5rem;">★ <?= number_format($movie1['rating_imdb'], 1) ?></td>
          <td class="label-cell">IMDb Rating</td>
          <td class="<?= ($movie2['rating_imdb'] > $movie1['rating_imdb']) ? 'winner' : '' ?>" style="font-size: 1.5rem;">★ <?= number_format($movie2['rating_imdb'], 1) ?></td>
        </tr>
        <tr>
          <td class="<?= ($movie1['revenue'] > $movie2['revenue']) ? 'winner' : '' ?>">&#x20B9;<?= formatRevenue($movie1['revenue']) ?></td>
          <td class="label-cell">Box Office</td>
          <td class="<?= ($movie2['revenue'] > $movie1['revenue']) ? 'winner' : '' ?>">&#x20B9;<?= formatRevenue($movie2['revenue']) ?></td>
        </tr>
        <tr>
          <td style="font-weight: 600;"><?= htmlspecialchars($movie1['director_name']) ?></td>
          <td class="label-cell">Director</td>
          <td style="font-weight: 600;"><?= htmlspecialchars($movie2['director_name']) ?></td>
        </tr>
        <tr>
          <td style="font-size: 0.85rem; color: var(--text-secondary);">
            <?php 
              $castNames1 = !empty($movie1['cast']) ? array_column(array_slice($movie1['cast'], 0, 3), 'name') : [];
              echo implode(', ', $castNames1);
            ?>
          </td>
          <td class="label-cell">Leading Cast</td>
          <td style="font-size: 0.85rem; color: var(--text-secondary);">
            <?php 
              $castNames2 = !empty($movie2['cast']) ? array_column(array_slice($movie2['cast'], 0, 3), 'name') : [];
              echo implode(', ', $castNames2);
            ?>
          </td>
        </tr>
        <tr>
          <td><?= ($movie1['release_year'] < $movie2['release_year'] ? 'Earlier' : ($movie1['release_year'] == $movie2['release_year'] ? 'Same Year' : 'Later')) ?></td>
          <td class="label-cell">Release Order</td>
          <td><?= ($movie2['release_year'] < $movie1['release_year'] ? 'Earlier' : ($movie2['release_year'] == $movie1['release_year'] ? 'Same Year' : 'Later')) ?></td>
        </tr>
      </table>
      
      <div style="margin-top: 3rem; text-align: center;">
        <div class="card" style="display: inline-block; padding: 2rem;">
          <h3 style="font-weight: 800; margin-bottom: 1rem;">Analytical Conclusion</h3>
          <p style="color: var(--text-muted); max-width: 600px; line-height: 1.6;">
            <?php
              if ($movie1['rating_imdb'] > $movie2['rating_imdb'] && $movie1['revenue'] > $movie2['revenue']) {
                echo "<strong>" . htmlspecialchars($movie1['title']) . "</strong> is the dominant force here, excelling in both critical acclaim and commercial success.";
              } elseif ($movie2['rating_imdb'] > $movie1['rating_imdb'] && $movie2['revenue'] > $movie1['revenue']) {
                echo "<strong>" . htmlspecialchars($movie2['title']) . "</strong> stands out as the superior performer across all key performance indicators.";
              } else {
                echo "A fascinating trade-off: One film leads in artistic merit (IMDb), while the other commands a larger audience footprint (Box Office).";
              }
            ?>
          </p>
        </div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div style="text-align: center; padding: 5rem 0; color: var(--text-muted);">
        <div style="font-size: 3rem; margin-bottom: 1.5rem; opacity: 0.5;">📊</div>
        <h3 style="font-weight: 700;">Select two movies to begin analysis</h3>
        <p style="font-size: 0.85rem; margin-top: 0.5rem;">Use the search boxes above to find movies in our database.</p>
      </div>
      <?php endif; ?>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2026. DATA PROVIDED BY CINEANALYTICS GLOBAL.</div>
    </div>
  </main>

  <script>
    function setupSearch(inputId, suggestId, paramName) {
      const input = document.getElementById(inputId);
      const suggest = document.getElementById(suggestId);
      
      input.addEventListener('input', function() {
        const q = this.value;
        if (q.length < 2) {
          suggest.style.display = 'none';
          return;
        }
        
        fetch(`api_search.php?type=movies&q=${encodeURIComponent(q)}`)
          .then(r => r.json())
          .then(data => {
            if (data.length === 0) {
              suggest.style.display = 'none';
              return;
            }
            
            suggest.innerHTML = '';
            data.forEach(item => {
              if (item.type !== 'movie') return;
              const div = document.createElement('div');
              div.className = 'suggestion-item';
              div.innerHTML = `
                <div style="display:flex; flex-direction:column;">
                  <span style="font-weight:700; font-size:0.9rem;">${item.name}</span>
                  <span style="font-size:0.7rem; color:var(--text-muted);">${item.meta || ''}</span>
                </div>
                <span class="text-accent" style="font-size:0.7rem; font-weight:800;">SELECT</span>
              `;
              div.onclick = () => {
                const url = new URL(window.location.href);
                url.searchParams.set(paramName, item.id);
                window.location.href = url.toString();
              };
              suggest.appendChild(div);
            });
            suggest.style.display = 'block';
          });
      });
      
      document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !suggest.contains(e.target)) {
          suggest.style.display = 'none';
        }
      });
    }

    setupSearch('search-m1', 'suggest-m1', 'm1');
    setupSearch('search-m2', 'suggest-m2', 'm2');
  </script>
</body>
</html>
