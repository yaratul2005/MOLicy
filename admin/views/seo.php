<?php
$csrfToken = \Core\Middleware::getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>SEO Manager — ACP</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --success:#10b981; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        h1 { font-family: 'Syne', sans-serif; color: var(--success); margin: 0; }
        .btn { padding: 10px 20px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); cursor: pointer; text-decoration: none; font-size:1rem; }
        .btn-primary { background: rgba(16,185,129,.1); border-color: var(--success); color: var(--success); font-weight: 600; }
        .btn-primary:hover { background: rgba(16,185,129,.2); }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 32px; align-items: start; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; color: var(--muted); margin-bottom: 8px; font-size: 0.9rem; }
        .help { display: block; font-size: 0.75rem; color: var(--muted); margin-top: 4px; }
        input[type="text"], input[type="url"], textarea { width: 100%; padding: 12px; background: rgba(0,0,0,.2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        input:focus, textarea:focus { outline:none; border-color: var(--success); }
        .alert { background: rgba(16,185,129,.1); border: 1px solid var(--success); color: var(--success); padding: 16px; border-radius: 8px; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SEO & Metadata</h1>
        <a href="/admin" class="btn">← Back to Dashboard</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert">✅ Settings saved successfully!</div>
    <?php endif; ?>

    <div class="grid">
        <div class="panel">
            <form action="/admin/seo/save" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label>Global Site Title</label>
                    <input type="text" name="site_title" value="<?= htmlspecialchars($meta['site_title'] ?? '') ?>" maxlength="70">
                    <span class="help">Max 70 characters. Appears in search engines.</span>
                </div>

                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea name="meta_description" rows="3" maxlength="160"><?= htmlspecialchars($meta['meta_description'] ?? '') ?></textarea>
                    <span class="help">Max 160 characters.</span>
                </div>

                <div class="form-group">
                    <label>Default OpenGraph Image URL</label>
                    <input type="url" name="og_image" value="<?= htmlspecialchars($meta['og_image'] ?? '') ?>" placeholder="https://...">
                    <span class="help">Used when a thread/page doesn't have a specific image.</span>
                </div>

                <div class="form-group">
                    <label>Canonical Domain</label>
                    <input type="url" name="canonical_domain" value="<?= htmlspecialchars($meta['canonical_domain'] ?? '') ?>" placeholder="https://yourforum.com">
                    <span class="help">Force a specific domain structure in canonical tags and sitemaps.</span>
                </div>

                <div class="form-group">
                    <label>Google Analytics 4 Measurement ID</label>
                    <input type="text" name="google_analytics" value="<?= htmlspecialchars($meta['google_analytics'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                </div>

                <div class="form-group">
                    <label>Custom robots.txt</label>
                    <textarea name="robots_txt" rows="5" placeholder="User-agent: *..."><?= htmlspecialchars($meta['robots_txt'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="noindex_search" value="1" <?= !empty($meta['noindex_search']) ? 'checked' : '' ?>>
                        Add "noindex" tag to internal search results pages
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Save SEO Settings</button>
            </form>
        </div>

        <div class="panel">
            <h3>XML Sitemap</h3>
            <p style="font-size:0.9rem; color:var(--muted);">
                The sitemap tells search engines about all the threads and categories on your forum. It updates automatically when cached, but you can manually regenerate it.
            </p>
            <button onclick="generateSitemap()" class="btn" style="width:100%; margin-bottom:16px;">🔄 Generate Sitemap Now</button>
            <a href="/sitemap.xml" target="_blank" style="color:var(--cyan); font-size:0.9rem; text-decoration:none;">🔗 View Current Sitemap</a>
            
            <hr style="border:0; border-top:1px solid var(--border); margin: 24px 0;">
            
            <h3>JSON-LD Schema</h3>
            <p style="font-size:0.9rem; color:var(--muted);">
                AGF automatically outputs standard schema markup (BreadcrumbList, WebSite, DiscussionForumPosting) on relevant pages to enhance rich snippets.
            </p>
        </div>
    </div>

    <script>
    async function generateSitemap() {
        const btn = event.target;
        btn.textContent = 'Generating...';
        btn.disabled = true;
        const fd = new URLSearchParams({ _csrf_token: '<?= $csrfToken ?>' });
        const res = await fetch('/admin/seo/sitemap', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert('Sitemap generated successfully!');
            btn.textContent = '🔄 Generate Sitemap Now';
            btn.disabled = false;
        }
    }
    </script>
</body>
</html>
