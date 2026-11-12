<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');

$roleLabels = [
    'student'  => 'Học viên',
    'teacher'  => 'Giáo viên',
    'training' => 'Bộ phận đào tạo',
    'manager'  => 'Bộ phận quản lý',
];
$role = $user['VaiTro'] ?? 'student';

$avatarUrl = '';
if (!empty($user['AnhDaiDien'])) {
    $clean = ltrim(trim((string) $user['AnhDaiDien']), '/');
    if (str_starts_with($clean, 'public/') && is_file(__DIR__ . '/../../' . $clean)) {
        $avatarUrl = $clean;
    } elseif (is_file(__DIR__ . '/../../public/' . $clean)) {
        $avatarUrl = 'public/' . $clean;
    }
}

$initial = '?';
if (preg_match('/^./u', trim((string) ($user['HoTen'] ?? '')), $m) === 1) {
    $initial = mb_strtoupper($m[0], 'UTF-8');
}
?>

<style>
    .prof-grid { display:grid; grid-template-columns:320px 1fr; gap:24px; align-items:start; }
    .prof-side { background:#fff; border:1px solid var(--border); border-radius:18px; padding:28px 24px; text-align:center; box-shadow:var(--shadow); }
    .prof-avatar-box { width:112px; height:112px; border-radius:50%; margin:0 auto 14px; background:var(--ink); color:var(--gold); display:flex; align-items:center; justify-content:center; font-size:40px; font-weight:800; overflow:hidden; border:4px solid var(--cream); box-shadow:0 4px 16px rgba(13,13,18,.12); }
    .prof-avatar-box img { width:100%; height:100%; object-fit:cover; display:block; }
    .prof-meta-list { margin-top:18px; padding-top:16px; border-top:1px solid var(--border); text-align:left; display:grid; gap:10px; font-size:13px; }
    .prof-meta-row { display:flex; justify-content:space-between; gap:10px; }
    .prof-meta-row span { color:#777; }
    .prof-form-card { background:#fff; border:1px solid var(--border); border-radius:18px; padding:26px 28px; margin-bottom:22px; box-shadow:var(--shadow); }
    .prof-form-card h3 { font-size:17px; font-weight:800; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--border); }
    .pf-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .pf-group { margin-bottom:15px; }
    .pf-group label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#666; margin-bottom:6px; letter-spacing:.4px; }
    .pf-group input, .pf-group textarea { width:100%; padding:10px 13px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; font-size:13px; background:var(--cream); color:var(--ink); outline:none; }
    .pf-group input:focus, .pf-group textarea:focus { border-color:var(--gold); background:#fff; }
    .pf-group input[readonly] { background:#eee; color:#777; cursor:not-allowed; }
    .pf-group textarea { min-height:86px; resize:vertical; }
    @media (max-width:900px) { .prof-grid { grid-template-columns:1fr; } .pf-row { grid-template-columns:1fr; } }
</style>

<div class="prof-grid">
    <div class="prof-side">
        <div class="prof-avatar-box">
            <?php if ($avatarUrl !== ''): ?>
                <img id="avatarPreviewImg" src="<?= $escape($avatarUrl) ?>" alt="Avatar">
            <?php else: ?>
                <span id="avatarInitialText"><?= $escape($initial) ?></span>
                <img id="avatarPreviewImg" src="" alt="Avatar" style="display:none;">
            <?php endif; ?>
        </div>
        <h3 style="font-size:19px;font-weight:800;margin-bottom:6px;"><?= $escape($user['HoTen'] ?? '') ?></h3>
        <span class="badge" style="background:#fef3c7;color:#92400e;"><?= $escape($roleLabels[$role] ?? $role) ?></span>

        <div class="prof-meta-list">
            <div class="prof-meta-row"><span>Mã tài khoản:</span> <strong>#<?= (int) ($user['MaNguoiDung'] ?? 0) ?></strong></div>
            <div class="prof-meta-row"><span>Email:</span> <strong><?= $escape($user['Email'] ?? '') ?></strong></div>
            <div class="prof-meta-row"><span>Số điện thoại:</span> <strong><?= $escape($user['SDT'] ?? '') ?></strong></div>
            <div class="prof-meta-row">
                <span>Ngày tham gia:</span>
                <strong><?= !empty($user['NgayTao']) ? $escape(date('d/m/Y', strtotime((string) $user['NgayTao']))) : '—' ?></strong>
            </div>
            <?php if ($role === 'student'): ?>
                <div class="prof-meta-row"><span>Giờ học tích lũy:</span> <strong style="color:var(--green);"><?= (int) ($user['TongGioHoc'] ?? 0) ?> giờ</strong></div>
            <?php elseif ($role === 'teacher'): ?>
                <div class="prof-meta-row"><span>Kinh nghiệm:</span> <strong style="color:var(--gold);"><?= (int) ($user['SoNamKinhNghiem'] ?? 1) ?> năm</strong></div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <form class="prof-form-card" method="POST" action="index.php?controller=auth&amp;action=profile" enctype="multipart/form-data">
            <h3>👤 Cập nhật thông tin cá nhân &amp; Ảnh đại diện</h3>

            <div class="pf-row">
                <div class="pf-group">
                    <label>Họ và tên *</label>
                    <input type="text" name="HoTen" value="<?= $escape($user['HoTen'] ?? '') ?>" maxlength="100" required>
                </div>
                <div class="pf-group">
                    <label>Số điện thoại *</label>
                    <input type="text" name="SDT" value="<?= $escape($user['SDT'] ?? '') ?>" maxlength="15" required>
                </div>
            </div>

            <div class="pf-row">
                <div class="pf-group">
                    <label>Địa chỉ Email (Định danh cố định)</label>
                    <input type="email" value="<?= $escape($user['Email'] ?? '') ?>" readonly>
                </div>
                <div class="pf-group">
                    <label>Tải ảnh đại diện mới (.jpg, .png, .webp - tối đa 5MB)</label>
                    <input type="file" name="AnhDaiDien" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" onchange="previewSelectedAvatar(this)">
                </div>
            </div>

            <?php if ($role === 'student'): ?>
                <div class="pf-group">
                    <label>Ngày sinh (Bảng Học Viên 1-1)</label>
                    <input type="date" name="NgaySinh" value="<?= $escape($user['NgaySinh'] ?? '') ?>">
                </div>
                <div class="pf-group">
                    <label>Mục tiêu &amp; Giới thiệu học tập</label>
                    <textarea name="GioiThieu" placeholder="Chia sẻ mục tiêu chinh phục tiếng Anh của bạn..."><?= $escape($user['GioiThieu'] ?? '') ?></textarea>
                </div>
            <?php elseif ($role === 'teacher'): ?>
                <div class="pf-row">
                    <div class="pf-group">
                        <label>Chuyên môn giảng dạy * (Bảng Giáo Viên 1-1)</label>
                        <input type="text" name="ChuyenMonGV" value="<?= $escape($user['ChuyenMonGV'] ?? '') ?>" required>
                    </div>
                    <div class="pf-group">
                        <label>Số năm kinh nghiệm</label>
                        <input type="number" min="0" max="50" name="SoNamKinhNghiem" value="<?= (int) ($user['SoNamKinhNghiem'] ?? 1) ?>">
                    </div>
                </div>
                <div class="pf-group">
                    <label>Chứng chỉ quốc tế (IELTS, TESOL, CELTA...)</label>
                    <input type="text" name="ChungChi" value="<?= $escape($user['ChungChi'] ?? '') ?>" placeholder="VD: IELTS 8.5, CELTA">
                </div>
                <div class="pf-group">
                    <label>Tiểu sử &amp; Phương pháp giảng dạy</label>
                    <textarea name="GioiThieuGV"><?= $escape($user['GioiThieuGV'] ?? '') ?></textarea>
                </div>
            <?php endif; ?>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-gold">💾 Lưu thay đổi hồ sơ</button>
            </div>
        </form>

        <form class="prof-form-card" method="POST" action="index.php?controller=auth&amp;action=changePassword">
            <h3>🔒 Đổi mật khẩu bảo mật</h3>
            <div class="pf-group">
                <label>Mật khẩu hiện tại *</label>
                <input type="password" name="current_password" placeholder="Nhập mật khẩu đang sử dụng..." required>
            </div>
            <div class="pf-row">
                <div class="pf-group">
                    <label>Mật khẩu mới * (Tối thiểu 6 ký tự)</label>
                    <input type="password" name="new_password" minlength="6" placeholder="Nhập mật khẩu mới..." required>
                </div>
                <div class="pf-group">
                    <label>Xác nhận mật khẩu mới *</label>
                    <input type="password" name="confirm_password" minlength="6" placeholder="Nhập lại mật khẩu mới..." required>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-dark">Cập nhật mật khẩu</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewSelectedAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('avatarPreviewImg');
            var txt = document.getElementById('avatarInitialText');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            if (txt) {
                txt.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>