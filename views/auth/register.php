<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$flash  = Session::getFlash();
$old    = $old ?? [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Học viên — <?= $escape(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#0d0d12; --cream:#faf8f3; --gold:#c9a84c; --green:#2d6a4f; --red:#c1121f; --border:#e2ddd6; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Lexend',sans-serif; background:var(--cream); color:var(--ink); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .reg-card { width:100%; max-width:560px; background:#fff; border-radius:22px; border:1px solid var(--border); padding:38px 40px; box-shadow:0 20px 60px rgba(13,13,18,.1); }
        .reg-logo { font-size:24px; font-weight:900; margin-bottom:16px; }
        .reg-logo span { color:var(--gold); }
        .reg-card h2 { font-size:23px; font-weight:800; margin-bottom:6px; }
        .reg-sub { font-size:13px; color:#666; margin-bottom:22px; }
        .flash { padding:12px 16px; border-radius:10px; font-size:13px; font-weight:500; margin-bottom:18px; }
        .flash.error { background:#fdedec; color:var(--red); border:1px solid #f5b7b1; }
        .f-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .f-group { margin-bottom:14px; }
        .f-group label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#555; margin-bottom:6px; }
        .f-group input { width:100%; padding:11px 13px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; font-size:13px; background:var(--cream); outline:none; }
        .f-group input:focus { border-color:var(--gold); background:#fff; }
        .btn-submit { width:100%; padding:13px; border:none; border-radius:10px; background:var(--gold); color:var(--ink); font-family:inherit; font-size:14px; font-weight:800; cursor:pointer; margin-top:8px; transition:.15s; }
        .btn-submit:hover { background:var(--ink); color:#fff; }
        .reg-foot { margin-top:20px; padding-top:16px; border-top:1px solid var(--border); text-align:center; font-size:13px; color:#666; }
        .reg-foot a { color:var(--ink); font-weight:700; text-decoration:none; border-bottom:2px solid var(--gold); }
        @media (max-width:600px) { .f-row { grid-template-columns:1fr; } .reg-card { padding:28px 22px; } }
    </style>
</head>
<body>
    <div class="reg-card">
        <div class="reg-logo">KOS<span> English</span></div>
        <h2>Đăng ký tài khoản Học viên</h2>
        <p class="reg-sub">Khởi tạo hồ sơ học tập cá nhân để tham gia các khóa học tại KOS English Center.</p>

        <?php if ($flash): ?>
            <div class="flash <?= $escape($flash['type'] ?? 'error') ?>">
                <?= $escape($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?controller=auth&amp;action=register">
            <div class="f-group">
                <label>Họ và tên *</label>
                <input type="text" name="HoTen" value="<?= $escape($old['HoTen'] ?? '') ?>" maxlength="100" placeholder="VD: Nguyễn Minh Anh" required>
            </div>

            <div class="f-row">
                <div class="f-group">
                    <label>Địa chỉ Email *</label>
                    <input type="email" name="Email" value="<?= $escape($old['Email'] ?? '') ?>" placeholder="email@domain.com" required>
                </div>
                <div class="f-group">
                    <label>Số điện thoại *</label>
                    <input type="text" name="SDT" value="<?= $escape($old['SDT'] ?? '') ?>" maxlength="15" placeholder="0912345678" required>
                </div>
            </div>

            <div class="f-group">
                <label>Ngày sinh (Hồ sơ học viên)</label>
                <input type="date" name="NgaySinh" value="<?= $escape($old['NgaySinh'] ?? '') ?>">
            </div>

            <div class="f-row">
                <div class="f-group">
                    <label>Mật khẩu * (tối thiểu 6 ký tự)</label>
                    <input type="password" name="MatKhau" minlength="6" placeholder="••••••••" required>
                </div>
                <div class="f-group">
                    <label>Nhập lại mật khẩu *</label>
                    <input type="password" name="MatKhauXacNhan" minlength="6" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Hoàn tất đăng ký tài khoản →</button>
        </form>

        <div class="reg-foot">
            Đã có tài khoản? <a href="index.php?controller=auth&amp;action=login">Quay lại Đăng nhập</a>
        </div>
    </div>
</body>
</html>