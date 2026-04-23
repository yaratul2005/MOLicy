<?php
$csrfToken = \Core\Middleware::getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Threads — ACP</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --danger:#ef4444; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        h1 { font-family: 'Syne', sans-serif; color: var(--cyan); margin: 0; }
        .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); cursor: pointer; text-decoration: none; }
        .btn:hover { background: rgba(124,58,237,.2); border-color: var(--violet); }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .toolbar { padding: 16px; border-bottom: 1px solid var(--border); display: flex; gap: 12px; }
        .toolbar input, .toolbar select { padding: 8px 12px; background: rgba(0,0,0,.2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; flex:1; }
        .toolbar button { flex: none; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { color: var(--muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: rgba(0,0,0,.2); }
        tr:hover { background: rgba(255,255,255,.02); }
        .actions { display: flex; gap: 8px; }
        .actions button { background: var(--surface2); border: 1px solid var(--border); color: var(--text); cursor: pointer; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .actions button:hover { border-color: var(--cyan); color: var(--cyan); }
        .actions button.danger:hover { border-color: var(--danger); color: var(--danger); }
        .pagination { padding: 16px; display: flex; justify-content: center; gap: 8px; }
        .pagination a { padding: 6px 12px; border: 1px solid var(--border); color: var(--text); text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Thread Management</h1>
        <a href="/admin" class="btn">← Back to Dashboard</a>
    </div>

    <div class="panel">
        <form class="toolbar" method="GET" action="/admin/content">
            <input type="text" name="q" placeholder="Search threads..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <select name="category_id">
                <option value="">All Categories</option>
                <?php foreach($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($_GET['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Thread</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Stats</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($threads as $t): ?>
                <tr>
                    <td>
                        <?= $t['is_pinned'] ? '📌 ' : '' ?><?= $t['is_locked'] ? '🔒 ' : '' ?>
                        <a href="/thread/<?= $t['slug'] ?>" target="_blank" style="color:var(--text); font-weight:600; text-decoration:none;">
                            <?= htmlspecialchars($t['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($t['category_name']) ?></td>
                    <td><?= htmlspecialchars($t['username']) ?></td>
                    <td style="color:var(--muted); font-size:0.8rem"><?= $t['reply_count'] ?> replies<br><?= $t['views'] ?> views</td>
                    <td style="font-size:0.8rem"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                    <td class="actions">
                        <button onclick="toggleLock(<?= $t['id'] ?>, <?= $t['is_locked'] ? 0 : 1 ?>)"><?= $t['is_locked'] ? 'Unlock' : 'Lock' ?></button>
                        <button onclick="moveThread(<?= $t['id'] ?>)">Move</button>
                        <button class="danger" onclick="deleteThread(<?= $t['id'] ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($threads)): ?>
                <tr><td colspan="6" style="text-align:center; padding: 40px; color:var(--muted);">No threads found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="pagination">
            <a href="?page=<?= max(1, ($page ?? 1) - 1) ?>&q=<?= urlencode($_GET['q'] ?? '') ?>&category_id=<?= urlencode($_GET['category_id'] ?? '') ?>">Previous</a>
            <a href="?page=<?= ($page ?? 1) + 1 ?>&q=<?= urlencode($_GET['q'] ?? '') ?>&category_id=<?= urlencode($_GET['category_id'] ?? '') ?>">Next</a>
        </div>
    </div>

    <script>
    const csrf = '<?= $csrfToken ?>';

    async function toggleLock(id, lockState) {
        const fd = new URLSearchParams({ id, lock: lockState, _csrf_token: csrf });
        await fetch('/admin/content/thread/lock', { method: 'POST', body: fd });
        location.reload();
    }

    async function deleteThread(id) {
        if (!confirm('Are you sure you want to permanently delete this thread and all its replies?')) return;
        const fd = new URLSearchParams({ id, _csrf_token: csrf });
        await fetch('/admin/content/thread/delete', { method: 'POST', body: fd });
        location.reload();
    }

    async function moveThread(id) {
        const catId = prompt('Enter the new Category ID:');
        if (!catId) return;
        const fd = new URLSearchParams({ thread_id: id, category_id: catId, _csrf_token: csrf });
        await fetch('/admin/content/thread/move', { method: 'POST', body: fd });
        location.reload();
    }
    </script>
</body>
</html>
