<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure this path matches your folder structure for Database.php
require_once __DIR__ . '/../../backend/Database.php';

$searchQuery = trim($_GET['q'] ?? '');
$movie = null;
$cast = [];
$industryAvgRating = 0;
$industryAvgBudget = 1; // Default to 1 to prevent Division by Zero crashes

if (!empty($searchQuery)) {
    try {
        $conn = Database::getConnection();
        
        // 1. Search for the movie (Partial match, highest grossing first)
        $stmt = $conn->prepare("
            SELECT m.movie_id, m.title, m.release_year, m.budget, m.language, m.rating_imdb, 
                   CONCAT(d.first_name, ' ', d.last_name) as director_name, 
                   g.genre_name
            FROM Movies m
            JOIN Directors d ON m.director_id = d.director_id
            JOIN Genres g ON m.genre_id = g.genre_id
            WHERE m.title LIKE :query
            ORDER BY m.budget DESC
            LIMIT 1
        ");
        $stmt->execute(['query' => '%' . $searchQuery . '%']);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($movie) {
            // 2. Fetch the Cast
            $castStmt = $conn->prepare("
                SELECT CONCAT(a.first_name, ' ', a.last_name) as actor_name
                FROM Actors a
                JOIN Movie_Actors ma ON a.actor_id = ma.actor_id
                WHERE ma.movie_id = :movie_id
            ");
            $castStmt->execute(['movie_id' => $movie['movie_id']]);
            $cast = $castStmt->fetchAll(PDO::FETCH_COLUMN);

            // 3. Fetch Industry Averages
            $avgStmt = $conn->query("SELECT AVG(rating_imdb) as avg_rating, AVG(budget) as avg_budget FROM Movies WHERE budget > 0");
            if ($avgs = $avgStmt->fetch(PDO::FETCH_ASSOC)) {
                $industryAvgRating = $avgs['avg_rating'] ?? 0;
                $industryAvgBudget = $avgs['avg_budget'] > 0 ? $avgs['avg_budget'] : 1; 
            }
        }

    } catch (PDOException $e) {
        die("<h3 style='color:red; padding: 20px;'>Database Error: " . $e->getMessage() . "</h3>");
    }
}

function getLanguageName($code) {
    $map = ['hi'=>'Hindi', 'te'=>'Telugu', 'ta'=>'Tamil', 'ml'=>'Malayalam', 'kn'=>'Kannada'];
    return $map[strtolower(trim($code))] ?? strtoupper($code);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Results: <?= htmlspecialchars($searchQuery) ?> - The Cinematic Lens</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css"> <style>
    /* Scoped styles specifically for the search results elements */
    .back-link { color: var(--accent-blue, #3b82f6); text-decoration: none; font-size: 14px; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 5px; font-weight: 500; }
    .back-link:hover { text-decoration: underline; }

    .movie-hero { background: var(--bg-surface, #1e293b); border: 1px solid var(--border-color, #334155); border-radius: 16px; padding: 40px; margin-bottom: 30px; position: relative; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .movie-hero::after { content:''; position: absolute; top:0; right:0; width: 300px; height: 300px; background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, transparent 70%); pointer-events: none; }
    
    .genre-tag { display: inline-block; background: rgba(59,130,246,0.15); color: var(--accent-blue, #3b82f6); padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; border: 1px solid rgba(59,130,246,0.3); }
    
    .search-title { font-size: 42px; margin: 0 0 8px 0; line-height: 1.1; color: var(--text-main, #f8fafc); font-family: 'DM Sans', sans-serif; font-weight: 700;}
    .movie-meta { color: var(--text-muted, #94a3b8); font-size: 16px; margin-bottom: 30px; display: flex; align-items: center; gap: 8px; }
    
    .stats-grid-search { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
    .stat-box { background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border-color, #334155); padding: 24px; border-radius: 12px; transition: transform 0.2s; }
    .stat-box:hover { transform: translateY(-2px); }
    .stat-label { font-size: 11px; color: var(--text-muted, #94a3b8); text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 10px; font-weight: 600; }
    .stat-value { font-size: 32px; font-weight: 700; color: var(--text-main, #f8fafc); }
    .stat-insight { font-size: 12px; margin-top: 10px; font-weight: 600; display: inline-block; padding: 4px 8px; border-radius: 4px; }
    .positive { color: var(--accent-green, #10b981); background: rgba(16, 185, 129, 0.1); }
    .negative { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

    .cast-list { display: flex; flex-wrap: wrap; gap: 10px; }
    .cast-pill { background: var(--bg-base, #0f172a); border: 1px solid var(--border-color, #334155); padding: 8px 16px; border-radius: 8px; font-size: 13px; color: var(--text-main, #f8fafc); }
    
    .empty-state { text-align: center; padding: 80px 20px; background: var(--bg-surface, #1e293b); border-radius: 16px; border: 1px dashed var(--border-color, #334155); }
</style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
        
        <a href="dashboard.php" class="back-link">&#8592; Back to Dashboard</a>

        <?php if ($movie): ?>
            <?php 
                $budgetCr = ($movie['budget'] ?? 0) / 10000000;
                $avgBudgetCr = $industryAvgBudget / 10000000;
                
                $ratingDiff = ($movie['rating_imdb'] ?? 0) - $industryAvgRating;
                $budgetDiffPct = (($budgetCr - $avgBudgetCr) / $avgBudgetCr) * 100;
            ?>

            <div class="movie-hero">
                <span class="genre-tag"><?= htmlspecialchars($movie['genre_name'] ?? 'Unknown') ?></span>
                <h1 class="search-title"><?= htmlspecialchars($movie['title']) ?></h1>
                <div class="movie-meta">
                    Directed by <strong style="color: var(--text-main);"><?= htmlspecialchars($movie['director_name'] ?? 'Unknown') ?></strong> &bull; 
                    <span style="font-family: monospace; font-size: 14px; background: var(--bg-base); padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($movie['release_year'] ?? 'N/A') ?></span> &bull; 
                    <?= getLanguageName($movie['language'] ?? 'Unknown') ?>
                </div>

                <div class="stats-grid-search">
                    <div class="stat-box">
                        <div class="stat-label">Global Box Office</div>
                        <div class="stat-value" style="color: var(--accent-green, #10b981);">₹<?= number_format($budgetCr, 2) ?> Cr</div>
                        <div class="stat-insight <?= $budgetDiffPct >= 0 ? 'positive' : 'negative' ?>">
                            <?= $budgetDiffPct >= 0 ? '&#x2191;' : '&#x2193;' ?> 
                            <?= number_format(abs($budgetDiffPct), 1) ?>% vs Industry Avg
                        </div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-label">IMDb Score</div>
                        <div class="stat-value" style="color: #fbbf24;">⭐ <?= number_format($movie['rating_imdb'] ?? 0, 1) ?></div>
                        <div class="stat-insight <?= $ratingDiff >= 0 ? 'positive' : 'negative' ?>">
                            <?= $ratingDiff >= 0 ? '&#x2191;' : '&#x2193;' ?> 
                            <?= number_format(abs($ratingDiff), 1) ?> pts vs Industry Avg
                        </div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-label">Data Verdict</div>
                        <div class="stat-value" style="font-size: 20px; line-height: 1.4; padding-top: 5px;">
                            <?php 
                                if (($movie['rating_imdb'] ?? 0) >= 8 && $budgetCr > $avgBudgetCr) echo "Blockbuster Masterpiece";
                                elseif (($movie['rating_imdb'] ?? 0) >= 7.5) echo "Critically Acclaimed";
                                elseif ($budgetCr > ($avgBudgetCr * 2)) echo "Commercial Juggernaut";
                                else echo "Standard Release";
                            ?>
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Top Billing Cast</div>
                <div class="cast-list">
                    <?php if (empty($cast)): ?>
                        <div class="cast-pill">No cast data available</div>
                    <?php else: ?>
                        <?php foreach ($cast as $actor): ?>
                            <div class="cast-pill"><?= htmlspecialchars($actor) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif (!empty($searchQuery)): ?>
            <div class="empty-state">
                <div style="font-size: 40px; margin-bottom: 15px;">🔍</div>
                <h2 style="margin-bottom: 10px; color: var(--text-main);">No records found for "<?= htmlspecialchars($searchQuery) ?>"</h2>
                <p style="color: var(--text-muted);">Try searching for a different movie title or check your spelling.</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 40px; margin-bottom: 15px;">🎬</div>
                <h2 style="margin-bottom: 10px; color: var(--text-main);">Search The Database</h2>
                <p style="color: var(--text-muted);">Use the search bar in the top navigation to look up a specific movie's data profile.</p>
            </div>
        <?php endif; ?>
        
    </div>
  </main>
</body>
</html>