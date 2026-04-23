<?php
http_response_code(404);
include ROOT_PATH . '/themes/antigravity/partials/header.php';
?>

<div class="container" style="max-width: 600px; margin: 80px auto; text-align: center;">
    <div class="animate-fall-in">
        <div class="not-found-number">404</div>
        <h1 class="not-found-title">Lost in Space</h1>
        <p class="not-found-desc">The page you're looking for doesn't exist, was moved, or is floating somewhere in the void.</p>
        <div class="not-found-actions">
            <a href="/" class="btn btn-primary magnetic">🏠 Go Home</a>
            <a href="/search" class="btn magnetic">🔍 Search Forum</a>
        </div>
    </div>
</div>

<style>
.not-found-number { font-family: var(--font-hero); font-size: clamp(6rem, 20vw, 10rem); font-weight: 800; background: linear-gradient(135deg, var(--color-violet), var(--color-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; margin-bottom: 16px; }
.not-found-title { font-size: clamp(1.4rem, 4vw, 2.2rem); margin-bottom: 16px; }
.not-found-desc { color: var(--color-text-muted); font-size: 1rem; max-width: 440px; margin: 0 auto 36px; line-height: 1.7; }
.not-found-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
</style>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
