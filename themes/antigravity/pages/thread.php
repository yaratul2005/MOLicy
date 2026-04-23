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
.post-card { display: flex; flex-direction: column; gap: 20px; padding: 28px; margin-bottom: 24px; border-radius: var(--radius-md); background: var(--color-surface-1); border: 1px solid var(--color-border); }
.post-header { display: flex; align-items: center; gap: 16px; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 16px; position: relative; }
.post-avatar-img, .post-avatar-initials { width: 52px; height: 52px; border-radius: 50%; border: 2px solid var(--color-border); }
.post-avatar-img { object-fit: cover; }
.post-avatar-initials { background: linear-gradient(135deg, var(--color-violet), var(--color-cyan)); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; color: white; }
.post-user-info { display: flex; flex-direction: column; gap: 4px; flex: 1; }
.post-user-info-top { display: flex; align-items: center; gap: 10px; }
.post-username { font-weight: 700; color: var(--color-text-main); font-size: 1.05rem; text-decoration: none; }
.post-username:hover { color: var(--color-cyan); }
.post-trust-badge { background: rgba(99,102,241,0.15); color: var(--color-violet); padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.post-user-stats { font-size: 0.8rem; color: var(--color-text-muted); display: flex; align-items: center; gap: 8px; }
.post-number { font-family: var(--font-code); font-size: 0.85rem; color: rgba(255,255,255,0.15); position: absolute; right: 0; top: 0; font-weight: 600; }

.post-body { padding-top: 4px; }
.post-content-body { font-size: 1.05rem; line-height: 1.8; color: var(--color-text-main); word-break: break-word; font-family: var(--font-body); }
.post-content-body p { margin-top: 0; margin-bottom: 1.2em; }
.post-content-body p:last-child { margin-bottom: 0; }
.post-content-body pre { background: var(--color-void); border: 1px solid rgba(255,255,255,0.03); padding: 16px; border-radius: 12px; overflow-x: auto; margin-bottom: 1.2em; }
.post-content-body code { font-family: var(--font-code); font-size: 0.9em; color: var(--color-cyan); }
.post-content-body pre code { color: #e2e8f0; }
.post-content-body blockquote { border-left: 4px solid var(--color-violet); background: rgba(255,255,255,0.02); padding: 12px 20px; margin: 0 0 1.2em 0; border-radius: 0 8px 8px 0; color: var(--color-text-muted); font-style: italic; }
.post-content-body img { max-width: 100%; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }

.post-actions { display: flex; gap: 10px; margin-top: 24px; }
.post-action-btn { font-size: 0.85rem; font-weight: 600; padding: 6px 14px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); color: var(--color-text-muted); cursor: pointer; text-decoration: none; transition: all 200ms var(--spring-bounce); }
.post-action-btn:hover { border-color: var(--color-violet); color: var(--color-text-main); background: rgba(99,102,241,0.1); transform: translateY(-2px); }

/* Reply box */
.reply-box { padding: 28px; }
.reply-box h3 { margin-bottom: 20px; color: var(--color-cyan); }
.editor-wrap { margin-bottom: 16px; }
.editor-toolbar { background: var(--color-surface-2); padding: 10px; border-radius: 8px 8px 0 0; border: 1px solid var(--color-border); border-bottom: none; display: flex; gap: 8px; flex-wrap: wrap; }
.reply-submit-row { display: flex; justify-content: space-between; align-items: center; }
.reply-char-count { font-size: 0.8rem; color: var(--color-text-muted); }

/* Mobile */
@media (max-width: 768px) {
    .thread-header h1 { font-size: 1.3rem; }
    .post-card { padding: 20px; }
    .post-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .post-user-info-top { flex-wrap: wrap; }
    .post-number { position: relative; }
    .reply-submit-row { flex-direction: column; gap: 12px; align-items: stretch; }
    .reply-submit-row .btn { width: 100%; justify-content: center; }
}
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
