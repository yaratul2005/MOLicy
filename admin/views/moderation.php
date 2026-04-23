<?php
$pageTitle = 'Moderation Queue';
$activeNav = 'moderation';
$csrfToken = \Core\Middleware::getCSRFToken();
require __DIR__ . '/partials/layout.php';
?>

<div class="acp-topbar">
    <h1><?= icon('flag','',22) ?> Moderation Queue</h1>
    <div class="acp-topbar-actions">
        <?php if (!empty($flags)): ?>
        <button class="btn btn-danger btn-sm" onclick="approveAll()">
            <?= icon('check','',14) ?> Clear All Flags
        </button>
        <?php endif; ?>
        <a href="/admin/content" class="btn btn-sm"><?= icon('content','',14) ?> Thread Manager</a>
    </div>
</div>

<?php if (empty($flags)): ?>
<div class="panel" style="text-align:center;padding:60px 40px">
    <div style="width:64px;height:64px;background:rgba(16,185,129,.12);border:2px solid rgba(16,185,129,.3);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
        <?= icon('check','color:#10b981',28) ?>
    </div>
    <h2 style="font-family:'Syne',sans-serif;color:var(--success);margin:0 0 8px">Queue is Clear!</h2>
    <p class="text-muted">No flagged posts to review. The community is behaving.</p>
</div>

<?php else: ?>

<div style="display:flex;flex-direction:column;gap:16px" id="flag-list">
<?php foreach ($flags as $f): ?>
<div class="panel" id="flag-<?= $f['id'] ?>" style="border-color:rgba(239,68,68,.25)">
    <div class="panel-header">
        <div style="display:flex;flex-direction:column;gap:4px">
            <div style="display:flex;align-items:center;gap:10px">
                <?= icon('flag','color:var(--danger)',14) ?>
                <span>Flagged post in <a href="/thread/<?= htmlspecialchars($f['thread_slug']) ?>#post-<?= $f['id'] ?>" target="_blank" style="color:var(--cyan);font-weight:600"><?= htmlspecialchars($f['thread_title']) ?></a></span>
            </div>
            <div class="text-muted" style="font-size:0.78rem">
                By <strong style="color:var(--text)"><?= htmlspecialchars($f['username']) ?></strong>
                &nbsp;·&nbsp; <?= date('M j, Y g:i A', strtotime($f['created_at'])) ?>
                <?php if (!empty($f['flag_reason'])): ?>
                &nbsp;·&nbsp; Reason: <span style="color:var(--amber)"><?= htmlspecialchars($f['flag_reason']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <button class="btn btn-success btn-sm" onclick="approvePost(<?= $f['id'] ?>)">
                <?= icon('check','',13) ?> Keep Post
            </button>
            <button class="btn btn-danger btn-sm" onclick="deletePost(<?= $f['id'] ?>)">
                <?= icon('trash','',13) ?> Delete Post
            </button>
            <a href="/admin/users?q=<?= urlencode($f['username']) ?>" class="btn btn-sm" title="View user profile">
                <?= icon('users','',13) ?> User
            </a>
        </div>
    </div>
    <div class="panel-body" style="padding:16px 20px">
        <div style="background:rgba(0,0,0,.25);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:0.88rem;line-height:1.7;white-space:pre-wrap;word-break:break-word;max-height:220px;overflow-y:auto">
            <?= htmlspecialchars(strip_tags($f['content'])) ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<script>
const csrf = '<?= htmlspecialchars($csrfToken) ?>';

async function approvePost(id) {
    const fd = new URLSearchParams({ post_id: id, _csrf_token: csrf });
    const r  = await fetch('/admin/moderation/approve', { method: 'POST', body: fd });
    if ((await r.json()).success) {
        document.getElementById('flag-' + id)?.remove();
        if (!document.querySelector('[id^="flag-"]')) location.reload();
    }
}

async function deletePost(id) {
    if (!confirm('Permanently delete this post?')) return;
    const fd = new URLSearchParams({ post_id: id, _csrf_token: csrf });
    const r  = await fetch('/admin/moderation/delete', { method: 'POST', body: fd });
    if ((await r.json()).success) {
        document.getElementById('flag-' + id)?.remove();
        if (!document.querySelector('[id^="flag-"]')) location.reload();
    }
}

async function approveAll() {
    if (!confirm('Clear all flags (keep posts)?')) return;
    const items = document.querySelectorAll('[id^="flag-"]');
    for (const el of items) {
        const id = el.id.replace('flag-', '');
        const fd = new URLSearchParams({ post_id: id, _csrf_token: csrf });
        await fetch('/admin/moderation/approve', { method: 'POST', body: fd });
    }
    location.reload();
}
</script>

</main>
</body>
</html>
