<div class="container-fluid px-4 pt-3">
    <!-- Thanh điều hướng Tab chuyên nghiệp -->
    <div class="card border-0 shadow-sm p-3 mb-4" style="border-radius: 14px; background: #ffffff;">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <button class="btn nav-pag-btn active" id="tab-video" type="button" onclick="switchTab('video-pane', 'tab-video')">
                Bài học Video
            </button>
            <button class="btn nav-pag-btn" id="tab-material" type="button" onclick="switchTab('material-pane', 'tab-material')">
                Tài liệu & Slide
            </button>
            <button class="btn nav-pag-btn" id="tab-assignment" type="button" onclick="switchTab('assignment-pane', 'tab-assignment')">
                Bài tập & Quiz
            </button>
        </div>
    </div>

    <!-- Nội dung các Tab -->
    <div class="tab-content-custom">
        
        <!-- Tab 1: Giao diện chuẩn YouTube (Dùng Flexbox ép nằm ngang trái - phải cố định) -->
        <div class="custom-tab-pane" id="video-pane" style="display: block;">
            <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
                
                <!-- BÊN TRÁI: Khung phát video chính (chiếm không gian rộng) -->
                <div style="flex: 1; min-width: 320px;">
                    <div class="card border-0 shadow-sm p-3" style="border-radius: 16px; background: #fff;">
                        <div class="ratio ratio-16x9 bg-dark rounded-3 overflow-hidden mb-3">
                            <?php 
                            $defaultVideoUrl = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
                            $defaultTitle = 'Chưa có bài giảng';
                            $defaultChapter = '';
                            if (!empty($videos)) {
                                $firstVid = $videos[0];
                                $defaultTitle = $firstVid['TenHoatDong'];
                                $defaultChapter = $firstVid['TenChuong'] ?? '';
                                $rawLink = $firstVid['LinkVideo'] ?? '';
                                if (strpos($rawLink, 'watch?v=') !== false) {
                                    $defaultVideoUrl = str_replace('watch?v=', 'embed/', $rawLink);
                                } elseif (!empty($rawLink)) {
                                    $defaultVideoUrl = $rawLink;
                                }
                            }
                            ?>
                            <iframe id="main-youtube-player" src="<?= htmlspecialchars($defaultVideoUrl) ?>" title="YouTube video player" allowfullscreen></iframe>
                        </div>
                        <h4 id="main-video-title" class="fw-bold text-dark mb-1" style="font-size: 18px;"><?= htmlspecialchars($defaultTitle) ?></h4>
                        <p id="main-video-chapter" class="text-muted small mb-0">Chương: <?= htmlspecialchars($defaultChapter) ?></p>
                    </div>
                </div>

                <!-- BÊN PHẢI: Danh sách bài học (Nằm đúng bên phải, bo viền từng bài tách biệt) -->
                <div style="width: 380px; min-width: 280px; flex-shrink: 0;">
                    <div class="card border-0 shadow-sm p-3" style="border-radius: 16px; background: #fff;">
                        <h5 class="fw-bold mb-3 text-dark" style="font-size: 16px;">Danh sách bài học</h5>
                        <div style="max-height: 500px; overflow-y: auto; padding-right: 4px;">
                            <?php if (!empty($videos)): ?>
                                <?php foreach ($videos as $index => $vid): ?>
                                    <?php 
                                        $vLink = $vid['LinkVideo'] ?? '';
                                        $vId = '';
                                        if (strpos($vLink, 'watch?v=') !== false) {
                                            $parts = explode('watch?v=', $vLink);
                                            $vId = explode('&', $parts[1])[0];
                                            $vLink = str_replace('watch?v=', 'embed/', $vLink);
                                        } elseif (strpos($vLink, 'embed/') !== false) {
                                            $parts = explode('embed/', $vLink);
                                            $vId = explode('?', $parts[1])[0];
                                        }
                                        $thumb = $vId ? "https://img.youtube.com/vi/{$vId}/mqdefault.jpg" : 'https://via.placeholder.com/160x90?text=KOS';
                                    ?>
                                    <!-- Mỗi bài học là một khung riêng biệt, thumbnail trái, chữ phải -->
                                    <div class="yt-item <?= $index === 0 ? 'active-yt-item' : '' ?>" 
                                         onclick="playThisVideo('<?= htmlspecialchars($vLink) ?>', '<?= htmlspecialchars(addslashes($vid['TenHoatDong'])) ?>', '<?= htmlspecialchars(addslashes($vid['TenChuong'] ?? '')) ?>', this)"
                                         style="display: flex; align-items: center; gap: 12px; padding: 10px; margin-bottom: 12px; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.2s ease;">
                                        
                                        <!-- Thumbnail nhỏ bên trái -->
                                        <div style="width: 100px; height: 56px; flex-shrink: 0; border-radius: 6px; overflow: hidden; background: #000;">
                                            <img src="<?= $thumb ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Thumb">
                                        </div>
                                        
                                        <!-- Tiêu đề và chương nằm bên phải, tuyệt đối không đè ảnh -->
                                        <div style="flex-grow: 1; min-width: 0;">
                                            <h6 style="font-size: 13px; font-weight: bold; color: #1a1a1a; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($vid['TenHoatDong']) ?></h6>
                                            <p style="font-size: 11px; color: #6c757d; margin-bottom: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($vid['TenChuong'] ?? '') ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small text-center py-3 mb-0">Chưa có bài học video nào.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tab 2: Tài liệu & Slide -->
        <div class="custom-tab-pane" id="material-pane" style="display: none;">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 14px; background: #fff;">
                <h5 class="fw-bold mb-3" style="color: #1a1a1a;">Tài liệu học tập & Slide bài giảng</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Tên tài liệu</th>
                                <th>Định dạng</th>
                                <th>Dung lượng</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($materials)): ?>
                                <?php foreach ($materials as $mat): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($mat['TenHoatDong']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($mat['DinhDangFile'] ?? 'PDF') ?></span></td>
                                    <td><?= htmlspecialchars($mat['DungLuongFile'] ?? '2.1 MB') ?></td>
                                    <td class="text-end">
                                        <a href="<?= htmlspecialchars($mat['FileDinhKem'] ?? '#') ?>" download class="btn btn-sm px-3 fw-bold text-dark" style="background: #e2b714; border-radius: 8px; text-decoration: none;">
                                            Tải xuống
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Chưa có tài liệu nào được tải lên.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Bài tập & Quiz -->
        <div class="custom-tab-pane" id="assignment-pane" style="display: none;">
            <div class="row">
                <?php if (!empty($assignments)): ?>
                    <?php foreach ($assignments as $asg): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 14px; background: #fff; border: 1px solid #f1f3f5;">
                            <h5 class="fw-bold mb-2" style="color: #1a1a1a;"><?= htmlspecialchars($asg['TenHoatDong']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($asg['NoiDung'] ?? 'Hoàn thành và nộp bài tập đúng hạn.') ?></p>
                            <p class="mb-3 text-secondary small"><strong>Hạn nộp:</strong> <?= htmlspecialchars($asg['HanNop'] ?? 'Không giới hạn') ?></p>
                            
                            <div class="mt-auto">
                                <?php if (in_array($asg['MaHoatDong'], $submittedActivityIds ?? [])): ?>
                                    <div class="alert alert-success py-2 px-3 text-center mb-0 fw-bold" style="border-radius: 8px; font-size: 14px;">
                                        Đã hoàn thành
                                    </div>
                                <?php else: ?>
                                    <?php if (($asg['Loai'] ?? '') === 'assignment'): ?>
                                        <form action="index.php?controller=clientLearning&action=uploadAssignment" method="POST" enctype="multipart/form-data" class="mt-3">
                                            <input type="hidden" name="MaHoatDong" value="<?= $asg['MaHoatDong'] ?>">
                                            <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                            <div class="mb-2">
                                                <input type="file" name="FileBaiNop" class="form-control form-control-sm" required style="border-radius: 8px;">
                                            </div>
                                            <button type="submit" class="btn btn-sm px-4 fw-bold text-dark w-100" style="background: #e2b714; border-radius: 8px;">
                                                Nộp bài tập
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="index.php?controller=clientLearning&action=doQuiz&id=<?= $asg['MaHoatDong'] ?>&course_id=<?= $courseId ?>" class="btn btn-sm px-4 fw-bold text-dark w-100" style="background: #e2b714; border-radius: 8px; text-decoration: none; display: inline-block; text-align: center; padding: 6px 0;">
                                            Làm bài Quiz ngay
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted text-center py-3">Chưa có bài tập hoặc quiz nào cho khóa học này.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.nav-pag-btn {
    padding: 10px 22px;
    font-weight: 600;
    font-size: 14.5px;
    border-radius: 10px;
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}
.nav-pag-btn:hover {
    background-color: #e9ecef;
    color: #1a1a1a;
}
.nav-pag-btn.active {
    background-color: #e2b714 !important;
    color: #111111 !important;
    border-color: #d1a80d !important;
    box-shadow: 0 4px 12px rgba(226, 183, 20, 0.3);
}

.yt-item:hover {
    background-color: #f8f9fa !important;
    border-color: #d1a80d !important;
}
.yt-item.active-yt-item {
    background-color: #fdf8e2 !important;
    border-color: #e2b714 !important;
    box-shadow: 0 2px 5px rgba(226, 183, 20, 0.2);
}
</style>

<script>
function switchTab(paneId, btnId) {
    document.querySelectorAll('.custom-tab-pane').forEach(pane => {
        pane.style.display = 'none';
    });
    document.querySelectorAll('.nav-pag-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const targetPane = document.getElementById(paneId);
    if (targetPane) targetPane.style.display = 'block';
    const targetBtn = document.getElementById(btnId);
    if (targetBtn) targetBtn.classList.add('active');
}

function playThisVideo(url, title, chapter, element) {
    const iframe = document.getElementById('main-youtube-player');
    if (iframe) iframe.src = url;

    const titleEl = document.getElementById('main-video-title');
    const chapterEl = document.getElementById('main-video-chapter');
    if (titleEl) titleEl.textContent = title;
    if (chapterEl) chapterEl.textContent = 'Chương: ' + chapter;

    document.querySelectorAll('.yt-item').forEach(item => {
        item.classList.remove('active-yt-item');
    });
    element.classList.add('active-yt-item');
}
</script>