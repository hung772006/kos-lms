<?php
$escape    = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$keyword   = $keyword ?? '';
$role      = $role ?? 'all';
$status    = $status ?? 'all';
$editUser  = $editUser ?? null;
$currentId = (int) ($user['MaNguoiDung'] ?? 0);

$baseUrl = 'index.php?controller=user&action=index'
    . '&q=' . rawurlencode($keyword)
    . '&role=' . rawurlencode($role)
    . '&status=' . rawurlencode($status);

$roleLabels = [
    'student'  => 'Học viên',
    'teacher'  => 'Giáo viên',
    'training' => 'Đào tạo',
    'manager'  => 'Quản lý',
];
?>

<style>
    .usr-top { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px 26px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; box-shadow:var(--shadow); }
    .usr-top h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .usr-top p { font-size:13px; color:#666; }
    .usr-kpi-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:22px; }
    .usr-kpi { background:#fff; border:1px solid var(--border); border-radius:14px; padding:18px 20px; box-shadow:var(--shadow); }
    .usr-kpi-label { font-size:11px; font-weight:700; text-transform:uppercase; color:#666; letter-spacing:.4px; }
    .usr-kpi-val { font-size:26px; font-weight:800; margin-top:6px; color:var(--ink); }
    .usr-filter { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
    .usr-filter input, .usr-filter select { padding:9px 13px; border:1.5px solid var(--border); border-radius:9px; font-family:inherit; font-size:13px; background:var(--cream); color:var(--ink); outline:none; }
    .usr-filter input { flex:1; min-width:220px; }
    .usr-filter input:focus, .usr-filter select:focus { border-color:var(--gold); background:#fff; }
    .usr-table { width:100%; border-collapse:collapse; font-size:13px; }
    .usr-table th { text-align:left; padding:12px 14px; background:var(--cream); border-bottom:2px solid var(--border); font-size:11px; font-weight:700; text-transform:uppercase; color:#666; white-space:nowrap; }
    .usr-table td { padding:13px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .usr-person { display:flex; align-items:center; gap:12px; }
    .usr-ava { width:40px; height:40px; border-radius:50%; background:var(--ink); color:var(--gold); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:15px; flex-shrink:0; overflow:hidden; }
    .usr-ava img { width:100%; height:100%; object-fit:cover; display:block; }
    .usr-role-sel { padding:5px 9px; border-radius:8px; border:1.5px solid var(--border); font-family:inherit; font-size:12px; font-weight:700; background:var(--cream); cursor:pointer; }
    .usr-actions { display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap; }

    .usr-modal-bg { display:<?= $editUser ? 'flex' : 'none' ?>; position:fixed; inset:0; background:rgba(13,13,18,.58); justify-content:center; align-items:center; padding:20px; z-index:1000; }
    .usr-modal { background:#fff; width:100%; max-width:640px; max-height:92vh; overflow-y:auto; border-radius:18px; padding:26px 28px; box-shadow:var(--shadow-lg); }
    .um-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .um-group { margin-bottom:14px; }
    .um-group label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#555; margin-bottom:5px; }
    .um-group input, .um-group select, .um-group textarea { width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:9px; font-family:inherit; font-size:13px; background:var(--cream); outline:none; }
    .um-group textarea { min-height:72px; resize:vertical; }
    .um-ext-box { background:#faf8f3; border:1px dashed var(--gold); border-radius:12px; padding:14px 16px; margin-bottom:14px; }

    @media (max-width:960px) {
        .usr-kpi-grid { grid-template-columns:1fr 1fr; }
        .um-row { grid-template-columns:1fr; }
    }
</style>

<div class="usr-top">
    <div>
        <h2>👥 Quản lý người dùng &amp; Phân quyền</h2>
        <p>Quản trị tập trung tài khoản 4 vai trò và hồ sơ mở rộng 1–1 (Học viên / Giảng viên) tại KOS English Center.</p>
    </div>
    <button type="button" class="btn btn-gold" onclick="openCreateUserModal()">
        ➕ Thêm tài khoản mới
    </button>
</div>

<div class="usr-kpi-grid">
    <div class="usr-kpi" style="border-left:4px solid var(--ink);">
        <div class="usr-kpi-label">Tổng tài khoản</div>
        <div class="usr-kpi-val"><?= number_format((int) ($stats['total'] ?? 0)) ?></div>
    </div>
    <div class="usr-kpi" style="border-left:4px solid var(--green);">
        <div class="usr-kpi-label">Học viên</div>
        <div class="usr-kpi-val" style="color:var(--green);"><?= number_format((int) ($stats['students'] ?? 0)) ?></div>
    </div>
    <div class="usr-kpi" style="border-left:4px solid var(--gold);">
        <div class="usr-kpi-label">Giảng viên</div>
        <div class="usr-kpi-val" style="color:#b7791f;"><?= number_format((int) ($stats['teachers'] ?? 0)) ?></div>
    </div>
    <div class="usr-kpi" style="border-left:4px solid var(--red);">
        <div class="usr-kpi-label">Đào tạo / Quản lý / Khóa</div>
        <div class="usr-kpi-val" style="font-size:20px;">
            <?= (int) ($stats['staff'] ?? 0) ?> quản trị · <span style="color:var(--red);"><?= (int) ($stats['inactive'] ?? 0) ?> khóa</span>
        </div>
    </div>
</div>

<div class="card">
    <form class="usr-filter" method="GET" action="index.php">
        <input type="hidden" name="controller" value="user">
        <input type="hidden" name="action" value="index">
        <input type="text" name="q" value="<?= $escape($keyword) ?>" placeholder="Tìm theo họ tên, email hoặc số điện thoại...">
        <select name="role">
            <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>Tất cả vai trò</option>
            <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>🎓 Học viên</option>
            <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>👩‍🏫 Giáo viên</option>
            <option value="training" <?= $role === 'training' ? 'selected' : '' ?>>📋 Bộ phận đào tạo</option>
            <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>🏛 Bộ phận quản lý</option>
        </select>
        <select name="status">
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Mọi trạng thái</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Đang tạm khóa</option>
        </select>
        <button type="submit" class="btn btn-dark">Lọc danh sách</button>
        <?php if ($keyword !== '' || $role !== 'all' || $status !== 'all'): ?>
            <a href="index.php?controller=user&amp;action=index" class="btn btn-outline">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x:auto;">
        <table class="usr-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Người dùng &amp; Hồ sơ 1-1</th>
                    <th>Liên hệ</th>
                    <th>Phân quyền</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $item): ?>
                        <?php
                        $uid    = (int) $item['MaNguoiDung'];
                        $isSelf = ($uid === $currentId);
                        $uRole  = $item['VaiTro'] ?? 'student';
                        $active = ($item['TrangThai'] ?? 'active') === 'active';

                        $avaSrc = '';
                        if (!empty($item['AnhDaiDien'])) {
                            $clean = ltrim(trim((string) $item['AnhDaiDien']), '/');
                            if (str_starts_with($clean, 'public/') && is_file(__DIR__ . '/../../../' . $clean)) {
                                $avaSrc = $clean;
                            } elseif (is_file(__DIR__ . '/../../../public/' . $clean)) {
                                $avaSrc = 'public/' . $clean;
                            }
                        }

                        $initChar = '?';
                        if (preg_match('/^./u', trim((string) ($item['HoTen'] ?? '')), $m) === 1) {
                            $initChar = mb_strtoupper($m[0], 'UTF-8');
                        }
                        ?>
                        <tr>
                            <td style="font-weight:700;color:#888;">#<?= $uid ?></td>
                            <td>
                                <div class="usr-person">
                                    <div class="usr-ava">
                                        <?php if ($avaSrc !== ''): ?>
                                            <img src="<?= $escape($avaSrc) ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?= $escape($initChar) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:800;color:var(--ink);">
                                            <?= $escape($item['HoTen']) ?>
                                            <?php if ($isSelf): ?>
                                                <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;margin-left:4px;">Bạn</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:11px;color:#777;margin-top:2px;">
                                            <?php if ($uRole === 'teacher'): ?>
                                                📚 <?= $escape($item['ChuyenMonGV'] ?? 'Giảng viên KOS') ?> · <?= (int) ($item['SoKhoaHocPhuTrach'] ?? 0) ?> khóa
                                            <?php elseif ($uRole === 'student'): ?>
                                                ⏱ <?= (int) ($item['TongGioHoc'] ?? 0) ?>h học · Đã ĐK <?= (int) ($item['SoKhoaHocDangKy'] ?? 0) ?> khóa
                                            <?php else: ?>
                                                🗓 Tham gia: <?= !empty($item['NgayTao']) ? $escape(date('d/m/Y', strtotime((string) $item['NgayTao']))) : '—' ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--ink);"><?= $escape($item['Email']) ?></div>
                                <div style="font-size:12px;color:#777;"><?= $escape($item['SDT']) ?></div>
                            </td>
                            <td>
                                <?php if ($isSelf): ?>
                                    <span class="badge" style="background:#e8f0fe;color:#1d3557;">🏛 Quản lý</span>
                                <?php else: ?>
                                    <form method="POST" action="index.php?controller=user&amp;action=changeRole" style="margin:0;">
                                        <input type="hidden" name="user_id" value="<?= $uid ?>">
                                        <select name="new_role" class="usr-role-sel" onchange="if(confirm('Đổi vai trò của tài khoản này?')) this.form.submit(); else this.value='<?= $escape($uRole) ?>';">
                                            <?php foreach ($roleLabels as $rk => $rl): ?>
                                                <option value="<?= $escape($rk) ?>" <?= $uRole === $rk ? 'selected' : '' ?>><?= $escape($rl) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="<?= $active ? 'background:#dff0d8;color:var(--green);' : 'background:#fde8e8;color:var(--red);' ?>">
                                    <?= $active ? '● Hoạt động' : '🔒 Tạm khóa' ?>
                                </span>
                            </td>
                            <td>
                                <div class="usr-actions">
                                    <a href="index.php?controller=user&amp;action=index&amp;edit=<?= $uid ?>" class="btn btn-outline btn-sm">
                                        ✏ Sửa
                                    </a>

                                    <?php if (!$isSelf): ?>
                                        <form method="POST" action="index.php?controller=user&amp;action=toggleStatus" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Xác nhận đổi trạng thái khóa/mở khóa tài khoản này?');">
                                                <?= $active ? '🔒 Khóa' : '🔓 Mở' ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="index.php?controller=user&amp;action=delete" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản #<?= $uid ?> không?');">
                                                🗑 Xóa
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding:36px;text-align:center;color:#888;">Không tìm thấy người dùng nào khớp với điều kiện lọc.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($pagination) && $pagination instanceof Pagination): ?>
        <div style="margin-top:18px;">
            <?= $pagination->render($baseUrl) ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL THÊM MỚI / CẬP NHẬT NGƯỜI DÙNG -->
<div id="userModal" class="usr-modal-bg" onclick="if(event.target===this) closeUserModal();">
    <div class="usr-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">
            <h3 id="userModalTitle" style="font-size:18px;font-weight:800;color:var(--ink);">
                <?= $editUser ? '✏ Chỉnh sửa tài khoản #' . (int) $editUser['MaNguoiDung'] : '➕ Thêm tài khoản người dùng mới' ?>
            </h3>
            <button type="button" onclick="closeUserModal()" style="border:0;background:var(--cream);width:32px;height:32px;border-radius:8px;cursor:pointer;font-weight:800;">✕</button>
        </div>

        <form id="userAdminForm" method="POST" action="index.php?controller=user&amp;action=save" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="f_user_id" value="<?= (int) ($editUser['MaNguoiDung'] ?? 0) ?>">

            <div class="um-row">
                <div class="um-group">
                    <label>Họ và tên *</label>
                    <input type="text" name="HoTen" id="f_HoTen" value="<?= $escape($editUser['HoTen'] ?? '') ?>" maxlength="100" required>
                </div>
                <div class="um-group">
                    <label>Số điện thoại *</label>
                    <input type="text" name="SDT" id="f_SDT" value="<?= $escape($editUser['SDT'] ?? '') ?>" maxlength="15" required>
                </div>
            </div>

            <div class="um-row">
                <div class="um-group">
                    <label>Địa chỉ Email *</label>
                    <input type="email" name="Email" id="f_Email" value="<?= $escape($editUser['Email'] ?? '') ?>" required>
                </div>
                <div class="um-group">
                    <label id="f_pw_label"><?= $editUser ? 'Mật khẩu mới (để trống nếu giữ nguyên)' : 'Mật khẩu khởi tạo * (tối thiểu 6 ký tự)' ?></label>
                    <input type="password" name="MatKhau" id="f_MatKhau" <?= $editUser ? '' : 'required minlength="6"' ?> placeholder="••••••••">
                </div>
            </div>

            <div class="um-row">
                <div class="um-group">
                    <label>Vai trò hệ thống *</label>
                    <?php $selRole = $editUser['VaiTro'] ?? 'student'; ?>
                    <select name="VaiTro" id="f_VaiTro" onchange="toggleRoleExtFields(this.value)">
                        <option value="student" <?= $selRole === 'student' ? 'selected' : '' ?>>🎓 Học viên (student)</option>
                        <option value="teacher" <?= $selRole === 'teacher' ? 'selected' : '' ?>>👩‍🏫 Giáo viên (teacher)</option>
                        <option value="training" <?= $selRole === 'training' ? 'selected' : '' ?>>📋 Bộ phận đào tạo (training)</option>
                        <option value="manager" <?= $selRole === 'manager' ? 'selected' : '' ?>>🏛 Bộ phận quản lý (manager)</option>
                    </select>
                </div>
                <div class="um-group">
                    <label>Trạng thái tài khoản</label>
                    <?php $selStatus = $editUser['TrangThai'] ?? 'active'; ?>
                    <select name="TrangThai" id="f_TrangThai">
                        <option value="active" <?= $selStatus === 'active' ? 'selected' : '' ?>>Đang hoạt động (active)</option>
                        <option value="inactive" <?= $selStatus === 'inactive' ? 'selected' : '' ?>>Tạm khóa (inactive)</option>
                    </select>
                </div>
            </div>

            <div class="um-group">
                <label>Ảnh đại diện (.jpg, .png, .webp - tối đa 5MB)</label>
                <input type="file" name="AnhDaiDien" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>

            <!-- Khối mở rộng 1-1 cho Học viên -->
            <div id="ext_student" class="um-ext-box" style="display:<?= $selRole === 'student' ? 'block' : 'none' ?>;">
                <div style="font-size:12px;font-weight:800;color:var(--ink);margin-bottom:10px;">🎓 Hồ sơ Học viên mở rộng (Quan hệ 1–1)</div>
                <div class="um-group">
                    <label>Ngày sinh</label>
                    <input type="date" name="NgaySinh" id="f_NgaySinh" value="<?= $escape($editUser['NgaySinh'] ?? '') ?>">
                </div>
                <div class="um-group" style="margin-bottom:0;">
                    <label>Giới thiệu / Mục tiêu học tập</label>
                    <textarea name="GioiThieu" id="f_GioiThieu"><?= $escape($editUser['GioiThieu'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Khối mở rộng 1-1 cho Giáo viên -->
            <div id="ext_teacher" class="um-ext-box" style="display:<?= $selRole === 'teacher' ? 'block' : 'none' ?>;">
                <div style="font-size:12px;font-weight:800;color:var(--ink);margin-bottom:10px;">👩‍🏫 Hồ sơ Giảng viên mở rộng (Quan hệ 1–1)</div>
                <div class="um-row">
                    <div class="um-group">
                        <label>Chuyên môn giảng dạy</label>
                        <input type="text" name="ChuyenMonGV" id="f_ChuyenMonGV" value="<?= $escape($editUser['ChuyenMonGV'] ?? 'Giảng viên Tiếng Anh KOS') ?>">
                    </div>
                    <div class="um-group">
                        <label>Số năm kinh nghiệm</label>
                        <input type="number" min="0" max="50" name="SoNamKinhNghiem" id="f_SoNamKinhNghiem" value="<?= (int) ($editUser['SoNamKinhNghiem'] ?? 1) ?>">
                    </div>
                </div>
                <div class="um-group">
                    <label>Chứng chỉ quốc tế</label>
                    <input type="text" name="ChungChi" id="f_ChungChi" value="<?= $escape($editUser['ChungChi'] ?? '') ?>" placeholder="VD: IELTS 8.5, TESOL, CELTA">
                </div>
                <div class="um-group" style="margin-bottom:0;">
                    <label>Tiểu sử giảng viên</label>
                    <textarea name="GioiThieuGV" id="f_GioiThieuGV"><?= $escape($editUser['GioiThieuGV'] ?? '') ?></textarea>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:12px;border-top:1px solid var(--border);">
                <button type="button" class="btn btn-outline" onclick="closeUserModal()">Hủy</button>
                <button type="submit" class="btn btn-gold">💾 Lưu thông tin người dùng</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRoleExtFields(role) {
    document.getElementById('ext_student').style.display = (role === 'student') ? 'block' : 'none';
    document.getElementById('ext_teacher').style.display = (role === 'teacher') ? 'block' : 'none';
}

function openCreateUserModal() {
    document.getElementById('userModalTitle').textContent = '➕ Thêm tài khoản người dùng mới';
    document.getElementById('f_user_id').value = '0';
    document.getElementById('f_HoTen').value = '';
    document.getElementById('f_SDT').value = '';
    document.getElementById('f_Email').value = '';
    document.getElementById('f_MatKhau').value = '';
    document.getElementById('f_MatKhau').required = true;
    document.getElementById('f_pw_label').textContent = 'Mật khẩu khởi tạo * (tối thiểu 6 ký tự)';
    document.getElementById('f_VaiTro').value = 'student';
    document.getElementById('f_TrangThai').value = 'active';
    document.getElementById('f_NgaySinh').value = '';
    document.getElementById('f_GioiThieu').value = '';
    document.getElementById('f_ChuyenMonGV').value = 'Giảng viên Tiếng Anh KOS';
    document.getElementById('f_SoNamKinhNghiem').value = '1';
    document.getElementById('f_ChungChi').value = '';
    document.getElementById('f_GioiThieuGV').value = '';
    toggleRoleExtFields('student');
    document.getElementById('userModal').style.display = 'flex';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
    if (window.location.search.indexOf('edit=') !== -1) {
        window.location.href = 'index.php?controller=user&action=index';
    }
}
</script>