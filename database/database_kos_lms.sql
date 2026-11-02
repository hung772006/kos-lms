-- =========================================================================
-- CƠ SỞ DỮ LIỆU HỆ THỐNG LMS - KOS ENGLISH CENTER (CHUẨN HÓA 10 BẢNG)
-- Môn học: Thực hành thiết kế và triển khai website với PHP (PHP OOP MVC)
-- Nhóm thực hiện: Nguyễn Thành Hưng (Leader), Khánh, Minh, Hiếu
-- Đảm bảo đầy đủ 3 mối quan hệ: 1-1, 1-N, N-N
-- =========================================================================

DROP DATABASE IF EXISTS `kos_lms_db`;
CREATE DATABASE `kos_lms_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kos_lms_db`;

-- =========================================================================
-- PHẦN 1: NHÓM BẢNG CỦA NGUYỄN THÀNH HƯNG (3 BẢNG: NguoiDung, HocVien, GiaoVien)
-- Thể hiện mối quan hệ 1 - 1 (NguoiDung 1-1 HocVien, NguoiDung 1-1 GiaoVien)
-- =========================================================================

-- Bảng 1: NguoiDung (Lưu thông tin tài khoản chung cho cả 4 vai trò)
CREATE TABLE `NguoiDung` (
    `MaNguoiDung` INT AUTO_INCREMENT PRIMARY KEY,
    `Email` VARCHAR(255) NOT NULL UNIQUE,
    `SDT` VARCHAR(15) NOT NULL UNIQUE,
    `MatKhau` VARCHAR(255) NOT NULL COMMENT 'Lưu mật khẩu mã hóa hoặc plain text demo',
    `HoTen` VARCHAR(100) NOT NULL,
    `VaiTro` ENUM('student', 'teacher', 'training', 'manager') NOT NULL DEFAULT 'student',
    `TrangThai` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `AnhDaiDien` VARCHAR(500) DEFAULT NULL COMMENT 'Đường dẫn file ảnh đại diện (Xử lý file Client)',
    `NgayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng 2: HocVien (Quan hệ 1 - 1 với NguoiDung)
CREATE TABLE `HocVien` (
    `MaHocVien` INT PRIMARY KEY COMMENT 'PK đồng thời là FK tham chiếu NguoiDung(MaNguoiDung) -> Quan hệ 1-1',
    `NgaySinh` DATE DEFAULT NULL,
    `GioiThieu` TEXT DEFAULT NULL,
    `TongGioHoc` INT NOT NULL DEFAULT 0,
    `LanTruyCapCuoi` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_hocvien_nguoidung` FOREIGN KEY (`MaHocVien`) REFERENCES `NguoiDung`(`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng 3: GiaoVien (Quan hệ 1 - 1 với NguoiDung)
CREATE TABLE `GiaoVien` (
    `MaGiaoVien` INT PRIMARY KEY COMMENT 'PK đồng thời là FK tham chiếu NguoiDung(MaNguoiDung) -> Quan hệ 1-1',
    `ChuyenMonGV` VARCHAR(255) NOT NULL,
    `ChungChi` VARCHAR(255) DEFAULT NULL,
    `SoNamKinhNghiem` INT NOT NULL DEFAULT 1,
    `GioiThieuGV` TEXT DEFAULT NULL,
    CONSTRAINT `fk_giaovien_nguoidung` FOREIGN KEY (`MaGiaoVien`) REFERENCES `NguoiDung`(`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- PHẦN 2: NHÓM BẢNG CỦA MINH (3 BẢNG: KhoaHoc, ChuongHoc, DangKyKhoaHoc)
-- Thể hiện quan hệ 1 - N (GiaoVien -> KhoaHoc -> ChuongHoc) và N - N (DangKyKhoaHoc)
-- =========================================================================

-- Bảng 4: KhoaHoc (Quan hệ 1 - N với GiaoVien)
CREATE TABLE `KhoaHoc` (
    `MaKhoaHoc` INT AUTO_INCREMENT PRIMARY KEY,
    `TenKhoaHoc` VARCHAR(150) NOT NULL UNIQUE,
    `DanhMuc` VARCHAR(50) NOT NULL DEFAULT 'IELTS' COMMENT 'IELTS, TOEIC, Giao tiếp, Business, Thiếu nhi, Phát âm',
    `MoTa` TEXT DEFAULT NULL,
    `Gia` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `AnhBia` VARCHAR(500) DEFAULT NULL COMMENT 'Đường dẫn file ảnh bìa khóa học (Xử lý file Admin)',
    `NgayBatDau` DATE NOT NULL,
    `NgayKetThuc` DATE NOT NULL,
    `NguoiTao` INT NOT NULL COMMENT 'FK tham chiếu GiaoVien(MaGiaoVien)',
    `TrangThai` ENUM('active', 'upcoming', 'ended') NOT NULL DEFAULT 'upcoming',
    `TrangThaiDuyet` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `NgayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_khoahoc_giaovien` FOREIGN KEY (`NguoiTao`) REFERENCES `GiaoVien`(`MaGiaoVien`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng 5: ChuongHoc (Quan hệ 1 - N với KhoaHoc)
CREATE TABLE `ChuongHoc` (
    `MaChuong` INT AUTO_INCREMENT PRIMARY KEY,
    `MaKhoaHoc` INT NOT NULL,
    `TenChuong` VARCHAR(255) NOT NULL,
    `MoTaChuong` TEXT DEFAULT NULL,
    `ThuTu` INT NOT NULL DEFAULT 1,
    CONSTRAINT `fk_chuonghoc_khoahoc` FOREIGN KEY (`MaKhoaHoc`) REFERENCES `KhoaHoc`(`MaKhoaHoc`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng 6: DangKyKhoaHoc (Bảng trung gian Quan hệ N - N giữa HocVien và KhoaHoc, lưu luôn Tiến độ)
CREATE TABLE `DangKyKhoaHoc` (
    `MaDangKy` INT AUTO_INCREMENT PRIMARY KEY,
    `MaHocVien` INT NOT NULL,
    `MaKhoaHoc` INT NOT NULL,
    `NgayDangKy` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `SoTien` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `PhuongThucTT` VARCHAR(50) NOT NULL DEFAULT 'bank' COMMENT 'bank, card, momo',
    `MaGDNganHang` VARCHAR(100) NOT NULL,
    `TrangThaiTT` ENUM('paid', 'pending', 'cancelled') NOT NULL DEFAULT 'paid',
    `PhanTramHoanThanh` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Tích hợp tiến độ học tập (%)',
    `SoHoatDongDaHoc` INT NOT NULL DEFAULT 0,
    `LanCapNhatCuoi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_hocvien_khoahoc` (`MaHocVien`, `MaKhoaHoc`),
    CONSTRAINT `fk_dangky_hocvien` FOREIGN KEY (`MaHocVien`) REFERENCES `HocVien`(`MaHocVien`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dangky_khoahoc` FOREIGN KEY (`MaKhoaHoc`) REFERENCES `KhoaHoc`(`MaKhoaHoc`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- PHẦN 3: NHÓM BẢNG CỦA KHÁNH (2 BẢNG: HoatDongHocTap, BaiNop)
-- Thể hiện quan hệ 1 - N (ChuongHoc -> HoatDongHocTap) và N - N (HocVien - HoatDongHocTap qua BaiNop)
-- =========================================================================

-- Bảng 7: HoatDongHocTap (Lưu Bài giảng Video, Tài liệu PDF/Slide, Bài tập và Quiz)
CREATE TABLE `HoatDongHocTap` (
    `MaHoatDong` INT AUTO_INCREMENT PRIMARY KEY,
    `MaChuong` INT NOT NULL,
    `TenHoatDong` VARCHAR(255) NOT NULL,
    `Loai` ENUM('video', 'quiz', 'assignment') NOT NULL DEFAULT 'video',
    `NoiDung` TEXT DEFAULT NULL COMMENT 'Mô tả bài học, đề bài tự luận hoặc nội dung văn bản',
    `LinkVideo` VARCHAR(500) DEFAULT NULL,
    `FileDinhKem` VARCHAR(500) DEFAULT NULL COMMENT 'Đường dẫn file giáo trình PDF/ZIP/Slide (Xử lý file Admin)',
    `DinhDangFile` VARCHAR(20) DEFAULT NULL,
    `DungLuongFile` VARCHAR(30) DEFAULT NULL,
    `DuLieuQuizJSON` TEXT DEFAULT NULL COMMENT 'Lưu danh sách câu hỏi và đáp án đúng nếu Loai = quiz',
    `DiemToiDa` FLOAT NOT NULL DEFAULT 10.0,
    `HanNop` DATE DEFAULT NULL,
    `ThuTu` INT NOT NULL DEFAULT 1,
    `TrangThai` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    CONSTRAINT `fk_hoatdong_chuonghoc` FOREIGN KEY (`MaChuong`) REFERENCES `ChuongHoc`(`MaChuong`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng 8: BaiNop (Bảng trung gian N - N lưu bài nộp file tự luận và kết quả Quiz của Học viên)
CREATE TABLE `BaiNop` (
    `MaBaiNop` INT AUTO_INCREMENT PRIMARY KEY,
    `MaHoatDong` INT NOT NULL,
    `MaHocVien` INT NOT NULL,
    `FileBaiNop` VARCHAR(500) DEFAULT NULL COMMENT 'Đường dẫn file bài làm học viên upload (Xử lý file Client)',
    `NoiDungBaiLam` TEXT DEFAULT NULL COMMENT 'Nội dung trả lời hoặc JSON đáp án Quiz đã chọn',
    `SoDiem` FLOAT DEFAULT NULL COMMENT 'Điểm tự động chấm (Quiz) hoặc Giáo viên chấm (Assignment)',
    `NhanXet` TEXT DEFAULT NULL COMMENT 'Nhận xét của giáo viên',
    `ThoiGianNop` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `TrangThai` ENUM('submitted', 'graded', 'late') NOT NULL DEFAULT 'submitted',
    CONSTRAINT `fk_bainop_hoatdong` FOREIGN KEY (`MaHoatDong`) REFERENCES `HoatDongHocTap`(`MaHoatDong`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bainop_hocvien` FOREIGN KEY (`MaHocVien`) REFERENCES `HocVien`(`MaHocVien`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- PHẦN 4: NHÓM BẢNG CỦA HIẾU (2 BẢNG: DuyetKhoaHoc, ThongBao)
-- Thể hiện quan hệ N - N (DuyetKhoaHoc) và 1 - N (ThongBao)
-- =========================================================================

-- Bảng 9: DuyetKhoaHoc (Lưu lịch sử Bộ phận Quản lý đào tạo xét duyệt khóa học)
CREATE TABLE `DuyetKhoaHoc` (
    `MaDuyet` INT AUTO_INCREMENT PRIMARY KEY,
    `MaKhoaHoc` INT NOT NULL,
    `MaNguoiDuyet` INT NOT NULL COMMENT 'FK tham chiếu NguoiDung(MaNguoiDung) có VaiTro = training',
    `TrangThai` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `NhanXet` TEXT DEFAULT NULL,
    `NgayDuyet` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_duyet_khoahoc` FOREIGN KEY (`MaKhoaHoc`) REFERENCES `KhoaHoc`(`MaKhoaHoc`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_duyet_nguoidung` FOREIGN KEY (`MaNguoiDuyet`) REFERENCES `NguoiDung`(`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng 10: ThongBao (Lưu trữ thông báo gửi đến người dùng theo từng vai trò)
CREATE TABLE `ThongBao` (
    `MaThongBao` INT AUTO_INCREMENT PRIMARY KEY,
    `MaNguoiNhan` INT NOT NULL,
    `MaNguoiGui` INT DEFAULT NULL,
    `MaKhoaHoc` INT DEFAULT NULL,
    `MaHoatDong` INT DEFAULT NULL,
    `LoaiThongBao` VARCHAR(50) NOT NULL DEFAULT 'system',
    `TieuDe` VARCHAR(255) NOT NULL,
    `NoiDung` TEXT NOT NULL,
    `DaDoc` TINYINT(1) NOT NULL DEFAULT 0,
    `NgayTao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_thongbao_nguoinhan` FOREIGN KEY (`MaNguoiNhan`) REFERENCES `NguoiDung`(`MaNguoiDung`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_thongbao_nguoigui` FOREIGN KEY (`MaNguoiGui`) REFERENCES `NguoiDung`(`MaNguoiDung`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_thongbao_khoahoc` FOREIGN KEY (`MaKhoaHoc`) REFERENCES `KhoaHoc`(`MaKhoaHoc`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- DỮ LIỆU MẪU CHUẨN HÓA (KHỚP 100% VỚI GIAO DIỆN lms-kos-final.html)
-- Mật khẩu mặc định hỗ trợ cả đăng nhập khớp trực tiếp hoặc password_verify
-- =========================================================================

INSERT INTO `NguoiDung` (`MaNguoiDung`, `Email`, `SDT`, `MatKhau`, `HoTen`, `VaiTro`, `TrangThai`, `AnhDaiDien`, `NgayTao`) VALUES
(1, 'hocvien@edu.vn', '0912345678', 'HocVien@2026', 'Nguyễn Văn An', 'student', 'active', 'uploads/avatars/default_student.png', '2026-01-01 08:00:00'),
(2, 'lan.hoang@gmail.com', '0908765432', 'HocVien@2026', 'Hoàng Thị Lan', 'student', 'active', 'uploads/avatars/default_student.png', '2026-01-15 09:00:00'),
(3, 'hung.bui@gmail.com', '0933111222', 'HocVien@2026', 'Bùi Quốc Hùng', 'student', 'inactive', 'uploads/avatars/default_student.png', '2026-01-20 10:00:00'),
(4, 'giaovien@edu.vn', '0987654321', 'GiaoVien@2026', 'Trần Thị Bình', 'teacher', 'active', 'uploads/avatars/default_teacher.png', '2025-09-01 08:00:00'),
(5, 'hao.ngo@edu.vn', '0922333444', 'GiaoVien@2026', 'Ngô Văn Hào', 'teacher', 'active', 'uploads/avatars/default_teacher.png', '2025-09-01 08:30:00'),
(6, 'mai.le@edu.vn', '0944555666', 'GiaoVien@2026', 'Lê Thị Mai', 'teacher', 'active', 'uploads/avatars/default_teacher.png', '2025-10-01 08:00:00'),
(7, 'huy.pham@edu.vn', '0955666777', 'GiaoVien@2026', 'Phạm Quang Huy', 'teacher', 'active', 'uploads/avatars/default_teacher.png', '2025-10-15 08:00:00'),
(8, 'tuan.dang@edu.vn', '0966777888', 'GiaoVien@2026', 'Đặng Minh Tuấn', 'teacher', 'active', 'uploads/avatars/default_teacher.png', '2025-11-01 08:00:00'),
(9, 'daotao@edu.vn', '0934567890', 'DaoTao@2026', 'Lê Minh Cường', 'training', 'active', 'uploads/avatars/default_training.png', '2025-06-01 08:00:00'),
(10, 'quanly@edu.vn', '0911222333', 'QuanLy@2026', 'Phạm Thu Dung', 'manager', 'active', 'uploads/avatars/default_manager.png', '2025-01-01 08:00:00');

INSERT INTO `HocVien` (`MaHocVien`, `NgaySinh`, `GioiThieu`, `TongGioHoc`, `LanTruyCapCuoi`) VALUES
(1, '2000-05-15', 'Học viên đang theo học khóa IELTS Foundation và English Communication B1 tại KOS English.', 47, '2026-11-02 09:45:00'),
(2, '2001-03-22', 'Học viên đang học Business English, hướng tới môi trường làm việc quốc tế.', 63, '2026-11-01 14:30:00'),
(3, '1999-11-10', 'Học viên đang theo học TOEIC 650+, chuẩn bị cho kỳ thi cuối năm.', 12, '2026-10-29 18:00:00');

INSERT INTO `GiaoVien` (`MaGiaoVien`, `ChuyenMonGV`, `ChungChi`, `SoNamKinhNghiem`, `GioiThieuGV`) VALUES
(4, 'Giảng viên IELTS & TOEIC Senior', 'IELTS 8.5, CELTA, TOEIC 990', 10, 'Tốt nghiệp ĐH Ngoại ngữ Hà Nội, thạc sĩ Ngôn ngữ học tại Úc. Đã giúp hàng trăm học viên đạt IELTS 7.0+.'),
(5, 'Giảng viên Tiếng Anh Giao tiếp', 'TESOL, 5 năm tại Singapore', 8, 'Từng làm việc tại Singapore 5 năm. Phương pháp chú trọng phản xạ ngôn ngữ tự nhiên.'),
(6, 'Giảng viên Business English', 'MBA, TOEIC 990', 12, 'Bằng MBA tại Pháp, từng quản lý tại công ty đa quốc gia. Khóa học giàu case study thực tế.'),
(7, 'Giảng viên Tiếng Anh Thiếu nhi', 'TEYL Certified', 7, 'Chuyên dạy tiếng Anh trẻ em qua TPR và trò chơi, giúp trẻ yêu thích tiếng Anh từ nhỏ.'),
(8, 'Chuyên gia Phát âm & Ngữ âm', 'MA Phonetics, Accent Coach', 9, 'Thạc sĩ Ngữ âm học, chuyên gia accent reduction tại Việt Nam.');

INSERT INTO `KhoaHoc` (`MaKhoaHoc`, `TenKhoaHoc`, `DanhMuc`, `MoTa`, `Gia`, `AnhBia`, `NgayBatDau`, `NgayKetThuc`, `NguoiTao`, `TrangThai`, `TrangThaiDuyet`) VALUES
(1, 'IELTS Foundation — Nền tảng IELTS', 'IELTS', 'Khóa học nền tảng IELTS từ cơ bản, luyện đủ 4 kỹ năng Listening, Reading, Writing, Speaking.', 500000.00, 'uploads/courses/ielts_foundation.png', '2026-03-01', '2026-05-30', 4, 'active', 'approved'),
(2, 'English Communication B1', 'Giao tiếp', 'Phát triển kỹ năng giao tiếp tiếng Anh trình độ B1 trong các tình huống thực tế.', 350000.00, 'uploads/courses/comm_b1.png', '2026-02-15', '2026-06-15', 5, 'active', 'approved'),
(3, 'Business English Toàn diện', 'Business', 'Tiếng Anh thương mại từ A đến Z: email, thuyết trình, đàm phán.', 450000.00, 'uploads/courses/business_en.png', '2026-04-01', '2026-07-31', 6, 'upcoming', 'approved'),
(4, 'English for Kids — Lớp 3-5', 'Thiếu nhi', 'Tiếng Anh vui nhộn cho trẻ em từ 8–11 tuổi, học qua trò chơi và bài hát.', 200000.00, 'uploads/courses/kids_en.png', '2026-01-20', '2026-04-20', 7, 'ended', 'approved'),
(5, 'TOEIC 650+ Intensive', 'TOEIC', 'Luyện thi TOEIC 650+ với phương pháp ôn tập hiệu quả và bộ đề thi thực tế.', 400000.00, 'uploads/courses/toeic_650.png', '2026-03-10', '2026-06-10', 4, 'active', 'approved'),
(6, 'Pronunciation & Accent Reduction', 'Phát âm', 'Cải thiện phát âm và giảm thiểu lỗi giọng địa phương khi nói tiếng Anh.', 300000.00, 'uploads/courses/pronunciation.png', '2026-01-01', '2026-02-28', 8, 'ended', 'approved'),
(7, 'IELTS Advanced — Band 7.0+', 'IELTS', 'Luyện thi IELTS nâng cao hướng tới band 7.0 trở lên.', 500000.00, 'uploads/courses/ielts_adv.png', '2026-11-15', '2027-02-15', 4, 'upcoming', 'pending'),
(8, 'English for Young Learners (Lớp 1-2)', 'Thiếu nhi', 'Tiếng Anh vui nhộn cho trẻ 6–8 tuổi qua trò chơi, bài hát và truyện tranh.', 250000.00, 'uploads/courses/young_learners.png', '2026-11-20', '2027-02-20', 7, 'upcoming', 'pending');

INSERT INTO `ChuongHoc` (`MaChuong`, `MaKhoaHoc`, `TenChuong`, `MoTaChuong`, `ThuTu`) VALUES
(1, 1, 'Unit 1 — Tổng quan IELTS & Listening Skills', 'Cấu trúc bài thi IELTS, hệ thống band score và kỹ thuật dự đoán trong Listening.', 1),
(2, 1, 'Unit 2 — Reading & Academic Writing', 'Kỹ thuật Skimming, Scanning và cách viết biểu đồ Writing Task 1, luận điểm Task 2.', 2),
(3, 2, 'Unit 1 — Everyday & Office Conversations', 'Chào hỏi, giới thiệu bản thân và giao tiếp nơi làm việc.', 1),
(4, 2, 'Unit 2 — Travel & Grammar in Use', 'Tình huống du lịch và ngữ pháp ứng dụng trình độ B1.', 2),
(5, 3, 'Chương 1 — Professional Emails & Presentations', 'Viết email kinh doanh trang trọng và kỹ năng thuyết trình.', 1);

INSERT INTO `DangKyKhoaHoc` (`MaDangKy`, `MaHocVien`, `MaKhoaHoc`, `NgayDangKy`, `SoTien`, `PhuongThucTT`, `MaGDNganHang`, `TrangThaiTT`, `PhanTramHoanThanh`, `SoHoatDongDaHoc`) VALUES
(1, 1, 1, '2026-03-01 09:00:00', 500000.00, 'bank', 'EDUPRO-1-NGUYEN', 'paid', 45.00, 3),
(2, 1, 2, '2026-02-15 10:15:00', 350000.00, 'momo', 'EDUPRO-2-NGUYEN', 'paid', 70.00, 4),
(3, 2, 1, '2026-03-02 11:00:00', 500000.00, 'card', 'EDUPRO-1-HOANG', 'paid', 80.00, 4),
(4, 2, 3, '2026-04-01 14:20:00', 450000.00, 'bank', 'EDUPRO-3-HOANG', 'paid', 30.00, 1),
(5, 3, 5, '2026-03-12 16:00:00', 400000.00, 'bank', 'EDUPRO-5-BUI', 'paid', 20.00, 1);

INSERT INTO `HoatDongHocTap` (`MaHoatDong`, `MaChuong`, `TenHoatDong`, `Loai`, `NoiDung`, `LinkVideo`, `FileDinhKem`, `DinhDangFile`, `DungLuongFile`, `DuLieuQuizJSON`, `DiemToiDa`, `HanNop`, `ThuTu`, `TrangThai`) VALUES
(1, 1, 'Bài 1 — Tổng quan IELTS & Band Score', 'video', 'Giới thiệu cấu trúc 4 kỹ năng IELTS và tiêu chí chấm điểm.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'uploads/materials/IELTS_Foundation_GiaoTrinh.pdf', 'PDF', '8.2 MB', NULL, 10.0, '2026-11-20', 1, 'published'),
(2, 1, 'Bài 2 — Listening: Kỹ thuật dự đoán', 'video', 'Phương pháp gạch chân từ khóa và dự đoán đáp án trước khi nghe.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'uploads/materials/Academic_Word_List_570.pdf', 'PDF', '2.1 MB', NULL, 10.0, '2026-11-22', 2, 'published'),
(3, 1, 'Quiz Reading Chapter 1', 'quiz', 'Bài kiểm tra trắc nghiệm từ vựng và kỹ năng đọc hiểu chương 1.', NULL, NULL, NULL, NULL, '[{"question":"What does Skimming mean in IELTS Reading?","options":["Reading quickly for main ideas","Reading slowly for every word","Looking only for numbers","Translating into Vietnamese"],"answer":0},{"question":"How many sections are there in IELTS Listening?","options":["2 sections","3 sections","4 sections","5 sections"],"answer":2}]', 10.0, '2026-11-25', 3, 'published'),
(4, 2, 'Writing Task 1 — Tuần 1', 'assignment', 'Viết bài mô tả biểu đồ cột (tối thiểu 150 từ) và nộp file PDF hoặc Word.', NULL, 'uploads/materials/Writing_Templates_Sample.pdf', 'PDF', '3.4 MB', NULL, 10.0, '2026-11-30', 1, 'published'),
(5, 3, 'Speaking Practice Unit 1', 'video', 'Hướng dẫn mẫu câu giới thiệu bản thân chuyên nghiệp trong công việc.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'uploads/materials/Comm_B1_Textbook.pdf', 'PDF', '6.5 MB', NULL, 10.0, '2026-11-22', 1, 'published'),
(6, 4, 'Grammar Exercise Unit 2', 'assignment', 'Hoàn thành bài tập thì Hiện tại hoàn thành và đính kèm file bài làm.', NULL, 'uploads/materials/Grammar_Guide_B1.pdf', 'PDF', '1.8 MB', NULL, 10.0, '2026-12-05', 1, 'published');

INSERT INTO `BaiNop` (`MaBaiNop`, `MaHoatDong`, `MaHocVien`, `FileBaiNop`, `NoiDungBaiLam`, `SoDiem`, `NhanXet`, `ThoiGianNop`, `TrangThai`) VALUES
(1, 3, 1, NULL, '{"0":0,"1":2}', 9.0, 'Hệ thống tự động chấm điểm: Xuất sắc!', '2026-11-01 10:30:00', 'graded'),
(2, 4, 1, 'uploads/submissions/NguyenVanAn_WritingTask1.pdf', 'Em nộp bài Writing Task 1 tuần 1 ạ.', NULL, NULL, '2026-11-02 08:15:00', 'submitted'),
(3, 4, 2, 'uploads/submissions/HoangThiLan_WritingTask1.pdf', 'Bài làm Writing Task 1 của Hoàng Thị Lan.', NULL, NULL, '2026-11-02 09:00:00', 'submitted'),
(4, 6, 3, 'uploads/submissions/BuiQuocHung_GrammarUnit2.pdf', 'Bài tập ngữ pháp Unit 2.', NULL, NULL, '2026-11-01 23:50:00', 'late');

INSERT INTO `DuyetKhoaHoc` (`MaDuyet`, `MaKhoaHoc`, `MaNguoiDuyet`, `TrangThai`, `NhanXet`, `NgayDuyet`) VALUES
(1, 1, 9, 'approved', 'Đề cương và học liệu đầy đủ, đồng ý công bố.', '2026-02-25 10:00:00'),
(2, 2, 9, 'approved', 'Nội dung đạt chuẩn B1 Châu Âu.', '2026-02-10 14:00:00'),
(3, 3, 9, 'approved', 'Bộ phận đào tạo đã xét duyệt thành công.', '2026-03-25 09:30:00'),
(4, 7, 9, 'pending', 'Đang chờ bộ phận đào tạo xét duyệt.', '2026-10-28 08:00:00'),
(5, 8, 9, 'pending', 'Cần rà soát giáo án chi tiết trước khi mở lớp.', '2026-10-27 15:00:00');

INSERT INTO `ThongBao` (`MaThongBao`, `MaNguoiNhan`, `MaNguoiGui`, `MaKhoaHoc`, `MaHoatDong`, `LoaiThongBao`, `TieuDe`, `NoiDung`, `DaDoc`, `NgayTao`) VALUES
(1, 1, 4, 1, NULL, 'enrollment', 'Bạn đã đăng ký thành công IELTS Foundation', 'IELTS Foundation — Nền tảng IELTS · Bắt đầu học ngay hôm nay!', 0, '2026-11-02 09:40:00'),
(2, 1, 4, 1, 4, 'deadline', 'Nhắc nhở: Hạn nộp Writing Task 1 sắp đến', 'Writing Task 1 — Tuần 1 · Hạn nộp: 30/11/2026', 0, '2026-11-02 07:30:00'),
(3, 4, 2, 1, 4, 'submission', 'Học viên Hoàng Thị Lan đã nộp bài', 'Writing Task 1 — Tuần 1 · IELTS Foundation', 0, '2026-11-02 09:01:00'),
(4, 9, 4, 7, NULL, 'approval', '2 khóa học tiếng Anh đang chờ phê duyệt', 'IELTS Advanced · English for Young Learners', 0, '2026-11-02 08:00:00'),
(5, 10, 9, NULL, NULL, 'report', 'Báo cáo doanh thu tháng đã sẵn sàng', 'Doanh thu hệ thống đạt mức tăng trưởng +18% so với tháng trước.', 0, '2026-11-02 06:00:00');
