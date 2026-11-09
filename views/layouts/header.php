<?php
$user = Session::getUser();
$roleLabels = [
    'student'  => 'Học viên',
    'teacher'  => 'Giáo viên',
    'training' => 'Bộ phận đào tạo',
    'manager'  => 'Bộ phận quản lý'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#0d0d12; --cream:#faf8f3; --gold:#c9a84c; --green:#2d6a4f; --red:#c1121f; --border:#e2ddd6; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Lexend',sans-serif; background:var(--cream); color:var(--ink); min-height:100vh; }
        .sidebar { width:260px; background:var(--ink); height:100vh; position:fixed; left:0; top:0; display:flex; flex-direction:column; color:#fff; padding:24px 16px; }
        .logo-text { font-size:24px; font-weight:900; padding:0 12px 20px; border-bottom:1px solid rgba(255,255,255,0.08); }
        .logo-text span { color:var(--gold); }
        .user-box { padding:18px 12px; border-bottom:1px solid rgba(255,255,255,0.08); margin-bottom:16px; }
        .nav-item { display:block; padding:11px 14px; border-radius:10px; color:rgba(255,255,255,0.7); text-decoration:none; font-size:14px; margin-bottom:4px; }
        .nav-item.active, .nav-item:hover { background:var(--gold); color:var(--ink); font-weight:600; }
        .main-content { margin-left:260px; min-height:100vh; }
        .topbar { background:#fff; height:64px; padding:0 40px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        .content-area { padding:32px 40px; }
        .card { background:#fff; border-radius:16px; border:1px solid var(--border); padding:24px; margin-bottom:20px; }
        .course-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:20px; }
        .btn { display:inline-block; padding:9px 16px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; cursor:pointer; border:none; }
        .btn-gold { background:var(--gold); color:var(--ink); }
        .btn-dark { background:var(--ink); color:#fff; }
        .badge { display:inline-block; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; background:#dff0d8; color:var(--green); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-text">KOS<span> English</span></div>
        <div class="user-box">
            <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($user['HoTen'] ?? '') ?></div>
            <div style="font-size:12px;color:var(--gold);margin-top:4px;"><?= $roleLabels[$user['VaiTro']] ?? '' ?></div>
        </div>
        <nav style="flex:1;">
           <a class="nav-item <?= ($_GET['action'] ?? '') === 'index' ? 'active' : '' ?>" href="index.php?controller=dashboard&action=index">🏠 Tổng quan & Khóa học</a>
    
    <!-- Chỉ hiện nút "Phê duyệt khóa học" khi đăng nhập tài khoản Bộ phận đào tạo -->
    <?php if (($user['VaiTro'] ?? '') === 'training'): ?>
        <a class="nav-item <?= ($_GET['action'] ?? '') === 'approvalcourses' ? 'active' : '' ?>" href="index.php?controller=dashboard&action=approvalcourses">📝 Phê duyệt khóa học</a>
    <?php endif; ?>

    <!-- Chỉ hiện nút "Báo cáo thống kê" khi đăng nhập tài khoản Bộ phận quản lý -->
    <?php if (($user['VaiTro'] ?? '') === 'manager'): ?>
        <a class="nav-item <?= ($_GET['action'] ?? '') === 'reports' ? 'active' : '' ?>" href="index.php?controller=dashboard&action=reports">📊 Báo cáo thống kê</a>
    <?php endif; ?>
        </nav>
        <a class="nav-item" href="index.php?controller=auth&action=logout" style="color:#ff8a8a;">🚪 Đăng xuất</a>
    </div>
    <div class="main-content">
        <div class="topbar">
            <div style="font-size:20px;font-weight:700;">Hệ thống LMS — KOS English Center</div>
            <form method="GET" action="index.php" style="display:flex;gap:8px;width:380px;">
                <input type="hidden" name="controller" value="dashboard">
                <input type="hidden" name="action" value="index">
                <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm khóa học, danh mục..." style="flex:1;padding:8px 14px;border:1.5px solid var(--border);border-radius:8px;">
                <button type="submit" class="btn btn-dark">Tìm</button>
            </form>
        </div>
        <div class="content-area">
            <?php if ($flash = Session::getFlash()): ?>
                <div class="card" style="background:#dff0d8;color:var(--green);padding:14px 20px;"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endif; ?>
