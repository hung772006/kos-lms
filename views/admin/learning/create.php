<div class="container-fluid px-4 mt-4 mb-5">
    <!-- KHUNG LỚN BỌC TOÀN BỘ TRANG -->
    <div class="card border-0 shadow-sm" style="border-radius: 20px; background: #fff; padding: 40px;">
        
        <!-- TIÊU ĐỀ TRANG VÀ DÒNG MÔ TẢ -->
        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 30px;">
            <h2 style="color: var(--ink); font-weight: 700; font-size: 24px; margin-bottom: 6px;">Thêm hoạt động học tập mới</h2>
            <p style="color: #6c757d; font-size: 14px; margin: 0;">Tạo hoạt động học tập để tăng cường trải nghiệm học tập cho học viên.</p>
        </div>

        <form action="index.php?controller=adminLearning&action=create" method="POST" enctype="multipart/form-data">
            
            <!-- BỐ CỤC 2 CỘT CHÍNH (CỘT NỘI DUNG VÀ CỘT HÌNH MINH HỌA) -->
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
                                    <option value="<?= $chap['MaChuong'] ?>"><?= htmlspecialchars($chap['TenChuong']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- 2. Tên hoạt động -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Tên hoạt động:</label>
                        <input type="text" name="TenHoatDong" required placeholder="Nhập tên hoạt động..." style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <!-- 3. Loại hoạt động -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Loại hoạt động:</label>
                        <select name="Loai" required style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                            <option value="video">Bài giảng Video/Slide (video)</option>
                            <option value="assignment">Bài tập tự luận (assignment)</option>
                            <option value="quiz">Bài kiểm tra trắc nghiệm (quiz)</option>
                        </select>
                    </div>

                    <!-- 4. Link Video -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Link Video (nếu là loại video):</label>
                        <input type="text" name="LinkVideo" placeholder="https://www.youtube.com/..." style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <!-- 5. File đính kèm -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">File đính kèm (PDF, ZIP, PPTX):</label>
                        <div style="flex: 1; border: 2px dashed #cbd5e0; border-radius: 12px; padding: 10px 16px; background-color: #faf8f3; display: flex; align-items: center; justify-content: space-between;">
                            <input type="file" name="FileDinhKem" style="font-size: 13px; color: #4a5568;">
                        </div>
                    </div>

                    <!-- 6. Hạn nộp -->
                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Hạn nộp (Dành cho bài tập):</label>
                        <input type="date" name="HanNop" style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <!-- HÀNG NÚT BẤM -->
                    <div style="display: flex; gap: 16px; margin-top: 15px; padding-top: 20px;">
                        <button type="submit" style="background: var(--gold); color: var(--ink); font-weight: 700; padding: 12px 32px; border-radius: 12px; border: none; font-size: 14px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            💾 Lưu hoạt động mới
                        </button>
                        <a href="index.php?controller=adminLearning&action=index" style="background: #fff; color: #4a5568; font-weight: 600; padding: 12px 28px; border-radius: 12px; border: 1.5px solid #e2e8f0; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center;">
                            ↪️ Quay lại
                        </a>
                    </div>

                </div>

                <!-- CỘT PHẢI: HÌNH MINH HỌA HỌC TẬP -->
                <div style="width: 320px; background: #faf8f3; border-radius: 20px; border: 1px dashed #e2d9c2; padding: 40px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="font-size: 80px; margin-bottom: 15px; line-height: 1;">🎓</div>
                    <h5 style="font-weight: 700; color: var(--ink); margin-bottom: 10px; font-size: 16px;">KOS Learning Content</h5>
                    <p style="color: #718096; font-size: 13px; margin: 0; line-height: 1.5;">Hệ thống tạo bài giảng và bài tập chuẩn hóa dành cho học viên.</p>
                </div>

            </div>

        </form>
    </div>
</div>