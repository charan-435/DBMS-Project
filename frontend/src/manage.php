<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$genres = $service->getAllGenres();
$directors = $service->getAllDirectors();

$message = '';
$messageType = '';

// ── Handle POST actions ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'insert_movie') {
        $ok = $service->insertMovie(
            trim($_POST['title']),
            (int)$_POST['release_year'],
            (float)$_POST['revenue'],
            trim($_POST['language']),
            (float)$_POST['rating_imdb'],
            (int)$_POST['director_id'],
            (int)$_POST['genre_id']
        );
        $message = $ok ? 'Movie added successfully!' : 'Failed to add movie.';
        $messageType = $ok ? 'success' : 'error';
    }

    elseif ($action === 'update_movie') {
        $ok = $service->updateMovie(
            (int)$_POST['movie_id'],
            trim($_POST['title']),
            (int)$_POST['release_year'],
            (float)$_POST['revenue'],
            trim($_POST['language']),
            (float)$_POST['rating_imdb'],
            (int)$_POST['director_id'],
            (int)$_POST['genre_id']
        );
        $message = $ok ? 'Movie updated successfully!' : 'Failed to update movie.';
        $messageType = $ok ? 'success' : 'error';
    }

    elseif ($action === 'delete_movie') {
        $ok = $service->deleteMovie((int)$_POST['movie_id']);
        $message = $ok ? 'Movie deleted successfully.' : 'Failed to delete movie.';
        $messageType = $ok ? 'success' : 'error';
    }

    elseif ($action === 'insert_genre') {
        $ok = $service->insertGenre(trim($_POST['genre_name']));
        $message = $ok ? 'Genre added!' : 'Failed to add genre (may already exist).';
        $messageType = $ok ? 'success' : 'error';
        $genres = $service->getAllGenres(); // refresh
    }

    elseif ($action === 'insert_director') {
        $ok = $service->insertDirector(trim($_POST['first_name']), trim($_POST['last_name']));
        $message = $ok ? 'Director added!' : 'Failed to add director.';
        $messageType = $ok ? 'success' : 'error';
        $directors = $service->getAllDirectors(); // refresh
    }
}

// ── Handle GET (load movie for editing) ──────────────────
$editMovie = null;
if (isset($_GET['edit'])) {
    $editMovie = $service->getMovieById((int)$_GET['edit']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Records — The Cinematic Lens</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .manage-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group label {
      display: block; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
      text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.4rem;
    }
    .form-group input, .form-group select {
      width: 100%; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm);
      border: 1px solid var(--border-color); background: var(--bg-input);
      color: var(--text-primary); font-family: inherit; font-size: 0.85rem;
      transition: border-color 0.2s;
    }
    .form-group input:focus, .form-group select:focus {
      outline: none; border-color: var(--accent-primary);
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }

    .btn-submit {
      background: var(--accent-primary); color: var(--bg-dark); border: none;
      padding: 0.7rem 1.5rem; border-radius: var(--radius-sm); font-weight: 700;
      font-size: 0.8rem; cursor: pointer; transition: all 0.2s; font-family: inherit;
      text-transform: uppercase; letter-spacing: 0.08em; width: 100%;
    }
    .btn-submit:hover { background: var(--accent-hover); transform: translateY(-1px); }
    .btn-danger {
      background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);
      padding: 0.5rem 1rem; border-radius: var(--radius-sm); font-weight: 600;
      font-size: 0.75rem; cursor: pointer; transition: all 0.2s; font-family: inherit;
    }
    .btn-danger:hover { background: rgba(239,68,68,0.3); }
    .btn-edit {
      background: rgba(110,168,254,0.15); color: var(--accent-blue); border: 1px solid rgba(110,168,254,0.3);
      padding: 0.4rem 0.8rem; border-radius: var(--radius-sm); font-weight: 600;
      font-size: 0.7rem; cursor: pointer; transition: all 0.2s; text-decoration: none;
    }
    .btn-edit:hover { background: rgba(110,168,254,0.3); }

    .flash-msg {
      padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;
      font-size: 0.85rem; font-weight: 600; animation: fadeIn 0.3s ease;
    }
    .flash-success { background: rgba(92,214,182,0.12); color: var(--accent-green); border: 1px solid rgba(92,214,182,0.25); }
    .flash-error { background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); }

    .section-title {
      font-size: 0.65rem; font-weight: 700; letter-spacing: 0.15em;
      text-transform: uppercase; color: var(--accent-primary); margin-bottom: 0.5rem;
    }
    .card-heading {
      font-size: 1.15rem; font-weight: 700; margin-bottom: 1.5rem;
    }
    .recent-table { width: 100%; border-collapse: collapse; }
    .recent-table th {
      font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em;
      text-transform: uppercase; color: var(--text-muted);
      padding: 0.6rem 0.75rem; text-align: left; border-bottom: 1px solid var(--border-color);
    }
    .recent-table td {
      padding: 0.7rem 0.75rem; font-size: 0.82rem; border-bottom: 1px solid var(--border-color);
      vertical-align: middle;
    }
    .recent-table tbody tr:hover { background: var(--bg-highlight); }
    .actions-cell { display: flex; gap: 0.5rem; align-items: center; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">

      <div style="margin-bottom: 1.5rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">DATABASE OPERATIONS</p>
        <h1 style="font-size: 2.25rem; font-weight: 800;">Manage <em style="color: var(--accent-primary); font-style: italic;">Records</em></h1>
        <p class="mt-2" style="color: var(--text-secondary); font-size: 0.88rem;">
          Insert, update, or delete movies, genres, and directors directly from the UI.
        </p>
      </div>

      <?php if ($message): ?>
        <div class="flash-msg flash-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="manage-grid">

        <!-- LEFT: Main Movie Form -->
        <div class="card">
          <div class="section-title"><?= $editMovie ? 'UPDATE MOVIE' : 'INSERT NEW MOVIE' ?></div>
          <div class="card-heading"><?= $editMovie ? 'Editing: ' . htmlspecialchars($editMovie['title']) : 'Add a Movie to the Database' ?></div>

          <form method="POST">
            <input type="hidden" name="action" value="<?= $editMovie ? 'update_movie' : 'insert_movie' ?>">
            <?php if ($editMovie): ?>
              <input type="hidden" name="movie_id" value="<?= $editMovie['movie_id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label>Movie Title</label>
              <input type="text" name="title" required placeholder="e.g. Baahubali 2"
                     value="<?= htmlspecialchars($editMovie['title'] ?? '') ?>">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Release Year</label>
                <input type="number" name="release_year" required min="1900" max="2030"
                       value="<?= $editMovie['release_year'] ?? '' ?>" placeholder="2024">
              </div>
              <div class="form-group">
                <label>Revenue (₹)</label>
                <input type="number" name="revenue" step="0.01" min="0"
                       value="<?= $editMovie['budget'] ?? '' ?>" placeholder="150000000">
              </div>
            </div>

            <div class="form-row-3">
              <div class="form-group">
                <label>Language</label>
                <select name="language" required>
                  <option value="">Select</option>
                  <?php foreach (['hi'=>'Hindi','te'=>'Telugu','ta'=>'Tamil','ml'=>'Malayalam','kn'=>'Kannada','en'=>'English'] as $code => $lang): ?>
                    <option value="<?= $code ?>" <?= ($editMovie['language'] ?? '') === $code ? 'selected' : '' ?>><?= $lang ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>IMDb Rating</label>
                <input type="number" name="rating_imdb" step="0.1" min="0" max="10"
                       value="<?= $editMovie['rating_imdb'] ?? '' ?>" placeholder="7.5">
              </div>
              <div class="form-group">
                <label>Genre</label>
                <select name="genre_id" required>
                  <option value="">Select Genre</option>
                  <?php foreach ($genres as $g): ?>
                    <option value="<?= $g['genre_id'] ?>" <?= ($editMovie['genre_id'] ?? '') == $g['genre_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($g['genre_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Director</label>
              <select name="director_id" required>
                <option value="">Select Director</option>
                <?php foreach ($directors as $d): ?>
                  <option value="<?= $d['director_id'] ?>" <?= ($editMovie['director_id'] ?? '') == $d['director_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn-submit">
              <?= $editMovie ? '&#x270F; Update Movie' : '&#x2795; Insert Movie' ?>
            </button>
            <?php if ($editMovie): ?>
              <a href="manage.php" style="display:block; text-align:center; margin-top:0.75rem; color:var(--text-muted); font-size:0.8rem;">Cancel Editing</a>
            <?php endif; ?>
          </form>
        </div>

        <!-- RIGHT: Quick Add Genre / Director -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
          <div class="card">
            <div class="section-title">ADD GENRE</div>
            <div class="card-heading">New Genre</div>
            <form method="POST">
              <input type="hidden" name="action" value="insert_genre">
              <div class="form-group">
                <label>Genre Name</label>
                <input type="text" name="genre_name" required placeholder="e.g. Sci-Fi">
              </div>
              <button type="submit" class="btn-submit">&#x2795; Add Genre</button>
            </form>
          </div>

          <div class="card">
            <div class="section-title">ADD DIRECTOR</div>
            <div class="card-heading">New Director</div>
            <form method="POST">
              <input type="hidden" name="action" value="insert_director">
              <div class="form-row">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" name="first_name" required placeholder="S.S.">
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" name="last_name" placeholder="Rajamouli">
                </div>
              </div>
              <button type="submit" class="btn-submit">&#x2795; Add Director</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Recent Movies Table with Edit/Delete -->
      <div class="card">
        <div class="section-title">EXISTING RECORDS</div>
        <div class="card-heading">Recent Movies in Database</div>
        <?php $recent = $service->getTrendingMovies(15); ?>
        <?php if (!empty($recent)): ?>
        <table class="recent-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Year</th>
              <th>Genre</th>
              <th>Rating</th>
              <th>Revenue</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($recent as $mov): ?>
            <tr>
              <td class="font-semibold"><?= htmlspecialchars($mov['title']) ?></td>
              <td><?= $mov['yr'] ?></td>
              <td><span class="genre-badge <?= getGenreClass($mov['genres']) ?>"><?= strtoupper($mov['genres']) ?></span></td>
              <td>&#x2B50; <?= number_format($mov['rating_imdb'], 1) ?></td>
              <td>&#x20B9;<?= formatRevenue($mov['revenue']) ?></td>
              <td class="actions-cell">
                <a href="?edit=<?= $mov['movie_id'] ?? '' ?>" class="btn-edit">Edit</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this movie?');">
                  <input type="hidden" name="action" value="delete_movie">
                  <input type="hidden" name="movie_id" value="<?= $mov['movie_id'] ?? '' ?>">
                  <button type="submit" class="btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <p class="text-muted" style="text-align:center; padding:2rem;">No movies in the database yet. Run DataReading.php to populate, or add one above.</p>
        <?php endif; ?>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. DATABASE OPERATIONS PANEL.</div>
    </div>
  </main>
</body>
</html>
