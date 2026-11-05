<?php
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$statusLabels = [
    'active' => 'Đang diễn ra',
    'upcoming' => 'Sắp khai giảng',
    'ended' => 'Đã kết thúc',
];
$approvalLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
];
$paginationUrl = 'index.php?controller=dashboard&action=index&view=' . rawurlencode($view)
    . '&q=' . rawurlencode($keyword) . '&category=' . rawurlencode($category);
?>

<?php if ($view === 'mine'): ?>
    <div class="section-header">
        <h2>Khóa học của tôi</h2>
        <a class="btn btn-gold" href="index.php?controller=dashboard&amp;action=index&amp;view=courses">Khám phá thêm</a>
    </div>
    <div class="learning-tracker">
        <div class="tracker-header">
            <div>
                <div style="font-size:18px;font-weight:700">Theo dõi tiến độ học tập</div>
                <div style="font-size:13px;color:#888;margin-top:4px">Tiến độ tổng hợp từ các khóa học đã đăng ký</div>
            </div>
        </div>
        <div class="tracker-meta">
            <div class="tracker-meta-item">
                <div class="val"><?= number_format((float) $averageProgress, 0) ?>%</div>
                <div class="lbl">Tiến độ trung bình</div>
            </div>
            <div class="tracker-meta-item">
                <div class="val"><?= count($myCourses) ?></div>
                <div class="lbl">Khóa học đã đăng ký</div>
            </div>
            <div class="tracker-meta-item">
                <div class="val"><?= count(array_filter($myCourses, static fn (array $course): bool => $course['TrangThaiTT'] === 'paid')) ?></div>
                <div class="lbl">Đã xác nhận thanh toán</div>
            </div>
        </div>
    </div>
    <div class="enrolled-grid">
        <?php if (!$myCourses): ?>
            <div class="empty-state">
                <div style="font-size:42px;margin-bottom:12px">◈</div>
                <div style="font-size:18px;font-weight:700;margin-bottom:8px">Bạn chưa đăng ký khóa học nào</div>
                <div style="font-size:14px">Khám phá danh sách khóa học để bắt đầu hành trình học tập.</div>
            </div>
        <?php else: ?>
            <?php foreach ($myCourses as $course): ?>
                <?php $progress = max(0, min(100, (float) $course['PhanTramHoanThanh'])); ?>
                <article class="enrolled-item">
                    <div class="enrolled-item-top">
                        <div class="course-thumb" style="width:56px;height:56px;border-radius:12px;flex-shrink:0">
                            <?php if ($course['AnhBia']): ?>
                                <img src="<?= $escape($course['AnhBia']) ?>" alt="">
                            <?php else: ?>
                                <span class="course-thumb-placeholder">◈</span>
                            <?php endif; ?>
                        </div>
                        <div class="enrolled-info">
                            <h4><?= $escape($course['TenKhoaHoc']) ?></h4>
                            <div class="teacher"><?= $escape($course['TenGiaoVien']) ?> · <?= $escape($course['DanhMuc']) ?></div>
                        </div>
                        <span class="badge <?= $course['TrangThaiTT'] === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                            <?= $course['TrangThaiTT'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán' ?>
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
                        <span class="btn btn-outline btn-sm"><?= $escape($statusLabels[$course['TrangThai']] ?? $course['TrangThai']) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="section-header">
        <h2><?= $view === 'courses' ? 'Khám phá khóa học' : 'Danh sách khóa học' ?></h2>
        <?php if ($canManage): ?>
            <a class="btn btn-dark" href="index.php?controller=dashboard&amp;action=index&amp;view=manage&amp;course_form=new">+ Thêm khóa học</a>
        <?php endif; ?>
    </div>

    <form class="filter-row" method="GET" action="index.php">
        <input type="hidden" name="controller" value="dashboard">
        <input type="hidden" name="action" value="index">
        <input type="hidden" name="view" value="<?= $escape($view) ?>">
        <input type="hidden" name="q" value="<?= $escape($keyword) ?>">
        <div class="form-group" style="margin:0;min-width:190px">
            <select name="category" aria-label="Lọc theo danh mục">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $option): ?>
                    <option value="<?= $escape($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= $escape($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-outline" type="submit">Lọc danh mục</button>
        <?php if ($category !== '' || $keyword !== ''): ?>
            <a class="btn btn-outline" href="index.php?controller=dashboard&amp;action=index&amp;view=<?= rawurlencode($view) ?>">Xóa bộ lọc</a>
        <?php endif; ?>
    </form>

    <div class="course-grid">
        <?php if (!$courses): ?>
            <div class="empty-state">
                <div style="font-size:42px;margin-bottom:12px">◈</div>
                <div style="font-size:18px;font-weight:700;margin-bottom:8px">
                    <?= $view === 'courses' ? 'Không còn khóa học phù hợp để khám phá' : 'Chưa có khóa học nào' ?>
                </div>
                <div style="font-size:14px">Thử thay đổi từ khóa tìm kiếm hoặc danh mục.</div>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <?php
                $statusClass = $course['TrangThai'] === 'active'
                    ? 'badge-success'
                    : ($course['TrangThai'] === 'ended' ? 'badge-gray' : 'badge-info');
                ?>
                <article class="course-card">
                    <div class="course-thumb">
                        <?php if ($course['AnhBia']): ?>
                            <img src="<?= $escape($course['AnhBia']) ?>" alt="Ảnh bìa <?= $escape($course['TenKhoaHoc']) ?>">
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
                                <span class="badge badge-gray"><?= $escape($approvalLabels[$course['TrangThaiDuyet']]) ?></span>
                            <?php endif; ?>
                        </div>
                        <h4><?= $escape($course['TenKhoaHoc']) ?></h4>
                        <div class="teacher">Giảng viên: <?= $escape($course['TenGiaoVien']) ?></div>
                        <p style="font-size:12px;color:#888;margin-bottom:12px;line-height:1.5"><?= nl2br($escape($course['MoTa'])) ?></p>
                        <div class="course-meta">
                            <div class="price"><?= number_format((float) $course['Gia'], 0, ',', '.') ?> đ</div>
                            <div class="students"><?= (int) $course['SoHocVien'] ?> học viên</div>
                        </div>
                        <div class="course-admin-actions">
                            <a class="btn btn-dark btn-sm" href="index.php?controller=dashboard&amp;action=index&amp;view=<?= rawurlencode($view) ?>&amp;detail=<?= (int) $course['MaKhoaHoc'] ?>">
                                <?= $view === 'courses' ? 'Xem chi tiết' : 'Chương học / Chi tiết' ?>
                            </a>
                            <?php if ($canManage): ?>
                                <a class="btn btn-outline btn-sm" href="index.php?controller=dashboard&amp;action=index&amp;view=manage&amp;course_form=<?= (int) $course['MaKhoaHoc'] ?>">Sửa</a>
                                <?php if ($user['VaiTro'] === 'training' && $course['TrangThaiDuyet'] !== 'approved'): ?>
                                    <form method="POST" action="index.php?controller=dashboard&amp;action=approveCourse">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $course['MaKhoaHoc'] ?>">
                                        <input type="hidden" name="TrangThaiDuyet" value="approved">
                                        <button class="btn btn-gold btn-sm" type="submit">Duyệt</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($user['VaiTro'] === 'training' && $course['TrangThaiDuyet'] === 'pending'): ?>
                                    <form method="POST" action="index.php?controller=dashboard&amp;action=approveCourse">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $course['MaKhoaHoc'] ?>">
                                        <input type="hidden" name="TrangThaiDuyet" value="rejected">
                                        <button class="btn btn-outline btn-sm" type="submit">Từ chối</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="index.php?controller=dashboard&amp;action=deleteCourse" onsubmit="return confirm('Bạn có chắc muốn xóa khóa học này?');">
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
    $closeDetailUrl = 'index.php?controller=dashboard&action=index&view=' . rawurlencode($view);
    preg_match('/^./u', $detail['TenGiaoVien'], $teacherInitialMatch);
    $teacherInitial = $teacherInitialMatch[0] ?? '?';
    ?>
    <div class="modal-overlay open" role="dialog" aria-modal="true" aria-labelledby="course-detail-title">
        <div class="modal" style="max-width:780px">
            <div class="modal-header">
                <div class="modal-title" id="course-detail-title"><?= $escape($detail['TenKhoaHoc']) ?></div>
                <a class="modal-close" href="<?= $escape($closeDetailUrl) ?>" aria-label="Đóng">✕</a>
            </div>
            <div class="detail-hero">
                <div class="course-thumb" style="width:76px;height:76px;border-radius:14px;flex-shrink:0">
                    <?php if ($detail['AnhBia']): ?>
                        <img src="<?= $escape($detail['AnhBia']) ?>" alt="">
                    <?php else: ?>
                        <span class="course-thumb-placeholder">◈</span>
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
                <span class="detail-tab">Thông tin</span>
                <span class="detail-tab">Đề cương</span>
                <span class="detail-tab">Giảng viên</span>
            </div>
            <section class="detail-section">
                <h3 style="font-size:14px;margin-bottom:8px">Thông tin khóa học</h3>
                <p style="font-size:14px;line-height:1.7;color:#666"><?= nl2br($escape($detail['MoTa'])) ?></p>
                <p style="font-size:13px;color:#888;margin-top:10px">
                    Khai giảng: <?= $escape($detail['NgayBatDau']) ?> · Kết thúc: <?= $escape($detail['NgayKetThuc']) ?>
                </p>
            </section>
            <section class="detail-section">
                <div class="section-header" style="margin:0 0 8px">
                    <h3 style="font-size:14px">Đề cương chương học</h3>
                    <?php if ($canManage): ?>
                        <span style="font-size:12px;color:#888"><?= count($detail['ChuongHoc']) ?> chương</span>
                    <?php endif; ?>
                </div>
                <?php if (!$detail['ChuongHoc']): ?>
                    <p style="font-size:13px;color:#888">Chưa có chương học trong đề cương.</p>
                <?php else: ?>
                    <?php foreach ($detail['ChuongHoc'] as $chapter): ?>
                        <div class="chapter-item">
                            <div style="font-size:14px;font-weight:700"><?= (int) $chapter['ThuTu'] ?>. <?= $escape($chapter['TenChuong']) ?></div>
                            <?php if ($chapter['MoTaChuong']): ?>
                                <div style="font-size:12px;color:#888;line-height:1.6;margin-top:4px"><?= nl2br($escape($chapter['MoTaChuong'])) ?></div>
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
                        <div class="form-group" style="margin:0"><label>Tên chương *</label><input name="TenChuong" required maxlength="255"></div>
                        <div class="form-group" style="margin:0"><label>Mô tả chương</label><input name="MoTaChuong"></div>
                        <div class="form-group" style="margin:0"><label>Thứ tự</label><input type="number" min="1" name="ThuTu" value="<?= count($detail['ChuongHoc']) + 1 ?>" required></div>
                        <button class="btn btn-dark" type="submit">+ Thêm chương</button>
                    </form>
                <?php endif; ?>
            </section>
            <section class="detail-section" style="display:flex;gap:14px;align-items:flex-start">
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
                <?php if ($view === 'courses' && $user['VaiTro'] === 'student' && $detail['TrangThai'] !== 'ended'): ?>
                    <form method="POST" action="index.php?controller=dashboard&amp;action=enroll" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="MaKhoaHoc" value="<?= (int) $detail['MaKhoaHoc'] ?>">
                        <div class="payment-method">
                            <label><input type="radio" name="PhuongThucTT" value="bank" checked>Chuyển khoản</label>
                            <label><input type="radio" name="PhuongThucTT" value="card">Thẻ</label>
                            <label><input type="radio" name="PhuongThucTT" value="momo">MoMo</label>
                        </div>
                        <button class="btn btn-green" type="submit">Đăng ký ngay</button>
                    </form>
                <?php endif; ?>
                <a class="btn btn-outline" href="<?= $escape($closeDetailUrl) ?>">Đóng</a>
            </div>
        </div>
    </div>
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
                <a class="modal-close" href="index.php?controller=dashboard&amp;action=index&amp;view=manage" aria-label="Đóng">✕</a>
            </div>
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="MaKhoaHoc" value="<?= (int) ($formCourse['MaKhoaHoc'] ?? 0) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Tên khóa học *</label>
                    <input type="text" name="TenKhoaHoc" value="<?= $escape($formCourse['TenKhoaHoc'] ?? '') ?>" maxlength="150" placeholder="Tên khóa học" required>
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
                <textarea name="MoTa" placeholder="Mô tả nội dung, mục tiêu khóa học..." required><?= $escape($formCourse['MoTa'] ?? '') ?></textarea>
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
                <label>Ảnh bìa khóa học</label>
                <input type="file" name="AnhBia" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <?php if (!empty($formCourse['AnhBia'])): ?>
                    <p style="font-size:12px;color:#888;margin-top:8px">Ảnh hiện tại: <?= $escape(basename($formCourse['AnhBia'])) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($user['VaiTro'] === 'teacher'): ?>
                <p style="font-size:12px;color:#888">Khóa học mới hoặc nội dung chỉnh sửa sẽ được gửi chờ bộ phận đào tạo duyệt.</p>
            <?php endif; ?>
            <div class="modal-footer">
                <a class="btn btn-outline" href="index.php?controller=dashboard&amp;action=index&amp;view=manage">Hủy</a>
                <button class="btn btn-dark" type="submit"><?= $courseForm ? 'Cập nhật khóa học' : 'Lưu khóa học' ?></button>
            </div>
        </form>
    </div>
<?php endif; ?>
