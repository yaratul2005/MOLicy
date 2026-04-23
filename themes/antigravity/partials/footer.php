<?php
/**
 * Site Footer Partial
 * Closes <main>/<body>, loads all JS modules.
 */
?>
            </div><!-- /.container -->
        </main><!-- /#main -->

        <footer class="site-footer">
            <div class="container footer-inner">
                <?php
                $footerText = \Core\Settings::get('footer_text', 'Powered by AntiGravity Forum');
                ?>
                <p class="footer-brand">
                    <span style="font-family:var(--font-hero);color:var(--color-violet)">Anti<span style="color:var(--color-cyan)">Gravity</span></span>
                    &mdash; <?= htmlspecialchars($footerText) ?>
                </p>
                <nav class="footer-links" aria-label="Footer navigation">
                    <a href="/">Home</a>
                    <a href="/members">Members</a>
                    <a href="/search">Search</a>
                    <a href="/sitemap.xml">Sitemap</a>
                </nav>
                <p class="footer-copy">&copy; <?= date('Y') ?> <?= htmlspecialchars(\Core\Settings::siteTitle()) ?>. All rights reserved.</p>
            </div>
        </footer>

    </div><!-- /.site-wrapper -->

    <!-- CSRF Meta for AJAX -->
    <meta name="csrf-token" id="csrf-meta" content="<?= \Core\Middleware::getCSRFToken() ?>">

    <!-- JS Modules (deferred) -->
    <script defer src="/themes/antigravity/assets/js/motion-engine.js"></script>
    <script defer src="/themes/antigravity/assets/js/magnetic.js"></script>
    <script defer src="/themes/antigravity/assets/js/parallax.js"></script>
    <script defer src="/themes/antigravity/assets/js/page-transitions.js"></script>
    <script defer src="/themes/antigravity/assets/js/live.js"></script>

    <script>
    // ── Header scroll effect ─────────────────────────────────────────
    const header = document.getElementById('site-header');
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });

    // ── Search suggestions ────────────────────────────────────────────
    let suggestTimer;
    const searchInput = document.getElementById('search-input');
    const suggestBox  = document.getElementById('search-suggestions');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(suggestTimer);
            const q = searchInput.value.trim();
            if (q.length < 2) { suggestBox.style.display = 'none'; return; }
            suggestTimer = setTimeout(async () => {
                const res  = await fetch(`/api/v1/search/suggest?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (!data.length) { suggestBox.style.display = 'none'; return; }
                suggestBox.innerHTML = data.map(item =>
                    `<a href="${item.type === 'user' ? '/u/' : '/thread/'}${encodeURIComponent(item.slug)}" class="suggest-item">
                        <span class="suggest-type">${item.type}</span>
                        ${item.text}
                    </a>`
                ).join('');
                suggestBox.style.display = 'block';
                suggestBox.animate(
                    [{opacity:0, transform:'translateY(-6px)'},{opacity:1, transform:'translateY(0)'}],
                    {duration:200, easing:'cubic-bezier(0.34,1.56,0.64,1)', fill:'forwards'}
                );
            }, 280);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                window.location.href = `/search?q=${encodeURIComponent(searchInput.value)}`;
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#header-search')) suggestBox.style.display = 'none';
        });
    }

    // ── Notification dropdown ─────────────────────────────────────────
    const notifBtn  = document.getElementById('notif-btn');
    const notifDrop = document.getElementById('notif-dropdown');

    if (notifBtn) {
        // Load unread count on boot
        fetch('/notifications/count')
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('notif-badge');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'flex';
                }
            }).catch(() => {});

        notifBtn.addEventListener('click', () => {
            const isOpen = notifDrop.classList.toggle('open');
            notifBtn.setAttribute('aria-expanded', isOpen);
            notifDrop.setAttribute('aria-hidden', !isOpen);

            if (isOpen) {
                notifDrop.animate(
                    [{opacity:0, transform:'scale(0.92) translateY(-8px)'},{opacity:1, transform:'scale(1) translateY(0)'}],
                    {duration:280, easing:'cubic-bezier(0.34,1.56,0.64,1)', fill:'forwards'}
                );
                loadNotifications();
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#notif-wrap')) {
                notifDrop.classList.remove('open');
                notifBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function loadNotifications() {
        fetch('/notifications')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('notif-list');
                if (!data.length) {
                    list.innerHTML = '<div class="notif-empty">All caught up! 🎉</div>'; return;
                }
                list.innerHTML = data.map(n => `
                    <div class="notif-item ${n.is_read ? '' : 'unread'}">
                        <div class="notif-icon">${getNotifIcon(n.type)}</div>
                        <div class="notif-body">
                            <div class="notif-text">${formatNotif(n)}</div>
                            <div class="notif-time">${timeAgo(n.created_at)}</div>
                        </div>
                    </div>
                `).join('');
            }).catch(() => {});
    }

    function markAllRead() {
        const csrf = document.getElementById('csrf-meta').content;
        fetch('/notifications/read-all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `_csrf_token=${csrf}`
        }).then(() => {
            document.getElementById('notif-badge').style.display = 'none';
            loadNotifications();
        });
    }

    function getNotifIcon(type) {
        return {new_reply:'💬', mention:'📣', vote:'⬆️', badge:'🏅'}[type] || '🔔';
    }

    function formatNotif(n) {
        const d = n.data || {};
        switch (n.type) {
            case 'new_reply': return `New reply in <strong>${d.thread_title || 'a thread'}</strong>`;
            case 'mention':   return `<strong>${d.from_username || 'Someone'}</strong> mentioned you`;
            case 'vote':      return `Your post received a vote`;
            case 'badge':     return `Badge unlocked: <strong>${d.badge_name || ''}</strong>`;
            default:          return 'New notification';
        }
    }

    function timeAgo(ts) {
        const diff = Math.floor((Date.now() - new Date(ts)) / 1000);
        if (diff < 60)   return 'just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    }
    </script>

    <?php if (!empty($customJs)): ?>
    <script><?= $customJs ?></script>
    <?php endif; ?>

    <?php if (!empty($ga)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($ga) ?>');</script>
    <?php endif; ?>

</body>
</html>
