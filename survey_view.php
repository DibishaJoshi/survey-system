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

// Aggregation Logic for Charts
$chartData = [];
$textResponses = [];

if ($survey['type'] === 'custom' && count($responses) > 0) {
    // Initialize structure based on questions
    foreach ($questions as $q) {
        $qId = $q['id'];
        $qType = $q['question_type'];
        
        if (in_array($qType, ['multiple_choice', 'checkbox'])) {
            $options = json_decode($q['options'], true) ?? [];
            $chartData[$qId] = [
                'type' => $qType,
                'labels' => $options,
                'data' => array_fill_keys($options, 0),
                'total' => 0
            ];
        } elseif (in_array($qType, ['short_answer', 'paragraph'])) {
            $textResponses[$qId] = [];
        }
    }

    // Process all responses
    foreach ($responses as $resp) {
        $stmtA = $pdo->prepare("SELECT question_id, answer_text FROM answers WHERE response_id = ?");
        $stmtA->execute([$resp['id']]);
        $answers = $stmtA->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($answers as $qId => $ansText) {
            if (!isset($chartData[$qId]) && !isset($textResponses[$qId])) {
                continue;
            }

            // Handle Chart Data (Multiple Choice / Checkbox)
            if (isset($chartData[$qId])) {
                if ($chartData[$qId]['type'] === 'checkbox') {
                    // Checkbox answers are comma separated
                    $selectedOptions = explode(', ', $ansText);
                    foreach ($selectedOptions as $opt) {
                        $opt = trim($opt);
                        if (isset($chartData[$qId]['data'][$opt])) {
                            $chartData[$qId]['data'][$opt]++;
                            $chartData[$qId]['total']++;
                        }
                    }
                } else {
                    // Multiple Choice
                    $ansText = trim($ansText);
                    if (isset($chartData[$qId]['data'][$ansText])) {
                        $chartData[$qId]['data'][$ansText]++;
                        $chartData[$qId]['total']++;
                    }
                }
            } 
            // Handle Text Data
            elseif (isset($textResponses[$qId])) {
                if (!empty($ansText)) {
                    $textResponses[$qId][] = $ansText;
                }
            }
        }
    }
}
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
                <button onclick="openExportModal()" class="btn btn-primary"><i class="fas fa-download" style="margin-right: 0.5rem;"></i> Export Data</button>
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
        
        <!-- Tab Navigation -->
        <div class="tabs-nav">
            <div class="tab-link active" onclick="switchTab('summary')">Summary</div>
            <div class="tab-link" onclick="switchTab('individual')">Individual</div>
        </div>

        <!-- Summary Tab -->
        <div id="tab-summary" class="tab-content active">
            <div class="chart-container" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem;">
                
                <?php foreach ($questions as $q): ?>
                    <div class="card-google">
                        <h4 class="question-text-google"><?php echo htmlspecialchars($q['question_text']); ?></h4>
                        
                        <?php if (isset($chartData[$q['id']])): ?>
                            <div style="max-height: 400px; display: flex; justify-content: center;">
                                <canvas id="chart_<?php echo $q['id']; ?>" style="max-width: 100%; max-height: 400px;"></canvas>
                            </div>
                        <?php elseif (isset($textResponses[$q['id']])): ?>
                            <div style="max-height: 300px; overflow-y: auto; background: #f8fafc; border-radius: var(--radius); border: 1px solid var(--border); padding: 0.5rem;">
                                <?php if (empty($textResponses[$q['id']])): ?>
                                    <div style="padding: 1rem; color: var(--text-muted); font-style: italic;">No responses yet.</div>
                                <?php else: ?>
                                    <?php foreach ($textResponses[$q['id']] as $text): ?>
                                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); background: white;">
                                            <?php echo htmlspecialchars($text); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 1rem; text-align: right; color: var(--text-muted); font-size: 0.8rem;">
                            <?php 
                                if (isset($chartData[$q['id']])) echo $chartData[$q['id']]['total'] . ' responses';
                                elseif (isset($textResponses[$q['id']])) echo count($textResponses[$q['id']]) . ' responses'; 
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Individual Tab -->
        <div id="tab-individual" class="tab-content">
            <div style="max-width: 100%; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; background: white; border: 1px solid var(--border); border-radius: 8px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border); background: #f8fafc;">
                            <th style="padding: 1rem; white-space: nowrap; font-weight: 600; color: var(--text-muted);">Timestamp</th>
                            <?php foreach ($questions as $q): ?>
                                <th style="padding: 1rem; min-width: 200px; font-weight: 600; color: var(--text-muted);"><?php echo htmlspecialchars($q['question_text']); ?></th>
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
        </div>
    <?php endif; ?>
</div>


<!-- Export Modal -->
<div id="exportModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 500px; padding: 2rem;">
        <h3 style="margin-bottom: 1.5rem;">Export Options</h3>
        
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Option 1: Download All -->
            <div style="border: 1px solid var(--border); padding: 1rem; border-radius: 8px;">
                <h4 style="margin-bottom: 0.5rem;"><i class="fas fa-file-csv" style="color: var(--primary);"></i> Download Full Data</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">Download all responses as a new CSV file.</p>
                <a href="actions/export.php?id=<?php echo $id; ?>" class="btn btn-primary" onclick="closeExportModal()" style="display: inline-block;">Download All</a>
            </div>

            <!-- Option 2: Merge -->
            <div style="border: 1px solid var(--border); padding: 1rem; border-radius: 8px;">
                <h4 style="margin-bottom: 0.5rem;"><i class="fas fa-layer-group" style="color: var(--success);"></i> Update Existing File</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">Upload your last export file. We will append only new responses to it.</p>
                
                <form action="actions/export_merge.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="survey_id" value="<?php echo $id; ?>">
                    <div style="margin-bottom: 1rem;">
                        <input type="file" name="existing_file" accept=".csv" required class="form-control">
                    </div>
                    <button type="submit" class="btn" style="background: var(--success); color: white;">Merge & Download</button>
                </form>
            </div>
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button onclick="closeExportModal()" class="btn" style="color: var(--text-muted);">Close</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function openExportModal() {
        document.getElementById('exportModal').style.display = 'flex';
    }

    function closeExportModal() {
        document.getElementById('exportModal').style.display = 'none';
    }

    // Tab Switching Logic
    function switchTab(tabName) {
        // Update Tabs
        document.querySelectorAll('.tab-link').forEach(el => el.classList.remove('active'));
        const activeLink = document.querySelector(`.tab-link[onclick="switchTab('${tabName}')"]`);
        if(activeLink) activeLink.classList.add('active');

        // Update Content
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tabName).classList.add('active');
    }

    // Prepare Data
    const chartData = <?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    
    // Define some Google Forms-like colors
    const colors = [
        '#4285F4', // Blue
        '#DB4437', // Red
        '#F4B400', // Yellow
        '#0F9D58', // Green
        '#AB47BC', // Purple
        '#00ACC1', // Cyan
        '#FF7043', // Orange
        '#9E9D24', // Lime
        '#5C6BC0', // Indigo
        '#F06292'  // Pink
    ];

    document.addEventListener('DOMContentLoaded', () => {
        for (const [qId, data] of Object.entries(chartData)) {
            const ctx = document.getElementById('chart_' + qId);
            if (!ctx) continue;
            
            const labels = Object.keys(data.data);
            const values = Object.values(data.data);
            
            let chartType = 'pie';
            let backgroundColor = colors;
            
            if (data.type === 'checkbox') {
                chartType = 'bar';
                backgroundColor = '#4285F4'; 
            } else {
                chartType = 'pie'; 
            }
            
            new Chart(ctx, {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes',
                        data: values,
                        backgroundColor: backgroundColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: data.type === 'checkbox' ? 'y' : 'x', 
                    plugins: {
                        legend: {
                            position: data.type === 'checkbox' ? 'none' : 'right',
                        }
                    },
                    scales: data.type === 'checkbox' ? {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    } : {}
                }
            });
        }
    });
</script>
</body>
</html>
