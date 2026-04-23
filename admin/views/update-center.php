<?php
$csrfToken = \Core\Middleware::getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Update Center — ACP</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --success:#10b981; --amber:#f59e0b; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        h1 { font-family: 'Syne', sans-serif; color: var(--cyan); margin: 0; }
        .btn { padding: 12px 24px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); cursor: pointer; text-decoration: none; font-size:1rem; font-family:'Syne',sans-serif; font-weight:600; }
        .btn-update { background: rgba(16,185,129,.1); border-color: var(--success); color: var(--success); box-shadow: 0 0 20px rgba(16,185,129,0.2); }
        .btn-update:hover { background: rgba(16,185,129,.2); transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 32px; max-width: 800px; margin: 0 auto; text-align: center; }
        
        .version-box { display: flex; justify-content: space-around; margin: 40px 0; padding: 24px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid var(--border); }
        .v-block { display: flex; flex-direction: column; gap: 8px; }
        .v-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); }
        .v-num { font-family: 'JetBrains Mono', monospace; font-size: 2.5rem; font-weight: 700; color: var(--text); }
        .v-arrow { font-size: 2rem; color: var(--muted); align-self: center; }

        .changelog { text-align: left; background: var(--surface2); padding: 24px; border-radius: 8px; margin: 24px 0; border: 1px solid var(--border); max-height: 300px; overflow-y: auto; font-size: 0.9rem; line-height: 1.6; }
        .changelog h2, .changelog h3 { color: var(--cyan); margin-top: 0; }
        
        #console { text-align: left; background: #000; padding: 16px; border-radius: 8px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #a0a0b0; display: none; margin-top: 24px; }
        .log-success { color: var(--success); }
        .log-error { color: var(--danger); }
    </style>
</head>
<body>
    <div class="header">
        <h1>Update Center</h1>
        <a href="/admin" class="btn" style="padding:8px 16px; font-size:0.9rem;">← Back</a>
    </div>

    <div class="panel">
        <?php if ($hasUpdate && $remote): ?>
            <h2 style="color:var(--success); margin-top:0;">Update Available! 🎉</h2>
            <p style="color:var(--muted)">A new version of AntiGravity Forum is ready to be installed.</p>
            
            <div class="version-box">
                <div class="v-block">
                    <span class="v-label">Current Version</span>
                    <span class="v-num">v<?= htmlspecialchars($local['version']) ?></span>
                </div>
                <div class="v-arrow">→</div>
                <div class="v-block">
                    <span class="v-label">Latest Version</span>
                    <span class="v-num" style="color:var(--success)"><?= htmlspecialchars($remote['tag_name']) ?></span>
                </div>
            </div>

            <?php if ($changelog): ?>
                <div class="changelog">
                    <?= nl2br(htmlspecialchars($changelog)) ?>
                </div>
            <?php endif; ?>

            <button id="btn-start" class="btn btn-update" onclick="startUpdate()">🚀 Install Update Automatically</button>
            
            <div id="console"></div>

        <?php else: ?>
            <div style="font-size: 4rem; margin-bottom: 20px;">✨</div>
            <h2 style="margin-top:0;">You're up to date!</h2>
            <p style="color:var(--muted)">You are running AntiGravity Forum version <strong>v<?= htmlspecialchars($local['version']) ?></strong>.</p>
            <p style="font-size:0.85rem; color:var(--muted); margin-top: 24px;">The system automatically checks GitHub for releases. Cache expires every 5 minutes.</p>
        <?php endif; ?>
    </div>

    <script>
    async function startUpdate() {
        if (!confirm('This will download the update, backup your files, run database migrations, and swap the core. Continue?')) return;
        
        const btn = document.getElementById('btn-start');
        const cons = document.getElementById('console');
        btn.disabled = true;
        btn.textContent = '⏳ Updating... Please do not close this page';
        cons.style.display = 'block';
        cons.innerHTML = 'Starting update sequence...<br>';

        try {
            const fd = new URLSearchParams({ _csrf_token: '<?= $csrfToken ?>' });
            const res = await fetch('/admin/updates/perform', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.error) {
                cons.innerHTML += `<span class="log-error">❌ Error: ${data.error}</span><br>`;
                btn.textContent = 'Update Failed';
            } else if (data.success) {
                data.steps.forEach(step => {
                    cons.innerHTML += `<span class="log-success">${step}</span><br>`;
                });
                cons.innerHTML += `<br><strong style="color:white">Update complete! Version is now v${data.version}. Refreshing in 3 seconds...</strong>`;
                setTimeout(() => location.reload(), 3000);
            }
        } catch (e) {
            cons.innerHTML += `<span class="log-error">❌ Connection lost or server error.</span><br>`;
            btn.textContent = 'Update Failed';
        }
    }
    </script>
</body>
</html>
