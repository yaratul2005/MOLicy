<?php
/**
 * ACP Dashboard View
 * @var array $stats       Forum statistics
 * @var array $recentPosts Recent post activity
 * @var array $recentUsers Recent registrations
 * @var array $chartData   Daily post chart data (7 days)
 * @var array $health      System health info
 */
$csrfToken = \Core\Middleware::getCSRFToken();
require __DIR__ . '/../../../themes/antigravity/partials/icons.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACP Dashboard — AntiGravity Forum</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg:       #07070d;
            --surface:  #12121f;
            --surface2: #1a1a2e;
            --violet:   #7c3aed;
            --cyan:     #06b6d4;
            --amber:    #f59e0b;
            --success:  #10b981;
            --danger:   #ef4444;
            --text:     #f1f5f9;
            --muted:    #94a3b8;
            --border:   rgba(255,255,255,.06);
            --glass:    rgba(255,255,255,.03);
            --spring-bounce: cubic-bezier(0.34,1.56,0.64,1);
            --spring-smooth: cubic-bezier(0.25,0.46,0.45,0.94);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .acp-sidebar {
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 28px 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .acp-logo {
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--violet);
            padding: 0 24px 28px;
            letter-spacing: -0.02em;
            border-bottom: 1px solid var(--border);
            margin-bottom: 12px;
        }
        .acp-logo span { color: var(--cyan); }
        .acp-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 220ms var(--spring-smooth);
            border-left: 3px solid transparent;
        }
        .acp-nav a:hover, .acp-nav a.active {
            color: var(--text);
            background: var(--glass);
            border-left-color: var(--violet);
        }
        .acp-nav .nav-section {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
            padding: 16px 24px 6px;
            opacity: 0.6;
        }
        .acp-sidebar-footer {
            margin-top: auto;
            padding: 16px 24px;
            border-top: 1px solid var(--border);
        }
        .acp-sidebar-footer a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 200ms;
        }
        .acp-sidebar-footer a:hover { color: var(--danger); }

        /* ── Main ── */
        .acp-main {
            padding: 36px 40px;
            overflow-y: auto;
        }
        .acp-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 36px;
        }
        .acp-topbar h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--violet), var(--cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ── Stat Cards ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 36px;
        }
        .stat-card {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            animation: cardRise 0.5s var(--spring-bounce) backwards;
        }
        .stat-card:nth-child(1) { animation-delay: 0.05s }
        .stat-card:nth-child(2) { animation-delay: 0.10s }
        .stat-card:nth-child(3) { animation-delay: 0.15s }
        .stat-card:nth-child(4) { animation-delay: 0.20s }
        .stat-card:nth-child(5) { animation-delay: 0.25s }
        .stat-card:nth-child(6) { animation-delay: 0.30s }
        @keyframes cardRise {
            from { opacity:0; transform: translateY(20px) scale(0.96); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 80px; height: 80px;
            border-radius: 50%;
            background: var(--card-glow, rgba(124,58,237,.15));
            filter: blur(20px);
        }
        .stat-icon { font-size: 1.8rem; margin-bottom: 12px; }
        .stat-val {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            display: block;
        }
        .stat-lbl {
            font-size: 0.78rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* ── Grid 2-col ── */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        @media (max-width: 1100px) { .grid-2 { grid-template-columns: 1fr; } }

        /* ── Panel ── */
        .panel {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
        }
        .panel-body { padding: 20px 24px; }

        /* ── Activity row ── */
        .activity-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            animation: fadeSlide 0.4s var(--spring-smooth) backwards;
        }
        .activity-item:last-child { border-bottom: none; }
        @keyframes fadeSlide {
            from { opacity:0; transform: translateX(-10px); }
            to   { opacity:1; transform: translateX(0); }
        }
        .activity-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--violet), var(--cyan));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem;
            flex-shrink: 0;
        }
        .activity-info { flex: 1; min-width: 0; }
        .activity-info strong { color: var(--cyan); font-size: 0.9rem; }
        .activity-info p {
            font-size: 0.82rem;
            color: var(--muted);
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .activity-time { font-size: 0.75rem; color: var(--muted); flex-shrink: 0; }

        /* ── Quick actions ── */
        .quick-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .qa-btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--glass);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 220ms var(--spring-bounce);
        }
        .qa-btn:hover {
            background: rgba(124,58,237,.15);
            border-color: var(--violet);
            transform: translateY(-2px);
        }
        .qa-btn.danger:hover {
            background: rgba(239,68,68,.15);
            border-color: var(--danger);
        }

        /* ── Health grid ── */
        .health-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }
        .health-item {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
        }
        .health-item dt { font-size: 0.75rem; color: var(--muted); margin-bottom: 4px; }
        .health-item dd { font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; color: var(--success); }

        /* ── User table ── */
        .mini-table { width: 100%; border-collapse: collapse; }
        .mini-table th {
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            padding: 8px 12px;
            border-bottom: 1px solid var(--border);
        }
        .mini-table td {
            padding: 10px 12px;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(255,255,255,.03);
        }
        .mini-table tr:last-child td { border-bottom: none; }
        .trust-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            background: rgba(124,58,237,.2);
            color: var(--violet);
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="acp-sidebar">
    <div class="acp-logo">Anti<span>Gravity</span> ACP</div>
    <nav class="acp-nav">
        <div class="nav-section">Core</div>
        <a href="/admin" class="active"><?= icon('chart') ?> Dashboard</a>
        <a href="/admin/users"><?= icon('users') ?> Users</a>
        <a href="/admin/content"><?= icon('content') ?> Content</a>
        <a href="/admin/categories"><?= icon('categories') ?> Categories</a>
        <a href="/admin/moderation"><?= icon('shield') ?> Moderation</a>

        <div class="nav-section">System</div>
        <a href="/admin/seo"><?= icon('search') ?> SEO Manager</a>
        <a href="/admin/theme"><?= icon('paintbrush') ?> Theme Engine</a>
        <a href="/admin/plugins"><?= icon('plugin') ?> Plugins</a>
        <a href="/admin/updates"><?= icon('updates') ?> Updates</a>
        <a href="/admin/analytics"><?= icon('analytics') ?> Analytics</a>
        <a href="/admin/settings"><?= icon('settings') ?> Settings</a>
    </nav>
    <div class="acp-sidebar-footer">
        <a href="/">&larr; View Forum</a><br><br>
        <a href="/logout"><?= icon('logout') ?> Logout</a>
    </div>
</aside>

<!-- Main -->
<main class="acp-main">
    <div class="acp-topbar">
        <h1>Command Center</h1>
        <div class="quick-actions">
            <button class="qa-btn" onclick="quickAction('clear_cache')"><?= icon('trash', '', 15) ?> Clear Cache</button>
            <a href="/admin/content/moderation" class="qa-btn"><?= icon('flag', '', 15) ?> Review Queue</a>
            <a href="/thread/create" class="qa-btn"><?= icon('plus', '', 15) ?> New Thread</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stat-grid">
        <div class="stat-card" style="--card-glow: rgba(124,58,237,.2)">
            <div class="stat-icon"><?= icon('users', 'opacity:.9', 28) ?></div>
            <span class="stat-val count-up" data-target="<?= (int)$stats['totalUsers'] ?>">0</span>
            <span class="stat-lbl">Total Users</span>
        </div>
        <div class="stat-card" style="--card-glow: rgba(6,182,212,.2)">
            <div class="stat-icon"><?= icon('threads', 'opacity:.9', 28) ?></div>
            <span class="stat-val count-up" data-target="<?= (int)$stats['totalThreads'] ?>">0</span>
            <span class="stat-lbl">Threads</span>
        </div>
        <div class="stat-card" style="--card-glow: rgba(16,185,129,.2)">
            <div class="stat-icon"><?= icon('content', 'opacity:.9', 28) ?></div>
            <span class="stat-val count-up" data-target="<?= (int)$stats['totalPosts'] ?>">0</span>
            <span class="stat-lbl">Posts</span>
        </div>
        <div class="stat-card" style="--card-glow: rgba(245,158,11,.2)">
            <div class="stat-icon">🟢</div>
            <span class="stat-val count-up" data-target="<?= (int)$stats['activeToday'] ?>">0</span>
            <span class="stat-lbl">Active Today</span>
        </div>
        <div class="stat-card" style="--card-glow: rgba(239,68,68,.2)">
            <div class="stat-icon">⚡</div>
            <span class="stat-val count-up" data-target="<?= (int)$stats['postsHour'] ?>">0</span>
            <span class="stat-lbl">Posts/Hour</span>
        </div>
        <div class="stat-card" style="--card-glow: rgba(124,58,237,.15)">
            <div class="stat-icon">🆕</div>
            <span class="stat-val count-up" data-target="<?= (int)$stats['newToday'] ?>">0</span>
            <span class="stat-lbl">New Today</span>
        </div>
    </div>

    <!-- Chart + Activity -->
    <div class="grid-2">
        <!-- Activity Chart -->
        <div class="panel">
            <div class="panel-header">📈 Posts (Last 7 Days)</div>
            <div class="panel-body">
                <canvas id="activityChart" height="200"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="panel">
            <div class="panel-header">
                🕐 Recent Posts
                <a href="/admin/content/threads" style="font-size:0.8rem; color:var(--violet); text-decoration:none">View all →</a>
            </div>
            <div class="panel-body" style="padding: 0 24px;">
                <?php foreach (array_slice($recentPosts, 0, 6) as $i => $post): ?>
                    <div class="activity-item" style="animation-delay: <?= $i * 0.05 ?>s">
                        <div class="activity-avatar">
                            <?= strtoupper(substr($post['username'], 0, 1)) ?>
                        </div>
                        <div class="activity-info">
                            <strong><?= htmlspecialchars($post['username']) ?></strong>
                            <p><?= htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 60)) ?>...</p>
                            <p>in <em><?= htmlspecialchars($post['thread_title']) ?></em></p>
                        </div>
                        <div class="activity-time"><?= date('H:i', strtotime($post['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Users + Health -->
    <div class="grid-2">
        <!-- Recent Users -->
        <div class="panel">
            <div class="panel-header">
                🆕 Recent Registrations
                <a href="/admin/users" style="font-size:0.8rem; color:var(--violet); text-decoration:none">Manage →</a>
            </div>
            <div class="panel-body" style="padding: 0;">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Trust</th>
                            <th>Rep</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>
                                    <a href="/u/<?= htmlspecialchars($u['username']) ?>" style="color:var(--cyan); text-decoration:none">
                                        <?= htmlspecialchars($u['username']) ?>
                                    </a>
                                </td>
                                <td><span class="trust-badge">L<?= (int)$u['trust_level'] ?></span></td>
                                <td><?= number_format((int)$u['reputation']) ?></td>
                                <td style="color:var(--muted); font-size:0.78rem"><?= date('M j', strtotime($u['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Health -->
        <div class="panel">
            <div class="panel-header">🩺 System Health</div>
            <div class="panel-body">
                <dl class="health-grid">
                    <div class="health-item"><dt>PHP Version</dt><dd><?= htmlspecialchars($health['php_version']) ?></dd></div>
                    <div class="health-item"><dt>Database Size</dt><dd><?= htmlspecialchars($health['db_size']) ?></dd></div>
                    <div class="health-item"><dt>Cache Driver</dt><dd><?= htmlspecialchars($health['cache_driver']) ?></dd></div>
                    <div class="health-item"><dt>Memory Limit</dt><dd><?= htmlspecialchars($health['php_memory']) ?></dd></div>
                    <div class="health-item"><dt>Upload Max</dt><dd><?= htmlspecialchars($health['upload_max']) ?></dd></div>
                    <div class="health-item"><dt>AGF Version</dt>
                        <dd><?= htmlspecialchars(json_decode(file_get_contents(ROOT_PATH.'/updates/manifest.json'), true)['version'] ?? '1.0.0') ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</main>

<script>
// Count-up animations
document.querySelectorAll('.count-up').forEach(el => {
    const target = parseInt(el.dataset.target) || 0;
    const dur = 1000;
    const start = performance.now();
    const tick = now => {
        const p = Math.min((now - start) / dur, 1);
        const e = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(e * target).toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
});

// Activity chart
const chartData = <?= json_encode($chartData) ?>;
const labels = chartData.map(r => r.day);
const data   = chartData.map(r => r.posts);

new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Posts',
            data,
            borderColor: '#7c3aed',
            backgroundColor: 'rgba(124,58,237,.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#7c3aed',
            pointRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#94a3b8' } },
            y: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#94a3b8' }, beginAtZero: true },
        },
        animation: { duration: 900, easing: 'easeOutQuart' }
    }
});

// Quick actions
async function quickAction(action) {
    const res = await fetch('/admin/quick-action', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}&_csrf_token=<?= $csrfToken ?>`
    });
    const data = await res.json();
    if (data.success) {
        showToast(data.message || 'Done!', 'success');
    } else {
        showToast(data.error || 'Error', 'error');
    }
}

function showToast(msg, type = 'info') {
    const colors = { info: '#06b6d4', success: '#10b981', error: '#ef4444' };
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;background:var(--surface2);border:1px solid ${colors[type]};color:#f1f5f9;padding:14px 20px;border-radius:12px;font-size:.9rem;box-shadow:0 8px 32px rgba(0,0,0,.4);`;
    t.animate([{transform:'translateY(20px)',opacity:0},{transform:'translateY(0)',opacity:1}],{duration:350,easing:'cubic-bezier(.34,1.56,.64,1)',fill:'forwards'});
    document.body.appendChild(t);
    setTimeout(() => { t.animate([{opacity:1},{opacity:0}],{duration:300,fill:'forwards'}).onfinish = ()=>t.remove(); }, 3500);
}
</script>
</body>
</html>
