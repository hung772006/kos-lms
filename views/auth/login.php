<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$flash  = Session::getFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — <?= $escape(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#0d0d12; --cream:#faf8f3; --gold:#c9a84c; --green:#2d6a4f; --red:#c1121f; --border:#e2ddd6; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Lexend',sans-serif; background:var(--cream); color:var(--ink); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .auth-shell { width:100%; max-width:960px; background:#fff; border-radius:24px; border:1px solid var(--border); overflow:hidden; display:grid; grid-template-columns:1.05fr 1fr; box-shadow:0 20px 60px rgba(13,13,18,.12); }
        .auth-brand { background:var(--ink); color:#fff; padding:48px 42px; display:flex; flex-direction:column; justify-content:space-between; }
        .brand-logo { font-size:28px; font-weight:900; }
        .brand-logo span { color:var(--gold); }
        .brand-tag { display:inline-block; margin-top:8px; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.45); }
        .brand-hero h1 { font-size:28px; font-weight:800; line-height:1.3; margin:28px 0 14px; }
        .brand-hero p { font-size:14px; color:rgba(255,255,255,.7); line-height:1.7; }
        .demo-box { margin-top:28px; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:16px; }
        .demo-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; color:var(--gold); margin-bottom:10px; }
        .demo-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .demo-btn { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); color:#fff; padding:9px 10px; border-radius:9px; font-family:inherit; font-size:12px; font-weight:600; cursor:pointer; text-align:left; transition:.15s; }
        .demo-btn:hover { background:var(--gold); color:var(--ink); border-color:var(--gold); }
        .demo-btn small { display:block; font-size:10px; opacity:.75; font-weight:400; margin-top:2px; }
        .auth-form-col { padding:48px 42px; display:flex; flex-direction:column; justify-content:center; }
        .auth-form-col h2 { font-size:24px; font-weight:800; margin-bottom:6px; }
        .auth-sub { font-size:13px; color:#666; margin-bottom:24px; }
        .flash { padding:12px 16px; border-radius:10px; font-size:13px; font-weight:500; margin-bottom:18px; }
        .flash.error { background:#fdedec; color:var(--red); border:1px solid #f5b7b1; }
        .flash.success { background:#dff0d8; color:var(--green); border:1px solid #b7dfb0; }
        .flash.info { background:#e8f0fe; color:#1d3557; border:1px solid #b6d4fe; }
        .f-group { margin-bottom:16px; }
        .f-group label { display:block; font-size:12px; font-weight:700; text-transform:uppercase; color:#555; margin-bottom:6px; }
        .f-group input { width:100%; padding:12px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; font-size:14px; background:var(--cream); outline:none; transition:.15s; }
        .f-group input:focus { border-color:var(--gold); background:#fff; }
        .btn-submit { width:100%; padding:13px; border:none; border-radius:10px; background:var(--ink); color:#fff; font-family:inherit; font-size:14px; font-weight:700; cursor:pointer; margin-top:6px; transition:.15s; }
        .btn-submit:hover { background:var(--gold); color:var(--ink); }
        .auth-switch { margin-top:22px; padding-top:18px; border-top:1px solid var(--border); text-align:center; font-size:13px; color:#666; }
        .auth-switch a { color:var(--ink); font-weight:700; text-decoration:none; border-bottom:2px solid var(--gold); }
        @media (max-width:780px) { .auth-shell { grid-template-columns:1fr; } .auth-brand { padding:32px 28px; } .auth-form-col { padding:32px 28px; } }
    </style>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-brand">
            <div>
                <div class="brand-logo">KOS<span> English</span></div>
                <div class="brand-tag">Learning Management System</div>
                <div class="brand-hero">
                    <h1>Nền tảng đào tạo Tiếng Anh trực tuyến toàn diện</h1>
                    <p>Quản lý lộ trình học tập IELTS, TOEIC, Giao tiếp với hệ thống bài giảng Video, chấm điểm Quiz tự động và theo dõi tiến độ trực quan.</p>
                </div>
            </div>

            <div class="demo-box">
                <div class="demo-title">⚡ Đăng nhập nhanh 4 vai trò mẫu</div>
                <div class="demo-grid">
                    <button type="button" class="demo-btn" onclick="fillDemo('hocvien@edu.vn', 'HocVien@2026')">
                        🎓 Học viên <small>hocvien@edu.vn</small>
                    </button>
                    <button type="button" class="demo-btn" onclick="fillDemo('giaovien@edu.vn', 'GiaoVien@2026')">
                        👩‍🏫 Giáo viên <small>giaovien@edu.vn</small>
                    </button>
                    <button type="button" class="demo-btn" onclick="fillDemo('daotao@edu.vn', 'DaoTao@2026')">
                        📋 Đào tạo <small>daotao@edu.vn</small>
                    </button>
                    <button type="button" class="demo-btn" onclick="fillDemo('quanly@edu.vn', 'QuanLy@2026')">
                        🏛 Quản lý <small>quanly@edu.vn</small>
                    </button>
                </div>
            </div>
        </div>

        <div class="auth-form-col">
            <h2>Đăng nhập hệ thống</h2>
            <p class="auth-sub">Nhập Email hoặc Số điện thoại đã đăng ký để tiếp tục.</p>

            <?php if ($flash): ?>
                <div class="flash <?= $escape($flash['type'] ?? 'info') ?>">
                    <?= $escape($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?controller=auth&amp;action=login">
                <div class="f-group">
                    <label for="identifier">Email hoặc Số điện thoại</label>
                    <input type="text" id="identifier" name="identifier" placeholder="VD: hocvien@edu.vn hoặc 0912345678" required autofocus>
                </div>
                <div class="f-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu..." required>
                </div>
                <button type="submit" class="btn-submit">Đăng nhập vào KOS LMS →</button>
            </form>

            <div class="auth-switch">
                Bạn là học viên mới? <a href="index.php?controller=auth&amp;action=register">Đăng ký tài khoản ngay</a>
            </div>
        </div>
    </div>

    <script>
    function fillDemo(id, pw) {
        document.getElementById('identifier').value = id;
        document.getElementById('password').value = pw;
    }
    </script>
</body>
</html>