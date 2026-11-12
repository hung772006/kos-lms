<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');

$resolveSubmissionFile = static function (?string $path): string {
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

$maxScore = (float) ($maxScore ?? ($submission['DiemToiDa'] ?? 10.0));
$fileUrl  = $resolveSubmissionFile($submission['FileBaiNop'] ?? null);
?>

<style>
    .grade-wrap { max-width:820px; margin:0 auto; }
    .grade-header { margin-bottom:20px; }
    .grade-header h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .grade-header p { font-size:13px; color:#666; }
    .grade-card { background:#fff; border:1px solid var(--border); border-radius:18px; padding:28px 32px; box-shadow:var(--shadow); }
    .sub-info-box { background:var(--cream); border:1px solid var(--border); border-radius:14px; padding:18px 20px; margin-bottom:22px; display:grid; gap:10px; font-size:13px; }
    .sub-info-row { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .sub-answer-box { background:#fff; border:1px dashed var(--border); border-radius:10px; padding:12px 14px; margin-top:4px; line-height:1.6; color:#333; white-space:pre-wrap; }
    .grade-field { margin-bottom:18px; }
    .grade-field label { display:block; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#555; margin-bottom:8px; }
    .grade-field input, .grade-field textarea { width:100%; padding:12px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; font-size:14px; background:var(--cream); color:var(--ink); outline:none; }
    .grade-field input:focus, .grade-field textarea:focus { border-color:var(--gold); background:#fff; }
    .grade-actions { display:flex; gap:12px; padding-top:18px; border-top:1px solid var(--border); margin-top:22px; flex-wrap:wrap; }
    .pill-late { display:inline-block; padding:3px 9px; border-radius:6px; font-size:11px; font-weight:700; background:#fde8e8; color:var(--red); }
    .pill-ontime { display:inline-block; padding:3px 9px; border-radius:6px; font-size:11px; font-weight:700; background:#dff0d8; color:var(--green); }
</style>

<div class="grade-wrap">
    <div class="grade-header">
        <h2>Chấm bài &amp; Nhận xét học viên</h2>
        <p>Xem nội dung bài nộp, tải file bài làm đính kèm và nhập điểm đánh giá cho học viên.</p>
    </div>

    <div class="grade-card">
        <div class="sub-info-box">
            <div class="sub-info-row">
                <div><strong>Học viên:</strong> <?= $escape($submission['TenHocVien'] ?? '') ?></div>
                <div>
                    <?php if (!empty($submission['isLate'])): ?>
                        <span class="pill-late">⏰ Nộp muộn</span>
                    <?php else: ?>
                        <span class="pill-ontime">✅ Đúng hạn</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($submission['TenKhoaHoc'])): ?>
                <div><strong>Khóa học:</strong> <?= $escape($submission['TenKhoaHoc']) ?><?= !empty($submission['TenChuong']) ? ' · ' . $escape($submission['TenChuong']) : '' ?></div>
            <?php endif; ?>

            <div><strong>Hoạt động / Bài tập:</strong> <?= $escape($submission['TenHoatDong'] ?? '') ?></div>

            <?php if (!empty($submission['DeBai'])): ?>
                <div><strong>Yêu cầu đề bài:</strong> <span style="color:#555;"><?= nl2br($escape($submission['DeBai'])) ?></span></div>
            <?php endif; ?>

            <div class="sub-info-row">
                <div><strong>Thời gian nộp:</strong> <?= $escape($submission['ThoiGianNop'] ?? '') ?></div>
                <?php if (!empty($submission['HanNop'])): ?>
                    <div><strong>Hạn chót:</strong> <?= $escape($submission['HanNop']) ?></div>
                <?php endif; ?>
            </div>

            <div>
                <strong>File bài làm đính kèm:</strong>
                <?php if ($fileUrl !== ''): ?>
                    <a href="<?= $escape($fileUrl) ?>" target="_blank" download style="color:#1d3557;font-weight:700;text-decoration:underline;margin-left:6px;">
                        📎 Tải xuống file bài nộp (<?= $escape(basename($fileUrl)) ?>)
                    </a>
                <?php else: ?>
                    <span style="color:#888;margin-left:6px;">Không có file đính kèm</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($submission['NoiDungBaiLam'])): ?>
                <div>
                    <strong>Nội dung trả lời / Ghi chú của học viên:</strong>
                    <div class="sub-answer-box"><?= $escape($submission['NoiDungBaiLam']) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <form action="index.php?controller=admingrading&amp;action=grade&amp;id=<?= (int) $submission['MaBaiNop'] ?>" method="POST">
            <div class="grade-field">
                <label for="score-input">Điểm số (Thang điểm tối đa: <?= $escape($maxScore) ?>) *</label>
                <input
                    id="score-input"
                    type="number"
                    step="0.1"
                    min="0"
                    max="<?= $escape($maxScore) ?>"
                    name="SoDiem"
                    value="<?= $escape($submission['SoDiem'] ?? '') ?>"
                    required
                    placeholder="Ví dụ: 8.5"
                >
            </div>

            <div class="grade-field">
                <label for="comment-input">Nhận xét &amp; Chữa bài của giáo viên</label>
                <textarea
                    id="comment-input"
                    name="NhanXet"
                    rows="4"
                    placeholder="Nhập nhận xét chi tiết, ưu điểm và phần cần cải thiện cho học viên..."
                ><?= $escape($submission['NhanXet'] ?? '') ?></textarea>
            </div>

            <div class="grade-actions">
                <button type="submit" class="btn btn-gold">💾 Lưu điểm &amp; Nhận xét</button>
                <a href="index.php?controller=admingrading&amp;action=index" class="btn btn-outline">↩ Quay lại danh sách</a>
            </div>
        </form>
    </div>
</div>