<?php 
$recaptchaEnabled = \Core\Settings::get('enable_recaptcha') === '1';
$siteKey = \Core\Settings::get('recaptcha_site_key');
include ROOT_PATH . '/themes/antigravity/partials/header.php'; 
?>

<?php if ($recaptchaEnabled && !empty($siteKey)): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<div class="container" style="max-width: 800px; margin: 40px auto;">
    <div class="thread-header animate-fall-in" style="margin-bottom: 20px;">
        <h1 style="color: var(--color-cyan);">Start New Thread</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error animate-shake" style="margin-bottom: 20px; padding: 15px; background: rgba(239,68,68,0.1); border: 1px solid var(--color-danger); border-radius: var(--radius-sm); color: var(--color-danger); display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="thread-card animate-rise-in" style="padding: 30px;">
        <form method="POST" action="/thread/create">
            <?= \Core\Middleware::csrfField() ?>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--color-text-muted);">Category</label>
                <select name="category_id" class="form-control" required style="appearance: none; background-color: var(--color-surface-2);">
                    <option value="">Select a category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (isset($old['category_id']) && $old['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--color-text-muted);">Title</label>
                <input type="text" name="title" class="form-control" required placeholder="What do you want to discuss?" value="<?= htmlspecialchars($old['title'] ?? '') ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--color-text-muted);">Content</label>
                
                <!-- Rich Editor Toolbar -->
                <div class="editor-toolbar" style="background: var(--color-surface-2); padding: 10px; border-radius: var(--radius-sm) var(--radius-sm) 0 0; border: 1px solid var(--color-border); border-bottom: none; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="bold" title="Bold"><strong>B</strong></button>
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="italic" title="Italic"><em>I</em></button>
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="link" title="Link">🔗</button>
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="image" title="Image">🖼️</button>
                </div>
                
                <textarea name="content" id="post-editor" class="form-control" required rows="10" placeholder="Type your message here..." style="border-radius: 0 0 var(--radius-sm) var(--radius-sm); font-family: var(--font-body); resize: vertical;"><?= htmlspecialchars($old['content'] ?? '') ?></textarea>
            </div>

            <?php if ($recaptchaEnabled && !empty($siteKey)): ?>
            <div style="margin-bottom: 20px;">
                <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey) ?>" data-theme="dark"></div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary magnetic" style="width: 100%; font-size: 1.1rem; padding: 15px;">Post Thread</button>
        </form>
    </div>
</div>

<script src="/themes/antigravity/assets/js/editor.js"></script>
<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
