<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

<div class="content-left" style="grid-column: span 2;">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo;
        <a href="/category/<?= htmlspecialchars($thread['category_slug']) ?>"><?= htmlspecialchars($thread['category_name']) ?></a> &rsaquo;
        <span><?= htmlspecialchars(mb_substr($thread['title'], 0, 60)) ?><?= strlen($thread['title']) > 60 ? '…' : '' ?></span>
    </nav>

    <div class="thread-header animate-fall-in">
        <h1><?= htmlspecialchars($thread['title']) ?></h1>
        <div class="thread-meta-row">
            <span>By <a href="/u/<?= htmlspecialchars($thread['username']) ?>" class="user-link"><?= htmlspecialchars($thread['username']) ?></a></span>
            <span class="sep">·</span>
            <span><?= date('F j, Y', strtotime($thread['created_at'])) ?></span>
            <span class="sep">·</span>
            <span><?= number_format($thread['reply_count'] ?? 0) ?> replies</span>
            <span class="sep">·</span>
            <span><?= number_format($thread['views'] ?? 0) ?> views</span>
            <?php if ($thread['is_locked'] ?? false): ?>
                <span class="badge-locked">🔒 Locked</span>
            <?php endif; ?>
            <?php if ($thread['is_pinned'] ?? false): ?>
                <span class="badge-pinned">📌 Pinned</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="post-list">
        <?php foreach ($posts as $index => $post): ?>
            <article class="thread-card post-card animate-rise-in" id="post-<?= $post['id'] ?>" style="animation-delay:<?= $index * 40 ?>ms">
                <div class="post-header">
                    <a href="/u/<?= htmlspecialchars($post['username']) ?>" class="post-avatar-link">
                        <?php if (!empty($post['avatar'])): ?>
                            <img src="<?= htmlspecialchars($post['avatar']) ?>" alt="<?= htmlspecialchars($post['username']) ?>" class="post-avatar-img">
                        <?php else: ?>
                            <div class="post-avatar-initials"><?= strtoupper(substr($post['username'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </a>
                    <div class="post-user-info">
                        <div class="post-user-info-top">
                            <a href="/u/<?= htmlspecialchars($post['username']) ?>" class="post-username"><?= htmlspecialchars($post['username']) ?></a>
                            <span class="post-trust-badge">Lvl <?= (int)$post['trust_level'] ?></span>
                        </div>
                        <div class="post-user-stats">
                            <span class="post-timestamp"><?= date('M j, Y \a\t g:i A', strtotime($post['created_at'])) ?></span>
                            <span class="sep">·</span>
                            <span class="post-rep">⭐ <?= number_format((int)$post['reputation']) ?></span>
                            <span class="sep">·</span>
                            <span class="post-count">📝 <?= number_format((int)$post['post_count']) ?></span>
                        </div>
                    </div>
                    <div class="post-number">#<?= $index + 1 ?></div>
                </div>
                
                <div class="post-body">
                    <div class="post-content-body">
                        <?= \Core\Markdown::render($post['content']) ?>
                    </div>
                    <div class="post-actions">
                        <?php if (\Core\Auth::check()): ?>
                            <button class="post-action-btn magnetic" onclick="quoteReply(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['username'])) ?>')">💬 Quote</button>
                        <?php endif; ?>
                        <a href="#post-<?= $post['id'] ?>" class="post-action-btn">🔗 Link</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (empty($posts)): ?>
            <div class="empty-state">No posts yet.</div>
        <?php endif; ?>
    </div>

    <?php if (\Core\Auth::check() && !($thread['is_locked'] ?? false)): ?>
        <div class="thread-card reply-box animate-rise-in" style="margin-top: 40px; animation-delay: 300ms;">
            <h3>Post a Reply</h3>
            <form method="POST" action="/thread/<?= htmlspecialchars($thread['slug']) ?>/reply" id="reply-form">
                <?= \Core\Middleware::csrfField() ?>
                <div class="editor-wrap">
                    <textarea name="content" id="post-editor" class="form-control rich-editor" required rows="6" placeholder="Write your reply… Markdown is supported."></textarea>
                </div>
                <div class="reply-submit-row">
                    <div class="reply-char-count"><span id="char-count">0</span> characters</div>
                    <button type="submit" class="btn btn-primary magnetic">Post Reply →</button>
                </div>
            </form>
        </div>
    <?php elseif ($thread['is_locked'] ?? false): ?>
        <div class="thread-card locked-notice animate-rise-in" style="margin-top: 32px; text-align: center; padding: 32px; animation-delay: 300ms;">
            <div style="font-size: 2.5rem; margin-bottom: 12px;">🔒</div>
            <p style="color: var(--color-text-muted);">This thread is locked. No new replies can be posted.</p>
        </div>
    <?php else: ?>
        <div class="thread-card animate-rise-in" style="margin-top: 32px; text-align: center; padding: 32px; animation-delay: 300ms;">
            <p style="color: var(--color-text-muted); margin-bottom: 16px;">You must be logged in to reply.</p>
            <a href="/login" class="btn btn-primary magnetic">Log In to Reply</a>
        </div>
    <?php endif; ?>
</div>

<style>
/* Moved to components.css for better caching and structure */
</style>

<script>
function quoteReply(postId, username) {
    const postEl = document.getElementById('post-' + postId);
    if (!postEl) return;
    const bodyEl = postEl.querySelector('.post-content-body');
    if (!bodyEl) return;
    const text = bodyEl.innerText.trim().substring(0, 300);
    const quoted = `> **@${username}:**\n> ${text.split('\n').join('\n> ')}\n\n`;
    
    if (window.editorInstance) {
        const current = window.editorInstance.value();
        window.editorInstance.value(quoted + current);
        window.editorInstance.codemirror.focus();
        
        // Scroll to editor
        const wrap = document.querySelector('.editor-wrap');
        if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Add loading state on submit
const replyForm = document.getElementById('reply-form');
if (replyForm) {
    replyForm.addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        if (btn) btn.classList.add('loading');
    });
}
</script>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
