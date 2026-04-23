<?php 
require ROOT_PATH . '/themes/antigravity/partials/icons.php';
include ROOT_PATH . '/themes/antigravity/partials/header.php'; 
?>

<div class="content-left">
    <!-- Hero Section -->
    <section class="home-hero animate-fall-in">
        <div class="hero-inner">
            <div class="hero-tag">✨ Welcome to the Forum</div>
            <h1>Explore the Universe<br><span class="hero-gradient">of Knowledge</span></h1>
            <p>Join thousands of thinkers sharing ideas, solving problems, and building community together.</p>
            <div class="hero-cta">
                <?php if (\Core\Auth::check()): ?>
                    <a href="/thread/create" class="btn btn-primary magnetic"><?= icon('plus', '', 15) ?> Start a Thread</a>
                    <a href="/search" class="btn magnetic"><?= icon('search', '', 15) ?> Search Topics</a>
                <?php else: ?>
                    <a href="/register" class="btn btn-primary magnetic"><?= icon('users2', '', 15) ?> Join Free</a>
                    <a href="/login" class="btn magnetic"><?= icon('lock', '', 15) ?> Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <div class="stats-bar animate-rise-in stagger-1">
        <?php
            $db = \Core\Database::getInstance();
            $forumStats = $db->fetch("SELECT (SELECT COUNT(*) FROM users) as users, (SELECT COUNT(*) FROM threads) as threads, (SELECT COUNT(*) FROM posts) as posts");
        ?>
        <div class="stat-item"><span class="stat-num"><?= number_format($forumStats['users'] ?? 0) ?></span><span class="stat-lbl">Members</span></div>
        <div class="stat-sep"></div>
        <div class="stat-item"><span class="stat-num"><?= number_format($forumStats['threads'] ?? 0) ?></span><span class="stat-lbl">Threads</span></div>
        <div class="stat-sep"></div>
        <div class="stat-item"><span class="stat-num"><?= number_format($forumStats['posts'] ?? 0) ?></span><span class="stat-lbl">Posts</span></div>
    </div>

    <!-- Categories -->
    <div class="section-heading">
        <h2>Browse Categories</h2>
        <span>Pick a topic and dive in</span>
    </div>

    <div class="category-grid">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $index => $cat): ?>
                <a href="/category/<?= htmlspecialchars($cat['slug']) ?>" class="category-card animate-rise-in magnetic" style="animation-delay:<?= $index * 80 ?>ms">
                    <div class="cat-icon"><?= htmlspecialchars($cat['icon'] ?? '💬') ?></div>
                    <div class="cat-info">
                        <h3><?= htmlspecialchars($cat['name']) ?></h3>
                        <p><?= htmlspecialchars($cat['description']) ?></p>
                    </div>
                    <div class="cat-stats"><?= number_format($cat['thread_count'] ?? 0) ?> threads</div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="thread-card animate-fall-in" style="padding: 40px; text-align:center;">
                <p style="color:var(--color-text-muted); margin-bottom: 16px;">No categories yet. Create one in the admin panel!</p>
                <?php if (\Core\Auth::check() && (\Core\Auth::user()['trust_level'] ?? 0) >= 5): ?>
                    <a href="/admin/categories" class="btn btn-primary">Go to Admin Panel</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Latest Threads -->
    <?php
        $latestThreads = $db->fetchAll(
            "SELECT t.title, t.slug, t.views, t.reply_count, t.created_at, t.last_post_at,
                    u.username, c.name as category_name, c.slug as category_slug
             FROM threads t
             JOIN users u ON t.user_id = u.id
             JOIN categories c ON t.category_id = c.id
             ORDER BY t.last_post_at DESC, t.created_at DESC
             LIMIT 8"
        );
    ?>
    <?php if (!empty($latestThreads)): ?>
        <div class="section-heading" style="margin-top: 48px;">
            <h2>Latest Discussions</h2>
            <span>Most recent activity</span>
        </div>
        <div class="thread-list">
            <?php foreach ($latestThreads as $i => $t): ?>
                <div class="thread-card thread-row animate-rise-in" style="animation-delay:<?= $i * 50 ?>ms">
                    <div class="thread-row-main">
                        <a href="/category/<?= htmlspecialchars($t['category_slug']) ?>" class="thread-cat-badge"><?= htmlspecialchars($t['category_name']) ?></a>
                        <a href="/thread/<?= htmlspecialchars($t['slug']) ?>" class="thread-row-title"><?= htmlspecialchars($t['title']) ?></a>
                        <div class="thread-row-meta">By <a href="/u/<?= htmlspecialchars($t['username']) ?>" class="user-link"><?= htmlspecialchars($t['username']) ?></a> · <?= date('M j', strtotime($t['created_at'])) ?></div>
                    </div>
                    <div class="thread-row-stats">
                        <span><?= icon('replies', '', 14) ?> <?= number_format($t['reply_count'] ?? 0) ?></span>
                        <span><?= icon('views', '', 14) ?> <?= number_format($t['views'] ?? 0) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<aside class="sidebar">
    <?php if (!\Core\Auth::check()): ?>
        <div class="widget thread-card animate-fall-in">
            <h3>Join the Community</h3>
            <p style="color:var(--color-text-muted); font-size:0.9rem; margin-bottom: 16px;">Create an account to join discussions, post replies, and build your reputation.</p>
            <a href="/register" class="btn btn-primary magnetic" style="width:100%; display:block; text-align:center; box-sizing:border-box;">Create Free Account</a>
            <a href="/login" class="btn magnetic" style="width:100%; display:block; text-align:center; box-sizing:border-box; margin-top: 8px;">Already a Member? Sign In</a>
        </div>
    <?php endif; ?>

    <div class="widget thread-card animate-fall-in stagger-1">
        <h3>Quick Links</h3>
        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px;">
            <li><a href="/thread/create" style="color:var(--color-cyan); text-decoration:none; font-size:0.9rem; display:flex; align-items:center; gap:6px;"><?= icon('plus', '', 14) ?> Start a New Thread</a></li>
            <li><a href="/search" style="color:var(--color-cyan); text-decoration:none; font-size:0.9rem; display:flex; align-items:center; gap:6px;"><?= icon('search', '', 14) ?> Search the Forum</a></li>
        </ul>
    </div>
</aside>

<style>
.home-hero { background: radial-gradient(ellipse at top, rgba(124,58,237,.12) 0%, transparent 70%); border: 1px solid var(--color-border); border-radius: 20px; padding: 60px 40px; margin-bottom: 32px; text-align: center; }
.hero-tag { display: inline-block; font-size: 0.8rem; padding: 4px 14px; background: rgba(124,58,237,.15); border: 1px solid rgba(124,58,237,.3); border-radius: 20px; color: var(--color-violet); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.08em; }
.home-hero h1 { font-size: clamp(1.8rem, 4vw, 3rem); line-height: 1.2; margin-bottom: 16px; }
.hero-gradient { background: linear-gradient(135deg, var(--color-violet), var(--color-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.home-hero p { color: var(--color-text-muted); font-size: 1.1rem; margin-bottom: 32px; max-width: 520px; margin-left: auto; margin-right: auto; }
.hero-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

.stats-bar { display: flex; align-items: center; justify-content: center; gap: 0; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 20px 32px; margin-bottom: 40px; }
.stat-item { text-align: center; padding: 0 32px; }
.stat-num { display: block; font-family: var(--font-hero); font-size: 1.8rem; font-weight: 800; color: var(--color-cyan); }
.stat-lbl { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); }
.stat-sep { width: 1px; height: 40px; background: var(--color-border); }

.section-heading { display: flex; align-items: baseline; gap: 14px; margin-bottom: 20px; }
.section-heading h2 { margin: 0; font-size: 1.3rem; }
.section-heading span { color: var(--color-text-muted); font-size: 0.85rem; }

.category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 8px; }
.category-card { display: flex; align-items: flex-start; gap: 16px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 14px; padding: 20px; text-decoration: none; transition: border-color 220ms var(--spring-smooth), transform 220ms var(--spring-bounce), box-shadow 220ms var(--spring-smooth); }
.category-card:hover { border-color: var(--color-violet); transform: translateY(-3px); box-shadow: 0 12px 40px rgba(124,58,237,.15); }
.cat-icon { font-size: 2rem; flex-shrink: 0; }
.cat-info { flex: 1; min-width: 0; }
.cat-info h3 { font-size: 1rem; margin-bottom: 4px; color: var(--color-text); }
.cat-info p { font-size: 0.82rem; color: var(--color-text-muted); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cat-stats { font-size: 0.75rem; color: var(--color-text-muted); white-space: nowrap; flex-shrink: 0; }

.thread-row { display: flex; align-items: center; gap: 20px; padding: 16px 20px; margin-bottom: 10px; }
.thread-row-main { flex: 1; min-width: 0; }
.thread-cat-badge { font-size: 0.7rem; padding: 2px 8px; background: rgba(124,58,237,.15); color: var(--color-violet); border-radius: 10px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.06em; display: inline-block; margin-bottom: 6px; }
.thread-row-title { display: block; color: var(--color-text); font-weight: 600; text-decoration: none; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.thread-row-title:hover { color: var(--color-cyan); }
.thread-row-meta { font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px; }
.thread-row-meta .user-link { color: var(--color-amber); text-decoration: none; }
.thread-row-stats { display: flex; gap: 12px; font-size: 0.78rem; color: var(--color-text-muted); flex-shrink: 0; }

@media (max-width: 768px) {
    .home-hero { padding: 36px 20px; }
    .stats-bar { padding: 16px; gap: 0; }
    .stat-item { padding: 0 16px; }
    .stat-num { font-size: 1.4rem; }
    .category-grid { grid-template-columns: 1fr; }
    .thread-row { flex-direction: column; align-items: flex-start; gap: 10px; }
    .thread-row-stats { width: 100%; }
}
</style>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
