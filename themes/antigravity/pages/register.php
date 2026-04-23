<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

<div class="container" style="max-width: 500px; margin: 60px auto;">
    <div class="thread-card animate-fall-in">
        <h2 style="text-align: center; color: var(--color-cyan); margin-bottom: 20px;">Join AntiGravity</h2>
        <form method="POST" action="/register">
            <?= \Core\Middleware::csrfField() ?>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: var(--color-text-muted);">Username</label>
                <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]{3,20}" title="3-20 characters, alphanumeric and underscore">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: var(--color-text-muted);">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; color: var(--color-text-muted);">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary magnetic" style="width: 100%;">Create Account</button>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <p style="color: var(--color-text-muted); font-size: 0.9rem;">Already have an account? <a href="/login" style="color: var(--color-violet);">Log In</a></p>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
