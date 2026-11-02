# Hệ thống Website Quản lý và Triển khai Khóa học Trực tuyến - KOS English Center

Dự án bài tập kết thúc môn **Thực hành thiết kế và triển khai website với PHP** (Kiến trúc PHP OOP thuần theo mô hình MVC).

## 1. Thành viên nhóm & Phân công nhánh Gitflow
- **Nguyễn Thành Hưng (Nhóm trưởng):** `feature/hung-auth-users` (Bảng `NguoiDung`, `HocVien`, `GiaoVien`)
- **Phan Hoàng Minh:** `feature/minh-courses` (Bảng `KhoaHoc`, `ChuongHoc`, `DangKyKhoaHoc`)
- **Nguyễn Thị Khánh:** `feature/khanh-learning` (Bảng `HoatDongHocTap`, `BaiNop`)
- **Nguyễn Trung Hiếu:** `feature/hieu-approval-reports` (Bảng `DuyetKhoaHoc`, `ThongBao` & Thống kê báo cáo)

## 2. Hướng dẫn cài đặt trên XAMPP 
1. Copy thư mục `kos-lms` vào `C:/xampp/htdocs/kos-lms`.
2. Mở `http://localhost/phpmyadmin`, nhập (Import) file `database/database_kos_lms.sql` (tự động tạo DB `kos_lms_db` gồm 10 bảng chuẩn hóa và dữ liệu mẫu).
3. Truy cập `http://localhost/kos-lms/` và đăng nhập bằng 4 tài khoản mẫu hiển thị trên màn hình đăng nhập.
