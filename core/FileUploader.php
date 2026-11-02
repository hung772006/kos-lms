<?php
/**
 * Class FileUploader: Xử lý kiểm tra định dạng, dung lượng và upload file an toàn
 * Đáp ứng yêu cầu bắt buộc: Xử lý file ở cả Admin & Client page
 * Người phụ trách: Nguyễn Thành Hưng (Nhóm trưởng)
 */

class FileUploader
{
    private array $allowedExtensions;
    private int $maxSizeMB;
    private string $subFolder;

    public function __construct(string $subFolder = 'materials', array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'zip'], int $maxSizeMB = 50)
    {
        $this->subFolder         = trim($subFolder, '/');
        $this->allowedExtensions = array_map('strtolower', $allowedExtensions);
        $this->maxSizeMB         = $maxSizeMB;
    }

    /**
     * Thực hiện upload file từ $_FILES[$fieldName]
     * Trả về mảng ['success' => bool, 'path' => string, 'error' => string, 'ext' => string, 'size' => string]
     */
    public function upload(array $file): array
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'Không có file nào được chọn.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Lỗi trong quá trình tải file lên server.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions, true)) {
            return [
                'success' => false,
                'error'   => 'Định dạng file .' . $ext . ' không hợp lệ! Chỉ chấp nhận: ' . implode(', ', $this->allowedExtensions)
            ];
        }

        $maxBytes = $this->maxSizeMB * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return ['success' => false, 'error' => "Dung lượng file vượt quá giới hạn {$this->maxSizeMB}MB!"];
        }

        $targetDir = UPLOAD_DIR . $this->subFolder . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $targetFile   = $targetDir . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return ['success' => false, 'error' => 'Không thể lưu file vào thư mục đích.'];
        }

        $sizeMB = round($file['size'] / (1024 * 1024), 2) . ' MB';

        return [
            'success' => true,
            'path'    => 'public/uploads/' . $this->subFolder . '/' . $safeFileName,
            'ext'     => strtoupper($ext),
            'size'    => $sizeMB,
            'error'   => ''
        ];
    }
}
