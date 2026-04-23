<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
  <div class="sidebar-logo">Cinematic Lens</div>
  <div class="sidebar-subtitle">Editorial Insights</div>
  
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
      &#x1F4CA; Dashboard
    </a>
    <a href="index.php" class="nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
      &#x1F3AC; Directors
    </a>
    <a href="genres.php" class="nav-item <?= ($currentPage == 'genres.php') ? 'active' : '' ?>">
      &#x1F3AD; Genres
    </a>
    <a href="insights.php" class="nav-item <?= ($currentPage == 'insights.php') ? 'active' : '' ?>">
      &#x1F4A1; Insights
    </a>
    <a href="collaborations.php" class="nav-item <?= ($currentPage == 'collaborations.php') ? 'active' : '' ?>">
      &#x1F91D; Collaborations
    </a>
    <a href="industry.php" class="nav-item <?= ($currentPage == 'industry.php') ? 'active' : '' ?>">
      &#x1F30D; Regional Stats
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="manage.php" class="nav-item <?= ($currentPage == 'manage.php') ? 'active' : '' ?>">
      &#x2699; Manage Records
    </a>
  </div>
</aside>
