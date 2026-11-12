<div class="main-content" style="padding: 30px; background: #f8f9fa; min-height: 100vh;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 700; color: #0d0d12;">Thông báo</h2>
        
        <!-- Nút Cài đặt thông báo chỉ hiển thị nếu là Giáo viên -->
        <?php if (isset($user) && $user['VaiTro'] === 'GiaoVien'): ?>
            <button onclick="openSettingsModal()" style="background: #fff; border: 1.5px solid #e2ddd6; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-gear"></i> Cài đặt thông báo
            </button>
        <?php endif; ?>
    </div>

    <!-- Danh sách thông báo dạng thẻ Grid 2 cột khớp mẫu giao diện -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $item): ?>
                <div style="background: #fff; border: 1px solid #e2ddd6; border-radius: 12px; padding: 20px; display: flex; gap: 16px; align-items: flex-start; position: relative;">
                    <!-- Icon theo loại thông báo -->
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #f0ede6; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #c9a84c; flex-shrink: 0;">
                        <?php if ($item['LoaiThongBao'] === 'approval'): ?>
                            <i class="fa-solid fa-check-circle"></i>
                        <?php elseif ($item['LoaiThongBao'] === 'deadline'): ?>
                            <i class="fa-solid fa-clock"></i>
                        <?php elseif ($item['LoaiThongBao'] === 'submission'): ?>
                            <i class="fa-solid fa-file-pen"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-bell"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div style="flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                            <h4 style="font-size: 15px; font-weight: 700; color: #0d0d12; margin: 0;"><?= htmlspecialchars($item['TieuDe']) ?></h4>
                            <span style="font-size: 12px; color: #71717a;"><?= date('H:i d/m/Y', strtotime($item['NgayTao'])) ?></span>
                        </div>
                        <p style="font-size: 13px; color: #52525b; margin: 0; line-height: 1.5;"><?= htmlspecialchars($item['NoiDung']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: span 2; text-align: center; color: #71717a; padding: 40px;">Không có thông báo nào.</p>
        <?php endif; ?>
    </div>

    <!-- Render thanh phân trang -->
    <?= $paginationHtml ?? '' ?>
</div>

<!-- Modal Cài đặt thông báo dành cho Giáo viên -->
<div id="settingsModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
    <div style="background: #fff; width: 450px; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 700; margin-bottom: 16px;">Cài đặt thông báo</h3>
        <form action="index.php?controller=thongbao&action=saveSettings" method="POST">
            <div style="margin-bottom: 16px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer;">
                    <input type="checkbox" name="nhan_thong_bao_bai_nop" value="1" checked> Nhận thông báo khi học viên nộp bài tập mới
                </label>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer;">
                    <input type="checkbox" name="nhan_thong_bao_duyet" value="1" checked> Nhận thông báo kết quả phê duyệt khóa học
                </label>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeSettingsModal()" style="padding: 8px 16px; border: 1px solid #e2ddd6; background: #fff; border-radius: 6px; cursor: pointer;">Hủy</button>
                <button type="submit" style="padding: 8px 16px; background: #c9a84c; border: none; font-weight: 600; border-radius: 6px; cursor: pointer; color: #0d0d12;">Lưu cài đặt</button>
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