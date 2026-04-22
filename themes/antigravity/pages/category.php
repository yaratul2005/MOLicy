<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

            <div class="content-left">
                <div class="category-header animate-fall-in" style="margin-bottom: 30px;">
                    <h1 style="color: var(--color-cyan);"><?= htmlspecialchars($category['name']) ?></h1>
                    <p style="color: var(--color-text-muted);"><?= htmlspecialchars($category['description']) ?></p>
                </div>

                <div class="thread-list">
                    <?php if (!empty($threads)): ?>
                        <?php foreach ($threads as $index => $thread): ?>
                            <div class="thread-card animate-rise-in" style="animation-delay: <?= $index * 50 ?>ms;">
                                <a href="/thread/<?= htmlspecialchars($thread['slug']) ?>" style="text-decoration: none;">
                                    <h2 class="title"><?= htmlspecialchars($thread['title']) ?></h2>
                                </a>
                                <div class="meta">
                                    Started by <?= htmlspecialchars($thread['username']) ?> &bull; 
                                    <?= date('M j, Y', strtotime($thread['created_at'])) ?> &bull; 
                                    <?= $thread['views'] ?> views
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="thread-card animate-fall-in">
                            <p>No threads found in this category. Be the first to start a discussion!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="sidebar">
                <a href="/thread/create?category=<?= $category['id'] ?>" class="btn btn-primary magnetic" style="width: 100%; margin-bottom: 20px; text-align: center; display: block; box-sizing: border-box;">Start New Thread</a>
            </aside>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
