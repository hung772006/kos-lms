<?php
$escape        = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$filter        = $filter ?? 'all';
$unreadCount   = (int) ($unreadCount ?? 0);
$notifSettings = $notifSettings ?? ['submission' => 1, 'approval' => 1, 'email' => 1];
$isTeacher     = (($user['VaiTro'] ?? '') === 'teacher');
$baseUrl       = 'index.php?controller=approvalreport&action=notifications&filter=' . rawurlencode($filter);

$typeIcons = [
    'approval'   => ['icon' => '✓',  'bg' => '#dff0d8', 'color' => '#2d6a4f'],
    'submission' => ['icon' => '📝', 'bg' => '#e8f0fe', 'color' => '#1d3557'],
    'grade'      => ['icon' => '🏆', 'bg' => '#fef3c7', 'color' => '#92400e'],
    'deadline'   => ['icon' => '⏰', 'bg' => '#fde8e8', 'color' => '#c1121f'],
    'system'     => ['icon' => '🔔', 'bg' => '#f0ede6', 'color' => '#784212'],
];
?>

<style>
    .ntf-bar { display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:22px; flex-wrap:wrap; }
    .ntf-tabs { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .ntf-tab { padding:9px 16px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; color:#555; background:#fff; border:1px solid var(--border); transition:0.15s; }
    .ntf-tab.active, .ntf-tab:hover { background:var(--ink); color:#fff; border-color:var(--ink); }
    .ntf-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .ntf-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; margin-bottom:20px; }
    .ntf-card { background:#fff; border:1px solid var(--border); border-radius:14px; padding:18px 20px; display:flex; gap:14px; align-items:flex-start; box-shadow:var(--shadow); transition:0.15s; }
    .ntf-card.unread { border-left:4px solid var(--gold); background:#fffdf7; }
    .ntf-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:800; flex-shrink:0; }
    .ntf-body { flex:1; min-width:0; }
    .ntf-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:6px; }
    .ntf-title { font-size:14px; font-weight:800; color:var(--ink); }
    .ntf-time { font-size:11px; color:#888; white-space:nowrap; }
    .ntf-text { font-size:13px; color:#555; line-height:1.55; }
    .ntf-foot { display:flex; justify-content:space-between; align-items:center; margin-top:10px; gap:8px; flex-wrap:wrap; }

    .ntf-modal-overlay { display:none; position:fixed; inset:0; background:rgba(13,13,18,0.55); justify-content:center; align-items:center; padding:20px; z-index:1000; }
    .ntf-modal { background:#fff; width:100%; max-width:460px; border-radius:18px; padding:26px 28px; box-shadow:var(--shadow-lg); }
    .ntf-setting-item { display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border:1px solid var(--border); border-radius:12px; background:var(--cream); margin-bottom:12px; cursor:pointer; font-size:13px; }
    .ntf-setting-item input { margin-top:2px; width:17px; height:17px; accent-color:var(--ink); flex-shrink:0; }

    @media (max-width:900px) {
        .ntf-grid { grid-template-columns:1fr; }
    }
</style>

<div class="ntf-bar">
    <div class="ntf-tabs">
        <a href="index.php?controller=approvalreport&amp;action=notifications&amp;filter=all" class="ntf-tab <?= $filter === 'all' ? 'active' : '' ?>">
            🔔 Tất cả thông báo
        </a>
        <a href="index.php?controller=approvalreport&amp;action=notifications&amp;filter=unread" class="ntf-tab <?= $filter === 'unread' ? 'active' : '' ?>">
            ✨ Chưa đọc (<?= $unreadCount ?>)
        </a>
    </div>

    <div class="ntf-actions">
        <?php if ($unreadCount > 0): ?>
            <form method="POST" action="index.php?controller=approvalreport&amp;action=markAllRead" style="margin:0;">
                <button type="submit" class="btn btn-outline">✓ Đánh dấu tất cả đã đọc</button>
            </form>
        <?php endif; ?>

        <?php if ($isTeacher): ?>
            <button type="button" onclick="openSettingsModal()" class="btn btn-gold">
                ⚙ Cài đặt thông báo
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="ntf-grid">
    <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $item): ?>
            <?php
            $typeKey  = $item['LoaiThongBao'] ?? 'system';
            $meta     = $typeIcons[$typeKey] ?? $typeIcons['system'];
            $isUnread = empty($item['DaDoc']);
            ?>
            <div class="ntf-card <?= $isUnread ? 'unread' : '' ?>">
                <div class="ntf-icon" style="background:<?= $meta['bg'] ?>;color:<?= $meta['color'] ?>;">
                    <?= $meta['icon'] ?>
                </div>

                <div class="ntf-body">
                    <div class="ntf-head">
                        <div class="ntf-title">
                            <?= $escape($item['TieuDe'] ?? 'Thông báo hệ thống') ?>
                            <?php if ($isUnread): ?>
                                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--gold);margin-left:5px;vertical-align:middle;"></span>
                            <?php endif; ?>
                        </div>
                        <span class="ntf-time">
                            <?= !empty($item['NgayTao']) ? $escape(date('H:i d/m/Y', strtotime((string) $item['NgayTao']))) : '' ?>
                        </span>
                    </div>

                    <p class="ntf-text"><?= nl2br($escape($item['NoiDung'] ?? '')) ?></p>

                    <div class="ntf-foot">
                        <span style="font-size:11px;color:#888;">
                            <?php if (!empty($item['TenKhoaHoc'])): ?>
                                📚 <?= $escape($item['TenKhoaHoc']) ?>
                            <?php elseif (!empty($item['TenNguoiGui'])): ?>
                                👤 Từ: <?= $escape($item['TenNguoiGui']) ?>
                            <?php else: ?>
                                🏛 Hệ thống KOS LMS
                            <?php endif; ?>
                        </span>

                        <?php if ($isUnread): ?>
                            <form method="POST" action="index.php?controller=approvalreport&amp;action=markRead" style="margin:0;">
                                <input type="hidden" name="id" value="<?= (int) $item['MaThongBao'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="padding:4px 10px;font-size:11px;">
                                    Đánh dấu đã đọc
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card" style="grid-column:1 / -1;text-align:center;padding:48px 24px;color:#888;">
            <div style="font-size:40px;margin-bottom:10px;">📭</div>
            <div style="font-size:16px;font-weight:700;color:var(--ink);margin-bottom:4px;">Chưa có thông báo nào</div>
            <div style="font-size:13px;">Các thông báo mới về khóa học, điểm số và xét duyệt sẽ hiển thị tại đây.</div>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($pagination) && $pagination instanceof Pagination): ?>
    <?= $pagination->render($baseUrl) ?>
<?php endif; ?>

<?php if ($isTeacher): ?>
    <div id="settingsModal" class="ntf-modal-overlay" onclick="if(event.target===this)closeSettingsModal();">
        <div class="ntf-modal">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">
                <h3 style="font-size:18px;font-weight:800;color:var(--ink);">⚙ Cài đặt thông báo Giảng viên</h3>
                <button type="button" onclick="closeSettingsModal()" style="border:0;background:var(--cream);width:30px;height:30px;border-radius:8px;cursor:pointer;font-weight:700;">✕</button>
            </div>

            <form action="index.php?controller=approvalreport&amp;action=saveNotificationSettings" method="POST">
                <label class="ntf-setting-item">
                    <input type="checkbox" name="nhan_thong_bao_bai_nop" value="1" <?= !empty($notifSettings['submission']) ? 'checked' : '' ?>>
                    <div>
                        <div style="font-weight:700;color:var(--ink);">Thông báo bài nộp mới</div>
                        <div style="font-size:12px;color:#666;margin-top:2px;">Nhận thông báo khi học viên nộp bài tập hoặc hoàn thành Quiz.</div>
                    </div>
                </label>

                <label class="ntf-setting-item">
                    <input type="checkbox" name="nhan_thong_bao_duyet" value="1" <?= !empty($notifSettings['approval']) ? 'checked' : '' ?>>
                    <div>
                        <div style="font-weight:700;color:var(--ink);">Kết quả phê duyệt khóa học</div>
                        <div style="font-size:12px;color:#666;margin-top:2px;">Nhận thông báo khi Bộ phận đào tạo duyệt hoặc yêu cầu chỉnh sửa khóa học.</div>
                    </div>
                </label>

                <label class="ntf-setting-item">
                    <input type="checkbox" name="nhan_thong_bao_email" value="1" <?= !empty($notifSettings['email']) ? 'checked' : '' ?>>
                    <div>
                        <div style="font-weight:700;color:var(--ink);">Đồng bộ thông báo qua Email</div>
                        <div style="font-size:12px;color:#666;margin-top:2px;">Gửi bản sao các thông báo quan trọng tới địa chỉ email giảng viên.</div>
                    </div>
                </label>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;padding-top:14px;border-top:1px solid var(--border);">
                    <button type="button" onclick="closeSettingsModal()" class="btn btn-outline">Hủy</button>
                    <button type="submit" class="btn btn-gold">💾 Lưu cài đặt</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openSettingsModal() {
        document.getElementById('settingsModal').style.display = 'flex';
    }
    function closeSettingsModal() {
        document.getElementById('settingsModal').style.display = 'none';
    }
    </script>
<?php endif; ?>