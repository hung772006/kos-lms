<?php
/**
 * Class AdminGradingController: Quản lý danh sách bài nộp, chấm điểm và nhận xét của Giáo viên
 * Người phụ trách: Nguyễn Thị Khánh (Nhánh feature/khanh-learning)
 */

class AdminGradingController extends BaseController
{
    private LearningModel $learningModel;

    public function __construct()
    {
        // Chỉ cho phép Giáo viên và Bộ phận Quản lý truy cập chấm bài (dùng hàm chuẩn của BaseController)
        $this->requireRoles(['teacher', 'manager']);
        $this->learningModel = new LearningModel();
    }

    /**
     * Hiển thị danh sách bài nộp (Tích hợp Tìm kiếm, Phân trang và gắn nhãn Nộp muộn chuẩn xác)
     */
    public function index(): void
    {
        $user    = Session::getUser();
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status  = trim((string) ($_GET['status'] ?? 'all'));
        $page    = max(1, (int) ($_GET['page'] ?? 1));

        $allSubmissions = $this->learningModel->getAllSubmissions();

        // Nếu đăng nhập là Giáo viên và dữ liệu có cột NguoiTao, chỉ hiển thị bài nộp thuộc khóa của Giáo viên đó
        if (($user['VaiTro'] ?? '') === 'teacher') {
            $teacherId = (int) ($user['MaNguoiDung'] ?? 0);
            $allSubmissions = array_values(array_filter(
                $allSubmissions,
                static fn (array $row): bool => !isset($row['NguoiTao']) || (int) $row['NguoiTao'] === $teacherId
            ));
        }

        // Gắn nhãn Muộn chính xác theo cuối ngày HanNop (23:59:59) hoặc trạng thái 'late' trong CSDL
        foreach ($allSubmissions as &$sub) {
            $sub['isLate'] = $this->checkIsLate($sub);
        }
        unset($sub);

        // Lọc theo từ khóa tìm kiếm và trạng thái bài nộp
        $filtered = array_values(array_filter(
            $allSubmissions,
            static function (array $sub) use ($keyword, $status): bool {
                if ($status !== 'all' && $status !== '') {
                    if ($status === 'late' && empty($sub['isLate'])) {
                        return false;
                    }
                    if ($status !== 'late' && ($sub['TrangThai'] ?? '') !== $status) {
                        return false;
                    }
                }

                if ($keyword === '') {
                    return true;
                }

                $kwLower  = mb_strtolower($keyword, 'UTF-8');
                $haystack = implode(' ', [
                    (string) ($sub['HoTen'] ?? ''),
                    (string) ($sub['TenHocVien'] ?? ''),
                    (string) ($sub['TenHoatDong'] ?? ''),
                    (string) ($sub['TenKhoaHoc'] ?? ''),
                    (string) ($sub['NoiDungBaiLam'] ?? ''),
                ]);

                return mb_strpos(mb_strtolower($haystack, 'UTF-8'), $kwLower) !== false;
            }
        ));

        // Áp dụng class Pagination dùng chung của hệ thống
        $totalItems  = count($filtered);
        $pagination  = new Pagination($totalItems, ITEMS_PER_PAGE, $page);
        $submissions = array_slice($filtered, $pagination->getOffset(), $pagination->getLimit());

        $this->view('admin/grading/index', [
            'user'        => $user,
            'submissions' => $submissions,
            'keyword'     => $keyword,
            'status'      => $status,
            'pagination'  => $pagination,
            'totalItems'  => $totalItems,
            'pageTitle'   => 'Chấm điểm & Nhận xét bài nộp',
        ]);
    }

    /**
     * Xem chi tiết bài làm và lưu Điểm số + Nhận xét
     */
    public function grade(): void
    {
        $user = Session::getUser();
        $id   = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? $_POST['MaBaiNop'] ?? 0));

        if ($id < 1) {
            $this->redirect('index.php?controller=admingrading&action=index', 'error', 'Mã bài nộp không hợp lệ.');
        }

        $submission = $this->learningModel->getSubmissionById($id);
        if (!$submission) {
            $this->redirect('index.php?controller=admingrading&action=index', 'error', 'Không tìm thấy bài nộp cần chấm.');
        }

        if (($user['VaiTro'] ?? '') === 'teacher' && isset($submission['NguoiTao']) && (int) $submission['NguoiTao'] !== (int) $user['MaNguoiDung']) {
            $this->redirect('index.php?controller=admingrading&action=index', 'error', 'Bạn không có quyền chấm bài nộp của khóa học khác.');
        }

        $submission['isLate'] = $this->checkIsLate($submission);
        $maxScore = (isset($submission['DiemToiDa']) && (float) $submission['DiemToiDa'] > 0)
            ? (float) $submission['DiemToiDa']
            : 10.0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawScore = $_POST['SoDiem'] ?? null;
            $score    = filter_var($rawScore, FILTER_VALIDATE_FLOAT);
            $comment  = trim((string) ($_POST['NhanXet'] ?? ''));

            if ($score === false || !is_finite((float) $score) || (float) $score < 0 || (float) $score > $maxScore) {
                $this->redirect(
                    'index.php?controller=admingrading&action=grade&id=' . $id,
                    'error',
                    "Điểm số không hợp lệ! Vui lòng nhập điểm từ 0 đến {$maxScore}."
                );
            }

            $this->learningModel->updateScore($id, round((float) $score, 2), $comment);

            $this->redirect(
                'index.php?controller=admingrading&action=index',
                'success',
                'Đã lưu điểm số và nhận xét bài nộp thành công!'
            );
        }

        $this->view('admin/grading/grade', [
            'user'       => $user,
            'submission' => $submission,
            'maxScore'   => $maxScore,
            'pageTitle'  => 'Chấm bài nộp học viên',
        ]);
    }

    /**
     * Kiểm tra bài nộp có quá hạn hay không (Hỗ trợ cả kiểu DATE và DATETIME của cột HanNop)
     */
    private function checkIsLate(array $sub): bool
    {
        if (($sub['TrangThai'] ?? '') === 'late') {
            return true;
        }

        if (empty($sub['HanNop']) || empty($sub['ThoiGianNop'])) {
            return false;
        }

        $deadlineStr = trim((string) $sub['HanNop']);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadlineStr) === 1) {
            $deadlineStr .= ' 23:59:59';
        }

        $submittedTs = strtotime((string) $sub['ThoiGianNop']);
        $deadlineTs  = strtotime($deadlineStr);

        return $submittedTs !== false && $deadlineTs !== false && $submittedTs > $deadlineTs;
    }
}