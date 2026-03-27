<?php
declare(strict_types=1);

require_once __DIR__ . '/app/includes/bootstrap.php';

$pdo = get_pdo();
$search = trim($_GET['q'] ?? '');
$categoryId = (int) ($_GET['category'] ?? 0);

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(s.title LIKE ? OR u.username LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $where[] = 'EXISTS (
        SELECT 1 FROM story_category x
        WHERE x.story_id = s.story_id AND x.category_id = ?
    )';
    $params[] = $categoryId;
}

$baseSql = story_meta_query();
if ($where) {
    $baseSql .= ' WHERE ' . implode(' AND ', $where);
}
$baseSql .= ' ORDER BY s.created_at DESC';

$latestStmt = $pdo->prepare($baseSql . ' LIMIT 9');
$latestStmt->execute($params);
$latestStories = $latestStmt->fetchAll();

$featuredStories = $pdo->query(
    story_meta_query() . ' ORDER BY view_count DESC, s.created_at DESC LIMIT 4'
)->fetchAll();

$rankingStories = $pdo->query(
    story_meta_query() . ' ORDER BY avg_rating DESC, view_count DESC, s.created_at DESC LIMIT 7'
)->fetchAll();

$categories = fetch_categories($pdo);

render_site_header('Trang chủ', 'home');
?>
<section class="hero-grid">
    <div class="hero-panel">
        <p class="eyebrow">Thư viện trực tuyến</p>
        <h1>Khám phá kho sách khổng lồ bên trong Tủ Sách Nhỏ</h1>
        <div class="hero-actions">
            <a href="#latest" class="primary-btn">Khám phá truyện mới</a>
            <?php if (is_admin()): ?>
                <a href="admin/index.php" class="ghost-btn">Đến khu quản trị</a>
            <?php endif; ?>
        </div>
    </div>
    <aside class="hero-side-card" id="ranking">
        <div class="section-head compact">
            <h2>Bảng xếp hạng</h2>
            <span>Top nổi bật</span>
        </div>
        <div class="ranking-list">
            <?php foreach ($rankingStories as $index => $story): ?>
                <a class="ranking-item" href="story.php?id=<?= (int) $story['story_id'] ?>">
                    <span class="ranking-index"><?= $index + 1 ?></span>
                    <?php render_cover($story['cover_image'], $story['title'], '', 'mini-cover'); ?>
                    <div>
                        <strong><?= h($story['title']) ?></strong>
                        <p><?= h($story['author_name'] ?: 'Ẩn danh') ?></p>
                        <small><?= h(number_format((float) $story['avg_rating'], 1)) ?> sao • <?= (int) $story['view_count'] ?> lượt xem</small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>
</section>

<section class="section-block">
    <div class="section-head">
        <h2>Truyện đề cử</h2>
    </div>
    <div class="featured-grid">
        <?php foreach ($featuredStories as $story): ?>
            <article class="featured-card">
                <?php render_cover($story['cover_image'], $story['title']); ?>
                <div class="featured-card-content">
                    <div class="chip-row">
                        <span class="status-chip"><?= h(story_status_label($story['status'])) ?></span>
                        <span class="soft-chip"><?= (int) $story['chapter_count'] ?> chương</span>
                    </div>
                    <h3><a href="story.php?id=<?= (int) $story['story_id'] ?>"><?= h($story['title']) ?></a></h3>
                    <p class="muted">By <?= h($story['author_name'] ?: 'Ẩn danh') ?></p>
                    <p><?= h(excerpt($story['description'], 145)) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section-block" id="genres">
    <div class="section-head">
        <h2>Thể loại</h2>
    </div>
    <div class="genre-cloud">
        <a class="<?= $categoryId === 0 ? 'active' : '' ?>" href="index.php">Tất cả</a>
        <?php foreach ($categories as $category): ?>
            <a class="<?= $categoryId === (int) $category['category_id'] ? 'active' : '' ?>" href="index.php?category=<?= (int) $category['category_id'] ?>">
                <?= h($category['category_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section-block" id="latest">
    <div class="section-head">
        <h2>Danh sách truyện mới nhất</h2>
        <span><?= $search !== '' ? 'Kết quả tìm kiếm cho: ' . h($search) : 'Các truyện mới cập nhật' ?></span>
    </div>
    <div class="story-grid">
        <?php foreach ($latestStories as $story): ?>
            <article class="story-card">
                <?php render_cover($story['cover_image'], $story['title']); ?>
                <div class="story-card-body">
                    <div class="chip-row">
                        <?php foreach (array_filter(explode(', ', (string) $story['category_names'])) as $categoryName): ?>
                            <span class="soft-chip"><?= h($categoryName) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <h3><a href="story.php?id=<?= (int) $story['story_id'] ?>"><?= h($story['title']) ?></a></h3>
                    <p class="muted">By <?= h($story['author_name'] ?: 'Ẩn danh') ?></p>
                    <p><?= h(excerpt($story['description'])) ?></p>
                    <div class="card-meta">
                        <span><?= (int) $story['chapter_count'] ?> chương</span>
                        <span><?= h(number_format((float) $story['avg_rating'], 1)) ?> sao</span>
                        <span><?= (int) $story['view_count'] ?> lượt xem</span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$latestStories): ?>
        <div class="empty-state">Chưa có truyện phù hợp với bộ lọc hiện tại.</div>
    <?php endif; ?>
</section>
<?php
render_site_footer();
