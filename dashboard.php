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


<!-- Google Forms Style Dashboard -->
</div> <!-- Close container from header.php -->

<!-- Template Header -->
<div class="dashboard-header">
    <div class="container">
        <h3 class="mb-4" style="color: #202124; font-weight: 400; margin-bottom: 1rem;">Start a new form</h3>
        <div class="template-gallery">
            <!-- Blank -->
            <a href="create_survey.php?template=blank" class="template-item" style="text-decoration: none;">
                <div class="template-preview blank">
                    <i class="fas fa-plus" style="font-size: 2rem; color: var(--primary);"></i>
                </div>
                <div class="template-name">Blank</div>
            </a>
            
            <!-- Customer Satisfaction -->
            <a href="create_survey.php?template=customer_satisfaction" class="template-item" style="text-decoration: none;">
                <div class="template-preview customer">
                    <i class="fas fa-smile"></i>
                </div>
                <div class="template-name">Customer Satisfaction</div>
            </a>
            
            <!-- Event Feedback -->
            <a href="create_survey.php?template=event_feedback" class="template-item" style="text-decoration: none;">
                <div class="template-preview event">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="template-name">Event Feedback</div>
            </a>
            
            <!-- Employee Engagement -->
            <a href="create_survey.php?template=employee_engagement" class="template-item" style="text-decoration: none;">
                <div class="template-preview employee">
                    <i class="fas fa-users"></i>
                </div>
                <div class="template-name">Employee Engagement</div>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="color: #202124; font-weight: 400;">Recent forms</h3>
        
        <!-- Search and Sort (Simplified) -->
        <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
             <div style="position: relative;">
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" 
                       style="padding: 0.5rem 0.75rem; border: none; background: #f1f3f4; border-radius: var(--radius); padding-left: 2rem; width: 200px;">
                <i class="fas fa-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            </div>
            <select name="sort" onchange="this.form.submit()" 
                    style="padding: 0.5rem; border: none; background: transparent; cursor: pointer; color: var(--text-muted); font-weight: 500;">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Last opened by me</option>
                <option value="az" <?php echo $sort === 'az' ? 'selected' : ''; ?>>A-Z</option>
            </select>
        </form>
    </div>

    <!-- Forms Grid -->
    <div class="form-grid">
        <?php foreach ($surveys as $survey): ?>
            <div class="form-card">
                <!-- Clickable Area (Goes to Results by default, better for admin) -->
                <a href="survey_view.php?id=<?php echo $survey['id']; ?>" class="form-preview-link" style="text-decoration: none;">
                    <div class="form-preview">
                        <i class="fas fa-file-alt" style="color: #7248b9; opacity: 0.5;"></i>
                    </div>
                </a>
                
                <div class="form-info">
                    <div class="form-title" title="<?php echo htmlspecialchars($survey['title']); ?>">
                        <?php echo htmlspecialchars($survey['title']); ?>
                    </div>
                    <div class="form-meta">
                        <span class="form-icon"></span>
                        <span>Opened <?php echo date('M j, Y', strtotime($survey['created_at'])); ?></span>
                    </div>
                </div>

                <!-- Footer Actions (Visible) -->
                <div class="form-footer-actions">
                    <div class="action-group">
                         <a href="edit_survey.php?id=<?php echo $survey['id']; ?>" class="action-btn" title="Edit Survey"><i class="fas fa-edit"></i></a>
                         <a href="view_survey.php?id=<?php echo $survey['id']; ?>" target="_blank" class="action-btn" title="Preview Form"><i class="fas fa-eye"></i></a>
                         <button onclick="copyLink('<?php echo $survey['hash_id'] ?? ''; ?>', <?php echo $survey['id']; ?>)" class="action-btn" title="Copy Link"><i class="fas fa-share-alt"></i></button>
                         <a href="survey_view.php?id=<?php echo $survey['id']; ?>" class="action-btn" title="View Results"><i class="fas fa-chart-bar"></i></a>
                    </div>

                    <!-- Dropdown for secondary actions -->
                    <div class="dropdown">
                        <button class="action-btn" title="More Options"><i class="fas fa-ellipsis-v"></i></button>
                        <div class="dropdown-content">
                            <a href="#" onclick="copyLink('<?php echo $survey['hash_id'] ?? ''; ?>', <?php echo $survey['id']; ?>); return false;" class="dropdown-item"><i class="fas fa-link" style="margin-right: 8px; width: 16px;"></i> Get pre-filled link</a>
                            <a href="#" onclick="deleteSurvey(<?php echo $survey['id']; ?>); return false;" class="dropdown-item danger"><i class="fas fa-trash-alt" style="margin-right: 8px; width: 16px;"></i> Remove</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($surveys) === 0): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: var(--text-muted);">
                <p>No forms yet. Click a template above to start.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteSurvey(id) {
    if(confirm('Are you sure you want to delete this survey? All responses will be lost.')) {
        window.location.href = 'actions/delete_survey.php?id=' + id;
    }
}

function copyLink(hash, id) {
    // Construct absolute URL
    const path = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
    
    let url;
    if (hash && hash.trim() !== '') {
         // Use new clean format
         url = window.location.origin + path + '/form/' + hash;
    } else {
         // Fallback to legacy ID format
         url = window.location.origin + path + '/view_survey.php?id=' + id;
    }
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Survey link copied to clipboard: ' + url);
        }).catch(err => {
            prompt("Copy this link:", url);
        });
    } else {
        prompt("Copy this link:", url);
    }
}
</script>

</body>
</html>
