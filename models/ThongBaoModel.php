<?php
/**
 * Class ThongBaoModel: Xử lý dữ liệu bảng thongbao
 * Phụ trách: Hiếu
 */

class ThongBaoModel extends BaseModel
{
    protected string $table = 'thongbao';
    protected string $primaryKey = 'MaThongBao';

    /**
     * Lấy danh sách thông báo của một người dùng có phân trang
     */
    public function getNotificationsByUser(int $maNguoiNhan, int $limit, int $offset): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE MaNguoiNhan = ? 
                ORDER BY NgayTao DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        // PDO bindValue với kiểu INT cho LIMIT và OFFSET
        $stmt->bindValue(1, $maNguoiNhan, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Đếm tổng số thông báo của user để phân trang
     */
    public function countNotificationsByUser(int $maNguoiNhan): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE MaNguoiNhan = ?";
        $row = $this->queryOne($sql, [$maNguoiNhan]);
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Tạo thông báo mới (dùng chung cho hệ thống khi duyệt khóa học, có bài nộp, v.v.)
     */
    public function createNotification(int $maNguoiNhan, ?int $maNguoiGui, ?int $maKhoaHoc, ?int $maHoatDong, string $loaiThongBao, string $tieuDe, string $noiDung): bool
    {
        $sql = "INSERT INTO {$this->table} (MaNguoiNhan, MaNguoiGui, MaKhoaHoc, MaHoatDong, LoaiThongBao, TieuDe, NoiDung, DaDoc, NgayTao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())";
        return $this->execute($sql, [$maNguoiNhan, $maNguoiGui, $maKhoaHoc, $maHoatDong, $loaiThongBao, $tieuDe, $noiDung]);
    }

    /**
     * Đánh dấu đã đọc một thông báo
     */
    public function markAsRead(int $maThongBao, int $maNguoiNhan): bool
    {
        $sql = "UPDATE {$this->table} SET DaDoc = 1 WHERE MaThongBao = ? AND MaNguoiNhan = ?";
        return $sql = $this->execute($sql, [$maThongBao, $maNguoiNhan]);
    }
}