<?php
$escape   = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$courseId = (int) ($courseId ?? 1);
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
        <h2>Thêm hoạt động học tập mới</h2>
        <p>Tạo bài giảng video, đính kèm tài liệu PDF/ZIP, giao bài tập tự luận hoặc tạo đề kiểm tra trắc nghiệm Quiz.</p>
    </div>

    <form action="index.php?controller=adminlearning&amp;action=create&amp;course_id=<?= $courseId ?>" method="POST" enctype="multipart/form-data" onsubmit="return prepareData();">
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
                                    <option value="<?= (int) $chap['MaChuong'] ?>" <?= ((int) ($chap['MaKhoaHoc'] ?? 0) === $courseId) ? 'selected' : '' ?>>
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
                        <input id="act-name" type="text" name="TenHoatDong" class="act-input" required maxlength="255" placeholder="VD: Bài 1 — Listening Practice / Writing Task 1...">
                    </div>
                </div>

                <div class="act-row">
                    <label for="activity-type-select">Loại hoạt động *</label>
                    <div class="act-control">
                        <select name="Loai" id="activity-type-select" class="act-select" required onchange="toggleFields()">
                            <option value="video">Bài giảng Video / Slide &amp; Tài liệu (video)</option>
                            <option value="assignment">Bài tập tự luận nộp file (assignment)</option>
                            <option value="quiz">Bài kiểm tra trắc nghiệm tự chấm (quiz)</option>
                        </select>
                    </div>
                </div>

                <div class="act-row">
                    <label for="act-content">Mô tả / Đề bài</label>
                    <div class="act-control">
                        <textarea id="act-content" name="NoiDung" class="act-textarea" placeholder="Nhập nội dung tóm tắt bài giảng, yêu cầu đề bài tự luận hoặc hướng dẫn làm bài..."></textarea>
                    </div>
                </div>

                <div class="act-row" id="f-video">
                    <label for="act-video">Link Video / Slide</label>
                    <div class="act-control">
                        <input id="act-video" type="text" name="LinkVideo" class="act-input" placeholder="https://www.youtube.com/embed/...">
                    </div>
                </div>

                <div class="act-row" id="f-file">
                    <label for="act-file">File học liệu đính kèm</label>
                    <div class="act-control">
                        <div class="act-file-box">
                            <input id="act-file" type="file" name="FileDinhKem" accept=".pdf,.zip,.pptx,.ppt,.doc,.docx" style="font-size:13px;">
                            <div style="font-size:11px;color:#777;margin-top:6px;">Hỗ trợ định dạng: PDF, ZIP, PPTX, DOCX (Tối đa 50MB)</div>
                        </div>
                    </div>
                </div>

                <div class="act-row" id="f-deadline">
                    <label for="act-deadline">Hạn nộp bài</label>
                    <div class="act-control">
                        <input id="act-deadline" type="date" name="HanNop" class="act-input">
                    </div>
                </div>

                <div class="act-row">
                    <label>Cấu hình điểm &amp; Thứ tự</label>
                    <div class="act-control" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                        <div>
                            <div style="font-size:11px;color:#666;margin-bottom:4px;font-weight:600;">Điểm tối đa</div>
                            <input type="number" step="0.5" min="0" max="100" name="DiemToiDa" value="10" class="act-input" required>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#666;margin-bottom:4px;font-weight:600;">Thứ tự hiển thị</div>
                            <input type="number" min="1" name="ThuTu" value="1" class="act-input" required>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#666;margin-bottom:4px;font-weight:600;">Trạng thái</div>
                            <select name="TrangThai" class="act-select">
                                <option value="published">Công bố ngay</option>
                                <option value="draft">Lưu bản nháp</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="f-quiz" class="quiz-panel">
                    <h4 style="font-size:15px;font-weight:800;margin-bottom:6px;">⚡ Thiết lập bộ câu hỏi Trắc nghiệm (Quiz)</h4>
                    <p style="font-size:12px;color:#666;margin-bottom:10px;">Có thể dán nhanh văn bản (Câu 1: ... A. ... B. ... C. ... D. ... Đáp án: A) hoặc thêm từng câu bên dưới:</p>
                    <textarea id="quick-txt" class="act-textarea" rows="3" placeholder="Dán nhanh danh sách câu hỏi vào đây (tùy chọn)..." style="background:#fff;margin-bottom:8px;"></textarea>
                    <button type="button" onclick="parseText()" class="btn btn-dark btn-sm" style="margin-bottom:14px;">Tự động tách câu hỏi</button>

                    <div id="q-box"></div>
                    <button type="button" onclick="addQ()" class="btn btn-gold btn-sm">+ Thêm câu hỏi</button>
                    <input type="hidden" name="DuLieuQuizJSON" id="quiz-json">
                </div>

                <div style="display:flex;gap:12px;padding-top:16px;border-top:1px solid var(--border);margin-top:6px;">
                    <button type="submit" class="btn btn-gold">💾 Lưu hoạt động mới</button>
                    <a href="index.php?controller=adminlearning&amp;action=index&amp;course_id=<?= $courseId ?>" class="btn btn-outline">↩ Quay lại</a>
                </div>
            </div>

            <div class="act-side">
                <div style="font-size:64px;margin-bottom:12px;">🎓</div>
                <h5 style="font-weight:800;font-size:16px;margin-bottom:6px;">KOS Learning Content</h5>
                <p style="color:#666;font-size:13px;line-height:1.6;">Xây dựng bài giảng điện tử, tài liệu số và bài kiểm tra tự động chấm điểm cho học viên.</p>
            </div>
        </div>
    </form>
</div>

<script>
function updateIdx() {
    document.querySelectorAll('#q-box .q-card').forEach(function(card, i) {
        var title = card.querySelector('.q-title');
        if (title) title.textContent = 'Câu hỏi ' + (i + 1);
    });
}

function toggleFields() {
    var val = document.getElementById('activity-type-select').value;
    var isQuiz = (val === 'quiz');
    document.getElementById('f-video').style.display = (val === 'video') ? 'flex' : 'none';
    document.getElementById('f-file').style.display = (!isQuiz) ? 'flex' : 'none';
    document.getElementById('f-quiz').style.display = isQuiz ? 'block' : 'none';

    if (isQuiz && document.querySelectorAll('#q-box .q-card').length === 0) {
        addQ();
    }

    // Bật/tắt thuộc tính required để không chặn submit khi ẩn khung Quiz
    document.querySelectorAll('#f-quiz .q-t, #f-quiz .q-o').forEach(function(inp) {
        inp.required = isQuiz;
        inp.disabled = !isQuiz;
    });
}

function addQ(q, opts, ans) {
    q = q || '';
    opts = opts || ['', '', '', ''];
    ans = ans || 'A';

    var box = document.getElementById('q-box');
    var idx = document.querySelectorAll('#q-box .q-card').length + 1;
    var div = document.createElement('div');
    div.className = 'q-card';
    div.innerHTML =
        '<div class="q-card-head">' +
            '<strong class="q-title" style="font-size:13px;">Câu hỏi ' + idx + '</strong>' +
            '<button type="button" class="btn btn-danger btn-sm" onclick="this.closest(\'.q-card\').remove(); updateIdx();">Xóa</button>' +
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

function parseText() {
    var txt = document.getElementById('quick-txt').value.trim();
    if (!txt) { alert('Vui lòng dán nội dung câu hỏi trước khi tách!'); return; }

    var lines = txt.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').map(function(l) { return l.trim(); }).filter(Boolean);
    var list = [];
    var currentQ = null;

    lines.forEach(function(l) {
        var upperL = l.toUpperCase();
        if (/^Câu\s*\d+/i.test(l) || /^\d+[\.\)]\s/.test(l)) {
            if (currentQ) list.push(currentQ);
            currentQ = {
                qText: l.replace(/^(Câu\s*\d+[\s\:\.]*|\d+[\.\)]\s*)/i, '').trim(),
                opts: ['', '', '', ''],
                ans: 'A'
            };
        } else if (currentQ) {
            if (/^A[\.\)\:]\s*/i.test(l)) currentQ.opts[0] = l.replace(/^A[\.\)\:]\s*/i, '').trim();
            else if (/^B[\.\)\:]\s*/i.test(l)) currentQ.opts[1] = l.replace(/^B[\.\)\:]\s*/i, '').trim();
            else if (/^C[\.\)\:]\s*/i.test(l)) currentQ.opts[2] = l.replace(/^C[\.\)\:]\s*/i, '').trim();
            else if (/^D[\.\)\:]\s*/i.test(l)) currentQ.opts[3] = l.replace(/^D[\.\)\:]\s*/i, '').trim();
            else if (upperL.indexOf('ĐÁP ÁN') === 0 || upperL.indexOf('DAP AN') === 0 || upperL.indexOf('ANSWER') === 0) {
                var m = l.match(/([A-D])/i);
                if (m) currentQ.ans = m[1].toUpperCase();
            } else {
                currentQ.qText += ' ' + l;
            }
        }
    });

    if (currentQ) list.push(currentQ);

    if (list.length > 0) {
        document.getElementById('q-box').innerHTML = '';
        list.forEach(function(item) { addQ(item.qText, item.opts, item.ans); });
        document.getElementById('quick-txt').value = '';
    } else {
        alert('Không nhận diện được cấu trúc câu hỏi (Hãy bắt đầu mỗi câu bằng "Câu 1:", "Câu 2:"...).');
    }
}

function prepareData() {
    if (document.getElementById('activity-type-select').value !== 'quiz') {
        document.getElementById('quiz-json').value = '';
        return true;
    }
    var cards = document.querySelectorAll('#q-box .q-card');
    if (cards.length === 0) {
        alert('Vui lòng thêm ít nhất 1 câu hỏi cho bài Quiz!');
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

document.addEventListener('DOMContentLoaded', toggleFields);
</script>