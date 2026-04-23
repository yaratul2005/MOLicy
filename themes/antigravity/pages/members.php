<?php
$pageTitle = 'Members';
include ROOT_PATH . '/themes/antigravity/partials/header.php';
?>

<div class="content-left" style="grid-column: span 2;">
    <div class="page-header animate-fall-in">
        <h1>🌐 Community Members</h1>
        <p style="color:var(--color-text-muted)">Discover the people who make this forum great.</p>
    </div>

    <!-- Filter/Sort Bar -->
    <form class="members-filter animate-rise-in stagger-1" method="GET" action="/members">
        <input type="text" name="q" placeholder="Search by username…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="filter-input">
        <div class="sort-links">
            <a href="?sort=joined&q=<?= urlencode($_GET['q'] ?? '') ?>" class="filter-btn <?= ($_GET['sort'] ?? 'joined') === 'joined' ? 'active' : '' ?>">🆕 Newest</a>
            <a href="?sort=reputation&q=<?= urlencode($_GET['q'] ?? '') ?>" class="filter-btn <?= ($_GET['sort'] ?? '') === 'reputation' ? 'active' : '' ?>">⭐ Top Reputation</a>
            <a href="?sort=posts&q=<?= urlencode($_GET['q'] ?? '') ?>" class="filter-btn <?= ($_GET['sort'] ?? '') === 'posts' ? 'active' : '' ?>">📝 Most Posts</a>
            <a href="?sort=activity&q=<?= urlencode($_GET['q'] ?? '') ?>" class="filter-btn <?= ($_GET['sort'] ?? '') === 'activity' ? 'active' : '' ?>">🟢 Recently Active</a>
        </div>
        <button type="submit" class="btn magnetic">Search</button>
    </form>

    <div class="members-grid">
        <?php foreach ($members as $i => $m): ?>
            <a href="/u/<?= htmlspecialchars($m['username']) ?>" class="member-card animate-rise-in magnetic" style="animation-delay:<?= $i * 40 ?>ms">
                <div class="member-avatar">
                    <?php if (!empty($m['avatar'])): ?>
                        <img src="<?= htmlspecialchars($m['avatar']) ?>" alt="<?= htmlspecialchars($m['username']) ?>">
                    <?php else: ?>
                        <span><?= strtoupper(substr($m['username'], 0, 1)) ?></span>
                    <?php endif; ?>
                    <div class="trust-ring trust-<?= min((int)$m['trust_level'], 5) ?>"></div>
                </div>
                <div class="member-info">
                    <strong><?= htmlspecialchars($m['username']) ?></strong>
                    <span class="trust-label">Level <?= (int)$m['trust_level'] ?></span>
                    <?php if (!empty($m['bio'])): ?>
                        <p><?= htmlspecialchars(mb_substr($m['bio'], 0, 60)) ?>…</p>
                    <?php endif; ?>
                </div>
                <div class="member-stats">
                    <div><strong><?= number_format($m['reputation'] ?? 0) ?></strong><br><small>Rep</small></div>
                    <div><strong><?= number_format($m['post_count'] ?? 0) ?></strong><br><small>Posts</small></div>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if (empty($members)): ?>
            <div class="empty-state" style="grid-column: span 3; padding: 80px 0;">
                <p>No members found matching your search.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>&sort=<?= htmlspecialchars($_GET['sort'] ?? 'joined') ?>&q=<?= urlencode($_GET['q'] ?? '') ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.page-header { margin-bottom: 32px; }
.page-header h1 { font-size: clamp(1.5rem, 3vw, 2.2rem); margin-bottom: 8px; }
.members-filter { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 32px; padding: 16px 20px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; }
.filter-input { flex: 1; min-width: 200px; padding: 10px 14px; background: rgba(0,0,0,.2); border: 1px solid var(--color-border); border-radius: 8px; color: var(--color-text); font-family: var(--font-body); }
.filter-input:focus { outline: none; border-color: var(--color-violet); }
.sort-links { display: flex; gap: 6px; flex-wrap: wrap; }
.filter-btn { font-size: 0.8rem; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--glass-bg); color: var(--color-text-muted); text-decoration: none; transition: all 180ms; }
.filter-btn:hover, .filter-btn.active { border-color: var(--color-violet); color: var(--color-text); background: rgba(124,58,237,.1); }
.members-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px; }
.member-card { display: flex; align-items: flex-start; gap: 16px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 20px; text-decoration: none; transition: border-color 220ms, transform 220ms var(--spring-bounce), box-shadow 220ms; }
.member-card:hover { border-color: var(--color-violet); transform: translateY(-3px); box-shadow: 0 10px 30px rgba(124,58,237,.12); }
.member-avatar { position: relative; flex-shrink: 0; }
.member-avatar img, .member-avatar span { width: 52px; height: 52px; border-radius: 50%; display: block; }
.member-avatar img { object-fit: cover; border: 2px solid var(--color-border); }
.member-avatar span { background: linear-gradient(135deg, var(--color-violet), var(--color-cyan)); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; color: white; border: 2px solid var(--color-border); }
.trust-ring { position: absolute; bottom: -2px; right: -2px; width: 16px; height: 16px; border-radius: 50%; border: 2px solid var(--bg, #07070d); }
.trust-0, .trust-1 { background: #94a3b8; }
.trust-2 { background: #10b981; }
.trust-3 { background: #06b6d4; }
.trust-4 { background: #7c3aed; }
.trust-5 { background: #f59e0b; }
.member-info { flex: 1; min-width: 0; }
.member-info strong { display: block; color: var(--color-text); font-size: 1rem; margin-bottom: 2px; }
.trust-label { font-size: 0.72rem; color: var(--color-text-muted); }
.member-info p { font-size: 0.8rem; color: var(--color-text-muted); margin: 6px 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.member-stats { display: flex; gap: 12px; text-align: center; flex-shrink: 0; }
.member-stats div { font-size: 0.78rem; color: var(--color-text-muted); }
.member-stats strong { display: block; font-size: 1rem; color: var(--color-cyan); }
.pagination { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
.page-btn { padding: 8px 14px; border: 1px solid var(--color-border); border-radius: 8px; text-decoration: none; color: var(--color-text-muted); transition: all 180ms; font-size: 0.9rem; }
.page-btn:hover, .page-btn.active { border-color: var(--color-violet); color: var(--color-violet); background: rgba(124,58,237,.1); }
@media (max-width: 768px) { .members-grid { grid-template-columns: 1fr 1fr; } .member-stats { display: none; } }
@media (max-width: 480px) { .members-grid { grid-template-columns: 1fr; } }
</style>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
