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

    <!-- Inline critical view transition CSS -->
    <style>
        @view-transition { navigation: auto; }
        ::view-transition-old(main-content) { animation: 180ms ease-out both slide-out; }
        ::view-transition-new(main-content) { animation: 280ms var(--spring-bounce, cubic-bezier(0.34,1.56,0.64,1)) both slide-in; }
        @keyframes slide-out { to { opacity:0; transform:translateY(-8px); } }
        @keyframes slide-in  { from { opacity:0; transform:translateY(12px); } }
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
                    <a href="/members" class="btn magnetic" style="display:none" id="btn-members">👥 Members</a>
                    <a href="/thread/create" class="btn btn-primary magnetic" id="btn-new-thread">✍️ New Thread</a>

                    <?php if ($currentUser): ?>
                        <!-- Notifications Bell -->
                        <div class="notif-wrap" id="notif-wrap">
                            <button class="notif-btn magnetic" id="notif-btn" aria-label="Notifications" aria-expanded="false">
                                🔔
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
                            <a href="/admin" class="btn magnetic" style="background:rgba(245,158,11,.15);border-color:#f59e0b;color:#f59e0b">⚙️ ACP</a>
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
