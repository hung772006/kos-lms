<?php
/**
 * Class Pagination: Hỗ trợ tính toán LIMIT/OFFSET và render thanh phân trang chuẩn HTML/CSS
 * Đáp ứng yêu cầu bắt buộc: Phân trang ở cả Admin & Client page
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class Pagination
{
    private int $totalItems;
    private int $itemsPerPage;
    private int $currentPage;
    private int $totalPages;

    public function __construct(int $totalItems, int $itemsPerPage = ITEMS_PER_PAGE, int $currentPage = 1)
    {
        $this->totalItems   = max(0, $totalItems);
        $this->itemsPerPage = max(1, $itemsPerPage);
        $this->totalPages   = max(1, (int) ceil($this->totalItems / $this->itemsPerPage));
        $this->currentPage  = max(1, min($currentPage, $this->totalPages));
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Render bộ nút phân trang giữ nguyên từ khóa tìm kiếm trên URL
     */
    public function render(string $baseUrl): string
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:24px;">';
        for ($i = 1; $i <= $this->totalPages; $i++) {
            $activeStyle = ($i === $this->currentPage)
                ? 'background:#c9a84c;color:#0d0d12;font-weight:700;border-color:#c9a84c;'
                : 'background:#fff;color:#0d0d12;border-color:#e2ddd6;';
            $url = $baseUrl . '&page=' . $i;
            $html .= "<a href='{$url}' style='padding:8px 14px;border:1.5px solid;border-radius:8px;text-decoration:none;font-size:13px;{$activeStyle}'>{$i}</a>";
        }
        $html .= '</div>';
        return $html;
    }
}