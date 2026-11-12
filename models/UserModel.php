<?php
/**
 * Class UserModel: Thao tác với 3 bảng NguoiDung, HocVien, GiaoVien (Quan hệ 1 - 1)
 * Hỗ trợ Đăng nhập, Đăng ký, Hồ sơ cá nhân (Avatar) và Quản trị người dùng (CRUD, Phân quyền, Khóa)
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class UserModel extends BaseModel
{
    protected string $table = 'NguoiDung';
    protected string $primaryKey = 'MaNguoiDung';

    /**
     * Tìm người dùng theo Email hoặc SĐT để Đăng nhập (JOIN 1-1 HocVien & GiaoVien)
     */
    public function findByEmailOrPhone(string $identifier): ?array
    {
        $sql = "SELECT nd.*, hv.NgaySinh, hv.GioiThieu, hv.TongGioHoc, hv.LanTruyCapCuoi,
                       gv.ChuyenMonGV, gv.ChungChi, gv.SoNamKinhNghiem, gv.GioiThieuGV
                FROM NguoiDung nd
                LEFT JOIN HocVien hv ON nd.MaNguoiDung = hv.MaHocVien
                LEFT JOIN GiaoVien gv ON nd.MaNguoiDung = gv.MaGiaoVien
                WHERE nd.Email = :id1 OR nd.SDT = :id2
                LIMIT 1";
        return $this->queryOne($sql, ['id1' => $identifier, 'id2' => $identifier]);
    }

    /**
     * Lấy thông tin chi tiết 1 người dùng theo ID (JOIN 1-1 với HocVien và GiaoVien)
     */
    public function findById(int $userId): ?array
    {
        $sql = "SELECT nd.*, hv.NgaySinh, hv.GioiThieu, hv.TongGioHoc, hv.LanTruyCapCuoi,
                       gv.ChuyenMonGV, gv.ChungChi, gv.SoNamKinhNghiem, gv.GioiThieuGV
                FROM NguoiDung nd
                LEFT JOIN HocVien hv ON nd.MaNguoiDung = hv.MaHocVien
                LEFT JOIN GiaoVien gv ON nd.MaNguoiDung = gv.MaGiaoVien
                WHERE nd.MaNguoiDung = :id";
        return $this->queryOne($sql, ['id' => $userId]);
    }

    /**
     * Kiểm tra trùng lặp Email (loại trừ chính user đang sửa nếu có)
     */
    public function existsEmail(string $email, int $excludeId = 0): bool
    {
        $row = $this->queryOne(
            'SELECT MaNguoiDung FROM NguoiDung WHERE Email = :email AND MaNguoiDung != :ex LIMIT 1',
            ['email' => $email, 'ex' => $excludeId]
        );
        return $row !== null;
    }

    /**
     * Kiểm tra trùng lặp Số điện thoại (loại trừ chính user đang sửa nếu có)
     */
    public function existsPhone(string $sdt, int $excludeId = 0): bool
    {
        $row = $this->queryOne(
            'SELECT MaNguoiDung FROM NguoiDung WHERE SDT = :sdt AND MaNguoiDung != :ex LIMIT 1',
            ['sdt' => $sdt, 'ex' => $excludeId]
        );
        return $row !== null;
    }

    /**
     * Cập nhật thời điểm truy cập gần nhất cho Học viên
     */
    public function updateLastLogin(int $userId): void
    {
        $this->execute(
            'UPDATE HocVien SET LanTruyCapCuoi = NOW() WHERE MaHocVien = :id',
            ['id' => $userId]
        );
    }

    /**
     * Thống kê nhanh số lượng tài khoản theo nhóm vai trò & trạng thái cho trang Quản lý
     */
    public function getUserStats(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN VaiTro = 'student' THEN 1 ELSE 0 END) AS students,
                    SUM(CASE WHEN VaiTro = 'teacher' THEN 1 ELSE 0 END) AS teachers,
                    SUM(CASE WHEN VaiTro IN ('training', 'manager') THEN 1 ELSE 0 END) AS staff,
                    SUM(CASE WHEN TrangThai = 'inactive' THEN 1 ELSE 0 END) AS inactive
                FROM NguoiDung";
        $row = $this->queryOne($sql) ?? [];

        return [
            'total'    => (int) ($row['total'] ?? 0),
            'students' => (int) ($row['students'] ?? 0),
            'teachers' => (int) ($row['teachers'] ?? 0),
            'staff'    => (int) ($row['staff'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
        ];
    }

    /**
     * Đếm tổng số người dùng theo bộ lọc Vai trò, Trạng thái & Từ khóa tìm kiếm
     */
    public function countUsers(string $keyword = '', string $role = 'all', string $status = 'all'): int
    {
        $where  = 'WHERE 1=1';
        $params = [];

        if ($role !== 'all' && $role !== '') {
            $where .= ' AND nd.VaiTro = :role';
            $params['role'] = $role;
        }
        if ($status !== 'all' && $status !== '') {
            $where .= ' AND nd.TrangThai = :status';
            $params['status'] = $status;
        }
        if ($keyword !== '') {
            $where .= ' AND (nd.HoTen LIKE :kw1 OR nd.Email LIKE :kw2 OR nd.SDT LIKE :kw3)';
            $params['kw1'] = "%{$keyword}%";
            $params['kw2'] = "%{$keyword}%";
            $params['kw3'] = "%{$keyword}%";
        }

        $row = $this->queryOne("SELECT COUNT(*) AS total FROM NguoiDung nd {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Lấy danh sách người dùng có Tìm kiếm, Lọc & Phân trang kèm dữ liệu 1-1
     */
    public function getUsersPaginated(string $keyword, string $role, int $limit, int $offset, string $status = 'all'): array
    {
        $limit  = max(1, (int) $limit);
        $offset = max(0, (int) $offset);
        $where  = 'WHERE 1=1';
        $params = [];

        if ($role !== 'all' && $role !== '') {
            $where .= ' AND nd.VaiTro = :role';
            $params['role'] = $role;
        }
        if ($status !== 'all' && $status !== '') {
            $where .= ' AND nd.TrangThai = :status';
            $params['status'] = $status;
        }
        if ($keyword !== '') {
            $where .= ' AND (nd.HoTen LIKE :kw1 OR nd.Email LIKE :kw2 OR nd.SDT LIKE :kw3)';
            $params['kw1'] = "%{$keyword}%";
            $params['kw2'] = "%{$keyword}%";
            $params['kw3'] = "%{$keyword}%";
        }

        $sql = "SELECT nd.*,
                       hv.NgaySinh, hv.GioiThieu, hv.TongGioHoc, hv.LanTruyCapCuoi,
                       gv.ChuyenMonGV, gv.ChungChi, gv.SoNamKinhNghiem, gv.GioiThieuGV,
                       (SELECT COUNT(*) FROM KhoaHoc kh WHERE kh.NguoiTao = nd.MaNguoiDung) AS SoKhoaHocPhuTrach,
                       (SELECT COUNT(*) FROM DangKyKhoaHoc dk WHERE dk.MaHocVien = nd.MaNguoiDung) AS SoKhoaHocDangKy
                FROM NguoiDung nd
                LEFT JOIN HocVien hv ON nd.MaNguoiDung = hv.MaHocVien
                LEFT JOIN GiaoVien gv ON nd.MaNguoiDung = gv.MaGiaoVien
                {$where}
                ORDER BY nd.MaNguoiDung DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->queryAll($sql, $params);
    }

    /**
     * Đăng ký tài khoản Học viên mới (Thêm đồng thời vào NguoiDung và HocVien - Quan hệ 1-1)
     */
    public function createStudent(string $hoTen, string $email, string $sdt, string $matKhau, ?string $ngaySinh = null): int
    {
        $hashed  = password_hash($matKhau, PASSWORD_BCRYPT);
        $sqlUser = "INSERT INTO NguoiDung (Email, SDT, MatKhau, HoTen, VaiTro, TrangThai)
                    VALUES (:email, :sdt, :mk, :hoten, 'student', 'active')";
        $this->execute($sqlUser, [
            'email' => $email,
            'sdt'   => $sdt,
            'mk'    => $hashed,
            'hoten' => $hoTen,
        ]);

        $newId = (int) $this->lastInsertId();
        $this->syncRoleExtension($newId, 'student', ['NgaySinh' => $ngaySinh]);

        return $newId;
    }

    /**
     * Cập nhật hồ sơ cá nhân, Ảnh đại diện (Upload file Client) và thông tin mở rộng 1-1
     */
    public function updateProfile(int $userId, string $hoTen, string $sdt, ?string $anhDaiDien = null, array $extra = []): bool
    {
        if ($anhDaiDien !== null && $anhDaiDien !== '') {
            $sql = 'UPDATE NguoiDung SET HoTen = :hoten, SDT = :sdt, AnhDaiDien = :avatar WHERE MaNguoiDung = :id';
            $ok  = $this->execute($sql, ['hoten' => $hoTen, 'sdt' => $sdt, 'avatar' => $anhDaiDien, 'id' => $userId]);
        } else {
            $sql = 'UPDATE NguoiDung SET HoTen = :hoten, SDT = :sdt WHERE MaNguoiDung = :id';
            $ok  = $this->execute($sql, ['hoten' => $hoTen, 'sdt' => $sdt, 'id' => $userId]);
        }

        if ($ok && !empty($extra['VaiTro'])) {
            $this->syncRoleExtension($userId, (string) $extra['VaiTro'], $extra);
        }

        return $ok;
    }

    /**
     * Đổi mật khẩu người dùng (Mã hóa Bcrypt an toàn)
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->execute(
            'UPDATE NguoiDung SET MatKhau = :mk WHERE MaNguoiDung = :id',
            ['mk' => $hashed, 'id' => $userId]
        );
    }

    /**
     * Thêm mới người dùng từ trang Quản trị (Hỗ trợ cả 4 vai trò + Bảng mở rộng 1-1)
     */
    public function createUserByAdmin(array $data): int
    {
        $role   = in_array($data['VaiTro'] ?? '', ['student', 'teacher', 'training', 'manager'], true) ? $data['VaiTro'] : 'student';
        $status = in_array($data['TrangThai'] ?? '', ['active', 'inactive'], true) ? $data['TrangThai'] : 'active';
        $hashed = password_hash((string) $data['MatKhau'], PASSWORD_BCRYPT);

        $sql = 'INSERT INTO NguoiDung (Email, SDT, MatKhau, HoTen, VaiTro, TrangThai, AnhDaiDien)
                VALUES (:email, :sdt, :mk, :hoten, :role, :status, :avatar)';
        $this->execute($sql, [
            'email'  => $data['Email'],
            'sdt'    => $data['SDT'],
            'mk'     => $hashed,
            'hoten'  => $data['HoTen'],
            'role'   => $role,
            'status' => $status,
            'avatar' => $data['AnhDaiDien'] ?? null,
        ]);

        $newId = (int) $this->lastInsertId();
        $this->syncRoleExtension($newId, $role, $data);

        return $newId;
    }

    /**
     * Cập nhật thông tin người dùng từ trang Quản trị (Bao gồm Vai trò, Trạng thái, Avatar, Mật khẩu mới)
     */
    public function updateUserByAdmin(int $userId, array $data): bool
    {
        $role   = in_array($data['VaiTro'] ?? '', ['student', 'teacher', 'training', 'manager'], true) ? $data['VaiTro'] : 'student';
        $status = in_array($data['TrangThai'] ?? '', ['active', 'inactive'], true) ? $data['TrangThai'] : 'active';

        $fields = [
            'HoTen = :hoten',
            'Email = :email',
            'SDT = :sdt',
            'VaiTro = :role',
            'TrangThai = :status',
        ];
        $params = [
            'hoten'  => $data['HoTen'],
            'email'  => $data['Email'],
            'sdt'    => $data['SDT'],
            'role'   => $role,
            'status' => $status,
            'id'     => $userId,
        ];

        if (!empty($data['MatKhau'])) {
            $fields[]     = 'MatKhau = :mk';
            $params['mk'] = password_hash((string) $data['MatKhau'], PASSWORD_BCRYPT);
        }

        if (!empty($data['AnhDaiDien'])) {
            $fields[]         = 'AnhDaiDien = :avatar';
            $params['avatar'] = $data['AnhDaiDien'];
        }

        $sql = 'UPDATE NguoiDung SET ' . implode(', ', $fields) . ' WHERE MaNguoiDung = :id';
        $ok  = $this->execute($sql, $params);

        if ($ok) {
            $this->syncRoleExtension($userId, $role, $data);
        }

        return $ok;
    }

    /**
     * Phân quyền nhanh người dùng & Đồng bộ bảng 1-1 để tránh lỗi khóa ngoại ở KhoaHoc / DangKyKhoaHoc
     */
    public function updateRole(int $userId, string $newRole): bool
    {
        if (!in_array($newRole, ['student', 'teacher', 'training', 'manager'], true)) {
            return false;
        }

        $ok = $this->execute(
            'UPDATE NguoiDung SET VaiTro = :role WHERE MaNguoiDung = :id',
            ['role' => $newRole, 'id' => $userId]
        );

        if ($ok) {
            $this->syncRoleExtension($userId, $newRole, []);
        }

        return $ok;
    }

    /**
     * Đảo trạng thái Khóa / Mở khóa tài khoản (active <-> inactive)
     */
    public function toggleStatus(int $userId): ?string
    {
        $user = $this->findById($userId);
        if ($user === null) {
            return null;
        }

        $nextStatus = ($user['TrangThai'] === 'active') ? 'inactive' : 'active';
        $this->execute(
            'UPDATE NguoiDung SET TrangThai = :status WHERE MaNguoiDung = :id',
            ['status' => $nextStatus, 'id' => $userId]
        );

        return $nextStatus;
    }

    /**
     * Kiểm tra số khóa học Giáo viên đang đứng lớp (tránh lỗi ON DELETE RESTRICT trên bảng KhoaHoc)
     */
    public function countTeacherCourses(int $userId): int
    {
        $row = $this->queryOne('SELECT COUNT(*) AS total FROM KhoaHoc WHERE NguoiTao = :id', ['id' => $userId]);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Xóa tài khoản người dùng (Tự động xóa dây chuyền bảng 1-1 HocVien/GiaoVien nhờ ON DELETE CASCADE)
     */
    public function deleteUser(int $userId): bool
    {
        return $this->execute('DELETE FROM NguoiDung WHERE MaNguoiDung = :id', ['id' => $userId]);
    }

    /**
     * Đảm bảo bản ghi 1-1 trong bảng HocVien hoặc GiaoVien luôn tồn tại và đồng bộ dữ liệu
     */
    private function syncRoleExtension(int $userId, string $role, array $data): void
    {
        if ($role === 'student') {
            $exists = $this->queryOne('SELECT MaHocVien FROM HocVien WHERE MaHocVien = :id', ['id' => $userId]);
            $dob    = !empty($data['NgaySinh']) ? $data['NgaySinh'] : null;
            $bio    = array_key_exists('GioiThieu', $data) ? $data['GioiThieu'] : 'Học viên tại KOS English Center.';

            if ($exists === null) {
                $this->execute(
                    'INSERT INTO HocVien (MaHocVien, NgaySinh, GioiThieu, TongGioHoc) VALUES (:id, :dob, :bio, 0)',
                    ['id' => $userId, 'dob' => $dob, 'bio' => $bio]
                );
            } elseif (array_key_exists('NgaySinh', $data) || array_key_exists('GioiThieu', $data)) {
                $this->execute(
                    'UPDATE HocVien SET NgaySinh = :dob, GioiThieu = :bio WHERE MaHocVien = :id',
                    ['dob' => $dob, 'bio' => $data['GioiThieu'] ?? null, 'id' => $userId]
                );
            }
        } elseif ($role === 'teacher') {
            $exists    = $this->queryOne('SELECT MaGiaoVien FROM GiaoVien WHERE MaGiaoVien = :id', ['id' => $userId]);
            $chuyenMon = trim((string) ($data['ChuyenMonGV'] ?? ''));
            if ($chuyenMon === '') {
                $chuyenMon = 'Giảng viên Tiếng Anh KOS';
            }
            $chungChi = $data['ChungChi'] ?? null;
            $kinhNgh  = isset($data['SoNamKinhNghiem']) ? max(0, (int) $data['SoNamKinhNghiem']) : 1;
            $gioiTh   = $data['GioiThieuGV'] ?? null;

            if ($exists === null) {
                $this->execute(
                    'INSERT INTO GiaoVien (MaGiaoVien, ChuyenMonGV, ChungChi, SoNamKinhNghiem, GioiThieuGV)
                     VALUES (:id, :cm, :cc, :kn, :gt)',
                    ['id' => $userId, 'cm' => $chuyenMon, 'cc' => $chungChi, 'kn' => $kinhNgh, 'gt' => $gioiTh]
                );
            } elseif (
                array_key_exists('ChuyenMonGV', $data)
                || array_key_exists('ChungChi', $data)
                || array_key_exists('SoNamKinhNghiem', $data)
                || array_key_exists('GioiThieuGV', $data)
            ) {
                $this->execute(
                    'UPDATE GiaoVien
                     SET ChuyenMonGV = :cm, ChungChi = :cc, SoNamKinhNghiem = :kn, GioiThieuGV = :gt
                     WHERE MaGiaoVien = :id',
                    ['cm' => $chuyenMon, 'cc' => $chungChi, 'kn' => $kinhNgh, 'gt' => $gioiTh, 'id' => $userId]
                );
            }
        }
    }
}