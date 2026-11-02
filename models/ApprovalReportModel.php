<?php
/**
 * Class ApprovalReportModel: Quản lý 2 bảng DuyetKhoaHoc, ThongBao và Thống kê báo cáo
 * Người phụ trách: Hiếu (Nhánh feature/hieu-approval-reports)
 */

class ApprovalReportModel extends BaseModel
{
    public function getPendingCourses(): array
    {
        $sql = "SELECT kh.*, nd.HoTen AS TenGiaoVien
                FROM KhoaHoc kh
                JOIN NguoiDung nd ON kh.NguoiTao = nd.MaNguoiDung
                WHERE kh.TrangThaiDuyet = 'pending'
                ORDER BY kh.NgayTao DESC";
        return $this->queryAll($sql);
    }

    public function getNotificationsByUser(int $userId): array
    {
        $sql = "SELECT * FROM ThongBao WHERE MaNguoiNhan = :uid ORDER BY NgayTao DESC";
        return $this->queryAll($sql, ['uid' => $userId]);
    }
}
