<?php
require_once __DIR__ . '/components/session.php';
require_once __DIR__ . '/../../backend/DataService.php';
require_once __DIR__ . '/components/utils.php';

$service = new DataService();
$totalMovies = $service->getTotalMovies();
$avgRating = $service->getAvgRating();
$mostActiveGenre = $service->getMostActiveGenre();
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
        <div class="suggestion-card" onclick="applySuggestion('language', 'AVG', 'rating_imdb', 'DESC')">
          <div class="suggestion-title">Best Languages</div>
          <div class="suggestion-desc">Compare average ratings across different film industries.</div>
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
                <option value="doughnut">Doughnut Chart</option>
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
          <option value="release_year">Year</option>
          <option value="genre_name">Genre</option>
          <option value="rating_imdb">Rating</option>
          <option value="revenue">Revenue</option>
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

    function runInsight() {
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
      
      const colors = [
        'rgba(249, 115, 22, 0.8)',
        'rgba(92, 214, 182, 0.8)',
        'rgba(110, 168, 254, 0.8)',
        'rgba(166, 141, 255, 0.8)',
        'rgba(255, 130, 150, 0.8)',
        'rgba(251, 191, 36, 0.8)'
      ];

      myChart = new Chart(ctx, {
        type: type,
        data: {
          labels: currentLabels,
          datasets: [{
            label: currentMetName,
            data: currentValues,
            backgroundColor: type === 'line' ? 'rgba(249, 115, 22, 0.2)' : colors,
            borderColor: type === 'line' ? 'rgba(249, 115, 22, 1)' : colors.map(c => c.replace('0.8', '1')),
            borderWidth: 1,
            fill: type === 'line'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: type === 'pie' || type === 'doughnut', position: 'right', labels: { color: '#eeeef5' } }
          },
          scales: (type === 'pie' || type === 'doughnut') ? {} : {
            y: { ticks: { color: '#64647a' }, grid: { color: '#1f1f27' } },
            x: { ticks: { color: '#64647a' }, grid: { color: '#1f1f27' } }
          }
        }
      });
    }

    function updateChartType() {
      if(currentData.length > 0) { renderChart(); }
    }

    function renderTable() {
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
        tr.innerHTML = `
          <td style="font-weight: 600;">${row.label || 'Unknown'}</td>
          <td style="color: var(--accent-primary); font-weight: 700;">${row.value}</td>
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
    }
    
    // Auto-run first insight
    window.onload = function() {
      const urlParams = new URLSearchParams(window.location.search);
      const q = urlParams.get('q');
      
      if (q) {
        document.getElementById('b-dimension').value = 'title';
        document.getElementById('b-metric-field').value = 'rating_imdb';
        updateMetricFunctions();
        document.getElementById('b-metric-func').value = 'MAX';
        addFilterRow();
        const firstRow = document.querySelector('.filter-row');
        firstRow.querySelector('.f-field').innerHTML += '<option value="title">Title</option>';
        firstRow.querySelector('.f-field').value = 'title';
        firstRow.querySelector('.f-op').value = 'LIKE';
        firstRow.querySelector('.f-val').value = q;
        runInsight();
      } else {
        applySuggestion('genre_name', 'AVG', 'rating_imdb', 'DESC');
      }
    };
  </script>
</body>
</html>
