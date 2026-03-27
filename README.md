# Tủ Sách Nhỏ

Project PHP/MySQL dựng từ schema `tu_sach_nho.sql`, tập trung vào:

- Giao diện trang đọc và trang quản trị theo phong cách mẫu.
- Dashboard quản trị riêng, chỉ `admin` mới được đăng/sửa truyện, thêm chương và quản lý bình luận.
- Ảnh bìa upload bằng file ảnh, lưu trong `uploads/covers`.
- Đăng nhập, đăng ký, lưu truyện yêu thích, chấm điểm, lịch sử đọc và bình luận theo chương.

## Cách chạy nhanh với XAMPP

1. Copy thư mục này vào `htdocs`, ví dụ `C:\xampp\htdocs\Tu_sach_nho`.
2. Import file `C:\Users\Nguyen Danh Truong\Downloads\tu_sach_nho.sql` vào MySQL/MariaDB.
3. Mở file `app/config/config.php` và chỉnh thông số DB nếu máy bạn không dùng `root` / mật khẩu trống.
4. Bảo đảm thư mục `uploads/covers` có quyền ghi.
5. Truy cập `http://localhost/Tu_sach_nho/`.

## Tài khoản mẫu từ SQL

- `admin` / `17032005`
- `user1` / `truong2005`

Ứng dụng hỗ trợ cả mật khẩu plaintext trong file SQL mẫu; sau lần đăng nhập đầu tiên, mật khẩu sẽ được tự động băm lại.

## Ghi chú

- Schema gốc không có trạng thái ẩn bình luận hay bản nháp truyện, nên dashboard hiện hỗ trợ xóa bình luận và lưu truyện với 2 trạng thái: `ongoing`, `completed`.
- Trường tác giả đang bám theo `stories.author_id -> users.user_id`; admin có thể chọn tài khoản đại diện tác giả khi đăng truyện.

