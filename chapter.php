<?php
declare(strict_types=1);

require_once __DIR__ . '/app/includes/bootstrap.php';

$pdo = get_pdo();
$chapterId = (int) ($_GET['id'] ?? 0);

$chapterStmt = $pdo->prepare(
    'SELECT
        ch.chapter_id,
        ch.story_id,
        ch.chapter_number,
        ch.title,
        ch.content,
        ch.created_at,
        s.title AS story_title,
        s.cover_image
     FROM chapters ch
     INNER JOIN stories s ON s.story_id = ch.story_id
     WHERE ch.chapter_id = ?'
);
$chapterStmt->execute([$chapterId]);
$chapter = $chapterStmt->fetch();

if (!$chapter) {
    set_flash('danger', 'Không tìm thấy chương truyện.');
    redirect('index.php');
}

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!$user) {
            throw new RuntimeException('Bạn cần đăng nhập để bình luận.');
        }

        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            throw new RuntimeException('Nội dung bình luận không được để trống.');
        }

        $insertComment = $pdo->prepare(
            'INSERT INTO comments (user_id, chapter_id, content, created_at)
             VALUES (?, ?, ?, NOW())'
        );
        $insertComment->execute([$user['user_id'], $chapterId, $content]);

        set_flash('success', 'Đã đăng bình luận của bạn.');
        redirect('chapter.php?id=' . $chapterId);
    } catch (Throwable $exception) {
        set_flash('danger', $exception->getMessage());
        redirect('chapter.php?id=' . $chapterId);
    }
}

$prevStmt = $pdo->prepare(
    'SELECT chapter_id FROM chapters
     WHERE story_id = ? AND chapter_number < ?
     ORDER BY chapter_number DESC LIMIT 1'
);
$prevStmt->execute([$chapter['story_id'], $chapter['chapter_number']]);
$previousChapterId = $prevStmt->fetchColumn();

$nextStmt = $pdo->prepare(
    'SELECT chapter_id FROM chapters
     WHERE story_id = ? AND chapter_number > ?
     ORDER BY chapter_number ASC LIMIT 1'
);
$nextStmt->execute([$chapter['story_id'], $chapter['chapter_number']]);
$nextChapterId = $nextStmt->fetchColumn();

$commentsStmt = $pdo->prepare(
    'SELECT c.comment_id, c.content, c.created_at, u.username
     FROM comments c
     INNER JOIN users u ON u.user_id = c.user_id
     WHERE c.chapter_id = ?
     ORDER BY c.created_at DESC'
);
$commentsStmt->execute([$chapterId]);
$comments = $commentsStmt->fetchAll();

$viewStmt = $pdo->prepare('INSERT INTO views (story_id, user_id, created_at) VALUES (?, ?, NOW())');
$viewStmt->execute([$chapter['story_id'], $user['user_id'] ?? null]);

if ($user) {
    $historyStmt = $pdo->prepare(
        'INSERT INTO reading_history (user_id, chapter_id, read_at)
         VALUES (?, ?, NOW())'
    );
    $historyStmt->execute([$user['user_id'], $chapterId]);
}

render_site_header($chapter['story_title']);
?>
<section class="reader-layout">
    <article class="reader-card">
        <div class="reader-toolbar">
            <a href="story.php?id=<?= (int) $chapter['story_id'] ?>" class="ghost-btn">Mục lục</a>
            <div class="reader-toolbar-meta">
                <span><?= h($chapter['story_title']) ?></span>
                <strong>Chương <?= (int) $chapter['chapter_number'] ?></strong>
            </div>
            <button type="button" class="theme-toggle" data-theme-toggle aria-label="Đổi giao diện đọc">◐</button>
        </div>
        <div class="reader-content">
            <h1><?= h($chapter['story_title']) ?> - Chương <?= (int) $chapter['chapter_number'] ?><?= $chapter['title'] ? ': ' . h($chapter['title']) : '' ?></h1>
            <div class="reader-text">
                <?= nl2br(h($chapter['content'] ?: 'Chương này chưa có nội dung.')) ?>
            </div>
        </div>
        <div class="reader-pagination">
            <?php if ($previousChapterId): ?>
                <a href="chapter.php?id=<?= (int) $previousChapterId ?>" class="ghost-btn">Chương trước</a>
            <?php endif; ?>
            <a href="story.php?id=<?= (int) $chapter['story_id'] ?>" class="soft-chip">Mục lục</a>
            <?php if ($nextChapterId): ?>
                <a href="chapter.php?id=<?= (int) $nextChapterId ?>" class="primary-btn">Chương sau</a>
            <?php endif; ?>
        </div>
    </article>

    <aside class="sidebar-card">
        <div class="section-head compact">
            <h2>Bình luận chương</h2>
            <span><?= count($comments) ?> bình luận</span>
        </div>
        <?php if ($user): ?>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <label>
                    Viết bình luận
                    <textarea name="content" rows="4" placeholder="Chia sẻ cảm nhận của bạn về chương này"></textarea>
                </label>
                <button type="submit" class="primary-btn wide-btn">Gửi bình luận</button>
            </form>
        <?php else: ?>
            <div class="empty-state">Đăng nhập để bình luận chương truyện.</div>
        <?php endif; ?>
        <div class="comment-stack">
            <?php foreach ($comments as $comment): ?>
                <article class="comment-card compact-comment">
                    <div class="avatar-circle"><?= h(cover_initial($comment['username'])) ?></div>
                    <div>
                        <strong><?= h($comment['username']) ?></strong>
                        <p><?= h($comment['content']) ?></p>
                        <small><?= h(format_datetime($comment['created_at'])) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </aside>
</section>
<?php
render_site_footer();
