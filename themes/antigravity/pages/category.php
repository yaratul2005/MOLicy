<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

<div class="content-left">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo;
        <span><?= htmlspecialchars($category['name']) ?></span>
    </nav>

    <div class="category-header animate-fall-in">
        <div class="cat-header-icon"><?= htmlspecialchars($category['icon'] ?? '💬') ?></div>
        <div>
            <h1><?= htmlspecialchars($category['name']) ?></h1>
            <p><?= htmlspecialchars($category['description']) ?></p>
        </div>
    </div>

    <!-- Sort/Filter bar -->
    <div class="thread-filter-bar animate-rise-in stagger-1">
        <a href="?sort=latest" class="filter-btn <?= ($_GET['sort'] ?? 'latest') === 'latest' ? 'active' : '' ?>">🕐 Latest</a>
        <a href="?sort=hot" class="filter-btn <?= ($_GET['sort'] ?? '') === 'hot' ? 'active' : '' ?>">🔥 Hot</a>
        <a href="?sort=views" class="filter-btn <?= ($_GET['sort'] ?? '') === 'views' ? 'active' : '' ?>">👁 Most Viewed</a>
        <span class="filter-count"><?= count($threads) ?> threads</span>
    </div>

    <div class="thread-list">
        <?php if (!empty($threads)): ?>
            <?php foreach ($threads as $index => $thread): ?>
                <div class="thread-card thread-row animate-rise-in" style="animation-delay:<?= $index * 50 ?>ms">
                    <div class="thread-row-main">
                        <?php if ($thread['is_pinned'] ?? false): ?><span class="badge-pinned">📌 Pinned</span><?php endif; ?>
                        <?php if ($thread['is_locked'] ?? false): ?><span class="badge-locked">🔒 Locked</span><?php endif; ?>
                        <a href="/thread/<?= htmlspecialchars($thread['slug']) ?>" class="thread-row-title"><?= htmlspecialchars($thread['title']) ?></a>
                        <div class="thread-row-meta">
                            By <a href="/u/<?= htmlspecialchars($thread['username']) ?>" class="user-link"><?= htmlspecialchars($thread['username']) ?></a>
                            · <?= date('M j, Y', strtotime($thread['created_at'])) ?>
                        </div>
                    </div>
                    <div class="thread-row-stats">
                        <span title="Replies">💬 <?= number_format($thread['reply_count'] ?? 0) ?></span>
                        <span title="Views">👁 <?= number_format($thread['views'] ?? 0) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="thread-card animate-fall-in" style="padding: 60px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 16px;">🌌</div>
                <h2 style="margin-bottom: 8px; font-size: 1.2rem;">No threads yet</h2>
                <p style="color: var(--color-text-muted); margin-bottom: 24px;">Be the first to start a discussion in this category!</p>
                <?php if (\Core\Auth::check()): ?>
                    <a href="/thread/create" class="btn btn-primary magnetic">✍️ Start First Thread</a>
                <?php else: ?>
                    <a href="/register" class="btn btn-primary magnetic">🚀 Join & Post First</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<aside class="sidebar">
    <?php if (\Core\Auth::check()): ?>
        <a href="/thread/create" class="btn btn-primary magnetic sidebar-cta">✍️ New Thread</a>
    <?php else: ?>
        <a href="/login" class="btn btn-primary magnetic sidebar-cta">🔑 Login to Post</a>
    <?php endif; ?>

    <div class="widget thread-card animate-fall-in stagger-1">
        <h3>About this Category</h3>
        <p style="color:var(--color-text-muted); font-size: 0.85rem; margin: 0;"><?= htmlspecialchars($category['description']) ?></p>
    </div>
</aside>

<style>
.breadcrumb { font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 6px; }
.breadcrumb a { color: var(--color-cyan); text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

.category-header { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 32px; padding: 28px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 16px; }
.cat-header-icon { font-size: 3rem; flex-shrink: 0; }
.category-header h1 { font-size: clamp(1.3rem, 3vw, 1.8rem); margin-bottom: 6px; }
.category-header p { color: var(--color-text-muted); margin: 0; font-size: 0.9rem; }

.thread-filter-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-btn { font-size: 0.82rem; padding: 6px 14px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--glass-bg); color: var(--color-text-muted); text-decoration: none; transition: all 180ms var(--spring-smooth); }
.filter-btn:hover, .filter-btn.active { border-color: var(--color-violet); color: var(--color-text); background: rgba(124,58,237,.1); }
.filter-count { margin-left: auto; font-size: 0.78rem; color: var(--color-text-muted); }

.thread-row { display: flex; align-items: center; gap: 20px; padding: 16px 20px; margin-bottom: 10px; }
.thread-row-main { flex: 1; min-width: 0; }
.thread-row-title { display: block; color: var(--color-text); font-weight: 600; text-decoration: none; font-size: 0.95rem; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.thread-row-title:hover { color: var(--color-cyan); }
.thread-row-meta { font-size: 0.78rem; color: var(--color-text-muted); }
.thread-row-meta .user-link { color: var(--color-amber); text-decoration: none; }
.thread-row-stats { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; font-size: 0.78rem; color: var(--color-text-muted); flex-shrink: 0; white-space: nowrap; }
.badge-locked { font-size: 0.7rem; padding: 2px 8px; background: rgba(239,68,68,.12); color: #ef4444; border-radius: 10px; display: inline-block; margin-bottom: 4px; }
.badge-pinned { font-size: 0.7rem; padding: 2px 8px; background: rgba(245,158,11,.12); color: var(--color-amber); border-radius: 10px; display: inline-block; margin-bottom: 4px; }

.sidebar-cta { display: block; width: 100%; text-align: center; box-sizing: border-box; margin-bottom: 20px; }

@media (max-width: 768px) {
    .category-header { flex-direction: column; gap: 12px; padding: 20px; }
    .thread-row { flex-direction: column; align-items: flex-start; gap: 8px; }
    .thread-row-stats { flex-direction: row; gap: 12px; align-items: center; }
    .thread-row-title { white-space: normal; }
}
</style>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
