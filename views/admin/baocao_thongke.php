<div class="card" style="margin-bottom: 24px;">
    <h2 style="font-size:22px; margin-bottom:6px;">📊 Báo cáo & Thống kê hệ thống</h2>
    <p style="color:#666; font-size:14px;">Tổng quan số liệu hoạt động và hiệu quả đào tạo tại KOS English Center</p>
</div>

<!-- THỐNG KÊ TỔNG QUAN (3 THẺ KPI) -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
    <div class="card" style="border-left: 5px solid var(--gold); text-align: center;">
        <span style="font-size: 13px; color: #666; text-transform: uppercase; font-weight: 600;">Tổng số khóa học đã duyệt</span>
        <div style="font-size: 32px; font-weight: 800; color: var(--ink); margin-top: 8px;">
            <?= number_format($overviewStats['total_courses']) ?>
        </div>
    </div>
    <div class="card" style="border-left: 5px solid var(--green); text-align: center;">
        <span style="font-size: 13px; color: #666; text-transform: uppercase; font-weight: 600;">Tổng số học viên</span>
        <div style="font-size: 32px; font-weight: 800; color: var(--green); margin-top: 8px;">
            <?= number_format($overviewStats['total_students']) ?>
        </div>
    </div>
    <div class="card" style="border-left: 5px solid #2b6cb0; text-align: center;">
        <span style="font-size: 13px; color: #666; text-transform: uppercase; font-weight: 600;">Giảng viên hệ thống</span>
        <div style="font-size: 32px; font-weight: 800; color: #2b6cb0; margin-top: 8px;">
            <?= number_format($overviewStats['total_teachers']) ?>
        </div>
    </div>
</div>

<!-- BẢNG THỐNG KÊ CHI TIẾT KHÓA HỌC -->
<div class="card">
    <h3 style="font-size: 18px; margin-bottom: 16px;">📚 Chi tiết hiệu quả từng khóa học</h3>
    
    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border); background: #f9f8f6;">
                <th style="padding: 12px;">Mã KH</th>
                <th style="padding: 12px;">Tên khóa học</th>
                <th style="padding: 12px;">Danh mục</th>
                <th style="padding: 12px;">Giảng viên</th>
                <th style="padding: 12px;">Học phí</th>
                <th style="padding: 12px; text-align: center;">Số học viên</th>
                <th style="padding: 12px; text-align: right;">Doanh thu ước tính</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($courseStats)): ?>
                <?php foreach ($courseStats as $item): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 12px; font-weight: 600;">#<?= $item['MaKhoaHoc'] ?></td>
                        <td style="padding: 12px; font-weight: 600; color: var(--ink);"><?= htmlspecialchars($item['TenKhoaHoc']) ?></td>
                        <td style="padding: 12px;"><span class="badge"><?= htmlspecialchars($item['DanhMuc']) ?></span></td>
                        <td style="padding: 12px; color: #555;"><?= htmlspecialchars($item['TenGiaoVien']) ?></td>
                        <td style="padding: 12px;"><?= number_format((float)$item['Gia'], 0, ',', '.') ?> đ</td>
                        <td style="padding: 12px; text-align: center; font-weight: 700; color: var(--green);"><?= (int)$item['SoHocVien'] ?></td>
                        <td style="padding: 12px; text-align: right; font-weight: 700; color: var(--gold);"><?= number_format((float)$item['DoanhThu'], 0, ',', '.') ?> đ</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="padding: 20px; text-align: center; color: #888;">Chưa có dữ liệu thống kê.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>