<?php
$escape       = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$keyword      = $keyword ?? '';
$totalPending = (int) ($totalPending ?? count($pendingCourses ?? []));
$baseUrl      = 'index.php?controller=approvalreport&action=approvals&q=' . rawurlencode($keyword);
?>

<style>
    .apv-header { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px 26px; margin-bottom:22px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; box-shadow:var(--shadow); }
    .apv-header h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .apv-header p { font-size:13px; color:#666; }
    .apv-search { display:flex; gap:8px; flex-wrap:wrap; }
    .apv-search input { padding:9px 14px; border:1.5px solid var(--border); border-radius:9px; font-size:13px; width:250px; outline:none; font-family:inherit; background:var(--cream); }
    .apv-search input:focus { border-color:var(--gold); background:#fff; }
    .apv-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:20px; margin-bottom:24px; }
    .apv-card { background:#fff; border:1px solid var(--border); border-top:4px solid var(--gold); border-radius:16px; padding:22px 24px; display:flex; flex-direction:column; justify-content:space-between; box-shadow:var(--shadow); }
    .apv-note-input { width:100%; padding:9px 12px; border:1.5px solid var(--border); border-radius:9px; font-family:inherit; font-size:13px; background:var(--cream); margin-bottom:12px; outline:none; }
    .apv-note-input:focus { border-color:var(--gold); background:#fff; }
    .apv-history-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px 24px; margin-top:26px; box-shadow:var(--shadow); }
    .apv-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
    .apv-table th { text-align:left; padding:11px 14px; background:var(--cream); border-bottom:1.5px solid var(--border); font-size:11px; text-transform:uppercase; color:#666; }
    .apv-table td { padding:12px 14px; border-bottom:1px solid var(--border); }
    @media (max-width:900px) {
        .apv-grid { grid-template-columns:1fr; }
    }
</style>

<div class="apv-header">
    <div>
        <h2>📝 Phê duyệt khóa học</h2>
        <p>Xét duyệt nội dung các khóa học mới hoặc khóa học vừa cập nhật từ giảng viên (<strong style="color:var(--gold);"><?= $totalPending ?></strong> khóa đang chờ duyệt).</p>
    </div>

    <form class="apv-search" method="GET" action="index.php">
        <input type="hidden" name="controller" value="approvalreport">
        <input type="hidden" name="action" value="approvals">
        <input type="text" name="q" value="<?= $escape($keyword) ?>" placeholder="Tìm tên khóa học, danh mục, giáo viên...">
        <button type="submit" class="btn btn-dark">Tìm kiếm</button>
        <?php if ($keyword !== ''): ?>
            <a href="index.php?controller=approvalreport&amp;action=approvals" class="btn btn-outline">Xóa lọc</a>
        <?php endif; ?>
    </form>
</div>

<?php if (!empty($pendingCourses)): ?>
    <div class="apv-grid">
        <?php foreach ($pendingCourses as $c): ?>
            <?php $cid = (int) $c['MaKhoaHoc']; ?>
            <div class="apv-card">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:8px;flex-wrap:wrap;">
                        <span class="badge" style="background:#fef3c7;color:#92400e;">⏳ Chờ phê duyệt</span>
                        <span style="font-size:12px;color:#888;">
                            📅 Ngày gửi: <?= !empty($c['NgayTao']) ? $escape(date('d/m/Y', strtotime((string) $c['NgayTao']))) : '—' ?>
                        </span>
                    </div>

                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap;">
                        <span style="background:var(--cream);border:1px solid var(--border);padding:3px 9px;border-radius:6px;font-size:11px;font-weight:700;">
                            <?= $escape($c['DanhMuc'] ?? 'Chung') ?>
                        </span>
                        <span style="font-size:12px;color:#666;">📑 <?= (int) ($c['SoChuong'] ?? 0) ?> chương học</span>
                    </div>

                    <h3 style="font-size:18px;font-weight:800;margin-bottom:6px;color:var(--ink);"><?= $escape($c['TenKhoaHoc']) ?></h3>
                    <p style="font-size:13px;color:#555;margin-bottom:12px;">
                        👤 Giảng viên: <strong style="color:var(--ink);"><?= $escape($c['TenGiaoVien']) ?></strong>
                    </p>

                    <div style="font-size:13px;color:#555;margin-bottom:16px;line-height:1.6;background:var(--cream);padding:12px 14px;border-radius:10px;border:1px solid var(--border);">
                        <?= nl2br($escape($c['MoTa'] ?? 'Chưa có mô tả chi tiết cho khóa học này.')) ?>
                    </div>
                </div>

                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-top:12px;border-top:1px solid var(--border);">
                        <a href="index.php?controller=dashboard&amp;action=index&amp;view=manage&amp;detail=<?= $cid ?>" class="btn btn-outline btn-sm">
                            👁 Xem đề cương
                        </a>
                        <div style="text-align:right;">
                            <span style="font-size:11px;color:#888;display:block;">Học phí đề xuất</span>
                            <strong style="color:var(--green);font-size:17px;"><?= number_format((float) ($c['Gia'] ?? 0), 0, ',', '.') ?> đ</strong>
                        </div>
                    </div>

                    <form method="POST" action="index.php?controller=approvalreport&amp;action=handleApproval" onsubmit="return validateApprovalForm(this, event);">
                        <input type="hidden" name="course_id" value="<?= $cid ?>">
                        <input
                            type="text"
                            name="ly_do"
                            class="apv-note-input"
                            placeholder="Nhập ghi chú duyệt hoặc lý do cần chỉnh sửa (bắt buộc nếu từ chối)..."
                        >
                        <div style="display:flex;gap:10px;">
                            <button type="submit" name="status" value="rejected" class="btn btn-danger" style="flex:1;">
                                ✕ Từ chối
                            </button>
                            <button type="submit" name="status" value="approved" class="btn btn-green" style="flex:1;">
                                ✓ Phê duyệt ngay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (isset($pagination) && $pagination instanceof Pagination): ?>
        <?= $pagination->render($baseUrl) ?>
    <?php endif; ?>
<?php else: ?>
    <div class="card" style="text-align:center;padding:48px 24px;">
        <div style="font-size:44px;margin-bottom:10px;">🎉</div>
        <h3 style="font-size:18px;font-weight:800;color:var(--ink);margin-bottom:6px;">Không có khóa học nào đang chờ phê duyệt!</h3>
        <p style="color:#666;font-size:14px;">Tất cả khóa học giảng viên gửi lên đều đã được Bộ phận đào tạo xử lý.</p>
    </div>
<?php endif; ?>

<?php if (!empty($recentLogs)): ?>
    <div class="apv-history-card">
        <h3 style="font-size:16px;font-weight:800;color:var(--ink);">🕒 Lịch sử xét duyệt khóa học gần đây</h3>
        <div style="overflow-x:auto;">
            <table class="apv-table">
                <thead>
                    <tr>
                        <th>Khóa học</th>
                        <th>Người duyệt</th>
                        <th>Kết quả</th>
                        <th>Ghi chú / Lý do</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                        <?php $isApproved = ($log['TrangThai'] ?? '') === 'approved'; ?>
                        <tr>
                            <td style="font-weight:700;color:var(--ink);"><?= $escape($log['TenKhoaHoc'] ?? '') ?></td>
                            <td><?= $escape($log['NguoiDuyetTen'] ?? 'Bộ phận đào tạo') ?></td>
                            <td>
                                <span class="badge" style="<?= $isApproved ? 'background:#dff0d8;color:var(--green);' : 'background:#fde8e8;color:var(--red);' ?>">
                                    <?= $isApproved ? '✓ Đã duyệt' : '✕ Từ chối' ?>
                                </span>
                            </td>
                            <td style="color:#555;"><?= $escape($log['GhiChu'] ?? '—') ?></td>
                            <td style="color:#888;font-size:12px;"><?= !empty($log['NgayDuyet']) ? $escape(date('H:i d/m/Y', strtotime((string) $log['NgayDuyet']))) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
function validateApprovalForm(form, event) {
    var submitter = event.submitter;
    var noteInput = form.querySelector('input[name="ly_do"]');
    if (submitter && submitter.value === 'rejected') {
        if (!noteInput.value.trim()) {
            alert('Vui lòng nhập lý do từ chối vào ô ghi chú để giảng viên biết nội dung cần chỉnh sửa!');
            noteInput.focus();
            return false;
        }
        return confirm('Xác nhận từ chối khóa học này và gửi thông báo chỉnh sửa cho giảng viên?');
    }
    return confirm('Xác nhận phê duyệt và công bố khóa học này lên hệ thống?');
}
</script>