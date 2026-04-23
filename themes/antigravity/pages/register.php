<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

<div class="container" style="max-width: 640px; margin: 60px auto;">

    <div class="auth-card animate-fall-in">
        <div class="auth-header">
            <div class="auth-logo">Anti<span>Gravity</span></div>
            <h1>Join the Community</h1>
            <p>Create your free account and start exploring</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="auth-alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register" class="auth-form" novalidate>
            <?= \Core\Middleware::csrfField() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required
                       pattern="[a-zA-Z0-9_]{3,20}" title="3–20 characters: letters, numbers, underscore"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="your_username" autocomplete="username">
                <span class="form-hint">3–20 characters. Letters, numbers, underscores only.</span>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-control" required
                           minlength="8" placeholder="Min. 8 characters" autocomplete="new-password">
                    <button type="button" class="toggle-pw" onclick="togglePw('password')" title="Show/hide">👁</button>
                </div>
                <div class="pw-strength-bar"><div id="pw-strength-fill"></div></div>
                <span class="form-hint" id="pw-hint">Enter a password</span>
            </div>

            <button type="submit" class="btn btn-primary magnetic auth-submit">Create Account →</button>

            <p class="auth-terms">By creating an account, you agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</p>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="/login">Sign In</a></p>
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
.auth-form { display: flex; flex-direction: column; gap: 20px; }
.form-group label { display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 8px; color: var(--color-text-muted); }
.form-hint { font-size: 0.75rem; color: var(--color-text-muted); margin-top: 5px; display: block; }
.password-wrap { position: relative; }
.password-wrap .form-control { padding-right: 44px; }
.toggle-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--color-text-muted); padding: 0; line-height: 1; }
.pw-strength-bar { height: 3px; background: var(--color-border); border-radius: 2px; margin-top: 8px; overflow: hidden; }
#pw-strength-fill { height: 100%; width: 0; border-radius: 2px; transition: width 300ms, background 300ms; }
.auth-submit { width: 100%; font-size: 1rem; padding: 14px; justify-content: center; }
.auth-terms { font-size: 0.75rem; color: var(--color-text-muted); text-align: center; margin: 0; }
.auth-terms a { color: var(--color-violet); text-decoration: none; }
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

const pwInput = document.getElementById('password');
const fill    = document.getElementById('pw-strength-fill');
const hint    = document.getElementById('pw-hint');
const levels  = [
    { max: 0,  color: '',          label: 'Enter a password' },
    { max: 5,  color: '#ef4444',   label: 'Weak' },
    { max: 8,  color: '#f59e0b',   label: 'Fair' },
    { max: 12, color: '#06b6d4',   label: 'Good' },
    { max: 999,color: '#10b981',   label: 'Strong' },
];

pwInput.addEventListener('input', () => {
    const v = pwInput.value;
    let score = v.length;
    if (/[A-Z]/.test(v)) score += 2;
    if (/[0-9]/.test(v)) score += 2;
    if (/[^A-Za-z0-9]/.test(v)) score += 4;

    const level = levels.find(l => score <= l.max) || levels[levels.length - 1];
    const pct   = Math.min(100, (score / 20) * 100);
    fill.style.width = pct + '%';
    fill.style.background = level.color;
    hint.textContent = level.label;
});
</script>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
