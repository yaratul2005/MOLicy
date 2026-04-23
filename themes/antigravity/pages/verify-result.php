<?php
require ROOT_PATH . '/themes/antigravity/partials/icons.php';
$pageTitle = $verifySuccess ? 'Email Verified!' : 'Verification Failed';
include ROOT_PATH . '/themes/antigravity/partials/header.php';
?>

<div style="min-height:60vh; display:flex; align-items:center; justify-content:center; padding:40px 20px;">
    <div class="thread-card animate-rise-in" style="max-width:520px; width:100%; padding:48px 40px; text-align:center;">

        <?php if ($verifySuccess ?? false): ?>
            <!-- ✅ Success -->
            <div style="width:72px; height:72px; background:rgba(16,185,129,.15); border:2px solid rgba(16,185,129,.4); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 28px;">
                <?= icon('check', 'color:#10b981', 34) ?>
            </div>

            <h1 style="font-family:var(--font-heading); font-size:1.6rem; margin:0 0 12px; color:var(--color-text-main);">
                You're verified!
            </h1>
            <p style="color:var(--color-text-muted); line-height:1.7; margin:0 0 8px;">
                Hey <strong style="color:var(--color-text-main)"><?= htmlspecialchars($verifyUsername ?? '') ?></strong>,
                your email has been confirmed. Your account is now fully active.
            </p>
            <p style="color:var(--color-text-muted); font-size:0.88rem; margin:0 0 32px;">
                You can now log in and start participating in the community.
            </p>

            <a href="/login" class="btn btn-primary magnetic" style="width:100%; justify-content:center; font-size:1rem; padding:14px;">
                <?= icon('lock', '', 16) ?> Log In to Your Account
            </a>

        <?php else: ?>
            <!-- ❌ Failed -->
            <div style="width:72px; height:72px; background:rgba(239,68,68,.12); border:2px solid rgba(239,68,68,.35); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 28px;">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="#ef4444" aria-hidden="true">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </div>

            <h1 style="font-family:var(--font-heading); font-size:1.6rem; margin:0 0 12px; color:var(--color-text-main);">
                Verification Failed
            </h1>
            <p style="color:var(--color-text-muted); line-height:1.7; margin:0 0 8px;">
                The verification link is invalid or has already been used.
            </p>
            <p style="color:var(--color-text-muted); font-size:0.88rem; margin:0 0 32px;">
                Links expire after 48 hours. If you need a new one, please contact the site admin or register again.
            </p>

            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="/register" class="btn btn-primary magnetic" style="width:100%; justify-content:center;">
                    <?= icon('users2', '', 15) ?> Register Again
                </a>
                <a href="/" class="btn magnetic" style="width:100%; justify-content:center;">
                    <?= icon('home', '', 15) ?> Back to Forum
                </a>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
