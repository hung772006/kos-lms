<?php
$escape  = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$keyword = $keyword ?? '';

$sumStudents = 0;
$sumRevenue  = 0.0;
foreach ($courseStats ?? [] as $row) {
    $sumStudents += (int) ($row['SoHocVien'] ?? 0);
    $sumRevenue  += (float) ($row['DoanhThu'] ?? 0);
}
?>

<style>
    .rep-top { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px 26px; margin-bottom:22px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; box-shadow:var(--shadow); }
    .rep-top h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .rep-top p { font-size:13px; color:#666; }
    .rep-actions { display:flex; gap:10px; flex-wrap:wrap; }
    .kpi-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:18px; margin-bottom:24px; }
    .kpi-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:20px 22px; box-shadow:var(--shadow); }
    .kpi-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#666; }
    .kpi-val { font-size:28px; font-weight:800; margin-top:8px; color:var(--ink); }
    .kpi-sub { font-size:12px; color:#888; margin-top:4px; }
    .rep-filter { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
    .rep-filter input { flex:1; min-width:220px; padding:9px 14px; border:1.5px solid var(--border); border-radius:9px; font-family:inherit; font-size:13px; background:var(--cream); outline:none; }
    .rep-filter input:focus { border-color:var(--gold); background:#fff; }
    .rep-table { width:100%; border-collapse:collapse; font-size:13px; }
    .rep-table th { text-align:left; padding:13px 14px; background:var(--cream); border-bottom:2px solid var(--border); font-size:11px; font-weight:700; text-transform:uppercase; color:#666; white-space:nowrap; }
    .rep-table td { padding:13px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .rep-table tfoot td { background:var(--cream); font-weight:800; border-top:2px solid var(--border); border-bottom:none; }
    .mini-prog { width:90px; height:7px; background:#eee; border-radius:999px; overflow:hidden; display:inline-block; vertical-align:middle; margin-right:6px; }
    .mini-prog-fill { height:100%; background:var(--gold); border-radius:999px; }

    @media (max-width:900px) {
        .kpi-grid { grid-template-columns:1fr 1fr; }
    }
    @media print {
        .sidebar, .topbar, .rep-actions, .rep-filter { display:none !important; }
        .main-content { margin-left:0 !important; }
        .content-area { padding:0 !important; }
        .card, .kpi-card, .rep-top { box-shadow:none !important; border:1px solid #ccc !important; }
    }
</style>

<div class="rep-top">
    <div>
        <h2>📊 Báo cáo &amp; Thống kê hệ thống</h2>
        <p>Tổng hợp số liệu đào tạo, tiến độ học tập và doanh thu thực tế tại KOS English Center.</p>
    </div>
    <div class="rep-actions">
        <a href="index.php?controller=approvalreport&amp;action=exportCsv&amp;q=<?= rawurlencode($keyword) ?>" class="btn btn-green">
            📥 Xuất báo cáo Excel (.CSV)
        </a>
        <button type="button" onclick="window.print()" class="btn btn-gold">
            🖨️ In / Xuất PDF
        </button>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card" style="border-left:5px solid var(--gold);">
        <div class="kpi-label">Khóa học đang hoạt động</div>
        <div class="kpi-val"><?= number_format((int) ($overviewStats['total_courses'] ?? 0)) ?></div>
        <div class="kpi-sub">Chờ phê duyệt: <strong><?= (int) ($overviewStats['pending_courses'] ?? 0) ?></strong> khóa</div>
    </div>

    <div class="kpi-card" style="border-left:5px solid var(--green);">
        <div class="kpi-label">Tổng số học viên</div>
        <div class="kpi-val" style="color:var(--green);"><?= number_format((int) ($overviewStats['total_students'] ?? 0)) ?></div>
        <div class="kpi-sub">Lượt đăng ký khóa học: <strong><?= number_format((int) ($overviewStats['total_enrollments'] ?? 0)) ?></strong></div>
    </div>

    <div class="kpi-card" style="border-left:5px solid #1d3557;">
        <div class="kpi-label">Đội ngũ giảng viên</div>
        <div class="kpi-val" style="color:#1d3557;"><?= number_format((int) ($overviewStats['total_teachers'] ?? 0)) ?></div>
        <div class="kpi-sub">Giảng viên cơ hữu &amp; thỉnh giảng</div>
    </div>

    <div class="kpi-card" style="border-left:5px solid #d97706;">
        <div class="kpi-label">Tổng doanh thu khóa học</div>
        <div class="kpi-val" style="color:#d97706;"><?= number_format((float) ($overviewStats['total_revenue'] ?? 0), 0, ',', '.') ?> đ</div>
        <div class="kpi-sub">Tính trên tổng lượt ghi danh hợp lệ</div>
    </div>

    <div class="kpi-card" style="border-left:5px solid #457b9d;">
        <div class="kpi-label">Tiến độ hoàn thành TB</div>
        <div class="kpi-val" style="color:#457b9d;"><?= number_format((float) ($overviewStats['avg_completion'] ?? 0), 1) ?>%</div>
        <div class="kpi-sub">Trung bình toàn bộ học viên</div>
    </div>

    <div class="kpi-card" style="border-left:5px solid #6d28d9;">
        <div class="kpi-label">Ngày kết xuất dữ liệu</div>
        <div class="kpi-val" style="font-size:22px;color:#6d28d9;"><?= date('d/m/Y') ?></div>
        <div class="kpi-sub">Cập nhật theo thời gian thực</div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <h3 style="font-size:18px;font-weight:800;color:var(--ink);">📚 Thống kê chi tiết hiệu quả từng khóa học</h3>
    </div>

    <form class="rep-filter" method="GET" action="index.php">
        <input type="hidden" name="controller" value="approvalreport">
        <input type="hidden" name="action" value="reports">
        <input type="text" name="q" value="<?= $escape($keyword) ?>" placeholder="Lọc theo tên khóa học, danh mục hoặc giảng viên...">
        <button type="submit" class="btn btn-dark">Lọc dữ liệu</button>
        <?php if ($keyword !== ''): ?>
            <a href="index.php?controller=approvalreport&amp;action=reports" class="btn btn-outline">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x:auto;">
        <table class="rep-table">
            <thead>
                <tr>
                    <th>Mã KH</th>
                    <th>Tên khóa học</th>
                    <th>Danh mục</th>
                    <th>Giảng viên</th>
                    <th style="text-align:right;">Học phí</th>
                    <th style="text-align:center;">Số học viên</th>
                    <th>Tiến độ TB</th>
                    <th style="text-align:right;">Doanh thu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($courseStats)): ?>
                    <?php foreach ($courseStats as $item): ?>
                        <?php $avgProg = max(0, min(100, (float) ($item['TienDoTB'] ?? 0))); ?>
                        <tr>
                            <td style="font-weight:700;color:#888;">#<?= (int) $item['MaKhoaHoc'] ?></td>
                            <td style="font-weight:700;color:var(--ink);"><?= $escape($item['TenKhoaHoc']) ?></td>
                            <td><span class="badge"><?= $escape($item['DanhMuc']) ?></span></td>
                            <td style="color:#555;"><?= $escape($item['TenGiaoVien']) ?></td>
                            <td style="text-align:right;"><?= number_format((float) $item['Gia'], 0, ',', '.') ?> đ</td>
                            <td style="text-align:center;font-weight:700;color:var(--green);"><?= (int) $item['SoHocVien'] ?></td>
                            <td>
                                <div class="mini-prog"><div class="mini-prog-fill" style="width:<?= $avgProg ?>%;"></div></div>
                                <span style="font-size:12px;font-weight:700;"><?= number_format($avgProg, 0) ?>%</span>
                            </td>
                            <td style="text-align:right;font-weight:800;color:var(--ink);"><?= number_format((float) $item['DoanhThu'], 0, ',', '.') ?> đ</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding:36px;text-align:center;color:#888;">Không có dữ liệu khóa học phù hợp với bộ lọc.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($courseStats)): ?>
                <tfoot>
                    <tr>
                        <td colspan="5">TỔNG CỘNG (<?= count($courseStats) ?> khóa học)</td>
                        <td style="text-align:center;color:var(--green);"><?= number_format($sumStudents) ?> lượt</td>
                        <td>—</td>
                        <td style="text-align:right;color:var(--green);font-size:15px;"><?= number_format($sumRevenue, 0, ',', '.') ?> đ</td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>