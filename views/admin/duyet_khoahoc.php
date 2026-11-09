<div class="card" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 4px; color: var(--ink);">📝 Phê duyệt khóa học</h2>
        <p style="color: #666; font-size: 14px;">Quản lý và xét duyệt các khóa học mới do giáo viên gửi lên hệ thống (<strong style="color: var(--gold);"><?= count($pendingCourses) ?></strong> đang chờ)</p>
    </div>
    
    <!-- THANH TÌM KIẾM RIÊNG CHO PHẦN DUYỆT -->
    <form method="GET" action="index.php" style="display: flex; gap: 8px;">
        <input type="hidden" name="controller" value="dashboard">
        <input type="hidden" name="action" value="approvalcourses">
        <input type="text" name="q" value="<?= htmlspecialchars($keyword ?? '') ?>" placeholder="Tìm tên khóa học, giáo viên..." style="padding: 9px 14px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 13px; width: 240px; outline: none; font-family: 'Lexend', sans-serif;">
        <button type="submit" class="btn" style="background: var(--ink); color: #fff; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none;">Tìm kiếm</button>
    </form>
</div>

<?php if (!empty($pendingCourses)): ?>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;">
        <?php foreach ($pendingCourses as $c): ?>
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; position: relative; border-top: 4px solid var(--gold); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span class="badge" style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">⏳ Chờ duyệt</span>
                        <span style="font-size: 12px; color: #888; font-weight: 500;">📅 Ngày gửi: <?= date('d/m/Y', strtotime($c['NgayTao'])) ?></span>
                    </div>
                    
                    <div style="margin-bottom: 6px;">
                        <span style="display: inline-block; background: #e2ddd6; color: var(--ink); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 6px;"><?= htmlspecialchars($c['DanhMuc'] ?? 'Chung') ?></span>
                    </div>

                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--ink);"><?= htmlspecialchars($c['TenKhoaHoc']) ?></h3>
                    
                    <p style="font-size: 13px; color: #555; margin-bottom: 12px;">👤 Giáo viên: <strong style="color: var(--ink);"><?= htmlspecialchars($c['TenGiaoVien']) ?></strong></p>
                    
                    <p style="font-size: 13px; color: #666; margin-bottom: 16px; line-height: 1.5; background: var(--cream); padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                        <?= htmlspecialchars($c['MoTa'] ?? 'Không có mô tả chi tiết cho khóa học này.') ?>
                    </p>
                </div>

                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-top: 10px; border-top: 1px solid var(--border);">
                        <span style="font-size: 12px; color: #888;">Học phí niêm yết:</span>
                        <strong style="color: var(--green); font-size: 16px;"><?= number_format((float)$c['Gia'], 0, ',', '.') ?> đ</strong>
                    </div>

                    <!-- FORM XỬ LÝ DUYỆT / TỪ CHỐI -->
                    <form method="POST" action="index.php?controller=dashboard&action=handleApproval" style="display: flex; gap: 10px;">
                        <input type="hidden" name="course_id" value="<?= $c['MaKhoaHoc'] ?>">
                        <input type="hidden" name="ly_do" value="Đạt chuẩn chất lượng đào tạo KOS">
                        
                        <button type="submit" name="status" value="rejected" onclick="return confirm('Cậu có chắc chắn muốn từ chối khóa học này không?');" style="flex: 1; background: #f8d7da; color: var(--red); border: 1px solid #f5c6cb; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; text-align: center; font-size: 13px; transition: background 0.2s;">
                            ❌ Không duyệt
                        </button>
                        
                        <button type="submit" name="status" value="approved" onclick="return confirm('Cậu xác nhận phê duyệt khóa học này để hiển thị lên hệ thống chứ?');" style="flex: 1; background: var(--green); color: #fff; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; text-align: center; font-size: 13px; transition: opacity 0.2s;">
                            ✓ Phê duyệt ngay
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 20px;">
        <?= $pagination->render('index.php?controller=dashboard&action=approvalcourses&q=' . urlencode($keyword ?? '')) ?>
    </div>
<?php else: ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
        <div style="font-size: 48px; margin-bottom: 12px;">🎉</div>
        <h3 style="font-size: 18px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">Tuyệt vời, không có khóa học nào đang chờ!</h3>
        <p style="color: #666; font-size: 14px;">Tất cả các khóa học gửi lên hệ thống đã được xử lý xong xuôi.</p>
    </div>
<?php endif; ?>