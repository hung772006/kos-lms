<?php
$escape = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');

$resolveMaterialFile = static function (?string $path): string {
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

$courseId    = (int) ($courseId ?? ($activity['MaKhoaHoc'] ?? 1));
$actId       = (int) ($activity['MaHoatDong'] ?? 0);
$currentFile = $resolveMaterialFile($activity['FileDinhKem'] ?? null);
$existingQuizJson = $activity['DuLieuQuizJSON'] ?? '[]';
if (json_decode((string) $existingQuizJson, true) === null) {
    $existingQuizJson = '[]';
}
?>

<style>
    .act-card { background:#fff; border:1px solid var(--border); border-radius:20px; padding:32px 36px; box-shadow:var(--shadow); }
    .act-head { border-bottom:1px solid var(--border); padding-bottom:18px; margin-bottom:26px; }
    .act-head h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:4px; }
    .act-head p { font-size:13px; color:#666; }
    .act-layout { display:flex; gap:36px; align-items:flex-start; }
    .act-main { flex:1; display:flex; flex-direction:column; gap:18px; min-width:0; }
    .act-side { width:280px; flex-shrink:0; background:var(--cream); border:1.5px dashed #dfd7c5; border-radius:18px; padding:32px 20px; text-align:center; }
    .act-row { display:flex; align-items:flex-start; gap:16px; }
    .act-row > label { width:190px; flex-shrink:0; font-size:13px; font-weight:700; color:var(--ink); padding-top:11px; }
    .act-control { flex:1; min-width:0; }
    .act-input, .act-select, .act-textarea { width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; background:var(--cream); font-family:inherit; font-size:14px; color:var(--ink); outline:none; }
    .act-input:focus, .act-select:focus, .act-textarea:focus { border-color:var(--gold); background:#fff; }
    .act-textarea { min-height:92px; resize:vertical; }
    .act-file-box { border:2px dashed #cbd5e0; border-radius:12px; padding:12px 14px; background:var(--cream); }
    .quiz-panel { display:none; background:#fffdf5; border:1.5px solid var(--gold); border-radius:16px; padding:20px; }
    .q-card { background:#fff; border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:14px; }
    .q-card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .q-opts-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px; }
    @media (max-width:900px) {
        .act-layout { flex-direction:column; }
        .act-side { width:100%; }
        .act-row { flex-direction:column; gap:6px; }
        .act-row > label { width:100%; padding-top:0; }
        .q-opts-grid { grid-template-columns:1fr; }
    }
</style>

<div class="act-card">
    <div class="act-head">
        <h2>Chỉnh sửa hoạt động học tập</h2>
        <p>Cập nhật thông tin bài học, đề bài tập, học liệu đính kèm hoặc bộ câu hỏi trắc nghiệm Quiz.</p>
    </div>

    <form action="index.php?controller=adminlearning&amp;action=edit&amp;id=<?= $actId ?>&amp;course_id=<?= $courseId ?>" method="POST" enctype="multipart/form-data" onsubmit="return prepareEditData();">
        <input type="hidden" name="course_id" value="<?= $courseId ?>">

        <div class="act-layout">
            <div class="act-main">
                <div class="act-row">
                    <label for="chap-select">Thuộc chương học *</label>
                    <div class="act-control">
                        <select id="chap-select" name="MaChuong" class="act-select" required>
                            <option value="">-- Chọn chương học --</option>
                            <?php if (!empty($chapters)): ?>
                                <?php foreach ($chapters as $chap): ?>
                                    <option value="<?= (int) $chap['MaChuong'] ?>" <?= ((int) $activity['MaChuong'] === (int) $chap['MaChuong']) ? 'selected' : '' ?>>
                                        <?= $escape($chap['TenChuong']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="act-row">
                    <label for="act-name">Tên hoạt động *</label>
                    <div class="act-control">
                        <input id="act-name" type="text" name="TenHoatDong" class="act-input" required maxlength="255" value="<?= $escape($activity['TenHoatDong'] ?? '') ?>">
                    </div>
                </div>

                <div class="act-row">
                    <label for="activity-type-select">Loại hoạt động *</label>
                    <div class="act-control">
                        <select name="Loai" id="activity-type-select" class="act-select" required onchange="toggleEditFields()">
                            <option value="video" <?= (($activity['Loai'] ?? '') === 'video') ? 'selected' : '' ?>>Bài giảng Video / Slide &amp; Tài liệu (video)</option>
                            <option value="assignment" <?= (($activity['Loai'] ?? '') === 'assignment') ? 'selected' : '' ?>>Bài tập tự luận nộp file (assignment)</option>
                            <option value="quiz" <?= (($activity['Loai'] ?? '') === 'quiz') ? 'selected' : '' ?>>Bài kiểm tra trắc nghiệm tự chấm (quiz)</option>
                        </select>
                    </div>
                </div>

                <div class="act-row">
                    <label for="act-content">Mô tả / Đề bài</label>
                    <div class="act-control">
                        <textarea id="act-content" name="NoiDung" class="act-textarea" placeholder="Nhập nội dung bài học hoặc đề bài tập..."><?= $escape($activity['NoiDung'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="act-row" id="f-video">
                    <label for="act-video">Link Video / Slide</label>
                    <div class="act-control">
                        <input id="act-video" type="text" name="LinkVideo" class="act-input" value="<?= $escape($activity['LinkVideo'] ?? '') ?>" placeholder="https://www.youtube.com/embed/...">
                    </div>
                </div>

                <div class="act-row" id="f-file">
                    <label for="act-file">File học liệu đính kèm</label>
                    <div class="act-control">
                        <div class="act-file-box">
                            <?php if ($currentFile !== ''): ?>
                                <div style="font-size:13px;margin-bottom:8px;">
                                    📎 Đang đính kèm:
                                    <a href="<?= $escape($currentFile) ?>" target="_blank" download style="color:#1d3557;font-weight:700;">
                                        <?= $escape(basename($currentFile)) ?>
                                    </a>
                                    <?= !empty($activity['DungLuongFile']) ? ' (' . $escape($activity['DungLuongFile']) . ')' : '' ?>
                                </div>
                            <?php else: ?>
                                <div style="font-size:12px;color:#777;margin-bottom:8px;">Chưa có file đính kèm (bỏ trống nếu không thay đổi).</div>
                            <?php endif; ?>
                            <input id="act-file" type="file" name="FileDinhKem" accept=".pdf,.zip,.pptx,.ppt,.doc,.docx" style="font-size:13px;">
                        </div>
                    </div>
                </div>

                <div class="act-row" id="f-deadline">
                    <label for="act-deadline">Hạn nộp bài</label>
                    <div class="act-control">
                        <input id="act-deadline" type="date" name="HanNop" class="act-input" value="<?= $escape($activity['HanNop'] ?? '') ?>">
                    </div>
                </div>

                <div class="act-row">
                    <label>Cấu hình điểm &amp; Thứ tự</label>
                    <div class="act-control" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                        <div>
                            <div style="font-size:11px;color:#666;margin-bottom:4px;font-weight:600;">Điểm tối đa</div>
                            <input type="number" step="0.5" min="0" max="100" name="DiemToiDa" value="<?= $escape($activity['DiemToiDa'] ?? 10) ?>" class="act-input" required>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#666;margin-bottom:4px;font-weight:600;">Thứ tự hiển thị</div>
                            <input type="number" min="1" name="ThuTu" value="<?= (int) ($activity['ThuTu'] ?? 1) ?>" class="act-input" required>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#666;margin-bottom:4px;font-weight:600;">Trạng thái</div>
                            <select name="TrangThai" class="act-select">
                                <option value="published" <?= (($activity['TrangThai'] ?? 'published') === 'published') ? 'selected' : '' ?>>Công bố</option>
                                <option value="draft" <?= (($activity['TrangThai'] ?? '') === 'draft') ? 'selected' : '' ?>>Bản nháp</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="f-quiz" class="quiz-panel">
                    <h4 style="font-size:15px;font-weight:800;margin-bottom:10px;">⚡ Danh sách câu hỏi Trắc nghiệm (Quiz)</h4>
                    <div id="q-box"></div>
                    <button type="button" onclick="addEditQ()" class="btn btn-gold btn-sm">+ Thêm câu hỏi</button>
                    <input type="hidden" name="DuLieuQuizJSON" id="quiz-json">
                </div>

                <div style="display:flex;gap:12px;padding-top:16px;border-top:1px solid var(--border);margin-top:6px;">
                    <button type="submit" class="btn btn-gold">💾 Cập nhật hoạt động</button>
                    <a href="index.php?controller=adminlearning&amp;action=index&amp;course_id=<?= $courseId ?>" class="btn btn-outline">↩ Quay lại</a>
                </div>
            </div>

            <div class="act-side">
                <div style="font-size:64px;margin-bottom:12px;">📝</div>
                <h5 style="font-weight:800;font-size:16px;margin-bottom:6px;">Chỉnh sửa Học liệu</h5>
                <p style="color:#666;font-size:13px;line-height:1.6;">Mọi cập nhật về tài liệu, hạn nộp và câu hỏi sẽ được đồng bộ tức thì tới học viên.</p>
            </div>
        </div>
    </form>
</div>

<script>
var initialQuizData = <?= $existingQuizJson ?>;

function updateEditIdx() {
    document.querySelectorAll('#q-box .q-card').forEach(function(card, i) {
        var title = card.querySelector('.q-title');
        if (title) title.textContent = 'Câu hỏi ' + (i + 1);
    });
}

function normalizeAnswerLetter(ans) {
    if (ans === 0 || ans === '0') return 'A';
    if (ans === 1 || ans === '1') return 'B';
    if (ans === 2 || ans === '2') return 'C';
    if (ans === 3 || ans === '3') return 'D';
    var upper = String(ans || 'A').toUpperCase();
    return ['A', 'B', 'C', 'D'].indexOf(upper) !== -1 ? upper : 'A';
}

function addEditQ(q, opts, ans) {
    q = q || '';
    opts = opts || ['', '', '', ''];
    ans = normalizeAnswerLetter(ans);

    var box = document.getElementById('q-box');
    var idx = document.querySelectorAll('#q-box .q-card').length + 1;
    var div = document.createElement('div');
    div.className = 'q-card';
    div.innerHTML =
        '<div class="q-card-head">' +
            '<strong class="q-title" style="font-size:13px;">Câu hỏi ' + idx + '</strong>' +
            '<button type="button" class="btn btn-danger btn-sm" onclick="this.closest(\'.q-card\').remove(); updateEditIdx();">Xóa</button>' +
        '</div>' +
        '<input type="text" class="act-input q-t" placeholder="Nhập nội dung câu hỏi..." style="background:#fff;">' +
        '<div class="q-opts-grid">' +
            '<input type="text" class="act-input q-o" placeholder="Phương án A" style="background:#fff;">' +
            '<input type="text" class="act-input q-o" placeholder="Phương án B" style="background:#fff;">' +
            '<input type="text" class="act-input q-o" placeholder="Phương án C" style="background:#fff;">' +
            '<input type="text" class="act-input q-o" placeholder="Phương án D" style="background:#fff;">' +
        '</div>' +
        '<div style="margin-top:10px;display:flex;align-items:center;gap:10px;">' +
            '<span style="font-size:12px;font-weight:700;color:#555;">Đáp án đúng:</span>' +
            '<select class="act-select q-a" style="width:110px;background:#fff;padding:7px 10px;">' +
                '<option value="A">A</option>' +
                '<option value="B">B</option>' +
                '<option value="C">C</option>' +
                '<option value="D">D</option>' +
            '</select>' +
        '</div>';

    box.appendChild(div);

    var inputs = div.querySelectorAll('.q-o');
    div.querySelector('.q-t').value = q;
    inputs[0].value = opts[0] || '';
    inputs[1].value = opts[1] || '';
    inputs[2].value = opts[2] || '';
    inputs[3].value = opts[3] || '';
    div.querySelector('.q-a').value = ans;

    var isQuiz = (document.getElementById('activity-type-select').value === 'quiz');
    div.querySelectorAll('.q-t, .q-o').forEach(function(inp) {
        inp.required = isQuiz;
        inp.disabled = !isQuiz;
    });
}

function toggleEditFields() {
    var val = document.getElementById('activity-type-select').value;
    var isQuiz = (val === 'quiz');
    document.getElementById('f-video').style.display = (val === 'video') ? 'flex' : 'none';
    document.getElementById('f-file').style.display = (!isQuiz) ? 'flex' : 'none';
    document.getElementById('f-quiz').style.display = isQuiz ? 'block' : 'none';

    if (isQuiz && document.querySelectorAll('#q-box .q-card').length === 0) {
        addEditQ();
    }

    document.querySelectorAll('#f-quiz .q-t, #f-quiz .q-o').forEach(function(inp) {
        inp.required = isQuiz;
        inp.disabled = !isQuiz;
    });
}

function prepareEditData() {
    if (document.getElementById('activity-type-select').value !== 'quiz') {
        document.getElementById('quiz-json').value = '';
        return true;
    }
    var cards = document.querySelectorAll('#q-box .q-card');
    if (cards.length === 0) {
        alert('Vui lòng giữ ít nhất 1 câu hỏi cho bài Quiz!');
        return false;
    }
    var data = [];
    cards.forEach(function(c) {
        data.push({
            question: c.querySelector('.q-t').value.trim(),
            options: Array.from(c.querySelectorAll('.q-o')).map(function(o) { return o.value.trim(); }),
            answer: c.querySelector('.q-a').value
        });
    });
    document.getElementById('quiz-json').value = JSON.stringify(data);
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    if (Array.isArray(initialQuizData) && initialQuizData.length > 0) {
        initialQuizData.forEach(function(item) {
            addEditQ(item.question || '', item.options || ['', '', '', ''], item.answer);
        });
    }
    toggleEditFields();
});
</script>