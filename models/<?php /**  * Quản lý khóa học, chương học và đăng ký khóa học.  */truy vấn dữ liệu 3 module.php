<?php
/**
 * Quản lý khóa học, chương học và đăng ký khóa học.
 */
class CourseModel extends BaseModel
{
    protected string $table = 'KhoaHoc';
    protected string $primaryKey = 'MaKhoaHoc';

    public function getCoursesPaginated(
        string $keyword = '',
        int $limit = ITEMS_PER_PAGE,
        int $offset = 0,
        bool $onlyApproved = false,
        string $category = '',
        ?int $excludeStudentId = null,
        ?int $teacherId = null
    ): array {
        [$where, $params] = $this->courseFilters($keyword, $onlyApproved, $category, $excludeStudentId, $teacherId);
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        return $this->queryAll(
            "SELECT kh.*, nd.HoTen AS TenGiaoVien,
                    (SELECT COUNT(*) FROM DangKyKhoaHoc dk
                     WHERE dk.MaKhoaHoc = kh.MaKhoaHoc AND dk.TrangThaiTT = 'paid') AS SoHocVien
             FROM KhoaHoc kh
             JOIN GiaoVien gv ON kh.NguoiTao = gv.MaGiaoVien
             JOIN NguoiDung nd ON gv.MaGiaoVien = nd.MaNguoiDung
             {$where}
             ORDER BY kh.MaKhoaHoc DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countCourses(
        string $keyword = '',
        bool $onlyApproved = false,
        string $category = '',
        ?int $excludeStudentId = null,
        ?int $teacherId = null
    ): int {
        [$where, $params] = $this->courseFilters($keyword, $onlyApproved, $category, $excludeStudentId, $teacherId);
        $row = $this->queryOne("SELECT COUNT(*) AS total FROM KhoaHoc kh {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    public function getCategories(): array
    {
        return $this->queryAll('SELECT DISTINCT DanhMuc FROM KhoaHoc ORDER BY DanhMuc');
    }

    public function getCourseDetail(int $courseId): ?array
    {
        return $this->queryOne(
            "SELECT kh.*, nd.HoTen AS TenGiaoVien, nd.MaNguoiDung AS MaGiaoVien,
                    gv.ChuyenMonGV, gv.ChungChi, gv.SoNamKinhNghiem, gv.GioiThieuGV,
                    (SELECT COUNT(*) FROM DangKyKhoaHoc dk
                     WHERE dk.MaKhoaHoc = kh.MaKhoaHoc AND dk.TrangThaiTT = 'paid') AS SoHocVien
             FROM KhoaHoc kh
             JOIN GiaoVien gv ON kh.NguoiTao = gv.MaGiaoVien
             JOIN NguoiDung nd ON gv.MaGiaoVien = nd.MaNguoiDung
             WHERE kh.MaKhoaHoc = :id",
            ['id' => $courseId]
        );
    }

    public function getCourseById(int $courseId): ?array
    {
        return $this->queryOne('SELECT * FROM KhoaHoc WHERE MaKhoaHoc = :id', ['id' => $courseId]);
    }

    public function courseNameExists(string $name, ?int $exceptCourseId = null): bool
    {
        $sql = 'SELECT MaKhoaHoc FROM KhoaHoc WHERE TenKhoaHoc = :name';
        $params = ['name' => $name];
        if ($exceptCourseId !== null) {
            $sql .= ' AND MaKhoaHoc <> :id';
            $params['id'] = $exceptCourseId;
        }
        return $this->queryOne($sql, $params) !== null;
    }

    public function getChapters(int $courseId): array
    {
        return $this->queryAll(
            'SELECT * FROM ChuongHoc WHERE MaKhoaHoc = :id ORDER BY ThuTu, MaChuong',
            ['id' => $courseId]
        );
    }

    public function getTeacherOptions(): array
    {
        return $this->queryAll(
            "SELECT gv.MaGiaoVien, nd.HoTen
             FROM GiaoVien gv
             JOIN NguoiDung nd ON nd.MaNguoiDung = gv.MaGiaoVien
             WHERE nd.TrangThai = 'active'
             ORDER BY nd.HoTen"
        );
    }

    public function getStudentIdForUser(int $userId): ?int
    {
        $row = $this->queryOne(
            'SELECT MaHocVien FROM HocVien WHERE MaHocVien = :user_id',
            ['user_id' => $userId]
        );
        return $row === null ? null : (int) $row['MaHocVien'];
    }

    public function getTeacherIdForUser(int $userId): ?int
    {
        $row = $this->queryOne(
            "SELECT gv.MaGiaoVien
             FROM GiaoVien gv
             JOIN NguoiDung nd ON nd.MaNguoiDung = gv.MaGiaoVien
             WHERE nd.MaNguoiDung = :user_id
               AND nd.VaiTro = 'teacher'
               AND nd.TrangThai = 'active'",
            ['user_id' => $userId]
        );
        return $row === null ? null : (int) $row['MaGiaoVien'];
    }

    public function isCourseOwnedBy(int $courseId, int $teacherId): bool
    {
        return $this->queryOne(
            'SELECT MaKhoaHoc FROM KhoaHoc WHERE MaKhoaHoc = :id AND NguoiTao = :teacher',
            ['id' => $courseId, 'teacher' => $teacherId]
        ) !== null;
    }

    public function saveCourse(?int $courseId, array $course, bool $submitForApproval = false): int
    {
        $fields = [
            'TenKhoaHoc' => $course['TenKhoaHoc'],
            'DanhMuc' => $course['DanhMuc'],
            'MoTa' => $course['MoTa'],
            'Gia' => $course['Gia'],
            'AnhBia' => $course['AnhBia'],
            'NgayBatDau' => $course['NgayBatDau'],
            'NgayKetThuc' => $course['NgayKetThuc'],
            'NguoiTao' => $course['NguoiTao'],
            'TrangThai' => $course['TrangThai'],
        ];

        $this->db->beginTransaction();
        try {
            if ($courseId === null) {
                $fields['TrangThaiDuyet'] = $course['TrangThaiDuyet'];
                $columns = implode(', ', array_keys($fields));
                $placeholders = implode(', ', array_map(static fn (string $field): string => ':' . $field, array_keys($fields)));
                $this->execute("INSERT INTO KhoaHoc ({$columns}) VALUES ({$placeholders})", $fields);
                $courseId = $this->lastInsertId();
            } else {
                $fields['TrangThaiDuyet'] = $course['TrangThaiDuyet'];
                $set = implode(', ', array_map(static fn (string $field): string => "{$field} = :{$field}", array_keys($fields)));
                $fields['MaKhoaHoc'] = $courseId;
                $this->execute("UPDATE KhoaHoc SET {$set} WHERE MaKhoaHoc = :MaKhoaHoc", $fields);
            }

            if ($submitForApproval) {
                $reviewer = $this->queryOne(
                    "SELECT MaNguoiDung FROM NguoiDung
                     WHERE VaiTro = 'training' AND TrangThai = 'active'
                     ORDER BY MaNguoiDung LIMIT 1"
                );
                if ($reviewer === null) {
                    throw new DomainException('Không tìm thấy tài khoản đào tạo đang hoạt động để nhận duyệt.');
                }
                $this->execute(
                    "INSERT INTO DuyetKhoaHoc (MaKhoaHoc, MaNguoiDuyet, TrangThai, NhanXet)
                     VALUES (:course, :reviewer, 'pending', :comment)",
                    [
                        'course' => $courseId,
                        'reviewer' => $reviewer['MaNguoiDung'],
                        'comment' => 'Khóa học được gửi để bộ phận đào tạo xét duyệt.',
                    ]
                );
            }

            $this->db->commit();
            return $courseId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function setApprovalStatus(int $courseId, string $status, int $reviewerId): bool
    {
        $this->db->beginTransaction();
        try {
            $course = $this->queryOne(
                "SELECT TrangThaiDuyet FROM KhoaHoc WHERE MaKhoaHoc = :id FOR UPDATE",
                ['id' => $courseId]
            );
            if ($course === null || $course['TrangThaiDuyet'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            $this->execute(
                "UPDATE KhoaHoc SET TrangThaiDuyet = :status WHERE MaKhoaHoc = :id",
                ['status' => $status, 'id' => $courseId]
            );
            $this->execute(
                'INSERT INTO DuyetKhoaHoc (MaKhoaHoc, MaNguoiDuyet, TrangThai, NhanXet)
                 VALUES (:course, :reviewer, :status, :comment)',
                [
                    'course' => $courseId,
                    'reviewer' => $reviewerId,
                    'status' => $status,
                    'comment' => $status === 'approved' ? 'Khóa học đã được duyệt.' : 'Khóa học chưa được duyệt.',
                ]
            );
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteCourse(int $courseId): bool
    {
        return $this->execute('DELETE FROM KhoaHoc WHERE MaKhoaHoc = :id', ['id' => $courseId]);
    }

    public function saveChapter(?int $chapterId, int $courseId, string $name, string $description, int $order): void
    {
        if ($chapterId === null) {
            $this->execute(
                'INSERT INTO ChuongHoc (MaKhoaHoc, TenChuong, MoTaChuong, ThuTu)
                 VALUES (:course, :name, :description, :chapter_order)',
                ['course' => $courseId, 'name' => $name, 'description' => $description, 'chapter_order' => $order]
            );
            return;
        }

        $this->execute(
            'UPDATE ChuongHoc
             SET TenChuong = :name, MoTaChuong = :description, ThuTu = :chapter_order
             WHERE MaChuong = :chapter AND MaKhoaHoc = :course',
            [
                'name' => $name,
                'description' => $description,
                'chapter_order' => $order,
                'chapter' => $chapterId,
                'course' => $courseId,
            ]
        );
    }

    public function deleteChapter(int $chapterId, int $courseId): bool
    {
        return $this->execute(
            'DELETE FROM ChuongHoc WHERE MaChuong = :chapter AND MaKhoaHoc = :course',
            ['chapter' => $chapterId, 'course' => $courseId]
        );
    }

    public function getStudentCourseIds(int $studentId): array
    {
        return array_map(
            static fn (array $row): int => (int) $row['MaKhoaHoc'],
            $this->queryAll(
                "SELECT MaKhoaHoc FROM DangKyKhoaHoc
                 WHERE MaHocVien = :student AND TrangThaiTT <> 'cancelled'",
                ['student' => $studentId]
            )
        );
    }

    public function getStudentCourses(int $studentId): array
    {
        return $this->queryAll(
            "SELECT kh.*, nd.HoTen AS TenGiaoVien, dk.TrangThaiTT, dk.PhanTramHoanThanh,
                    dk.NgayDangKy, dk.PhuongThucTT
             FROM DangKyKhoaHoc dk
             JOIN KhoaHoc kh ON kh.MaKhoaHoc = dk.MaKhoaHoc
             JOIN GiaoVien gv ON gv.MaGiaoVien = kh.NguoiTao
             JOIN NguoiDung nd ON nd.MaNguoiDung = gv.MaGiaoVien
             WHERE dk.MaHocVien = :student AND dk.TrangThaiTT <> 'cancelled'
             ORDER BY dk.NgayDangKy DESC",
            ['student' => $studentId]
        );
    }

    public function getStudentAverageProgress(int $studentId): float
    {
        $row = $this->queryOne(
            'SELECT AVG(PhanTramHoanThanh) AS average_progress
             FROM DangKyKhoaHoc WHERE MaHocVien = :student AND TrangThaiTT = \'paid\'',
            ['student' => $studentId]
        );
        return (float) ($row['average_progress'] ?? 0);
    }

    public function registerStudent(int $studentId, int $courseId, string $paymentMethod): void
    {
        $course = $this->queryOne(
            "SELECT Gia FROM KhoaHoc
             WHERE MaKhoaHoc = :id AND TrangThaiDuyet = 'approved' AND TrangThai <> 'ended'",
            ['id' => $courseId]
        );
        if ($course === null) {
            throw new DomainException('Khóa học không còn mở đăng ký.');
        }
        $registration = $this->queryOne(
            'SELECT MaDangKy, TrangThaiTT FROM DangKyKhoaHoc WHERE MaHocVien = :student AND MaKhoaHoc = :course',
            ['student' => $studentId, 'course' => $courseId]
        );
        if ($registration !== null && $registration['TrangThaiTT'] !== 'cancelled') {
            throw new DomainException('Bạn đã đăng ký khóa học này.');
        }

        $transactionCode = 'KOS-' . $courseId . '-' . strtoupper(bin2hex(random_bytes(5)));
        if ($registration !== null) {
            $this->execute(
                "UPDATE DangKyKhoaHoc
                 SET SoTien = :amount, PhuongThucTT = :method, MaGDNganHang = :transaction,
                     TrangThaiTT = 'pending', PhanTramHoanThanh = 0, SoHoatDongDaHoc = 0,
                     NgayDangKy = CURRENT_TIMESTAMP
                 WHERE MaDangKy = :registration",
                [
                    'amount' => $course['Gia'],
                    'method' => $paymentMethod,
                    'transaction' => $transactionCode,
                    'registration' => $registration['MaDangKy'],
                ]
            );
            return;
        }
        $this->execute(
            "INSERT INTO DangKyKhoaHoc
                (MaHocVien, MaKhoaHoc, SoTien, PhuongThucTT, MaGDNganHang, TrangThaiTT)
             VALUES (:student, :course, :amount, :method, :transaction, 'pending')",
            [
                'student' => $studentId,
                'course' => $courseId,
                'amount' => $course['Gia'],
                'method' => $paymentMethod,
                'transaction' => $transactionCode,
            ]
        );
    }

    private function courseFilters(
        string $keyword,
        bool $onlyApproved,
        string $category,
        ?int $excludeStudentId,
        ?int $teacherId
    ): array {
        $conditions = [];
        $params = [];

        if ($onlyApproved) {
            $conditions[] = "kh.TrangThaiDuyet = 'approved'";
        }
        if ($keyword !== '') {
            $conditions[] = '(kh.TenKhoaHoc LIKE :keyword_name OR kh.DanhMuc LIKE :keyword_category)';
            $params['keyword_name'] = '%' . $keyword . '%';
            $params['keyword_category'] = '%' . $keyword . '%';
        }
        if ($category !== '') {
            $conditions[] = 'kh.DanhMuc = :category';
            $params['category'] = $category;
        }
        if ($excludeStudentId !== null) {
            $conditions[] = 'NOT EXISTS (
                SELECT 1 FROM DangKyKhoaHoc dk_filter
                WHERE dk_filter.MaKhoaHoc = kh.MaKhoaHoc AND dk_filter.MaHocVien = :student
            )';
            $params['student'] = $excludeStudentId;
        }
        if ($teacherId !== null) {
            $conditions[] = 'kh.NguoiTao = :teacher';
            $params['teacher'] = $teacherId;
        }

        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}
