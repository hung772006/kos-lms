<?php
/**
 * Class LearningModel: Quản lý bảng HoatDongHocTap và BaiNop
 * Người phụ trách: Khánh (Nhánh feature/khanh-learning)
 */

class LearningModel extends BaseModel
{
    protected string $table = 'HoatDongHocTap';
    protected string $primaryKey = 'MaHoatDong';

    // 1. Lấy danh sách hoạt động học tập theo khóa học
    public function getActivitiesByCourse(int $courseId): array
    {
        $sql = "SELECT hd.*, ch.TenChuong
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE ch.MaKhoaHoc = :cid
                ORDER BY ch.ThuTu ASC, hd.ThuTu ASC";
        return $this->queryAll($sql, ['cid' => $courseId]);
    }

    // 2. Thêm bài nộp mới của học viên vào bảng BaiNop
    public function addSubmission(array $data)
    {
        $sql = "INSERT INTO BaiNop (MaHoatDong, MaHocVien, FileBaiNop, NoiDungBaiLam, ThoiGianNop, TrangThai) 
                VALUES (:MaHoatDong, :MaHocVien, :FileBaiNop, :NoiDungBaiLam, NOW(), 'submitted')";
        return $this->execute($sql, $data);
    }

    // 3. Lấy danh sách bài nộp theo mã học viên
    public function getSubmissionsByStudent(int $studentId): array
    {
        $sql = "SELECT * FROM BaiNop WHERE MaHocVien = :studentId ORDER BY ThoiGianNop DESC";
        return $this->queryAll($sql, ['studentId' => $studentId]);
    }

    // 4. Cập nhật điểm và nhận xét của giáo viên cho bài nộp
    public function updateScore(int $submissionId, float $score, string $comment)
    {
        $sql = "UPDATE BaiNop SET SoDiem = :score, NhanXet = :comment, TrangThai = 'graded' WHERE MaBaiNop = :id";
        return $this->execute($sql, [
            'score' => $score,
            'comment' => $comment,
            'id' => $submissionId
        ]);
    }

    // 5. Lấy danh sách hoạt động có phân trang và tìm kiếm theo tên (Dành cho Admin)
    public function getActivitiesPaginated($keyword, $offset, $limit): array
    {
        $sql = "SELECT hd.*, ch.TenChuong 
                FROM HoatDongHocTap hd 
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE hd.TenHoatDong LIKE :keyword
                ORDER BY hd.MaHoatDong DESC 
                LIMIT :offset, :limit";
        return $this->queryAll($sql, [
            'keyword' => '%' . $keyword . '%',
            'offset' => (int)$offset,
            'limit' => (int)$limit
        ]);
    }

    // 6. Đếm tổng số lượng hoạt động để phân trang
    public function countActivities($keyword): int
    {
        $sql = "SELECT COUNT(*) as total FROM HoatDongHocTap WHERE TenHoatDong LIKE :keyword";
        $result = $this->queryOne($sql, ['keyword' => '%' . $keyword . '%']);
        return $result['total'] ?? 0;
    }

    // 7. Thêm mới hoạt động học tập
    public function insertActivity(array $data)
    {
        $sql = "INSERT INTO HoatDongHocTap (MaChuong, TenHoatDong, Loai, NoiDung, LinkVideo, FileDinhKem, DinhDangFile, DungLuongFile, DiemToiDa, HanNop, ThuTu, TrangThai,DuLieuQuizJSON) 
                VALUES (:MaChuong, :TenHoatDong, :Loai, :NoiDung, :LinkVideo, :FileDinhKem, :DinhDangFile, :DungLuongFile, :DiemToiDa, :HanNop, :ThuTu, :TrangThai,:DuLieuQuizJSON)";
        
        return $this->execute($sql, [
            'MaChuong' => $data['MaChuong'],
            'TenHoatDong' => $data['TenHoatDong'],
            'Loai' => $data['Loai'],
            'NoiDung' => $data['NoiDung'] ?? null,
            'LinkVideo' => $data['LinkVideo'] ?? null,
            'FileDinhKem' => $data['FileDinhKem'] ?? null,
            'DinhDangFile' => $data['DinhDangFile'] ?? null,
            'DungLuongFile' => $data['DungLuongFile'] ?? null,
            'DiemToiDa' => $data['DiemToiDa'] ?? 10.0,
            'HanNop' => $data['HanNop'] ?? null,
            'ThuTu' => $data['ThuTu'] ?? 1,
            'TrangThai' => $data['TrangThai'] ?? 'published',
            'DuLieuQuizJSON' => $data['DuLieuQuizJSON'] ?? null
        ]);
    }

    // 8. Xóa hoạt động học tập theo ID
    public function deleteActivity(int $id)
    {
        $sql = "DELETE FROM HoatDongHocTap WHERE MaHoatDong = :id";
        return $this->execute($sql, ['id' => $id]);
    }

    // 9. Lấy danh sách chương học để đổ dữ liệu vào thẻ <select>
    public function getAllChapters(): array
    {
        $sql = "SELECT MaChuong, TenChuong FROM ChuongHoc";
        return $this->queryAll($sql);
    }

    // 10. Lấy thông tin chi tiết 1 hoạt động theo ID
    public function getById(int $id)
    {
        $sql = "SELECT * FROM HoatDongHocTap WHERE MaHoatDong = :id";
        return $this->queryOne($sql, ['id' => $id]);
    }

    // 11. Cập nhật thông tin hoạt động học tập
    public function updateActivity(int $id, array $data)
    {
        $sql = "UPDATE HoatDongHocTap 
                SET MaChuong = :MaChuong, 
                    TenHoatDong = :TenHoatDong, 
                    Loai = :Loai, 
                    LinkVideo = :LinkVideo, 
                    HanNop = :HanNop";
        
        $params = [
            'MaChuong' => $data['MaChuong'],
            'TenHoatDong' => $data['TenHoatDong'],
            'Loai' => $data['Loai'],
            'LinkVideo' => $data['LinkVideo'] ?? null,
            'HanNop' => !empty($data['HanNop']) ? $data['HanNop'] : null,
            'id' => $id
        ];

        if (isset($data['FileDinhKem'])) {
            $sql .= ", FileDinhKem = :FileDinhKem, DinhDangFile = :DinhDangFile, DungLuongFile = :DungLuongFile";
            $params['FileDinhKem'] = $data['FileDinhKem'];
            $params['DinhDangFile'] = $data['DinhDangFile'];
            $params['DungLuongFile'] = $data['DungLuongFile'];
        }

        $sql .= " WHERE MaHoatDong = :id";

        return $this->execute($sql, $params);
    }

    // 12. Lấy danh sách toàn bộ bài nộp của học viên (JOIN bảng NguoiDung để lấy HoTen)
    public function getAllSubmissions(): array
    {
        $sql = "SELECT bn.*, nd.HoTen as TenHocVien, hd.TenHoatDong, hd.HanNop 
                FROM BaiNop bn
                JOIN HocVien hv ON bn.MaHocVien = hv.MaHocVien
                JOIN NguoiDung nd ON hv.MaHocVien = nd.MaNguoiDung
                JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong
                ORDER BY bn.ThoiGianNop DESC";
        return $this->queryAll($sql);
    }

    // 13. Lấy chi tiết 1 bài nộp theo ID để chấm điểm
    public function getSubmissionById(int $id)
    {
        $sql = "SELECT bn.*, nd.HoTen as TenHocVien, hd.TenHoatDong, hd.HanNop 
                FROM BaiNop bn
                JOIN HocVien hv ON bn.MaHocVien = hv.MaHocVien
                JOIN NguoiDung nd ON hv.MaHocVien = nd.MaNguoiDung
                JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong
                WHERE bn.MaBaiNop = :id";
        return $this->queryOne($sql, ['id' => $id]);
    }

    // Lấy toàn bộ danh sách hoạt động học tập để đồng bộ chung cho tất cả các khóa học (Client)
    public function getActivitiesByCourseId(int $courseId): array
    {
        // Bỏ điều kiện lọc theo khóa học (WHERE ch.MaKhoaHoc) để tất cả hoạt động mới thêm đều hiện đồng bộ
        $sql = "SELECT hd.*, ch.TenChuong, ch.MaChuong 
                FROM HoatDongHocTap hd
                LEFT JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE hd.TrangThai = 'published'
                ORDER BY hd.MaHoatDong DESC";
        
        return $this->queryAll($sql);
    }

    // 15. Lấy thông tin Quiz theo ID hoạt động
    public function getQuizById(int $id)
    {
        $sql = "SELECT * FROM HoatDongHocTap WHERE MaHoatDong = :id";
        return $this->queryOne($sql, ['id' => $id]);
    }

    // 16. Lấy mã khóa học từ mã hoạt động (Phục vụ tính tiến độ)
    public function getCourseIdByActivity(int $maHoatDong)
    {
        $sql = "SELECT ch.MaKhoaHoc FROM HoatDongHocTap hd JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong WHERE hd.MaHoatDong = :hid";
        $res = $this->queryOne($sql, ['hid' => $maHoatDong]);
        return $res['MaKhoaHoc'] ?? null;
    }

    // 17. Đếm tổng số hoạt động của khóa học
    public function getTotalActivitiesByCourse(int $maKhoaHoc)
    {
        $sql = "SELECT COUNT(*) as total FROM HoatDongHocTap hd JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong WHERE ch.MaKhoaHoc = :cid";
        $res = $this->queryOne($sql, ['cid' => $maKhoaHoc]);
        return $res['total'] ?? 1;
    }

    // 18. Đếm số hoạt động học viên đã hoàn thành
    public function getCompletedActivitiesCount(int $maHocVien, int $maKhoaHoc)
    {
        $sql = "SELECT COUNT(DISTINCT bn.MaHoatDong) as done FROM BaiNop bn JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong WHERE bn.MaHocVien = :hid AND ch.MaKhoaHoc = :cid";
        $res = $this->queryOne($sql, ['hid' => $maHocVien, 'cid' => $maKhoaHoc]);
        return $res['done'] ?? 0;
    }

    // 19. Cập nhật tiến độ vào bảng DangKyKhoaHoc
    public function updateCourseProgress(int $maHocVien, int $maKhoaHoc, int $done, float $percent)
    {
        $sql = "UPDATE DangKyKhoaHoc SET SoHoatDongDaHoc = :done, PhanTramHoanThanh = :per WHERE MaHocVien = :hid AND MaKhoaHoc = :cid";
        return $this->execute($sql, [
            'done' => $done,
            'per' => $percent,
            'hid' => $maHocVien,
            'cid' => $maKhoaHoc
        ]);
    }

    // 20. Lưu kết quả làm bài Quiz của học viên vào bảng BaiNop
    public function saveQuizSubmission(int $maHoatDong, int $maHocVien, string $noiDung, float $diem)
    {
        $sql = "INSERT INTO BaiNop (MaHoatDong, MaHocVien, NoiDungBaiLam, SoDiem, NhanXet, ThoiGianNop, TrangThai) 
                VALUES (:mahd, :mahv, :noidung, :diem, 'Hệ thống tự động chấm điểm Quiz.', NOW(), 'graded')";
        return $this->execute($sql, [
            'mahd' => $maHoatDong,
            'mahv' => $maHocVien,
            'noidung' => $noiDung,
            'diem' => $diem
        ]);
    }
}