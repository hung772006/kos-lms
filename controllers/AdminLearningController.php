<?php
/**
 * Class AdminLearningController: Quản lý hoạt động học tập phía Admin/Giáo viên
 * Người phụ trách: Nguyễn Thị Khánh (Nhánh feature/khanh-learning)
 */

class AdminLearningController extends BaseController
{
    private LearningModel $learningModel;

    public function __construct()
    {
        // Chỉ cho phép Giáo viên và Bộ phận Quản lý quản trị nội dung đào tạo
        $this->requireRoles(['teacher', 'manager']);
        $this->learningModel = new LearningModel();
    }

    /**
     * 1. Hiển thị danh sách hoạt động học tập (Tích hợp Tìm kiếm & class Pagination chuẩn)
     */
    public function index(): void
    {
        $keyword  = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
        $courseId = max(0, (int) ($_GET['course_id'] ?? 0));
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $limit    = ITEMS_PER_PAGE;

        $totalRows  = (int) $this->learningModel->countActivities($keyword);
        $pagination = new Pagination($totalRows, $limit, $page);
        $activities = $this->learningModel->getActivitiesPaginated(
            $keyword,
            $pagination->getOffset(),
            $pagination->getLimit()
        );

        $this->view('admin/learning/index', [
            'activities'  => $activities,
            'keyword'     => $keyword,
            'courseId'    => $courseId > 0 ? $courseId : 1,
            'currentPage' => $page,
            'totalPages'  => $pagination->getTotalPages(),
            'pagination'  => $pagination,
            'pageTitle'   => 'Quản lý nội dung đào tạo',
        ]);
    }

    /**
     * 2. Thêm mới hoạt động học tập (Sử dụng FileUploader chuẩn cho PDF, ZIP, PPTX, DOCX)
     */
    public function create(): void
    {
        $courseId = max(1, (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 1));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tenHoatDong = trim((string) ($_POST['TenHoatDong'] ?? ''));
            $maChuong    = max(1, (int) ($_POST['MaChuong'] ?? 1));
            $rawLoai     = trim((string) ($_POST['Loai'] ?? 'video'));
            // Đồng bộ với ENUM('video', 'quiz', 'assignment') trong database_kos_lms.sql
            $loai        = ($rawLoai === 'file') ? 'video' : (in_array($rawLoai, ['video', 'quiz', 'assignment'], true) ? $rawLoai : 'video');
            $diemToiDa   = filter_var($_POST['DiemToiDa'] ?? 10, FILTER_VALIDATE_FLOAT);
            $thuTu       = max(1, (int) ($_POST['ThuTu'] ?? 1));
            $trangThai   = in_array($_POST['TrangThai'] ?? 'published', ['published', 'draft'], true)
                ? $_POST['TrangThai']
                : 'published';
            $hanNop      = !empty($_POST['HanNop']) ? trim((string) $_POST['HanNop']) : null;
            $quizJson    = ($loai === 'quiz' && isset($_POST['DuLieuQuizJSON']) && trim((string) $_POST['DuLieuQuizJSON']) !== '')
                ? trim((string) $_POST['DuLieuQuizJSON'])
                : null;

            if ($tenHoatDong === '') {
                $this->redirect(
                    'index.php?controller=adminlearning&action=create&course_id=' . $courseId,
                    'error',
                    'Tên hoạt động học tập không được để trống!'
                );
            }

            if ($diemToiDa === false || (float) $diemToiDa < 0 || (float) $diemToiDa > 100) {
                $this->redirect(
                    'index.php?controller=adminlearning&action=create&course_id=' . $courseId,
                    'error',
                    'Điểm tối đa phải nằm trong khoảng từ 0 đến 100!'
                );
            }

            if ($loai === 'quiz' && $quizJson !== null) {
                json_decode($quizJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->redirect(
                        'index.php?controller=adminlearning&action=create&course_id=' . $courseId,
                        'error',
                        'Dữ liệu câu hỏi Quiz không đúng định dạng JSON!'
                    );
                }
            }

            $data = [
                'MaChuong'       => $maChuong,
                'TenHoatDong'    => $tenHoatDong,
                'Loai'           => $loai,
                'NoiDung'        => isset($_POST['NoiDung']) ? trim((string) $_POST['NoiDung']) : null,
                'LinkVideo'      => !empty($_POST['LinkVideo']) ? trim((string) $_POST['LinkVideo']) : null,
                'FileDinhKem'    => null,
                'DinhDangFile'   => null,
                'DungLuongFile'  => null,
                'DuLieuQuizJSON' => $quizJson,
                'DiemToiDa'      => (float) $diemToiDa,
                'HanNop'         => $hanNop,
                'ThuTu'          => $thuTu,
                'TrangThai'      => $trangThai,
            ];

            // Upload file học liệu qua class FileUploader dùng chung
            if (isset($_FILES['FileDinhKem']) && $_FILES['FileDinhKem']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploader = new FileUploader('materials', ['pdf', 'zip', 'pptx', 'ppt', 'doc', 'docx'], 50);
                $upload   = $uploader->upload($_FILES['FileDinhKem']);
                if (!$upload['success']) {
                    $this->redirect(
                        'index.php?controller=adminlearning&action=create&course_id=' . $courseId,
                        'error',
                        $upload['error']
                    );
                }
                $data['FileDinhKem']   = $upload['path'];
                $data['DinhDangFile']  = $upload['ext'];
                $data['DungLuongFile'] = $upload['size'];
            }

            $this->learningModel->insertActivity($data);

            $this->redirect(
                'index.php?controller=adminlearning&action=index&course_id=' . $courseId,
                'success',
                'Đã thêm hoạt động học tập mới thành công!'
            );
        }

        $chapters = $this->learningModel->getAllChapters();
        $this->view('admin/learning/create', [
            'chapters'  => $chapters,
            'courseId'  => $courseId,
            'pageTitle' => 'Thêm hoạt động học tập',
        ]);
    }

    /**
     * 3. Chỉnh sửa hoạt động học tập (Bảo toàn đầy đủ các cột và file đính kèm cũ)
     */
    public function edit(): void
    {
        $id       = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
        $courseId = max(1, (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 1));

        if ($id < 1) {
            $this->redirect('index.php?controller=adminlearning&action=index&course_id=' . $courseId, 'error', 'Mã hoạt động không hợp lệ.');
        }

        $activity = $this->learningModel->getById($id);
        if (!$activity) {
            $this->redirect('index.php?controller=adminlearning&action=index&course_id=' . $courseId, 'error', 'Không tìm thấy hoạt động học tập cần chỉnh sửa.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tenHoatDong = trim((string) ($_POST['TenHoatDong'] ?? $activity['TenHoatDong']));
            $rawLoai     = trim((string) ($_POST['Loai'] ?? $activity['Loai']));
            $loai        = ($rawLoai === 'file') ? 'video' : (in_array($rawLoai, ['video', 'quiz', 'assignment'], true) ? $rawLoai : $activity['Loai']);
            $diemToiDa   = isset($_POST['DiemToiDa'])
                ? filter_var($_POST['DiemToiDa'], FILTER_VALIDATE_FLOAT)
                : (float) ($activity['DiemToiDa'] ?? 10.0);

            if ($tenHoatDong === '') {
                $this->redirect(
                    'index.php?controller=adminlearning&action=edit&id=' . $id . '&course_id=' . $courseId,
                    'error',
                    'Tên hoạt động học tập không được để trống!'
                );
            }

            if ($diemToiDa === false || (float) $diemToiDa < 0 || (float) $diemToiDa > 100) {
                $this->redirect(
                    'index.php?controller=adminlearning&action=edit&id=' . $id . '&course_id=' . $courseId,
                    'error',
                    'Điểm tối đa không hợp lệ!'
                );
            }

            $quizJson = array_key_exists('DuLieuQuizJSON', $_POST)
                ? (trim((string) $_POST['DuLieuQuizJSON']) !== '' ? trim((string) $_POST['DuLieuQuizJSON']) : null)
                : ($activity['DuLieuQuizJSON'] ?? null);

            if ($loai === 'quiz' && $quizJson !== null) {
                json_decode($quizJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->redirect(
                        'index.php?controller=adminlearning&action=edit&id=' . $id . '&course_id=' . $courseId,
                        'error',
                        'Dữ liệu câu hỏi Quiz không đúng định dạng JSON!'
                    );
                }
            }

            $data = [
                'MaChuong'       => max(1, (int) ($_POST['MaChuong'] ?? $activity['MaChuong'])),
                'TenHoatDong'    => $tenHoatDong,
                'Loai'           => $loai,
                'NoiDung'        => array_key_exists('NoiDung', $_POST) ? trim((string) $_POST['NoiDung']) : ($activity['NoiDung'] ?? null),
                'LinkVideo'      => array_key_exists('LinkVideo', $_POST)
                    ? (trim((string) $_POST['LinkVideo']) !== '' ? trim((string) $_POST['LinkVideo']) : null)
                    : ($activity['LinkVideo'] ?? null),
                'FileDinhKem'    => $activity['FileDinhKem'] ?? null,
                'DinhDangFile'   => $activity['DinhDangFile'] ?? null,
                'DungLuongFile'  => $activity['DungLuongFile'] ?? null,
                'DuLieuQuizJSON' => ($loai === 'quiz') ? $quizJson : null,
                'DiemToiDa'      => (float) $diemToiDa,
                'HanNop'         => array_key_exists('HanNop', $_POST)
                    ? (!empty($_POST['HanNop']) ? trim((string) $_POST['HanNop']) : null)
                    : ($activity['HanNop'] ?? null),
                'ThuTu'          => max(1, (int) ($_POST['ThuTu'] ?? ($activity['ThuTu'] ?? 1))),
                'TrangThai'      => in_array($_POST['TrangThai'] ?? '', ['published', 'draft'], true)
                    ? $_POST['TrangThai']
                    : ($activity['TrangThai'] ?? 'published'),
            ];

            // Nếu Giáo viên chọn upload file học liệu mới
            if (isset($_FILES['FileDinhKem']) && $_FILES['FileDinhKem']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploader = new FileUploader('materials', ['pdf', 'zip', 'pptx', 'ppt', 'doc', 'docx'], 50);
                $upload   = $uploader->upload($_FILES['FileDinhKem']);
                if (!$upload['success']) {
                    $this->redirect(
                        'index.php?controller=adminlearning&action=edit&id=' . $id . '&course_id=' . $courseId,
                        'error',
                        $upload['error']
                    );
                }
                $data['FileDinhKem']   = $upload['path'];
                $data['DinhDangFile']  = $upload['ext'];
                $data['DungLuongFile'] = $upload['size'];
            }

            $this->learningModel->updateActivity($id, $data);

            $this->redirect(
                'index.php?controller=adminlearning&action=index&course_id=' . $courseId,
                'success',
                'Đã cập nhật nội dung hoạt động học tập thành công!'
            );
        }

        $chapters = $this->learningModel->getAllChapters();
        $this->view('admin/learning/edit', [
            'activity'  => $activity,
            'chapters'  => $chapters,
            'courseId'  => $courseId,
            'pageTitle' => 'Chỉnh sửa hoạt động học tập',
        ]);
    }

    /**
     * 4. Xóa hoạt động học tập (Chặn xóa nếu đã có bài nộp của học viên)
     */
    public function delete(): void
    {
        $id       = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
        $courseId = max(1, (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 1));

        if ($id < 1) {
            $this->redirect('index.php?controller=adminlearning&action=index&course_id=' . $courseId, 'error', 'Mã hoạt động không hợp lệ.');
        }

        if (method_exists($this->learningModel, 'hasSubmissions') && $this->learningModel->hasSubmissions($id)) {
            $this->redirect(
                'index.php?controller=adminlearning&action=index&course_id=' . $courseId,
                'error',
                'Không thể xóa hoạt động đã có học viên nộp bài!'
            );
        }

        $deleted = $this->learningModel->deleteActivity($id);
        if ($deleted === false) {
            $this->redirect(
                'index.php?controller=adminlearning&action=index&course_id=' . $courseId,
                'error',
                'Không thể xóa hoạt động học tập này.'
            );
        }

        $this->redirect(
            'index.php?controller=adminlearning&action=index&course_id=' . $courseId,
            'success',
            'Đã xóa hoạt động học tập thành công!'
        );
    }
}