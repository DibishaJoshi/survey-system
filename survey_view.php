<?php
require 'config.php';
require 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$id]);
$survey = $stmt->fetch();

if (!$survey) {
    redirect('dashboard.php');
}

// Fetch questions
$questions = [];
if ($survey['type'] === 'custom') {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_index ASC");
    $stmt->execute([$id]);
    $questions = $stmt->fetchAll();
}

// Fetch responses
$stmt = $pdo->prepare("SELECT * FROM responses WHERE survey_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$id]);
$responses = $stmt->fetchAll();
?>

<div class="mb-4">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <a href="dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h1 style="margin-top: 0.5rem;"><?php echo htmlspecialchars($survey['title']); ?> <small style="font-size: 1rem; color: var(--text-muted); font-weight: 400;">Results</small></h1>
        </div>
        
        <div>
            <a href="view_survey.php?id=<?php echo $id; ?>" target="_blank" class="btn" style="background: white; border: 1px solid var(--border); color: var(--text-main); margin-right: 0.5rem;">View Survey</a>
            <?php if ($survey['type'] === 'custom' && count($responses) > 0): ?>
                <a href="actions/export.php?id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-download" style="margin-right: 0.5rem;"></i> Export CSV</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 1.5rem;">Responses (<?php echo count($responses); ?>)</h3>
    
    <?php if ($survey['type'] === 'embed'): ?>
        <?php if (count($responses) === 0): ?>
            <p style="color: var(--text-muted);">No responses recorded yet.</p>
        <?php else: ?>
            <p style="color: var(--text-muted); margin-bottom: 1rem;">
                <i class="fas fa-info-circle"></i> Note: Answers are stored in the external form provider. This list only shows users who confirmed their submission here.
            </p>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border);">
                            <th style="padding: 1rem;">Submitted At</th>
                            <th style="padding: 1rem;">Respondent</th>
                            <th style="padding: 1rem;">Token</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $resp): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1rem;">
                                    <?php echo date('M j, Y H:i', strtotime($resp['submitted_at'])); ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo $resp['respondent_email'] ? htmlspecialchars($resp['respondent_email']) : '<span style="color:var(--text-muted);">Anonymous</span>'; ?>
                                </td>
                                <td style="padding: 1rem; font-family: monospace;">
                                    <?php echo htmlspecialchars(substr($resp['token'], 0, 8)) . '...'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php elseif (count($responses) === 0): ?>
        <p style="color: var(--text-muted);">No responses yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border);">
                        <th style="padding: 1rem; white-space: nowrap;">Submitted At</th>
                        <?php foreach ($questions as $q): ?>
                            <th style="padding: 1rem; min-width: 200px;"><?php echo htmlspecialchars($q['question_text']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $resp): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem; color: var(--text-muted); white-space: nowrap;">
                                <?php echo date('M j, Y H:i', strtotime($resp['submitted_at'])); ?>
                            </td>
                            <?php 
                            // Fetch answers for this response
                            // Use FETCH_KEY_PAIR to get [question_id => answer_text]
                            $stmtA = $pdo->prepare("SELECT question_id, answer_text FROM answers WHERE response_id = ?");
                            $stmtA->execute([$resp['id']]);
                            $answers = $stmtA->fetchAll(PDO::FETCH_KEY_PAIR);
                            ?>
                            
                            <?php foreach ($questions as $q): ?>
                                <td style="padding: 1rem; vertical-align: top;">
                                    <?php echo isset($answers[$q['id']]) ? htmlspecialchars($answers[$q['id']]) : '-'; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
