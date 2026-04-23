<?php
$pageTitle = 'User Management';
$activeNav = 'users';
$csrfToken = \Core\Middleware::getCSRFToken();
require __DIR__ . '/partials/layout.php';
?>

<div class="acp-topbar">
    <h1><?= icon('users','',22) ?> User Management</h1>
    <div class="acp-topbar-actions">
        <a href="/admin/users/export" class="btn btn-sm btn-success"><?= icon('upload','',14) ?> Export CSV</a>
        <a href="/admin/email-tools" class="btn btn-sm"><?= icon('email','',14) ?> Email Tools</a>
    </div>
</div>

<!-- Alerts -->
<?php if (isset($_GET['done'])): ?>
<div class="alert alert-success"><?= icon('check','',15) ?> Action completed.</div>
<?php endif; ?>

<!-- Toolbar -->
<div class="panel" style="margin-bottom:20px">
    <div class="panel-body" style="padding:14px 20px">
        <form method="GET" action="/admin/users" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" name="q" class="form-control" style="max-width:240px"
                   placeholder="Search username / email…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <select name="role" class="form-control" style="max-width:160px">
                <option value="">All Roles</option>
                <option value="admin"     <?= ($_GET['role']??'')  === 'admin'     ? 'selected':'' ?>>Admins</option>
                <option value="moderator" <?= ($_GET['role']??'')  === 'moderator' ? 'selected':'' ?>>Moderators</option>
                <option value="member"    <?= ($_GET['role']??'')  === 'member'    ? 'selected':'' ?>>Members</option>
                <option value="banned"    <?= ($_GET['role']??'')  === 'banned'    ? 'selected':'' ?>>Banned</option>
            </select>
            <select name="verified" class="form-control" style="max-width:170px">
                <option value="">All Verification</option>
                <option value="1" <?= ($_GET['verified']??'') === '1' ? 'selected':'' ?>>Verified</option>
                <option value="0" <?= ($_GET['verified']??'') === '0' ? 'selected':'' ?>>Unverified</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><?= icon('search','',14) ?> Filter</button>
            <a href="/admin/users" class="btn btn-sm">Reset</a>
        </form>
    </div>
</div>

<!-- Bulk actions bar -->
<div id="bulk-bar" style="display:none;background:rgba(124,58,237,.1);border:1px solid rgba(124,58,237,.3);border-radius:10px;padding:12px 20px;margin-bottom:16px;align-items:center;gap:12px">
    <span id="bulk-count" class="text-violet" style="font-weight:600"></span>
    <button class="btn btn-sm btn-success" onclick="bulkAction('verify')"><?= icon('check','',13) ?> Verify</button>
    <button class="btn btn-sm btn-danger" onclick="bulkAction('ban')"><?= icon('shield','',13) ?> Ban</button>
    <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')"><?= icon('trash','',13) ?> Delete</button>
</div>

<div class="panel">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:36px"><input type="checkbox" id="check-all" onchange="toggleAll(this)"></th>
                <th>ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Trust</th>
                <th>Posts</th>
                <th>Rep</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr id="urow-<?= $u['id'] ?>">
            <td><input type="checkbox" class="row-check" value="<?= $u['id'] ?>" onchange="updateBulkBar()"></td>
            <td class="text-muted mono" style="font-size:0.78rem">#<?= $u['id'] ?></td>
            <td>
                <a href="/u/<?= htmlspecialchars($u['username']) ?>" target="_blank"
                   style="font-weight:600;color:var(--text);text-decoration:none">
                    <?= htmlspecialchars($u['username']) ?>
                </a><br>
                <span class="text-muted mono" style="font-size:0.75rem"><?= htmlspecialchars($u['email']) ?></span>
            </td>
            <td>
                <select class="form-control" style="padding:4px 8px;font-size:0.8rem;width:auto"
                        onchange="updateUser(<?= $u['id'] ?>, 'role', this.value)">
                    <option value="member"    <?= $u['role']==='member'    ?'selected':'' ?>>Member</option>
                    <option value="moderator" <?= $u['role']==='moderator' ?'selected':'' ?>>Moderator</option>
                    <option value="admin"     <?= $u['role']==='admin'     ?'selected':'' ?>>Admin</option>
                    <option value="banned"    <?= $u['role']==='banned'    ?'selected':'' ?>>Banned</option>
                </select>
            </td>
            <td>
                <select class="form-control" style="padding:4px 8px;font-size:0.8rem;width:auto"
                        onchange="updateUser(<?= $u['id'] ?>, 'trust_level', this.value)">
                    <?php for($i=0;$i<=5;$i++): ?>
                    <option value="<?= $i ?>" <?= (int)$u['trust_level']===$i?'selected':'' ?>>Lvl <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </td>
            <td><?= number_format($u['post_count']) ?></td>
            <td><?= number_format($u['reputation']) ?></td>
            <td>
                <?php if ($u['is_verified']): ?>
                    <span class="badge badge-success"><?= icon('check','',11) ?> Verified</span>
                <?php else: ?>
                    <span class="badge badge-amber"><?= icon('flag','',11) ?> Unverified</span>
                <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size:0.8rem;white-space:nowrap"><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
            <td>
                <div style="display:flex;gap:4px">
                    <button class="btn btn-xs" title="Impersonate" onclick="impersonate(<?= $u['id'] ?>)"><?= icon('eye','',12) ?></button>
                    <?php if (!$u['is_verified']): ?>
                    <button class="btn btn-xs btn-success" title="Verify" onclick="manualVerify(<?= $u['id'] ?>)"><?= icon('check','',12) ?></button>
                    <?php endif; ?>
                    <button class="btn btn-xs btn-danger" title="Delete" onclick="deleteUser(<?= $u['id'] ?>)"><?= icon('trash','',12) ?></button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
        <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--muted)">No users match your filter.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="panel-footer" style="display:flex;justify-content:center;gap:8px">
        <?php for($i=1;$i<=$totalPages;$i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($_GET['q']??'') ?>&role=<?= urlencode($_GET['role']??'') ?>"
           class="btn btn-sm <?= $page===$i?'btn-primary':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<script>
const csrf = '<?= htmlspecialchars($csrfToken) ?>';

async function updateUser(id, field, value) {
    const fd = new URLSearchParams({ user_id:id, field, value, _csrf_token:csrf });
    await fetch('/admin/users/update', { method:'POST', body:fd });
}

async function deleteUser(id) {
    if (!confirm('Permanently delete this user and all their content?')) return;
    const fd = new URLSearchParams({ user_id:id, _csrf_token:csrf });
    const r  = await fetch('/admin/users/delete', { method:'POST', body:fd });
    if ((await r.json()).success) document.getElementById('urow-'+id)?.remove();
    else alert('Error.');
}

async function manualVerify(id) {
    const fd = new URLSearchParams({ user_id:id, _csrf_token:csrf });
    const r  = await fetch('/admin/email-tools/verify', { method:'POST', body:fd });
    if ((await r.json()).success) location.reload();
}

function impersonate(id) {
    if (!confirm('View the site as this user? (Not yet implemented — opens their profile.)')) return;
    window.open('/u/' + id, '_blank');
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-check:checked');
    const bar     = document.getElementById('bulk-bar');
    const count   = document.getElementById('bulk-count');
    if (checked.length > 0) {
        bar.style.display = 'flex';
        count.textContent = checked.length + ' user(s) selected';
    } else {
        bar.style.display = 'none';
    }
}

async function bulkAction(action) {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
    if (!ids.length) return;
    if (!confirm(`${action.toUpperCase()} ${ids.length} user(s)?`)) return;

    for (const id of ids) {
        const fd = new URLSearchParams({ _csrf_token: csrf });
        if (action === 'ban') {
            fd.append('user_id', id); fd.append('field', 'role'); fd.append('value', 'banned');
            await fetch('/admin/users/update', { method: 'POST', body: fd });
        } else if (action === 'delete') {
            fd.append('user_id', id);
            await fetch('/admin/users/delete', { method: 'POST', body: fd });
        } else if (action === 'verify') {
            fd.append('user_id', id);
            await fetch('/admin/email-tools/verify', { method: 'POST', body: fd });
        }
    }
    location.reload();
}
</script>

</main>
</body>
</html>
