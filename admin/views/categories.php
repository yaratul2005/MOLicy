<?php
$csrfToken = \Core\Middleware::getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Categories — ACP</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#07070d; --surface:#12121f; --surface2:#1a1a2e; --violet:#7c3aed; --cyan:#06b6d4; --text:#f1f5f9; --muted:#94a3b8; --border:rgba(255,255,255,.06); --danger:#ef4444; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding: 40px; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        h1 { font-family: 'Syne', sans-serif; color: var(--violet); margin: 0; }
        .btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface2); color: var(--text); cursor: pointer; text-decoration: none; }
        .btn-primary { background: rgba(124,58,237,.2); border-color: var(--violet); color: var(--violet); }
        .grid { display: grid; grid-template-columns: 300px 1fr; gap: 32px; align-items: start; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.85rem; color: var(--muted); margin-bottom: 6px; }
        input, select, textarea { width: 100%; padding: 10px; background: rgba(0,0,0,.2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; box-sizing: border-box; }
        .cat-list { list-style: none; padding: 0; margin: 0; }
        .cat-item { background: var(--surface2); border: 1px solid var(--border); padding: 12px 16px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; cursor: grab; }
        .cat-item:active { cursor: grabbing; border-color: var(--cyan); }
        .cat-info h4 { margin: 0; font-size: 1rem; color: var(--cyan); }
        .cat-info p { margin: 4px 0 0; font-size: 0.8rem; color: var(--muted); }
        .cat-actions button { background: none; border: none; color: var(--danger); cursor: pointer; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Category Management</h1>
        <a href="/admin" class="btn">← Back to Dashboard</a>
    </div>

    <div class="grid">
        <!-- Create Form -->
        <div class="panel">
            <h3 style="margin-top:0">Create New</h3>
            <form id="create-cat-form">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Slug (optional)</label>
                    <input type="text" name="slug">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Parent Category (optional)</label>
                    <select name="parent_id">
                        <option value="0">-- None (Root) --</option>
                        <?php foreach($cats as $c): if(!$c['parent_id']): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Create Category</button>
            </form>
        </div>

        <!-- List / Reorder -->
        <div class="panel">
            <h3 style="margin-top:0; display:flex; justify-content:space-between;">
                Categories
                <button class="btn" onclick="saveOrder()" style="font-size:0.8rem; padding:4px 12px;">Save Order</button>
            </h3>
            <ul class="cat-list" id="cat-list">
                <?php foreach($cats as $c): ?>
                    <li class="cat-item" data-id="<?= $c['id'] ?>" draggable="true">
                        <div class="cat-info">
                            <h4><?= $c['parent_id'] ? '↳ ' : '' ?><?= htmlspecialchars($c['name']) ?></h4>
                            <p>/<?= htmlspecialchars($c['slug']) ?> • <?= $c['thread_count'] ?> threads</p>
                        </div>
                        <div class="cat-actions">
                            <button onclick="deleteCat(<?= $c['id'] ?>)">🗑️</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
    // Create
    document.getElementById('create-cat-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const res = await fetch('/admin/categories/create', { method: 'POST', body: fd });
        if ((await res.json()).success) location.reload();
    });

    // Delete
    async function deleteCat(id) {
        if (!confirm('Delete category? All threads will become uncategorized.')) return;
        const fd = new URLSearchParams();
        fd.append('id', id); fd.append('_csrf_token', '<?= $csrfToken ?>');
        const res = await fetch('/admin/categories/delete', { method: 'POST', body: fd });
        if ((await res.json()).success) location.reload();
    }

    // Simple Drag & Drop Reorder
    let dragged = null;
    const list = document.getElementById('cat-list');
    list.addEventListener('dragstart', e => { dragged = e.target; e.target.style.opacity = .5; });
    list.addEventListener('dragend', e => { e.target.style.opacity = ''; });
    list.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = getDragAfterElement(list, e.clientY);
        if (afterElement == null) list.appendChild(dragged);
        else list.insertBefore(dragged, afterElement);
    });
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.cat-item:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
            else return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    async function saveOrder() {
        const order = {};
        document.querySelectorAll('.cat-item').forEach((el, index) => { order[index] = el.dataset.id; });
        await fetch('/admin/categories/reorder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= $csrfToken ?>' },
            body: JSON.stringify({ order, _csrf_token: '<?= $csrfToken ?>' })
        });
        alert('Order saved!');
    }
    </script>
</body>
</html>
