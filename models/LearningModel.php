<?php
/**
 * Class LearningModel: Quản lý 2 bảng HoatDongHocTap và BaiNop
 * Người phụ trách: Khánh (Nhánh feature/khanh-learning)
 */

class LearningModel extends BaseModel
{
    protected string $table = 'HoatDongHocTap';
    protected string $primaryKey = 'MaHoatDong';

    public function getActivitiesByCourse(int $courseId): array
    {
        $sql = "SELECT hd.*, ch.TenChuong
                FROM HoatDongHocTap hd
                JOIN ChuongHoc ch ON hd.MaChuong = ch.MaChuong
                WHERE ch.MaKhoaHoc = :cid
                ORDER BY ch.ThuTu ASC, hd.ThuTu ASC";
        return $this->queryAll($sql, ['cid' => $courseId]);
    }
}
