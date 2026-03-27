<?php
declare(strict_types=1);

require_once __DIR__ . '/app/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            throw new RuntimeException('Vui lòng nhập tài khoản và mật khẩu.');
        }

        if (!attempt_login($identifier, $password)) {
            throw new RuntimeException('Thông tin đăng nhập chưa chính xác.');
        }

        set_flash('success', 'Đăng nhập thành công.');
        redirect(is_admin() ? 'admin/index.php' : 'profile.php');
    } catch (Throwable $exception) {
        set_flash('danger', $exception->getMessage());
    }
}

render_site_header('Đăng nhập', '', '');
?>
<section class="auth-section">
    <div class="auth-card">
        <div class="auth-form-pane">
            <p class="eyebrow">Đăng nhập / Đăng ký</p>
            <h1>Đăng nhập</h1>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <label>
                    Email hoặc tên đăng nhập
                    <input type="text" name="identifier" placeholder="admin@gmail.com hoặc admin">
                </label>
                <label>
                    Mật khẩu
                    <input type="password" name="password" placeholder="Nhập mật khẩu">
                </label>
                <button type="submit" class="primary-btn wide-btn">Đăng nhập</button>
            </form>
            <p class="auth-footnote">Nếu bạn import dữ liệu mẫu từ file SQL, tài khoản `admin` / `17032005` sẽ tự được nâng cấp sang mật khẩu băm sau lần đăng nhập đầu tiên.</p>
        </div>
        <div class="auth-art-pane">
            <div class="art-card">
                <h2>Không gian đọc yên tĩnh</h2>
                <p>Đăng nhập để bình luận, đánh giá và lưu tủ sách cá nhân của bạn.</p>
            </div>
            <p class="switch-link">Chưa có tài khoản? <a href="register.php">Tạo tài khoản mới</a></p>
        </div>
    </div>
</section>
<?php
render_site_footer();
