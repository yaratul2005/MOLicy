<?php
/**
 * Search Results Page
 */
$pageTitle = 'Search - AntiGravity Forum';
require ROOT_PATH . '/themes/antigravity/partials/header.php';
$q = htmlspecialchars($_GET['q'] ?? '');
?>

<div class="search-page">
    <div class="search-header magnetic-container">
        <h1>Search the Forum</h1>
        <form action="/search" method="GET" class="search-form" id="main-search-form">
            <input type="text" name="q" value="<?= $q ?>" placeholder="Search for anything..." autofocus id="search-page-input">
            <button type="submit" class="btn btn-primary magnetic">Search</button>
        </form>
        
        <div class="search-filters">
            <button class="filter-btn active" data-type="threads">Threads</button>
            <button class="filter-btn" data-type="posts">Posts</button>
            <button class="filter-btn" data-type="users">Users</button>
        </div>
    </div>

    <div class="search-results-container">
        <div id="search-spinner" class="spinner" style="display:none;"></div>
        <div id="search-results">
            <?php if (!$q): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <p>Enter a query above to start searching.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.search-page { max-width: 800px; margin: 0 auto; }
.search-header {
    text-align: center; margin-bottom: 40px;
    animation: slideDown 0.6s var(--spring-bounce) backwards;
}
.search-header h1 { font-size: 2.5rem; margin-bottom: 24px; color: var(--color-cyan); }
.search-form { display: flex; gap: 12px; margin-bottom: 24px; }
.search-form input {
    flex: 1; padding: 16px 24px; font-size: 1.1rem;
    background: var(--color-surface); border: 1px solid var(--color-border);
    border-radius: 12px; color: var(--color-text-main); transition: all 0.3s;
}
.search-form input:focus { outline: none; border-color: var(--color-cyan); box-shadow: 0 0 0 3px rgba(6,182,212,0.15); }
.search-filters { display: flex; justify-content: center; gap: 12px; }
.filter-btn {
    background: transparent; border: 1px solid var(--color-border);
    color: var(--color-muted); padding: 8px 16px; border-radius: 20px;
    cursor: pointer; transition: all 0.3s;
}
.filter-btn.active { background: rgba(124,58,237,0.1); color: var(--color-violet); border-color: var(--color-violet); }
.filter-btn:hover:not(.active) { color: var(--color-text-main); border-color: rgba(255,255,255,0.2); }

.search-results-container { min-height: 200px; position: relative; }
.result-card {
    background: var(--color-surface); border: 1px solid var(--color-border);
    border-radius: 12px; padding: 20px; margin-bottom: 16px;
    transition: transform 0.3s var(--spring-smooth), box-shadow 0.3s;
    animation: slideUp 0.4s var(--spring-bounce) backwards;
}
.result-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.1); }
.result-card mark { background: rgba(6,182,212,0.2); color: var(--color-cyan); padding: 0 2px; border-radius: 2px; }
.result-title { font-size: 1.2rem; margin-bottom: 8px; font-family: var(--font-hero); }
.result-title a { color: var(--color-text-main); text-decoration: none; }
.result-meta { font-size: 0.8rem; color: var(--color-muted); display: flex; gap: 12px; margin-bottom: 12px; }
.result-snippet { font-size: 0.9rem; color: #cbd5e1; line-height: 1.5; }

.empty-state { text-align: center; padding: 40px; color: var(--color-muted); }
.empty-icon { font-size: 3rem; margin-bottom: 16px; opacity: 0.5; }

.spinner {
    width: 40px; height: 40px; margin: 40px auto;
    border: 3px solid rgba(255,255,255,0.1); border-top-color: var(--color-cyan);
    border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
let currentType = 'threads';
const qInput = document.getElementById('search-page-input');
const resultsDiv = document.getElementById('search-results');
const spinner = document.getElementById('search-spinner');

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        currentType = e.target.dataset.type;
        if (qInput.value.trim().length >= 2) performSearch();
    });
});

document.getElementById('main-search-form').addEventListener('submit', (e) => {
    e.preventDefault();
    if (qInput.value.trim().length >= 2) performSearch();
});

async function performSearch() {
    const q = qInput.value.trim();
    history.replaceState(null, '', `/search?q=${encodeURIComponent(q)}`);
    resultsDiv.style.display = 'none';
    spinner.style.display = 'block';

    try {
        const res = await fetch(`/api/v1/search?q=${encodeURIComponent(q)}&type=${currentType}`);
        const data = await res.json();
        spinner.style.display = 'none';
        resultsDiv.style.display = 'block';

        if (!data.data || data.data.length === 0) {
            resultsDiv.innerHTML = `<div class="empty-state"><div class="empty-icon">🏜️</div><p>No results found for "${q}" in ${currentType}.</p></div>`;
            return;
        }

        let html = '';
        data.data.forEach((item, i) => {
            const delay = i * 0.05;
            if (currentType === 'threads') {
                html += `
                <div class="result-card" style="animation-delay: ${delay}s">
                    <h3 class="result-title"><a href="/thread/${item.slug}">${item.title_highlighted}</a></h3>
                    <div class="result-meta">
                        <span>By ${item.username}</span>
                        <span>in <a href="/category/${item.category_slug}" style="color:var(--color-cyan)">${item.category_name}</a></span>
                        <span>${new Date(item.created_at).toLocaleDateString()}</span>
                        <span>${item.reply_count} replies</span>
                    </div>
                </div>`;
            } else if (currentType === 'posts') {
                html += `
                <div class="result-card" style="animation-delay: ${delay}s">
                    <div class="result-meta">
                        <span>Posted by ${item.username}</span>
                        <span>in <a href="/thread/${item.thread_slug}" style="color:var(--color-violet)">${item.thread_title}</a></span>
                        <span>${new Date(item.created_at).toLocaleDateString()}</span>
                    </div>
                    <div class="result-snippet">${item.snippet}</div>
                </div>`;
            } else if (currentType === 'users') {
                html += `
                <div class="result-card" style="animation-delay: ${delay}s; display:flex; align-items:center; gap:16px;">
                    <div style="width:50px;height:50px;border-radius:50%;background:var(--color-border);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;">
                        ${item.avatar ? `<img src="${item.avatar}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : item.username.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <h3 class="result-title" style="margin:0"><a href="/u/${item.username}">${item.username}</a> <span style="font-size:0.7rem;background:rgba(255,255,255,0.1);padding:2px 6px;border-radius:10px;">L${item.trust_level}</span></h3>
                        <div class="result-meta" style="margin-top:4px;">Reputation: ${item.reputation}</div>
                    </div>
                </div>`;
            }
        });
        resultsDiv.innerHTML = html;
    } catch (e) {
        spinner.style.display = 'none';
        resultsDiv.style.display = 'block';
        resultsDiv.innerHTML = `<div class="empty-state"><p style="color:var(--color-danger)">An error occurred while searching.</p></div>`;
    }
}

// Auto-run if Q exists
if (qInput.value.trim().length >= 2) performSearch();
</script>

<?php require ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
