<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
  <div class="sidebar-logo">Cinematic Lens</div>
  <div class="sidebar-subtitle">Editorial Insights</div>
  
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
      <span class="nav-icon">&#x1F3E0;</span> Dashboard
    </a>
    <a href="index.php" class="nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
      <span class="nav-icon">&#x1F3AC;</span> Directors
    </a>
    <a href="genres.php" class="nav-item <?= ($currentPage == 'genres.php') ? 'active' : '' ?>">
      <span class="nav-icon">&#x1F3AD;</span> Genres
    </a>
    <a href="insights.php" class="nav-item <?= ($currentPage == 'insights.php') ? 'active' : '' ?>">
      <span class="nav-icon">&#x1F4A1;</span> Interactive Insights
    </a>
    <a href="#" class="nav-item">
      <span class="nav-icon">&#x2699;</span> Collaborations
    </a>
    <a href="#" class="nav-item">
      <span class="nav-icon">&#x1F4CA;</span> Regional Stats
    </a>
  </nav>

 
</aside>
