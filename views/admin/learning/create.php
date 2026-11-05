<div class="container-fluid px-4 mt-4 mb-5">
    <div class="card border-0 shadow-sm" style="border-radius: 20px; background: #fff; padding: 40px;">
        <div style="border-bottom: 1px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 30px;">
            <h2 style="color: var(--ink); font-weight: 700; font-size: 24px; margin-bottom: 6px;">Thêm hoạt động học tập mới</h2>
            <p style="color: #6c757d; font-size: 14px; margin: 0;">Tạo hoạt động học tập để tăng cường trải nghiệm học tập cho học viên.</p>
        </div>

        <form action="index.php?controller=adminLearning&action=create&course_id=<?= htmlspecialchars($courseId ?? 1) ?>" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="course_id" value="<?= htmlspecialchars($courseId ?? 1) ?>">

            <div style="display: flex; gap: 40px; align-items: flex-start;">
                
                <div style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
                    
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

                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Tên hoạt động:</label>
                        <input type="text" name="TenHoatDong" required placeholder="Nhập tên hoạt động..." style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <div style="display: flex; align-items: center;">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Loại hoạt động:</label>
                        <select name="Loai" id="activity-type-select" required onchange="toggleFields()" style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                            <option value="video">Bài giảng Video/Slide (video)</option>
                            <option value="assignment">Bài tập tự luận (assignment)</option>
                            <option value="quiz">Bài kiểm tra trắc nghiệm (quiz)</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center;" id="f-video">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Link Video:</label>
                        <input type="text" name="LinkVideo" placeholder="https://www.youtube.com/..." style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <div style="display: flex; align-items: center;" id="f-file">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">File đính kèm:</label>
                        <div style="flex: 1; border: 2px dashed #cbd5e0; border-radius: 12px; padding: 10px 16px; background-color: #faf8f3;">
                            <input type="file" name="FileDinhKem" style="font-size: 13px; color: #4a5568;">
                        </div>
                    </div>

                    <div style="display: flex; align-items: center;" id="f-deadline">
                        <label style="width: 220px; font-weight: 700; color: var(--ink); font-size: 14px; margin: 0;">Hạn nộp:</label>
                        <input type="date" name="HanNop" style="flex: 1; height: 46px; border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 0 16px; background-color: #faf8f3; font-size: 14px; outline: none;">
                    </div>

                    <!-- Khung Quiz chuẩn gọn gàng -->
                    <div id="f-quiz" style="display: none; background: #fffdf5; border: 1.5px solid #e2b714; border-radius: 16px; padding: 20px;">
                        <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 8px;">⚡ Dán danh sách câu hỏi nhanh</h4>
                        <p class="text-muted small mb-2">Mỗi câu bắt đầu bằng "Câu 1:", "Câu 2:" kèm các đáp án A, B, C, D và Đáp án đúng.</p>
                        <textarea id="quick-txt" class="form-control form-control-sm mb-2" rows="4" placeholder="Dán nội dung vào đây..." style="border-radius: 8px;"></textarea>
                        <button type="button" onclick="parseText()" class="btn btn-sm fw-bold mb-3" style="background: #111; color: #fff; border-radius: 6px;">Tự động tách câu hỏi</button>
                        
                        <div id="q-box"></div>
                        <button type="button" onclick="addQ()" class="btn btn-sm fw-bold mt-2" style="background: #e2b714; border-radius: 6px;">+ Thêm câu hỏi</button>
                        <input type="hidden" name="DuLieuQuizJSON" id="quiz-json">
                    </div>

                    <div style="display: flex; gap: 16px; margin-top: 15px; padding-top: 20px;">
                        <button type="submit" onclick="prepareData()" style="background: var(--gold); color: var(--ink); font-weight: 700; padding: 12px 32px; border-radius: 12px; border: none; cursor: pointer;">
                            💾 Lưu hoạt động mới
                        </button>
                        <a href="index.php?controller=adminLearning&action=index&course_id=<?= htmlspecialchars($courseId ?? 1) ?>" style="background: #fff; color: #4a5568; font-weight: 600; padding: 12px 28px; border-radius: 12px; border: 1.5px solid #e2e8f0; text-decoration: none; display: inline-flex; align-items: center;">
                            ↪️ Quay lại
                        </a>
                    </div>

                </div>

                <div style="width: 320px; background: #faf8f3; border-radius: 20px; border: 1.5px dashed #e2d9c2; padding: 40px 20px; text-align: center;">
                    <div style="font-size: 80px; margin-bottom: 15px;">🎓</div>
                    <h5 style="font-weight: 700; font-size: 16px;">KOS Learning Content</h5>
                    <p style="color: #718096; font-size: 13px; margin: 0;">Hệ thống quản lý nội dung học tập thông minh.</p>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const val = document.getElementById('activity-type-select').value;
    document.getElementById('f-video').style.display = (val === 'video') ? 'flex' : 'none';
    document.getElementById('f-file').style.display = (val !== 'quiz') ? 'flex' : 'none';
    document.getElementById('f-deadline').style.display = 'flex';
    document.getElementById('f-quiz').style.display = (val === 'quiz') ? 'block' : 'none';
    if(val === 'quiz' && document.querySelectorAll('.q-card').length === 0) addQ();
}

function addQ(q = '', opts = ['', '', '', ''], ans = 'A') {
    const box = document.getElementById('q-box');
    const idx = document.querySelectorAll('.q-card').length + 1;
    const div = document.createElement('div');
    div.className = 'q-card card p-3 mb-3 border-0 shadow-sm';
    
    // Tạo cấu trúc card an toàn tuyệt đối, escape dấu nháy để không bị cụt chữ
    div.innerHTML = `
        <div class="d-flex justify-content-between mb-2">
            <strong class="q-title" style="font-size: 13px;">Câu hỏi ${idx}</strong>
            <button type="button" class="btn btn-sm text-danger p-0" onclick="this.closest('.q-card').remove(); updateIdx();" style="font-size: 12px;">Xóa</button>
        </div>
        <input type="text" class="form-control form-control-sm mb-2 q-t" placeholder="Nội dung câu hỏi..." required>
        <div class="row g-2">
            <div class="col-6"><input type="text" class="form-control form-control-sm q-o" placeholder="A" required></div>
            <div class="col-6"><input type="text" class="form-control form-control-sm q-o" placeholder="B" required></div>
            <div class="col-6"><input type="text" class="form-control form-control-sm q-o" placeholder="C" required></div>
            <div class="col-6"><input type="text" class="form-control form-control-sm q-o" placeholder="D" required></div>
        </div>
        <div class="mt-2">
            <label class="small text-muted">Đáp án đúng:</label>
            <select class="form-select form-select-sm q-a" style="width: 100px;">
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
        </div>`;
    
    box.appendChild(div);

    // Gán giá trị trực tiếp bằng thuộc tính .value để tránh triệt để lỗi cụt chuỗi do ký tự đặc biệt (" hoặc :)
    const inputs = div.querySelectorAll('.q-o');
    div.querySelector('.q-t').value = q;
    inputs[0].value = opts[0];
    inputs[1].value = opts[1];
    inputs[2].value = opts[2];
    inputs[3].value = opts[3];
    div.querySelector('.q-a').value = ans;
}

function parseText() {
    let txt = document.getElementById('quick-txt').value.trim();
    if (!txt) { alert('Chưa nhập văn bản!'); return; }
    
    txt = txt.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    let lines = txt.split('\n').map(l => l.trim()).filter(l => l);
    
    let list = [];
    let currentQ = null;
    
    lines.forEach(l => {
        let upperL = l.toUpperCase();
        
        // Nhận diện dòng bắt đầu câu hỏi mới
        if (/^Câu\s*\d+/i.test(l) || /^\d+[\.\)]\s/.test(l)) {
            if (currentQ) {
                list.push(currentQ);
            }
            let qTextClean = l.replace(/^(Câu\s*\d+[\s\:\.]*|\d+[\.\)]\s*)/i, '').trim();
            currentQ = {
                qText: qTextClean,
                opts: ['', '', '', ''],
                ans: 'A'
            };
        } else if (currentQ) {
            let isOptionA = /^A[\.\)\:]\s*/i.test(l);
            let isOptionB = /^B[\.\)\:]\s*/i.test(l);
            let isOptionC = /^C[\.\)\:]\s*/i.test(l);
            let isOptionD = /^D[\.\)\:]\s*/i.test(l);
            
            if (isOptionA) {
                currentQ.opts[0] = l;
            } else if (isOptionB) {
                currentQ.opts[1] = l;
            } else if (isOptionC) {
                currentQ.opts[2] = l;
            } else if (isOptionD) {
                currentQ.opts[3] = l;
            } else if (upperL.startsWith('ĐÁP ÁN') || upperL.startsWith('DUNG') || upperL.startsWith('ANSWER')) {
                let m = l.match(/([A-D])/i);
                if (m) currentQ.ans = m[1].toUpperCase();
            } else {
                // Nối trọn vẹn toàn bộ phần còn lại của câu hỏi (bao gồm cả dấu ngoặc kép, dấu hỏi, chữ child...)
                currentQ.qText += ' ' + l;
            }
        }
    });
    
    if (currentQ) {
        list.push(currentQ);
    }
    
    if (list.length > 0) {
        document.getElementById('q-box').innerHTML = '';
        list.forEach(i => addQ(i.qText, i.opts, i.ans));
        alert('Tách thành công ' + list.length + ' câu hỏi đầy đủ 100% không mất chữ!');
        document.getElementById('quick-txt').value = '';
    } else {
        alert('Không tìm thấy định dạng câu hỏi phù hợp!');
    }
}
function prepareData() {
    if(document.getElementById('activity-type-select').value !== 'quiz') return;
    let cards = document.querySelectorAll('.q-card');
    let data = [];
    cards.forEach(c => {
        data.push({
            question: c.querySelector('.q-t').value,
            options: Array.from(c.querySelectorAll('.q-o')).map(o => o.value),
            answer: c.querySelector('.q-a').value
        });
    });
    document.getElementById('quiz-json').value = JSON.stringify(data);
}

document.addEventListener("DOMContentLoaded", toggleFields);
</script>