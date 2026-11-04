<div class="container-fluid px-4 mt-4">
    <!-- THANH TÌM KIẾM -->
    <div class="card border-0 shadow-sm p-3 mb-4" style="border-radius: 16px; background: #fff;">
        <form method="GET" action="index.php" style="display: flex; align-items: center; width: 100%; gap: 15px; margin: 0;">
            <input type="hidden" name="controller" value="adminLearning">
            <input type="hidden" name="action" value="index">
            
            <div style="flex: 1; min-width: 0;">
                <input type="text" name="search" class="form-control px-4" placeholder="Nhập tên hoạt động cần tìm kiếm..." value="<?= htmlspecialchars($keyword ?? '') ?>" style="border-radius: 12px; border: 1.5px solid var(--border); font-size: 15px; background: #faf8f3; height: 48px; width: 100%;">
            </div>
            
            <button type="submit" class="btn px-5 text-dark fw-bold shadow-sm" style="background: var(--gold); border-radius: 12px; font-size: 15px; height: 48px; border: none; white-space: nowrap; flex-shrink: 0;">
                Tìm kiếm
            </button>
        </form>
    </div>

    <!-- BẢNG DANH SÁCH & CỤM ĐIỀU HƯỚNG -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden; background: #fff;">
        
        <div style="padding: 16px 24px; background: #fff; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="index.php?controller=adminLearning&action=index" class="btn px-3 py-2 fw-semibold shadow-sm" style="background: #fdf6e2; color: #b78103; border: 1.5px solid #f6e05e; border-radius: 10px; font-size: 14px; text-decoration: none; white-space: nowrap;">
                    📁 Tất cả hoạt động
                </a>
                <span class="btn px-3 py-2 fw-medium bg-white text-muted shadow-sm" style="border: 1px solid var(--border); border-radius: 10px; font-size: 14px; white-space: nowrap;">
                    ✅ Đã giao
                </span>
            </div>

            <a href="index.php?controller=adminLearning&action=create" class="btn px-4 py-2 shadow-sm fw-bold text-dark" style="background: var(--gold); border-radius: 10px; text-decoration: none; font-size: 14px; border: none; white-space: nowrap;">
                ➕ Thêm hoạt động mới
            </a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background: #f8f9fa; border-bottom: 2px solid var(--border);">
                    <tr>
                        <th class="py-3 px-3" style="width: 70px; color: #555; font-weight: 700;">#ID</th>
                        <th class="py-3" style="color: #555; font-weight: 700;">Tên hoạt động</th>
                        <th class="py-3" style="color: #555; font-weight: 700;">Chương học</th>
                        <th class="py-3" style="width: 130px; color: #555; font-weight: 700;">Loại</th>
                        <th class="py-3" style="color: #555; font-weight: 700;">File đính kèm</th>
                        <th class="py-3" style="width: 140px; color: #555; font-weight: 700;">Hạn nộp</th>
                        <th class="py-3 text-center" style="width: 150px; color: #555; font-weight: 700;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $item): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="fw-bold px-3 text-secondary">#<?= $item['MaHoatDong'] ?></td>
                            <td>
                                <div class="fw-semibold" style="color: var(--ink); font-size: 15px;"><?= htmlspecialchars($item['TenHoatDong']) ?></div>
                            </td>
                            <td>
                                <span class="text-muted" style="font-size: 13px;"><?= htmlspecialchars($item['TenChuong'] ?? 'Chưa phân loại') ?></span>
                            </td>
                            <td>
                                <?php 
                                    $badgeBg = '#e2e3e5'; $badgeColor = '#383d41';
                                    if ($item['Loai'] === 'assignment') { $badgeBg = '#d4edda'; $badgeColor = '#155724'; }
                                    elseif ($item['Loai'] === 'video') { $badgeBg = '#d1ecf1'; $badgeColor = '#0c5460'; }
                                    elseif ($item['Loai'] === 'quiz') { $badgeBg = '#fff3cd'; $badgeColor = '#856404'; }
                                ?>
                                <span class="badge px-2.5 py-1.5" style="background-color: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-weight: 600; border-radius: 6px;">
                                    <?= htmlspecialchars($item['Loai']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($item['FileDinhKem'])): ?>
                                    <a href="<?= $item['FileDinhKem'] ?>" target="_blank" class="text-decoration-none fw-medium" style="color: #2b6cb0; font-size: 13px;">
                                        📎 Tải file <span class="text-muted small">(<?= htmlspecialchars($item['DungLuongFile'] ?? 'Xem') ?>)</span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Không có</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #4a5568;">📅 <?= htmlspecialchars($item['HanNop'] ?? 'Không có') ?></span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- Nút Sửa dẫn sang action edit -->
                                    <a href="index.php?controller=adminLearning&action=edit&id=<?= $item['MaHoatDong'] ?>" class="btn btn-sm px-2.5 py-1" style="background: #ebf8ff; color: #2b6cb0; border: 1px solid #bee3f8; border-radius: 6px; font-weight: 500; font-size: 13px; text-decoration: none;">
                                        ✏️ Sửa
                                    </a>
                                    <!-- Nút Xóa -->
                                    <a href="index.php?controller=adminLearning&action=delete&id=<?= $item['MaHoatDong'] ?>" class="btn btn-sm px-2.5 py-1" style="background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; border-radius: 6px; font-weight: 500; font-size: 13px; text-decoration: none;" onclick="return confirm('Bạn có chắc chắn muốn xóa hoạt động này không?')">
                                        🗑️ Xóa
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Không tìm thấy dữ liệu hoạt động nào trong hệ thống.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center border-0">
            <div class="text-muted" style="font-size: 13px;">
                Quản lý hệ thống nội dung học tập chuyên nghiệp — KOS English Center
            </div>
        </div>
    </div>
</div>