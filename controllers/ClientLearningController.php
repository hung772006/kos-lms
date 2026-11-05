<?php
class ClientLearningController extends BaseController
{
    private $learningModel;

    public function __construct()
    {
        $this->learningModel = new LearningModel();
    }

    // Hiển thị giao diện học tập chi tiết của khóa học
    public function show()
    {
        $courseId = $_GET['course_id'] ?? 1;
        $maHocVien = $_SESSION['user_id'] ?? 1; // ID học viên đang đăng nhập
        
        // Lấy danh sách hoạt động học tập thuộc khóa học này
        $activities = $this->learningModel->getActivitiesByCourseId((int)$courseId);

        // Lấy danh sách bài nộp của học viên để check trạng thái đã hoàn thành
        $submittedList = $this->learningModel->getSubmissionsByStudent((int)$maHocVien);
        $submittedActivityIds = array_column($submittedList, 'MaHoatDong');

        // Phân loại dữ liệu linh hoạt hơn để không bị sót video do khác tên định dạng Loai
        $videos = array_filter($activities, function($item) {
            $loai = strtolower(trim($item['Loai'] ?? ''));
            return in_array($loai, ['video', 'bai_giang', 'lectures']) || !empty($item['LinkVideo']);
        });

        $assignments = array_filter($activities, fn($item) => in_array(strtolower(trim($item['Loai'] ?? '')), ['assignment', 'quiz', 'bai_tap']));
        $materials = array_filter($activities, fn($item) => !empty($item['FileDinhKem']));

        $this->view('client/learning/show', [
            'activities' => $activities,
            'videos' => array_values($videos), // Reset lại key mảng cho chuẩn
            'assignments' => array_values($assignments),
            'materials' => array_values($materials),
            'submittedActivityIds' => $submittedActivityIds,
            'courseId' => $courseId
        ]);
    }

    // Xử lý nộp bài tập tự luận từ Client
    public function uploadAssignment()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maHoatDong = $_POST['MaHoatDong'] ?? 0;
            $courseId = $_POST['course_id'] ?? 1;
            $maHocVien = $_SESSION['user_id'] ?? 1;

            if (isset($_FILES['FileBaiNop']) && $_FILES['FileBaiNop']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['FileBaiNop']['tmp_name'];
                $fileName = $_FILES['FileBaiNop']['name'];
                $uploadFileDir = './uploads/submissions/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }

                $dest_path = $uploadFileDir . time() . '_' . basename($fileName);

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Lưu vào bảng BaiNop thông qua LearningModel
                    $this->learningModel->addSubmission([
                        'MaHoatDong' => $maHoatDong,
                        'MaHocVien' => $maHocVien,
                        'FileBaiNop' => $dest_path,
                        'NoiDungBaiLam' => 'Học viên nộp file tự luận.'
                    ]);

                    // Cập nhật tiến độ học tập tự động vào bảng DangKyKhoaHoc
                    $this->updateProgress($maHocVien, $maHoatDong);
                }
            }
            // Chuyển hướng về đúng khóa học đang học
            header('Location: index.php?controller=clientLearning&action=show&course_id=' . $courseId);
            exit();
        }
    }

    // Giao diện làm bài Quiz
    public function doQuiz()
    {
        $id = $_GET['id'] ?? 0;
        $quiz = $this->learningModel->getQuizById((int)$id);

        $this->view('client/learning/do_quiz', [
            'quiz' => $quiz
        ]);
    }

    // Xử lý nộp bài Quiz tự chấm điểm
    public function submitQuiz()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maHoatDong = $_POST['MaHoatDong'] ?? 0;
            $courseId = $_POST['course_id'] ?? 1;
            $maHocVien = $_SESSION['user_id'] ?? 1;
            $answers = $_POST['answer'] ?? [];

            // Lấy thông tin Quiz và đáp án đúng từ DB để chấm điểm tự động
            $quiz = $this->learningModel->getQuizById((int)$maHoatDong);
            $questions = json_decode($quiz['DuLieuQuizJSON'] ?? '[]', true);

            $score = 0;
            $totalQ = count($questions);

            foreach ($questions as $index => $q) {
                if (isset($answers[$index])) {
                    $hocVienChon = trim((string)$answers[$index]);
                    $dapAnDung = trim((string)($q['answer'] ?? ''));

                    // Hỗ trợ so sánh linh hoạt cả dạng chữ cái (A, B, C, D) lẫn dạng index số (0, 1, 2, 3)
                    $isCorrect = false;
                    
                    if (strcasecmp($hocVienChon, $dapAnDung) === 0) {
                        $isCorrect = true;
                    } else {
                        // Quy đổi chữ cái sang số nếu cần thiết (A=0, B=1, C=2, D=3)
                        $mapLetterToNum = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
                        $numDung = $mapLetterToNum[strtoupper($dapAnDung)] ?? -1;
                        if ((int)$hocVienChon === $numDung) {
                            $isCorrect = true;
                        }
                    }

                    if ($isCorrect) {
                        $score += (10.0 / ($totalQ > 0 ? $totalQ : 1));
                    }
                }
            }
            $finalScore = round($score, 1);

            // Gọi hàm từ Model để lưu kết quả bài nộp an toàn
            $this->learningModel->saveQuizSubmission(
                (int)$maHoatDong,
                (int)$maHocVien,
                json_encode($answers),
                $finalScore
            );

            // Cập nhật tiến độ học tập
            $this->updateProgress($maHocVien, $maHoatDong);

            header('Location: index.php?controller=clientLearning&action=show&course_id=' . $courseId);
            exit();
        }
    }

    // Hàm phụ trợ cập nhật tiến độ học tập thông qua Model
    private function updateProgress(int $maHocVien, int $maHoatDong)
    {
        $maKhoaHoc = $this->learningModel->getCourseIdByActivity($maHoatDong);
        if (!$maKhoaHoc) return;

        $totalAct = $this->learningModel->getTotalActivitiesByCourse($maKhoaHoc);
        $doneAct = $this->learningModel->getCompletedActivitiesCount($maHocVien, $maKhoaHoc);

        $percent = $totalAct > 0 ? round(($doneAct / $totalAct) * 100, 2) : 0;

        $this->learningModel->updateCourseProgress($maHocVien, $maKhoaHoc, $doneAct, $percent);
    }
}