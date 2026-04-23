<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

<div class="container" style="max-width: 640px; margin: 60px auto;">

    <div class="auth-card animate-fall-in">
        <div class="auth-header">
            <div class="auth-logo">Anti<span>Gravity</span></div>
            <h1>Welcome Back</h1>
            <p>Sign in to continue the discussion</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="auth-alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login" class="auth-form" novalidate>
            <?= \Core\Middleware::csrfField() ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required autocomplete="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Your password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password')" title="Show/hide password">👁</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary magnetic auth-submit">Sign In →</button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="/register">Create one free</a></p>
        </div>
    </div>
</div>

<style>
.auth-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 20px; padding: 48px; box-shadow: 0 24px 80px rgba(0,0,0,.4); }
.auth-header { text-align: center; margin-bottom: 36px; }
.auth-logo { font-family: var(--font-hero); font-size: 1.4rem; font-weight: 800; color: var(--color-violet); margin-bottom: 20px; }
.auth-logo span { color: var(--color-cyan); }
.auth-header h1 { font-size: 1.8rem; margin-bottom: 8px; }
.auth-header p { color: var(--color-text-muted); margin: 0; font-size: 0.9rem; }
.auth-alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 0.9rem; font-weight: 500; }
.alert-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #ef4444; }
.alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #10b981; }
.auth-form { display: flex; flex-direction: column; gap: 20px; }
.form-group label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 8px; color: var(--color-text-muted); }
.password-wrap { position: relative; }
.password-wrap .form-control { padding-right: 44px; }
.toggle-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--color-text-muted); padding: 0; line-height: 1; }
.auth-submit { width: 100%; font-size: 1rem; padding: 14px; justify-content: center; }
.auth-footer { text-align: center; margin-top: 28px; font-size: 0.88rem; color: var(--color-text-muted); }
.auth-footer a { color: var(--color-violet); font-weight: 600; text-decoration: none; }
.auth-footer a:hover { text-decoration: underline; }

@media (max-width: 640px) {
    .auth-card { padding: 28px 20px; border-radius: 16px; }
}
</style>

<script>
function togglePw(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
</script>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
