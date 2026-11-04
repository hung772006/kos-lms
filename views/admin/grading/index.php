<div class="container-fluid px-4 mt-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1" style="color: var(--ink);">Chấm điểm & Nhận xét bài nộp</h2>
        <p class="text-muted" style="font-size: 14px;">Quản lý danh sách bài làm, kiểm tra thời gian nộp và chấm điểm cho học viên.</p>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; background: #fff;">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background: #f8f9fa; border-bottom: 2px solid var(--border);">
                    <tr>
                        <th class="py-3 px-3" style="width: 70px;">#ID</th>
                        <th class="py-3">Học viên</th>
                        <th class="py-3">Hoạt động / Bài tập</th>
                        <th class="py-3">File bài làm</th>
                        <th class="py-3">Thời gian nộp</th>
                        <th class="py-3">Trạng thái / Nhãn</th>
                        <th class="py-3">Điểm số</th>
                        <th class="py-3 text-center" style="width: 120px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($submissions)): ?>
                        <?php foreach ($submissions as $sub): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="fw-bold px-3 text-secondary">#<?= $sub['MaBaiNop'] ?></td>
                            <td class="fw-semibold" style="color: var(--ink);"><?= htmlspecialchars($sub['TenHocVien']) ?></td>
                            <td><?= htmlspecialchars($sub['TenHoatDong']) ?></td>
                            <td>
                                <?php if (!empty($sub['FileBaiNop'])): ?>
                                    <a href="<?= $sub['FileBaiNop'] ?>" target="_blank" class="text-decoration-none fw-medium" style="color: #2b6cb0; font-size: 13px;">
                                        📎 Tải file làm
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Nộp qua văn bản</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #4a5568;"><?= htmlspecialchars($sub['ThoiGianNop']) ?></span>
                            </td>
                            <td>
                                <!-- Tính năng tự động gắn nhãn Muộn -->
                                <?php if ($sub['isLate']): ?>
                                    <span class="badge px-2.5 py-1.5" style="background-color: #fff5f5; color: #c53030; font-weight: 600; border-radius: 6px;">⏰ Nộp muộn</span>
                                <?php else: ?>
                                    <span class="badge px-2.5 py-1.5" style="background-color: #d4edda; color: #155724; font-weight: 600; border-radius: 6px;">✅ Đúng hạn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold" style="color: var(--ink);"><?= isset($sub['SoDiem']) ? $sub['SoDiem'] . '/10' : 'Chưa chấm' ?></span>
                            </td>
                            <td class="text-center">
                                <a href="index.php?controller=adminGrading&action=grade&id=<?= $sub['MaBaiNop'] ?>" class="btn btn-sm px-3 py-1.5" style="background: var(--gold); color: var(--ink); border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none;">
                                    ✍️ Chấm điểm
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Chưa có bài nộp nào từ học viên.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>