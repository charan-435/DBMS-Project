<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$totalMovies = $service->getTotalMovies();
$avgRating = $service->getAvgRating();
$mostActiveGenre = $service->getMostActiveGenre();

// Curated Insights Data
$flopMasterpieces = $service->getFlopMasterpieces(3);
$disasters = $service->getCommercialDisasters(3);
$genreTrend = $service->getGenreTrend();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Explore Data — The Cinematic Lens</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .explore-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 1.5rem;
    margin-top: 1.5rem;
  }
  @media (max-width: 1024px) {
    .explore-grid { grid-template-columns: 1fr; }
  }
  
  .builder-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
  }
  
  .builder-group { margin-bottom: 1.2rem; }
  .builder-group label {
    display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;
  }
  .builder-input {
    width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid var(--border-color);
    background: var(--bg-input); color: var(--text-primary); font-family: inherit; font-size: 0.85rem;
  }
  .builder-input:focus { outline: none; border-color: var(--accent-primary); }
  
  .filter-row { display: grid; grid-template-columns: 1fr 70px 1fr 30px; gap: 0.5rem; margin-bottom: 0.8rem; align-items: center; }
  .filter-row select, .filter-row input { width: 100%; min-width: 0; padding: 0.5rem; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-primary); font-size: 0.8rem; box-sizing: border-box; }
  .btn-remove-filter { background: #ef4444; color: white; border: none; border-radius: 4px; width: 100%; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; }
  
  .btn-add-filter {
    background: transparent; border: 1px dashed var(--border-color); color: var(--text-secondary);
    padding: 0.5rem; width: 100%; border-radius: 4px; cursor: pointer; font-size: 0.8rem; transition: all 0.2s;
  }
  .btn-add-filter:hover { border-color: var(--accent-primary); color: var(--accent-primary); }
  
  .run-btn {
    width: 100%; padding: 0.75rem; background: var(--accent-primary); color: var(--bg-dark);
    border: none; border-radius: 6px; font-weight: 800; font-size: 0.9rem; text-transform: uppercase;
    cursor: pointer; transition: background 0.2s; margin-top: 1rem;
  }
  .run-btn:hover { background: var(--accent-hover); }
  
  .chart-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
  }
  .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
  .chart-container { flex: 1; min-height: 400px; position: relative; }
  
  .suggestions-row { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
  .suggestion-card {
    background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;
    padding: 1rem; min-width: 220px; cursor: pointer; transition: all 0.2s;
  }
  .suggestion-card:hover { border-color: var(--accent-primary); transform: translateY(-2px); }
  .suggestion-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 0.3rem; color: var(--text-primary); }
  .suggestion-desc { font-size: 0.75rem; color: var(--text-secondary); line-height: 1.4; }
  
  .results-panel { margin-top: 1.5rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; }
  .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
  
  .flex-row { display: flex; gap: 0.5rem; }

  /* Curated Insights Styling */
  .curated-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 3rem;
  }
  .insight-card { 
    background: var(--bg-card); 
    border: 1px solid var(--border-color); 
    border-radius: var(--radius-lg); 
    padding: 1.5rem;
    display: flex; 
    flex-direction: column; 
    transition: transform 0.2s, border-color 0.2s;
  }
  .insight-card:hover { border-color: var(--accent-primary); transform: translateY(-2px); }
  .q-number { font-size: 0.7rem; font-weight: 800; color: var(--text-muted); letter-spacing: 0.12em; margin-bottom: 0.6rem; text-transform: uppercase; }
  .q-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.3; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
  .q-desc { font-size: 1.15rem; color: var(--accent-primary); margin-bottom: 1.5rem; line-height: 1.45; font-weight: 700; }
  .a-content { background: rgba(17, 18, 26, 0.6); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); backdrop-filter: blur(12px); }
  
  .mini-table { width: 100%; border-collapse: collapse; }
  .mini-table th { text-align: left; font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); }
  .mini-table td { padding: 0.5rem 0; font-size: 0.85rem; border-bottom: 1px dashed var(--border-color); }
  .mini-table tr:last-child td { border-bottom: none; }

  .movie-link { color: inherit; text-decoration: none; transition: color 0.2s; }
  .movie-link:hover { color: var(--accent-primary); text-decoration: underline; }
</style>
</head>
<body>

  <?php include 'components/sidebar.php'; ?>

  <main class="main-content">
    <?php include 'components/topbar.php'; ?>

    <div class="page-content">
      <div style="margin-bottom: 1.5rem;">
        <p class="text-accent uppercase tracking-wider text-xxs mb-2 font-bold">INTELLIGENCE DASHBOARD</p>
        <h1 style="font-size: 2rem; font-weight: 800;">Explore <em style="color: var(--accent-primary); font-style: italic;">Movie Data</em></h1>
        <p class="text-muted" style="max-width: 800px; margin-top: 0.5rem; font-size: 0.9rem; line-height: 1.5;">
          Dive into our movie database and uncover insights using filters, visual analytics, and custom queries. Whether you're curious about top-rated films, trending genres, or director performance, this section lets you explore data your way.
        </p>
      </div>
      
      <!-- Quick Stats -->
      <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
          <div class="stat-card-header"><span class="stat-card-label">TOTAL MOVIES</span><div class="stat-card-icon">🎬</div></div>
          <div class="stat-card-value"><?= number_format($totalMovies) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><span class="stat-card-label">AVERAGE RATING</span><div class="stat-card-icon">⭐</div></div>
          <div class="stat-card-value"><?= number_format($avgRating, 1) ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-card-header"><span class="stat-card-label">TOP GENRE</span><div class="stat-card-icon">🎭</div></div>
          <div class="stat-card-value"><?= htmlspecialchars($mostActiveGenre['genre']) ?></div>
        </div>
      </div>

      <!-- Suggested Insights -->
      <h3 style="font-size: 1rem; margin-bottom: 0.8rem; font-weight: 700;">Try These Insights</h3>
      <div class="suggestions-row">
        <div class="suggestion-card" onclick="applySuggestion('genre_name', 'AVG', 'rating_imdb', 'DESC')">
          <div class="suggestion-title">Highest Rated Genres</div>
          <div class="suggestion-desc">Find which genres have the highest average IMDb scores.</div>
        </div>
        <div class="suggestion-card" onclick="applySuggestion('release_year', 'COUNT', 'movie_id', 'ASC')">
          <div class="suggestion-title">Movies by Year</div>
          <div class="suggestion-desc">Analyze trends in movie release volume over time.</div>
        </div>
        <div class="suggestion-card" onclick="applySuggestion('director_name', 'SUM', 'revenue', 'DESC')">
          <div class="suggestion-title">Top Revenue Directors</div>
          <div class="suggestion-desc">See which directors generated the highest total box office.</div>
        </div>
        <div class="suggestion-card" onclick="applySuggestion('genre_name', 'SUM', 'revenue', 'DESC')">
          <div class="suggestion-title">Genre Revenue Share</div>
          <div class="suggestion-desc">Identify the most profitable genres across the platform.</div>
        </div>
        <div class="suggestion-card" onclick="applySuggestion('language', 'AVG', 'rating_imdb', 'DESC')">
          <div class="suggestion-title">Best Languages</div>
          <div class="suggestion-desc">Compare average ratings across different film industries.</div>
        </div>
        <div class="suggestion-card" onclick="runTrendAnalysis()">
          <div class="suggestion-title">Comparative Genre Trend</div>
          <div class="suggestion-desc">Analyze the production volume of Action vs Romance over the years.</div>
        </div>
      </div>

      <div class="explore-grid">
        <!-- Insight Builder Panel -->
        <div class="builder-panel">
          <h2 style="font-size: 1.1rem; margin-bottom: 1.5rem; font-weight: 800;">Build Your Insight</h2>
          
          <div class="builder-group">
            <label>1. Dimension (Group By)</label>
            <select id="b-dimension" class="builder-input">
              <option value="genre_name">Genre</option>
              <option value="release_year">Release Year</option>
              <option value="director_name">Director</option>
              <option value="cast_names">Cast / Actors</option>
              <option value="language">Language</option>
              <option value="title">Movie Title</option>
            </select>
          </div>
          
          <div class="builder-group">
            <label>2. Metric (What to measure)</label>
            <div class="flex-row">
              <select id="b-metric-field" class="builder-input" style="flex: 1;">
                <option value="movie_id">Movies</option>
                <option value="rating_imdb">IMDb Rating</option>
                <option value="revenue">Revenue</option>
                <option value="release_year">Year</option>
              </select>
              <select id="b-metric-func" class="builder-input" style="flex: 1;">
                <!-- Populated dynamically by JS -->
                <option value="COUNT">Count</option>
              </select>
            </div>
            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.3rem;">Select the field first, then choose how to calculate it.</div>
          </div>
          
          <div class="builder-group">
            <label>3. Filters (Optional)</label>
            <div id="filters-container"></div>
            <button class="btn-add-filter" onclick="addFilterRow()">+ Add Condition</button>
          </div>
          
          <div class="builder-group">
            <label>4. Sorting & Limit</label>
            <div class="flex-row">
              <select id="b-sort" class="builder-input">
                <option value="DESC">Descending</option>
                <option value="ASC">Ascending</option>
              </select>
              <input type="number" id="b-limit" class="builder-input" value="15" min="1" max="100">
            </div>
          </div>
          
          <button class="run-btn" onclick="runInsight()">Generate Results</button>
        </div>
        
        <!-- Visualization Panel -->
        <div class="chart-panel">
          <div class="chart-header">
            <div>
              <h2 style="font-size: 1.1rem; font-weight: 800;">Visual Analytics</h2>
            </div>
            <div class="flex-row">
              <select id="chart-type" class="builder-input" style="width: auto; padding: 0.4rem;" onchange="updateChartType()">
                <option value="bar">Bar Chart</option>
                <option value="line">Line Chart</option>
                <option value="pie">Pie Chart</option>
                <option value="radar">Radar Chart</option>
              </select>
              <button class="btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;" onclick="downloadChart()">⬇ Download Chart</button>
            </div>
          </div>
          
          <div class="chart-container">
            <canvas id="insightChart"></canvas>
            <div id="chart-loading" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(18,18,24,0.8); display:flex; align-items:center; justify-content:center; font-weight:bold; color:var(--accent-primary); display:none;">
              Analyzing Data...
            </div>
          </div>
        </div>
      </div>
      
      <!-- Results Table -->
      <div class="results-panel">
        <div class="results-header">
          <h2 style="font-size: 1.1rem; font-weight: 800;">Data Results</h2>
          <button class="btn-accent" style="padding: 0.4rem 1rem; font-size: 0.75rem;" onclick="downloadCSV()">⬇ Download CSV</button>
        </div>
        <div style="overflow-x: auto;">
          <table class="data-table" id="results-table">
            <thead>
              <tr id="rt-head">
                <th>Label (Dimension)</th>
                <th>Value (Metric)</th>
              </tr>
            </thead>
            <tbody id="rt-body">
              <tr><td colspan="2" style="text-align:center; padding: 2rem; color:var(--text-muted);">Run an insight to view data here.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Curated Deep Dives (Insights from the Intelligence Hub) -->
      <div style="margin-top: 4rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem;">
        <h2 style="font-size: 1.5rem; font-weight: 800;">Curated <em style="color: var(--accent-primary); font-style: italic;">Deep Dives</em></h2>
        <div style="flex: 1; height: 1px; background: linear-gradient(to right, var(--border-color), transparent);"></div>
      </div>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">Pre-calculated intelligence reports focusing on anomalies and trends across the industry.</p>
      
      <div class="curated-grid">
         <!-- Flop Masterpieces -->
         <div class="insight-card">
           <div class="q-number">SPECIAL REPORT</div>
           <div class="q-title">The Flop Masterpieces</div>
           <div class="q-desc">Which universally acclaimed movies (IMDb ≥ 8.0) completely bombed at the box office?</div>
           <div class="a-content">
             <table class="mini-table">
               <tr><th>Movie</th><th style="text-align:right;">Revenue</th></tr>
               <?php foreach ($flopMasterpieces as $fm): ?>
               <tr>
                 <td style="font-weight: 600;"><a href="movie_details.php?id=<?= $fm['movie_id'] ?>" class="movie-link"><?= htmlspecialchars($fm['title']) ?></a><br><span style="color:var(--text-secondary); font-size: 0.7rem;">★ <?= number_format($fm['rating_imdb'], 1) ?></span></td>
                 <td style="text-align:right; color: #ef4444;">&#x20B9;<?= formatRevenue($fm['revenue']) ?></td>
               </tr>
               <?php endforeach; ?>
             </table>
           </div>
         </div>

         <!-- Commercial Disasters -->
         <div class="insight-card">
           <div class="q-number">SPECIAL REPORT</div>
           <div class="q-title">Commercial Hits, Critical Misses</div>
           <div class="q-desc">Which highly profitable movies were absolutely hated by audiences (IMDb < 5.0)?</div>
           <div class="a-content">
             <table class="mini-table">
               <tr><th>Movie</th><th style="text-align:right;">Revenue</th></tr>
               <?php foreach ($disasters as $cd): ?>
               <tr>
                 <td style="font-weight: 600;"><a href="movie_details.php?id=<?= $cd['movie_id'] ?>" class="movie-link"><?= htmlspecialchars($cd['title']) ?></a><br><span style="color:var(--text-secondary); font-size: 0.7rem;">★ <?= number_format($cd['rating_imdb'], 1) ?></span></td>
                 <td style="text-align:right; color: var(--accent-green); font-weight: bold;">&#x20B9;<?= formatRevenue($cd['revenue']) ?></td>
               </tr>
               <?php endforeach; ?>
             </table>
           </div>
         </div>

         <!-- Genre Trends -->
         <div class="insight-card">
           <div class="q-number">TREND ANALYSIS</div>
           <div class="q-title">Genre Shifts Over Time</div>
           <div class="q-desc">Production volume trend of Action versus Romance movies over the decades.</div>
           <div class="a-content" style="height: 180px; padding: 0.5rem; display: flex; align-items: center; justify-content: center;">
              <canvas id="curatedGenreTrendChart"></canvas>
           </div>
         </div>
      </div>

      <div class="page-footer">THE CINEMATIC LENS &copy; 2025. NO-CODE ANALYTICS ENGINE.</div>
    </div>
  </main>

  <script>
    let myChart = null;
    let currentData = [];
    let currentLabels = [];
    let currentValues = [];
    let currentDimName = '';
    let currentMetName = '';
    let isMultiSeries = false;
    let multiSeriesDatasets = [];

    const metricConfigs = {
      movie_id: { allowedFuncs: ['COUNT'] },
      rating_imdb: { allowedFuncs: ['AVG', 'MAX', 'MIN'] },
      revenue: { allowedFuncs: ['SUM', 'AVG', 'MAX', 'MIN'] },
      release_year: { allowedFuncs: ['MAX', 'MIN', 'COUNT'] }
    };

    const funcsMap = {
      COUNT: 'Count',
      AVG: 'Average',
      SUM: 'Sum',
      MAX: 'Max',
      MIN: 'Min'
    };

    function updateMetricFunctions() {
      const fieldSelect = document.getElementById('b-metric-field');
      const funcSelect = document.getElementById('b-metric-func');
      const selectedField = fieldSelect.value;
      const currentFunc = funcSelect.value;
      
      funcSelect.innerHTML = '';
      
      if (metricConfigs[selectedField]) {
        metricConfigs[selectedField].allowedFuncs.forEach(func => {
          const option = document.createElement('option');
          option.value = func;
          option.textContent = funcsMap[func];
          funcSelect.appendChild(option);
        });
        
        if (metricConfigs[selectedField].allowedFuncs.includes(currentFunc)) {
          funcSelect.value = currentFunc;
        } else {
          funcSelect.value = metricConfigs[selectedField].allowedFuncs[0];
        }
      }
    }

    document.getElementById('b-metric-field').addEventListener('change', updateMetricFunctions);
    
    // Initialize functions dropdown
    updateMetricFunctions();

    function addFilterRow() {
      const container = document.getElementById('filters-container');
      const row = document.createElement('div');
      row.className = 'filter-row';
      row.innerHTML = `
        <select class="f-field">
          <option value="search">Search All</option>
          <option value="title">Title</option>
          <option value="director_name">Director</option>
          <option value="cast_names">Cast / Actors</option>
          <option value="genre_name">Genre</option>
          <option value="release_year">Year</option>
          <option value="rating_imdb">Rating</option>
          <option value="revenue">Revenue</option>
          <option value="movie_count">Movie Count (Grouped)</option>
        </select>
        <select class="f-op" style="flex:0.5;">
          <option value="=">=</option>
          <option value=">">></option>
          <option value="<"><</option>
          <option value=">=">>=</option>
          <option value="<="><=</option>
          <option value="LIKE">Contains</option>
        </select>
        <input type="text" class="f-val" placeholder="Value">
        <button class="btn-remove-filter" onclick="this.parentElement.remove()">×</button>
      `;
      container.appendChild(row);
    }

    function applySuggestion(dim, mFunc, mField, sort) {
      document.getElementById('b-dimension').value = dim;
      document.getElementById('b-metric-field').value = mField;
      updateMetricFunctions();
      document.getElementById('b-metric-func').value = mFunc;
      document.getElementById('b-sort').value = sort;
      document.getElementById('filters-container').innerHTML = '';
      runInsight();
    }

    function runTrendAnalysis() {
      isMultiSeries = true;
      document.getElementById('chart-loading').style.display = 'flex';
      
      fetch('api_explore.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_trend_analysis' })
      })
      .then(r => r.json())
      .then(res => {
        document.getElementById('chart-loading').style.display = 'none';
        if(res.status === 'success') {
          currentDimName = 'Release Year';
          currentMetName = 'Film Count';
          currentLabels = res.data.map(d => d.yr);
          
          multiSeriesDatasets = [
            {
              label: 'Action',
              data: res.data.map(d => parseInt(d.action_count)),
              borderColor: '#f97316',
              backgroundColor: 'rgba(249, 115, 22, 0.1)',
              fill: true,
              tension: 0.4
            },
            {
              label: 'Romance',
              data: res.data.map(d => parseInt(d.romance_count)),
              borderColor: '#5cd6b6',
              backgroundColor: 'rgba(92, 214, 182, 0.1)',
              fill: true,
              tension: 0.4
            }
          ];
          
          document.getElementById('chart-type').value = 'line';
          renderChart();
          renderTrendTable(res.data);
        }
      });
    }

    function runInsight() {
      isMultiSeries = false;
      const dim = document.getElementById('b-dimension').value;
      const mFunc = document.getElementById('b-metric-func').value;
      const mField = document.getElementById('b-metric-field').value;
      const sort = document.getElementById('b-sort').value;
      const limit = document.getElementById('b-limit').value;
      
      const filters = [];
      document.querySelectorAll('.filter-row').forEach(row => {
        filters.push({
          field: row.querySelector('.f-field').value,
          operator: row.querySelector('.f-op').value,
          value: row.querySelector('.f-val').value
        });
      });

      const payload = {
        action: 'build_insight',
        dimension: dim,
        metric_func: mFunc,
        metric_field: mField,
        sort_dir: sort,
        limit: limit,
        filters: filters
      };

      document.getElementById('chart-loading').style.display = 'flex';

      fetch('api_explore.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(res => {
        document.getElementById('chart-loading').style.display = 'none';
        if(res.status === 'success') {
          currentDimName = res.query_info.dimension;
          currentMetName = res.query_info.metric;
          currentData = res.data;
          currentLabels = currentData.map(d => d.label || 'Unknown');
          currentValues = currentData.map(d => parseFloat(d.value) || 0);
          
          renderChart();
          renderTable();
        } else {
          alert("Error: " + res.message);
        }
      })
      .catch(e => {
        document.getElementById('chart-loading').style.display = 'none';
        console.error(e);
        alert("Failed to fetch data.");
      });
    }

    function renderChart() {
      const ctx = document.getElementById('insightChart').getContext('2d');
      const type = document.getElementById('chart-type').value;
      
      if (myChart) { myChart.destroy(); }
      
      let datasets = [];
      if (isMultiSeries) {
        datasets = multiSeriesDatasets.map(ds => ({
          ...ds,
          type: type === 'bar' ? 'bar' : 'line' // Allow toggling between bar/line for trends
        }));
      } else {
        const colors = [
          'rgba(249, 115, 22, 0.8)',
          'rgba(92, 214, 182, 0.8)',
          'rgba(110, 168, 254, 0.8)',
          'rgba(166, 141, 255, 0.8)',
          'rgba(255, 130, 150, 0.8)',
          'rgba(251, 191, 36, 0.8)'
        ];
        datasets = [{
          label: currentMetName,
          data: currentValues,
          backgroundColor: (type === 'line' || type === 'radar') ? 'rgba(249, 115, 22, 0.2)' : colors,
          borderColor: (type === 'line' || type === 'radar') ? 'rgba(249, 115, 22, 1)' : colors.map(c => c.replace('0.8', '1')),
          borderWidth: 2,
          fill: (type === 'line' || type === 'radar')
        }];
      }

      myChart = new Chart(ctx, {
        type: type,
        data: {
          labels: currentLabels,
          datasets: datasets
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: type === 'pie' || type === 'radar', position: 'right', labels: { color: '#eeeef5' } }
          },
          scales: (type === 'pie') ? {} : (type === 'radar' ? {
            r: {
              angleLines: { color: '#1f1f27' },
              grid: { color: '#1f1f27' },
              pointLabels: { color: '#64647a' },
              ticks: { display: false, backdropColor: 'transparent' }
            }
          } : {
            y: { ticks: { color: '#64647a' }, grid: { color: '#1f1f27' } },
            x: { ticks: { color: '#64647a' }, grid: { color: '#1f1f27' } }
          })
        }
      });
    }

    function updateChartType() {
      if(currentData.length > 0) { renderChart(); }
    }

    function renderTable() {
      if (isMultiSeries) return; // Handled by renderTrendTable
      const thead = document.getElementById('rt-head');
      const tbody = document.getElementById('rt-body');
      
      thead.innerHTML = `<th>${currentDimName.toUpperCase()}</th><th>${currentMetName.toUpperCase()}</th>`;
      tbody.innerHTML = '';
      
      if (currentData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2" style="text-align:center; padding: 2rem;">No results found for these filters.</td></tr>`;
        return;
      }
      
      currentData.forEach(row => {
        const tr = document.createElement('tr');
        let labelHtml = row.label || 'Unknown';
        
        if (row.movie_id) {
          labelHtml = `<a href="movie_details.php?id=${row.movie_id}" class="movie-link">${labelHtml} <span style="font-size: 0.7rem; color: var(--accent-primary);">↗</span></a>`;
        } else if (row.director_id) {
          labelHtml = `<a href="director_details.php?id=${row.director_id}" class="movie-link">${labelHtml} <span style="font-size: 0.7rem; color: var(--accent-primary);">↗</span></a>`;
        } else if (row.actor_id) {
          labelHtml = `<a href="actor_details.php?id=${row.actor_id}" class="movie-link">${labelHtml} <span style="font-size: 0.7rem; color: var(--accent-primary);">↗</span></a>`;
        }
        
        tr.innerHTML = `
          <td style="font-weight: 600;">${labelHtml}</td>
          <td style="color: var(--accent-primary); font-weight: 700;">${row.value}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    function renderTrendTable(data) {
      const thead = document.getElementById('rt-head');
      const tbody = document.getElementById('rt-body');
      
      thead.innerHTML = `<th>YEAR</th><th>ACTION</th><th>ROMANCE</th>`;
      tbody.innerHTML = '';
      
      data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td style="font-weight: 600;">${row.yr}</td>
          <td style="color: #f97316; font-weight: 700;">${row.action_count}</td>
          <td style="color: #5cd6b6; font-weight: 700;">${row.romance_count}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    function downloadChart() {
      const canvas = document.getElementById('insightChart');
      if(currentData.length === 0) { alert("Generate an insight first!"); return; }
      
      const link = document.createElement('a');
      link.download = 'movie-insight-chart.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
    }

    function downloadCSV() {
      if(currentData.length === 0) { alert("Generate an insight first!"); return; }
      
      let csv = `${currentDimName},${currentMetName}\n`;
      currentData.forEach(row => {
        // Escape quotes and wrap in quotes to handle commas in labels
        let safeLabel = `"${(row.label || 'Unknown').toString().replace(/"/g, '""')}"`;
        csv += `${safeLabel},${row.value}\n`;
      });
      
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', 'movie_insights.csv');
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }

    // Auto-run first insight or from URL params
    window.onload = function() {
      const urlParams = new URLSearchParams(window.location.search);
      const q = urlParams.get('q');
      
      if (q) {
        // Default to a Career Trend (Line Chart) when searching for a specific name
        document.getElementById('b-dimension').value = 'release_year';
        document.getElementById('b-metric-field').value = 'revenue';
        document.getElementById('b-chart-type').value = 'line';
        updateMetricFunctions();
        document.getElementById('b-metric-func').value = 'SUM';
        
        const container = document.getElementById('filters-container');
        container.innerHTML = ''; 
        
        addFilterRow();
        const row = container.lastElementChild;
        row.querySelector('.f-field').value = 'search'; 
        row.querySelector('.f-op').value = 'LIKE';
        row.querySelector('.f-val').value = q;
        
        runInsight();
      } else {
        applySuggestion('genre_name', 'AVG', 'rating_imdb', 'DESC');
      }

      // Initialize Curated Genre Trend Chart
      <?php
        $trendYears = [];
        $actionCounts = [];
        $romanceCounts = [];
        foreach ($genreTrend as $row) { 
            $trendYears[] = (int)$row['yr'];
            $actionCounts[] = (int)$row['action_count'];
            $romanceCounts[] = (int)$row['romance_count'];
        }
      ?>
      const curatedCtx = document.getElementById('curatedGenreTrendChart').getContext('2d');
      new Chart(curatedCtx, {
          type: 'line',
          data: {
              labels: <?= json_encode($trendYears) ?>,
              datasets: [
                  {
                      label: 'Action',
                      data: <?= json_encode($actionCounts) ?>,
                      borderColor: '#f5c518',
                      backgroundColor: 'rgba(245, 197, 24, 0.1)',
                      borderWidth: 2,
                      tension: 0.4,
                      fill: true,
                      pointRadius: 0
                  },
                  {
                      label: 'Romance',
                      data: <?= json_encode($romanceCounts) ?>,
                      borderColor: '#5cd6b6',
                      backgroundColor: 'rgba(92, 214, 182, 0.1)',
                      borderWidth: 2,
                      tension: 0.4,
                      fill: true,
                      pointRadius: 0
                  }
              ]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      display: true,
                      position: 'top',
                      labels: { color: '#8b8d9e', font: { size: 9 }, usePointStyle: true }
                  }
              },
              scales: {
                  x: { display: false },
                  y: { display: false }
              }
          }
      });
    };
  </script>
</body>
</html>
