/**
 * Live Search Logic
 */
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('global-search');
    const resultsDropdown = document.getElementById('search-results-dropdown');
    let debounceTimer;

    if (!searchInput || !resultsDropdown) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();

        clearTimeout(debounceTimer);
        if (query.length < 2) {
            resultsDropdown.innerHTML = '';
            resultsDropdown.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`api_search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    renderResults(data);
                })
                .catch(err => console.error('Search error:', err));
        }, 300);
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.classList.add('hidden');
        }
    });

    // Re-open on focus if query exists
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length >= 2 && resultsDropdown.children.length > 0) {
            resultsDropdown.classList.remove('hidden');
        }
    });

    function renderResults(results) {
        if (results.length === 0) {
            resultsDropdown.innerHTML = '<div style="padding: 1.5rem; color: var(--text-muted); font-size: 0.9rem; text-align: center; font-weight: 500;">No matching films found</div>';
            resultsDropdown.classList.remove('hidden');
            return;
        }

        resultsDropdown.innerHTML = '';
        results.forEach(movie => {
            const item = document.createElement('a');
            item.href = `explore.php?q=${encodeURIComponent(movie.title)}`;
            item.className = 'search-result-item';
            
            item.innerHTML = `
                <div class="result-poster">🎬</div>
                <div class="result-info">
                    <div class="result-title">${movie.title}</div>
                    <div class="result-meta">${movie.lang.toUpperCase()} • ${movie.year}</div>
                </div>
                <div class="result-rating">★ ${movie.rating}</div>
            `;
            resultsDropdown.appendChild(item);
        });

        resultsDropdown.classList.remove('hidden');
    }
});
