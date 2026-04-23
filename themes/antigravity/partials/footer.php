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
    <!-- Markdown Editor (EasyMDE) -->
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
          const editors = document.querySelectorAll('.rich-editor');
          editors.forEach(el => {
              window.editorInstance = new EasyMDE({ 
                  element: el,
                  spellChecker: false,
                  status: ["words", "lines"],
                  toolbar: ["bold", "italic", "heading", "|", "quote", "code", "unordered-list", "ordered-list", "|", "link", "image", "|", "preview", "side-by-side", "fullscreen"]
              });
          });
      });
    </script>

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
                    list.innerHTML = '<div class="notif-empty"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> All caught up!</div>'; return;
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
        const icons = {
            new_reply: '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M21 15c0 1.1-.9 2-2 2H7l-4 4V5c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v10z"/></svg>',
            mention:   '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10h5v-2h-5c-4.34 0-8-3.66-8-8s3.66-8 8-8 8 3.66 8 8v1.43c0 .79-.71 1.57-1.5 1.57s-1.5-.78-1.5-1.57V12c0-2.76-2.24-5-5-5s-5 2.24-5 5 2.24 5 5 5c1.38 0 2.64-.56 3.54-1.47.65.89 1.77 1.47 2.96 1.47 1.97 0 3.5-1.6 3.5-3.57V12c0-5.52-4.48-10-10-10zm0 13c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/></svg>',
            vote:      '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14z"/></svg>',
            badge:     '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
        };
        return icons[type] || '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>';
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
