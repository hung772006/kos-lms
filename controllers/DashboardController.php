<?php
/**
 * Class DashboardController: Điều phối màn hình chính sau khi đăng nhập cho 4 vai trò
 * Người phụ trách: Nguyễn Thành Hưng (Khung chính) + Minh, Khánh, Hiếu ghép module
 */

class DashboardController extends BaseController
{
    private CourseModel $courseModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->courseModel = new CourseModel();
        $this->userModel   = new UserModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $user    = Session::getUser();
        $keyword = trim($_GET['q'] ?? '');
        $page    = max(1, (int) ($_GET['page'] ?? 1));

        // Minh họa Phân trang + Tìm kiếm khóa học ngay trên trang danh sách
        $totalCourses = $this->courseModel->countCourses($keyword, $user['VaiTro'] === 'student');
        $pagination   = new Pagination($totalCourses, ITEMS_PER_PAGE, $page);
        $courses      = $this->courseModel->getCoursesPaginated(
            $keyword,
            $pagination->getLimit(),
            $pagination->getOffset(),
            $user['VaiTro'] === 'student'
        );

        $this->view('dashboard/index', [
            'user'       => $user,
            'courses'    => $courses,
            'keyword'    => $keyword,
            'pagination' => $pagination,
        ]);
    }
}
