<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

            <div class="content-left" style="grid-column: span 2;">
                <nav class="breadcrumb" style="margin-bottom: 20px; color: var(--color-text-muted);">
                    <a href="/" style="color: var(--color-cyan); text-decoration: none;">Home</a> &raquo; 
                    <a href="/category/<?= htmlspecialchars($thread['category_slug']) ?>" style="color: var(--color-cyan); text-decoration: none;"><?= htmlspecialchars($thread['category_name']) ?></a> &raquo; 
                    <?= htmlspecialchars($thread['title']) ?>
                </nav>

                <div class="thread-header animate-fall-in" style="margin-bottom: 40px;">
                    <h1 style="margin-bottom: 10px;"><?= htmlspecialchars($thread['title']) ?></h1>
                    <div class="meta" style="color: var(--color-text-muted);">
                        Started by <span style="color: var(--color-amber); font-weight: bold;"><?= htmlspecialchars($thread['username']) ?></span> on <?= date('F j, Y', strtotime($thread['created_at'])) ?>
                    </div>
                </div>

                <div class="post-list">
                    <?php foreach ($posts as $index => $post): ?>
                        <div class="thread-card animate-rise-in" style="animation-delay: <?= $index * 50 ?>ms; display: flex; gap: 20px;">
                            <div class="post-user-info" style="min-width: 150px; text-align: center; border-right: 1px solid var(--color-border); padding-right: 20px;">
                                <!-- Placeholder Avatar -->
                                <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--color-surface-2); margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-family: var(--font-hero); font-size: 2rem; color: var(--color-cyan); border: 2px solid var(--color-border);">
                                    <?= strtoupper(substr($post['username'], 0, 1)) ?>
                                </div>
                                <h3 style="font-size: 1.1rem; margin-bottom: 5px; color: var(--color-amber);"><?= htmlspecialchars($post['username']) ?></h3>
                                <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Rep: <?= $post['reputation'] ?></p>
                                <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Lvl: <?= $post['trust_level'] ?></p>
                            </div>
                            <div class="post-content" style="flex: 1;">
                                <div class="post-meta" style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 15px; display: flex; justify-content: space-between;">
                                    <span><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></span>
                                    <span>#<?= $index + 1 ?></span>
                                </div>
                                <div class="content-body" style="font-size: 1.05rem;">
                                    <?= \Core\Markdown::render($post['content']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (\Core\Auth::check()): ?>
                    <div class="thread-card animate-rise-in" style="margin-top: 40px; padding: 30px; animation-delay: 300ms;">
                        <h3 style="margin-bottom: 20px; color: var(--color-cyan);">Post a Reply</h3>
                        <form method="POST" action="/thread/<?= htmlspecialchars($thread['slug']) ?>/reply">
                            <?= \Core\Middleware::csrfField() ?>
                            <div style="margin-bottom: 20px;">
                                <!-- Rich Editor Toolbar -->
                                <div class="editor-toolbar" style="background: var(--color-surface-2); padding: 10px; border-radius: var(--radius-sm) var(--radius-sm) 0 0; border: 1px solid var(--color-border); border-bottom: none; display: flex; gap: 10px;">
                                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="bold" style="padding: 5px 10px; font-size: 0.9rem;">B</button>
                                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="italic" style="padding: 5px 10px; font-size: 0.9rem; font-style: italic;">I</button>
                                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="link" style="padding: 5px 10px; font-size: 0.9rem;">Link</button>
                                    <button type="button" class="btn btn-sm editor-btn magnetic" data-command="image" style="padding: 5px 10px; font-size: 0.9rem;">Image</button>
                                </div>
                                <textarea name="content" id="post-editor" class="form-control" required rows="6" placeholder="Write your reply here... Markdown supported." style="border-radius: 0 0 var(--radius-sm) var(--radius-sm); font-family: var(--font-code); resize: vertical;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary magnetic" style="padding: 12px 30px;">Post Reply</button>
                        </form>
                    </div>
                    <script src="/themes/antigravity/assets/js/editor.js"></script>
                <?php else: ?>
                    <div class="thread-card animate-rise-in" style="margin-top: 40px; text-align: center; animation-delay: 300ms;">
                        <p style="margin-bottom: 15px; color: var(--color-text-muted);">You must be logged in to reply to this thread.</p>
                        <a href="/login" class="btn btn-primary magnetic">Log In to Reply</a>
                    </div>
                <?php endif; ?>
            </div>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
