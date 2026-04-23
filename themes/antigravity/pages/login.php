<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

<div class="container" style="max-width: 500px; margin: 60px auto;">
    <div class="thread-card animate-fall-in">
        <h2 style="text-align: center; color: var(--color-cyan); margin-bottom: 20px;">Login</h2>
        <form method="POST" action="/login">
            <?= \Core\Middleware::csrfField() ?>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: var(--color-text-muted);">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; color: var(--color-text-muted);">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary magnetic" style="width: 100%;">Sign In</button>
        </form>
    </div>
</div>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
