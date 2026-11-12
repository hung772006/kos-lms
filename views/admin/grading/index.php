<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');

$resolveSubmissionFile = static function (?string $path): string {
    if ($path === null || trim($path) === '') {
        return '';
    }
    $clean = ltrim(trim($path), '/');
    if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://') || str_starts_with($clean, 'public/')) {
        return $clean;
    }
    if (is_file(__DIR__ . '/../../../public/' . $clean)) {
        return 'public/' . $clean;
    }
    return $clean;
};

$keyword    = $keyword ?? '';
$status     = $status ?? 'all';
$totalItems = (int) ($totalItems ?? count($submissions ?? []));
$baseUrl    = 'index.php?controller=admingrading&action=index&q=' . rawurlencode($keyword) . '&status=' . rawurlencode($status);
?>

<style>
    .admin-subnav { display:flex; gap:10px; margin-bottom:22px; border-bottom:1px solid var(--border); padding-bottom:14px; flex-wrap:wrap; }
    .admin-subnav a { padding:9px 18px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none; color:#555; background:#fff; border:1px solid var(--border); transition:0.15s; }
    .admin-subnav a.active, .admin-subnav a:hover { background:var(--ink); color:#fff; border-color:var(--ink); }
    .grading-header { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
    .grading-header h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .grading-header p { font-size:13px; color:#666; }
    .grading-filter { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:#fff; padding:14px 18px; border-radius:14px; border:1px solid var(--border); margin-bottom:20px; }
    .grading-filter input, .grading-filter select { padding:9px 12px; border:1.5px solid var(--border); border-radius:9px; font-family:inherit; font-size:13px; background:#fff; color:var(--ink); }
    .grading-filter input { flex:1; min-width:220px; }
    .kos-table-wrap { background:#fff; border-radius:16px; border:1px solid var(--border); overflow-x:auto; box-shadow:var(--shadow); }
    .kos-table { width:100%; border-collapse:collapse; font-size:13px; }
    .kos-table thead { background:var(--cream); border-bottom:1.5px solid var(--border); }
    .kos-table th { text-align:left; padding:14px 16px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#666; white-space:nowrap; }
    .kos-table td { padding:14px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .kos-table tbody tr:last-child td { border-bottom:none; }
    .kos-table tbody tr:hover { background:#fcfbf8; }
    .pill { display:inline-block; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; white-space:nowrap; }
    .pill-late { background:#fde8e8; color:var(--red); }
    .pill-ontime { background:#dff0d8; color:var(--green); }
    .pill-pending { background:#fef3c7; color:#92400e; }
    .pill-graded { background:#e8f0fe; color:#1d3557; }
    .file-link { display:inline-flex; align-items:center; gap:5px; color:#1d3557; font-weight:600; text-decoration:none; padding:5px 10px; border-radius:7px; background:#f0f4f8; font-size:12px; }
    .file-link:hover { background:#e1eaf2; }
</style>

<div class="admin-subnav">
    <a href="index.php?controller=adminlearning&amp;action=index">📚 Quản lý hoạt động học tập</a>
    <a class="active" href="index.php?controller=admingrading&amp;action=index">✍️ Chấm điểm &amp; Nhận xét bài nộp</a>
</div>

<div class="grading-header">
    <div>
        <h2>Chấm điểm &amp; Nhận xét bài nộp</h2>
        <p>Quản lý bài làm của học viên, tải file bài nộp, kiểm tra hạn nộp và chấm điểm phản hồi.</p>
    </div>
    <span class="badge" style="font-size:12px;padding:6px 12px;">Tổng cộng: <?= $totalItems ?> bài nộp</span>
</div>

<form class="grading-filter" method="GET" action="index.php">
    <input type="hidden" name="controller" value="admingrading">
    <input type="hidden" name="action" value="index">
    <input type="text" name="q" value="<?= $escape($keyword) ?>" placeholder="Tìm theo tên học viên, tên bài tập, khóa học...">
    <select name="status" aria-label="Lọc theo trạng thái">
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
        <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>Chờ chấm điểm</option>
        <option value="graded" <?= $status === 'graded' ? 'selected' : '' ?>>Đã chấm điểm</option>
        <option value="late" <?= $status === 'late' ? 'selected' : '' ?>>Nộp muộn</option>
    </select>
    <button type="submit" class="btn btn-dark">Tìm &amp; Lọc</button>
    <?php if ($keyword !== '' || ($status !== 'all' && $status !== '')): ?>
        <a class="btn btn-outline" href="index.php?controller=admingrading&amp;action=index">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="kos-table-wrap">
    <table class="kos-table">
        <thead>
            <tr>
                <th style="width:65px;">#ID</th>
                <th>Học viên</th>
                <th>Khóa học / Hoạt động</th>
                <th>Bài làm đính kèm</th>
                <th>Thời gian nộp</th>
                <th>Tiến độ &amp; Trạng thái</th>
                <th>Điểm số</th>
                <th style="text-align:center;width:130px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($submissions)): ?>
                <?php foreach ($submissions as $sub): ?>
                    <?php
                    $fileUrl = $resolveSubmissionFile($sub['FileBaiNop'] ?? null);
                    $maxPts  = (isset($sub['DiemToiDa']) && (float) $sub['DiemToiDa'] > 0) ? (float) $sub['DiemToiDa'] : 10;
                    ?>
                    <tr>
                        <td style="font-weight:700;color:#888;">#<?= (int) $sub['MaBaiNop'] ?></td>
                        <td>
                            <div style="font-weight:700;color:var(--ink);"><?= $escape($sub['TenHocVien'] ?? '') ?></div>
                            <?php if (!empty($sub['EmailHocVien'])): ?>
                                <div style="font-size:11px;color:#888;margin-top:2px;"><?= $escape($sub['EmailHocVien']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600;color:var(--ink);"><?= $escape($sub['TenHoatDong'] ?? '') ?></div>
                            <?php if (!empty($sub['TenKhoaHoc'])): ?>
                                <div style="font-size:11px;color:#888;margin-top:2px;"><?= $escape($sub['TenKhoaHoc']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($fileUrl !== ''): ?>
                                <a href="<?= $escape($fileUrl) ?>" target="_blank" download class="file-link">
                                    📎 Tải file bài làm
                                </a>
                            <?php else: ?>
                                <span style="color:#666;font-size:12px;">📝 Nộp văn bản / Quiz</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="color:#444;"><?= $escape($sub['ThoiGianNop'] ?? '') ?></div>
                            <?php if (!empty($sub['HanNop'])): ?>
                                <div style="font-size:11px;color:#888;margin-top:2px;">Hạn: <?= $escape($sub['HanNop']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php if (!empty($sub['isLate'])): ?>
                                    <span class="pill pill-late">⏰ Nộp muộn</span>
                                <?php else: ?>
                                    <span class="pill pill-ontime">✅ Đúng hạn</span>
                                <?php endif; ?>

                                <?php if (($sub['TrangThai'] ?? '') === 'graded' || $sub['SoDiem'] !== null): ?>
                                    <span class="pill pill-graded">Đã chấm</span>
                                <?php else: ?>
                                    <span class="pill pill-pending">Chờ chấm</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($sub['SoDiem'] !== null && $sub['SoDiem'] !== ''): ?>
                                <strong style="color:var(--green);font-size:14px;"><?= $escape($sub['SoDiem']) ?>/<?= $escape($maxPts) ?></strong>
                            <?php else: ?>
                                <span style="color:#999;font-size:12px;">Chưa chấm</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <a href="index.php?controller=admingrading&amp;action=grade&amp;id=<?= (int) $sub['MaBaiNop'] ?>" class="btn btn-gold btn-sm">
                                ✍️ Chấm điểm
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:44px 20px;color:#888;">
                        Không tìm thấy bài nộp nào phù hợp với bộ lọc hiện tại.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($pagination) && $pagination instanceof Pagination): ?>
    <?= $pagination->render($baseUrl) ?>
<?php endif; ?>