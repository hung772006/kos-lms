<div class="container-fluid px-4 mt-4 mb-5">
    <!-- KHUNG LỚN BỌC TOÀN BỘ TRANG -->
    <div class="card border-0 shadow-sm" style="border-radius: 20px; background: #fff; padding: 40px;">
        
        <!-- TIÊU ĐỀ TRANG VÀ DÒNG MÔ TẢ -->
        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 30px;">
            <h2 style="color: var(--ink); font-weight: 700; font-size: 24px; margin-bottom: 6px;">Chỉnh sửa hoạt động học tập</h2>
            <p style="color: #6c757d; font-size: 14px; margin: 0;">Cập nhật thông tin bài học, tài liệu hoặc bài tập cho khóa học.</p>
        </div>

        <form action="index.php?controller=adminLearning&action=edit&id=<?= $activity['MaHoatDong'] ?>" method="POST" enctype="multipart/form-data">
            
            <!-- BỐ CỤC 2 CỘT CHÍNH -->
            <div style="display: flex; gap: 40px; align-items: flex-start;">
                
                <!-- CỘT TRÁI: FORM NHẬP LIỆU -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                    
                    <!-- 1. Thuộc chương học -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Thuộc chương học:</label>
                        <select name="MaChuong" required style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                            <option value="">-- Chọn chương học --</option>
                            <?php if (!empty($chapters)): ?>
                                <?php foreach ($chapters as $chap): ?>
                                    <option value="<?= $chap['MaChuong'] ?>" <?= ($activity['MaChuong'] == $chap['MaChuong']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($chap['TenChuong']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- 2. Tên hoạt động -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Tên hoạt động:</label>
                        <input type="text" name="TenHoatDong" required value="<?= htmlspecialchars($activity['TenHoatDong']) ?>" placeholder="Nhập tên hoạt động..." style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <!-- 3. Loại hoạt động -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Loại hoạt động:</label>
                        <select name="Loai" required style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                            <option value="video" <?= ($activity['Loai'] == 'video') ? 'selected' : '' ?>>Bài giảng Video/Slide (video)</option>
                            <option value="assignment" <?= ($activity['Loai'] == 'assignment') ? 'selected' : '' ?>>Bài tập tự luận (assignment)</option>
                            <option value="quiz" <?= ($activity['Loai'] == 'quiz') ? 'selected' : '' ?>>Bài kiểm tra trắc nghiệm (quiz)</option>
                        </select>
                    </div>

                    <!-- 4. Link Video -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Link Video (nếu là loại video):</label>
                        <input type="text" name="LinkVideo" value="<?= htmlspecialchars($activity['LinkVideo'] ?? '') ?>" placeholder="https://www.youtube.com/..." style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <!-- 5. File đính kèm -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">File đính kèm hiện tại:</label>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                            <?php if (!empty($activity['FileDinhKem'])): ?>
                                <span style="font-size: 13px; color: #2b6cb0;">📎 Đang có file: <a href="<?= $activity['FileDinhKem'] ?>" target="_blank">Xem file cũ</a></span>
                            <?php else: ?>
                                <span style="font-size: 13px; color: #718096;">Chưa có file đính kèm nào.</span>
                            <?php endif; ?>
                            <input type="file" name="FileDinhKem" style="font-size: 13px; color: #4a5568; padding: 6px; border: 2px dashed #cbd5e0; border-radius: 12px; background-color: #faf8f3;">
                        </div>
                    </div>

                    <!-- 6. Hạn nộp -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Hạn nộp (Dành cho bài tập):</label>
                        <input type="date" name="HanNop" value="<?= htmlspecialchars($activity['HanNop'] ?? '') ?>" style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <!-- HÀNG NÚT BẤM -->
                    <div style="display: flex; gap: 16px; margin-top: 15px; padding-top: 20px;">
                        <button type="submit" style="background: var(--gold); color: var(--ink); font-weight: 700; padding: 12px 32px; border-radius: 12px; border: none; font-size: 14px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            💾 Cập nhật hoạt động
                        </button>
                        <a href="index.php?controller=adminLearning&action=index" style="background: #fff; color: #4a5568; font-weight: 600; padding: 12px 28px; border-radius: 12px; border: 1.5px solid #e2e8f0; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center;">
                            ↪️ Quay lại
                        </a>
                    </div>

                </div>

                <!-- CỘT PHẢI: HÌNH MINH HỌC TẬP -->
                <div style="width: 320px; background: #faf8f3; border-radius: 20px; border: 1px dashed #e2d9c2; padding: 40px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="font-size: 80px; margin-bottom: 15px; line-height: 1;">📝</div>
                    <h5 style="font-weight: 700; color: var(--ink); margin-bottom: 10px; font-size: 16px;">Edit Learning Item</h5>
                    <p style="color: #718096; font-size: 13px; margin: 0; line-height: 1.5;">Chỉnh sửa nội dung học tập để cập nhật thông tin mới nhất cho học viên.</p>
                </div>

            </div>

        </form>
    </div>
</div>