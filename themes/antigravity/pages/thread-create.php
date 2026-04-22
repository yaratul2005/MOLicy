<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

<div class="container" style="max-width: 800px; margin: 40px auto;">
    <div class="thread-header animate-fall-in" style="margin-bottom: 20px;">
        <h1 style="color: var(--color-cyan);">Start New Thread</h1>
    </div>

    <div class="thread-card animate-rise-in" style="padding: 30px;">
        <form method="POST" action="/thread/create">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--color-text-muted);">Category</label>
                <select name="category_id" class="form-control" required style="appearance: none; background-color: var(--color-surface-2);">
                    <option value="">Select a category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--color-text-muted);">Title</label>
                <input type="text" name="title" class="form-control" required placeholder="What do you want to discuss?">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--color-text-muted);">Content</label>
                
                <!-- Rich Editor Toolbar -->
                <div class="editor-toolbar" style="background: var(--color-surface-2); padding: 10px; border-radius: var(--radius-sm) var(--radius-sm) 0 0; border: 1px solid var(--color-border); border-bottom: none; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="bold" style="padding: 5px 10px; font-size: 0.9rem;">B</button>
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="italic" style="padding: 5px 10px; font-size: 0.9rem; font-style: italic;">I</button>
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="link" style="padding: 5px 10px; font-size: 0.9rem;">Link</button>
                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="image" style="padding: 5px 10px; font-size: 0.9rem;">Image</button>
                </div>
                
                <textarea name="content" id="post-editor" class="form-control" required rows="10" placeholder="Type your message here... You can use Markdown." style="border-radius: 0 0 var(--radius-sm) var(--radius-sm); font-family: var(--font-code); resize: vertical;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary magnetic" style="width: 100%; font-size: 1.1rem; padding: 15px;">Post Thread</button>
        </form>
    </div>
</div>

<script src="/themes/antigravity/assets/js/editor.js"></script>
<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
