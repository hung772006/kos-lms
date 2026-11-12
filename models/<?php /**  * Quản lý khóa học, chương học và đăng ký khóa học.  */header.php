<?php
$user = Session::getUser();
$roleLabels = [
    'student' => 'Học viên',
    'teacher' => 'Giáo viên',
    'training' => 'Bộ phận đào tạo',
    'manager' => 'Bộ phận quản lý',
];
$isStudent = ($user['VaiTro'] ?? '') === 'student';
$activeView = $view ?? ($isStudent ? 'courses' : 'manage');
$pageTitle = $activeView === 'mine'
    ? 'Khóa học của tôi'
    : ($isStudent ? 'Khám phá khóa học' : 'Quản lý khóa học');
$navigation = $isStudent
    ? [
        ['courses', '◈', 'Khám phá khóa học'],
        ['mine', '▣', 'Khóa học của tôi'],
    ]
    : [
        ['manage', '◈', 'Quản lý khóa học'],
    ];
$avatarInitial = '?';
if (preg_match('/^./u', $user['HoTen'] ?? '', $avatarMatch) === 1) {
    $avatarInitial = $avatarMatch[0];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle . ' — ' . APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
        .sidebar-logo-row { display:flex; align-items:center; }
        .logo-text { font-size:22px; font-weight:900; color:#fff; }
        .logo-text span { color:var(--gold); }
        .sidebar-role { padding:4px 0 0; font-size:10px; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,.3); }
        .sidebar-user { padding:20px 24px; display:flex; align-items:center; gap:12px; border-bottom:1px solid rgba(255,255,255,.06); }
        .user-avatar {
            width:40px; height:40px; border-radius:50%; display:flex; align-items:center;
            justify-content:center; font-weight:700; font-size:15px; flex-shrink:0;
            background:#fdebd0; color:#784212;
        }
        .user-info .name { color:#fff; font-size:14px; font-weight:600; }
        .user-info .role-label { font-size:11px; color:rgba(255,255,255,.4); }
        .sidebar-nav { flex:1; padding:16px 12px; }
        .nav-section-label {
            font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase;
            color:rgba(255,255,255,.25); padding:12px 12px 6px;
        }
        .nav-item {
            display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
            color:rgba(255,255,255,.55); text-decoration:none; font-size:14px; font-weight:500;
            transition:all .15s; margin-bottom:2px;
        }
        .nav-item:hover { background:rgba(255,255,255,.06); color:rgba(255,255,255,.9); }
        .nav-item.active { background:var(--gold); color:var(--ink); font-weight:600; }
        .nav-item .icon { font-size:16px; width:20px; text-align:center; }
        .sidebar-bottom { padding:16px 12px; border-top:1px solid rgba(255,255,255,.06); }
        .logout-btn {
            display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
            color:rgba(255,255,255,.4); font-size:13px; transition:all .15s; width:100%;
            background:none; border:none; font-family:Lexend,sans-serif; text-decoration:none;
        }
        .logout-btn:hover { background:rgba(255,255,255,.06); color:rgba(255,255,255,.7); }
        .main-content { margin-left:260px; min-height:100vh; padding:0; }
        .topbar {
            background:#fff; padding:0 40px; border-bottom:1px solid var(--border);
            display:flex; align-items:stretch; gap:0; position:sticky; top:0; z-index:50; height:64px;
        }
        .page-title {
            font-size:22px; font-weight:700; white-space:nowrap; flex-shrink:0; display:flex;
            align-items:center; padding-right:20px; border-right:1px solid var(--border);
        }
        .topbar-actions { display:flex; align-items:center; gap:12px; flex:1; padding-left:20px; }
        .search-bar {
            position:relative; flex:1; max-width:440px; display:flex; align-items:center; gap:10px;
            border:1.5px solid var(--border); border-radius:10px; padding:0 12px; background:#fff;
        }
        .search-bar input {
            width:100%; min-width:0; border:0; outline:0; padding:10px 0;
            font-family:Lexend,sans-serif; font-size:13px; color:var(--ink); background:transparent;
        }
        .content-area { padding:32px 40px; }
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:16px; }
        .section-header h2 { font-size:20px; font-weight:700; flex-shrink:0; }
        .course-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:24px; }
        .course-card { background:#fff; border-radius:16px; border:1px solid var(--border); overflow:hidden; box-shadow:var(--shadow); }
        .course-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-lg); }
        .course-thumb { height:160px; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden; background:var(--paper); }
        .course-thumb img { width:100%; height:100%; object-fit:cover; }
        .course-thumb-placeholder { font-size:42px; color:var(--gold); }
        .course-body { padding:18px; }
        .course-tags { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px; }
        .tag { padding:3px 8px; border-radius:6px; font-size:10px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; background:#f5f3ee; }
        .course-body h4 { font-size:16px; margin-bottom:6px; line-height:1.3; }
        .course-body .teacher { font-size:12px; color:#888; margin-bottom:14px; }
        .course-meta { display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px solid var(--border); }
        .course-meta .price { font-size:18px; font-weight:700; color:var(--green); }
        .course-meta .students { font-size:12px; color:#999; }
        .badge { display:inline-block; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; }
        .badge-success { background:#dff0d8; color:var(--green); }
        .badge-warning { background:#fff3cd; color:#856404; }
        .badge-info { background:#d6eaf8; color:var(--blue); }
        .badge-gray { background:#eee; color:#666; }
        .pending-badge { background:#fff3cd; color:#856404; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .btn {
            display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 16px;
            border-radius:8px; border:1.5px solid transparent; font-family:Lexend,sans-serif;
            font-size:13px; font-weight:600; text-decoration:none; cursor:pointer; transition:all .15s;
        }
        .btn-dark { background:var(--ink); color:#fff; }
        .btn-dark:hover { background:#1a1a24; transform:translateY(-1px); }
        .btn-gold { background:var(--gold); color:var(--ink); }
        .btn-gold:hover { background:var(--gold-light); }
        .btn-green { background:var(--green); color:#fff; }
        .btn-outline { background:#fff; border-color:var(--border); color:var(--ink); }
        .btn-outline:hover { background:var(--cream); border-color:#ccc; }
        .btn-danger { background:#fff; border-color:#f2c5c8; color:var(--red); }
        .btn-sm { padding:7px 10px; font-size:12px; }
        .form-row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#666; margin-bottom:8px; }
        .form-group input, .form-group select, .form-group textarea {
            width:100%; padding:12px 14px; border:1.5px solid var(--border); border-radius:10px;
            font-family:Lexend,sans-serif; font-size:14px; color:var(--ink); background:#fff; outline:none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,168,76,.1); }
        .form-group textarea { min-height:100px; resize:vertical; }
        .modal-overlay {
            display:none; position:fixed; inset:0; background:rgba(13,13,18,.6); z-index:1000;
            align-items:center; justify-content:center; backdrop-filter:blur(4px); padding:20px;
        }
        .modal-overlay.open { display:flex; }
        .modal {
            background:#fff; border-radius:20px; padding:36px; width:100%; max-width:720px;
            box-shadow:var(--shadow-lg); animation:slideUp .25s ease; max-height:90vh; overflow-y:auto;
        }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .modal-title { font-size:22px; font-weight:700; }
        .modal-close {
            width:32px; height:32px; border-radius:8px; border:1px solid var(--border);
            background:var(--cream); cursor:pointer; display:flex; align-items:center; justify-content:center;
            font-size:16px; color:var(--ink); text-decoration:none;
        }
        .modal-footer { display:flex; justify-content:flex-end; gap:12px; margin-top:24px; padding-top:20px; border-top:1px solid var(--border); }
        .detail-hero { display:flex; gap:16px; align-items:center; padding:16px; border-radius:14px; margin-bottom:18px; background:var(--cream); }
        .detail-tabs { display:flex; gap:8px; margin:18px 0 10px; flex-wrap:wrap; }
        .detail-tab { padding:8px 12px; border-radius:8px; background:var(--paper); font-size:12px; font-weight:700; }
        .detail-section { padding:14px 0; border-bottom:1px solid var(--border); }
        .detail-section:last-child { border-bottom:0; }
        .filter-row { display:flex; gap:12px; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
        .filter-row .search-bar { flex:1; min-width:200px; }
        .learning-tracker { background:#fff; border-radius:16px; border:1px solid var(--border); padding:24px; box-shadow:var(--shadow); margin-bottom:20px; }
        .tracker-meta { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:16px; }
        .tracker-meta-item { text-align:center; padding:16px; background:var(--cream); border-radius:12px; }
        .tracker-meta-item .val { font-size:22px; font-weight:700; color:var(--gold); }
        .tracker-meta-item .lbl { font-size:12px; color:#888; margin-top:4px; }
        .enrolled-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .enrolled-item { display:flex; flex-direction:column; gap:12px; padding:20px; border-radius:16px; border:1px solid var(--border); background:#fff; }
        .enrolled-item-top { display:flex; align-items:center; gap:14px; }
        .enrolled-info { flex:1; }
        .enrolled-info h4 { font-size:15px; margin-bottom:4px; }
        .progress-bar { background:var(--border); border-radius:100px; height:6px; overflow:hidden; margin:8px 0; }
        .progress-fill { height:100%; border-radius:100px; background:linear-gradient(90deg,var(--gold),var(--gold-light)); }
        .enrolled-progress { display:flex; align-items:center; justify-content:space-between; gap:12px; padding-top:10px; border-top:1px solid var(--border); }
        .enrolled-progress .pct { font-size:22px; font-weight:700; color:var(--gold); }
        .empty-state { grid-column:1/-1; text-align:center; padding:60px 20px; color:#888; background:#fff; border:1px solid var(--border); border-radius:16px; }
        .payment-method { display:flex; gap:12px; margin-bottom:16px; }
        .payment-method label { flex:1; padding:12px; border:1.5px solid var(--border); border-radius:12px; cursor:pointer; text-align:center; font-size:13px; font-weight:600; }
        .payment-method input { accent-color:var(--gold); margin-right:5px; }
        .flash { background:#fff; border-radius:12px; border:1px solid var(--border); padding:14px 20px; margin-bottom:20px; font-size:14px; }
        .flash.success { background:#dff0d8; color:var(--green); }
        .flash.error { background:#fdedec; color:#922b21; }
        .course-admin-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:14px; }
        .chapter-item { padding:14px 0; border-bottom:1px solid var(--border); }
        .chapter-item:last-child { border-bottom:0; }
        .chapter-actions { display:flex; gap:8px; margin-top:10px; }
        @media (max-width:900px) {
            .sidebar { width:220px; }
            .main-content { margin-left:220px; }
            .topbar { padding:0 20px; }
            .content-area { padding:24px 20px; }
            .course-grid,.enrolled-grid { grid-template-columns:1fr; }
        }
        @media (max-width:640px) {
            .sidebar { position:static; width:100%; height:auto; }
            .sidebar-user { padding:12px 24px; }
            .sidebar-nav { display:flex; padding:8px 12px; }
            .sidebar-nav .nav-section-label { display:none; }
            .nav-item { flex:1; justify-content:center; font-size:12px; padding:9px 6px; }
            .sidebar-bottom { position:absolute; right:8px; top:8px; border:0; padding:0; }
            .main-content { margin-left:0; }
            .topbar { height:auto; min-height:64px; padding:12px 16px; flex-wrap:wrap; }
            .page-title { font-size:18px; padding-right:12px; }
            .topbar-actions { padding-left:12px; min-width:0; }
            .content-area { padding:20px 16px; }
            .form-row,.tracker-meta { grid-template-columns:1fr; }
            .modal { padding:24px 18px; }
        }
    </style>
</head>
<body>
    <div id="dashboard">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-row"><div class="logo-text">KOS<span> English</span></div></div>
                <div class="sidebar-role">HỆ THỐNG QUẢN LÝ ĐÀO TẠO</div>
            </div>
            <div class="sidebar-user">
                <div class="user-avatar"><?= htmlspecialchars($avatarInitial, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($user['HoTen'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="role-label"><?= htmlspecialchars($roleLabels[$user['VaiTro'] ?? ''] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section-label"><?= $isStudent ? 'HỌC TẬP' : 'QUẢN LÝ' ?></div>
                <?php foreach ($navigation as [$target, $icon, $label]): ?>
                    <a class="nav-item <?= $activeView === $target ? 'active' : '' ?>"
                       href="index.php?controller=dashboard&amp;action=index&amp;view=<?= rawurlencode($target) ?>">
                        <span class="icon"><?= $icon ?></span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-bottom">
                <a class="logout-btn" href="index.php?controller=auth&amp;action=logout">⇥ <span>Đăng xuất</span></a>
            </div>
        </aside>
        <main class="main-content">
            <div class="topbar">
                <div class="page-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($activeView !== 'mine'): ?>
                    <div class="topbar-actions">
                        <form class="search-bar" method="GET" action="index.php">
                            <input type="hidden" name="controller" value="dashboard">
                            <input type="hidden" name="action" value="index">
                            <input type="hidden" name="view" value="<?= htmlspecialchars($activeView, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (($category ?? '') !== ''): ?>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                            <span aria-hidden="true">⌕</span>
                            <input type="search" name="q" value="<?= htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm kiếm khóa học..." autocomplete="off">
                            <button class="btn btn-dark btn-sm" type="submit">Tìm</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <div class="content-area">
                <?php if ($flash = Session::getFlash()): ?>
                    <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
