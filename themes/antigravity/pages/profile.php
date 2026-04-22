<?php
/**
 * User Profile Page
 * @var array $user   User data
 * @var array $threads Recent threads
 * @var array $posts   Recent posts
 * @var array $badges  User badges
 */
require ROOT_PATH . '/themes/antigravity/partials/header.php';

$isOwn    = \Core\Auth::check() && \Core\Auth::user()['id'] === $user['id'];
$joinDate = date('F Y', strtotime($user['created_at']));
$isOnline = $user['last_seen_at'] && (time() - strtotime($user['last_seen_at'])) < 300;
$rankNames = ['Newcomer', 'Member', 'Regular', 'Trusted', 'Veteran', 'Elder'];
$rankName  = $rankNames[$user['trust_level']] ?? 'Member';
?>

<style>
.profile-hero {
    position: relative;
    padding: 60px 0 40px;
    overflow: hidden;
}
.profile-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 30% 50%, rgba(124,58,237,.18) 0%, transparent 70%),
                radial-gradient(ellipse at 70% 50%, rgba(6,182,212,.12) 0%, transparent 70%);
    pointer-events: none;
}
.profile-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 32px;
    align-items: start;
}
.profile-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 32px;
    text-align: center;
    backdrop-filter: blur(20px);
    position: sticky;
    top: 100px;
    animation: profileCardIn var(--duration-dramatic) var(--spring-bounce) forwards;
    opacity: 0;
    transform: translateY(30px);
}
@keyframes profileCardIn {
    to { opacity:1; transform: translateY(0); }
}
.avatar-wrap {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}
.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--color-violet);
    display: block;
    transition: transform var(--duration-normal) var(--spring-bounce);
}
.profile-avatar:hover { transform: scale(1.08) rotate(3deg); }
.online-ring {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--color-success);
    border: 3px solid var(--color-bg);
    animation: pulse-ring 2s var(--spring-smooth) infinite;
}
@keyframes pulse-ring {
    0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,.6); }
    50%      { box-shadow: 0 0 0 8px rgba(16,185,129,0); }
}
.profile-username {
    font-family: var(--font-hero);
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 4px;
}
.profile-rank {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 20px;
    background: rgba(124,58,237,.2);
    color: var(--color-violet);
    display: inline-block;
    margin-bottom: 12px;
}
.profile-bio {
    color: var(--color-muted);
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 20px;
}
.profile-meta a, .profile-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--color-muted);
    margin: 6px 0;
    text-decoration: none;
}
.profile-meta a:hover { color: var(--color-cyan); }
.profile-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin: 24px 0;
    padding: 16px;
    background: rgba(255,255,255,.03);
    border-radius: 12px;
}
.stat-item { text-align: center; }
.stat-val {
    font-family: var(--font-hero);
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--color-violet);
    display: block;
}
.stat-lbl {
    font-size: 0.75rem;
    color: var(--color-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.badge-shelf {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-top: 16px;
}
.badge-item {
    font-size: 1.4rem;
    title: attr(data-name);
    cursor: default;
    animation: badgePop var(--duration-fast) var(--spring-bounce) backwards;
}
.badge-item:nth-child(1) { animation-delay: 0.05s }
.badge-item:nth-child(2) { animation-delay: 0.1s }
.badge-item:nth-child(3) { animation-delay: 0.15s }
.badge-item:nth-child(4) { animation-delay: 0.2s }
.badge-item:nth-child(5) { animation-delay: 0.25s }
@keyframes badgePop {
    from { opacity:0; transform: scale(0); }
    to   { opacity:1; transform: scale(1); }
}

/* Tabs */
.profile-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--glass-border);
    padding-bottom: 0;
}
.tab-btn {
    padding: 10px 20px;
    border: none;
    background: transparent;
    color: var(--color-muted);
    font-family: var(--font-body);
    font-size: 0.95rem;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: color var(--duration-fast) var(--spring-smooth),
                border-color var(--duration-fast) var(--spring-smooth);
}
.tab-btn.active {
    color: var(--color-violet);
    border-bottom-color: var(--color-violet);
}
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* Thread/post rows */
.activity-row {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    backdrop-filter: blur(10px);
    transition: transform var(--duration-fast) var(--spring-bounce),
                box-shadow var(--duration-fast) var(--spring-smooth);
    animation: rowFallIn var(--duration-normal) var(--spring-bounce) backwards;
}
.activity-row:hover {
    transform: translateX(6px);
    box-shadow: 0 4px 20px rgba(124,58,237,.15);
}
.activity-row:nth-child(1) { animation-delay: 0.05s }
.activity-row:nth-child(2) { animation-delay: 0.1s }
.activity-row:nth-child(3) { animation-delay: 0.15s }
.activity-row:nth-child(4) { animation-delay: 0.2s }
.activity-row:nth-child(5) { animation-delay: 0.25s }
@keyframes rowFallIn {
    from { opacity:0; transform: translateY(-12px); }
    to   { opacity:1; transform: translateY(0); }
}
.activity-title a {
    color: var(--color-text);
    text-decoration: none;
    font-weight: 500;
    font-size: 1rem;
}
.activity-title a:hover { color: var(--color-violet); }
.activity-meta {
    font-size: 0.8rem;
    color: var(--color-muted);
    margin-top: 6px;
}
.activity-excerpt {
    font-size: 0.88rem;
    color: var(--color-muted);
    margin-top: 6px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (max-width: 768px) {
    .profile-layout { grid-template-columns: 1fr; }
    .profile-card { position: static; }
}
</style>

<div class="profile-hero">
    <div class="container">
        <div class="profile-layout">

            <!-- LEFT: Profile Card -->
            <div class="profile-card">
                <div class="avatar-wrap">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" 
                             alt="<?= htmlspecialchars($user['username']) ?>" 
                             class="profile-avatar" loading="lazy">
                    <?php else: ?>
                        <div class="profile-avatar" style="background: linear-gradient(135deg, var(--color-violet), var(--color-cyan)); display:flex; align-items:center; justify-content:center; font-family:var(--font-hero); font-size:2.5rem; color:white;">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($isOnline): ?>
                        <div class="online-ring" title="Online now"></div>
                    <?php endif; ?>
                </div>

                <h1 class="profile-username"><?= htmlspecialchars($user['username']) ?></h1>
                <div class="profile-rank"><?= $rankName ?></div>

                <?php if ($user['bio']): ?>
                    <p class="profile-bio"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                <?php endif; ?>

                <div class="profile-meta">
                    <?php if ($user['location']): ?>
                        <span>📍 <?= htmlspecialchars($user['location']) ?></span>
                    <?php endif; ?>
                    <?php if ($user['website']): ?>
                        <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" rel="noopener">
                            🔗 <?= htmlspecialchars($user['website']) ?>
                        </a>
                    <?php endif; ?>
                    <span>📅 Joined <?= $joinDate ?></span>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-val count-up" data-target="<?= (int)$user['post_count'] ?>">0</span>
                        <span class="stat-lbl">Posts</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-val count-up" data-target="<?= (int)$user['reputation'] ?>">0</span>
                        <span class="stat-lbl">Reputation</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-val"><?= count($threads) ?></span>
                        <span class="stat-lbl">Threads</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-val"><?= $user['trust_level'] ?>/5</span>
                        <span class="stat-lbl">Trust Level</span>
                    </div>
                </div>

                <?php if ($badges): ?>
                    <div class="badge-shelf">
                        <?php foreach ($badges as $b): ?>
                            <span class="badge-item" title="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['icon']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($isOwn): ?>
                    <a href="/profile/edit" class="btn btn-primary" style="display:block; margin-top:20px;">Edit Profile</a>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Activity Tabs -->
            <div class="profile-content">
                <div class="profile-tabs">
                    <button class="tab-btn active" onclick="switchTab('threads', this)">Threads</button>
                    <button class="tab-btn" onclick="switchTab('posts', this)">Replies</button>
                </div>

                <div id="tab-threads" class="tab-panel active">
                    <?php if ($threads): ?>
                        <?php foreach ($threads as $t): ?>
                            <div class="activity-row">
                                <div class="activity-title">
                                    <a href="/thread/<?= htmlspecialchars($t['slug']) ?>">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </a>
                                </div>
                                <div class="activity-meta">
                                    in <a href="/category/<?= htmlspecialchars($t['category_slug']) ?>" style="color:var(--color-violet)">
                                        <?= htmlspecialchars($t['category_name']) ?>
                                    </a>
                                    &bull; <?= (int)$t['reply_count'] ?> replies
                                    &bull; <?= (int)$t['views'] ?> views
                                    &bull; <?= date('M j, Y', strtotime($t['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--color-muted);text-align:center;padding:40px">No threads yet.</p>
                    <?php endif; ?>
                </div>

                <div id="tab-posts" class="tab-panel">
                    <?php if ($posts): ?>
                        <?php foreach ($posts as $p): ?>
                            <div class="activity-row">
                                <div class="activity-title">
                                    <a href="/thread/<?= htmlspecialchars($p['thread_slug']) ?>">
                                        ↩ <?= htmlspecialchars($p['thread_title']) ?>
                                    </a>
                                </div>
                                <div class="activity-excerpt">
                                    <?= htmlspecialchars(mb_substr(strip_tags($p['content']), 0, 200)) ?>...
                                </div>
                                <div class="activity-meta">
                                    <?= date('M j, Y', strtotime($p['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--color-muted);text-align:center;padding:40px">No replies yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}

// Count-up animation for stats
document.querySelectorAll('.count-up').forEach(el => {
    const target = parseInt(el.dataset.target) || 0;
    const duration = 1200;
    const start = performance.now();
    function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(eased * target).toLocaleString();
        if (progress < 1) requestAnimationFrame(tick);
    }
    const obs = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) { requestAnimationFrame(tick); obs.disconnect(); }
    });
    obs.observe(el);
});
</script>

<?php require ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
