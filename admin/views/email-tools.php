<?php
$pageTitle = 'Email Tools';
$activeNav = 'email-tools';
require __DIR__ . '/partials/layout.php';
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success"><?= icon('check', '', 15) ?> Settings saved successfully.</div>
<?php endif; ?>

<div class="acp-topbar">
    <h1><?= icon('email', '', 22) ?> Email Tools</h1>
    <div class="acp-topbar-actions">
        <span class="badge <?= $smtpConfigured ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.8rem;padding:5px 12px">
            <?= $smtpConfigured ? icon('check','',12).' SMTP Configured' : icon('flag','',12).' SMTP Not Set' ?>
        </span>
        <a href="/admin/settings#tab-email" class="btn btn-sm"><?= icon('settings','',14) ?> SMTP Settings</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

    <!-- ── Test Email ── -->
    <div class="panel">
        <div class="panel-header">
            <h2><?= icon('email','',16) ?> Send Test Email</h2>
        </div>
        <div class="panel-body">
            <p class="text-muted" style="margin-bottom:16px;font-size:0.85rem">
                Send a test email to verify your SMTP configuration is working correctly.
            </p>
            <div class="form-group">
                <label>Recipient Email</label>
                <input type="email" id="test-email-addr" class="form-control" placeholder="admin@yourdomain.com">
            </div>
            <button class="btn btn-primary" onclick="sendTestEmail()">
                <?= icon('email','',15) ?> Send Test Email
            </button>
            <div id="test-result" style="margin-top:12px;font-size:0.85rem"></div>
        </div>
    </div>

    <!-- ── Broadcast ── -->
    <div class="panel">
        <div class="panel-header">
            <h2><?= icon('announce','',16) ?> Broadcast Email</h2>
        </div>
        <div class="panel-body">
            <p class="text-muted" style="margin-bottom:16px;font-size:0.85rem">
                Send a custom email to users. Use <code style="color:var(--cyan)">{username}</code> as a placeholder.
            </p>
            <div class="form-group">
                <label>Send To</label>
                <select id="bc-filter" class="form-control">
                    <option value="all">All Users</option>
                    <option value="verified">Verified Users Only</option>
                    <option value="unverified">Unverified Users Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" id="bc-subject" class="form-control" placeholder="Announcement from the team">
            </div>
            <div class="form-group">
                <label>Message Body</label>
                <textarea id="bc-body" class="form-control" rows="4" placeholder="Hi {username}, ..."></textarea>
            </div>
            <button class="btn btn-primary" onclick="sendBroadcast()">
                <?= icon('announce','',15) ?> Send Broadcast
            </button>
            <div id="broadcast-result" style="margin-top:12px;font-size:0.85rem"></div>
        </div>
    </div>
</div>

<!-- ── Unverified Users ── -->
<div class="panel" style="margin-top:24px">
    <div class="panel-header">
        <h2><?= icon('users','',16) ?> Unverified Accounts (<?= count($unverified) ?>)</h2>
        <div style="display:flex;gap:8px">
            <button class="btn btn-sm btn-primary" onclick="bulkResend()">
                <?= icon('email','',13) ?> Resend All Verifications
            </button>
        </div>
    </div>
    <?php if (empty($unverified)): ?>
        <div class="panel-body" style="text-align:center;padding:40px;color:var(--success)">
            <?= icon('check','',20) ?> All registered users are verified!
        </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="unverified-table">
        <?php foreach ($unverified as $u): ?>
            <tr id="urow-<?= $u['id'] ?>">
                <td class="text-muted mono">#<?= $u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                <td class="mono" style="font-size:0.82rem"><?= htmlspecialchars($u['email']) ?></td>
                <td class="text-muted" style="font-size:0.8rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <button class="btn btn-xs" onclick="resendVerify(<?= $u['id'] ?>)">
                            <?= icon('email','',12) ?> Resend
                        </button>
                        <button class="btn btn-xs btn-success" onclick="manualVerify(<?= $u['id'] ?>)">
                            <?= icon('check','',12) ?> Approve
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
const csrf = '<?= htmlspecialchars(\Core\Middleware::getCSRFToken()) ?>';

async function sendTestEmail() {
    const to  = document.getElementById('test-email-addr').value.trim();
    const res = document.getElementById('test-result');
    res.textContent = 'Sending…';
    const fd = new URLSearchParams({ to, _csrf_token: csrf });
    const r  = await fetch('/admin/email-tools/test', { method: 'POST', body: fd });
    const j  = await r.json();
    if (j.success) {
        res.innerHTML = '<span style="color:var(--success)">✓ ' + j.message + '</span>';
    } else {
        res.innerHTML = '<span style="color:var(--danger)">✗ ' + (j.message || j.error || 'Failed') + '</span>';
    }
}

async function resendVerify(id) {
    const btn = event.target.closest('button');
    btn.disabled = true; btn.textContent = '…';
    const fd = new URLSearchParams({ user_id: id, _csrf_token: csrf });
    const r  = await fetch('/admin/email-tools/resend', { method: 'POST', body: fd });
    const j  = await r.json();
    btn.disabled = false;
    btn.innerHTML = j.success ? '✓ Sent' : '✗ Failed';
    btn.style.color = j.success ? 'var(--success)' : 'var(--danger)';
}

async function manualVerify(id) {
    if (!confirm('Mark this user as verified without sending an email?')) return;
    const fd = new URLSearchParams({ user_id: id, _csrf_token: csrf });
    const r  = await fetch('/admin/email-tools/verify', { method: 'POST', body: fd });
    const j  = await r.json();
    if (j.success) {
        const row = document.getElementById('urow-' + id);
        if (row) row.remove();
    }
}

async function bulkResend() {
    if (!confirm('Resend verification emails to all unverified users? (Max 50 at a time)')) return;
    const btn = event.target.closest('button');
    btn.disabled = true; btn.textContent = 'Sending…';
    const fd = new URLSearchParams({ _csrf_token: csrf });
    const r  = await fetch('/admin/email-tools/bulk-resend', { method: 'POST', body: fd });
    const j  = await r.json();
    btn.disabled = false;
    btn.textContent = j.success ? `✓ Sent: ${j.sent}, Failed: ${j.failed}` : '✗ Error';
}

async function sendBroadcast() {
    const subject = document.getElementById('bc-subject').value.trim();
    const body    = document.getElementById('bc-body').value.trim();
    const filter  = document.getElementById('bc-filter').value;
    const result  = document.getElementById('broadcast-result');

    if (!subject || !body) { result.innerHTML = '<span style="color:var(--danger)">Subject and body are required.</span>'; return; }
    if (!confirm(`Send broadcast to "${filter}" users?`)) return;

    result.textContent = 'Sending…';
    const fd = new URLSearchParams({ subject, body, filter, _csrf_token: csrf });
    const r  = await fetch('/admin/email-tools/broadcast', { method: 'POST', body: fd });
    const j  = await r.json();
    result.innerHTML = j.success
        ? `<span style="color:var(--success)">✓ Sent: ${j.sent} emails (${j.failed} failed)</span>`
        : `<span style="color:var(--danger)">✗ ${j.error || 'Failed'}</span>`;
}
</script>

</main>
</body>
</html>
