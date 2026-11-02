<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — Đăng nhập</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#0d0d12; --cream:#faf8f3; --gold:#c9a84c; --green:#2d6a4f; --red:#c1121f; --border:#e2ddd6; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Lexend',sans-serif; background:var(--cream); color:var(--ink); min-height:100vh; display:flex; }
        .auth-left { width:45%; background:var(--ink); padding:60px; display:flex; flex-direction:column; justify-content:space-between; color:#fff; }
        .logo-text { font-size:36px; font-weight:900; }
        .logo-text span { color:var(--gold); }
        .auth-hero h2 { font-size:40px; line-height:1.2; margin-bottom:16px; }
        .auth-hero h2 em { color:var(--gold); }
        .auth-right { flex:1; display:flex; align-items:center; justify-content:center; padding:60px; }
        .auth-card { width:100%; max-width:420px; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:12px; font-weight:600; text-transform:uppercase; color:#666; margin-bottom:8px; }
        .form-group input { width:100%; padding:14px 16px; border:1.5px solid var(--border); border-radius:10px; font-size:15px; }
        .btn-primary { width:100%; padding:15px; background:var(--ink); color:#fff; border:none; border-radius:10px; font-size:15px; font-weight:600; cursor:pointer; }
        .alert { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px; background:#fdedec; color:var(--red); }
        .demo-box { margin-top:24px; padding:14px; background:#fff; border-radius:10px; border:1px solid var(--border); font-size:12px; line-height:1.8; }
    </style>
</head>
<body>
    <div class="auth-left">
        <div>
            <div class="logo-text">KOS<span> English</span></div>
            <p style="color:rgba(255,255,255,0.5);font-size:12px;letter-spacing:2px;margin-top:6px;">ENGLISH LEARNING MANAGEMENT SYSTEM</p>
        </div>
        <div class="auth-hero">
            <h2>Nền tảng học <em>tiếng Anh</em> chuyên nghiệp</h2>
            <p style="color:rgba(255,255,255,0.6);line-height:1.7;">Hệ thống quản lý khóa học tiếng Anh hiện đại, hỗ trợ đa vai trò và phân quyền chặt chẽ cho trung tâm KOS English.</p>
        </div>
        <div style="font-size:12px;color:rgba(255,255,255,0.4);">© 2026 KOS English Center · PHP OOP MVC</div>
    </div>
    <div class="auth-right">
        <div class="auth-card">
            <h3 style="font-size:28px;margin-bottom:6px;">Đăng nhập hệ thống</h3>
            <p style="color:#888;font-size:14px;margin-bottom:28px;">Vui lòng đăng nhập để tiếp tục</p>
            <?php if ($flash = Session::getFlash()): ?>
                <div class="alert"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endif; ?>
            <form method="POST" action="index.php?controller=auth&action=login">
                <div class="form-group">
                    <label>Email hoặc Số điện thoại</label>
                    <input type="text" name="identifier" required placeholder="hocvien@edu.vn">
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary">Đăng nhập →</button>
            </form>
            <div class="demo-box">
                <strong>Tài khoản mẫu:</strong><br>
                • Học viên: <code>hocvien@edu.vn</code> / <code>HocVien@2026</code><br>
                • Giáo viên: <code>giaovien@edu.vn</code> / <code>GiaoVien@2026</code><br>
                • BP Đào tạo: <code>daotao@edu.vn</code> / <code>DaoTao@2026</code><br>
                • BP Quản lý: <code>quanly@edu.vn</code> / <code>QuanLy@2026</code>
            </div>
        </div>
    </div>
</body>
</html>
