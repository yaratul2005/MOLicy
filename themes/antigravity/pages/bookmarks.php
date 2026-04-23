<?php
$pageTitle = 'My Bookmarks';
\Core\Middleware::requireAuth();
include ROOT_PATH . '/themes/antigravity/partials/header.php';
?>

<div class="content-left" style="grid-column: span 2;">
    <div class="animate-fall-in" style="margin-bottom: 32px;">
        <h1>🔖 My Bookmarks</h1>
        <p style="color:var(--color-text-muted)">Threads you've saved for later.</p>
    </div>

    <div class="thread-list">
        <?php foreach ($bookmarks as $i => $b): ?>
            <div class="thread-card thread-row animate-rise-in" style="animation-delay:<?= $i * 40 ?>ms">
                <div class="thread-row-main">
                    <a href="/category/<?= htmlspecialchars($b['category_slug'] ?? '') ?>" class="thread-cat-badge"><?= htmlspecialchars($b['category_name']) ?></a>
                    <a href="/thread/<?= htmlspecialchars($b['slug']) ?>" class="thread-row-title"><?= htmlspecialchars($b['title']) ?></a>
                    <div class="thread-row-meta">
                        By <a href="/u/<?= htmlspecialchars($b['username']) ?>" class="user-link"><?= htmlspecialchars($b['username']) ?></a>
                        · Bookmarked <?= date('M j, Y', strtotime($b['bookmarked_at'])) ?>
                    </div>
                </div>
                <div class="thread-row-stats">
                    <span>💬 <?= number_format($b['reply_count'] ?? 0) ?></span>
                    <span>👁 <?= number_format($b['views'] ?? 0) ?></span>
                    <form method="POST" action="/bookmark" style="display:inline">
                        <?= \Core\Middleware::csrfField() ?>
                        <input type="hidden" name="thread_id" value="<?= (int)$b['id'] ?>">
                        <button type="submit" class="post-action-btn" style="color:var(--color-danger)">Remove</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($bookmarks)): ?>
            <div class="thread-card animate-fall-in" style="padding: 60px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 16px;">🔖</div>
                <h2 style="margin-bottom: 8px;">No bookmarks yet</h2>
                <p style="color:var(--color-text-muted); margin-bottom: 24px;">Click the bookmark button on any thread to save it here.</p>
                <a href="/" class="btn btn-primary magnetic">Browse Forum</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.thread-row { display: flex; align-items: center; gap: 20px; padding: 16px 20px; margin-bottom: 10px; }
.thread-row-main { flex: 1; min-width: 0; }
.thread-cat-badge { font-size: 0.7rem; padding: 2px 8px; background: rgba(124,58,237,.15); color: var(--color-violet); border-radius: 10px; text-decoration: none; display: inline-block; margin-bottom: 6px; }
.thread-row-title { display: block; color: var(--color-text); font-weight: 600; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.thread-row-title:hover { color: var(--color-cyan); }
.thread-row-meta { font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px; }
.thread-row-stats { display: flex; gap: 10px; font-size: 0.78rem; color: var(--color-text-muted); flex-shrink: 0; align-items: center; }
.post-action-btn { font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; background: var(--glass-bg); border: 1px solid rgba(239,68,68,.3); color: #ef4444; cursor: pointer; }
</style>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
