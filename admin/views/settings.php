<?php
/**
 * ACP Settings View
 * @var array  $settings   All current settings
 * @var array  $auditLog   Recent admin actions
 */
$csrfToken = \Core\Middleware::getCSRFToken();
$s = $settings; // shorthand
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forum Settings — ACP</title>
    <meta name="robots" content="noindex">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --success:#10b981; --amber:#f59e0b; --danger:#ef4444; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }

        .acp-sidebar { background: var(--surface); border-right: 1px solid var(--border); padding: 28px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; }
        .acp-logo { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; color: var(--violet); padding: 0 24px 28px; border-bottom: 1px solid var(--border); margin-bottom: 12px; }
        .acp-logo span { color: var(--cyan); }
        .acp-nav a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: var(--muted); text-decoration: none; font-size: 0.9rem; border-left: 3px solid transparent; transition: all 200ms ease; }
        .acp-nav a:hover, .acp-nav a.active { color: var(--text); background: rgba(255,255,255,.03); border-left-color: var(--violet); }
        .acp-nav .nav-section { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); padding: 14px 24px 5px; opacity: 0.5; }
        .acp-sidebar-footer { margin-top: auto; padding: 16px 24px; border-top: 1px solid var(--border); }
        .acp-sidebar-footer a { color: var(--muted); text-decoration: none; font-size: 0.85rem; display: block; margin-bottom: 6px; }

        .acp-main { padding: 36px 40px; overflow-y: auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 36px; }
        .page-header h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--violet), var(--cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin-bottom: 32px; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; background: none; border: none; border-bottom: 2px solid transparent; color: var(--muted); cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 600; transition: all 200ms; margin-bottom: -1px; }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active { color: var(--cyan); border-bottom-color: var(--cyan); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        .settings-section { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 28px; margin-bottom: 24px; }
        .settings-section h2 { font-family: 'Syne', sans-serif; font-size: 1.1rem; margin-bottom: 20px; color: var(--cyan); padding-bottom: 12px; border-bottom: 1px solid var(--border); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-grid.single { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--muted); }
        .form-group input[type=text], .form-group input[type=email], .form-group input[type=number], .form-group input[type=password], .form-group input[type=url], .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; background: rgba(0,0,0,.3); border: 1px solid var(--border); color: var(--text); border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; transition: border-color 180ms; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--violet); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group .hint { font-size: 0.75rem; color: var(--muted); }
        .toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid var(--border); }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-row label { font-size: 0.9rem; font-weight: 500; }
        .toggle-row p { font-size: 0.78rem; color: var(--muted); }
        .toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; inset: 0; background: rgba(255,255,255,.1); border-radius: 24px; cursor: pointer; transition: background 200ms; }
        .toggle input:checked + .toggle-slider { background: var(--violet); }
        .toggle-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: transform 200ms; }
        .toggle input:checked + .toggle-slider::before { transform: translateX(20px); }

        .btn-save { padding: 12px 32px; background: var(--violet); border: none; border-radius: 10px; color: white; font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 200ms; }
        .btn-save:hover { background: #6d28d9; box-shadow: 0 8px 24px rgba(124,58,237,.4); transform: translateY(-1px); }
        .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); cursor: pointer; text-decoration: none; font-size: 0.88rem; }
        .btn:hover { border-color: var(--violet); }

        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-weight: 600; }
        .alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: var(--success); }

        .audit-table { width: 100%; border-collapse: collapse; }
        .audit-table th { text-align: left; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); padding: 8px 12px; border-bottom: 1px solid var(--border); }
        .audit-table td { padding: 10px 12px; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,.02); vertical-align: top; }
        .audit-table tr:hover td { background: rgba(255,255,255,.02); }
        .audit-action { padding: 2px 8px; border-radius: 6px; font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; background: rgba(124,58,237,.15); color: var(--violet); }

        .code-editor { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; min-height: 180px; background: rgba(0,0,0,.4) !important; }

        @media (max-width: 1024px) { body { grid-template-columns: 1fr; } .acp-sidebar { display: none; } .form-grid { grid-template-columns: 1fr; } }
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
        <a href="/admin/seo">🔍 SEO Manager</a>
        <a href="/admin/updates">🔄 Updates</a>
        <a href="/admin/settings" class="active">⚙️ Settings</a>
    </nav>
    <div class="acp-sidebar-footer">
        <a href="/">← View Forum</a>
        <a href="/logout" style="color:#ef4444">🚪 Logout</a>
    </div>
</aside>

<main class="acp-main">
    <div class="page-header">
        <h1>Forum Settings</h1>
        <a href="/admin" class="btn">← Dashboard</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">✅ Settings saved successfully!</div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('general', this)">🌐 General</button>
        <button class="tab-btn" onclick="switchTab('features', this)">🔧 Features</button>
        <button class="tab-btn" onclick="switchTab('appearance', this)">🎨 Appearance</button>
        <button class="tab-btn" onclick="switchTab('email', this)">📧 Email / SMTP</button>
        <button class="tab-btn" onclick="switchTab('custom', this)">💻 Custom Code</button>
        <button class="tab-btn" onclick="switchTab('audit', this)">📋 Audit Log</button>
    </div>

    <form action="/admin/settings/save" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

        <!-- ── GENERAL ── -->
        <div class="tab-pane active" id="tab-general">
            <div class="settings-section">
                <h2>🌐 Site Identity</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Site Title</label>
                        <input type="text" name="site_title" value="<?= htmlspecialchars($s['site_title'] ?? 'AntiGravity Forum') ?>">
                        <span class="hint">Shown in browser tabs and search engine results.</span>
                    </div>
                    <div class="form-group">
                        <label>Site Tagline</label>
                        <input type="text" name="site_tagline" value="<?= htmlspecialchars($s['site_tagline'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Logo URL</label>
                        <input type="url" name="site_logo" value="<?= htmlspecialchars($s['site_logo'] ?? '') ?>" placeholder="https://...">
                        <span class="hint">Leave empty to show text logo.</span>
                    </div>
                    <div class="form-group">
                        <label>Favicon URL</label>
                        <input type="url" name="site_favicon" value="<?= htmlspecialchars($s['site_favicon'] ?? '') ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Forum Contact Email</label>
                        <input type="email" name="forum_email" value="<?= htmlspecialchars($s['forum_email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Footer Text</label>
                        <input type="text" name="footer_text" value="<?= htmlspecialchars($s['footer_text'] ?? 'Powered by AntiGravity Forum') ?>">
                    </div>
                    <div class="form-group">
                        <label>Site Language</label>
                        <select name="site_language">
                            <option value="en" <?= ($s['site_language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                            <option value="bn" <?= ($s['site_language'] ?? '') === 'bn' ? 'selected' : '' ?>>Bengali</option>
                            <option value="ar" <?= ($s['site_language'] ?? '') === 'ar' ? 'selected' : '' ?>>Arabic</option>
                            <option value="fr" <?= ($s['site_language'] ?? '') === 'fr' ? 'selected' : '' ?>>French</option>
                            <option value="es" <?= ($s['site_language'] ?? '') === 'es' ? 'selected' : '' ?>>Spanish</option>
                            <option value="de" <?= ($s['site_language'] ?? '') === 'de' ? 'selected' : '' ?>>German</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Timezone</label>
                        <select name="site_timezone">
                            <?php foreach (\DateTimeZone::listIdentifiers() as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($s['site_timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2>🏠 Homepage Welcome Block</h2>
                <div class="toggle-row">
                    <div><label>Enable Welcome Banner</label><p>Show a large hero section on the homepage.</p></div>
                    <label class="toggle"><input type="checkbox" name="home_welcome_enabled" value="1" <?= !empty($s['home_welcome_enabled']) && $s['home_welcome_enabled'] !== '0' ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                </div>
                <div class="form-grid" style="margin-top:16px">
                    <div class="form-group">
                        <label>Welcome Title</label>
                        <input type="text" name="home_welcome_title" value="<?= htmlspecialchars($s['home_welcome_title'] ?? 'Welcome to Our Forum') ?>">
                    </div>
                    <div class="form-group">
                        <label>Welcome Text</label>
                        <input type="text" name="home_welcome_text" value="<?= htmlspecialchars($s['home_welcome_text'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2>🚧 Maintenance Mode</h2>
                <div class="toggle-row">
                    <div><label>Enable Maintenance Mode</label><p>Shows a maintenance notice to all non-admin visitors. Admins can still browse normally.</p></div>
                    <label class="toggle"><input type="checkbox" name="maintenance_mode" value="1" <?= !empty($s['maintenance_mode']) && $s['maintenance_mode'] !== '0' ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                </div>
                <div class="form-group" style="margin-top:16px">
                    <label>Maintenance Message</label>
                    <textarea name="maintenance_message"><?= htmlspecialchars($s['maintenance_message'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- ── FEATURES ── -->
        <div class="tab-pane" id="tab-features">
            <div class="settings-section">
                <h2>👥 User Registration</h2>
                <div class="toggle-row">
                    <div><label>Allow New Registrations</label><p>Users can create new accounts.</p></div>
                    <label class="toggle"><input type="checkbox" name="registration_enabled" value="1" <?= ($s['registration_enabled'] ?? '1') !== '0' ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                </div>
                <div class="toggle-row">
                    <div><label>Require Email Verification</label><p>New accounts must verify email before posting.</p></div>
                    <label class="toggle"><input type="checkbox" name="require_email_verify" value="1" <?= !empty($s['require_email_verify']) && $s['require_email_verify'] !== '0' ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                </div>
                <div class="toggle-row">
                    <div><label>Allow Guest Browsing</label><p>Logged-out visitors can read threads.</p></div>
                    <label class="toggle"><input type="checkbox" name="allow_guest_view" value="1" <?= ($s['allow_guest_view'] ?? '1') !== '0' ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                </div>
            </div>

            <div class="settings-section">
                <h2>📄 Pagination</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Threads per Page</label>
                        <input type="number" name="threads_per_page" value="<?= (int)($s['threads_per_page'] ?? 20) ?>" min="5" max="100">
                    </div>
                    <div class="form-group">
                        <label>Posts per Page</label>
                        <input type="number" name="posts_per_page" value="<?= (int)($s['posts_per_page'] ?? 20) ?>" min="5" max="100">
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2>📁 File Uploads</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Max Upload Size (MB)</label>
                        <input type="number" name="max_upload_size" value="<?= (int)($s['max_upload_size'] ?? 5) ?>" min="1" max="100">
                    </div>
                    <div class="form-group">
                        <label>Allowed File Types</label>
                        <input type="text" name="allowed_file_types" value="<?= htmlspecialchars($s['allowed_file_types'] ?? 'jpg,png,gif,webp,pdf') ?>">
                        <span class="hint">Comma-separated extensions (no dots).</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── APPEARANCE ── -->
        <div class="tab-pane" id="tab-appearance">
            <div class="settings-section">
                <h2>🎨 Theme</h2>
                <div class="form-group">
                    <label>Active Theme</label>
                    <select name="site_theme">
                        <?php foreach (glob(ROOT_PATH . '/themes/*', GLOB_ONLYDIR) as $themeDir): ?>
                            <?php $themeName = basename($themeDir); ?>
                            <option value="<?= $themeName ?>" <?= ($s['site_theme'] ?? 'antigravity') === $themeName ? 'selected' : '' ?>><?= ucfirst($themeName) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">Themes are directories inside the <code>/themes/</code> folder.</span>
                </div>
            </div>
        </div>

        <!-- ── EMAIL ── -->
        <div class="tab-pane" id="tab-email">
            <div class="settings-section">
                <h2>📧 SMTP Configuration</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="number" name="smtp_port" value="<?= (int)($s['smtp_port'] ?? 587) ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="email" name="smtp_user" value="<?= htmlspecialchars($s['smtp_user'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="smtp_pass" value="<?= htmlspecialchars($s['smtp_pass'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_secure">
                            <option value="tls" <?= ($s['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($s['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="" <?= empty($s['smtp_secure']) ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── CUSTOM CODE ── -->
        <div class="tab-pane" id="tab-custom">
            <div class="settings-section">
                <h2>💻 Custom CSS</h2>
                <div class="form-group form-grid single">
                    <textarea name="custom_css" class="code-editor" placeholder="/* Your custom CSS here */"><?= htmlspecialchars($s['custom_css'] ?? '') ?></textarea>
                    <span class="hint">Injected in <code>&lt;/head&gt;</code> on every page.</span>
                </div>
            </div>
            <div class="settings-section">
                <h2>💻 Custom JavaScript</h2>
                <div class="form-group form-grid single">
                    <textarea name="custom_js" class="code-editor" placeholder="// Your custom JS here"><?= htmlspecialchars($s['custom_js'] ?? '') ?></textarea>
                    <span class="hint">Injected before <code>&lt;/body&gt;</code> on every page.</span>
                </div>
            </div>
        </div>

        <!-- ── AUDIT LOG (no form) ── -->
        <div class="tab-pane" id="tab-audit">
            <div class="settings-section">
                <h2>📋 Admin Audit Log (last 30 actions)</h2>
                <table class="audit-table">
                    <thead>
                        <tr><th>Time</th><th>Admin</th><th>Action</th><th>Target</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLog as $entry): ?>
                            <tr>
                                <td style="color:var(--muted); font-size:0.78rem; white-space:nowrap"><?= date('M j H:i', strtotime($entry['created_at'])) ?></td>
                                <td><?= htmlspecialchars($entry['username'] ?? 'System') ?></td>
                                <td><span class="audit-action"><?= htmlspecialchars($entry['action']) ?></span></td>
                                <td style="color:var(--muted)"><?= htmlspecialchars($entry['target_type'] ?? '') ?> <?= $entry['target_id'] ? '#'.$entry['target_id'] : '' ?></td>
                                <td style="font-family:monospace; font-size:0.8rem"><?= htmlspecialchars($entry['ip'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($auditLog)): ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--muted); padding:40px">No actions logged yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:28px; gap:12px" id="save-bar">
            <a href="/admin" class="btn">Cancel</a>
            <button type="submit" class="btn-save">💾 Save All Settings</button>
        </div>
    </form>
</main>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
    // Hide save bar on audit tab
    document.getElementById('save-bar').style.display = id === 'audit' ? 'none' : 'flex';
}
</script>
</body>
</html>
