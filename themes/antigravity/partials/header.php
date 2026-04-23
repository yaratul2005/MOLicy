<?php
$currentUser  = \Core\Auth::check() ? \Core\Auth::user() : null;
$csrfToken    = \Core\Middleware::getCSRFToken();
$siteTitle    = \Core\Settings::siteTitle();
$siteTagline  = \Core\Settings::siteTagline();
$siteLogo     = \Core\Settings::get('site_logo');
$siteFavicon  = \Core\Settings::get('site_favicon');
$customCss    = \Core\Settings::get('custom_css');
$customJs     = \Core\Settings::get('custom_js');
$ga           = \Core\Settings::get('google_analytics');
$pageTitle    = isset($pageTitle) ? $pageTitle . ' — ' . $siteTitle : $siteTitle;
$metaDesc     = $metaDesc ?? \Core\Settings::get('meta_description', $siteTagline);
$canonicalUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php if ($siteFavicon): ?><link rel="icon" href="<?= htmlspecialchars($siteFavicon) ?>">  <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:title"       content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta name="twitter:card"       content="summary_large_image">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500&family=Playfair+Display:ital@0;1&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/themes/antigravity/assets/css/variables.css">
    <link rel="stylesheet" href="/themes/antigravity/assets/css/motion.css">
    <link rel="stylesheet" href="/themes/antigravity/assets/css/layout.css">
    <link rel="stylesheet" href="/themes/antigravity/assets/css/components.css">
    <link rel="stylesheet" href="/themes/antigravity/assets/css/dark-mode.css">
    
    <!-- Markdown Editor (EasyMDE) -->
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <style>
      .editor-toolbar { border-color: var(--color-border); background: var(--color-surface-2); opacity: 1; border-radius: var(--radius-sm) var(--radius-sm) 0 0; }
      .editor-toolbar button { color: var(--color-text-muted); border: none; }
      .editor-toolbar button:hover, .editor-toolbar button.active { background: rgba(255,255,255,0.05); color: var(--color-cyan); border: none; }
      .editor-toolbar i.separator { border-left-color: var(--color-border); border-right-color: transparent; }
      .CodeMirror { background: var(--color-surface-1); color: var(--color-text-main); border-color: var(--color-border); border-radius: 0 0 var(--radius-sm) var(--radius-sm); font-family: var(--font-body); }
      .CodeMirror-cursor { border-left-color: var(--color-violet) !important; }
      .editor-statusbar { color: var(--color-text-muted); }
    </style>

    <!-- JSON-LD: Website schema on every page -->
    <?= \Admin\SEOManager::schema('website', []) ?>

    <!-- Inline critical page transition CSS (no @view-transition to avoid DOM timeout) -->
    <style>
        /* Smooth page transitions via JS-driven class toggling */
        .page-exit { animation: 180ms ease-out both page-fade-out; }
        .page-enter { animation: 300ms cubic-bezier(0.34,1.56,0.64,1) both page-fade-in; }
        @keyframes page-fade-out { to { opacity:0; transform:translateY(-6px); } }
        @keyframes page-fade-in  { from { opacity:0; transform:translateY(10px); } }
    </style>
    <?php if (!empty($customCss)): ?>
    <style id="custom-css"><?= $customCss ?></style>
    <?php endif; ?>
</head>
<body data-user-id="<?= $currentUser ? (int)$currentUser['id'] : '' ?>">
    <div class="site-wrapper">

        <header class="site-header" id="site-header">
            <div class="container header-inner">

                <!-- Mobile menu toggle -->
                <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Toggle menu" onclick="document.body.classList.toggle('nav-open')">
                    <span></span><span></span><span></span>
                </button>

                <!-- Logo -->
                <a href="/" class="site-logo" data-no-transition>
                    <?php if (!empty($siteLogo)): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteTitle) ?>" style="max-height:36px;">
                    <?php else: ?>
                        <span class="logo-text"><?= htmlspecialchars($siteTitle) ?></span>
                    <?php endif; ?>
                </a>

                <!-- Search Bar -->
                <div class="header-search" id="header-search">
                    <input type="text"
                           id="search-input"
                           placeholder="Search threads, people..."
                           autocomplete="off"
                           aria-label="Search">
                    <div id="search-suggestions" class="search-dropdown" aria-live="polite"></div>
                </div>

                <!-- Nav Right -->
                <nav class="header-nav" aria-label="Main navigation">
                    <a href="/members" class="btn magnetic" style="display:none" id="btn-members">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Members
                    </a>
                    <a href="/thread/create" class="btn btn-primary magnetic" id="btn-new-thread">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        New Thread
                    </a>

                    <?php if ($currentUser): ?>
                        <!-- Notifications Bell -->
                        <div class="notif-wrap" id="notif-wrap">
                            <button class="notif-btn magnetic" id="notif-btn" aria-label="Notifications" aria-expanded="false">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                <span class="notif-badge" id="notif-badge" style="display:none">0</span>
                            </button>
                            <div class="notif-dropdown" id="notif-dropdown" role="menu" aria-hidden="true">
                                <div class="notif-header">
                                    Notifications
                                    <button onclick="markAllRead()" class="notif-clear">Mark all read</button>
                                </div>
                                <div id="notif-list">
                                    <div class="notif-loading">Loading...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Avatar / User -->
                        <a href="/u/<?= htmlspecialchars($currentUser['username']) ?>"
                           class="user-chip magnetic"
                           data-vt-avatar="<?= (int)$currentUser['id'] ?>">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?= htmlspecialchars($currentUser['avatar']) ?>"
                                     alt="<?= htmlspecialchars($currentUser['username']) ?>"
                                     class="user-chip-avatar">
                            <?php else: ?>
                                <span class="user-chip-initials">
                                    <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                                </span>
                            <?php endif; ?>
                            <span class="user-chip-name"><?= htmlspecialchars($currentUser['username']) ?></span>
                        </a>

                        <?php if (($currentUser['trust_level'] ?? 0) >= 5): ?>
                            <a href="/admin" class="btn magnetic" style="background:rgba(245,158,11,.15);border-color:#f59e0b;color:#f59e0b">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 1 0 4.93 19.07 10 10 0 0 0 19.07 4.93z"/></svg>
                                ACP
                            </a>
                        <?php endif; ?>
                        <a href="/logout" class="btn magnetic" data-no-transition>Logout</a>

                    <?php else: ?>
                        <a href="/login"    class="btn magnetic">Login</a>
                        <a href="/register" class="btn btn-primary magnetic">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <main class="main-content" id="main">
            <div class="container">


