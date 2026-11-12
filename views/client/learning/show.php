<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');

$resolveFileUrl = static function (?string $path): string {
    if ($path === null || trim($path) === '') {
        return '';
    }
    $clean = ltrim(trim($path), '/');
    if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://') || str_starts_with($clean, 'public/')) {
        return $clean;
    }
    if (is_file(__DIR__ . '/../../../public/' . $clean)) {
        return 'public/' . $clean;
    }
    return $clean;
};

$toEmbedYoutube = static function (?string $url): array {
    $raw = trim((string) ($url ?? ''));
    if ($raw === '') {
        return ['embed' => '', 'id' => ''];
    }
    $videoId = '';
    if (str_contains($raw, 'watch?v=')) {
        $parts   = explode('watch?v=', $raw, 2);
        $videoId = explode('&', $parts[1])[0];
        $embed   = 'https://www.youtube.com/embed/' . $videoId;
    } elseif (str_contains($raw, 'youtu.be/')) {
        $parts   = explode('youtu.be/', $raw, 2);
        $videoId = explode('?', $parts[1])[0];
        $embed   = 'https://www.youtube.com/embed/' . $videoId;
    } elseif (str_contains($raw, 'embed/')) {
        $parts   = explode('embed/', $raw, 2);
        $videoId = explode('?', $parts[1])[0];
        $embed   = $raw;
    } else {
        $embed = $raw;
    }
    return ['embed' => $embed, 'id' => $videoId];
};

$courseId       = (int) ($courseId ?? 1);
$progress       = max(0, min(100, (float) ($progress ?? 0)));
$submissionsMap = $submissionsMap ?? [];
$submittedIds   = array_map('intval', $submittedActivityIds ?? []);
?>

<style>
    .learn-banner { background:#fff; border:1px solid var(--border); border-radius:18px; padding:22px 26px; margin-bottom:20px; box-shadow:var(--shadow); display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; }
    .learn-banner-info h2 { font-size:21px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .learn-banner-meta { font-size:13px; color:#666; }
    .learn-progress-wrap { min-width:240px; flex:1; max-width:340px; }
    .learn-progress-top { display:flex; justify-content:space-between; font-size:12px; font-weight:700; margin-bottom:6px; }
    .learn-progress-bar { height:8px; background:var(--border); border-radius:999px; overflow:hidden; }
    .learn-progress-fill { height:100%; background:linear-gradient(90deg, var(--gold), #e8d08a); border-radius:999px; }

    .learn-tabs-bar { background:#fff; border:1px solid var(--border); border-radius:14px; padding:10px 14px; margin-bottom:22px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .learn-tabs-group { display:flex; gap:10px; flex-wrap:wrap; }
    .nav-pag-btn { padding:10px 20px; font-weight:700; font-size:13px; border-radius:10px; background:var(--cream); color:#555; border:1px solid var(--border); cursor:pointer; font-family:inherit; transition:all 0.15s; }
    .nav-pag-btn:hover { color:var(--ink); border-color:#ccc; }
    .nav-pag-btn.active { background:var(--gold); color:var(--ink); border-color:var(--gold); }

    .video-layout { display:flex; gap:24px; align-items:flex-start; }
    .video-player-col { flex:1; min-width:0; }
    .video-list-col { width:360px; flex-shrink:0; }
    .video-box { background:#fff; border:1px solid var(--border); border-radius:16px; padding:18px; box-shadow:var(--shadow); }
    .video-frame-wrap { width:100%; aspect-ratio:16 / 9; background:#0d0d12; border-radius:12px; overflow:hidden; margin-bottom:14px; position:relative; }
    .video-frame-wrap iframe { width:100%; height:100%; border:0; display:block; }
    .yt-list-scroll { max-height:480px; overflow-y:auto; padding-right:4px; }
    .yt-item { display:flex; align-items:center; gap:12px; padding:10px; margin-bottom:10px; background:#fff; border:1.5px solid var(--border); border-radius:12px; cursor:pointer; transition:all 0.15s; }
    .yt-item:hover { border-color:var(--gold); background:#fcfbf7; }
    .yt-item.active-yt-item { background:#fffdf2; border-color:var(--gold); }
    .yt-thumb { width:96px; height:54px; flex-shrink:0; border-radius:8px; overflow:hidden; background:var(--ink); display:flex; align-items:center; justify-content:center; color:var(--gold); font-weight:800; font-size:12px; }
    .yt-thumb img { width:100%; height:100%; object-fit:cover; display:block; }

    .kos-mat-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:24px; box-shadow:var(--shadow); }
    .kos-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
    .kos-table th { text-align:left; padding:12px 14px; background:var(--cream); border-bottom:1.5px solid var(--border); font-size:11px; font-weight:700; text-transform:uppercase; color:#666; }
    .kos-table td { padding:14px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .kos-table tbody tr:last-child td { border-bottom:none; }

    .asg-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:20px; }
    .asg-card { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px; box-shadow:var(--shadow); display:flex; flex-direction:column; gap:12px; }
    .asg-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .asg-result-box { background:var(--cream); border:1px solid var(--border); border-radius:12px; padding:14px; font-size:13px; display:grid; gap:6px; }
    .asg-upload-form { background:var(--cream); border:1px dashed #cbd5e0; border-radius:12px; padding:14px; margin-top:auto; }
    .asg-upload-form input[type="file"] { width:100%; font-size:12px; margin-bottom:10px; }
    .asg-upload-form input[type="text"] { width:100%; padding:8px 11px; border:1px solid var(--border); border-radius:8px; font-family:inherit; font-size:12px; margin-bottom:10px; background:#fff; }

    @media (max-width:960px) {
        .video-layout { flex-direction:column; }
        .video-list-col { width:100%; }
        .asg-grid { grid-template-columns:1fr; }
    }
</style>

<div class="learn-banner">
    <div class="learn-banner-info">
        <h2><?= $escape($course['TenKhoaHoc'] ?? 'Không gian học tập trực tuyến') ?></h2>
        <div class="learn-banner-meta">
            Giảng viên: <strong><?= $escape($course['TenGiaoVien'] ?? 'KOS English') ?></strong>
            <?php if (!empty($course['DanhMuc'])): ?>
                · Danh mục: <span class="badge"><?= $escape($course['DanhMuc']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="learn-progress-wrap">
        <div class="learn-progress-top">
            <span>Tiến độ hoàn thành khóa học</span>
            <span style="color:var(--gold);font-size:14px;"><?= number_format($progress, 0) ?>%</span>
        </div>
        <div class="learn-progress-bar">
            <div class="learn-progress-fill" style="width:<?= $progress ?>%;"></div>
        </div>
    </div>
</div>

<div class="learn-tabs-bar">
    <div class="learn-tabs-group">
        <button class="nav-pag-btn active" id="tab-video" type="button" onclick="switchTab('video-pane', 'tab-video')">
            🎬 Bài học Video (<?= count($videos ?? []) ?>)
        </button>
        <button class="nav-pag-btn" id="tab-material" type="button" onclick="switchTab('material-pane', 'tab-material')">
            📚 Tài liệu &amp; Slide (<?= count($materials ?? []) ?>)
        </button>
        <button class="nav-pag-btn" id="tab-assignment" type="button" onclick="switchTab('assignment-pane', 'tab-assignment')">
            ✍️ Bài tập &amp; Quiz (<?= count($assignments ?? []) ?>)
        </button>
    </div>
    <a href="index.php?controller=dashboard&amp;action=index&amp;view=mine" class="btn btn-outline btn-sm">
        ← Về Khóa học của tôi
    </a>
</div>

<!-- TAB 1: XEM BÀI GIẢNG VIDEO / SLIDE -->
<div class="custom-tab-pane" id="video-pane" style="display:block;">
    <?php
    $firstVideoInfo = ['embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'id' => ''];
    $defaultTitle   = 'Chưa có bài giảng video trong khóa học này';
    $defaultChapter = '';
    $defaultDesc    = '';

    if (!empty($videos)) {
        $firstVid       = $videos[0];
        $defaultTitle   = $firstVid['TenHoatDong'] ?? '';
        $defaultChapter = $firstVid['TenChuong'] ?? '';
        $defaultDesc    = $firstVid['NoiDung'] ?? '';
        $parsedFirst    = $toEmbedYoutube($firstVid['LinkVideo'] ?? '');
        if ($parsedFirst['embed'] !== '') {
            $firstVideoInfo = $parsedFirst;
        }
    }
    ?>
    <div class="video-layout">
        <div class="video-player-col">
            <div class="video-box">
                <div class="video-frame-wrap">
                    <iframe id="main-youtube-player" src="<?= $escape($firstVideoInfo['embed']) ?>" title="KOS Video Player" allowfullscreen></iframe>
                </div>
                <h3 id="main-video-title" style="font-size:18px;font-weight:800;color:var(--ink);margin-bottom:4px;"><?= $escape($defaultTitle) ?></h3>
                <div id="main-video-chapter" style="font-size:12px;color:var(--gold);font-weight:700;margin-bottom:8px;">
                    <?= $defaultChapter !== '' ? 'Chương: ' . $escape($defaultChapter) : '' ?>
                </div>
                <p id="main-video-desc" style="font-size:13px;color:#555;line-height:1.6;"><?= nl2br($escape($defaultDesc)) ?></p>
            </div>
        </div>

        <div class="video-list-col">
            <div class="video-box">
                <h4 style="font-size:15px;font-weight:800;margin-bottom:12px;">Danh sách bài giảng</h4>
                <div class="yt-list-scroll">
                    <?php if (!empty($videos)): ?>
                        <?php foreach ($videos as $index => $vid): ?>
                            <?php
                            $parsed = $toEmbedYoutube($vid['LinkVideo'] ?? '');
                            $vUrl   = $parsed['embed'] !== '' ? $parsed['embed'] : 'https://www.youtube.com/embed/dQw4w9WgXcQ';
                            $thumb  = $parsed['id'] !== '' ? "https://img.youtube.com/vi/{$parsed['id']}/mqdefault.jpg" : '';
                            ?>
                            <div
                                class="yt-item <?= $index === 0 ? 'active-yt-item' : '' ?>"
                                data-url="<?= $escape($vUrl) ?>"
                                data-title="<?= $escape($vid['TenHoatDong'] ?? '') ?>"
                                data-chapter="<?= $escape($vid['TenChuong'] ?? '') ?>"
                                data-desc="<?= $escape($vid['NoiDung'] ?? '') ?>"
                                onclick="playThisVideo(this)"
                            >
                                <div class="yt-thumb">
                                    <?php if ($thumb !== ''): ?>
                                        <img src="<?= $escape($thumb) ?>" alt="Thumb">
                                    <?php else: ?>
                                        <span>▶ VIDEO</span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= $escape($vid['TenHoatDong'] ?? '') ?>
                                    </div>
                                    <div style="font-size:11px;color:#777;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= $escape($vid['TenChuong'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="font-size:13px;color:#888;text-align:center;padding:20px 0;">Chưa có bài giảng video nào.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TAB 2: TẢI TÀI LIỆU & SLIDE (DOWNLOAD FILE CLIENT) -->
<div class="custom-tab-pane" id="material-pane" style="display:none;">
    <div class="kos-mat-card">
        <h3 style="font-size:17px;font-weight:800;color:var(--ink);">📚 Tài liệu học tập &amp; Giáo trình đính kèm</h3>
        <p style="font-size:13px;color:#666;margin-top:4px;">Bấm tải xuống để lưu tài liệu PDF, Slide bài giảng hoặc bộ file nghe ZIP về máy.</p>

        <div style="overflow-x:auto;">
            <table class="kos-table">
                <thead>
                    <tr>
                        <th>Tên học liệu / Bài học</th>
                        <th>Chương học</th>
                        <th>Định dạng</th>
                        <th>Dung lượng</th>
                        <th style="text-align:right;">Tải file</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($materials)): ?>
                        <?php foreach ($materials as $mat): ?>
                            <?php $matUrl = $resolveFileUrl($mat['FileDinhKem'] ?? null); ?>
                            <tr>
                                <td style="font-weight:700;color:var(--ink);"><?= $escape($mat['TenHoatDong'] ?? '') ?></td>
                                <td style="color:#666;"><?= $escape($mat['TenChuong'] ?? '') ?></td>
                                <td><span class="badge" style="background:#e8f0fe;color:#1d3557;"><?= $escape($mat['DinhDangFile'] ?? strtoupper(pathinfo($matUrl, PATHINFO_EXTENSION) ?: 'PDF')) ?></span></td>
                                <td style="color:#666;"><?= $escape($mat['DungLuongFile'] ?? '—') ?></td>
                                <td style="text-align:right;">
                                    <?php if ($matUrl !== ''): ?>
                                        <a href="<?= $escape($matUrl) ?>" target="_blank" download class="btn btn-gold btn-sm">
                                            ⬇ Tải xuống
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding:32px;color:#888;">Chưa có tài liệu đính kèm nào cho khóa học này.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 3: BÀI TẬP TỰ LUẬN (UPLOAD FILE CLIENT) & LÀM QUIZ TỰ CHẤM -->
<div class="custom-tab-pane" id="assignment-pane" style="display:none;">
    <div class="asg-grid">
        <?php if (!empty($assignments)): ?>
            <?php foreach ($assignments as $asg): ?>
                <?php
                $actId     = (int) ($asg['MaHoatDong'] ?? 0);
                $isQuiz    = strtolower((string) ($asg['Loai'] ?? '')) === 'quiz';
                $maxPts    = (isset($asg['DiemToiDa']) && (float) $asg['DiemToiDa'] > 0) ? (float) $asg['DiemToiDa'] : 10.0;
                $subRecord = $submissionsMap[$actId] ?? null;
                $isDone    = $subRecord !== null || in_array($actId, $submittedIds, true);
                $subFile   = $subRecord ? $resolveFileUrl($subRecord['FileBaiNop'] ?? null) : '';
                ?>
                <div class="asg-card">
                    <div class="asg-top">
                        <div>
                            <span class="badge" style="<?= $isQuiz ? 'background:#fef3c7;color:#92400e;' : 'background:#dff0d8;color:var(--green);' ?>margin-bottom:6px;">
                                <?= $isQuiz ? '⚡ Trắc nghiệm Quiz' : '📝 Bài tập tự luận' ?>
                            </span>
                            <h4 style="font-size:16px;font-weight:800;color:var(--ink);"><?= $escape($asg['TenHoatDong'] ?? '') ?></h4>
                            <?php if (!empty($asg['TenChuong'])): ?>
                                <div style="font-size:12px;color:#888;margin-top:2px;"><?= $escape($asg['TenChuong']) ?></div>
                            <?php endif; ?>
                        </div>
                        <span style="font-size:12px;font-weight:700;color:#555;white-space:nowrap;">Thang <?= $escape($maxPts) ?>đ</span>
                    </div>

                    <p style="font-size:13px;color:#555;line-height:1.6;">
                        <?= nl2br($escape($asg['NoiDung'] ?? 'Hoàn thành bài tập theo hướng dẫn và nộp đúng thời hạn.')) ?>
                    </p>

                    <div style="font-size:12px;color:#666;">
                        <strong>📅 Hạn nộp:</strong> <?= !empty($asg['HanNop']) ? $escape($asg['HanNop']) : 'Không giới hạn' ?>
                    </div>

                    <?php if ($isDone && $subRecord !== null): ?>
                        <div class="asg-result-box">
                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                                <?php if (($subRecord['TrangThai'] ?? '') === 'graded' || $subRecord['SoDiem'] !== null): ?>
                                    <span style="color:var(--green);font-weight:700;">✅ Đã chấm điểm</span>
                                    <strong style="font-size:16px;color:var(--green);">Điểm: <?= $escape($subRecord['SoDiem']) ?>/<?= $escape($maxPts) ?></strong>
                                <?php elseif (($subRecord['TrangThai'] ?? '') === 'late'): ?>
                                    <span style="color:var(--red);font-weight:700;">⏰ Đã nộp (Muộn hạn) — Chờ chấm</span>
                                <?php else: ?>
                                    <span style="color:#92400e;font-weight:700;">⏳ Đã nộp bài — Chờ giáo viên chấm</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($subRecord['NhanXet'])): ?>
                                <div style="color:#333;padding-top:4px;border-top:1px dashed var(--border);">
                                    <strong>Nhận xét:</strong> <?= $escape($subRecord['NhanXet']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($subFile !== ''): ?>
                                <div>
                                    <a href="<?= $escape($subFile) ?>" target="_blank" download style="font-size:12px;color:#1d3557;font-weight:700;">
                                        📎 Xem file bài làm đã nộp
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($isQuiz): ?>
                            <a href="index.php?controller=clientlearning&amp;action=doquiz&amp;id=<?= $actId ?>&amp;course_id=<?= $courseId ?>" class="btn btn-outline btn-sm" style="margin-top:auto;">
                                🔄 Làm lại bài Quiz
                            </a>
                        <?php elseif (($subRecord['TrangThai'] ?? '') !== 'graded'): ?>
                            <form action="index.php?controller=clientlearning&amp;action=uploadassignment" method="POST" enctype="multipart/form-data" class="asg-upload-form">
                                <input type="hidden" name="MaHoatDong" value="<?= $actId ?>">
                                <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                <div style="font-size:11px;font-weight:700;color:#555;margin-bottom:6px;">Nộp cập nhật lại file bài làm:</div>
                                <input type="file" name="FileBaiNop" accept=".pdf,.doc,.docx,.zip,.pptx,.txt,.png,.jpg,.jpeg" required>
                                <button type="submit" class="btn btn-outline btn-sm" style="width:100%;">📤 Nộp lại file mới</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!$isQuiz): ?>
                            <form action="index.php?controller=clientlearning&amp;action=uploadassignment" method="POST" enctype="multipart/form-data" class="asg-upload-form">
                                <input type="hidden" name="MaHoatDong" value="<?= $actId ?>">
                                <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                <input type="text" name="NoiDungBaiLam" placeholder="Ghi chú bài nộp cho giáo viên (tùy chọn)...">
                                <input type="file" name="FileBaiNop" accept=".pdf,.doc,.docx,.zip,.pptx,.txt,.png,.jpg,.jpeg" required>
                                <button type="submit" class="btn btn-gold" style="width:100%;">📤 Tải lên &amp; Nộp bài tập</button>
                            </form>
                        <?php else: ?>
                            <a href="index.php?controller=clientlearning&amp;action=doquiz&amp;id=<?= $actId ?>&amp;course_id=<?= $courseId ?>" class="btn btn-gold" style="margin-top:auto;width:100%;">
                                ⚡ Bắt đầu làm bài Quiz
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="grid-column:1 / -1;text-align:center;color:#888;padding:40px;">
                Chưa có bài tập tự luận hoặc bài kiểm tra Quiz nào trong khóa học này.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(paneId, btnId) {
    document.querySelectorAll('.custom-tab-pane').forEach(function(pane) {
        pane.style.display = 'none';
    });
    document.querySelectorAll('.nav-pag-btn').forEach(function(btn) {
        btn.classList.remove('active');
    });
    var targetPane = document.getElementById(paneId);
    if (targetPane) targetPane.style.display = 'block';
    var targetBtn = document.getElementById(btnId);
    if (targetBtn) targetBtn.classList.add('active');
}

function playThisVideo(element) {
    var url     = element.getAttribute('data-url') || '';
    var title   = element.getAttribute('data-title') || '';
    var chapter = element.getAttribute('data-chapter') || '';
    var desc    = element.getAttribute('data-desc') || '';

    var iframe = document.getElementById('main-youtube-player');
    if (iframe && url !== '') iframe.src = url;

    var titleEl   = document.getElementById('main-video-title');
    var chapterEl = document.getElementById('main-video-chapter');
    var descEl    = document.getElementById('main-video-desc');

    if (titleEl) titleEl.textContent = title;
    if (chapterEl) chapterEl.textContent = chapter ? ('Chương: ' + chapter) : '';
    if (descEl) descEl.textContent = desc;

    document.querySelectorAll('.yt-item').forEach(function(item) {
        item.classList.remove('active-yt-item');
    });
    element.classList.add('active-yt-item');
}
</script>