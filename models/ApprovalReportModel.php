<?php
/**
 * Class ApprovalReportModel: Quản lý 2 bảng DuyetKhoaHoc, ThongBao và Thống kê báo cáo
 * Người phụ trách: Hiếu (Nhánh feature/approval-reports-notifications)
 */
class ApprovalReportModel extends BaseModel
{
    // --- MODULE PHÊ DUYỆT KHÓA HỌC ---
    
    public function getPendingCoursesPaginated(string $keyword = '', int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT kh.*, nd.HoTen AS TenGiaoVien 
                FROM KhoaHoc kh 
                JOIN NguoiDung nd ON kh.NguoiTao = nd.MaNguoiDung 
                WHERE kh.TrangThaiDuyet = 'pending'";
        
        $params = [];
        if (!empty($keyword)) {
            $sql .= " AND (kh.TenKhoaHoc LIKE :keyword OR nd.HoTen LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }
        
        $sql .= " ORDER BY kh.NgayTao DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        return $this->queryAll($sql, $params);
    }

    public function countPendingCourses(string $keyword = ''): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM KhoaHoc kh 
                JOIN NguoiDung nd ON kh.NguoiTao = nd.MaNguoiDung 
                WHERE kh.TrangThaiDuyet = 'pending'";
        $params = [];
        if (!empty($keyword)) {
            $sql .= " AND (kh.TenKhoaHoc LIKE :keyword OR nd.HoTen LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }
        $result = $this->queryOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    // Xử lý Phê duyệt / Không duyệt khóa học
    public function updateCourseApprovalStatus(int $courseId, int $adminId, string $status, string $lyDo = ''): bool
    {
        try {
            // 1. Cập nhật trạng thái trong bảng KhoaHoc
            $sqlKhoaHoc = "UPDATE KhoaHoc SET TrangThaiDuyet = :status WHERE MaKhoaHoc = :id";
            $this->execute($sqlKhoaHoc, ['status' => $status, 'id' => $courseId]);

            // 2. Thêm bản ghi lịch sử vào bảng DuyetKhoaHoc
            $sqlDuyet = "INSERT INTO DuyetKhoaHoc (MaKhoaHoc, MaNguoiDuyet, TrangThai, GhiChu, NgayDuyet) 
                         VALUES (:courseId, :adminId, :status, :ghiChu, NOW())";
            $this->execute($sqlDuyet, [
                'courseId' => $courseId,
                'adminId'  => $adminId,
                'status'   => $status,
                'ghiChu'   => $lyDo
            ]);

            // 3. Tạo thông báo gửi cho Giáo viên tạo khóa học
            $sqlGetTeacher = "SELECT NguoiTao, TenKhoaHoc FROM KhoaHoc WHERE MaKhoaHoc = :id";
            $course = $this->queryOne($sqlGetTeacher, ['id' => $courseId]);
            
            if ($course) {
                $tieuDe = ($status === 'approved') ? 'Khóa học đã được phê duyệt' : 'Khóa học bị từ chối';
                $noiDung = ($status === 'approved') 
                    ? "Khóa học '{$course['TenKhoaHoc']}' của bạn đã được duyệt và hiển thị trên hệ thống." 
                    : "Khóa học '{$course['TenKhoaHoc']}' của bạn không được duyệt. Lý do: {$lyDo}";
                
                $sqlThongBao = "INSERT INTO ThongBao (MaNguoiNhan, TieuDe, NoiDung, TrangThaiDoc, NgayTao) 
                                VALUES (:teacherId, :tieuDe, :noiDung, 0, NOW())";
                $this->execute($sqlThongBao, [
                    'teacherId' => $course['NguoiTao'],
                    'tieuDe'    => $tieuDe,
                    'noiDung'   => $noiDung
                ]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // --- MODULE THÔNG BÁO ---

    public function getNotificationsByUser(int $userId, int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT * FROM ThongBao WHERE MaNguoiNhan = :uid ORDER BY NgayTao DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        return $this->queryAll($sql, ['uid' => $userId]);
    }

    public function countNotificationsByUser(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM ThongBao WHERE MaNguoiNhan = :uid";
        $result = $this->queryOne($sql, ['uid' => $userId]);
        return (int)($result['total'] ?? 0);
    }

    public function saveNotificationSettings(int $userId, array $settings): bool
    {
        $sql = "UPDATE NguoiDung SET NhanThongBaoEmail = :email, NhanThongBaoHeThong = :sys WHERE MaNguoiDung = :uid";
        return $this->execute($sql, [
            'email' => $settings['email'] ?? 0,
            'sys'   => $settings['system'] ?? 0,
            'uid'   => $userId
        ]);
    }

    // --- MODULE BÁO CÁO THỐNG KÊ (Dành cho Quản lý) ---

    public function getOverviewStats(): array
    {
        $sqlCourses = "SELECT COUNT(*) as total FROM KhoaHoc WHERE TrangThaiDuyet = 'approved'";
        $sqlStudents = "SELECT COUNT(*) as total FROM NguoiDung WHERE VaiTro = 'student'";
        $sqlTeachers = "SELECT COUNT(*) as total FROM NguoiDung WHERE VaiTro = 'teacher'";
        
        $courses  = $this->queryOne($sqlCourses)['total'] ?? 0;
        $students = $this->queryOne($sqlStudents)['total'] ?? 0;
        $teachers = $this->queryOne($sqlTeachers)['total'] ?? 0;

        return [
            'total_courses'  => (int)$courses,
            'total_students' => (int)$students,
            'total_teachers' => (int)$teachers,
        ];
    }

    public function getCourseStatistics(): array
    {
        $sql = "SELECT kh.MaKhoaHoc, kh.TenKhoaHoc, kh.Gia, kh.DanhMuc, nd.HoTen AS TenGiaoVien,
                       0 AS SoHocVien,
                       0 AS DoanhThu
                FROM KhoaHoc kh
                JOIN NguoiDung nd ON kh.NguoiTao = nd.MaNguoiDung
                WHERE kh.TrangThaiDuyet = 'approved'
                ORDER BY kh.MaKhoaHoc DESC";
        return $this->queryAll($sql);
    }
}