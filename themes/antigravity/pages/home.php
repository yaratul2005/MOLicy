<?php include ROOT_PATH . '/themes/antigravity/partials/header.php'; ?>

            <div class="content-left">
                <section class="hero parallax" data-speed="0.3" style="margin-bottom: 40px;">
                    <h1 class="animate-fall-in">Explore the Universe of Knowledge</h1>
                    <p class="animate-fall-in stagger-1" style="color: var(--color-text-muted); font-size: 1.2rem;">Join the discussion and discover new ideas.</p>
                </section>

                <div class="category-grid">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <a href="/category/<?= htmlspecialchars($cat['slug']) ?>" class="category-card animate-rise-in" style="animation-delay: <?= $index * 100 ?>ms; text-decoration: none;">
                                <h3 style="color: var(--color-cyan); margin-bottom: 10px;"><?= htmlspecialchars($cat['name']) ?></h3>
                                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;"><?= htmlspecialchars($cat['description']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No categories found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="sidebar">
                <div class="widget thread-card animate-fall-in stagger-2">
                    <h3 style="margin-bottom: 15px;">Trending Tags</h3>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <span class="badge badge-primary magnetic">#php8</span>
                        <span class="badge badge-success magnetic">#motion</span>
                        <span class="badge badge-primary magnetic">#css</span>
                    </div>
                </div>
            </aside>

<?php include ROOT_PATH . '/themes/antigravity/partials/footer.php'; ?>
