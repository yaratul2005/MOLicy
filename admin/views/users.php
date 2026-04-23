<?php
$csrfToken = \Core\Middleware::getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Users — ACP</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Base styles inherited from dashboard */
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --danger:#ef4444; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        h1 { font-family: 'Syne', sans-serif; color: var(--cyan); margin: 0; }
        .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); cursor: pointer; text-decoration: none; }
        .btn:hover { background: rgba(124,58,237,.2); border-color: var(--violet); }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        .toolbar { padding: 16px; border-bottom: 1px solid var(--border); display: flex; gap: 12px; }
        .toolbar input, .toolbar select { padding: 8px 12px; background: rgba(0,0,0,.2); border: 1px solid var(--border); color: var(--text); border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        th { color: var(--muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; background: rgba(0,0,0,.2); }
        tr:hover { background: rgba(255,255,255,.02); }
        select.inline-edit { background: transparent; border: none; color: var(--cyan); cursor: pointer; }
        select.inline-edit:focus { outline: none; border-bottom: 1px solid var(--cyan); }
        .actions { display: flex; gap: 8px; }
        .actions button { background: transparent; border: none; color: var(--muted); cursor: pointer; padding: 4px; border-radius: 4px; }
        .actions button:hover { color: var(--danger); background: rgba(239,68,68,.1); }
        .pagination { padding: 16px; display: flex; justify-content: center; gap: 8px; }
        .pagination a { padding: 6px 12px; border: 1px solid var(--border); color: var(--text); text-decoration: none; border-radius: 6px; }
        .pagination a.active { background: var(--violet); border-color: var(--violet); }
    </style>
</head>
<body>
    <div class="header">
        <h1>User Management</h1>
        <div>
            <a href="/admin" class="btn">← Back to Dashboard</a>
            <a href="/admin/users/export" class="btn" style="background: rgba(16,185,129,.1); border-color: #10b981; color: #10b981;">📥 Export CSV</a>
        </div>
    </div>

    <div class="panel">
        <form class="toolbar" method="GET" action="/admin/users">
            <input type="text" name="q" placeholder="Search username/email..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <select name="role">
                <option value="">All Roles</option>
                <option value="admin" <?= ($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admins</option>
                <option value="moderator" <?= ($_GET['role'] ?? '') === 'moderator' ? 'selected' : '' ?>>Moderators</option>
                <option value="member" <?= ($_GET['role'] ?? '') === 'member' ? 'selected' : '' ?>>Members</option>
            </select>
            <button type="submit" class="btn">Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Trust Lvl</th>
                    <th>Reputation</th>
                    <th>Posts</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="color:var(--muted)">#<?= $u['id'] ?></td>
                    <td>
                        <a href="/u/<?= htmlspecialchars($u['username']) ?>" style="color:var(--text); font-weight:600; text-decoration:none;">
                            <?= htmlspecialchars($u['username']) ?>
                        </a><br>
                        <span style="font-size:0.75rem; color:var(--muted)"><?= htmlspecialchars($u['email']) ?></span>
                    </td>
                    <td>
                        <select class="inline-edit" onchange="updateUser(<?= $u['id'] ?>, 'role', this.value)">
                            <option value="member" <?= $u['role']==='member'?'selected':'' ?>>Member</option>
                            <option value="moderator" <?= $u['role']==='moderator'?'selected':'' ?>>Moderator</option>
                            <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                            <option value="banned" <?= $u['role']==='banned'?'selected':'' ?>>Banned</option>
                        </select>
                    </td>
                    <td>
                        <select class="inline-edit" onchange="updateUser(<?= $u['id'] ?>, 'trust_level', this.value)">
                            <?php for($i=0; $i<=5; $i++): ?>
                                <option value="<?= $i ?>" <?= (int)$u['trust_level']===$i?'selected':'' ?>>Level <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </td>
                    <td><?= $u['reputation'] ?></td>
                    <td><?= $u['post_count'] ?></td>
                    <td style="font-size:0.8rem"><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                    <td class="actions">
                        <button onclick="banUser(<?= $u['id'] ?>)" title="Ban User">🔨</button>
                        <button onclick="deleteUser(<?= $u['id'] ?>)" title="Delete Permanently">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" style="text-align:center; padding: 40px; color:var(--muted);">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&q=<?= urlencode($_GET['q'] ?? '') ?>&role=<?= urlencode($_GET['role'] ?? '') ?>" class="<?= $page === $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    const csrf = '<?= $csrfToken ?>';

    async function updateUser(id, field, value) {
        const fd = new URLSearchParams();
        fd.append('user_id', id); fd.append('field', field); fd.append('value', value); fd.append('_csrf_token', csrf);
        await fetch('/admin/users/update', { method: 'POST', body: fd });
    }

    async function banUser(id) {
        if (!confirm('Ban this user?')) return;
        const fd = new URLSearchParams();
        fd.append('user_id', id); fd.append('ban_type', 'permanent'); fd.append('_csrf_token', csrf);
        const res = await fetch('/admin/users/ban', { method: 'POST', body: fd });
        if ((await res.json()).success) location.reload();
    }

    async function deleteUser(id) {
        if (!confirm('WARNING: Permanently delete this user? This cannot be undone.')) return;
        const fd = new URLSearchParams();
        fd.append('user_id', id); fd.append('_csrf_token', csrf);
        const res = await fetch('/admin/users/delete', { method: 'POST', body: fd });
        if ((await res.json()).success) location.reload();
        else alert('Error deleting user.');
    }
    </script>
</body>
</html>
