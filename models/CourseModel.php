<?php
/**
 * Class CourseModel: Quản lý 3 bảng KhoaHoc, ChuongHoc, DangKyKhoaHoc
 * Người phụ trách: Minh (Nhánh feature/minh-courses)
 */

class CourseModel extends BaseModel
{
    protected string $table = 'KhoaHoc';
    protected string $primaryKey = 'MaKhoaHoc';

    /**
     * Lấy danh sách khóa học có Tìm kiếm & Phân trang
     */
    public function getCoursesPaginated(string $keyword = '', int $limit = ITEMS_PER_PAGE, int $offset = 0, bool $onlyApproved = false): array
    {
        $where = "WHERE 1=1";
        $params = [];
        if ($onlyApproved) {
            $where .= " AND kh.TrangThaiDuyet = 'approved'";
        }
        if ($keyword !== '') {
            $where .= " AND (kh.TenKhoaHoc LIKE :kw1 OR kh.DanhMuc LIKE :kw2)";
            $params['kw1'] = "%{$keyword}%";
            $params['kw2'] = "%{$keyword}%";
        }
        $sql = "SELECT kh.*, nd.HoTen AS TenGiaoVien,
                       (SELECT COUNT(*) FROM DangKyKhoaHoc dk WHERE dk.MaKhoaHoc = kh.MaKhoaHoc) AS SoHocVien
                FROM KhoaHoc kh
                JOIN GiaoVien gv ON kh.NguoiTao = gv.MaGiaoVien
                JOIN NguoiDung nd ON gv.MaGiaoVien = nd.MaNguoiDung
                {$where}
                ORDER BY kh.MaKhoaHoc DESC
                LIMIT {$limit} OFFSET {$offset}";
        return $this->queryAll($sql, $params);
    }

    public function countCourses(string $keyword = '', bool $onlyApproved = false): int
    {
        $where = "WHERE 1=1";
        $params = [];
        if ($onlyApproved) {
            $where .= " AND TrangThaiDuyet = 'approved'";
        }
        if ($keyword !== '') {
            $where .= " AND (TenKhoaHoc LIKE :kw1 OR DanhMuc LIKE :kw2)";
            $params['kw1'] = "%{$keyword}%";
            $params['kw2'] = "%{$keyword}%";
        }
        $row = $this->queryOne("SELECT COUNT(*) AS total FROM KhoaHoc {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }
}
