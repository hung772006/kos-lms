<div class="container py-4">
    <div class="card border-0 shadow-sm p-4 mx-auto" style="max-width: 800px; border-radius: 16px; background: #fff;">
        <h3 class="fw-bold mb-3" style="color: var(--ink);"><?= htmlspecialchars($quiz['TenHoatDong'] ?? 'Bài kiểm tra Quiz') ?></h3>
        <p class="text-muted mb-4"><?= htmlspecialchars($quiz['NoiDung'] ?? '') ?></p>


        <form action="index.php?controller=clientLearning&action=submitQuiz" method="POST">
            <input type="hidden" name="MaHoatDong" value="<?= $quiz['MaHoatDong'] ?? 0 ?>">
            <!-- Bổ sung thêm input ẩn này để giữ nguyên course_id khi nộp bài xong -->
            <input type="hidden" name="course_id" value="<?= $_GET['course_id'] ?? 1 ?>">
           
            <?php
            $questions = json_decode($quiz['DuLieuQuizJSON'] ?? '[]', true);
            if (!empty($questions)):
                foreach ($questions as $i => $q):
            ?>
                <div class="mb-4 p-3 bg-light rounded-3" style="border-radius: 12px;">
                    <p class="fw-bold mb-2">Câu hỏi <?= $i + 1 ?>: <?= htmlspecialchars($q['question'] ?? '') ?></p>
                    <?php if (isset($q['options']) && is_array($q['options'])): ?>
                        <?php foreach ($q['options'] as $optIndex => $opt): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="answer[<?= $i ?>]" value="<?= $optIndex ?>" id="q<?= $i ?>opt<?= $optIndex ?>" required>
                                <label class="form-check-label" for="q<?= $i ?>opt<?= $optIndex ?>">
                                    <?= htmlspecialchars($opt) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="alert alert-warning text-center">Chưa có dữ liệu câu hỏi trắc nghiệm cho bài này.</div>
            <?php endif; ?>


            <div class="d-flex justify-content-between mt-4">
                <a href="index.php?controller=clientLearning&action=show&course_id=<?= $_GET['course_id'] ?? 1 ?>" class="btn btn-secondary px-4 fw-bold" style="border-radius: 8px;">Quay lại</a>
                <?php if (!empty($questions)): ?>
                    <button type="submit" class="btn px-5 fw-bold text-dark" style="background: var(--gold); border-radius: 8px;">Nộp bài Quiz</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
