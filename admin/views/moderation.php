<?php
$csrfToken = \Core\Middleware::getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Moderation — ACP</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --danger:#ef4444; --success:#10b981; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        h1 { font-family: 'Syne', sans-serif; color: var(--danger); margin: 0; }
        .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); cursor: pointer; text-decoration: none; }
        .btn:hover { background: rgba(124,58,237,.2); border-color: var(--violet); }
        .queue-list { display: flex; flex-direction: column; gap: 16px; }
        .flag-item { background: var(--surface); border: 1px solid var(--danger); border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(239,68,68,0.1); }
        .flag-header { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem; color: var(--muted); }
        .flag-content { padding: 16px; background: rgba(0,0,0,0.2); border-radius: 8px; font-family: monospace; font-size: 0.9rem; margin-bottom: 16px; white-space: pre-wrap; word-break: break-all; }
        .flag-actions { display: flex; gap: 12px; }
        .btn-approve { border-color: var(--success); color: var(--success); }
        .btn-approve:hover { background: rgba(16,185,129,0.1); }
        .btn-delete { border-color: var(--danger); color: var(--danger); }
        .btn-delete:hover { background: rgba(239,68,68,0.1); }
    </style>
</head>
<body>
    <div class="header">
        <h1>Moderation Queue</h1>
        <a href="/admin" class="btn">← Back to Dashboard</a>
    </div>

    <div class="queue-list">
        <?php foreach ($flags as $f): ?>
            <div class="flag-item">
                <div class="flag-header">
                    <span>Reported Post in <strong><a href="/thread/<?= $f['thread_slug'] ?>" target="_blank" style="color:var(--cyan)"><?= htmlspecialchars($f['thread_title']) ?></a></strong></span>
                    <span>Posted by <strong><?= htmlspecialchars($f['username']) ?></strong> on <?= $f['created_at'] ?></span>
                </div>
                <div class="flag-content"><?= htmlspecialchars(strip_tags($f['content'])) ?></div>
                <div class="flag-actions">
                    <button class="btn btn-approve" onclick="approve(<?= $f['id'] ?>)">✅ Approve (Ignore Flag)</button>
                    <button class="btn btn-delete" onclick="removePost(<?= $f['id'] ?>)">🗑️ Delete Post</button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($flags)): ?>
            <div style="text-align:center; padding: 60px; color:var(--success); font-size: 1.2rem; background: var(--surface); border-radius: 12px; border: 1px solid var(--border);">
                🎉 The queue is empty! Great job.
            </div>
        <?php endif; ?>
    </div>

    <script>
    const csrf = '<?= $csrfToken ?>';

    async function approve(id) {
        const fd = new URLSearchParams({ post_id: id, _csrf_token: csrf });
        await fetch('/admin/moderation/approve', { method: 'POST', body: fd });
        location.reload();
    }

    async function removePost(id) {
        if (!confirm('Permanently delete this reported post?')) return;
        const fd = new URLSearchParams({ post_id: id, _csrf_token: csrf });
        await fetch('/admin/moderation/delete', { method: 'POST', body: fd });
        location.reload();
    }
    </script>
</body>
</html>
