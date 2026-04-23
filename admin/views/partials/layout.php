<?php
/**
 * Admin Panel Shared Layout — include at top of every ACP view
 * @param string $activeNav  Which nav item to mark active (e.g. 'dashboard', 'users', 'content')
 * @param string $pageTitle  Browser title suffix
 */
require_once ROOT_PATH . '/themes/antigravity/partials/icons.php';
$acpTitle  = $pageTitle ?? 'ACP';
$activeNav = $activeNav ?? '';
$csrfMeta  = \Core\Middleware::getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($acpTitle) ?> — AntiGravity ACP</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:#07070d; --surface:#0f172a; --surface2:#1a1a2e; --surface3:#232340;
            --violet:#7c3aed; --cyan:#06b6d4; --amber:#f59e0b;
            --success:#10b981; --danger:#ef4444; --info:#3b82f6;
            --text:#f1f5f9; --muted:#94a3b8;
            --border:rgba(255,255,255,.06); --glass:rgba(255,255,255,.03);
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:grid;grid-template-columns:220px 1fr;min-height:100vh;font-size:0.92rem}

        /* ── Sidebar ── */
        .acp-sidebar{background:var(--surface);border-right:1px solid var(--border);padding:0;position:sticky;top:0;height:100vh;overflow-y:auto;display:flex;flex-direction:column}
        .acp-logo{padding:22px 20px 16px;font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;letter-spacing:-0.3px;border-bottom:1px solid var(--border);flex-shrink:0}
        .acp-logo span{background:linear-gradient(135deg,var(--violet),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .acp-nav{padding:12px 0;flex:1}
        .nav-section{padding:16px 20px 6px;font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
        .acp-nav a{display:flex;align-items:center;gap:9px;padding:8px 20px;color:var(--muted);text-decoration:none;font-size:0.85rem;transition:all 160ms;border-left:3px solid transparent}
        .acp-nav a:hover{color:var(--text);background:var(--glass)}
        .acp-nav a.active{color:var(--violet);background:rgba(124,58,237,.08);border-left-color:var(--violet);font-weight:600}
        .acp-nav a svg{flex-shrink:0;opacity:.7}
        .acp-nav a.active svg{opacity:1}
        .nav-badge{margin-left:auto;background:var(--danger);color:#fff;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:20px;line-height:1.5}
        .acp-footer{padding:16px 20px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:6px;flex-shrink:0}
        .acp-footer a{color:var(--muted);text-decoration:none;font-size:0.82rem;display:flex;align-items:center;gap:8px;transition:color 160ms}
        .acp-footer a:hover{color:var(--danger)}

        /* ── Main ── */
        .acp-main{padding:32px 36px;overflow-y:auto;min-height:100vh}
        .acp-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;gap:16px;flex-wrap:wrap}
        .acp-topbar h1{font-family:'Syne',sans-serif;font-size:1.65rem;font-weight:800;background:linear-gradient(135deg,var(--violet),var(--cyan));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .acp-topbar-actions{display:flex;gap:10px;flex-wrap:wrap}

        /* ── Buttons ── */
        .btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:9px;border:1px solid var(--border);background:var(--surface2);color:var(--text);cursor:pointer;text-decoration:none;font-size:0.85rem;font-family:inherit;transition:all 180ms}
        .btn:hover{background:rgba(124,58,237,.15);border-color:var(--violet);color:var(--violet)}
        .btn-primary{background:var(--violet);border-color:var(--violet);color:#fff;font-weight:600}
        .btn-primary:hover{background:#6d28d9;border-color:#6d28d9;color:#fff}
        .btn-success{background:rgba(16,185,129,.15);border-color:var(--success);color:var(--success)}
        .btn-success:hover{background:rgba(16,185,129,.25)}
        .btn-danger{background:rgba(239,68,68,.1);border-color:var(--danger);color:var(--danger)}
        .btn-danger:hover{background:rgba(239,68,68,.2)}
        .btn-sm{padding:5px 11px;font-size:0.78rem;gap:5px}
        .btn-xs{padding:3px 8px;font-size:0.72rem;gap:4px}

        /* ── Panels / Cards ── */
        .panel{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden}
        .panel-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}
        .panel-header h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
        .panel-body{padding:20px}
        .panel-footer{padding:12px 20px;border-top:1px solid var(--border);background:var(--glass)}

        /* ── Tables ── */
        .data-table{width:100%;border-collapse:collapse;font-size:0.85rem}
        .data-table th{text-align:left;color:var(--muted);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.07em;padding:10px 16px;border-bottom:1px solid var(--border);background:rgba(0,0,0,.15);font-weight:600}
        .data-table td{padding:11px 16px;border-bottom:1px solid var(--border);vertical-align:middle}
        .data-table tbody tr:hover td{background:rgba(255,255,255,.02)}
        .data-table tbody tr:last-child td{border-bottom:none}

        /* ── Forms ── */
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:0.82rem;font-weight:600;color:var(--muted);margin-bottom:6px}
        .form-control{width:100%;padding:9px 12px;background:rgba(0,0,0,.3);border:1px solid var(--border);color:var(--text);border-radius:8px;font-family:inherit;font-size:0.88rem;transition:border-color 180ms}
        .form-control:focus{outline:none;border-color:var(--violet)}
        .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
        .hint{font-size:0.75rem;color:var(--muted);margin-top:4px;display:block}

        /* ── Badges / Status ── */
        .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:0.72rem;font-weight:600}
        .badge-success{background:rgba(16,185,129,.15);color:var(--success);border:1px solid rgba(16,185,129,.25)}
        .badge-danger{background:rgba(239,68,68,.12);color:var(--danger);border:1px solid rgba(239,68,68,.2)}
        .badge-amber{background:rgba(245,158,11,.12);color:var(--amber);border:1px solid rgba(245,158,11,.2)}
        .badge-muted{background:rgba(255,255,255,.05);color:var(--muted);border:1px solid var(--border)}
        .badge-violet{background:rgba(124,58,237,.15);color:var(--violet);border:1px solid rgba(124,58,237,.25)}

        /* ── Alerts ── */
        .alert{padding:12px 18px;border-radius:10px;font-size:0.88rem;margin-bottom:20px;display:flex;align-items:center;gap:10px}
        .alert-success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:var(--success)}
        .alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger)}
        .alert-info{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);color:var(--info)}
        .alert-amber{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:var(--amber)}

        /* ── Toggle ── */
        .toggle{position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0}
        .toggle input{opacity:0;width:0;height:0}
        .toggle-slider{position:absolute;inset:0;background:rgba(255,255,255,.12);border-radius:22px;cursor:pointer;transition:background 220ms}
        .toggle-slider:before{content:'';position:absolute;width:16px;height:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:transform 220ms}
        .toggle input:checked + .toggle-slider{background:var(--violet)}
        .toggle input:checked + .toggle-slider:before{transform:translateX(18px)}

        /* ── Misc ── */
        .text-muted{color:var(--muted)}
        .text-danger{color:var(--danger)}
        .text-success{color:var(--success)}
        .text-amber{color:var(--amber)}
        .text-violet{color:var(--violet)}
        .mono{font-family:'JetBrains Mono',monospace}
        .section-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin:0 0 16px;display:flex;align-items:center;gap:8px}
        .divider{border:none;border-top:1px solid var(--border);margin:24px 0}

        @media(max-width:900px){body{grid-template-columns:1fr}.acp-sidebar{display:none}}
    </style>
</head>
<body>

<!-- ══ Sidebar ══ -->
<aside class="acp-sidebar">
    <div class="acp-logo">Anti<span>Gravity</span> ACP</div>
    <nav class="acp-nav">
        <div class="nav-section">Overview</div>
        <a href="/admin" class="<?= $activeNav==='dashboard'?'active':'' ?>"><?= icon('chart') ?> Dashboard</a>
        <a href="/admin/analytics" class="<?= $activeNav==='analytics'?'active':'' ?>"><?= icon('analytics') ?> Analytics</a>

        <div class="nav-section">Community</div>
        <a href="/admin/users" class="<?= $activeNav==='users'?'active':'' ?>"><?= icon('users') ?> Users</a>
        <a href="/admin/content" class="<?= $activeNav==='content'?'active':'' ?>"><?= icon('content') ?> Threads</a>
        <a href="/admin/categories" class="<?= $activeNav==='categories'?'active':'' ?>"><?= icon('categories') ?> Categories</a>
        <a href="/admin/moderation" class="<?= $activeNav==='moderation'?'active':'' ?>"><?= icon('flag') ?> Moderation <?php
            $mq = \Core\Database::getInstance()->fetch("SELECT COUNT(*) as c FROM posts WHERE is_flagged=1");
            if(($mq['c']??0)>0) echo '<span class="nav-badge">'.(int)$mq['c'].'</span>';
        ?></a>

        <div class="nav-section">System</div>
        <a href="/admin/email-tools" class="<?= $activeNav==='email-tools'?'active':'' ?>"><?= icon('email') ?> Email Tools</a>
        <a href="/admin/seo" class="<?= $activeNav==='seo'?'active':'' ?>"><?= icon('seo') ?> SEO Manager</a>
        <a href="/admin/updates" class="<?= $activeNav==='updates'?'active':'' ?>"><?= icon('updates') ?> Updates</a>
        <a href="/admin/settings" class="<?= $activeNav==='settings'?'active':'' ?>"><?= icon('settings') ?> Settings</a>
    </nav>
    <div class="acp-footer">
        <a href="/" target="_blank"><?= icon('globe', '', 14) ?> View Forum</a>
        <a href="/logout"><?= icon('logout', '', 14) ?> Logout</a>
    </div>
</aside>

<!-- ══ Main Content ══ -->
<main class="acp-main">
