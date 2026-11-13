<?php
/**
 * user/browse.php - Browse APPROVED lost and found reports with search, filters and sorting.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$db = get_db();

$q        = trim(isset($_GET['q']) ? (string)$_GET['q'] : '');
$type     = isset($_GET['type']) ? (string)$_GET['type'] : '';
$category = isset($_GET['category']) ? (string)$_GET['category'] : '';
$location = trim(isset($_GET['location']) ? (string)$_GET['location'] : '');
$sort     = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'newest';
$page     = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$per_page = 12;

// ONLY approved reports are ever listed here.
$where  = ["status = 'APPROVED'"];
$params = [];

if ($type === 'LOST' || $type === 'FOUND') {
    $where[]  = 'report_type = ?';
    $params[] = $type;
}
if ($category !== '' && in_array($category, categories(), true)) {
    $where[]  = 'category = ?';
    $params[] = $category;
}
if ($location !== '') {
    $where[]  = 'location LIKE ?';
    $params[] = '%' . addcslashes($location, '%_\\') . '%';
}
if ($q !== '') {
    // every word must appear in the name, description, category or location
    foreach (preg_split('/\s+/', $q) as $word) {
        $like     = '%' . addcslashes($word, '%_\\') . '%';
        $where[]  = '(item_name LIKE ? OR description LIKE ? OR category LIKE ? OR location LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
}
$where_sql = implode(' AND ', $where);
$order_sql = ($sort === 'oldest') ? 'created_at ASC, id ASC' : 'created_at DESC, id DESC';

$count = $db->prepare("SELECT COUNT(*) FROM reports WHERE $where_sql");
$count->execute($params);
$total = (int)$count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page   = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// $per_page and $offset are integers we calculated ourselves, so they are safe here.
$list = $db->prepare(
    "SELECT id, item_name, report_type, category, location, event_date, image_path
     FROM reports WHERE $where_sql ORDER BY $order_sql LIMIT $per_page OFFSET $offset"
);
$list->execute($params);
$items = $list->fetchAll();

$page_title = 'Browse Items';
require __DIR__ . '/../includes/header.php';
?>
<h1>Browse Lost &amp; Found Items</h1>
<p class="muted">Only reports approved by the administrator are shown.</p>

<form method="get" action="<?= e(url('user/browse.php')) ?>" class="card filter-bar">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Item name or keywords">
    </div>
    <div class="form-group">
        <label for="type">Lost / Found</label>
        <select id="type" name="type">
            <option value="">All</option>
            <option value="LOST"<?= $type === 'LOST' ? ' selected' : '' ?>>Lost</option>
            <option value="FOUND"<?= $type === 'FOUND' ? ' selected' : '' ?>>Found</option>
        </select>
    </div>
    <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach (categories() as $cat): ?>
                <option value="<?= e($cat) ?>"<?= $category === $cat ? ' selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($location) ?>" placeholder="e.g. Library">
    </div>
    <div class="form-group">
        <label for="sort">Sort</label>
        <select id="sort" name="sort">
            <option value="newest"<?= $sort === 'newest' ? ' selected' : '' ?>>Newest first</option>
            <option value="oldest"<?= $sort === 'oldest' ? ' selected' : '' ?>>Oldest first</option>
        </select>
    </div>
    <div class="form-group filter-buttons">
        <button type="submit" class="btn btn-primary">Search</button>
        <a class="btn btn-outline" href="<?= e(url('user/browse.php')) ?>">Reset</a>
    </div>
</form>

<p class="muted"><?= $total ?> item<?= $total === 1 ? '' : 's' ?> found.</p>

<?php if (!$items): ?>
    <div class="card empty-state">No approved items match your search.</div>
<?php else: ?>
    <div class="item-grid">
    <?php foreach ($items as $r): ?>
        <article class="item-card">
            <div class="item-media">
                <?= report_image($r['image_path'], $r['item_name']) ?>
                <span class="tag tag-<?= e(strtolower($r['report_type'])) ?>"><?= e(type_label($r['report_type'])) ?></span>
            </div>
            <div class="item-body">
                <h3><?= e($r['item_name']) ?></h3>
                <ul class="item-meta">
                    <li><strong>Category:</strong> <?= e($r['category']) ?></li>
                    <li><strong>Location:</strong> <?= e($r['location']) ?></li>
                    <li><strong>Date:</strong> <?= e(format_date($r['event_date'])) ?></li>
                </ul>
                <a class="btn btn-primary btn-block" href="<?= e(url('user/item-details.php?id=' . (int)$r['id'])) ?>">View Details</a>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a class="page-link<?= $p === $page ? ' active' : '' ?>" href="<?= e(url('user/browse.php?' . http_build_query(array_merge($_GET, ['page' => $p])))) ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
