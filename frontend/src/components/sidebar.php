<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
  <div class="sidebar-logo">Cinematic Lens</div>
  <div class="sidebar-subtitle">Editorial Insights</div>
  
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
      Dashboard
    </a>
    <a href="index.php" class="nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
      Directors
    </a>
    <a href="genres.php" class="nav-item <?= ($currentPage == 'genres.php') ? 'active' : '' ?>">
      Genres
    </a>
    <a href="insights.php" class="nav-item <?= ($currentPage == 'insights.php') ? 'active' : '' ?>">
       Insights
    </a>
    <a href="collaborations.php" class="nav-item <?= ($currentPage == 'collaborations.php') ? 'active' : '' ?>">
      Collaborations
    </a>
    <a href="industry.php" class="nav-item <?= ($currentPage == 'industry.php') ? 'active' : '' ?>">
     Regional Stats
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="manage.php" class="nav-item <?= ($currentPage == 'manage.php') ? 'active' : '' ?>">
       Manage Records
    </a>
  </div>
</aside>
