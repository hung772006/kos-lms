<?php
/**
 * Class LearningModel: Quản lý 2 bảng HoatDongHocTap và BaiNop (Kèm đồng bộ tiến độ DangKyKhoaHoc)
 * Người phụ trách: Nguyễn Thị Khánh (Nhánh feature/khanh-learning)
 */

class LearningModel extends BaseModel
{
    protected string $table = 'HoatDongHocTap';
    protected string $primaryKey = 'MaHoatDong';

    /**
     * 1. Lấy danh sách hoạt động học tập theo khóa học
     */
    public function getActivitiesByCourse(int $courseId): array
    {
        $sql = "SELECT hd.*, ch.TenChuong, ch.MaKhoaHoc, kh.TenKhoaHoc
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                JOIN KhoaHoc kh ON ch.MaKhoaHoc = kh.MaKhoaHoc
                WHERE ch.MaKhoaHoc = :cid
                ORDER BY ch.ThuTu ASC, hd.ThuTu ASC, hd.MaHoatDong ASC";
        return $this->queryAll($sql, ['cid' => $courseId]);
    }

    /**
     * 2. Thêm (hoặc cập nhật) bài nộp tự luận của học viên vào bảng BaiNop & tự động xét hạn nộp
     */
    public function addSubmission(array $data): bool
    {
        $maHoatDong = (int) ($data['MaHoatDong'] ?? 0);
        $maHocVien  = (int) ($data['MaHocVien'] ?? 0);
        $fileBaiNop = $data['FileBaiNop'] ?? null;
        $noiDung    = $data['NoiDungBaiLam'] ?? null;

        $activity  = $this->getById($maHoatDong);
        $trangThai = 'submitted';
        if (!empty($activity['HanNop'])) {
            $deadlineTs = strtotime(trim((string) $activity['HanNop']) . ' 23:59:59');
            if ($deadlineTs !== false && time() > $deadlineTs) {
                $trangThai = 'late';
            }
        }

        $existing = $this->queryOne(
            'SELECT MaBaiNop FROM BaiNop WHERE MaHoatDong = :hd AND MaHocVien = :hv ORDER BY MaBaiNop DESC LIMIT 1',
            ['hd' => $maHoatDong, 'hv' => $maHocVien]
        );

        if ($existing !== null) {
            $ok = $this->execute(
                "UPDATE BaiNop
                 SET FileBaiNop = COALESCE(:file, FileBaiNop),
                     NoiDungBaiLam = :content,
                     ThoiGianNop = NOW(),
                     TrangThai = :status
                 WHERE MaBaiNop = :id",
                [
                    'file'    => $fileBaiNop,
                    'content' => $noiDung,
                    'status'  => $trangThai,
                    'id'      => $existing['MaBaiNop'],
                ]
            );
        } else {
            $ok = $this->execute(
                "INSERT INTO BaiNop (MaHoatDong, MaHocVien, FileBaiNop, NoiDungBaiLam, ThoiGianNop, TrangThai)
                 VALUES (:MaHoatDong, :MaHocVien, :FileBaiNop, :NoiDungBaiLam, NOW(), :TrangThai)",
                [
                    'MaHoatDong'    => $maHoatDong,
                    'MaHocVien'     => $maHocVien,
                    'FileBaiNop'    => $fileBaiNop,
                    'NoiDungBaiLam' => $noiDung,
                    'TrangThai'     => $trangThai,
                ]
            );
        }

        $courseId = $this->getCourseIdByActivity($maHoatDong);
        if ($ok && $courseId !== null) {
            $this->syncStudentProgress($maHocVien, $courseId);
        }

        return $ok;
    }

    /**
     * 3. Lấy danh sách bài nộp theo mã học viên
     */
    public function getSubmissionsByStudent(int $studentId): array
    {
        $sql = "SELECT bn.*, hd.TenHoatDong, hd.Loai, hd.DiemToiDa, hd.HanNop
                FROM BaiNop bn
                JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong
                WHERE bn.MaHocVien = :studentId
                ORDER BY bn.ThoiGianNop DESC";
        return $this->queryAll($sql, ['studentId' => $studentId]);
    }

    /**
     * 4. Cập nhật điểm số, nhận xét của Giáo viên và gửi thông báo cho Học viên
     */
    public function updateScore(int $submissionId, float $score, string $comment): bool
    {
        $submission = $this->getSubmissionById($submissionId);
        $ok = $this->execute(
            "UPDATE BaiNop SET SoDiem = :score, NhanXet = :comment, TrangThai = 'graded' WHERE MaBaiNop = :id",
            [
                'score'   => $score,
                'comment' => $comment,
                'id'      => $submissionId,
            ]
        );

        if ($ok && $submission !== null) {
            $this->execute(
                "INSERT INTO ThongBao (MaNguoiNhan, MaNguoiGui, MaKhoaHoc, MaHoatDong, LoaiThongBao, TieuDe, NoiDung)
                 VALUES (:receiver, :sender, :course, :activity, 'grade', :title, :content)",
                [
                    'receiver' => (int) $submission['MaHocVien'],
                    'sender'   => isset($submission['NguoiTao']) ? (int) $submission['NguoiTao'] : null,
                    'course'   => isset($submission['MaKhoaHoc']) ? (int) $submission['MaKhoaHoc'] : null,
                    'activity' => (int) $submission['MaHoatDong'],
                    'title'    => 'Đã có điểm bài tập: ' . ($submission['TenHoatDong'] ?? ''),
                    'content'  => 'Điểm số: ' . $score . '/' . ($submission['DiemToiDa'] ?? 10)
                        . ($comment !== '' ? ' · Nhận xét: ' . $comment : ''),
                ]
            );
        }

        return $ok;
    }

    /**
     * 5. Lấy danh sách hoạt động có Tìm kiếm & Phân trang (Sửa lỗi PDO LIMIT native prepared statement)
     */
    public function getActivitiesPaginated(string $keyword = '', int $offset = 0, int $limit = ITEMS_PER_PAGE): array
    {
        $offset = max(0, (int) $offset);
        $limit  = max(1, (int) $limit);
        $params = [];
        $where  = '';

        if (trim($keyword) !== '') {
            $kw = '%' . trim($keyword) . '%';
            $where = 'WHERE (hd.TenHoatDong LIKE :kw1 OR ch.TenChuong LIKE :kw2 OR kh.TenKhoaHoc LIKE :kw3)';
            $params = ['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw];
        }

        $sql = "SELECT hd.*, ch.TenChuong, ch.MaKhoaHoc, kh.TenKhoaHoc
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                JOIN KhoaHoc kh ON ch.MaKhoaHoc = kh.MaKhoaHoc
                {$where}
                ORDER BY hd.MaHoatDong DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->queryAll($sql, $params);
    }

    /**
     * 6. Đếm tổng số hoạt động phục vụ Phân trang (Đồng bộ điều kiện JOIN với getActivitiesPaginated)
     */
    public function countActivities(string $keyword = ''): int
    {
        $params = [];
        $where  = '';

        if (trim($keyword) !== '') {
            $kw = '%' . trim($keyword) . '%';
            $where = 'WHERE (hd.TenHoatDong LIKE :kw1 OR ch.TenChuong LIKE :kw2 OR kh.TenKhoaHoc LIKE :kw3)';
            $params = ['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw];
        }

        $sql = "SELECT COUNT(*) AS total
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                JOIN KhoaHoc kh ON ch.MaKhoaHoc = kh.MaKhoaHoc
                {$where}";
        $result = $this->queryOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * 7. Thêm mới hoạt động học tập
     */
    public function insertActivity(array $data): bool
    {
        $sql = "INSERT INTO HoatDongHocTap
                (MaChuong, TenHoatDong, Loai, NoiDung, LinkVideo, FileDinhKem, DinhDangFile, DungLuongFile, DiemToiDa, HanNop, ThuTu, TrangThai, DuLieuQuizJSON)
                VALUES
                (:MaChuong, :TenHoatDong, :Loai, :NoiDung, :LinkVideo, :FileDinhKem, :DinhDangFile, :DungLuongFile, :DiemToiDa, :HanNop, :ThuTu, :TrangThai, :DuLieuQuizJSON)";

        return $this->execute($sql, [
            'MaChuong'       => (int) ($data['MaChuong'] ?? 1),
            'TenHoatDong'    => trim((string) ($data['TenHoatDong'] ?? '')),
            'Loai'           => in_array($data['Loai'] ?? 'video', ['video', 'quiz', 'assignment'], true) ? $data['Loai'] : 'video',
            'NoiDung'        => $data['NoiDung'] ?? null,
            'LinkVideo'      => !empty($data['LinkVideo']) ? $data['LinkVideo'] : null,
            'FileDinhKem'    => $data['FileDinhKem'] ?? null,
            'DinhDangFile'   => $data['DinhDangFile'] ?? null,
            'DungLuongFile'  => $data['DungLuongFile'] ?? null,
            'DiemToiDa'      => (float) ($data['DiemToiDa'] ?? 10.0),
            'HanNop'         => !empty($data['HanNop']) ? $data['HanNop'] : null,
            'ThuTu'          => max(1, (int) ($data['ThuTu'] ?? 1)),
            'TrangThai'      => in_array($data['TrangThai'] ?? 'published', ['published', 'draft'], true) ? $data['TrangThai'] : 'published',
            'DuLieuQuizJSON' => $data['DuLieuQuizJSON'] ?? null,
        ]);
    }

    /**
     * Kiểm tra hoạt động đã có học viên nộp bài chưa (Phục vụ chặn xóa an toàn)
     */
    public function hasSubmissions(int $id): bool
    {
        $row = $this->queryOne(
            'SELECT COUNT(*) AS total FROM BaiNop WHERE MaHoatDong = :id',
            ['id' => $id]
        );
        return (int) ($row['total'] ?? 0) > 0;
    }

    /**
     * 8. Xóa hoạt động học tập theo ID (Chặn xóa nếu đã có học viên nộp bài)
     */
    public function deleteActivity(int $id): bool
    {
        if ($this->hasSubmissions($id)) {
            return false;
        }
        return $this->execute('DELETE FROM HoatDongHocTap WHERE MaHoatDong = :id', ['id' => $id]);
    }

    /**
     * 9. Lấy danh sách chương học kèm tên khóa học để đổ vào thẻ <select> rõ ràng
     */
    public function getAllChapters(): array
    {
        $sql = "SELECT ch.MaChuong, ch.MaKhoaHoc, ch.ThuTu,
                       CONCAT(kh.TenKhoaHoc, ' — ', ch.TenChuong) AS TenChuong,
                       ch.TenChuong AS TenChuongGoc,
                       kh.TenKhoaHoc
                FROM ChuongHoc ch
                JOIN KhoaHoc kh ON ch.MaKhoaHoc = kh.MaKhoaHoc
                ORDER BY kh.MaKhoaHoc ASC, ch.ThuTu ASC";
        return $this->queryAll($sql);
    }

    /**
     * 10. Lấy thông tin chi tiết 1 hoạt động theo ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT hd.*, ch.TenChuong, ch.MaKhoaHoc, kh.TenKhoaHoc
                FROM HoatDongHocTap hd
                LEFT JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                LEFT JOIN KhoaHoc kh ON ch.MaKhoaHoc = kh.MaKhoaHoc
                WHERE hd.MaHoatDong = :id";
        return $this->queryOne($sql, ['id' => $id]);
    }

    /**
     * 11. Cập nhật đầy đủ các trường của hoạt động học tập
     */
    public function updateActivity(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if ($existing === null) {
            return false;
        }

        $sql = "UPDATE HoatDongHocTap
                SET MaChuong = :MaChuong,
                    TenHoatDong = :TenHoatDong,
                    Loai = :Loai,
                    NoiDung = :NoiDung,
                    LinkVideo = :LinkVideo,
                    FileDinhKem = :FileDinhKem,
                    DinhDangFile = :DinhDangFile,
                    DungLuongFile = :DungLuongFile,
                    DuLieuQuizJSON = :DuLieuQuizJSON,
                    DiemToiDa = :DiemToiDa,
                    HanNop = :HanNop,
                    ThuTu = :ThuTu,
                    TrangThai = :TrangThai
                WHERE MaHoatDong = :id";

        $hasNewFile = isset($data['FileDinhKem']) && $data['FileDinhKem'] !== '';

        return $this->execute($sql, [
            'MaChuong'       => (int) ($data['MaChuong'] ?? $existing['MaChuong']),
            'TenHoatDong'    => trim((string) ($data['TenHoatDong'] ?? $existing['TenHoatDong'])),
            'Loai'           => in_array($data['Loai'] ?? '', ['video', 'quiz', 'assignment'], true) ? $data['Loai'] : $existing['Loai'],
            'NoiDung'        => array_key_exists('NoiDung', $data) ? $data['NoiDung'] : $existing['NoiDung'],
            'LinkVideo'      => array_key_exists('LinkVideo', $data) ? $data['LinkVideo'] : $existing['LinkVideo'],
            'FileDinhKem'    => $hasNewFile ? $data['FileDinhKem'] : $existing['FileDinhKem'],
            'DinhDangFile'   => $hasNewFile ? ($data['DinhDangFile'] ?? $existing['DinhDangFile']) : $existing['DinhDangFile'],
            'DungLuongFile'  => $hasNewFile ? ($data['DungLuongFile'] ?? $existing['DungLuongFile']) : $existing['DungLuongFile'],
            'DuLieuQuizJSON' => array_key_exists('DuLieuQuizJSON', $data) ? $data['DuLieuQuizJSON'] : $existing['DuLieuQuizJSON'],
            'DiemToiDa'      => isset($data['DiemToiDa']) ? (float) $data['DiemToiDa'] : (float) $existing['DiemToiDa'],
            'HanNop'         => array_key_exists('HanNop', $data) ? (!empty($data['HanNop']) ? $data['HanNop'] : null) : $existing['HanNop'],
            'ThuTu'          => isset($data['ThuTu']) ? max(1, (int) $data['ThuTu']) : (int) $existing['ThuTu'],
            'TrangThai'      => in_array($data['TrangThai'] ?? '', ['published', 'draft'], true) ? $data['TrangThai'] : $existing['TrangThai'],
            'id'             => $id,
        ]);
    }

    /**
     * 12. Lấy danh sách bài nộp của học viên (JOIN đủ thông tin Khóa học & Giáo viên tạo khóa)
     */
    public function getAllSubmissions(): array
    {
        $sql = "SELECT bn.*, nd.HoTen AS TenHocVien, nd.Email AS EmailHocVien,
                       hd.TenHoatDong, hd.Loai, hd.HanNop, hd.DiemToiDa,
                       ch.TenChuong, kh.MaKhoaHoc, kh.TenKhoaHoc, kh.NguoiTao
                FROM BaiNop bn
                JOIN HocVien hv ON bn.MaHocVien = hv.MaHocVien
                JOIN NguoiDung nd ON hv.MaHocVien = nd.MaNguoiDung
                JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                JOIN KhoaHoc kh ON ch.MaKhoaHoc = kh.MaKhoaHoc
                ORDER BY bn.ThoiGianNop DESC";
        return $this->queryAll($sql);
    }

    /**
     * 13. Lấy chi tiết 1 bài nộp theo ID để chấm điểm
     */
    public function getSubmissionById(int $id): ?array
    {
        $sql = "SELECT bn.*, nd.HoTen AS TenHocVien, nd.Email AS EmailHocVien,
                       hd.TenHoatDong, hd.Loai, hd.NoiDung AS DeBai, hd.HanNop, hd.DiemToiDa,
                       ch.TenChuong, kh.MaKhoaHoc, kh.TenKhoaHoc, kh.NguoiTao
                FROM BaiNop bn
                JOIN HocVien hv ON bn.MaHocVien = hv.MaHocVien
                JOIN NguoiDung nd ON hv.MaHocVien = nd.MaNguoiDung
                JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                JOIN KhoaHoc kh ON ch.MaKhoaHoc = kh.MaKhoaHoc
                WHERE bn.MaBaiNop = :id";
        return $this->queryOne($sql, ['id' => $id]);
    }

    /**
     * 14. Lấy danh sách hoạt động đã công bố theo đúng Mã khóa học cho phía Client
     */
    public function getActivitiesByCourseId(int $courseId): array
    {
        if ($courseId <= 0) {
            return $this->queryAll(
                "SELECT hd.*, ch.TenChuong, ch.MaChuong, ch.MaKhoaHoc
                 FROM HoatDongHocTap hd
                 JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                 WHERE hd.TrangThai = 'published'
                 ORDER BY ch.ThuTu ASC, hd.ThuTu ASC"
            );
        }

        $sql = "SELECT hd.*, ch.TenChuong, ch.MaChuong, ch.MaKhoaHoc
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE ch.MaKhoaHoc = :cid AND hd.TrangThai = 'published'
                ORDER BY ch.ThuTu ASC, hd.ThuTu ASC, hd.MaHoatDong ASC";
        return $this->queryAll($sql, ['cid' => $courseId]);
    }

    /**
     * 15. Lấy thông tin Quiz theo ID hoạt động
     */
    public function getQuizById(int $id): ?array
    {
        return $this->getById($id);
    }

    /**
     * 16. Lấy mã khóa học từ mã hoạt động (Phục vụ tính tiến độ)
     */
    public function getCourseIdByActivity(int $maHoatDong): ?int
    {
        $sql = "SELECT ch.MaKhoaHoc
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE hd.MaHoatDong = :hid";
        $res = $this->queryOne($sql, ['hid' => $maHoatDong]);
        return isset($res['MaKhoaHoc']) ? (int) $res['MaKhoaHoc'] : null;
    }

    /**
     * 17. Đếm tổng số hoạt động đã công bố của khóa học (Trả về tối thiểu 1 để chống lỗi chia cho 0)
     */
    public function getTotalActivitiesByCourse(int $maKhoaHoc): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE ch.MaKhoaHoc = :cid AND hd.TrangThai = 'published'";
        $res = $this->queryOne($sql, ['cid' => $maKhoaHoc]);
        return max(1, (int) ($res['total'] ?? 0));
    }

    /**
     * 18. Đếm số hoạt động học viên đã hoàn thành trong khóa học
     */
    public function getCompletedActivitiesCount(int $maHocVien, int $maKhoaHoc): int
    {
        $sql = "SELECT COUNT(DISTINCT bn.MaHoatDong) AS done
                FROM BaiNop bn
                JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE bn.MaHocVien = :hid AND ch.MaKhoaHoc = :cid AND hd.TrangThai = 'published'";
        $res = $this->queryOne($sql, ['hid' => $maHocVien, 'cid' => $maKhoaHoc]);
        return (int) ($res['done'] ?? 0);
    }

    /**
     * 19. Cập nhật tiến độ học tập vào bảng DangKyKhoaHoc
     */
    public function updateCourseProgress(int $maHocVien, int $maKhoaHoc, int $done, float $percent): bool
    {
        $safePercent = max(0.0, min(100.0, round($percent, 2)));
        $sql = "UPDATE DangKyKhoaHoc
                SET SoHoatDongDaHoc = :done, PhanTramHoanThanh = :per
                WHERE MaHocVien = :hid AND MaKhoaHoc = :cid";
        return $this->execute($sql, [
            'done' => max(0, $done),
            'per'  => $safePercent,
            'hid'  => $maHocVien,
            'cid'  => $maKhoaHoc,
        ]);
    }

    /**
     * 20. Lưu kết quả làm bài Quiz tự động chấm điểm & đồng bộ ngay tiến độ khóa học
     */
    public function saveQuizSubmission(int $maHoatDong, int $maHocVien, string $noiDung, float $diem): bool
    {
        $existing = $this->queryOne(
            'SELECT MaBaiNop FROM BaiNop WHERE MaHoatDong = :hd AND MaHocVien = :hv ORDER BY MaBaiNop DESC LIMIT 1',
            ['hd' => $maHoatDong, 'hv' => $maHocVien]
        );

        if ($existing !== null) {
            $ok = $this->execute(
                "UPDATE BaiNop
                 SET NoiDungBaiLam = :noidung,
                     SoDiem = :diem,
                     NhanXet = 'Hệ thống tự động chấm điểm Quiz.',
                     ThoiGianNop = NOW(),
                     TrangThai = 'graded'
                 WHERE MaBaiNop = :id",
                [
                    'noidung' => $noiDung,
                    'diem'    => $diem,
                    'id'      => $existing['MaBaiNop'],
                ]
            );
        } else {
            $ok = $this->execute(
                "INSERT INTO BaiNop (MaHoatDong, MaHocVien, NoiDungBaiLam, SoDiem, NhanXet, ThoiGianNop, TrangThai)
                 VALUES (:mahd, :mahv, :noidung, :diem, 'Hệ thống tự động chấm điểm Quiz.', NOW(), 'graded')",
                [
                    'mahd'    => $maHoatDong,
                    'mahv'    => $maHocVien,
                    'noidung' => $noiDung,
                    'diem'    => $diem,
                ]
            );
        }

        $courseId = $this->getCourseIdByActivity($maHoatDong);
        if ($ok && $courseId !== null) {
            $this->syncStudentProgress($maHocVien, $courseId);
        }

        return $ok;
    }

    /**
     * Tự động tính lại và cập nhật % hoàn thành khóa học cho học viên
     */
    public function syncStudentProgress(int $maHocVien, int $maKhoaHoc): void
    {
        $total   = $this->getTotalActivitiesByCourse($maKhoaHoc);
        $done    = $this->getCompletedActivitiesCount($maHocVien, $maKhoaHoc);
        $percent = ($done / $total) * 100.0;
        $this->updateCourseProgress($maHocVien, $maKhoaHoc, $done, $percent);
    }
}