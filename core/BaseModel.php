<?php
/**
 * Class BaseModel: Lớp cơ sở cho tất cả các Model kế thừa (Tính kế thừa & đóng gói trong PHP OOP)
 * Hỗ trợ sẵn truy vấn Prepared Statements, Tìm kiếm và Phân trang
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

abstract class BaseModel
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Thực thi câu lệnh SELECT trả về nhiều dòng
     */
    protected function queryAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Thực thi câu lệnh SELECT trả về 1 dòng
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Thực thi INSERT / UPDATE / DELETE
     */
    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Lấy ID vừa thêm mới
     */
    protected function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
