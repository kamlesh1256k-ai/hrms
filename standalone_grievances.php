<?php
// Standalone Grievance System (Backup Solution)
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$pdo = new PDO("mysql:host=localhost;dbname=hrms", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create_grievance'])) {
        $stmt = $pdo->prepare("INSERT INTO grievances (user_id, category, title, description, status, is_anonymous, anonymous_token, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $anonymousToken = 'GRV_' . strtoupper(substr(md5(uniqid()), 0, 12));
        $stmt->execute([$_SESSION['user_id'] ?? 1, $_POST['category'], $_POST['title'], $_POST['description'], 'open', $_POST['is_anonymous'] ?? false, $_POST['is_anonymous'] ? $anonymousToken : null]);
        $success = "Grievance submitted successfully!";
    }
    
    if (isset($_POST['add_response'])) {
        $stmt = $pdo->prepare("INSERT INTO grievance_responses (grievance_id, responder_id, message, response_type, created_at, updated_at) VALUES (?, ?, ?, 'hr_response', NOW(), NOW())");
        $stmt->execute([$_POST['grievance_id'], $_SESSION['user_id'] ?? 1, $_POST['message']]);
        
        // Update status to in_progress
        $stmt = $pdo->prepare("UPDATE grievances SET status = 'in_progress' WHERE id = ?");
        $stmt->execute([$_POST['grievance_id']]);
        
        $success = "Response added successfully!";
    }
    
    if (isset($_POST['update_status'])) {
        $stmt = $pdo->prepare("UPDATE grievances SET status = ?, resolved_at = ? WHERE id = ?");
        $resolvedAt = $_POST['status'] === 'resolved' ? 'NOW()' : null;
        $stmt->execute([$_POST['status'], $resolvedAt, $_POST['grievance_id']]);
        $success = "Status updated successfully!";
    }
}

// Get grievances
$grievances = $pdo->query("SELECT g.*, u.name as user_name FROM grievances g LEFT JOIN users u ON g.user_id = u.id ORDER BY g.created_at DESC")->fetchAll();

// Get specific grievance if requested
$grievance = null;
$responses = [];
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT g.*, u.name as user_name FROM grievances g LEFT JOIN users u ON g.user_id = u.id WHERE g.id = ?");
    $stmt->execute([$_GET['id']]);
    $grievance = $stmt->fetch();
    
    if ($grievance) {
        $stmt = $pdo->prepare("SELECT gr.*, u.name as responder_name FROM grievance_responses gr LEFT JOIN users u ON gr.responder_id = u.id WHERE gr.grievance_id = ? ORDER BY gr.created_at ASC");
        $stmt->execute([$_GET['id']]);
        $responses = $stmt->fetchAll();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Grievance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .grievance-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .grievance-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-open { background: #dc3545; color: white; }
        .status-in_progress { background: #ffc107; color: black; }
        .status-resolved { background: #28a745; color: white; }
        .response-item {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .anonymous-badge {
            background: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="standalone_grievances.php">
                            <i class="bi bi-chat-square-text me-2"></i>Grievance Management
                        </a>
                        <div class="navbar-nav">
                            <a class="nav-link" href="standalone_grievances.php">
                                <i class="bi bi-list"></i> All Grievances
                            </a>
                            <a class="nav-link" href="standalone_grievances.php?action=create">
                                <i class="bi bi-plus-circle"></i> Raise Grievance
                            </a>
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['action']) && $_GET['action'] === 'create'): ?>
            <!-- Create Grievance Form -->
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Raise New Grievance</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="create_grievance" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="HR">HR Related</option>
                                        <option value="Salary">Salary & Compensation</option>
                                        <option value="Manager">Manager Related</option>
                                        <option value="Harassment">Harassment</option>
                                        <option value="Work Conditions">Work Conditions</option>
                                        <option value="Policies">Company Policies</option>
                                        <option value="Discrimination">Discrimination</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" name="title" class="form-control" required maxlength="255" placeholder="Brief summary of your grievance">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea name="description" class="form-control" rows="6" required placeholder="Provide detailed information about your grievance..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous">
                                        <label class="form-check-label" for="is_anonymous">
                                            Submit Anonymously
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="standalone_grievances.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-1"></i> Submit Grievance
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif (isset($_GET['id']) && $grievance): ?>
            <!-- Grievance Detail -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><?= htmlspecialchars($grievance['title']) ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span class="badge bg-secondary"><?= htmlspecialchars($grievance['category']) ?></span>
                                <?php if ($grievance['is_anonymous']): ?>
                                    <span class="anonymous-badge ms-2">Anonymous</span>
                                <?php endif; ?>
                                <span class="status-badge status-<?= $grievance['status'] ?> ms-2">
                                    <?= ucfirst(str_replace('_', ' ', $grievance['status'])) ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Description:</strong><br>
                                <?= nl2br(htmlspecialchars($grievance['description'])) ?>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Complainant: <?= $grievance['is_anonymous'] ? 'Anonymous' : htmlspecialchars($grievance['user_name'] ?: 'Unknown') ?></small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small class="text-muted">Created: <?= date('M d, Y H:i', strtotime($grievance['created_at'])) ?></small>
                                </div>
                            </div>
                            
                            <!-- Responses -->
                            <h5 class="mt-4 mb-3">Response History</h5>
                            <?php if (!empty($responses)): ?>
                                <?php foreach ($responses as $response): ?>
                                    <div class="response-item">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong><?= htmlspecialchars($response['responder_name'] ?: 'System') ?></strong>
                                            <small class="text-muted"><?= date('M d, Y H:i', strtotime($response['created_at'])) ?></small>
                                        </div>
                                        <div><?= nl2br(htmlspecialchars($response['message'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-chat-square-text" style="font-size: 3rem;"></i>
                                    <p>No responses yet</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Add Response Form -->
                            <div class="mt-4">
                                <h6>Add Response</h6>
                                <form method="POST">
                                    <input type="hidden" name="add_response" value="1">
                                    <input type="hidden" name="grievance_id" value="<?= $grievance['id'] ?>">
                                    <div class="mb-3">
                                        <textarea name="message" class="form-control" rows="3" required placeholder="Type your response here..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send me-1"></i> Send Response
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Status Update -->
                            <div class="mt-3">
                                <h6>Update Status</h6>
                                <form method="POST">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="grievance_id" value="<?= $grievance['id'] ?>">
                                    <div class="d-flex gap-2">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="open" <?= $grievance['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                            <option value="in_progress" <?= $grievance['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="resolved" <?= $grievance['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                        </select>
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Grievance List -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="bi bi-chat-square-text me-2"></i>All Grievances</h2>
                        <a href="standalone_grievances.php?action=create" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Raise Grievance
                        </a>
                    </div>
                    
                    <?php if (!empty($grievances)): ?>
                        <?php foreach ($grievances as $g): ?>
                            <div class="grievance-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5>
                                            <a href="standalone_grievances.php?id=<?= $g['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($g['title']) ?>
                                            </a>
                                            <?php if ($g['is_anonymous']): ?>
                                                <span class="anonymous-badge ms-2">Anonymous</span>
                                            <?php endif; ?>
                                        </h5>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($g['category']) ?></span>
                                        <span class="status-badge status-<?= $g['status'] ?> ms-2">
                                            <?= ucfirst(str_replace('_', ' ', $g['status'])) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($g['created_at'])) ?></small>
                                </div>
                                
                                <p class="text-muted mb-2">
                                    <?= substr(htmlspecialchars($g['description']), 0, 120) ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i>
                                        <?= $g['is_anonymous'] ? 'Anonymous' : htmlspecialchars($g['user_name'] ?: 'Unknown') ?>
                                    </small>
                                    <a href="standalone_grievances.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text" style="font-size: 4rem; color: #cbd5e1;"></i>
                            <h5 class="mt-3 text-muted">No grievances found</h5>
                            <p class="text-muted">Be the first to raise a grievance.</p>
                            <a href="standalone_grievances.php?action=create" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Raise Your First Grievance
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
