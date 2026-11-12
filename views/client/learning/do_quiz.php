<?php
$escape   = static fn ($val): string => htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
$courseId = (int) ($courseId ?? ($_GET['course_id'] ?? 1));
$quizId   = (int) ($quiz['MaHoatDong'] ?? 0);
$maxScore = (isset($quiz['DiemToiDa']) && (float) $quiz['DiemToiDa'] > 0) ? (float) $quiz['DiemToiDa'] : 10.0;

$decoded   = json_decode((string) ($quiz['DuLieuQuizJSON'] ?? '[]'), true);
$questions = is_array($decoded) ? array_values($decoded) : [];
$letters   = ['A', 'B', 'C', 'D', 'E', 'F'];
?>

<style>
    .quiz-wrap { max-width:820px; margin:0 auto; }
    .quiz-card { background:#fff; border:1px solid var(--border); border-radius:20px; padding:32px 36px; box-shadow:var(--shadow); }
    .quiz-head { border-bottom:1px solid var(--border); padding-bottom:18px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; }
    .quiz-head h2 { font-size:22px; font-weight:800; color:var(--ink); margin-bottom:6px; }
    .quiz-head p { font-size:14px; color:#666; line-height:1.6; }
    .quiz-meta-pills { display:flex; gap:8px; flex-wrap:wrap; }
    .quiz-pill { background:var(--cream); border:1px solid var(--border); padding:6px 12px; border-radius:999px; font-size:12px; font-weight:700; color:var(--ink); }
    .q-block { background:var(--cream); border:1px solid var(--border); border-radius:14px; padding:20px 22px; margin-bottom:18px; }
    .q-title { font-size:15px; font-weight:700; color:var(--ink); margin-bottom:14px; line-height:1.5; }
    .q-options { display:grid; gap:10px; }
    .q-opt-label { display:flex; align-items:center; gap:12px; background:#fff; border:1.5px solid var(--border); border-radius:10px; padding:11px 14px; cursor:pointer; font-size:14px; transition:all 0.15s; }
    .q-opt-label:hover { border-color:var(--gold); background:#fffdf7; }
    .q-opt-label input[type="radio"] { accent-color:var(--ink); width:17px; height:17px; cursor:pointer; flex-shrink:0; }
    .q-opt-badge { width:26px; height:26px; border-radius:7px; background:var(--cream); border:1px solid var(--border); display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:var(--ink); flex-shrink:0; }
    .quiz-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:26px; padding-top:20px; border-top:1px solid var(--border); flex-wrap:wrap; }
    .quiz-empty { background:#fff3cd; color:#856404; border:1px solid #ffeeba; padding:18px; border-radius:12px; text-align:center; font-size:14px; font-weight:600; }
</style>

<div class="quiz-wrap">
    <div class="quiz-card">
        <div class="quiz-head">
            <div style="flex:1;min-width:240px;">
                <h2>⚡ <?= $escape($quiz['TenHoatDong'] ?? 'Bài kiểm tra trắc nghiệm') ?></h2>
                <?php if (!empty($quiz['NoiDung'])): ?>
                    <p><?= nl2br($escape($quiz['NoiDung'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="quiz-meta-pills">
                <span class="quiz-pill">📋 <?= count($questions) ?> câu hỏi</span>
                <span class="quiz-pill">🏆 Thang điểm: <?= $escape($maxScore) ?></span>
            </div>
        </div>

        <form action="index.php?controller=clientlearning&amp;action=submitquiz" method="POST">
            <input type="hidden" name="MaHoatDong" value="<?= $quizId ?>">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">

            <?php if (!empty($questions)): ?>
                <?php foreach ($questions as $i => $q): ?>
                    <div class="q-block">
                        <div class="q-title">Câu <?= $i + 1 ?>: <?= $escape($q['question'] ?? '') ?></div>
                        <?php if (!empty($q['options']) && is_array($q['options'])): ?>
                            <div class="q-options">
                                <?php foreach (array_values($q['options']) as $optIndex => $opt): ?>
                                    <?php $letter = $letters[$optIndex] ?? (string) ($optIndex + 1); ?>
                                    <label class="q-opt-label" for="q<?= $i ?>_opt<?= $optIndex ?>">
                                        <input
                                            type="radio"
                                            name="answer[<?= $i ?>]"
                                            value="<?= (int) $optIndex ?>"
                                            id="q<?= $i ?>_opt<?= $optIndex ?>"
                                            required
                                        >
                                        <span class="q-opt-badge"><?= $escape($letter) ?></span>
                                        <span><?= $escape($opt) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="quiz-empty">Bài kiểm tra này hiện chưa có câu hỏi trắc nghiệm nào.</div>
            <?php endif; ?>

            <div class="quiz-footer">
                <a href="index.php?controller=clientlearning&amp;action=show&amp;course_id=<?= $courseId ?>" class="btn btn-outline">
                    ← Quay lại khóa học
                </a>
                <?php if (!empty($questions)): ?>
                    <button type="submit" class="btn btn-gold" onclick="return confirm('Xác nhận nộp bài kiểm tra Quiz? Hệ thống sẽ chấm điểm ngay lập tức.');">
                        ✅ Nộp bài &amp; Chấm điểm tự động
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>