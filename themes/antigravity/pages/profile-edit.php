<?php
/**
 * Edit Profile View
 * @var array $user  The current user's data
 */
$pageTitle = 'Edit Profile - AntiGravity Forum';
require ROOT_PATH . '/themes/antigravity/partials/header.php';
?>

<div class="edit-profile-wrapper">
    <div class="panel edit-panel magnetic-container">
        <h1 class="panel-title">Edit Profile</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Profile updated successfully!</div>
        <?php endif; ?>

        <form action="/profile/update" method="POST" enctype="multipart/form-data" class="edit-form">
            <?= \Core\Middleware::csrfField() ?>
            
            <div class="form-row avatar-upload">
                <div class="avatar-preview" id="avatar-preview">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <span class="initials"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="upload-controls">
                    <label for="avatar_input" class="btn btn-secondary magnetic">Change Avatar</label>
                    <input type="file" id="avatar_input" name="avatar" accept="image/png, image/jpeg, image/webp" style="display:none;">
                    <p class="help-text">JPG, PNG or WebP. Max 2MB.</p>
                </div>
            </div>

            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea name="bio" id="bio" rows="4" maxlength="500"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                <div class="char-counter"><span id="bio-count">0</span>/500</div>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" name="location" id="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" maxlength="100">
            </div>

            <div class="form-group">
                <label for="website">Website</label>
                <input type="url" name="website" id="website" value="<?= htmlspecialchars($user['website'] ?? '') ?>" placeholder="https://">
            </div>

            <div class="form-group">
                <label for="github">GitHub Username</label>
                <input type="text" name="github" id="github" value="<?= htmlspecialchars($user['github'] ?? '') ?>">
            </div>

            <div class="form-actions">
                <a href="/u/<?= htmlspecialchars($user['username']) ?>" class="btn btn-text">Cancel</a>
                <button type="submit" class="btn btn-primary magnetic">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
.edit-profile-wrapper {
    max-width: 600px;
    margin: 0 auto;
    animation: slideUp 0.6s var(--spring-bounce) backwards;
}
.edit-panel {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 16px;
    padding: 32px;
}
.panel-title {
    font-size: 1.8rem;
    margin-bottom: 24px;
    color: var(--color-primary);
}
.avatar-upload {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 32px;
}
.avatar-preview {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: var(--color-border);
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 700;
}
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
.help-text { font-size: 0.8rem; color: var(--color-muted); margin-top: 8px; }
.form-group { margin-bottom: 24px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--color-muted); }
.form-group input, .form-group textarea {
    width: 100%; padding: 12px 16px;
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--color-border);
    border-radius: 10px;
    color: var(--color-text-main);
    font-family: inherit;
    transition: all 0.3s var(--spring-smooth);
}
.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
}
.char-counter { text-align: right; font-size: 0.75rem; color: var(--color-muted); margin-top: 4px; }
.form-actions {
    display: flex; justify-content: flex-end; gap: 16px;
    margin-top: 32px; padding-top: 24px;
    border-top: 1px solid var(--color-border);
}
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid #10b981; }
</style>

<script>
document.getElementById('avatar_input').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(this.files[0]);
    }
});
const bio = document.getElementById('bio');
const count = document.getElementById('bio-count');
bio.addEventListener('input', () => { count.textContent = bio.value.length; });
count.textContent = bio.value.length;
</script>

<?php require ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
