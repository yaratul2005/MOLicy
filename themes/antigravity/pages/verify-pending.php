<?php
require ROOT_PATH . '/themes/antigravity/partials/icons.php';
$pageTitle = 'Check Your Email';
include ROOT_PATH . '/themes/antigravity/partials/header.php';
$pendingEmail = $_SESSION['verify_pending_email'] ?? null;
?>

<div style="min-height:60vh; display:flex; align-items:center; justify-content:center; padding:40px 20px;">
    <div class="thread-card animate-rise-in" style="max-width:520px; width:100%; padding:48px 40px; text-align:center;">

        <div style="width:72px; height:72px; background:linear-gradient(135deg, rgba(99,102,241,.25), rgba(14,165,233,.25)); border:2px solid rgba(99,102,241,.4); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 28px;">
            <?= icon('email', 'color:#6366f1', 32) ?>
        </div>

        <h1 style="font-family:var(--font-heading); font-size:1.6rem; margin:0 0 12px; background:linear-gradient(135deg,#6366f1,#0ea5e9); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">
            Check Your Inbox
        </h1>
        <p style="color:var(--color-text-muted); line-height:1.7; margin:0 0 20px;">
            We sent a verification email to
            <?php if ($pendingEmail): ?>
                <strong style="color:var(--color-text-main)"><?= htmlspecialchars($pendingEmail) ?></strong>
            <?php else: ?>
                your email address
            <?php endif; ?>.
            Click the link inside to activate your account.
        </p>
        <p style="color:var(--color-text-muted); font-size:0.85rem; margin:0 0 32px;">
            Didn't receive it? Check your spam folder. The link is valid for 48 hours.
        </p>

        <div style="display:flex; flex-direction:column; gap:10px;">
            <a href="/login" class="btn btn-primary magnetic" style="width:100%; justify-content:center;">
                <?= icon('lock', '', 15) ?> Go to Login
            </a>
            <a href="/" class="btn magnetic" style="width:100%; justify-content:center;">
                <?= icon('home', '', 15) ?> Back to Forum
            </a>
        </div>

        <p style="color:var(--color-text-muted); font-size:0.78rem; margin:24px 0 0;">
            Wrong email? <a href="/register" style="color:var(--color-cyan);">Register again</a>
        </p>
    </div>
</div>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
