/**
 * Live Search Logic with Multi-Entity Support
 * Uses root-relative paths to work from any page.
 */
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('global-search');
    const searchType = document.getElementById('search-type');
    const resultsDropdown = document.getElementById('search-results-dropdown');
    let debounceTimer;

    if (!searchInput || !resultsDropdown) return;

    // Dynamically determine base URL to work from any page depth
    const baseUrl = (function() {
        const scripts = document.querySelectorAll('script[src*="search_live.js"]');
        if (scripts.length > 0) {
            return scripts[0].src.replace(/js\/search_live\.js.*/, '');
        }
        return '/';
    })();

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        const type = searchType ? searchType.value : 'all';

        clearTimeout(debounceTimer);
        if (query.length < 2) {
            resultsDropdown.innerHTML = '';
            resultsDropdown.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`${baseUrl}api_search.php?q=${encodeURIComponent(query)}&type=${encodeURIComponent(type)}`)
                .then(response => response.json())
                .then(data => renderResults(data, baseUrl))
                .catch(err => console.error('Search error:', err));
        }, 300);
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const query = searchInput.value.trim();
            if (query.length >= 2) {
                window.location.href = `${baseUrl}explore.php?q=${encodeURIComponent(query)}`;
            }
        }
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.classList.add('hidden');
        }
    });

    function renderResults(results, base) {
        base = base || baseUrl;
        if (results.length === 0) {
            resultsDropdown.innerHTML = '<div style="padding: 1.5rem; color: var(--text-muted); font-size: 0.9rem; text-align: center; font-weight: 500;">No matching results found</div>';
            resultsDropdown.classList.remove('hidden');
            return;
        }

        resultsDropdown.innerHTML = '';
        results.forEach(item => {
            const a = document.createElement('a');
            a.className = 'search-result-item';

            let icon = '🎬';
            let href = '';
            let typeLabel = '';
            let typeBg = '';
            let subMeta = item.meta ? `📅 ${item.meta}` : '';

            if (item.type === 'movie') {
                icon = '🎞️';
                href = `${base}movie_details.php?id=${item.id}`;
                typeLabel = 'MOVIE';
                typeBg = 'rgba(126,175,232,0.15)';
            } else if (item.type === 'director') {
                icon = '🎥';
                href = `${base}director_details.php?id=${item.id}`;
                typeLabel = 'DIRECTOR';
                typeBg = 'rgba(129, 140, 248, 0.15)';
                subMeta = 'View career analytics →';
            } else if (item.type === 'actor') {
                icon = '🎭';
                href = `${base}actor_details.php?id=${item.id}`;
                typeLabel = 'ACTOR';
                typeBg = 'rgba(52, 211, 153, 0.15)';
                subMeta = 'View star profile →';
            }

            a.href = href;
            a.innerHTML = `
                <div class="result-poster">${icon}</div>
                <div class="result-info">
                    <div class="result-title">${item.name}</div>
                    <div class="result-meta">${subMeta}</div>
                </div>
                <div style="font-size:0.6rem;font-weight:800;letter-spacing:0.05em;padding:3px 7px;border-radius:4px;background:${typeBg};color:var(--accent-primary);flex-shrink:0;">${typeLabel}</div>
            `;
            resultsDropdown.appendChild(a);
        });

        resultsDropdown.classList.remove('hidden');
    }
});
