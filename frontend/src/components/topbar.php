<div class="topbar">
  <div class="topbar-brand">THE CINEMATIC LENS</div>
  <div class="topbar-center">
    
    <form action="search.php" method="GET" style="display: flex; width: 100%; margin: 0;">
      <div class="search-bar" style="width: 100%;">
        <span class="search-icon">&#x1F50D;</span>
        
        <input 
            type="text" 
            name="q" 
            placeholder="Search films, directors..." 
            value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
            required
            style="width: 100%; border: none; outline: none; background: transparent; color: inherit;"
        >
        
        <button type="submit" style="display: none;"></button>
      </div>
    </form>
    
  </div>
</div>