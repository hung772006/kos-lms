<?php
class AdminGradingController extends BaseController
{
    private $learningModel;

    public function __construct()
    {
        $this->learningModel = new LearningModel();
    }

    // Hiển thị danh sách bài nộp và xử lý logic nhãn Muộn
    public function index()
    {
        $submissions = $this->learningModel->getAllSubmissions();
        
        // Logic kiểm tra thời gian nộp > hạn nộp để gán nhãn Muộn
        foreach ($submissions as &$sub) {
            $sub['isLate'] = false;
            if (!empty($sub['HanNop']) && !empty($sub['ThoiGianNop'])) {
                if (strtotime($sub['ThoiGianNop']) > strtotime($sub['HanNop'])) {
                    $sub['isLate'] = true;
                }
            }
        }

        $this->view('admin/grading/index', ['submissions' => $submissions]);
    }

    // Xử lý chấm điểm và nhận xét
    public function grade()
    {
        $id = $_GET['id'] ?? 0;
        $submission = $this->learningModel->getSubmissionById($id);

        if (!$submission) {
            header('Location: index.php?controller=adminGrading&action=index');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $score = $_POST['SoDiem'] ?? 0;
            $comment = $_POST['NhanXet'] ?? '';

            $this->learningModel->updateScore($id, (float)$score, $comment);
            header('Location: index.php?controller=adminGrading&action=index');
            exit();
        }

        $this->view('admin/grading/grade', ['submission' => $submission]);
    }
}