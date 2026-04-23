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
                <div class="post-sidebar">
                    <a href="/u/<?= htmlspecialchars($post['username']) ?>" class="post-avatar-link">
                        <?php if (!empty($post['avatar'])): ?>
                            <img src="<?= htmlspecialchars($post['avatar']) ?>" alt="<?= htmlspecialchars($post['username']) ?>" class="post-avatar-img">
                        <?php else: ?>
                            <div class="post-avatar-initials"><?= strtoupper(substr($post['username'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </a>
                    <a href="/u/<?= htmlspecialchars($post['username']) ?>" class="post-username"><?= htmlspecialchars($post['username']) ?></a>
                    <span class="post-trust">Level <?= (int)$post['trust_level'] ?></span>
                    <span class="post-rep">⭐ <?= number_format((int)$post['reputation']) ?> rep</span>
                    <span class="post-count">📝 <?= number_format((int)$post['post_count']) ?> posts</span>
                </div>
                <div class="post-body">
                    <div class="post-body-meta">
                        <span class="post-timestamp"><?= date('M j, Y \a\t g:i A', strtotime($post['created_at'])) ?></span>
                        <span class="post-number">#<?= $index + 1 ?></span>
                    </div>
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
                    <div class="editor-toolbar">
                        <button type="button" class="btn btn-sm editor-btn magnetic" data-command="bold"><b>B</b></button>
                        <button type="button" class="btn btn-sm editor-btn magnetic" data-command="italic"><i>I</i></button>
                        <button type="button" class="btn btn-sm editor-btn magnetic" data-command="code">&#60;/&#62;</button>
                        <button type="button" class="btn btn-sm editor-btn magnetic" data-command="link">🔗</button>
                        <button type="button" class="btn btn-sm editor-btn magnetic" data-command="image">🖼️</button>
                    </div>
                    <textarea name="content" id="post-editor" class="form-control" required rows="6" placeholder="Write your reply… Markdown is supported."></textarea>
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
.breadcrumb { font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.breadcrumb a { color: var(--color-cyan); text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

.thread-header { margin-bottom: 36px; }
.thread-header h1 { font-size: clamp(1.4rem, 3vw, 2rem); margin-bottom: 12px; line-height: 1.3; }
.thread-meta-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; font-size: 0.85rem; color: var(--color-text-muted); }
.thread-meta-row .user-link { color: var(--color-amber); font-weight: 600; text-decoration: none; }
.thread-meta-row .user-link:hover { text-decoration: underline; }
.sep { opacity: 0.4; }
.badge-locked, .badge-pinned { font-size: 0.75rem; padding: 2px 8px; border-radius: 20px; }
.badge-locked { background: rgba(239,68,68,.15); color: #ef4444; }
.badge-pinned { background: rgba(245,158,11,.15); color: var(--color-amber); }

/* Post card layout */
.post-card { display: flex; gap: 24px; padding: 24px; margin-bottom: 20px; }
.post-sidebar { min-width: 120px; max-width: 140px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; padding-right: 20px; border-right: 1px solid var(--color-border); }
.post-avatar-img, .post-avatar-initials { width: 72px; height: 72px; border-radius: 50%; margin-bottom: 4px; }
.post-avatar-img { object-fit: cover; border: 2px solid var(--color-border); }
.post-avatar-initials { background: linear-gradient(135deg, var(--color-violet), var(--color-cyan)); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 800; color: white; border: 2px solid var(--color-border); }
.post-avatar-link { text-decoration: none; }
.post-username { font-weight: 700; color: var(--color-amber); font-size: 0.9rem; text-decoration: none; }
.post-username:hover { text-decoration: underline; }
.post-trust, .post-rep, .post-count { font-size: 0.72rem; color: var(--color-text-muted); }

.post-body { flex: 1; min-width: 0; }
.post-body-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--color-text-muted); margin-bottom: 16px; }
.post-number { font-family: var(--font-code); }
.post-content-body { font-size: 1rem; line-height: 1.75; word-break: break-word; }
.post-content-body p { margin-top: 0; margin-bottom: 1em; }
.post-content-body pre { background: var(--color-surface-2); padding: 12px 16px; border-radius: 8px; overflow-x: auto; }
.post-content-body code { font-family: var(--font-code); font-size: 0.88em; }
.post-content-body blockquote { border-left: 3px solid var(--color-violet); margin-left: 0; padding: 8px 16px; color: var(--color-text-muted); font-style: italic; }
.post-actions { display: flex; gap: 8px; margin-top: 20px; padding-top: 12px; border-top: 1px solid var(--color-border); }
.post-action-btn { font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--color-text-muted); cursor: pointer; text-decoration: none; transition: all 180ms var(--spring-smooth); }
.post-action-btn:hover { border-color: var(--color-violet); color: var(--color-text); }

/* Reply box */
.reply-box { padding: 28px; }
.reply-box h3 { margin-bottom: 20px; color: var(--color-cyan); }
.editor-wrap { margin-bottom: 16px; }
.editor-toolbar { background: var(--color-surface-2); padding: 10px; border-radius: 8px 8px 0 0; border: 1px solid var(--color-border); border-bottom: none; display: flex; gap: 8px; flex-wrap: wrap; }
.reply-submit-row { display: flex; justify-content: space-between; align-items: center; }
.reply-char-count { font-size: 0.8rem; color: var(--color-text-muted); }

/* Mobile */
@media (max-width: 768px) {
    .post-card { flex-direction: column; gap: 16px; }
    .post-sidebar { flex-direction: row; max-width: 100%; padding-right: 0; padding-bottom: 16px; border-right: none; border-bottom: 1px solid var(--color-border); flex-wrap: wrap; justify-content: flex-start; align-items: center; gap: 12px; }
    .post-avatar-img, .post-avatar-initials { width: 44px; height: 44px; margin-bottom: 0; flex-shrink: 0; }
    .post-avatar-initials { font-size: 1.1rem; }
    .post-sidebar > *:not(.post-avatar-link):not(.post-avatar-img) { }
    .thread-header h1 { font-size: 1.3rem; }
    .reply-submit-row { flex-direction: column; gap: 12px; align-items: stretch; }
    .reply-submit-row .btn { width: 100%; justify-content: center; }
}
</style>

<script>
const postEditor = document.getElementById('post-editor');
if (postEditor) {
    postEditor.addEventListener('input', () => {
        document.getElementById('char-count').textContent = postEditor.value.length;
    });
}

function quoteReply(postId, username) {
    const postEl = document.getElementById('post-' + postId);
    if (!postEl) return;
    const bodyEl = postEl.querySelector('.post-content-body');
    if (!bodyEl) return;
    const text = bodyEl.innerText.trim().substring(0, 300);
    const quoted = `> **@${username}:**\n> ${text.split('\n').join('\n> ')}\n\n`;
    const editor = document.getElementById('post-editor');
    if (editor) {
        editor.value = quoted + editor.value;
        editor.focus();
        editor.dispatchEvent(new Event('input'));
        editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
</script>

<script src="/themes/antigravity/assets/js/editor.js"></script>
<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
