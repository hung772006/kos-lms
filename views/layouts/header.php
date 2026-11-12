<?php
$user = Session::getUser();
$roleLabels = [
    'student'  => 'Học viên',
    'teacher'  => 'Giáo viên',
    'training' => 'Bộ phận đào tạo',
    'manager'  => 'Bộ phận quản lý',
];

$currentRole       = $user['VaiTro'] ?? 'student';
$isStudent         = $currentRole === 'student';
$currentController = strtolower($_GET['controller'] ?? 'dashboard');
$currentAction     = strtolower($_GET['action'] ?? 'index');
$activeView        = $_GET['view'] ?? ($isStudent ? 'courses' : 'manage');

// Menu điều hướng chuẩn theo 4 vai trò của dự án KOS English Center
$menusByRole = [
    'student' => [
        ['label' => 'Khám phá khóa học', 'icon' => '◈', 'url' => 'index.php?controller=dashboard&action=index&view=courses', 'active' => ($currentController === 'dashboard' && $activeView === 'courses')],
        ['label' => 'Khóa học của tôi',  'icon' => '▣', 'url' => 'index.php?controller=dashboard&action=index&view=mine',    'active' => ($currentController === 'dashboard' && $activeView === 'mine') || $currentController === 'learning'],
        ['label' => 'Thông báo',         'icon' => '🔔', 'url' => 'index.php?controller=approvalreport&action=notifications', 'active' => ($currentController === 'approvalreport' && $currentAction === 'notifications')],
        ['label' => 'Hồ sơ cá nhân',     'icon' => '👤', 'url' => 'index.php?controller=auth&action=profile',                 'active' => ($currentController === 'auth' && $currentAction === 'profile')],
    ],
    'teacher' => [
        ['label' => 'Quản lý khóa học',  'icon' => '◈', 'url' => 'index.php?controller=dashboard&action=index&view=manage',  'active' => ($currentController === 'dashboard')],
        ['label' => 'Nội dung & Chấm bài','icon' => '📝', 'url' => 'index.php?controller=learning&action=manage',             'active' => ($currentController === 'learning')],
        ['label' => 'Thông báo',         'icon' => '🔔', 'url' => 'index.php?controller=approvalreport&action=notifications', 'active' => ($currentController === 'approvalreport' && $currentAction === 'notifications')],
        ['label' => 'Hồ sơ cá nhân',     'icon' => '👤', 'url' => 'index.php?controller=auth&action=profile',                 'active' => ($currentController === 'auth' && $currentAction === 'profile')],
    ],
    'training' => [
        ['label' => 'Phê duyệt khóa học','icon' => '✓', 'url' => 'index.php?controller=approvalreport&action=approvals',     'active' => ($currentController === 'approvalreport' && $currentAction === 'approvals')],
        ['label' => 'Danh sách khóa học','icon' => '◈', 'url' => 'index.php?controller=dashboard&action=index&view=manage',  'active' => ($currentController === 'dashboard')],
        ['label' => 'Thông báo',         'icon' => '🔔', 'url' => 'index.php?controller=approvalreport&action=notifications', 'active' => ($currentController === 'approvalreport' && $currentAction === 'notifications')],
        ['label' => 'Hồ sơ cá nhân',     'icon' => '👤', 'url' => 'index.php?controller=auth&action=profile',                 'active' => ($currentController === 'auth' && $currentAction === 'profile')],
    ],
    'manager' => [
        ['label' => 'Quản lý người dùng','icon' => '👥', 'url' => 'index.php?controller=user&action=index',                   'active' => ($currentController === 'user')],
        ['label' => 'Quản lý khóa học',  'icon' => '◈', 'url' => 'index.php?controller=dashboard&action=index&view=manage',  'active' => ($currentController === 'dashboard')],
        ['label' => 'Báo cáo thống kê',  'icon' => '📊', 'url' => 'index.php?controller=approvalreport&action=reports',       'active' => ($currentController === 'approvalreport' && $currentAction === 'reports')],
        ['label' => 'Thông báo',         'icon' => '🔔', 'url' => 'index.php?controller=approvalreport&action=notifications', 'active' => ($currentController === 'approvalreport' && $currentAction === 'notifications')],
        ['label' => 'Hồ sơ cá nhân',     'icon' => '👤', 'url' => 'index.php?controller=auth&action=profile',                 'active' => ($currentController === 'auth' && $currentAction === 'profile')],
    ],
];
$navigation = $menusByRole[$currentRole] ?? $menusByRole['student'];

// Tự động xác định tiêu đề trang theo màn hình đang mở (cho phép View ghi đè bằng $pageTitle)
if (!isset($pageTitle) || $pageTitle === '') {
    $pageTitle = 'Hệ thống LMS';
    foreach ($navigation as $item) {
        if ($item['active']) {
            $pageTitle = $item['label'];
            break;
        }
    }
}

// Xử lý chữ cái đầu & đường dẫn ảnh đại diện thật
$avatarInitial = '?';
if (preg_match('/^./u', trim((string) ($user['HoTen'] ?? '')), $avatarMatch) === 1) {
    $avatarInitial = mb_strtoupper($avatarMatch[0], 'UTF-8');
}
$avatarUrl = '';
if (!empty($user['AnhDaiDien'])) {
    $cleanAvatar = ltrim(trim((string) $user['AnhDaiDien']), '/');
    if (str_starts_with($cleanAvatar, 'public/') && is_file(__DIR__ . '/../../' . $cleanAvatar)) {
        $avatarUrl = $cleanAvatar;
    } elseif (is_file(__DIR__ . '/../../public/' . $cleanAvatar)) {
        $avatarUrl = 'public/' . $cleanAvatar;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle . ' — ' . APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:#0d0d12; --paper:#f5f3ee; --cream:#faf8f3; --gold:#c9a84c;
            --gold-light:#e8d08a; --green:#2d6a4f; --green-light:#52b788;
            --blue:#1d3557; --blue-mid:#457b9d; --red:#c1121f;
            --border:#e2ddd6; --shadow:0 2px 20px rgba(13,13,18,.08);
            --shadow-lg:0 8px 40px rgba(13,13,18,.14);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Lexend',sans-serif; background:var(--cream); color:var(--ink); min-height:100vh; }
        .sidebar {
            width:260px; background:var(--ink); height:100vh; position:fixed; left:0; top:0;
            display:flex; flex-direction:column; z-index:100; overflow-y:auto;
        }
        .sidebar-logo { padding:20px 24px; border-bottom:1px solid rgba(255,255,255,.06); }
        .logo-text { font-size:22px; font-weight:900; color:#fff; }
        .logo-text span { color:var(--gold); }
        .sidebar-role { padding-top:4px; font-size:10px; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,.35); }
        .sidebar-user { padding:18px 24px; display:flex; align-items:center; gap:12px; border-bottom:1px solid rgba(255,255,255,.06); }
        .user-avatar {
            width:40px; height:40px; border-radius:50%; display:flex; align-items:center;
            justify-content:center; font-weight:700; font-size:15px; flex-shrink:0;
            background:#fdebd0; color:#784212; overflow:hidden;
        }
        .user-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .user-info .name { color:#fff; font-size:14px; font-weight:600; }
        .user-info .role-label { font-size:11px; color:var(--gold); margin-top:2px; }
        .sidebar-nav { flex:1; padding:16px 12px; }
        .nav-section-label {
            font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase;
            color:rgba(255,255,255,.25); padding:10px 12px 6px;
        }
        .nav-item {
            display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
            color:rgba(255,255,255,.65); text-decoration:none; font-size:14px; font-weight:500;
            transition:all .15s; margin-bottom:4px;
        }
        .nav-item:hover { background:rgba(255,255,255,.08); color:#fff; }
        .nav-item.active { background:var(--gold); color:var(--ink); font-weight:700; }
        .nav-item .icon { font-size:15px; width:20px; text-align:center; }
        .sidebar-bottom { padding:16px 12px; border-top:1px solid rgba(255,255,255,.06); }
        .logout-btn {
            display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
            color:#ff8a8a; font-size:13px; font-weight:500; transition:all .15s; width:100%;
            background:none; border:none; text-decoration:none;
        }
        .logout-btn:hover { background:rgba(255,255,255,.06); color:#ffb3b3; }
        .main-content { margin-left:260px; min-height:100vh; }
        .topbar {
            background:#fff; padding:0 40px; border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between; gap:20px;
            position:sticky; top:0; z-index:50; height:64px;
        }
        .page-title { font-size:20px; font-weight:700; white-space:nowrap; }
        .topbar-actions { display:flex; align-items:center; gap:12px; flex:1; justify-content:flex-end; }
        .search-bar {
            flex:1; max-width:400px; display:flex; align-items:center; gap:8px;
            border:1.5px solid var(--border); border-radius:10px; padding:4px 6px 4px 12px; background:#fff;
        }
        .search-bar input {
            width:100%; border:0; outline:0; padding:6px 0;
            font-family:'Lexend',sans-serif; font-size:13px; color:var(--ink); background:transparent;
        }
        .content-area { padding:32px 40px; }
        .card { background:#fff; border-radius:16px; border:1px solid var(--border); padding:24px; margin-bottom:20px; box-shadow:var(--shadow); }
        .course-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:24px; }
        .btn {
            display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 16px;
            border-radius:8px; border:1.5px solid transparent; font-family:'Lexend',sans-serif;
            font-size:13px; font-weight:600; text-decoration:none; cursor:pointer; transition:all .15s;
        }
        .btn-dark { background:var(--ink); color:#fff; }
        .btn-gold { background:var(--gold); color:var(--ink); }
        .btn-green { background:var(--green); color:#fff; }
        .btn-outline { background:#fff; border-color:var(--border); color:var(--ink); }
        .btn-danger { background:#fff; border-color:#f2c5c8; color:var(--red); }
        .btn-sm { padding:7px 12px; font-size:12px; }
        .badge { display:inline-block; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; background:#dff0d8; color:var(--green); }
        .flash { border-radius:12px; border:1px solid var(--border); padding:14px 20px; margin-bottom:20px; font-size:14px; font-weight:500; background:#fff; }
        .flash.success { background:#dff0d8; color:var(--green); border-color:#b7dfb0; }
        .flash.error { background:#fdedec; color:var(--red); border-color:#f5b7b1; }
        .flash.info { background:#e8f0fe; color:var(--blue); border-color:#b6d4fe; }
        @media (max-width:900px) {
            .sidebar { width:220px; }
            .main-content { margin-left:220px; }
            .topbar, .content-area { padding-left:20px; padding-right:20px; }
            .course-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-text">KOS<span> English</span></div>
            <div class="sidebar-role">ENGLISH LMS SYSTEM</div>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php if ($avatarUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar">
                <?php else: ?>
                    <?= htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($user['HoTen'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="role-label"><?= htmlspecialchars($roleLabels[$currentRole] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label"><?= $isStudent ? 'HỌC VIÊN (CLIENT)' : 'QUẢN TRỊ (ADMIN)' ?></div>
            <?php foreach ($navigation as $nav): ?>
                <a class="nav-item <?= $nav['active'] ? 'active' : '' ?>" href="<?= htmlspecialchars($nav['url'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="icon"><?= $nav['icon'] ?></span>
                    <span><?= htmlspecialchars($nav['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <a class="logout-btn" href="index.php?controller=auth&amp;action=logout" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?');">🚪 <span>Đăng xuất</span></a>
        </div>
    </aside>

    <div class="main-content">
        <div class="topbar">
            <div class="page-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="topbar-actions">
                <form class="search-bar" method="GET" action="index.php">
                    <input type="hidden" name="controller" value="dashboard">
                    <input type="hidden" name="action" value="index">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($isStudent ? 'courses' : 'manage', ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" name="q" value="<?= htmlspecialchars($currentController === 'dashboard' ? ($_GET['q'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm nhanh khóa học..." autocomplete="off">
                    <button class="btn btn-dark btn-sm" type="submit">Tìm</button>
                </form>
            </div>
        </div>
        <div class="content-area">
            <?php if ($flash = Session::getFlash()): ?>
                <div class="flash <?= htmlspecialchars($flash['type'] ?? 'info', ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($flash['message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>