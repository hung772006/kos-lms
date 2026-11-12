<?php
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

$resolveCoverUrl = static function (?string $path): string {
    if ($path === null || trim($path) === '') {
        return '';
    }
    $clean = ltrim(trim($path), '/');
    if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://') || str_starts_with($clean, 'public/')) {
        return $clean;
    }
    if (is_file(__DIR__ . '/../../public/' . $clean)) {
        return 'public/' . $clean;
    }
    return $clean;
};

$hasClientLearning = is_file(__DIR__ . '/../../controllers/ClientLearningController.php');
$hasAdminLearning  = is_file(__DIR__ . '/../../controllers/AdminLearningController.php');

$statusLabels = [
    'active'   => 'Đang diễn ra',
    'upcoming' => 'Sắp diễn ra',
    'ended'    => 'Đã kết thúc',
];
$approvalLabels = [
    'pending'  => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
];

$currentPage   = (int) ($page ?? 1);
$paginationUrl = 'index.php?controller=dashboard&action=index&view=' . rawurlencode($view)
    . '&q=' . rawurlencode($keyword)
    . '&category=' . rawurlencode($category);
$stateBaseUrl  = $paginationUrl . ($currentPage > 1 ? '&page=' . $currentPage : '');
?>

<style>
    .subnav-tabs { display:flex; gap:10px; margin-bottom:22px; border-bottom:1px solid var(--border); padding-bottom:14px; }
    .subnav-tab { padding:9px 18px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none; color:#555; background:#fff; border:1px solid var(--border); transition:0.15s; }
    .subnav-tab.active, .subnav-tab:hover { background:var(--ink); color:#fff; border-color:var(--ink); }
    .section-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
    .section-header h2 { font-size:22px; font-weight:800; }
    .filter-row { display:flex; align-items:center; gap:10px; margin-bottom:22px; flex-wrap:wrap; background:#fff; padding:14px 18px; border-radius:14px; border:1px solid var(--border); }
    .form-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; }
    .form-group { margin-bottom:14px; }
    .form-group label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#666; margin-bottom:6px; letter-spacing:0.4px; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:9px; font-family:inherit; font-size:13px; background:#fff; color:var(--ink); }
    .form-group textarea { min-height:90px; resize:vertical; }
    .btn-sm { padding:7px 12px; font-size:12px; border-radius:8px; }
    .btn-outline { background:#fff; color:var(--ink); border:1.5px solid var(--border); }
    .btn-outline:hover { border-color:var(--ink); }
    .btn-danger { background:#fde8e8; color:var(--red); border:1px solid #f8b4b4; }
    .btn-green { background:var(--green); color:#fff; }
    .badge-success { background:#dff0d8; color:var(--green); }
    .badge-info { background:#e8f0fe; color:#1a56db; }
    .badge-warning { background:#fef3c7; color:#92400e; }
    .badge-gray { background:#eee; color:#666; }
    .pending-badge { background:var(--gold); color:var(--ink); font-size:11px; font-weight:700; padding:4px 10px; border-radius:999px; }
    .course-card { background:#fff; border-radius:16px; border:1px solid var(--border); overflow:hidden; display:flex; flex-direction:column; transition:transform 0.15s, box-shadow 0.15s; }
    .course-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(13,13,18,0.06); }
    .course-thumb { position:relative; height:150px; background:linear-gradient(135deg, #1b1b26, #2d3142); display:flex; align-items:center; justify-content:center; overflow:hidden; color:var(--gold); }
    .course-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .course-thumb-placeholder { font-size:28px; font-weight:800; color:var(--gold); display:flex; align-items:center; justify-content:center; width:100%; height:100%; }
    .course-body { padding:20px; display:flex; flex-direction:column; flex:1; }
    .course-tags { display:flex; gap:8px; align-items:center; margin-bottom:10px; flex-wrap:wrap; }
    .tag { font-size:11px; font-weight:700; text-transform:uppercase; padding:4px 9px; border-radius:6px; background:var(--cream); color:var(--ink); border:1px solid var(--border); }
    .course-body h4 { font-size:17px; font-weight:700; margin-bottom:6px; }
    .teacher { font-size:12px; color:#666; margin-bottom:10px; }
    .course-meta { display:flex; justify-content:space-between; align-items:center; padding-top:12px; margin-top:auto; border-top:1px solid var(--border); margin-bottom:14px; }
    .course-meta .price { font-size:17px; font-weight:800; color:var(--green); }
    .course-meta .students { font-size:12px; color:#888; }
    .course-admin-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .course-admin-actions form { display:inline-block; margin:0; }
    .learning-tracker { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px 24px; margin-bottom:22px; }
    .tracker-meta { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-top:16px; }
    .tracker-meta-item { background:var(--cream); border-radius:12px; padding:16px; text-align:center; }
    .tracker-meta-item .val { font-size:24px; font-weight:800; color:var(--gold); }
    .tracker-meta-item .lbl { font-size:12px; color:#666; margin-top:4px; }
    .enrolled-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:18px; }
    .enrolled-item { background:#fff; border:1px solid var(--border); border-radius:16px; padding:20px; display:flex; flex-direction:column; gap:14px; }
    .enrolled-item-top { display:flex; align-items:center; gap:14px; }
    .enrolled-info { flex:1; }
    .enrolled-info h4 { font-size:15px; font-weight:700; margin-bottom:4px; }
    .progress-bar { width:100%; height:8px; background:#eee; border-radius:999px; overflow:hidden; }
    .progress-fill { height:100%; background:var(--gold); border-radius:999px; }
    .enrolled-progress { display:flex; justify-content:space-between; align-items:center; }
    .enrolled-progress .pct { font-size:18px; font-weight:800; color:var(--gold); }
    .empty-state { grid-column:1 / -1; background:#fff; border:1px dashed var(--border); border-radius:16px; padding:44px 24px; text-align:center; color:#666; }
    .modal-overlay { position:fixed; inset:0; background:rgba(13,13,18,0.55); display:none; align-items:center; justify-content:center; padding:20px; z-index:999; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:18px; width:100%; max-width:640px; max-height:90vh; overflow-y:auto; padding:26px; box-shadow:0 20px 50px rgba(0,0,0,0.25); }
    .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--border); }
    .modal-title { font-size:20px; font-weight:800; }
    .modal-close { text-decoration:none; color:#666; font-size:18px; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:var(--cream); }
    .detail-hero { display:flex; gap:16px; align-items:center; background:var(--cream); padding:16px; border-radius:14px; margin-bottom:16px; }
    .detail-tabs { display:flex; gap:8px; margin-bottom:16px; }
    .detail-tab { padding:7px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; background:var(--cream); color:#555; border:none; }
    .detail-tab.active { background:var(--ink); color:#fff; }
    .detail-section { padding:14px 0; border-top:1px solid var(--border); }
    .chapter-item { background:var(--cream); border-radius:10px; padding:12px 14px; margin-bottom:10px; }
    .chapter-actions { display:flex; gap:8px; align-items:center; margin-top:10px; flex-wrap:wrap; }
    .user-avatar { width:44px; height:44px; border-radius:50%; background:var(--gold); color:var(--ink); font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .modal-footer { display:flex; justify-content:flex-end; align-items:center; gap:10px; margin-top:20px; padding-top:16px; border-top:1px solid var(--border); flex-wrap:wrap; }
    .payment-method { display:flex; gap:10px; align-items:center; flex-wrap:wrap; font-size:13px; }
    .payment-method label { display:inline-flex; align-items:center; gap:5px; padding:7px 12px; border:1px solid var(--border); border-radius:8px; cursor:pointer; background:var(--cream); }
</style>

<?php if ($user['VaiTro'] === 'student'): ?>
    <div class="subnav-tabs">
        <a class="subnav-tab <?= $view === 'courses' ? 'active' : '' ?>" href="index.php?controller=dashboard&amp;action=index&amp;view=courses">🔍 Khám phá khóa học</a>
        <a class="subnav-tab <?= $view === 'mine' ? 'active' : '' ?>" href="index.php?controller=dashboard&amp;action=index&amp;view=mine">🎓 Khóa học của tôi</a>
    </div>
<?php endif; ?>

<?php if ($view === 'mine'): ?>
    <div class="section-header">
        <h2>Khóa học của tôi</h2>
        <a class="btn btn-gold" href="index.php?controller=dashboard&amp;action=index&amp;view=courses">Khám phá thêm</a>
    </div>
    <div class="learning-tracker">
        <div class="tracker-header">
            <div>
                <div style="font-size:18px;font-weight:700">Theo dõi tiến độ học tập</div>
                <div style="font-size:13px;color:#888;margin-top:4px">Cập nhật theo thời gian thực từ các khóa học đã đăng ký</div>
            </div>
        </div>
        <div class="tracker-meta">
            <div class="tracker-meta-item">
                <div class="val"><?= number_format((float) $averageProgress, 0) ?>%</div>
                <div class="lbl">Tiến độ trung bình</div>
            </div>
            <div class="tracker-meta-item">
                <div class="val"><?= count($myCourses) ?></div>
                <div class="lbl">Khóa học đang theo học</div>
            </div>
            <div class="tracker-meta-item">
                <div class="val"><?= (int) ($user['TongGioHoc'] ?? 0) ?>h</div>
                <div class="lbl">Tổng giờ học tích lũy</div>
            </div>
        </div>
    </div>
    <div class="enrolled-grid">
        <?php if (!$myCourses): ?>
            <div class="empty-state">
                <div style="font-size:42px;margin-bottom:12px">◈</div>
                <div style="font-size:18px;font-weight:700;margin-bottom:8px">Bạn chưa đăng ký khóa học nào</div>
                <div style="font-size:14px">Khám phá danh sách khóa học để bắt đầu hành trình học tập tại KOS English Center.</div>
            </div>
        <?php else: ?>
            <?php foreach ($myCourses as $course): ?>
                <?php
                $progress = max(0, min(100, (float) $course['PhanTramHoanThanh']));
                $coverUrl = $resolveCoverUrl($course['AnhBia'] ?? null);
                $learnUrl = $hasClientLearning
                    ? 'index.php?controller=clientlearning&action=show&course_id=' . (int) $course['MaKhoaHoc']
                    : 'index.php?controller=dashboard&action=index&view=mine&detail=' . (int) $course['MaKhoaHoc'];
                ?>
                <article class="enrolled-item">
                    <div class="enrolled-item-top">
                        <div class="course-thumb" style="width:56px;height:56px;border-radius:12px;flex-shrink:0">
                            <?php if ($coverUrl !== ''): ?>
                                <img src="<?= $escape($coverUrl) ?>" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span class="course-thumb-placeholder" style="display:none;font-size:20px">◈</span>
                            <?php else: ?>
                                <span class="course-thumb-placeholder" style="font-size:20px">◈</span>
                            <?php endif; ?>
                        </div>
                        <div class="enrolled-info">
                            <h4><?= $escape($course['TenKhoaHoc']) ?></h4>
                            <div class="teacher"><?= $escape($course['TenGiaoVien']) ?> · <?= $escape($course['DanhMuc']) ?></div>
                        </div>
                        <span class="badge badge-success">
                            <?= $escape($statusLabels[$course['TrangThai']] ?? 'Đang diễn ra') ?>
                        </span>
                    </div>
                    <div>
                        <div class="progress-bar"><div class="progress-fill" style="width:<?= $progress ?>%"></div></div>
                    </div>
                    <div class="enrolled-progress">
                        <div style="display:flex;align-items:baseline;gap:4px">
                            <div class="pct"><?= number_format($progress, 0) ?>%</div>
                            <div style="font-size:11px;color:#999">hoàn thành</div>
                        </div>
                        <a class="btn btn-dark btn-sm" href="<?= $escape($learnUrl) ?>">Chi tiết / Vào học</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="section-header">
        <h2><?= $view === 'courses' ? 'Khám phá khóa học' : 'Quản lý khóa học' ?></h2>
        <?php if ($canManage): ?>
            <a class="btn btn-dark" href="index.php?controller=dashboard&amp;action=index&amp;view=manage&amp;course_form=new">+ Thêm khóa học</a>
        <?php endif; ?>
    </div>

    <form class="filter-row" method="GET" action="index.php">
        <input type="hidden" name="controller" value="dashboard">
        <input type="hidden" name="action" value="index">
        <input type="hidden" name="view" value="<?= $escape($view) ?>">
        <div class="form-group" style="margin:0;flex:1;min-width:200px">
            <input type="text" name="q" value="<?= $escape($keyword) ?>" placeholder="Nhập tên khóa học, danh mục hoặc giảng viên...">
        </div>
        <div class="form-group" style="margin:0;min-width:180px">
            <select name="category" aria-label="Lọc theo danh mục">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $option): ?>
                    <option value="<?= $escape($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= $escape($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-dark" type="submit">Tìm &amp; Lọc</button>
        <?php if ($category !== '' || $keyword !== ''): ?>
            <a class="btn btn-outline" href="index.php?controller=dashboard&amp;action=index&amp;view=<?= rawurlencode($view) ?>">Xóa bộ lọc</a>
        <?php endif; ?>
    </form>

    <div class="course-grid">
        <?php if (!$courses): ?>
            <div class="empty-state">
                <div style="font-size:42px;margin-bottom:12px">◈</div>
                <div style="font-size:18px;font-weight:700;margin-bottom:8px">
                    <?= $view === 'courses' ? 'Không tìm thấy khóa học phù hợp để khám phá' : 'Chưa có khóa học nào phù hợp' ?>
                </div>
                <div style="font-size:14px">Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc danh mục.</div>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <?php
                $statusClass = $course['TrangThai'] === 'active'
                    ? 'badge-success'
                    : ($course['TrangThai'] === 'ended' ? 'badge-gray' : 'badge-info');
                $coverUrl = $resolveCoverUrl($course['AnhBia'] ?? null);
                ?>
                <article class="course-card">
                    <div class="course-thumb">
                        <?php if ($coverUrl !== ''): ?>
                            <img src="<?= $escape($coverUrl) ?>" alt="Ảnh bìa <?= $escape($course['TenKhoaHoc']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <span class="course-thumb-placeholder" style="display:none">◈</span>
                        <?php else: ?>
                            <span class="course-thumb-placeholder">◈</span>
                        <?php endif; ?>
                        <?php if ($course['TrangThaiDuyet'] === 'pending'): ?>
                            <span class="pending-badge" style="position:absolute;top:10px;right:10px"><?= $escape($approvalLabels[$course['TrangThaiDuyet']]) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="course-body">
                        <div class="course-tags">
                            <span class="tag"><?= $escape($course['DanhMuc']) ?></span>
                            <span class="badge <?= $statusClass ?>"><?= $escape($statusLabels[$course['TrangThai']] ?? $course['TrangThai']) ?></span>
                            <?php if ($course['TrangThaiDuyet'] === 'rejected'): ?>
                                <span class="badge badge-danger" style="background:#fde8e8;color:var(--red)"><?= $escape($approvalLabels[$course['TrangThaiDuyet']]) ?></span>
                            <?php endif; ?>
                        </div>
                        <h4><?= $escape($course['TenKhoaHoc']) ?></h4>
                        <div class="teacher">Giảng viên: <?= $escape($course['TenGiaoVien']) ?></div>
                        <p style="font-size:12px;color:#666;margin-bottom:12px;line-height:1.5"><?= nl2br($escape($course['MoTa'])) ?></p>
                        <div class="course-meta">
                            <div class="price"><?= number_format((float) $course['Gia'], 0, ',', '.') ?> đ</div>
                            <div class="students"><?= (int) $course['SoHocVien'] ?> học viên</div>
                        </div>
                        <div class="course-admin-actions">
                            <a class="btn btn-dark btn-sm" href="<?= $escape($stateBaseUrl . '&detail=' . (int) $course['MaKhoaHoc']) ?>">
                                <?= $view === 'courses' ? 'Xem chi tiết' : 'Chương học / Chi tiết' ?>
                            </a>
                            <?php if ($canManage): ?>
                                <a class="btn btn-outline btn-sm" href="<?= $escape($stateBaseUrl . '&course_form=' . (int) $course['MaKhoaHoc']) ?>">Sửa</a>
                                <?php if ($hasAdminLearning && in_array($user['VaiTro'], ['teacher', 'manager'], true)): ?>
                                    <a class="btn btn-gold btn-sm" href="index.php?controller=adminlearning&amp;action=index&amp;course_id=<?= (int) $course['MaKhoaHoc'] ?>">Nội dung</a>
                                <?php endif; ?>
                                <?php if ($user['VaiTro'] === 'training' && $course['TrangThaiDuyet'] === 'pending'): ?>
                                    <form method="POST" action="index.php?controller=dashboard&amp;action=approveCourse">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $course['MaKhoaHoc'] ?>">
                                        <input type="hidden" name="TrangThaiDuyet" value="approved">
                                        <button class="btn btn-gold btn-sm" type="submit">Duyệt</button>
                                    </form>
                                    <form method="POST" action="index.php?controller=dashboard&amp;action=approveCourse">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $course['MaKhoaHoc'] ?>">
                                        <input type="hidden" name="TrangThaiDuyet" value="rejected">
                                        <button class="btn btn-outline btn-sm" type="submit">Từ chối</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="index.php?controller=dashboard&amp;action=deleteCourse" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khóa học này?');">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <input type="hidden" name="MaKhoaHoc" value="<?= (int) $course['MaKhoaHoc'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Xóa</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?= $pagination->render($paginationUrl) ?>
<?php endif; ?>

<?php if ($detail !== null): ?>
    <?php
    $closeDetailUrl = $stateBaseUrl;
    preg_match('/^./u', $detail['TenGiaoVien'], $teacherInitialMatch);
    $teacherInitial = $teacherInitialMatch[0] ?? '?';
    $detailCoverUrl = $resolveCoverUrl($detail['AnhBia'] ?? null);
    ?>
    <div class="modal-overlay open" role="dialog" aria-modal="true" aria-labelledby="course-detail-title">
        <div class="modal" style="max-width:780px">
            <div class="modal-header">
                <div class="modal-title" id="course-detail-title"><?= $escape($detail['TenKhoaHoc']) ?></div>
                <a class="modal-close" href="<?= $escape($closeDetailUrl) ?>" aria-label="Đóng">✕</a>
            </div>
            <div class="detail-hero">
                <div class="course-thumb" style="width:76px;height:76px;border-radius:14px;flex-shrink:0">
                    <?php if ($detailCoverUrl !== ''): ?>
                        <img src="<?= $escape($detailCoverUrl) ?>" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <span class="course-thumb-placeholder" style="display:none;font-size:22px">◈</span>
                    <?php else: ?>
                        <span class="course-thumb-placeholder" style="font-size:22px">◈</span>
                    <?php endif; ?>
                </div>
                <div style="flex:1">
                    <div style="font-size:17px;font-weight:700;margin-bottom:5px"><?= $escape($detail['TenKhoaHoc']) ?></div>
                    <div style="font-size:13px;color:#555;margin-bottom:7px"><?= $escape($detail['TenGiaoVien']) ?> · <?= $escape($detail['DanhMuc']) ?></div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <span class="badge <?= $detail['TrangThai'] === 'active' ? 'badge-success' : ($detail['TrangThai'] === 'ended' ? 'badge-gray' : 'badge-info') ?>">
                            <?= $escape($statusLabels[$detail['TrangThai']] ?? $detail['TrangThai']) ?>
                        </span>
                        <span style="font-size:13px;color:#555"><?= (int) $detail['SoHocVien'] ?> học viên</span>
                        <strong style="font-size:18px;color:var(--green)"><?= number_format((float) $detail['Gia'], 0, ',', '.') ?> đ</strong>
                    </div>
                </div>
            </div>
            <div class="detail-tabs">
                <button type="button" class="detail-tab active" onclick="switchKosTab('all', this)">Tất cả</button>
                <button type="button" class="detail-tab" onclick="switchKosTab('info', this)">Thông tin</button>
                <button type="button" class="detail-tab" onclick="switchKosTab('syllabus', this)">Đề cương</button>
                <button type="button" class="detail-tab" onclick="switchKosTab('teacher', this)">Giảng viên</button>
            </div>
            <section class="detail-section kos-tab-pane" data-tab="info">
                <h3 style="font-size:14px;margin-bottom:8px">Thông tin khóa học</h3>
                <p style="font-size:14px;line-height:1.7;color:#555"><?= nl2br($escape($detail['MoTa'])) ?></p>
                <p style="font-size:13px;color:#888;margin-top:10px">
                    Khai giảng: <?= $escape($detail['NgayBatDau']) ?> · Kết thúc: <?= $escape($detail['NgayKetThuc']) ?>
                </p>
            </section>
            <section class="detail-section kos-tab-pane" data-tab="syllabus">
                <div class="section-header" style="margin:0 0 10px">
                    <h3 style="font-size:14px">Đề cương chương học</h3>
                    <span style="font-size:12px;color:#888"><?= count($detail['ChuongHoc']) ?> chương</span>
                </div>
                <?php if (!$detail['ChuongHoc']): ?>
                    <p style="font-size:13px;color:#888">Chưa có chương học trong đề cương.</p>
                <?php else: ?>
                    <?php foreach ($detail['ChuongHoc'] as $chapter): ?>
                        <div class="chapter-item">
                            <div style="font-size:14px;font-weight:700"><?= (int) $chapter['ThuTu'] ?>. <?= $escape($chapter['TenChuong']) ?></div>
                            <?php if ($chapter['MoTaChuong']): ?>
                                <div style="font-size:12px;color:#666;line-height:1.6;margin-top:4px"><?= nl2br($escape($chapter['MoTaChuong'])) ?></div>
                            <?php endif; ?>
                            <?php if ($canManage): ?>
                                <div class="chapter-actions">
                                    <form method="POST" action="index.php?controller=dashboard&amp;action=saveChapter" class="form-row" style="flex:1">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $detail['MaKhoaHoc'] ?>">
                                        <input type="hidden" name="MaChuong" value="<?= (int) $chapter['MaChuong'] ?>">
                                        <div class="form-group" style="margin:0"><input name="TenChuong" value="<?= $escape($chapter['TenChuong']) ?>" aria-label="Tên chương" required></div>
                                        <div class="form-group" style="margin:0"><input name="MoTaChuong" value="<?= $escape($chapter['MoTaChuong']) ?>" aria-label="Mô tả chương"></div>
                                        <div class="form-group" style="margin:0"><input type="number" min="1" name="ThuTu" value="<?= (int) $chapter['ThuTu'] ?>" aria-label="Thứ tự" required></div>
                                        <button class="btn btn-outline btn-sm" type="submit">Cập nhật</button>
                                    </form>
                                    <form method="POST" action="index.php?controller=dashboard&amp;action=deleteChapter" onsubmit="return confirm('Xóa chương học này?');">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $detail['MaKhoaHoc'] ?>">
                                        <input type="hidden" name="MaChuong" value="<?= (int) $chapter['MaChuong'] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Xóa</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($canManage): ?>
                    <form method="POST" action="index.php?controller=dashboard&amp;action=saveChapter" class="form-row" style="margin-top:16px;align-items:end">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $detail['MaKhoaHoc'] ?>">
                        <div class="form-group" style="margin:0"><label>Tên chương *</label><input name="TenChuong" required maxlength="255" placeholder="VD: Unit 1 — Listening"></div>
                        <div class="form-group" style="margin:0"><label>Mô tả chương</label><input name="MoTaChuong" placeholder="Tóm tắt nội dung"></div>
                        <div class="form-group" style="margin:0"><label>Thứ tự</label><input type="number" min="1" name="ThuTu" value="<?= count($detail['ChuongHoc']) + 1 ?>" required></div>
                        <button class="btn btn-dark" type="submit">+ Thêm chương</button>
                    </form>
                <?php endif; ?>
            </section>
            <section class="detail-section kos-tab-pane" data-tab="teacher" style="display:flex;gap:14px;align-items:flex-start">
                <div class="user-avatar"><?= $escape($teacherInitial) ?></div>
                <div>
                    <h3 style="font-size:14px;margin-bottom:4px">Thông tin giảng viên</h3>
                    <div style="font-weight:700"><?= $escape($detail['TenGiaoVien']) ?></div>
                    <div style="font-size:13px;color:#666;margin-top:3px"><?= $escape($detail['ChuyenMonGV']) ?></div>
                    <?php if ($detail['ChungChi']): ?>
                        <div style="font-size:12px;color:#888;margin-top:4px">Chứng chỉ: <?= $escape($detail['ChungChi']) ?></div>
                    <?php endif; ?>
                    <?php if ($detail['GioiThieuGV']): ?>
                        <p style="font-size:13px;color:#666;line-height:1.6;margin-top:8px"><?= nl2br($escape($detail['GioiThieuGV'])) ?></p>
                    <?php endif; ?>
                    <div style="font-size:12px;color:#888;margin-top:6px">Kinh nghiệm: <?= (int) $detail['SoNamKinhNghiem'] ?> năm</div>
                </div>
            </section>
            <div class="modal-footer">
                <?php if ($user['VaiTro'] === 'student' && empty($detail['IsEnrolled']) && $detail['TrangThai'] !== 'ended'): ?>
                    <form method="POST" action="index.php?controller=dashboard&amp;action=enroll" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-right:auto">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $detail['MaKhoaHoc'] ?>">
                        <div class="payment-method">
                            <label><input type="radio" name="PhuongThucTT" value="bank" checked> Chuyển khoản</label>
                            <label><input type="radio" name="PhuongThucTT" value="card"> Thẻ tín dụng</label>
                            <label><input type="radio" name="PhuongThucTT" value="momo"> Ví MoMo</label>
                        </div>
                        <button class="btn btn-green" type="submit">Xác nhận đăng ký — <?= number_format((float) $detail['Gia'], 0, ',', '.') ?> đ</button>
                    </form>
                <?php elseif ($user['VaiTro'] === 'student' && !empty($detail['IsEnrolled']) && $hasClientLearning): ?>
                    <a class="btn btn-gold" href="index.php?controller=clientlearning&amp;action=show&amp;course_id=<?= (int) $detail['MaKhoaHoc'] ?>">Vào học ngay →</a>
                <?php endif; ?>
                <a class="btn btn-outline" href="<?= $escape($closeDetailUrl) ?>">Đóng</a>
            </div>
        </div>
    </div>
    <script>
        function switchKosTab(tabName, btn) {
            document.querySelectorAll('.detail-tab').forEach(function(el) { el.classList.remove('active'); });
            btn.classList.add('active');
            document.querySelectorAll('.kos-tab-pane').forEach(function(pane) {
                if (tabName === 'all' || pane.getAttribute('data-tab') === tabName) {
                    pane.style.display = pane.getAttribute('data-tab') === 'teacher' ? 'flex' : 'block';
                } else {
                    pane.style.display = 'none';
                }
            });
        }
    </script>
<?php endif; ?>

<?php if ($canManage && array_key_exists('course_form', $_GET)): ?>
    <?php
    $formCourse = $courseForm ?? [];
    $formAction = 'index.php?controller=dashboard&action=saveCourse';
    ?>
    <div class="modal-overlay open" role="dialog" aria-modal="true" aria-labelledby="course-form-title">
        <form class="modal" method="POST" action="<?= $escape($formAction) ?>" enctype="multipart/form-data">
            <div class="modal-header">
                <div class="modal-title" id="course-form-title"><?= $courseForm ? 'Chỉnh sửa khóa học' : 'Thêm khóa học mới' ?></div>
                <a class="modal-close" href="<?= $escape($stateBaseUrl) ?>" aria-label="Đóng">✕</a>
            </div>
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="MaKhoaHoc" value="<?= (int) ($formCourse['MaKhoaHoc'] ?? 0) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Tên khóa học *</label>
                    <input type="text" name="TenKhoaHoc" value="<?= $escape($formCourse['TenKhoaHoc'] ?? '') ?>" maxlength="150" placeholder="VD: IELTS Intensive 7.0+" required>
                </div>
                <div class="form-group">
                    <label>Danh mục</label>
                    <select name="DanhMuc" required>
                        <?php foreach ($categories as $option): ?>
                            <option value="<?= $escape($option) ?>" <?= ($formCourse['DanhMuc'] ?? 'IELTS') === $option ? 'selected' : '' ?>><?= $escape($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Mô tả khóa học *</label>
                <textarea name="MoTa" placeholder="Mô tả nội dung, mục tiêu đầu ra khóa học..." required><?= $escape($formCourse['MoTa'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Ngày bắt đầu *</label><input type="date" name="NgayBatDau" value="<?= $escape($formCourse['NgayBatDau'] ?? '') ?>" required></div>
                <div class="form-group"><label>Ngày kết thúc *</label><input type="date" name="NgayKetThuc" value="<?= $escape($formCourse['NgayKetThuc'] ?? '') ?>" required></div>
                <div class="form-group"><label>Học phí (VNĐ)</label><input type="number" min="0" step="1000" name="Gia" value="<?= $escape($formCourse['Gia'] ?? '0') ?>" required></div>
            </div>
            <?php if ($user['VaiTro'] !== 'teacher'): ?>
                <div class="form-group">
                    <label>Giáo viên phụ trách *</label>
                    <select name="NguoiTao" required>
                        <option value="">Chọn giáo viên</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= (int) $teacher['MaGiaoVien'] ?>" <?= (int) ($formCourse['NguoiTao'] ?? 0) === (int) $teacher['MaGiaoVien'] ? 'selected' : '' ?>>
                                <?= $escape($teacher['HoTen']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Ảnh bìa khóa học (Upload File)</label>
                <input type="file" name="AnhBia" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <?php if (!empty($formCourse['AnhBia'])): ?>
                    <p style="font-size:12px;color:#888;margin-top:8px">Ảnh hiện tại: <?= $escape(basename($formCourse['AnhBia'])) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($user['VaiTro'] === 'teacher'): ?>
                <p style="font-size:12px;color:#888">Khóa học mới hoặc nội dung chỉnh sửa sẽ được gửi chờ Bộ phận Quản lý đào tạo phê duyệt.</p>
            <?php endif; ?>
            <div class="modal-footer">
                <a class="btn btn-outline" href="<?= $escape($stateBaseUrl) ?>">Hủy</a>
                <button class="btn btn-dark" type="submit"><?= $courseForm ? 'Cập nhật khóa học' : 'Lưu khóa học' ?></button>
            </div>
        </form>
    </div>
<?php endif; ?>