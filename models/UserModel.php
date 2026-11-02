<?php
/**
 * Class UserModel: Thao tác với 3 bảng NguoiDung, HocVien, GiaoVien (Quan hệ 1 - 1)
 * Hỗ trợ Đăng nhập, Đăng ký, Tìm kiếm, Phân trang, Phân quyền và Cập nhật Avatar
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class UserModel extends BaseModel
{
    protected string $table = 'NguoiDung';
    protected string $primaryKey = 'MaNguoiDung';

    /**
     * Tìm người dùng theo Email hoặc SĐT để Đăng nhập
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
     * Đếm tổng số người dùng theo bộ lọc Vai trò & Từ khóa tìm kiếm (Phục vụ Phân trang Admin)
     */
    public function countUsers(string $keyword = '', string $role = 'all'): int
    {
        $where = "WHERE 1=1";
        $params = [];

        if ($role !== 'all' && $role !== '') {
            $where .= " AND nd.VaiTro = :role";
            $params['role'] = $role;
        }
        if ($keyword !== '') {
            $where .= " AND (nd.HoTen LIKE :kw1 OR nd.Email LIKE :kw2 OR nd.SDT LIKE :kw3)";
            $params['kw1'] = "%{$keyword}%";
            $params['kw2'] = "%{$keyword}%";
            $params['kw3'] = "%{$keyword}%";
        }

        $row = $this->queryOne("SELECT COUNT(*) AS total FROM NguoiDung nd {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Lấy danh sách người dùng có Tìm kiếm & Phân trang
     */
    public function getUsersPaginated(string $keyword, string $role, int $limit, int $offset): array
    {
        $where = "WHERE 1=1";
        $params = [];

        if ($role !== 'all' && $role !== '') {
            $where .= " AND nd.VaiTro = :role";
            $params['role'] = $role;
        }
        if ($keyword !== '') {
            $where .= " AND (nd.HoTen LIKE :kw1 OR nd.Email LIKE :kw2 OR nd.SDT LIKE :kw3)";
            $params['kw1'] = "%{$keyword}%";
            $params['kw2'] = "%{$keyword}%";
            $params['kw3'] = "%{$keyword}%";
        }

        $sql = "SELECT nd.*, hv.TongGioHoc, gv.ChuyenMonGV
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
        $hashed = password_hash($matKhau, PASSWORD_BCRYPT);
        $sqlUser = "INSERT INTO NguoiDung (Email, SDT, MatKhau, HoTen, VaiTro, TrangThai)
                    VALUES (:email, :sdt, :mk, :hoten, 'student', 'active')";
        $this->execute($sqlUser, [
            'email' => $email,
            'sdt'   => $sdt,
            'mk'    => $hashed,
            'hoten' => $hoTen
        ]);

        $newId = $this->lastInsertId();
        $sqlStudent = "INSERT INTO HocVien (MaHocVien, NgaySinh, GioiThieu, TongGioHoc)
                       VALUES (:id, :dob, 'Học viên mới tại KOS English Center.', 0)";
        $this->execute($sqlStudent, ['id' => $newId, 'dob' => $ngaySinh ?: null]);
        return $newId;
    }

    /**
     * Cập nhật hồ sơ cá nhân và Ảnh đại diện (Xử lý file phía Client/Admin)
     */
    public function updateProfile(int $userId, string $hoTen, string $sdt, ?string $anhDaiDien = null): bool
    {
        if ($anhDaiDien !== null) {
            $sql = "UPDATE NguoiDung SET HoTen = :hoten, SDT = :sdt, AnhDaiDien = :avatar WHERE MaNguoiDung = :id";
            return $this->execute($sql, ['hoten' => $hoTen, 'sdt' => $sdt, 'avatar' => $anhDaiDien, 'id' => $userId]);
        }
        $sql = "UPDATE NguoiDung SET HoTen = :hoten, SDT = :sdt WHERE MaNguoiDung = :id";
        return $this->execute($sql, ['hoten' => $hoTen, 'sdt' => $sdt, 'id' => $userId]);
    }

    /**
     * Phân quyền người dùng (Dành cho Bộ phận Quản lý)
     */
    public function updateRole(int $userId, string $newRole): bool
    {
        return $this->execute("UPDATE NguoiDung SET VaiTro = :role WHERE MaNguoiDung = :id", [
            'role' => $newRole,
            'id'   => $userId
        ]);
    }
}
