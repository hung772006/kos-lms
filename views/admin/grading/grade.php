<div class="container-fluid px-4 mt-4 mb-5" style="max-width: 800px;">
    <div class="mb-4">
        <h2 class="fw-bold mb-1" style="color: var(--ink);">Chấm bài & Nhận xét học viên</h2>
        <p class="text-muted" style="font-size: 14px;">Đánh giá bài làm, nhập điểm số và đưa ra nhận xét chi tiết.</p>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 20px; background: #fff; overflow: hidden;">
        <div class="card-body p-4 p-md-5">
            <div class="mb-4 p-3 bg-light rounded-3" style="border: 1px solid var(--border);">
                <p class="mb-1"><strong>Học viên:</strong> <?= htmlspecialchars($submission['TenHocVien']) ?></p>
                <p class="mb-1"><strong>Hoạt động:</strong> <?= htmlspecialchars($submission['TenHoatDong']) ?></p>
                <p class="mb-1"><strong>Thời gian nộp:</strong> <?= htmlspecialchars($submission['ThoiGianNop']) ?></p>
                <p class="mb-0"><strong>File bài làm:</strong> 
                    <?php if (!empty($submission['FileBaiNop'])): ?>
                        <a href="<?= $submission['FileBaiNop'] ?>" target="_blank" class="fw-bold text-primary">Tải xuống file bài nộp của học viên</a>
                    <?php else: ?>
                        Không có file đính kèm.
                    <?php endif; ?>
                </p>
            </div>

            <form action="index.php?controller=adminGrading&action=grade&id=<?= $submission['MaBaiNop'] ?>" method="POST">
                
                <div class="mb-4">
                    <label class="form-label fw-bold mb-2" style="font-size: 14px; color: var(--ink);">Điểm số (Thang điểm 10):</label>
                    <input type="number" step="0.1" min="0" max="10" name="SoDiem" value="<?= $submission['SoDiem'] ?? '' ?>" class="form-control px-3 py-2.5" required style="border-radius: 12px; border: 1.5px solid var(--border); background: #faf8f3; font-size: 14px;" placeholder="Ví dụ: 8.5">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold mb-2" style="font-size: 14px; color: var(--ink);">Nhận xét của giáo viên:</label>
                    <textarea name="NhanXet" rows="4" class="form-control p-3" style="border-radius: 12px; border: 1.5px solid var(--border); background: #faf8f3; font-size: 14px;" placeholder="Nhập nhận xét, góp ý cho bài làm của học viên..."><?= htmlspecialchars($submission['NhanXet'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn px-4 py-2.5 shadow-sm fw-bold text-dark" style="background: var(--gold); border-radius: 12px; font-size: 14px; border: none;">
                        💾 Lưu điểm & Nhận xét
                    </button>
                    <a href="index.php?controller=adminGrading&action=index" class="btn px-4 py-2.5 shadow-sm fw-semibold text-muted bg-white" style="border: 1.5px solid var(--border); border-radius: 12px; font-size: 14px; text-decoration: none;">
                        ↩️ Quay lại
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>