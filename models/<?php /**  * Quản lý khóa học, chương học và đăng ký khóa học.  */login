<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> — Đăng nhập</title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:#0d0d12; --cream:#faf8f3; --gold:#c9a84c; --gold-light:#e8d08a;
            --green:#2d6a4f; --red:#c1121f; --border:#e2ddd6;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Lexend',sans-serif; background:var(--cream); color:var(--ink); min-height:100vh; }
        #auth-screen { min-height:100vh; display:flex; align-items:stretch; }
        .auth-left {
            width:45%; background:var(--ink); padding:60px; color:#fff; display:flex;
            flex-direction:column; justify-content:space-between; position:relative; overflow:hidden;
        }
        .auth-left::before {
            content:''; position:absolute; top:-100px; right:-100px; width:400px; height:400px;
            border:1px solid rgba(201,168,76,.15); border-radius:50%;
        }
        .auth-left::after {
            content:''; position:absolute; bottom:-150px; left:-80px; width:500px; height:500px;
            border:1px solid rgba(201,168,76,.08); border-radius:50%;
        }
        .auth-brand,.auth-hero,.auth-stats { position:relative; z-index:1; }
        .auth-brand .logo-text { font-size:36px; font-weight:900; letter-spacing:-1px; }
        .auth-brand .logo-text span { color:var(--gold); }
        .auth-brand p {
            color:rgba(255,255,255,.45); font-size:13px; margin-top:6px; font-weight:300;
            letter-spacing:2px; text-transform:uppercase;
        }
        .auth-hero h2 { font-size:42px; line-height:1.2; margin-bottom:20px; }
        .auth-hero h2 em { color:var(--gold); font-style:italic; }
        .auth-hero p { color:rgba(255,255,255,.5); line-height:1.7; font-size:15px; }
        .auth-stats { display:flex; gap:30px; }
        .stat { border-top:1px solid rgba(255,255,255,.12); padding-top:16px; }
        .stat-num { font-size:28px; color:var(--gold); font-weight:700; }
        .stat-label { font-size:11px; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:1px; }
        .auth-right { flex:1; display:flex; align-items:center; justify-content:center; padding:60px; }
        .auth-card { width:100%; max-width:420px; }
        .auth-card h3 { font-size:28px; margin-bottom:6px; }
        .subtitle { color:#888; font-size:14px; margin-bottom:36px; }
        .form-group { margin-bottom:20px; }
        .form-group label {
            display:block; font-size:12px; font-weight:600; letter-spacing:1px;
            text-transform:uppercase; color:#666; margin-bottom:8px;
        }
        .form-group input {
            width:100%; padding:14px 16px; border:1.5px solid var(--border); border-radius:10px;
            font-family:Lexend,sans-serif; font-size:15px; color:var(--ink); background:#fff;
            outline:none; transition:border-color .2s,box-shadow .2s;
        }
        .form-group input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,168,76,.1); }
        .btn-primary {
            width:100%; padding:15px; background:var(--ink); color:#fff; border:0; border-radius:10px;
            font-family:Lexend,sans-serif; font-size:15px; font-weight:600; cursor:pointer;
            transition:background .2s,transform .1s; margin-top:8px;
        }
        .btn-primary:hover { background:#1a1a24; transform:translateY(-1px); }
        .alert { padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px; }
        .alert.error { background:#fdedec; color:var(--red); }
        .alert.success { background:#dff0d8; color:var(--green); }
        .alert.info { background:#fff3cd; color:#784212; }
        @media (max-width:760px) {
            #auth-screen { flex-direction:column; }
            .auth-left { width:100%; min-height:320px; padding:32px 24px; gap:28px; }
            .auth-hero h2 { font-size:32px; }
            .auth-stats { gap:18px; }
            .stat-num { font-size:23px; }
            .auth-right { padding:36px 24px; }
        }
    </style>
</head>
<body>
    <div id="auth-screen">
        <section class="auth-left">
            <div class="auth-brand">
                <div class="logo-text">KOS<span> English</span></div>
                <p>English Learning Management System</p>
            </div>
            <div class="auth-hero">
                <h2>Nền tảng học <em>tiếng Anh</em> chuyên nghiệp</h2>
                <p>Hệ thống quản lý khóa học tiếng Anh hiện đại, hỗ trợ đa vai trò và phân quyền chặt chẽ cho trung tâm KOS English.</p>
            </div>
            <div class="auth-stats">
                <div class="stat"><div class="stat-num">1,240</div><div class="stat-label">Học viên</div></div>
                <div class="stat"><div class="stat-num">86</div><div class="stat-label">Khóa tiếng Anh</div></div>
                <div class="stat"><div class="stat-num">34</div><div class="stat-label">Giáo viên</div></div>
            </div>
        </section>
        <section class="auth-right">
            <div class="auth-card">
                <h3>Đăng nhập hệ thống</h3>
                <p class="subtitle">Vui lòng đăng nhập để tiếp tục</p>
                <?php if ($flash = Session::getFlash()): ?>
                    <div class="alert <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="index.php?controller=auth&amp;action=login">
                    <div class="form-group">
                        <label for="login-email">Email hoặc Số điện thoại</label>
                        <input type="text" id="login-email" name="identifier" required autocomplete="username" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label for="login-pass">Mật khẩu</label>
                        <input type="password" id="login-pass" name="password" required autocomplete="current-password" placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-primary">Đăng nhập →</button>
                </form>
            </div>
        </section>
    </div>
</body>
</html>
