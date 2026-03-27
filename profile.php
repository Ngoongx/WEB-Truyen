<?php
declare(strict_types=1);

require_once __DIR__ . '/app/includes/bootstrap.php';

require_login();

$pdo = get_pdo();
$user = current_user();

$favoritesStmt = $pdo->prepare(
    'SELECT
        s.story_id,
        s.title,
        s.cover_image,
        s.status,
        MAX(ch.chapter_number) AS chapter_count,
        MAX(f.created_at) AS saved_at
     FROM favorites f
     INNER JOIN stories s ON s.story_id = f.story_id
     LEFT JOIN chapters ch ON ch.story_id = s.story_id
     WHERE f.user_id = ?
     GROUP BY s.story_id
     ORDER BY saved_at DESC'
);
$favoritesStmt->execute([$user['user_id']]);
$favorites = $favoritesStmt->fetchAll();

$historyStmt = $pdo->prepare(
    'SELECT
        rh.read_at,
        s.story_id,
        s.title AS story_title,
        ch.chapter_id,
        ch.chapter_number,
        ch.title AS chapter_title
     FROM reading_history rh
     INNER JOIN chapters ch ON ch.chapter_id = rh.chapter_id
     INNER JOIN stories s ON s.story_id = ch.story_id
     WHERE rh.user_id = ?
     ORDER BY rh.read_at DESC
     LIMIT 10'
);
$historyStmt->execute([$user['user_id']]);
$histories = $historyStmt->fetchAll();

$ratingCountStmt = $pdo->prepare('SELECT COUNT(*) FROM ratings WHERE user_id = ?');
$ratingCountStmt->execute([$user['user_id']]);
$ratingCount = (int) $ratingCountStmt->fetchColumn();

render_site_header('Trang cá nhân', 'profile');
?>
<section class="profile-layout">
    <aside class="profile-side">
        <div class="profile-card">
            <div class="profile-avatar"><?= h(cover_initial($user['username'])) ?></div>
            <h1><?= h($user['username']) ?></h1>
            <p><?= h($user['email']) ?></p>
            <div class="profile-stats">
                <div>
                    <strong><?= count($favorites) ?></strong>
                    <span>Truyện theo dõi</span>
                </div>
                <div>
                    <strong><?= $ratingCount ?></strong>
                    <span>Đánh giá đã gửi</span>
                </div>
            </div>
            <small>Ngày tham gia: <?= h(format_date($user['created_at'])) ?></small>
        </div>
    </aside>

    <div class="profile-main">
        <section class="section-block slim">
            <div class="section-head">
                <h2>Tủ sách của tôi</h2>
                <span>Các truyện bạn đang theo dõi</span>
            </div>
            <div class="story-grid">
                <?php foreach ($favorites as $story): ?>
                    <article class="story-card">
                        <?php render_cover($story['cover_image'], $story['title']); ?>
                        <div class="story-card-body">
                            <h3><a href="story.php?id=<?= (int) $story['story_id'] ?>"><?= h($story['title']) ?></a></h3>
                            <p class="muted"><?= h(story_status_label($story['status'])) ?></p>
                            <div class="card-meta">
                                <span><?= (int) $story['chapter_count'] ?> chương</span>
                                <span>Lưu <?= h(format_date($story['saved_at'])) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$favorites): ?>
                    <div class="empty-state">Bạn chưa lưu truyện nào vào tủ sách cá nhân.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-block slim">
            <div class="section-head">
                <h2>Lịch sử đọc</h2>
                <span>Các chương bạn đã mở gần đây</span>
            </div>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Truyện</th>
                            <th>Chương</th>
                            <th>Thời gian</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($histories as $history): ?>
                            <tr>
                                <td><?= h($history['story_title']) ?></td>
                                <td>Chương <?= (int) $history['chapter_number'] ?><?= $history['chapter_title'] ? ': ' . h($history['chapter_title']) : '' ?></td>
                                <td><?= h(format_datetime($history['read_at'])) ?></td>
                                <td><a href="chapter.php?id=<?= (int) $history['chapter_id'] ?>">Đọc lại</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$histories): ?>
                            <tr>
                                <td colspan="4">Bạn chưa có lịch sử đọc nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<?php
render_site_footer();
