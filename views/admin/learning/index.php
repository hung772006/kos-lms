<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');

$resolveMaterialFile = static function (?string $path): string {
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

$keyword  = $keyword ?? '';
$courseId = (int) ($courseId ?? 1);
$typeLabels = [
    'video'      => '🎬 Video / Tài liệu',
    'assignment' => '📝 Bài tập tự luận',
    'quiz'       => '⚡ Trắc nghiệm Quiz',
];
$baseUrl = 'index.php?controller=adminlearning&action=index&course_id=' . $courseId . '&search=' . rawurlencode($keyword);
?>

<style>
    .admin-subnav { display:flex; gap:10px; margin-bottom:22px; border-bottom:1px solid var(--border); padding-bottom:14px; flex-wrap:wrap; }
    .admin-subnav a { padding:9px 18px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none; color:#555; background:#fff; border:1px solid var(--border); transition:0.15s; }
    .admin-subnav a.active, .admin-subnav a:hover { background:var(--ink); color:#fff; border-color:var(--ink); }
    .learning-top { display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
    .learning-top h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .learning-top p { font-size:13px; color:#666; }
    .learning-search { display:flex; gap:10px; align-items:center; background:#fff; padding:14px 18px; border-radius:14px; border:1px solid var(--border); margin-bottom:20px; flex-wrap:wrap; }
    .learning-search input { flex:1; min-width:220px; padding:10px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; font-size:14px; background:var(--cream); color:var(--ink); outline:none; }
    .learning-search input:focus { border-color:var(--gold); background:#fff; }
    .kos-table-card { background:#fff; border-radius:16px; border:1px solid var(--border); overflow:hidden; box-shadow:var(--shadow); }
    .kos-table-toolbar { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .kos-table-wrap { overflow-x:auto; }
    .kos-table { width:100%; border-collapse:collapse; font-size:13px; }
    .kos-table thead { background:var(--cream); border-bottom:1.5px solid var(--border); }
    .kos-table th { text-align:left; padding:13px 16px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#666; white-space:nowrap; }
    .kos-table td { padding:14px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .kos-table tbody tr:last-child td { border-bottom:none; }
    .kos-table tbody tr:hover { background:#fcfbf8; }
    .type-pill { display:inline-block; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; white-space:nowrap; }
    .type-video { background:#e8f0fe; color:#1d3557; }
    .type-assignment { background:#dff0d8; color:var(--green); }
    .type-quiz { background:#fef3c7; color:#92400e; }
    .file-chip { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:7px; background:#f0f4f8; color:#1d3557; font-weight:600; font-size:12px; text-decoration:none; }
    .file-chip:hover { background:#e1eaf2; }
    .row-actions { display:flex; justify-content:center; gap:8px; }
</style>

<div class="admin-subnav">
    <a class="active" href="index.php?controller=adminlearning&amp;action=index">📚 Quản lý hoạt động học tập</a>
    <a href="index.php?controller=admingrading&amp;action=index">✍️ Chấm điểm &amp; Nhận xét bài nộp</a>
</div>

<div class="learning-top">
    <div>
        <h2>Quản lý nội dung đào tạo</h2>
        <p>Thêm mới, chỉnh sửa bài giảng video, tài liệu PDF/ZIP, bài tập tự luận và đề trắc nghiệm Quiz.</p>
    </div>
    <a href="index.php?controller=adminlearning&amp;action=create&amp;course_id=<?= $courseId ?>" class="btn btn-gold">
        + Thêm hoạt động mới
    </a>
</div>

<form class="learning-search" method="GET" action="index.php">
    <input type="hidden" name="controller" value="adminlearning">
    <input type="hidden" name="action" value="index">
    <input type="hidden" name="course_id" value="<?= $courseId ?>">
    <input type="text" name="search" value="<?= $escape($keyword) ?>" placeholder="Tìm kiếm theo tên hoạt động, tên chương hoặc khóa học...">
    <button type="submit" class="btn btn-dark">Tìm kiếm</button>
    <?php if ($keyword !== ''): ?>
        <a href="index.php?controller=adminlearning&amp;action=index&amp;course_id=<?= $courseId ?>" class="btn btn-outline">Xóa lọc</a>
    <?php endif; ?>
</form>

<div class="kos-table-card">
    <div class="kos-table-toolbar">
        <div style="font-size:13px;font-weight:600;color:#555;">Danh sách học liệu &amp; hoạt động học tập</div>
        <a href="index.php?controller=dashboard&amp;action=index&amp;view=manage" class="btn btn-outline btn-sm">← Về danh sách khóa học</a>
    </div>

    <div class="kos-table-wrap">
        <table class="kos-table">
            <thead>
                <tr>
                    <th style="width:60px;">#ID</th>
                    <th>Tên hoạt động</th>
                    <th>Khóa học / Chương học</th>
                    <th style="width:150px;">Phân loại</th>
                    <th>Tài liệu đính kèm</th>
                    <th style="width:120px;">Hạn nộp</th>
                    <th style="text-align:center;width:150px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $item): ?>
                        <?php
                        $loai    = $item['Loai'] ?? 'video';
                        $pillCls = $loai === 'assignment' ? 'type-assignment' : ($loai === 'quiz' ? 'type-quiz' : 'type-video');
                        $fileUrl = $resolveMaterialFile($item['FileDinhKem'] ?? null);
                        $itemCid = (int) ($item['MaKhoaHoc'] ?? $courseId);
                        ?>
                        <tr>
                            <td style="font-weight:700;color:#888;">#<?= (int) $item['MaHoatDong'] ?></td>
                            <td>
                                <div style="font-weight:700;color:var(--ink);font-size:14px;"><?= $escape($item['TenHoatDong']) ?></div>
                                <?php if (($item['TrangThai'] ?? 'published') === 'draft'): ?>
                                    <span style="font-size:11px;color:#92400e;background:#fef3c7;padding:2px 6px;border-radius:4px;font-weight:600;">Bản nháp</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['TenKhoaHoc'])): ?>
                                    <div style="font-weight:600;font-size:12px;color:var(--ink);"><?= $escape($item['TenKhoaHoc']) ?></div>
                                <?php endif; ?>
                                <div style="font-size:12px;color:#666;"><?= $escape($item['TenChuong'] ?? 'Chưa phân chương') ?></div>
                            </td>
                            <td>
                                <span class="type-pill <?= $pillCls ?>">
                                    <?= $escape($typeLabels[$loai] ?? $loai) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($fileUrl !== ''): ?>
                                    <a href="<?= $escape($fileUrl) ?>" target="_blank" download class="file-chip">
                                        📎 Tải file <?= !empty($item['DungLuongFile']) ? '(' . $escape($item['DungLuongFile']) . ')' : '' ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999;font-size:12px;">Không có</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:12px;color:#444;">
                                    <?= !empty($item['HanNop']) ? '📅 ' . $escape($item['HanNop']) : '—' ?>
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="index.php?controller=adminlearning&amp;action=edit&amp;id=<?= (int) $item['MaHoatDong'] ?>&amp;course_id=<?= $itemCid ?>" class="btn btn-outline btn-sm">
                                        ✏️ Sửa
                                    </a>
                                    <a href="index.php?controller=adminlearning&amp;action=delete&amp;id=<?= (int) $item['MaHoatDong'] ?>&amp;course_id=<?= $itemCid ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa hoạt động học tập này không?');">
                                        🗑️ Xóa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:44px 20px;color:#888;">
                            Không tìm thấy hoạt động học tập nào phù hợp.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($pagination) && $pagination instanceof Pagination): ?>
    <?= $pagination->render($baseUrl) ?>
<?php endif; ?>