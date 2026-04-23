<?php
/**
 * ACP Analytics View
 * @var array $postChart      Daily post counts (30 days)
 * @var array $regChart       Daily registration counts (30 days)
 * @var array $topCategories  Top categories by thread count
 * @var array $topThreads     Top threads by views
 * @var array $topPosters     Top posters (30d)
 * @var array $totals         Summary totals
 */
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analytics — ACP</title>
    <meta name="robots" content="noindex">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --success:#10b981; --amber:#f59e0b; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
        .acp-sidebar { background: var(--surface); border-right: 1px solid var(--border); padding: 28px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; }
        .acp-logo { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; color: var(--violet); padding: 0 24px 28px; border-bottom: 1px solid var(--border); margin-bottom: 12px; }
        .acp-logo span { color: var(--cyan); }
        .acp-nav a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: var(--muted); text-decoration: none; font-size: 0.9rem; border-left: 3px solid transparent; transition: all 200ms; }
        .acp-nav a:hover, .acp-nav a.active { color: var(--text); background: rgba(255,255,255,.03); border-left-color: var(--violet); }
        .acp-nav .nav-section { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); padding: 14px 24px 5px; opacity: 0.5; }
        .acp-sidebar-footer { margin-top: auto; padding: 16px 24px; border-top: 1px solid var(--border); }
        .acp-sidebar-footer a { color: var(--muted); text-decoration: none; font-size: 0.85rem; display: block; margin-bottom: 6px; }

        .acp-main { padding: 36px 40px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 36px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--violet), var(--cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); text-decoration: none; font-size: 0.88rem; }

        .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; }
        .stat-card .val { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--cyan); display: block; }
        .stat-card .lbl { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
        .stat-card .delta { font-size: 0.78rem; color: var(--success); margin-top: 4px; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .panel-header { padding: 16px 20px; border-bottom: 1px solid var(--border); font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; }
        .panel-body { padding: 20px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,.03); text-align: left; }
        th { color: var(--muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; }
        tr:hover td { background: rgba(255,255,255,.02); }
        a { color: var(--cyan); text-decoration: none; }
        a:hover { text-decoration: underline; }

        @media (max-width: 1024px) { body { grid-template-columns: 1fr; } .acp-sidebar { display: none; } .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<aside class="acp-sidebar">
    <div class="acp-logo">Anti<span>Gravity</span> ACP</div>
    <nav class="acp-nav">
        <div class="nav-section">Core</div>
        <a href="/admin">📊 Dashboard</a>
        <a href="/admin/users">👥 Users</a>
        <a href="/admin/content">📝 Content</a>
        <a href="/admin/categories">📁 Categories</a>
        <a href="/admin/moderation">🚩 Moderation</a>
        <div class="nav-section">System</div>
        <a href="/admin/analytics" class="active">📈 Analytics</a>
        <a href="/admin/seo">🔍 SEO</a>
        <a href="/admin/updates">🔄 Updates</a>
        <a href="/admin/settings">⚙️ Settings</a>
    </nav>
    <div class="acp-sidebar-footer">
        <a href="/">← View Forum</a>
        <a href="/logout" style="color:#ef4444">🚪 Logout</a>
    </div>
</aside>

<main class="acp-main">
    <div class="page-header">
        <h1>📈 Analytics</h1>
        <a href="/admin" class="btn">← Dashboard</a>
    </div>

    <!-- Totals -->
    <div class="stat-grid">
        <div class="stat-card"><span class="val"><?= number_format($totals['users']) ?></span><span class="lbl">Total Users</span><span class="delta">+<?= number_format($totals['new_users_week']) ?> this week</span></div>
        <div class="stat-card"><span class="val"><?= number_format($totals['threads']) ?></span><span class="lbl">Threads</span><span class="delta">+<?= number_format($totals['new_threads_week']) ?> this week</span></div>
        <div class="stat-card"><span class="val"><?= number_format($totals['posts']) ?></span><span class="lbl">Posts</span><span class="delta">+<?= number_format($totals['new_posts_week']) ?> this week</span></div>
    </div>

    <!-- Charts -->
    <div class="grid-2">
        <div class="panel">
            <div class="panel-header">📝 Posts per Day (Last 30 Days)</div>
            <div class="panel-body"><canvas id="postChart" height="200"></canvas></div>
        </div>
        <div class="panel">
            <div class="panel-header">👤 Registrations per Day (Last 30 Days)</div>
            <div class="panel-body"><canvas id="regChart" height="200"></canvas></div>
        </div>
    </div>

    <!-- Tables -->
    <div class="grid-2">
        <div class="panel">
            <div class="panel-header">🔥 Top Threads by Views</div>
            <div class="panel-body" style="padding:0">
                <table>
                    <thead><tr><th>Thread</th><th>Views</th><th>Replies</th></tr></thead>
                    <tbody>
                        <?php foreach ($topThreads as $t): ?>
                            <tr>
                                <td><a href="/thread/<?= htmlspecialchars($t['slug']) ?>"><?= htmlspecialchars(mb_substr($t['title'], 0, 40)) ?><?= strlen($t['title']) > 40 ? '…' : '' ?></a></td>
                                <td><?= number_format($t['views']) ?></td>
                                <td><?= number_format($t['reply_count']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">📁 Top Categories</div>
            <div class="panel-body" style="padding:0">
                <table>
                    <thead><tr><th>Category</th><th>Threads</th><th>Total Views</th></tr></thead>
                    <tbody>
                        <?php foreach ($topCategories as $c): ?>
                            <tr>
                                <td><a href="/category/<?= htmlspecialchars($c['slug']) ?>"><?= htmlspecialchars($c['name']) ?></a></td>
                                <td><?= number_format($c['thread_count']) ?></td>
                                <td><?= number_format($c['total_views'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">🏆 Top Posters (Last 30 Days)</div>
        <div class="panel-body" style="padding:0">
            <table>
                <thead><tr><th>#</th><th>User</th><th>Posts (30d)</th><th>Reputation</th></tr></thead>
                <tbody>
                    <?php foreach ($topPosters as $i => $p): ?>
                        <tr>
                            <td style="color:var(--muted)"><?= $i + 1 ?></td>
                            <td><a href="/u/<?= htmlspecialchars($p['username']) ?>"><?= htmlspecialchars($p['username']) ?></a></td>
                            <td><?= number_format($p['post_count_30d']) ?></td>
                            <td style="color:var(--amber)">⭐ <?= number_format($p['reputation']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
const chartDefaults = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#94a3b8', font: { size: 10 } } },
        y: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#94a3b8' }, beginAtZero: true }
    },
    animation: { duration: 900, easing: 'easeOutQuart' }
};

const postData = <?= json_encode($postChart) ?>;
new Chart(document.getElementById('postChart'), {
    type: 'bar',
    data: {
        labels: postData.map(r => r.day.substring(5)),
        datasets: [{ label: 'Posts', data: postData.map(r => r.count), backgroundColor: 'rgba(124,58,237,.6)', borderColor: '#7c3aed', borderRadius: 4 }]
    },
    options: chartDefaults
});

const regData = <?= json_encode($regChart) ?>;
new Chart(document.getElementById('regChart'), {
    type: 'line',
    data: {
        labels: regData.map(r => r.day.substring(5)),
        datasets: [{ label: 'Signups', data: regData.map(r => r.count), borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,.1)', tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#06b6d4' }]
    },
    options: chartDefaults
});
</script>

</body>
</html>
