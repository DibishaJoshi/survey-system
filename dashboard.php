<?php
require 'config.php';
require 'includes/header.php';

// Handle Search, Sort, and Type Filter
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$typeFilter = $_GET['type'] ?? 'all';

$query = "SELECT * FROM surveys WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (title LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($typeFilter !== 'all') {
    $query .= " AND type = :type";
    $params[':type'] = $typeFilter;
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

<style>
* { box-sizing: border-box; }

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.page-title {
    font-size: 2.25rem; /* Large but clean */
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1.125rem;
    color: #6b7280;
    margin-bottom: 3.5rem;
}

.templates-section {
    margin-bottom: 3rem;
}

.templates-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.templates-title {
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    color: #4b5563;
}

.view-all-link {
    font-size: 0.875rem;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.view-all-link:hover {
    color: #5568d3;
}

.templates-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
}

.template-btn {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 2px solid transparent;
    position: relative;
}

.template-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.template-btn:hover::before {
    left: 100%;
}

.template-btn:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    border-color: transparent;
}

.template-preview {
    height: 140px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-size: 3rem;
    color: white;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.template-preview::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.template-btn:nth-child(1) .template-preview {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.template-btn:nth-child(2) .template-preview {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.template-btn:nth-child(3) .template-preview {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.template-btn:nth-child(4) .template-preview {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.template-preview i {
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.template-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(255,255,255,0.95);
    color: #667eea;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    font-size: 0.688rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.template-info {
    padding: 1.25rem;
    background: white;
}

.template-name {
    font-weight: 600;
    color: #111827;
    font-size: 1.125rem;
    margin-bottom: 0.35rem;
}

.template-desc {
    font-size: 0.813rem;
    color: #6b7280;
    line-height: 1.5;
}

.controls-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.section-label {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
}

.controls-right {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.search-input {
    padding: 0.75rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    width: 300px;
    font-size: 1rem;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
}

.filter-btn {
    padding: 0.625rem 1rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
    color: #374151;
    font-weight: 500;
}

.filter-btn:hover {
    border-color: #667eea;
}

.surveys-list {
    display: grid;
    gap: 1rem;
}

.survey-item {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.25rem 1.75rem; /* Clean large padding */
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
}

.survey-item:hover {
    border-color: #667eea;
}

.survey-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.survey-icon {
    width: 52px; /* Balanced icon size */
    height: 52px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.35rem;
}

.survey-details {
    flex: 1;
}

.survey-name {
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.25rem;
    font-size: 1.2rem; /* Clear large title */
}

.survey-date {
    font-size: 1rem; /* Larger date */
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.type-badge {
    padding: 0.25rem 0.625rem;
    border-radius: 4px;
    font-size: 0.688rem;
    font-weight: 600;
    text-transform: uppercase;
}

.type-badge.internal {
    background: #dbeafe;
    color: #1e40af;
}

.type-badge.embed {
    background: #fef3c7;
    color: #92400e;
}

.survey-actions {
    display: flex;
    gap: 0.5rem;
}

.icon-btn {
    width: 40px; /* Functional size */
    height: 40px;
    background: #f1f5f9;
    border: none;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s ease;
    text-decoration: none;
    font-size: 1.1rem;
}

.icon-btn:hover {
    background: #667eea;
    color: white;
}

.icon-btn.danger:hover {
    background: #dc2626;
}

.empty-message {
    text-align: center;
    padding: 4rem 2rem;
    color: #9ca3af;
}

.empty-message i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.4;
}

@media (max-width: 768px) {
    .controls-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .controls-right {
        flex-direction: column;
    }
    
    .search-input {
        width: 100%;
    }
    
    .survey-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .survey-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

</div> <!-- Close container from header.php -->

<div class="dashboard-container">
    <h1 class="page-title">Surveys</h1>
    <p class="page-subtitle">Create and manage your surveys</p>

    <!-- Templates -->
    <div class="templates-section">
        <div class="templates-header">
            <div class="templates-title">Start with a Template</div>
            <a href="templates.php" class="view-all-link"> <!-- Points to new templates page -->
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="templates-row" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));"> <!-- Adjusted for 1.25rem scale -->
            <a href="create_survey.php?template=blank" class="template-btn">
                <div class="template-preview">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div class="template-info">
                    <div class="template-name">Blank Survey</div>
                    <div class="template-desc">Start from scratch with a clean slate</div>
                </div>
            </a>
            <a href="create_survey.php?template=customer_satisfaction" class="template-btn">
                <div class="template-preview">
                   
                    <i class="fa-solid fa-face-smile"></i>
                </div>
                <div class="template-info">
                    <div class="template-name">Customer Feedback</div>
                    <div class="template-desc">Measure satisfaction and loyalty</div>
                </div>
            </a>
            <a href="create_survey.php?template=contact_info" class="template-btn">
                <div class="template-preview">
                    <i class="fa-solid fa-address-book"></i>
                </div>
                <div class="template-info">
                    <div class="template-name">Contact Info</div>
                    <div class="template-desc">Capture lead and contact details</div>
                </div>
            </a>
            <a href="create_survey.php?template=event_feedback" class="template-btn">
                <div class="template-preview">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="template-info">
                    <div class="template-name">Event Survey</div>
                    <div class="template-desc">Gather post-event insights</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Controls -->
    <div class="controls-bar">
        <div class="section-label">All Surveys (<?php echo count($surveys); ?>)</div>
        
        <form method="GET" class="controls-right">
            <input type="text" name="search" class="search-input" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="type" class="filter-btn" onchange="this.form.submit()">
                <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="custom" <?php echo $typeFilter === 'custom' ? 'selected' : ''; ?>>Internal</option>
                <option value="embed" <?php echo $typeFilter === 'embed' ? 'selected' : ''; ?>>Embedded</option>
            </select>
            
            <select name="sort" class="filter-btn" onchange="this.form.submit()">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                <option value="az" <?php echo $sort === 'az' ? 'selected' : ''; ?>>A-Z</option>
            </select>
        </form>
    </div>

    <!-- Surveys List -->
    <div class="surveys-list">
        <?php if (empty($surveys)): ?>
            <div class="empty-message">
                <i class="fas fa-clipboard-list"></i>
                <p>No surveys yet. Create one using the templates above.</p>
            </div>
        <?php
else: ?>
            <?php foreach ($surveys as $survey): ?>
                <div class="survey-item">
                    <div class="survey-info">
                        <div class="survey-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        
                        <div class="survey-details">
                            <div class="survey-name">
                                <?php echo htmlspecialchars($survey['title']); ?>
                            </div>
                            <div class="survey-date">
                                <span><?php echo date('M j, Y', strtotime($survey['created_at'])); ?></span>
                                <span class="type-badge <?php echo $survey['type'] === 'embed' ? 'embed' : 'internal'; ?>">
                                    <?php echo $survey['type'] === 'embed' ? 'Embedded' : 'Internal'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="survey-actions">
                        <?php
        $edit_link = function_exists('base_url') ? base_url('edit_survey.php?id=' . $survey['id']) : 'edit_survey.php?id=' . $survey['id'];
        $view_link = function_exists('base_url') ? base_url('view_survey.php?id=' . $survey['id']) : 'view_survey.php?id=' . $survey['id'];
        $results_link = function_exists('base_url') ? base_url('survey_view.php?id=' . $survey['id']) : 'survey_view.php?id=' . $survey['id'];
?>
                        <a href="<?php echo $edit_link; ?>" class="icon-btn" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="<?php echo $view_link; ?>" class="icon-btn" title="Preview">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <button onclick="copyLink('<?php echo $survey['hash_id'] ?? ''; ?>', <?php echo $survey['id']; ?>)" class="icon-btn" title="Copy Link">
                            <i class="fa-solid fa-link"></i>
                        </button>
                        <a href="<?php echo $results_link; ?>" class="icon-btn" title="Results">
                            <i class="fa-solid fa-chart-column"></i>
                        </a>
                        <button onclick="deleteSurvey(<?php echo $survey['id']; ?>)" class="icon-btn danger" title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            <?php
    endforeach; ?>
        <?php
endif; ?>
    </div>
</div>

<script>
function deleteSurvey(id) {
    if(confirm('Are you sure you want to delete this survey? All responses will be lost.')) {
        window.location.href = '<?php echo base_url("actions/delete_survey.php?id="); ?>' + id;
    }
}

function copyLink(hash, id) {
    // Ensuring we copy the direct, valid link type as requested by the user
    let url = '<?php echo base_url("view_survey.php?id="); ?>' + id;
    
    // Fallback to hash if specifically needed, but user requested the direct ID link
    // if (hash && hash.trim() !== '') {
    //      url = '<?php echo base_url("form/"); ?>' + hash;
    // }
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Link copied to clipboard!');
        }).catch(err => {
            prompt("Copy this link:", url);
        });
    } else {
        prompt("Copy this link:", url);
    }
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: #667eea;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease;
        font-weight: 500;
    `;
    notification.innerHTML = `<i class="fa-solid fa-circle-check" style="margin-right: 0.5rem;"></i>${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 2500);
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(100px); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes slideOut {
        to { opacity: 0; transform: translateX(100px); }
    }
`;
document.head.appendChild(style);
</script>

</body>
</html>