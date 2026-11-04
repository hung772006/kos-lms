<?php
/**
 * Class LearningModel: Quản lý 2 bảng HoatDongHocTap và BaiNop
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

    // 2. Thêm bài nộp mới của học viên vào bảng BaiNop (Khớp đúng tên cột trong SQL)
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

    // 4. Cập nhật điểm và nhận xét của giáo viên cho bài nộp (Khớp đúng cột SoDiem, NhanXet, MaBaiNop)
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

    // 7. Thêm mới hoạt động học tập (Đã viết chuẩn câu lệnh SQL INSERT)
    public function insertActivity(array $data)
    {
        $sql = "INSERT INTO HoatDongHocTap (MaChuong, TenHoatDong, Loai, NoiDung, LinkVideo, FileDinhKem, DinhDangFile, DungLuongFile, DiemToiDa, HanNop, ThuTu, TrangThai) 
                VALUES (:MaChuong, :TenHoatDong, :Loai, :NoiDung, :LinkVideo, :FileDinhKem, :DinhDangFile, :DungLuongFile, :DiemToiDa, :HanNop, :ThuTu, :TrangThai)";
        
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
            'TrangThai' => $data['TrangThai'] ?? 'published'
        ]);
    }

    // 8. Xóa hoạt động học tập theo ID
    public function deleteActivity(int $id)
    {
        $sql = "DELETE FROM HoatDongHocTap WHERE MaHoatDong = :id";
        return $this->execute($sql, ['id' => $id]);
    }

    // 9. Lấy danh sách chương học để đổ dữ liệu vào thẻ <select> khi Thêm/Sửa
    public function getAllChapters(): array
    {
        $sql = "SELECT MaChuong, TenChuong FROM ChuongHoc";
        return $this->queryAll($sql);
    }
// 10. Lấy thông tin chi tiết 1 hoạt động theo ID (Phục vụ chức năng Sửa)
    public function getById(int $id)
    {
        $sql = "SELECT * FROM HoatDongHocTap WHERE MaHoatDong = :id";
        return $this->queryOne($sql, ['id' => $id]);
    }

    // 11. Cập nhật thông tin hoạt động học tập (Phục vụ chức năng Sửa)
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

        // Nếu có upload file mới thì cập nhật thêm thông tin file
        if (isset($data['FileDinhKem'])) {
            $sql .= ", FileDinhKem = :FileDinhKem, DinhDangFile = :DinhDangFile, DungLuongFile = :DungLuongFile";
            $params['FileDinhKem'] = $data['FileDinhKem'];
            $params['DinhDangFile'] = $data['DinhDangFile'];
            $params['DungLuongFile'] = $data['DungLuongFile'];
        }

        $sql .= " WHERE MaHoatDong = :id";

        return $this->execute($sql, $params);
    }
// 12. Lấy danh sách toàn bộ bài nộp của học viên (Đã join đúng bảng NguoiDung để lấy HoTen)
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
}