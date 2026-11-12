<?php
/**
 * Class ApprovalReportModel: Quản lý bảng DuyetKhoaHoc, ThongBao và Báo cáo thống kê
 * Người phụ trách: Nguyễn Trung Hiếu (Nhánh feature/hieu-approval-reports)
 */

class ApprovalReportModel extends BaseModel
{
    protected string $table = 'DuyetKhoaHoc';
    protected string $primaryKey = 'MaDuyet';

    // =========================================================================
    // 1. MODULE PHÊ DUYỆT KHÓA HỌC (Bảng KhoaHoc & DuyetKhoaHoc)
    // =========================================================================

    /**
     * Lấy danh sách khóa học chờ phê duyệt có Tìm kiếm & Phân trang
     */
    public function getPendingCoursesPaginated(string $keyword = '', int $limit = ITEMS_PER_PAGE, int $offset = 0): array
    {
        $limit  = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        $params = [];
        $where  = "WHERE kh.TrangThaiDuyet = 'pending'";

        if (trim($keyword) !== '') {
            $kw = '%' . trim($keyword) . '%';
            $where .= ' AND (kh.TenKhoaHoc LIKE :kw1 OR kh.DanhMuc LIKE :kw2 OR nd.HoTen LIKE :kw3)';
            $params = ['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw];
        }

        $sql = "SELECT kh.*, nd.HoTen AS TenGiaoVien, nd.Email AS EmailGiaoVien,
                       (SELECT COUNT(*) FROM ChuongHoc ch WHERE ch.MaKhoaHoc = kh.MaKhoaHoc) AS SoChuong
                FROM KhoaHoc kh
                JOIN NguoiDung nd ON kh.NguoiTao = nd.MaNguoiDung
                {$where}
                ORDER BY kh.NgayTao DESC, kh.MaKhoaHoc DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->queryAll($sql, $params);
    }

    /**
     * Đếm tổng số khóa học đang chờ phê duyệt
     */
    public function countPendingCourses(string $keyword = ''): int
    {
        $params = [];
        $where  = "WHERE kh.TrangThaiDuyet = 'pending'";

        if (trim($keyword) !== '') {
            $kw = '%' . trim($keyword) . '%';
            $where .= ' AND (kh.TenKhoaHoc LIKE :kw1 OR kh.DanhMuc LIKE :kw2 OR nd.HoTen LIKE :kw3)';
            $params = ['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw];
        }

        $sql = "SELECT COUNT(*) AS total
                FROM KhoaHoc kh
                JOIN NguoiDung nd ON kh.NguoiTao = nd.MaNguoiDung
                {$where}";

        $result = $this->queryOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Lấy lịch sử xét duyệt khóa học gần đây từ bảng DuyetKhoaHoc
     */
    public function getRecentApprovalLogs(int $limit = 8): array
    {
        $limit = max(1, (int) $limit);
        try {
            $cols = array_column($this->queryAll('SHOW COLUMNS FROM DuyetKhoaHoc'), 'Field');
            $reviewerCol = in_array('MaNguoiDuyet', $cols, true) ? 'MaNguoiDuyet' : (in_array('NguoiDuyet', $cols, true) ? 'NguoiDuyet' : '');
            $statusCol   = in_array('TrangThai', $cols, true) ? 'TrangThai' : (in_array('TrangThaiDuyet', $cols, true) ? 'TrangThaiDuyet' : (in_array('KetQua', $cols, true) ? 'KetQua' : ''));
            $noteCol     = in_array('GhiChu', $cols, true) ? 'GhiChu' : (in_array('LyDo', $cols, true) ? 'LyDo' : '');
            $dateCol     = in_array('NgayDuyet', $cols, true) ? 'NgayDuyet' : '';

            if ($reviewerCol === '' || $statusCol === '') {
                return [];
            }

            $selectNote = $noteCol !== '' ? "dkh.{$noteCol} AS GhiChu," : "'' AS GhiChu,";
            $selectDate = $dateCol !== '' ? "dkh.{$dateCol} AS NgayDuyet" : 'NOW() AS NgayDuyet';
            $orderBy    = $dateCol !== '' ? "dkh.{$dateCol} DESC" : 'kh.MaKhoaHoc DESC';

            $sql = "SELECT kh.MaKhoaHoc, kh.TenKhoaHoc, nd.HoTen AS NguoiDuyetTen,
                           dkh.{$statusCol} AS TrangThai, {$selectNote} {$selectDate}
                    FROM DuyetKhoaHoc dkh
                    JOIN KhoaHoc kh ON dkh.MaKhoaHoc = kh.MaKhoaHoc
                    LEFT JOIN NguoiDung nd ON dkh.{$reviewerCol} = nd.MaNguoiDung
                    ORDER BY {$orderBy}
                    LIMIT {$limit}";
            return $this->queryAll($sql);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Xử lý Phê duyệt / Từ chối khóa học, ghi lịch sử DuyetKhoaHoc và tự động gửi ThongBao cho Giáo viên
     */
    public function updateCourseApprovalStatus(int $courseId, int $adminId, string $status, string $lyDo = ''): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $course = $this->queryOne(
            'SELECT MaKhoaHoc, TenKhoaHoc, NguoiTao FROM KhoaHoc WHERE MaKhoaHoc = :id',
            ['id' => $courseId]
        );
        if ($course === null) {
            return false;
        }

        // 1. Cập nhật trạng thái duyệt trên bảng KhoaHoc
        $updated = $this->execute(
            'UPDATE KhoaHoc SET TrangThaiDuyet = :status WHERE MaKhoaHoc = :id',
            ['status' => $status, 'id' => $courseId]
        );
        if (!$updated) {
            return false;
        }

        // 2. Lưu lịch sử xét duyệt vào bảng DuyetKhoaHoc (tự động khớp tên cột thực tế trong CSDL)
        $this->insertApprovalHistoryRecord($courseId, $adminId, $status, $lyDo);

        // 3. Tự động tạo thông báo gửi cho Giáo viên phụ trách khóa học (nếu giáo viên bật nhận thông báo duyệt)
        $teacherId = (int) ($course['NguoiTao'] ?? 0);
        if ($teacherId > 0) {
            $settings = $this->getNotificationSettings($teacherId);
            if (!empty($settings['approval'])) {
                $tieuDe  = ($status === 'approved')
                    ? 'Khóa học đã được phê duyệt'
                    : 'Khóa học cần chỉnh sửa bổ sung';
                $noiDung = ($status === 'approved')
                    ? 'Khóa học "' . $course['TenKhoaHoc'] . '" của bạn đã được Bộ phận đào tạo phê duyệt và công bố trên hệ thống.'
                        . ($lyDo !== '' ? ' Ghi chú: ' . $lyDo : '')
                    : 'Khóa học "' . $course['TenKhoaHoc'] . '" chưa được phê duyệt. Lý do: ' . $lyDo;

                $this->createNotification(
                    $teacherId,
                    $adminId > 0 ? $adminId : null,
                    $courseId,
                    null,
                    'approval',
                    $tieuDe,
                    $noiDung
                );
            }
        }

        return true;
    }

    /**
     * Ghi log vào bảng DuyetKhoaHoc an toàn với mọi biến thể tên cột trong file SQL gốc
     */
    private function insertApprovalHistoryRecord(int $courseId, int $adminId, string $status, string $note): void
    {
        try {
            $cols = array_column($this->queryAll('SHOW COLUMNS FROM DuyetKhoaHoc'), 'Field');
            if (empty($cols)) {
                return;
            }

            $insertCols = [];
            $placeholders = [];
            $params = [];

            if (in_array('MaKhoaHoc', $cols, true)) {
                $insertCols[] = 'MaKhoaHoc';
                $placeholders[] = ':courseId';
                $params['courseId'] = $courseId;
            }

            foreach (['MaNguoiDuyet', 'NguoiDuyet'] as $cand) {
                if (in_array($cand, $cols, true)) {
                    $insertCols[] = $cand;
                    $placeholders[] = ':adminId';
                    $params['adminId'] = $adminId;
                    break;
                }
            }

            foreach (['TrangThai', 'TrangThaiDuyet', 'KetQua', 'HanhDong'] as $cand) {
                if (in_array($cand, $cols, true)) {
                    $insertCols[] = $cand;
                    $placeholders[] = ':status';
                    $params['status'] = $status;
                    break;
                }
            }

            foreach (['GhiChu', 'LyDo', 'NhanXet'] as $cand) {
                if (in_array($cand, $cols, true)) {
                    $insertCols[] = $cand;
                    $placeholders[] = ':note';
                    $params['note'] = $note;
                    break;
                }
            }

            if (in_array('NgayDuyet', $cols, true)) {
                $insertCols[] = 'NgayDuyet';
                $placeholders[] = 'NOW()';
            }

            if (!empty($insertCols)) {
                $sql = 'INSERT INTO DuyetKhoaHoc (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $placeholders) . ')';
                $this->execute($sql, $params);
            }
        } catch (Throwable) {
            // Không làm gián đoạn luồng duyệt chính nếu bảng log có ràng buộc riêng
        }
    }

    // =========================================================================
    // 2. MODULE HỆ THỐNG THÔNG BÁO (Bảng ThongBao)
    // =========================================================================

    /**
     * Lấy danh sách thông báo của người dùng kèm Phân trang & Bộ lọc
     */
    public function getNotificationsByUser(int $userId, int $limit = ITEMS_PER_PAGE, int $offset = 0, string $filter = 'all'): array
    {
        $limit  = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        $where  = 'WHERE tb.MaNguoiNhan = :uid';

        if ($filter === 'unread') {
            $where .= ' AND tb.DaDoc = 0';
        }

        $sql = "SELECT tb.*, nd.HoTen AS TenNguoiGui, kh.TenKhoaHoc
                FROM ThongBao tb
                LEFT JOIN NguoiDung nd ON tb.MaNguoiGui = nd.MaNguoiDung
                LEFT JOIN KhoaHoc kh ON tb.MaKhoaHoc = kh.MaKhoaHoc
                {$where}
                ORDER BY tb.DaDoc ASC, tb.NgayTao DESC, tb.MaThongBao DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->queryAll($sql, ['uid' => $userId]);
    }

    /**
     * Đếm tổng số thông báo của người dùng phục vụ Phân trang
     */
    public function countNotificationsByUser(int $userId, string $filter = 'all'): int
    {
        $where = 'WHERE MaNguoiNhan = :uid';
        if ($filter === 'unread') {
            $where .= ' AND DaDoc = 0';
        }

        $row = $this->queryOne("SELECT COUNT(*) AS total FROM ThongBao {$where}", ['uid' => $userId]);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Đếm số thông báo chưa đọc của người dùng
     */
    public function countUnreadByUser(int $userId): int
    {
        return $this->countNotificationsByUser($userId, 'unread');
    }

    /**
     * Tạo thông báo mới vào bảng ThongBao
     */
    public function createNotification(
        int $maNguoiNhan,
        ?int $maNguoiGui,
        ?int $maKhoaHoc,
        ?int $maHoatDong,
        string $loaiThongBao,
        string $tieuDe,
        string $noiDung
    ): bool {
        $allowedTypes = ['approval', 'submission', 'grade', 'deadline', 'system'];
        $safeType     = in_array($loaiThongBao, $allowedTypes, true) ? $loaiThongBao : 'system';

        $sql = "INSERT INTO ThongBao (MaNguoiNhan, MaNguoiGui, MaKhoaHoc, MaHoatDong, LoaiThongBao, TieuDe, NoiDung, DaDoc, NgayTao)
                VALUES (:receiver, :sender, :course, :activity, :type, :title, :content, 0, NOW())";

        return $this->execute($sql, [
            'receiver' => $maNguoiNhan,
            'sender'   => $maNguoiGui,
            'course'   => $maKhoaHoc,
            'activity' => $maHoatDong,
            'type'     => $safeType,
            'title'    => $tieuDe,
            'content'  => $noiDung,
        ]);
    }

    /**
     * Đánh dấu đã đọc 1 thông báo
     */
    public function markAsRead(int $maThongBao, int $userId): bool
    {
        return $this->execute(
            'UPDATE ThongBao SET DaDoc = 1 WHERE MaThongBao = :id AND MaNguoiNhan = :uid',
            ['id' => $maThongBao, 'uid' => $userId]
        );
    }

    /**
     * Đánh dấu đã đọc toàn bộ thông báo của người dùng
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->execute(
            'UPDATE ThongBao SET DaDoc = 1 WHERE MaNguoiNhan = :uid AND DaDoc = 0',
            ['uid' => $userId]
        );
    }

    /**
     * Lấy cấu hình nhận thông báo của Giáo viên
     */
    public function getNotificationSettings(int $userId): array
    {
        $defaults = [
            'submission' => 1,
            'approval'   => 1,
            'email'      => 1,
        ];

        if (isset($_SESSION['kos_notif_settings'][$userId]) && is_array($_SESSION['kos_notif_settings'][$userId])) {
            return array_merge($defaults, $_SESSION['kos_notif_settings'][$userId]);
        }

        try {
            $gvCols = array_column($this->queryAll('SHOW COLUMNS FROM GiaoVien'), 'Field');
            if (in_array('NhanThongBaoBaiNop', $gvCols, true) && in_array('NhanThongBaoDuyet', $gvCols, true)) {
                $row = $this->queryOne(
                    'SELECT NhanThongBaoBaiNop, NhanThongBaoDuyet FROM GiaoVien WHERE MaGiaoVien = :id',
                    ['id' => $userId]
                );
                if ($row !== null) {
                    $defaults['submission'] = (int) $row['NhanThongBaoBaiNop'];
                    $defaults['approval']   = (int) $row['NhanThongBaoDuyet'];
                }
            }
        } catch (Throwable) {
            // Dùng cấu hình mặc định nếu bảng không có cột mở rộng
        }

        return $defaults;
    }

    /**
     * Lưu cấu hình nhận thông báo của Giáo viên (Lưu Session & đồng bộ CSDL nếu có cột)
     */
    public function saveNotificationSettings(int $userId, array $settings): bool
    {
        $clean = [
            'submission' => !empty($settings['submission']) ? 1 : 0,
            'approval'   => !empty($settings['approval']) ? 1 : 0,
            'email'      => !empty($settings['email']) ? 1 : 0,
        ];

        $_SESSION['kos_notif_settings'][$userId] = $clean;

        try {
            $gvCols = array_column($this->queryAll('SHOW COLUMNS FROM GiaoVien'), 'Field');
            if (in_array('NhanThongBaoBaiNop', $gvCols, true) && in_array('NhanThongBaoDuyet', $gvCols, true)) {
                $this->execute(
                    'UPDATE GiaoVien SET NhanThongBaoBaiNop = :sub, NhanThongBaoDuyet = :app WHERE MaGiaoVien = :id',
                    ['sub' => $clean['submission'], 'app' => $clean['approval'], 'id' => $userId]
                );
            }

            $ndCols = array_column($this->queryAll('SHOW COLUMNS FROM NguoiDung'), 'Field');
            if (in_array('NhanThongBaoEmail', $ndCols, true) && in_array('NhanThongBaoHeThong', $ndCols, true)) {
                $this->execute(
                    'UPDATE NguoiDung SET NhanThongBaoEmail = :email, NhanThongBaoHeThong = :sys WHERE MaNguoiDung = :uid',
                    ['email' => $clean['email'], 'sys' => $clean['submission'], 'uid' => $userId]
                );
            }
        } catch (Throwable) {
            // Cấu hình vẫn được đảm bảo lưu an toàn trong phiên làm việc
        }

        return true;
    }

    // =========================================================================
    // 3. MODULE BÁO CÁO & THỐNG KÊ HỆ THỐNG (Dành cho Bộ phận Quản lý)
    // =========================================================================

    /**
     * Tổng hợp các chỉ số KPI toàn hệ thống (COUNT, SUM, AVG thực tế từ CSDL)
     */
    public function getOverviewStats(): array
    {
        $courses   = (int) ($this->queryOne("SELECT COUNT(*) AS total FROM KhoaHoc WHERE TrangThaiDuyet = 'approved'")['total'] ?? 0);
        $pending   = (int) ($this->queryOne("SELECT COUNT(*) AS total FROM KhoaHoc WHERE TrangThaiDuyet = 'pending'")['total'] ?? 0);
        $students  = (int) ($this->queryOne("SELECT COUNT(*) AS total FROM NguoiDung WHERE VaiTro = 'student'")['total'] ?? 0);
        $teachers  = (int) ($this->queryOne("SELECT COUNT(*) AS total FROM NguoiDung WHERE VaiTro = 'teacher'")['total'] ?? 0);

        $enrollRow = $this->queryOne(
            "SELECT COUNT(*) AS total_enrollments,
                    COALESCE(SUM(kh.Gia), 0) AS total_revenue,
                    COALESCE(ROUND(AVG(dk.PhanTramHoanThanh), 1), 0) AS avg_completion
             FROM DangKyKhoaHoc dk
             JOIN KhoaHoc kh ON dk.MaKhoaHoc = kh.MaKhoaHoc
             WHERE kh.TrangThaiDuyet = 'approved'"
        );

        return [
            'total_courses'     => $courses,
            'pending_courses'   => $pending,
            'total_students'    => $students,
            'total_teachers'    => $teachers,
            'total_enrollments' => (int) ($enrollRow['total_enrollments'] ?? 0),
            'total_revenue'     => (float) ($enrollRow['total_revenue'] ?? 0),
            'avg_completion'    => (float) ($enrollRow['avg_completion'] ?? 0),
        ];
    }

    /**
     * Thống kê chi tiết số lượng học viên, tiến độ trung bình và doanh thu thực tế của từng khóa học
     */
    public function getCourseStatistics(string $keyword = ''): array
    {
        $params = [];
        $where  = "WHERE kh.TrangThaiDuyet = 'approved'";

        if (trim($keyword) !== '') {
            $kw = '%' . trim($keyword) . '%';
            $where .= ' AND (kh.TenKhoaHoc LIKE :kw1 OR kh.DanhMuc LIKE :kw2 OR nd.HoTen LIKE :kw3)';
            $params = ['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw];
        }

        $sql = "SELECT kh.MaKhoaHoc, kh.TenKhoaHoc, kh.Gia, kh.DanhMuc, kh.TrangThai,
                       nd.HoTen AS TenGiaoVien,
                       COUNT(DISTINCT dk.MaHocVien) AS SoHocVien,
                       (COUNT(DISTINCT dk.MaHocVien) * kh.Gia) AS DoanhThu,
                       COALESCE(ROUND(AVG(dk.PhanTramHoanThanh), 1), 0) AS TienDoTB
                FROM KhoaHoc kh
                JOIN NguoiDung nd ON kh.NguoiTao = nd.MaNguoiDung
                LEFT JOIN DangKyKhoaHoc dk ON kh.MaKhoaHoc = dk.MaKhoaHoc
                {$where}
                GROUP BY kh.MaKhoaHoc, kh.TenKhoaHoc, kh.Gia, kh.DanhMuc, kh.TrangThai, nd.HoTen
                ORDER BY DoanhThu DESC, SoHocVien DESC, kh.MaKhoaHoc DESC";

        return $this->queryAll($sql, $params);
    }
}