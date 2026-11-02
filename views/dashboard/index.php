<div class="card">
    <h2 style="font-size:20px;margin-bottom:8px;">Xin chào, <?= htmlspecialchars($user['HoTen']) ?>!</h2>
    <p style="color:#666;font-size:14px;">Danh sách khóa học từ CSDL MySQL (Đã tích hợp sẵn Tìm kiếm & Phân trang bằng PHP OOP):</p>
</div>

<div class="course-grid">
    <?php foreach ($courses as $c): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <span class="badge"><?= htmlspecialchars($c['DanhMuc']) ?></span>
                <span style="font-size:12px;color:#888;">GV: <?= htmlspecialchars($c['TenGiaoVien']) ?></span>
            </div>
            <h3 style="font-size:17px;margin-bottom:8px;"><?= htmlspecialchars($c['TenKhoaHoc']) ?></h3>
            <p style="font-size:13px;color:#666;margin-bottom:14px;line-height:1.5;"><?= htmlspecialchars($c['MoTa']) ?></p>
            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid var(--border);">
                <strong style="color:var(--green);font-size:16px;"><?= number_format((float)$c['Gia'], 0, ',', '.') ?> đ</strong>
                <span style="font-size:12px;color:#888;"><?= (int)$c['SoHocVien'] ?> học viên</span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $pagination->render('index.php?controller=dashboard&action=index&q=' . urlencode($keyword)) ?>
