<?php
/**
 * Class CourseModel: Quản lý 3 bảng KhoaHoc, ChuongHoc, DangKyKhoaHoc
 * Người phụ trách: Phan Hoàng Minh (Nhánh feature/minh-courses)
 * Đã chuẩn hóa MVC, ràng buộc xóa an toàn và đồng bộ trạng thái thanh toán
 */
class CourseModel extends BaseModel
{
    protected string $table = 'KhoaHoc';
    protected string $primaryKey = 'MaKhoaHoc';

    /**
     * Lấy danh sách khóa học có Tìm kiếm & Phân trang
     */
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

    /**
     * Đếm tổng số khóa học (Đồng bộ JOIN với getCoursesPaginated để dùng cho Pagination)
     */
    public function countCourses(
        string $keyword = '',
        bool $onlyApproved = false,
        string $category = '',
        ?int $excludeStudentId = null,
        ?int $teacherId = null
    ): int {
        [$where, $params] = $this->courseFilters($keyword, $onlyApproved, $category, $excludeStudentId, $teacherId);
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total
             FROM KhoaHoc kh
             JOIN GiaoVien gv ON kh.NguoiTao = gv.MaGiaoVien
             JOIN NguoiDung nd ON gv.MaGiaoVien = nd.MaNguoiDung
             {$where}",
            $params
        );
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

    /**
     * Lấy MaHocVien và tự động đồng bộ bản ghi 1-1 trong HocVien nếu tài khoản vừa được đổi quyền
     */
    public function getStudentIdForUser(int $userId): ?int
    {
        $user = $this->queryOne(
            "SELECT MaNguoiDung FROM NguoiDung
             WHERE MaNguoiDung = :user_id AND VaiTro = 'student' AND TrangThai = 'active'",
            ['user_id' => $userId]
        );
        if ($user === null) {
            return null;
        }

        $row = $this->queryOne(
            'SELECT MaHocVien FROM HocVien WHERE MaHocVien = :user_id',
            ['user_id' => $userId]
        );
        if ($row === null) {
            $this->execute(
                "INSERT INTO HocVien (MaHocVien, GioiThieu, TongGioHoc)
                 VALUES (:id, 'Học viên tại KOS English Center.', 0)",
                ['id' => $userId]
            );
        }

        return $userId;
    }

    /**
     * Lấy MaGiaoVien và tự động đồng bộ bản ghi 1-1 trong GiaoVien nếu tài khoản vừa được phân quyền
     */
    public function getTeacherIdForUser(int $userId): ?int
    {
        $user = $this->queryOne(
            "SELECT MaNguoiDung FROM NguoiDung
             WHERE MaNguoiDung = :user_id AND VaiTro = 'teacher' AND TrangThai = 'active'",
            ['user_id' => $userId]
        );
        if ($user === null) {
            return null;
        }

        $row = $this->queryOne(
            'SELECT MaGiaoVien FROM GiaoVien WHERE MaGiaoVien = :user_id',
            ['user_id' => $userId]
        );
        if ($row === null) {
            $this->execute(
                "INSERT INTO GiaoVien (MaGiaoVien, ChuyenMonGV, SoNamKinhNghiem)
                 VALUES (:id, 'Giảng viên Tiếng Anh', 1)",
                ['id' => $userId]
            );
        }

        return $userId;
    }

    public function isCourseOwnedBy(int $courseId, int $teacherId): bool
    {
        return $this->queryOne(
            'SELECT MaKhoaHoc FROM KhoaHoc WHERE MaKhoaHoc = :id AND NguoiTao = :teacher',
            ['id' => $courseId, 'teacher' => $teacherId]
        ) !== null;
    }

    /**
     * Thêm mới hoặc cập nhật khóa học (Giữ lại AnhBia cũ nếu sửa mà không upload ảnh mới)
     */
    public function saveCourse(?int $courseId, array $course, bool $submitForApproval = false): int
    {
        $existing = $courseId !== null ? $this->getCourseById($courseId) : null;
        if ($courseId !== null && $existing === null) {
            throw new DomainException('Khóa học cần chỉnh sửa không tồn tại.');
        }

        $anhBia = $course['AnhBia'] ?? null;
        if ($courseId !== null && ($anhBia === null || $anhBia === '')) {
            $anhBia = $existing['AnhBia'] ?? null;
        }

        $fields = [
            'TenKhoaHoc'     => trim((string) ($course['TenKhoaHoc'] ?? '')),
            'DanhMuc'        => trim((string) ($course['DanhMuc'] ?? 'IELTS')),
            'MoTa'           => $course['MoTa'] ?? null,
            'Gia'            => (float) ($course['Gia'] ?? 0),
            'AnhBia'         => $anhBia,
            'NgayBatDau'     => $course['NgayBatDau'],
            'NgayKetThuc'    => $course['NgayKetThuc'],
            'NguoiTao'       => (int) $course['NguoiTao'],
            'TrangThai'      => $course['TrangThai'] ?? ($existing['TrangThai'] ?? 'upcoming'),
            'TrangThaiDuyet' => $course['TrangThaiDuyet'] ?? ($existing['TrangThaiDuyet'] ?? 'pending'),
        ];

        $this->db->beginTransaction();
        try {
            if ($courseId === null) {
                $columns = implode(', ', array_keys($fields));
                $placeholders = implode(', ', array_map(static fn (string $field): string => ':' . $field, array_keys($fields)));
                $this->execute("INSERT INTO KhoaHoc ({$columns}) VALUES ({$placeholders})", $fields);
                $courseId = $this->lastInsertId();
            } else {
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
                if ($reviewer !== null) {
                    $this->execute(
                        "INSERT INTO DuyetKhoaHoc (MaKhoaHoc, MaNguoiDuyet, TrangThai, NhanXet)
                         VALUES (:course, :reviewer, 'pending', :comment)",
                        [
                            'course'   => $courseId,
                            'reviewer' => $reviewer['MaNguoiDung'],
                            'comment'  => 'Khóa học mới được gửi để bộ phận đào tạo xét duyệt.',
                        ]
                    );
                    $this->execute(
                        "INSERT INTO ThongBao (MaNguoiNhan, MaNguoiGui, MaKhoaHoc, LoaiThongBao, TieuDe, NoiDung)
                         VALUES (:receiver, :sender, :course, 'approval', :title, :content)",
                        [
                            'receiver' => $reviewer['MaNguoiDung'],
                            'sender'   => (int) $course['NguoiTao'],
                            'course'   => $courseId,
                            'title'    => 'Khóa học mới chờ phê duyệt: ' . $fields['TenKhoaHoc'],
                            'content'  => 'Giáo viên vừa gửi yêu cầu xét duyệt khóa học "' . $fields['TenKhoaHoc'] . '".',
                        ]
                    );
                }
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

    /**
     * Hỗ trợ tương thích duyệt khóa học (Khuyến nghị gọi qua ApprovalReportModel của Hiếu khi ghép nhánh)
     */
    public function setApprovalStatus(int $courseId, string $status, int $reviewerId, string $comment = ''): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $course = $this->queryOne(
                "SELECT MaKhoaHoc, TenKhoaHoc, NguoiTao, TrangThaiDuyet
                 FROM KhoaHoc WHERE MaKhoaHoc = :id FOR UPDATE",
                ['id' => $courseId]
            );
            if ($course === null || $course['TrangThaiDuyet'] !== 'pending') {
                $this->db->rollBack();
                return false;
            }

            $finalComment = trim($comment) !== ''
                ? trim($comment)
                : ($status === 'approved' ? 'Khóa học đã được phê duyệt công bố.' : 'Khóa học chưa đạt yêu cầu phê duyệt.');

            $this->execute(
                'UPDATE KhoaHoc SET TrangThaiDuyet = :status WHERE MaKhoaHoc = :id',
                ['status' => $status, 'id' => $courseId]
            );
            $this->execute(
                'INSERT INTO DuyetKhoaHoc (MaKhoaHoc, MaNguoiDuyet, TrangThai, NhanXet)
                 VALUES (:course, :reviewer, :status, :comment)',
                [
                    'course'   => $courseId,
                    'reviewer' => $reviewerId,
                    'status'   => $status,
                    'comment'  => $finalComment,
                ]
            );
            $this->execute(
                "INSERT INTO ThongBao (MaNguoiNhan, MaNguoiGui, MaKhoaHoc, LoaiThongBao, TieuDe, NoiDung)
                 VALUES (:receiver, :sender, :course, 'approval', :title, :content)",
                [
                    'receiver' => (int) $course['NguoiTao'],
                    'sender'   => $reviewerId,
                    'course'   => $courseId,
                    'title'    => $status === 'approved'
                        ? 'Khóa học "' . $course['TenKhoaHoc'] . '" đã được phê duyệt'
                        : 'Khóa học "' . $course['TenKhoaHoc'] . '" bị từ chối duyệt',
                    'content'  => $finalComment,
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

    /**
     * Kiểm tra điều kiện được phép xóa khóa học (Chặn xóa khi đã có học viên hoặc đang diễn ra)
     */
    public function canDeleteCourse(int $courseId): array
    {
        $course = $this->getCourseById($courseId);
        if ($course === null) {
            return ['can_delete' => false, 'reason' => 'Khóa học không tồn tại.'];
        }
        if ($course['TrangThai'] === 'active') {
            return ['can_delete' => false, 'reason' => 'Không thể xóa khóa học đang diễn ra.'];
        }

        $enrolled = $this->queryOne(
            "SELECT COUNT(*) AS total FROM DangKyKhoaHoc
             WHERE MaKhoaHoc = :id AND TrangThaiTT <> 'cancelled'",
            ['id' => $courseId]
        );
        if ((int) ($enrolled['total'] ?? 0) > 0) {
            return ['can_delete' => false, 'reason' => 'Không thể xóa khóa học đã có học viên đăng ký.'];
        }

        return ['can_delete' => true, 'reason' => ''];
    }

    /**
     * Xóa khóa học an toàn (Trả về false nếu vi phạm ràng buộc nghiệp vụ)
     */
    public function deleteCourse(int $courseId): bool
    {
        $check = $this->canDeleteCourse($courseId);
        if (!$check['can_delete']) {
            return false;
        }

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
                'name'          => $name,
                'description'   => $description,
                'chapter_order' => $order,
                'chapter'       => $chapterId,
                'course'        => $courseId,
            ]
        );
    }

    /**
     * Xóa chương học (Chặn xóa nếu trong chương đã có bài nộp của học viên)
     */
    public function deleteChapter(int $chapterId, int $courseId): bool
    {
        $submissions = $this->queryOne(
            'SELECT COUNT(*) AS total
             FROM BaiNop bn
             JOIN HoatDongHocTap hd ON bn.MaHoatDong = hd.MaHoatDong
             WHERE hd.MaChuong = :chapter',
            ['chapter' => $chapterId]
        );
        if ((int) ($submissions['total'] ?? 0) > 0) {
            return false;
        }

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
                    dk.SoHoatDongDaHoc, dk.NgayDangKy, dk.PhuongThucTT, dk.MaGDNganHang
             FROM DangKyKhoaHoc dk
             JOIN KhoaHoc kh ON kh.MaKhoaHoc = dk.MaKhoaHoc
             JOIN GiaoVien gv ON gv.MaGiaoVien = kh.NguoiTao
             JOIN NguoiDung nd ON nd.MaNguoiDung = gv.MaGiaoVien
             WHERE dk.MaHocVien = :student AND dk.TrangThaiTT = 'paid'
             ORDER BY dk.NgayDangKy DESC",
            ['student' => $studentId]
        );
    }

    public function getStudentAverageProgress(int $studentId): float
    {
        $row = $this->queryOne(
            "SELECT AVG(PhanTramHoanThanh) AS average_progress
             FROM DangKyKhoaHoc WHERE MaHocVien = :student AND TrangThaiTT = 'paid'",
            ['student' => $studentId]
        );
        return round((float) ($row['average_progress'] ?? 0), 2);
    }

    /**
     * Đăng ký và thanh toán khóa học trực tuyến (Ghi nhận TrangThaiTT = 'paid' và tạo thông báo)
     */
    public function registerStudent(int $studentId, int $courseId, string $paymentMethod): void
    {
        $validStudentId = $this->getStudentIdForUser($studentId);
        if ($validStudentId === null) {
            throw new DomainException('Tài khoản học viên không hợp lệ.');
        }

        $course = $this->queryOne(
            "SELECT MaKhoaHoc, TenKhoaHoc, Gia, NguoiTao FROM KhoaHoc
             WHERE MaKhoaHoc = :id AND TrangThaiDuyet = 'approved' AND TrangThai <> 'ended'",
            ['id' => $courseId]
        );
        if ($course === null) {
            throw new DomainException('Khóa học không tồn tại hoặc không còn mở đăng ký.');
        }

        $registration = $this->queryOne(
            'SELECT MaDangKy, TrangThaiTT FROM DangKyKhoaHoc WHERE MaHocVien = :student AND MaKhoaHoc = :course',
            ['student' => $validStudentId, 'course' => $courseId]
        );
        if ($registration !== null && $registration['TrangThaiTT'] !== 'cancelled') {
            throw new DomainException('Bạn đã đăng ký khóa học này rồi.');
        }

        $allowedMethods = ['bank', 'card', 'momo'];
        $method = in_array($paymentMethod, $allowedMethods, true) ? $paymentMethod : 'bank';
        $transactionCode = 'EDUPRO-' . $courseId . '-' . strtoupper(bin2hex(random_bytes(3)));

        $this->db->beginTransaction();
        try {
            if ($registration !== null) {
                $this->execute(
                    "UPDATE DangKyKhoaHoc
                     SET SoTien = :amount, PhuongThucTT = :method, MaGDNganHang = :transaction,
                         TrangThaiTT = 'paid', PhanTramHoanThanh = 0.00, SoHoatDongDaHoc = 0,
                         NgayDangKy = CURRENT_TIMESTAMP
                     WHERE MaDangKy = :registration",
                    [
                        'amount'       => $course['Gia'],
                        'method'       => $method,
                        'transaction'  => $transactionCode,
                        'registration' => $registration['MaDangKy'],
                    ]
                );
            } else {
                $this->execute(
                    "INSERT INTO DangKyKhoaHoc
                        (MaHocVien, MaKhoaHoc, SoTien, PhuongThucTT, MaGDNganHang, TrangThaiTT, PhanTramHoanThanh, SoHoatDongDaHoc)
                     VALUES (:student, :course, :amount, :method, :transaction, 'paid', 0.00, 0)",
                    [
                        'student'     => $validStudentId,
                        'course'      => $courseId,
                        'amount'      => $course['Gia'],
                        'method'      => $method,
                        'transaction' => $transactionCode,
                    ]
                );
            }

            $this->execute(
                "INSERT INTO ThongBao (MaNguoiNhan, MaNguoiGui, MaKhoaHoc, LoaiThongBao, TieuDe, NoiDung)
                 VALUES (:receiver, :sender, :course, 'enrollment', :title, :content)",
                [
                    'receiver' => $validStudentId,
                    'sender'   => (int) $course['NguoiTao'],
                    'course'   => $courseId,
                    'title'    => 'Bạn đã đăng ký thành công ' . $course['TenKhoaHoc'],
                    'content'  => $course['TenKhoaHoc'] . ' · Mã giao dịch: ' . $transactionCode,
                ]
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
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
            $conditions[] = '(kh.TenKhoaHoc LIKE :keyword_name OR kh.DanhMuc LIKE :keyword_category OR nd.HoTen LIKE :keyword_teacher)';
            $params['keyword_name']     = '%' . $keyword . '%';
            $params['keyword_category'] = '%' . $keyword . '%';
            $params['keyword_teacher']  = '%' . $keyword . '%';
        }
        if ($category !== '') {
            $conditions[] = 'kh.DanhMuc = :category';
            $params['category'] = $category;
        }
        if ($excludeStudentId !== null) {
            $conditions[] = "NOT EXISTS (
                SELECT 1 FROM DangKyKhoaHoc dk_filter
                WHERE dk_filter.MaKhoaHoc = kh.MaKhoaHoc
                  AND dk_filter.MaHocVien = :student
                  AND dk_filter.TrangThaiTT <> 'cancelled'
            )";
            $params['student'] = $excludeStudentId;
        }
        if ($teacherId !== null) {
            $conditions[] = 'kh.NguoiTao = :teacher';
            $params['teacher'] = $teacherId;
        }

        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}