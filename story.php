<?php
declare(strict_types=1);

require_once __DIR__ . '/app/includes/bootstrap.php';

$pdo = get_pdo();
$storyId = (int) ($_GET['id'] ?? 0);
$story = fetch_story($pdo, $storyId);

if (!$story) {
    set_flash('danger', 'Không tìm thấy truyện yêu cầu.');
    redirect('index.php');
}

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? '';

        if (!$user) {
            throw new RuntimeException('Vui lòng đăng nhập để thực hiện thao tác này.');
        }

        if ($action === 'toggle_favorite') {
            $check = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ? AND story_id = ?');
            $check->execute([$user['user_id'], $storyId]);

            if ((int) $check->fetchColumn() > 0) {
                $delete = $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND story_id = ?');
                $delete->execute([$user['user_id'], $storyId]);
                set_flash('success', 'Đã bỏ truyện khỏi tủ sách cá nhân.');
            } else {
                $insert = $pdo->prepare('INSERT INTO favorites (user_id, story_id, created_at) VALUES (?, ?, NOW())');
                $insert->execute([$user['user_id'], $storyId]);
                set_flash('success', 'Đã lưu truyện vào tủ sách cá nhân.');
            }
        }

        if ($action === 'rate_story') {
            $rating = (int) ($_POST['rating'] ?? 0);
            if ($rating < 1 || $rating > 5) {
                throw new RuntimeException('Điểm đánh giá phải từ 1 đến 5.');
            }

            $check = $pdo->prepare('SELECT rating_id FROM ratings WHERE user_id = ? AND story_id = ? LIMIT 1');
            $check->execute([$user['user_id'], $storyId]);
            $ratingId = $check->fetchColumn();

            if ($ratingId) {
                $stmt = $pdo->prepare('UPDATE ratings SET rating = ?, created_at = NOW() WHERE rating_id = ?');
                $stmt->execute([$rating, $ratingId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO ratings (user_id, story_id, rating, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$user['user_id'], $storyId, $rating]);
            }

            set_flash('success', 'Cảm ơn bạn đã đánh giá truyện.');
        }

        redirect('story.php?id=' . $storyId);
    } catch (Throwable $exception) {
        set_flash('danger', $exception->getMessage());
        redirect('story.php?id=' . $storyId);
    }
}

$chaptersStmt = $pdo->prepare(
    'SELECT chapter_id, chapter_number, title, created_at
     FROM chapters
     WHERE story_id = ?
     ORDER BY chapter_number ASC'
);
$chaptersStmt->execute([$storyId]);
$chapters = $chaptersStmt->fetchAll();

$commentsStmt = $pdo->prepare(
    'SELECT c.comment_id, c.content, c.created_at, u.username, ch.chapter_number
     FROM comments c
     INNER JOIN users u ON u.user_id = c.user_id
     INNER JOIN chapters ch ON ch.chapter_id = c.chapter_id
     WHERE ch.story_id = ?
     ORDER BY c.created_at DESC
     LIMIT 8'
);
$commentsStmt->execute([$storyId]);
$comments = $commentsStmt->fetchAll();

$isFavorite = false;
if ($user) {
    $favoriteStmt = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ? AND story_id = ?');
    $favoriteStmt->execute([$user['user_id'], $storyId]);
    $isFavorite = (int) $favoriteStmt->fetchColumn() > 0;
}

$userRating = 0;
if ($user) {
    $ratingStmt = $pdo->prepare('SELECT rating FROM ratings WHERE user_id = ? AND story_id = ? LIMIT 1');
    $ratingStmt->execute([$user['user_id'], $storyId]);
    $userRating = (int) ($ratingStmt->fetchColumn() ?: 0);
}

render_site_header($story['title']);
?>
<section class="detail-layout">
    <article class="detail-card">
        <div class="detail-cover-wrap">
            <?php render_cover($story['cover_image'], $story['title'], '', 'detail-cover'); ?>
        </div>
        <div class="detail-content">
            <p class="eyebrow">Thông tin truyện</p>
            <h1><?= h($story['title']) ?></h1>
            <p class="detail-author">Tác giả tài khoản: <?= h($story['author_name'] ?: 'Ẩn danh') ?></p>
            <div class="chip-row">
                <span class="status-chip"><?= h(story_status_label($story['status'])) ?></span>
                <span class="soft-chip"><?= (int) $story['chapter_count'] ?> chương</span>
                <span class="soft-chip"><?= (int) $story['view_count'] ?> lượt xem</span>
                <span class="soft-chip"><?= h(number_format((float) $story['avg_rating'], 1)) ?> sao</span>
            </div>
            <p class="detail-description"><?= nl2br(h($story['description'] ?: 'Chưa có mô tả truyện.')) ?></p>
            <div class="detail-actions">
                <?php if ($chapters): ?>
                    <a href="chapter.php?id=<?= (int) $chapters[0]['chapter_id'] ?>" class="primary-btn">Đọc từ đầu</a>
                    <?php $latestChapter = $chapters[count($chapters) - 1]; ?>
                    <a href="chapter.php?id=<?= (int) $latestChapter['chapter_id'] ?>" class="ghost-btn">Đọc chương mới nhất</a>
                <?php endif; ?>
            </div>
            <?php if ($user): ?>
                <div class="interaction-panel">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_favorite">
                        <button type="submit" class="ghost-btn wide-btn"><?= $isFavorite ? 'Bỏ theo dõi truyện' : 'Theo dõi truyện' ?></button>
                    </form>
                    <form method="post" class="rating-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="rate_story">
                        <label>
                            Đánh giá của bạn
                            <select name="rating">
                                <?php for ($star = 5; $star >= 1; $star--): ?>
                                    <option value="<?= $star ?>" <?= $userRating === $star ? 'selected' : '' ?>><?= $star ?> sao</option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <button type="submit" class="primary-btn">Gửi đánh giá</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </article>

    <aside class="sidebar-card">
        <div class="section-head compact">
            <h2>Danh sách chương</h2>
            <span>Cập nhật mới nhất</span>
        </div>
        <div class="chapter-list">
            <?php foreach ($chapters as $chapter): ?>
                <a href="chapter.php?id=<?= (int) $chapter['chapter_id'] ?>" class="chapter-item">
                    <strong>Chương <?= (int) $chapter['chapter_number'] ?></strong>
                    <p><?= h($chapter['title'] ?: 'Không có tiêu đề') ?></p>
                    <small><?= h(format_date($chapter['created_at'])) ?></small>
                </a>
            <?php endforeach; ?>
            <?php if (!$chapters): ?>
                <div class="empty-state">Truyện này chưa có chương nào.</div>
            <?php endif; ?>
        </div>
    </aside>
</section>

<section class="section-block">
    <div class="section-head">
        <h2>Bình luận gần đây</h2>
        <span>Thảo luận theo từng chương truyện</span>
    </div>
    <div class="comment-stack">
        <?php foreach ($comments as $comment): ?>
            <article class="comment-card">
                <div class="avatar-circle"><?= h(cover_initial($comment['username'])) ?></div>
                <div>
                    <strong><?= h($comment['username']) ?></strong>
                    <p><?= h($comment['content']) ?></p>
                    <small>Chương <?= (int) $comment['chapter_number'] ?> • <?= h(format_datetime($comment['created_at'])) ?></small>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$comments): ?>
            <div class="empty-state">Chưa có bình luận nào cho truyện này.</div>
        <?php endif; ?>
    </div>
</section>
<?php
render_site_footer();
