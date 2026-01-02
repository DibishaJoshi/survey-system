<?php
require 'config.php';
require 'includes/header.php';

// Fetch stats
$stmt = $pdo->query("SELECT COUNT(*) FROM surveys");
$totalSurveys = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM responses");
$totalResponses = $stmt->fetchColumn();

// Handle Search and Sort
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$query = "SELECT * FROM surveys";
$params = [];

if ($search) {
    $query .= " WHERE title LIKE :search OR description LIKE :search";
    $params[':search'] = "%$search%";
}

switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'az':
        $query .= " ORDER BY title ASC";
        break;
    case 'za':
        $query .= " ORDER BY title DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$surveys = $stmt->fetchAll();
?>

<div class="mb-4" style="margin-bottom: 2rem;">
    <h1>Dashboard</h1>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
        <div class="card" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: white;">
            <h3>Total Surveys</h3>
            <p style="font-size: 2.5rem; font-weight: 700; margin: 0;"><?php echo $totalSurveys; ?></p>
        </div>
        <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
            <h3>Total Responses</h3>
            <p style="font-size: 2.5rem; font-weight: 700; margin: 0;"><?php echo $totalResponses; ?></p>
        </div>
    </div>
</div>

<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">
    <h2>Your Surveys</h2>
    
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <form method="GET" action="" style="display: flex; gap: 0.5rem;">
            <div style="position: relative;">
                <input type="text" name="search" placeholder="Search surveys..." value="<?php echo htmlspecialchars($search); ?>" 
                       style="padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius); padding-left: 2rem;">
                <i class="fas fa-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            </div>
            
            <select name="sort" onchange="this.form.submit()" 
                    style="padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius); background-color: white; cursor: pointer;">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="az" <?php echo $sort === 'az' ? 'selected' : ''; ?>>Title (A-Z)</option>
                <option value="za" <?php echo $sort === 'za' ? 'selected' : ''; ?>>Title (Z-A)</option>
            </select>
        </form>
        
        <a href="create_survey.php" class="btn btn-primary"><i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Create New</a>
    </div>
</div>

<div class="grid">
    <?php foreach ($surveys as $survey): ?>
        <div class="survey-card">
            <div class="survey-header">
                <h3 style="margin: 0; font-size: 1.125rem;"><?php echo htmlspecialchars($survey['title']); ?></h3>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Created: <?php echo date('M j, Y', strtotime($survey['created_at'])); ?></span>
            </div>
            <div class="survey-body">
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                    <?php echo $survey['description'] ? htmlspecialchars(substr($survey['description'], 0, 100)) . '...' : 'No description'; ?>
                </p>
                <div style="margin-top: 1rem;">
                    <span style="background: #e2e8f0; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                        <?php echo $survey['type']; ?>
                    </span>
                </div>
            </div>
            <div class="survey-footer">
                <div>
                    <a href="edit_survey.php?id=<?php echo $survey['id']; ?>" class="btn" style="background: var(--bg-color); color: var(--text-main); border: 1px solid var(--border);"><i class="fas fa-edit"></i> Edit</a>
                    <a href="view_survey.php?id=<?php echo $survey['id']; ?>" target="_blank" class="btn" style="color: var(--primary); padding-left: 0;">Preview</a>
                    <a href="survey_view.php?id=<?php echo $survey['id']; ?>" class="btn" style="color: var(--text-main);">Results</a>
                </div>
                <button onclick="deleteSurvey(<?php echo $survey['id']; ?>)" class="btn" style="color: var(--danger);">Delete</button>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($surveys) === 0): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: var(--text-muted); background: white; border-radius: var(--radius); border: 1px dashed var(--border);">
            <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
            <p>No surveys created yet.</p>
            <a href="create_survey.php" class="btn btn-primary" style="margin-top: 1rem;">Create your first survey</a>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteSurvey(id) {
    if(confirm('Are you sure you want to delete this survey? All responses will be lost.')) {
        window.location.href = 'actions/delete_survey.php?id=' + id;
    }
}

function copyLink(id) {
    // Construct absolute URL
    // Assuming the folder structure is /survery_system/ based on previous context
    // Ideally we would use a JS variable passed from PHP, but this works for the specific setup
    const path = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    const url = window.location.origin + path + '/view_survey.php?id=' + id;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Survey link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
            prompt("Copy this link:", url);
        });
    } else {
        prompt("Copy this link:", url);
    }
}
</script>

</body>
</html>
