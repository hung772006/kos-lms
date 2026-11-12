<?php
/**
 * Class DashboardController: Điều phối màn hình Khóa học, Chương học & Đăng ký học cho các vai trò
 * Người phụ trách: Nguyễn Thành Hưng (Khung chính) + Phan Hoàng Minh (Module Khóa học & Đăng ký)
 */
class DashboardController extends BaseController
{
    private CourseModel $courseModel;

    public function __construct()
    {
        $this->courseModel = new CourseModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $user      = Session::getUser();
        $isStudent = $user['VaiTro'] === 'student';
        $canManage = in_array($user['VaiTro'], ['teacher', 'training', 'manager'], true);
        $view      = trim($_GET['view'] ?? ($isStudent ? 'courses' : 'manage'));

        if (($view === 'mine' && !$isStudent) || ($view === 'manage' && !$canManage)) {
            $view = $isStudent ? 'courses' : 'manage';
        }
        if (!in_array($view, ['courses', 'manage', 'mine'], true)) {
            $view = $isStudent ? 'courses' : 'manage';
        }

        $keyword         = trim($_GET['q'] ?? '');
        $category        = trim($_GET['category'] ?? '');
        $knownCategories = ['IELTS', 'TOEIC', 'Giao tiếp', 'Business', 'Thiếu nhi', 'Phát âm', 'Ngữ pháp'];
        if ($category !== '' && !in_array($category, $knownCategories, true)) {
            $category = '';
        }

        $studentId = $isStudent
            ? $this->courseModel->getStudentIdForUser((int) $user['MaNguoiDung'])
            : null;
        if ($isStudent && $studentId === null) {
            $this->redirect('index.php?controller=auth&action=logout', 'error', 'Tài khoản học viên chưa có hồ sơ hợp lệ.');
        }

        $teacherId = $user['VaiTro'] === 'teacher'
            ? $this->courseModel->getTeacherIdForUser((int) $user['MaNguoiDung'])
            : null;
        if ($user['VaiTro'] === 'teacher' && $teacherId === null) {
            $this->redirect('index.php?controller=auth&action=logout', 'error', 'Tài khoản giáo viên chưa có hồ sơ hợp lệ.');
        }

        $excludeStudentId = ($isStudent && $view === 'courses') ? $studentId : null;
        $onlyApproved     = $isStudent;
        $page             = max(1, (int) ($_GET['page'] ?? 1));

        $totalCourses = 0;
        $courses      = [];
        $pagination   = new Pagination(0, ITEMS_PER_PAGE, $page);

        if ($view !== 'mine') {
            $totalCourses = $this->courseModel->countCourses(
                $keyword,
                $onlyApproved,
                $category,
                $excludeStudentId,
                $teacherId
            );
            $pagination = new Pagination($totalCourses, ITEMS_PER_PAGE, $page);
            $courses    = $this->courseModel->getCoursesPaginated(
                $keyword,
                $pagination->getLimit(),
                $pagination->getOffset(),
                $onlyApproved,
                $category,
                $excludeStudentId,
                $teacherId
            );
        }

        $enrolledCourseIds = $isStudent ? $this->courseModel->getStudentCourseIds($studentId) : [];

        $detail   = null;
        $detailId = max(0, (int) ($_GET['detail'] ?? 0));
        if ($detailId > 0) {
            $detail        = $this->courseModel->getCourseDetail($detailId);
            $canViewDetail = $detail !== null
                && (
                    ($isStudent && $detail['TrangThaiDuyet'] === 'approved')
                    || (!$isStudent && ($user['VaiTro'] !== 'teacher'
                        || ($teacherId !== null && $this->courseModel->isCourseOwnedBy($detailId, $teacherId))))
                );

            if (!$canViewDetail) {
                $this->redirect($this->dashboardUrl($view, $keyword, $category, $page), 'error', 'Khóa học không tồn tại hoặc bạn không có quyền xem.');
            }
            $detail['ChuongHoc']  = $this->courseModel->getChapters($detailId);
            $detail['IsEnrolled'] = $isStudent && in_array($detailId, $enrolledCourseIds, true);
        }

        $courseForm = null;
        if ($canManage && isset($_GET['course_form'])) {
            $courseFormId = (int) $_GET['course_form'];
            if ($courseFormId > 0) {
                $courseForm = $this->courseModel->getCourseById($courseFormId);
                if ($courseForm === null || !$this->canManageCourse($courseFormId, $user)) {
                    $this->redirect($this->dashboardUrl('manage'), 'error', 'Bạn không có quyền chỉnh sửa khóa học này.');
                }
            }
        }

        $myCourses = [];
        if ($view === 'mine' && $studentId !== null) {
            $myCourses = $this->courseModel->getStudentCourses($studentId);
            if ($keyword !== '') {
                $kwLower   = mb_strtolower($keyword, 'UTF-8');
                $myCourses = array_values(array_filter(
                    $myCourses,
                    static fn (array $c): bool => mb_strpos(mb_strtolower($c['TenKhoaHoc'], 'UTF-8'), $kwLower) !== false
                        || mb_strpos(mb_strtolower($c['DanhMuc'], 'UTF-8'), $kwLower) !== false
                        || mb_strpos(mb_strtolower($c['TenGiaoVien'], 'UTF-8'), $kwLower) !== false
                ));
            }
        }

        $this->view('dashboard/index', [
            'user'            => $user,
            'view'            => $view,
            'courses'         => $courses,
            'keyword'         => $keyword,
            'category'        => $category,
            'page'            => $page,
            'categories'      => $knownCategories,
            'pagination'      => $pagination,
            'totalCourses'    => $totalCourses,
            'detail'          => $detail,
            'courseForm'      => $courseForm,
            'teachers'        => ($canManage && $user['VaiTro'] !== 'teacher') ? $this->courseModel->getTeacherOptions() : [],
            'myCourses'       => $myCourses,
            'averageProgress' => ($isStudent && $studentId !== null) ? $this->courseModel->getStudentAverageProgress($studentId) : 0,
            'csrfToken'       => $this->csrfToken(),
            'canManage'       => $canManage,
        ]);
    }

    public function saveCourse(): void
    {
        $this->requireRoles(['teacher', 'training', 'manager']);
        $this->verifyCsrf();
        $user = Session::getUser();

        $courseId = max(0, (int) ($_POST['MaKhoaHoc'] ?? 0));
        if ($courseId > 0 && !$this->canManageCourse($courseId, $user)) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Bạn không có quyền chỉnh sửa khóa học này.');
        }

        $name        = trim($_POST['TenKhoaHoc'] ?? '');
        $category    = trim($_POST['DanhMuc'] ?? '');
        $description = trim($_POST['MoTa'] ?? '');
        $startDate   = trim($_POST['NgayBatDau'] ?? '');
        $endDate     = trim($_POST['NgayKetThuc'] ?? '');
        $price       = filter_var($_POST['Gia'] ?? null, FILTER_VALIDATE_FLOAT);

        if (
            preg_match('/^.{1,150}$/us', $name) !== 1
            || $description === ''
            || !in_array($category, ['IELTS', 'TOEIC', 'Giao tiếp', 'Business', 'Thiếu nhi', 'Phát âm', 'Ngữ pháp'], true)
            || !$this->isValidDate($startDate)
            || !$this->isValidDate($endDate)
            || $startDate > $endDate
            || $price === false || !is_finite((float) $price) || $price < 0 || $price > 9999999999999.99
        ) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Vui lòng kiểm tra lại tên khóa học, danh mục, ngày bắt đầu/kết thúc, mô tả và học phí.');
        }

        $teacherId = $user['VaiTro'] === 'teacher'
            ? ($this->courseModel->getTeacherIdForUser((int) $user['MaNguoiDung']) ?? 0)
            : max(0, (int) ($_POST['NguoiTao'] ?? 0));

        if ($teacherId < 1) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Vui lòng chọn giáo viên phụ trách khóa học.');
        }
        if ($user['VaiTro'] !== 'teacher') {
            $validTeacherIds = array_map('intval', array_column($this->courseModel->getTeacherOptions(), 'MaGiaoVien'));
            if (!in_array($teacherId, $validTeacherIds, true)) {
                $this->redirect($this->dashboardUrl('manage'), 'error', 'Giáo viên phụ trách không hợp lệ.');
            }
        }

        $existing = $courseId > 0 ? $this->courseModel->getCourseById($courseId) : null;
        if ($courseId > 0 && $existing === null) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Không tìm thấy khóa học cần cập nhật.');
        }
        if ($existing !== null && $existing['TrangThai'] === 'ended' && $user['VaiTro'] === 'teacher') {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Không thể chỉnh sửa khóa học đã kết thúc.');
        }
        if ($this->courseModel->courseNameExists($name, $courseId > 0 ? $courseId : null)) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Tên khóa học đã tồn tại trên hệ thống.');
        }

        $coverPath = $existing['AnhBia'] ?? null;
        if (isset($_FILES['AnhBia']) && $_FILES['AnhBia']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = (new FileUploader('courses', ['jpg', 'jpeg', 'png', 'webp'], 20))->upload($_FILES['AnhBia']);
            if (!$upload['success']) {
                $this->redirect($this->dashboardUrl('manage'), 'error', $upload['error']);
            }
            $coverPath = $upload['path'];
        }

        $today          = date('Y-m-d');
        $courseStatus   = $today < $startDate ? 'upcoming' : ($today > $endDate ? 'ended' : 'active');
        $approvalStatus = $user['VaiTro'] === 'teacher'
            ? 'pending'
            : ($existing['TrangThaiDuyet'] ?? 'approved');

        try {
            $this->courseModel->saveCourse(
                $courseId > 0 ? $courseId : null,
                [
                    'TenKhoaHoc'     => $name,
                    'DanhMuc'        => $category,
                    'MoTa'           => $description,
                    'Gia'            => $price,
                    'AnhBia'         => $coverPath,
                    'NgayBatDau'     => $startDate,
                    'NgayKetThuc'    => $endDate,
                    'NguoiTao'       => $teacherId,
                    'TrangThai'      => $courseStatus,
                    'TrangThaiDuyet' => $approvalStatus,
                ],
                $user['VaiTro'] === 'teacher'
            );
        } catch (DomainException $exception) {
            $this->redirect($this->dashboardUrl('manage'), 'error', $exception->getMessage());
        }

        $this->redirect(
            $this->dashboardUrl('manage'),
            'success',
            $user['VaiTro'] === 'teacher'
                ? 'Đã lưu khóa học và gửi yêu cầu chờ Bộ phận Đào tạo phê duyệt.'
                : 'Đã lưu thông tin khóa học thành công.'
        );
    }

    public function deleteCourse(): void
    {
        $this->requireRoles(['teacher', 'training', 'manager']);
        $this->verifyCsrf();

        $courseId = max(0, (int) ($_POST['MaKhoaHoc'] ?? 0));
        $user     = Session::getUser();
        if ($courseId < 1 || !$this->canManageCourse($courseId, $user)) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Bạn không có quyền xóa khóa học này.');
        }

        $check = $this->courseModel->canDeleteCourse($courseId);
        if (!$check['can_delete']) {
            $this->redirect($this->dashboardUrl('manage'), 'error', $check['reason']);
        }

        if (!$this->courseModel->deleteCourse($courseId)) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Không thể xóa khóa học này.');
        }

        $this->redirect($this->dashboardUrl('manage'), 'success', 'Đã xóa khóa học thành công.');
    }

    public function approveCourse(): void
    {
        $this->requireRoles(['training']);
        $this->verifyCsrf();

        $courseId = max(0, (int) ($_POST['MaKhoaHoc'] ?? 0));
        $status   = $_POST['TrangThaiDuyet'] ?? '';
        $comment  = trim($_POST['NhanXet'] ?? '');

        if ($courseId < 1 || !in_array($status, ['approved', 'rejected'], true)) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Yêu cầu phê duyệt khóa học không hợp lệ.');
        }

        $updated = $this->courseModel->setApprovalStatus(
            $courseId,
            $status,
            (int) Session::getUser()['MaNguoiDung'],
            $comment
        );
        if (!$updated) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Khóa học không còn ở trạng thái chờ duyệt.');
        }

        $this->redirect(
            $this->dashboardUrl('manage'),
            'success',
            $status === 'approved' ? 'Đã phê duyệt công bố khóa học.' : 'Đã từ chối khóa học và gửi phản hồi cho giáo viên.'
        );
    }

    public function saveChapter(): void
    {
        $this->requireRoles(['teacher', 'training', 'manager']);
        $this->verifyCsrf();

        $courseId    = max(0, (int) ($_POST['MaKhoaHoc'] ?? 0));
        $chapterId   = max(0, (int) ($_POST['MaChuong'] ?? 0));
        $user        = Session::getUser();
        $name        = trim($_POST['TenChuong'] ?? '');
        $description = trim($_POST['MoTaChuong'] ?? '');
        $order       = filter_var($_POST['ThuTu'] ?? 1, FILTER_VALIDATE_INT);

        if (
            !$this->canManageCourse($courseId, $user)
            || preg_match('/^.{1,255}$/us', $name) !== 1
            || $order === false || $order < 1
        ) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Thông tin chương học không hợp lệ.');
        }

        $this->courseModel->saveChapter($chapterId > 0 ? $chapterId : null, $courseId, $name, $description, $order);
        $this->redirect(
            $this->dashboardUrl('manage') . '&detail=' . $courseId,
            'success',
            $chapterId > 0 ? 'Đã cập nhật chương học.' : 'Đã thêm chương học mới.'
        );
    }

    public function deleteChapter(): void
    {
        $this->requireRoles(['teacher', 'training', 'manager']);
        $this->verifyCsrf();

        $courseId  = max(0, (int) ($_POST['MaKhoaHoc'] ?? 0));
        $chapterId = max(0, (int) ($_POST['MaChuong'] ?? 0));
        if (!$this->canManageCourse($courseId, Session::getUser()) || $chapterId < 1) {
            $this->redirect($this->dashboardUrl('manage'), 'error', 'Bạn không có quyền xóa chương học này.');
        }

        if (!$this->courseModel->deleteChapter($chapterId, $courseId)) {
            $this->redirect(
                $this->dashboardUrl('manage') . '&detail=' . $courseId,
                'error',
                'Không thể xóa chương học đã có bài nộp của học viên.'
            );
        }

        $this->redirect(
            $this->dashboardUrl('manage') . '&detail=' . $courseId,
            'success',
            'Đã xóa chương học thành công.'
        );
    }

    public function enroll(): void
    {
        $this->requireRoles(['student']);
        $this->verifyCsrf();

        $user          = Session::getUser();
        $courseId      = max(0, (int) ($_POST['MaKhoaHoc'] ?? 0));
        $paymentMethod = $_POST['PhuongThucTT'] ?? '';
        if ($courseId < 1 || !in_array($paymentMethod, ['bank', 'card', 'momo'], true)) {
            $this->redirect($this->dashboardUrl('courses'), 'error', 'Thông tin đăng ký hoặc phương thức thanh toán không hợp lệ.');
        }

        try {
            $studentId = $this->courseModel->getStudentIdForUser((int) $user['MaNguoiDung']);
            if ($studentId === null) {
                $this->redirect($this->dashboardUrl('courses'), 'error', 'Tài khoản chưa có hồ sơ học viên hợp lệ.');
            }
            $this->courseModel->registerStudent($studentId, $courseId, $paymentMethod);
        } catch (DomainException $exception) {
            $this->redirect($this->dashboardUrl('courses'), 'error', $exception->getMessage());
        }

        $this->redirect(
            $this->dashboardUrl('mine'),
            'success',
            'Đăng ký và thanh toán khóa học thành công! Bạn có thể bắt đầu học ngay.'
        );
    }

    private function canManageCourse(int $courseId, array $user): bool
    {
        if ($user['VaiTro'] === 'teacher') {
            $teacherId = $this->courseModel->getTeacherIdForUser((int) $user['MaNguoiDung']);
            return $teacherId !== null && $this->courseModel->isCourseOwnedBy($courseId, $teacherId);
        }
        return in_array($user['VaiTro'], ['training', 'manager'], true)
            && $this->courseModel->getCourseById($courseId) !== null;
    }

    private function dashboardUrl(string $view, string $keyword = '', string $category = '', int $page = 1): string
    {
        $url = 'index.php?controller=dashboard&action=index&view=' . rawurlencode($view);
        if ($keyword !== '') {
            $url .= '&q=' . rawurlencode($keyword);
        }
        if ($category !== '') {
            $url .= '&category=' . rawurlencode($category);
        }
        if ($page > 1) {
            $url .= '&page=' . $page;
        }
        return $url;
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('Yêu cầu không hợp lệ (CSRF). Vui lòng tải lại trang và thử lại.');
        }
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}