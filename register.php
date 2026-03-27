<?php
declare(strict_types=1);

require_once __DIR__ . '/app/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('profile.php');
}

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirmation'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            throw new RuntimeException('Vui lòng điền đầy đủ thông tin đăng ký.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email chưa đúng định dạng.');
        }

        if ($password !== $confirmPassword) {
            throw new RuntimeException('Mật khẩu xác nhận chưa khớp.');
        }

        if (mb_strlen($password) < 6) {
            throw new RuntimeException('Mật khẩu cần ít nhất 6 ký tự.');
        }

        $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $exists->execute([$username, $email]);
        if ((int) $exists->fetchColumn() > 0) {
            throw new RuntimeException('Tên đăng nhập hoặc email đã tồn tại.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password, role, created_at)
             VALUES (?, ?, ?, "user", NOW())'
        );
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);

        set_flash('success', 'Tạo tài khoản thành công, bạn có thể đăng nhập ngay.');
        redirect('login.php');
    } catch (Throwable $exception) {
        set_flash('danger', $exception->getMessage());
    }
}

render_site_header('Đăng ký');
?>
<section class="auth-section">
    <div class="auth-card">
        <div class="auth-form-pane">
            <p class="eyebrow">Tham gia cộng đồng</p>
            <h1>Đăng ký tài khoản</h1>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <label>
                    Tên đăng nhập
                    <input type="text" name="username" placeholder="ví dụ: linhtrang">
                </label>
                <label>
                    Email
                    <input type="email" name="email" placeholder="ban@example.com">
                </label>
                <label>
                    Mật khẩu
                    <input type="password" name="password" placeholder="Ít nhất 6 ký tự">
                </label>
                <label>
                    Xác nhận mật khẩu
                    <input type="password" name="password_confirmation" placeholder="Nhập lại mật khẩu">
                </label>
                <button type="submit" class="primary-btn wide-btn">Tạo tài khoản</button>
            </form>
        </div>
        <div class="auth-art-pane">
            <div class="art-card secondary">
                <h2>Đọc, theo dõi, bình luận</h2>
                <p>Sau khi đăng ký bạn có thể lưu truyện yêu thích, chấm điểm và tham gia thảo luận theo từng chương.</p>
            </div>
            <p class="switch-link">Đã có tài khoản? <a href="login.php">Quay lại đăng nhập</a></p>
        </div>
    </div>
</section>
<?php
render_site_footer();
