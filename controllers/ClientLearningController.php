<?php
/**
 * Class ClientLearningController: Điều phối Xem bài giảng, Tải tài liệu, Nộp bài tập và Làm Quiz ở Client
 * Người phụ trách: Nguyễn Thị Khánh (Nhánh feature/khanh-learning)
 */

class ClientLearningController extends BaseController
{
    private LearningModel $learningModel;
    private CourseModel $courseModel;

    public function __construct()
    {
        $this->requireLogin();
        $this->learningModel = new LearningModel();
        $this->courseModel   = new CourseModel();
    }

    /**
     * Hỗ trợ điều hướng mặc định nếu gọi action=index
     */
    public function index(): void
    {
        $this->show();
    }

    /**
     * 1. Hiển thị không gian học tập chi tiết của khóa học (Video/Slide, Tài liệu, Bài tập, Quiz)
     */
    public function show(): void
    {
        $user     = Session::getUser();
        $courseId = max(1, (int) ($_GET['course_id'] ?? 1));
        $userId   = (int) ($user['MaNguoiDung'] ?? 0);

        $course = $this->courseModel->getCourseDetail($courseId);
        if ($course === null) {
            $this->redirect('index.php?controller=dashboard&action=index', 'error', 'Không tìm thấy khóa học yêu cầu.');
        }

        // Nếu là Học viên thì kiểm tra đã đăng ký khóa học chưa
        $maHocVien = $userId;
        if (($user['VaiTro'] ?? '') === 'student') {
            $studentId = $this->courseModel->getStudentIdForUser($userId);
            if ($studentId === null) {
                $this->redirect('index.php?controller=auth&action=logout', 'error', 'Hồ sơ học viên không hợp lệ.');
            }
            $maHocVien   = $studentId;
            $enrolledIds = $this->courseModel->getStudentCourseIds($maHocVien);
            if (!in_array($courseId, $enrolledIds, true)) {
                $this->redirect(
                    'index.php?controller=dashboard&action=index&view=courses&detail=' . $courseId,
                    'error',
                    'Vui lòng đăng ký khóa học trước khi truy cập nội dung học tập!'
                );
            }
        }

        // Lấy danh sách hoạt động đã công bố thuộc đúng khóa học
        $activities = $this->learningModel->getActivitiesByCourseId($courseId);

        // Lấy lịch sử bài nộp & điểm số của học viên hiện tại
        $submittedList        = $this->learningModel->getSubmissionsByStudent($maHocVien);
        $submittedActivityIds = array_map('intval', array_column($submittedList, 'MaHoatDong'));
        $submissionsMap       = [];
        foreach ($submittedList as $sub) {
            $actKey = (int) $sub['MaHoatDong'];
            if (!isset($submissionsMap[$actKey])) {
                $submissionsMap[$actKey] = $sub;
            }
        }

        // Phân loại hoạt động ra các tab Bài học, Bài tập/Quiz và Tài liệu tải về
        $videos = array_filter($activities, static function (array $item): bool {
            $loai = strtolower(trim((string) ($item['Loai'] ?? '')));
            return in_array($loai, ['video', 'bai_giang', 'lectures'], true) || !empty($item['LinkVideo']);
        });

        $assignments = array_filter($activities, static function (array $item): bool {
            $loai = strtolower(trim((string) ($item['Loai'] ?? '')));
            return in_array($loai, ['assignment', 'quiz', 'bai_tap'], true);
        });

        $materials = array_filter($activities, static fn (array $item): bool => !empty($item['FileDinhKem']));

        // Tính phần trăm tiến độ hiện tại của khóa học
        $totalAct = $this->learningModel->getTotalActivitiesByCourse($courseId);
        $doneAct  = $this->learningModel->getCompletedActivitiesCount($maHocVien, $courseId);
        $progress = count($activities) > 0 ? min(100, round(($doneAct / $totalAct) * 100, 0)) : 0;

        $this->view('client/learning/show', [
            'user'                 => $user,
            'course'               => $course,
            'courseId'             => $courseId,
            'progress'             => $progress,
            'activities'           => $activities,
            'videos'               => array_values($videos),
            'assignments'          => array_values($assignments),
            'materials'            => array_values($materials),
            'submittedActivityIds' => $submittedActivityIds,
            'submissionsMap'       => $submissionsMap,
            'pageTitle'            => $course['TenKhoaHoc'] ?? 'Tham gia học tập',
        ]);
    }

    /**
     * 2. Xử lý Học viên nộp file bài tập tự luận ở Client (Sử dụng class FileUploader chuẩn)
     */
    public function uploadAssignment(): void
    {
        $this->requireRoles(['student']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=dashboard&action=index&view=mine');
        }

        $user       = Session::getUser();
        $maHocVien  = $this->courseModel->getStudentIdForUser((int) ($user['MaNguoiDung'] ?? 0));
        $maHoatDong = max(0, (int) ($_POST['MaHoatDong'] ?? 0));
        $courseId   = max(1, (int) ($_POST['course_id'] ?? 1));
        $ghiChu     = trim((string) ($_POST['NoiDungBaiLam'] ?? ''));

        if ($maHocVien === null || $maHoatDong < 1) {
            $this->redirect(
                'index.php?controller=clientlearning&action=show&course_id=' . $courseId,
                'error',
                'Thông tin bài tập nộp không hợp lệ.'
            );
        }

        $realCourseId = $this->learningModel->getCourseIdByActivity($maHoatDong) ?? $courseId;
        $enrolledIds  = $this->courseModel->getStudentCourseIds($maHocVien);
        if (!in_array($realCourseId, $enrolledIds, true)) {
            $this->redirect('index.php?controller=dashboard&action=index&view=courses', 'error', 'Bạn chưa đăng ký khóa học này.');
        }

        if (!isset($_FILES['FileBaiNop']) || $_FILES['FileBaiNop']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->redirect(
                'index.php?controller=clientlearning&action=show&course_id=' . $realCourseId,
                'error',
                'Vui lòng chọn file bài làm (.pdf, .docx, .zip...) trước khi bấm nộp!'
            );
        }

        $uploader = new FileUploader('submissions', ['pdf', 'doc', 'docx', 'zip', 'pptx', 'txt', 'png', 'jpg', 'jpeg'], 50);
        $upload   = $uploader->upload($_FILES['FileBaiNop']);

        if (!$upload['success']) {
            $this->redirect(
                'index.php?controller=clientlearning&action=show&course_id=' . $realCourseId,
                'error',
                $upload['error']
            );
        }

        $this->learningModel->addSubmission([
            'MaHoatDong'    => $maHoatDong,
            'MaHocVien'     => $maHocVien,
            'FileBaiNop'    => $upload['path'],
            'NoiDungBaiLam' => $ghiChu !== '' ? $ghiChu : 'Học viên nộp file bài tập tự luận.',
        ]);

        $this->updateProgress($maHocVien, $maHoatDong);

        $this->redirect(
            'index.php?controller=clientlearning&action=show&course_id=' . $realCourseId,
            'success',
            'Nộp bài tập thành công! Hệ thống đã ghi nhận bài làm và cập nhật tiến độ học tập.'
        );
    }

    /**
     * 3. Hiển thị giao diện làm bài kiểm tra trắc nghiệm (Quiz)
     */
    public function doQuiz(): void
    {
        $id   = max(0, (int) ($_GET['id'] ?? 0));
        $quiz = $id > 0 ? $this->learningModel->getQuizById($id) : null;

        if (!$quiz || ($quiz['Loai'] ?? '') !== 'quiz') {
            $fallbackCourse = max(1, (int) ($_GET['course_id'] ?? 1));
            $this->redirect(
                'index.php?controller=clientlearning&action=show&course_id=' . $fallbackCourse,
                'error',
                'Không tìm thấy bài kiểm tra trắc nghiệm này.'
            );
        }

        $courseId = (int) ($quiz['MaKhoaHoc'] ?? $this->learningModel->getCourseIdByActivity($id) ?? ($_GET['course_id'] ?? 1));

        $this->view('client/learning/do_quiz', [
            'quiz'      => $quiz,
            'courseId'  => $courseId,
            'pageTitle' => 'Làm bài Quiz: ' . ($quiz['TenHoatDong'] ?? ''),
        ]);
    }

    /**
     * 4. Chấm điểm tự động bài Quiz và đồng bộ tiến độ hoàn thành khóa học
     */
    public function submitQuiz(): void
    {
        $this->requireRoles(['student']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?controller=dashboard&action=index&view=mine');
        }

        $user       = Session::getUser();
        $maHocVien  = $this->courseModel->getStudentIdForUser((int) ($user['MaNguoiDung'] ?? 0));
        $maHoatDong = max(0, (int) ($_POST['MaHoatDong'] ?? 0));
        $courseId   = max(1, (int) ($_POST['course_id'] ?? 1));
        $answers    = is_array($_POST['answer'] ?? null) ? $_POST['answer'] : [];

        $quiz = $maHoatDong > 0 ? $this->learningModel->getQuizById($maHoatDong) : null;
        if ($maHocVien === null || !$quiz) {
            $this->redirect(
                'index.php?controller=clientlearning&action=show&course_id=' . $courseId,
                'error',
                'Dữ liệu bài kiểm tra không hợp lệ.'
            );
        }

        $realCourseId = (int) ($quiz['MaKhoaHoc'] ?? $this->learningModel->getCourseIdByActivity($maHoatDong) ?? $courseId);
        $decoded      = json_decode((string) ($quiz['DuLieuQuizJSON'] ?? '[]'), true);
        $questions    = is_array($decoded) ? array_values($decoded) : [];
        $totalQ       = count($questions);

        if ($totalQ === 0) {
            $this->redirect(
                'index.php?controller=clientlearning&action=show&course_id=' . $realCourseId,
                'error',
                'Bài Quiz này chưa có câu hỏi nào để chấm điểm.'
            );
        }

        $maxScore     = (isset($quiz['DiemToiDa']) && (float) $quiz['DiemToiDa'] > 0) ? (float) $quiz['DiemToiDa'] : 10.0;
        $correctCount = 0;

        foreach ($questions as $index => $q) {
            if (!array_key_exists($index, $answers)) {
                continue;
            }
            $options       = is_array($q['options'] ?? null) ? $q['options'] : [];
            $studentLetter = $this->normalizeQuizChoice($answers[$index], $options);
            $correctLetter = $this->normalizeQuizChoice($q['answer'] ?? '', $options);

            if ($studentLetter !== '' && $studentLetter === $correctLetter) {
                $correctCount++;
            }
        }

        $finalScore = round(($correctCount / $totalQ) * $maxScore, 1);

        $this->learningModel->saveQuizSubmission(
            $maHoatDong,
            $maHocVien,
            (string) json_encode($answers, JSON_UNESCAPED_UNICODE),
            $finalScore
        );

        $this->updateProgress($maHocVien, $maHoatDong);

        $this->redirect(
            'index.php?controller=clientlearning&action=show&course_id=' . $realCourseId,
            'success',
            "Hoàn thành bài Quiz! Bạn trả lời đúng {$correctCount}/{$totalQ} câu — Điểm số: {$finalScore}/{$maxScore}."
        );
    }

    /**
     * Chuẩn hóa đáp án Quiz về chữ cái A/B/C/D (Hỗ trợ cả chỉ số 0..3, chữ A..D hoặc nội dung phương án)
     */
    private function normalizeQuizChoice(mixed $choice, array $options = []): string
    {
        if ($choice === null) {
            return '';
        }
        $str = trim((string) $choice);
        if ($str === '') {
            return '';
        }

        $indexMap = ['0' => 'A', '1' => 'B', '2' => 'C', '3' => 'D'];
        if (isset($indexMap[$str])) {
            return $indexMap[$str];
        }

        $upper = strtoupper($str);
        if (in_array($upper, ['A', 'B', 'C', 'D'], true)) {
            return $upper;
        }

        foreach (array_values($options) as $idx => $optText) {
            if (trim((string) $optText) === $str && isset($indexMap[(string) $idx])) {
                return $indexMap[(string) $idx];
            }
        }

        return $upper;
    }

    /**
     * Đồng bộ lại phần trăm tiến độ hoàn thành khóa học vào bảng DangKyKhoaHoc
     */
    private function updateProgress(int $maHocVien, int $maHoatDong): void
    {
        $maKhoaHoc = $this->learningModel->getCourseIdByActivity($maHoatDong);
        if (!$maKhoaHoc) {
            return;
        }

        $totalAct = $this->learningModel->getTotalActivitiesByCourse($maKhoaHoc);
        $doneAct  = $this->learningModel->getCompletedActivitiesCount($maHocVien, $maKhoaHoc);
        $percent  = $totalAct > 0 ? round(($doneAct / $totalAct) * 100, 2) : 0.0;

        $this->learningModel->updateCourseProgress($maHocVien, $maKhoaHoc, $doneAct, $percent);
    }
}