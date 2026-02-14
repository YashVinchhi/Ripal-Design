<?php
/**
 * Review Requests Management
 * 
 * This page displays all review requests submitted by workers for
 * project phases, materials, or work completion. Supervisors and
 * project managers can approve, reject, or request changes.
 * 
 * @package RipalDesign
 * @subpackage Dashboard
 */

// Initialize session and load dependencies
require_once __DIR__ . '/../includes/init.php';

// Get current user info
$user = $_SESSION['user'] ?? 'employee01';
$user_id = $_SESSION['user_id'] ?? 0;

// Try to load review requests from database
$requests = [];
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("
            SELECT 
                rr.id,
                rr.subject,
                rr.description,
                rr.urgency,
                rr.phase,
                rr.status,
                rr.created_at,
                rr.updated_at,
                p.name as project_name,
                p.id as project_id,
                u.username as submitted_by
            FROM review_requests rr
            LEFT JOIN projects p ON p.id = rr.project_id
            LEFT JOIN users u ON u.id = rr.submitted_by
            ORDER BY 
                CASE rr.urgency 
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    ELSE 4
                END,
                rr.created_at DESC
            LIMIT 50
        ");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading review requests: ' . $e->getMessage());
        $requests = [];
    }
}

// Fallback sample data if database unavailable or empty
if (empty($requests)) {
    $requests = [
        [
            'id' => 1,
            'subject' => 'Foundation Layer Inspection',
            'description' => 'Ground floor foundation work is complete and ready for inspection. All reinforcement bars are in place as per drawing specifications.',
            'project_name' => 'Renovation — Oak Street Residence',
            'project_id' => 101,
            'urgency' => 'high',
            'phase' => 'civil work',
            'status' => 'pending',
            'submitted_by' => 'Ramesh Kumar',
            'created_at' => '2026-02-12 10:30:00',
            'updated_at' => '2026-02-12 10:30:00'
        ],
        [
            'id' => 2,
            'subject' => 'Electrical Layout Approval',
            'description' => 'Electrical conduit installation completed. Requesting approval before proceeding with wiring.',
            'project_name' => 'Shop Fitout — Market Road',
            'project_id' => 102,
            'urgency' => 'normal',
            'phase' => 'electrical',
            'status' => 'pending',
            'submitted_by' => 'Suresh Bhai',
            'created_at' => '2026-02-13 14:15:00',
            'updated_at' => '2026-02-13 14:15:00'
        ],
        [
            'id' => 3,
            'subject' => 'Plumbing Fixture Installation',
            'description' => 'Bathroom plumbing fixtures installed. Need final approval before sealing walls.',
            'project_name' => 'New Build — Riverfront Villa',
            'project_id' => 103,
            'urgency' => 'critical',
            'phase' => 'plumbing',
            'status' => 'pending',
            'submitted_by' => 'Ramesh Kumar',
            'created_at' => '2026-02-14 09:00:00',
            'updated_at' => '2026-02-14 09:00:00'
        ],
        [
            'id' => 4,
            'subject' => 'Woodwork Quality Check',
            'description' => 'Kitchen cabinet installation complete. Requesting quality inspection.',
            'project_name' => 'Renovation — Oak Street Residence',
            'project_id' => 101,
            'urgency' => 'normal',
            'phase' => 'carpentry',
            'status' => 'approved',
            'submitted_by' => 'Mahesh M.',
            'created_at' => '2026-02-10 11:20:00',
            'updated_at' => '2026-02-11 15:30:00'
        ],
        [
            'id' => 5,
            'subject' => 'Paint Color Confirmation',
            'description' => 'Sample paint applied to bedroom walls. Requesting client confirmation before proceeding.',
            'project_name' => 'Shop Fitout — Market Road',
            'project_id' => 102,
            'urgency' => 'high',
            'phase' => 'painting',
            'status' => 'changes_requested',
            'submitted_by' => 'Dinesh Shah',
            'created_at' => '2026-02-11 16:45:00',
            'updated_at' => '2026-02-12 10:00:00'
        ],
    ];
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    if ($request_id && in_array($new_status, ['approved', 'rejected', 'changes_requested'])) {
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE review_requests 
                    SET status = :status,
                        admin_notes = :admin_notes,
                        reviewed_by = :reviewed_by,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'status' => $new_status,
                    'admin_notes' => $admin_notes,
                    'reviewed_by' => $user,
                    'id' => $request_id
                ]);
                
                $_SESSION['flash_message'] = 'Review request updated successfully!';
                $_SESSION['flash_type'] = 'success';
                
                // Redirect to avoid form resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (Exception $e) {
                error_log('Failed to update review request: ' . $e->getMessage());
                $_SESSION['flash_message'] = 'Failed to update request. Please try again.';
                $_SESSION['flash_type'] = 'danger';
            }
        } else {
            $_SESSION['flash_message'] = 'Status updated (demo mode - no database)';
            $_SESSION['flash_type'] = 'info';
        }
    }
}

// Count requests by status
$status_counts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'changes_requested' => 0
];
foreach ($requests as $r) {
    $status = $r['status'] ?? 'pending';
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}

// Function to get urgency badge class
function get_urgency_class($urgency) {
    switch (strtolower($urgency)) {
        case 'critical': return 'danger';
        case 'high': return 'warning';
        case 'normal': return 'info';
        default: return 'secondary';
    }
}

// Function to get status badge class
function get_status_class($status) {
    switch (strtolower($status)) {
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'changes_requested': return 'warning';
        case 'pending': return 'secondary';
        default: return 'secondary';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Review Requests - Ripal Design</title>
    
    <!-- Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <?php asset_enqueue_css('/worker/worker_dashboard.css'); ?>
    
    <style>
        .request-card {
            background: #fff;
            border: 1px solid var(--color-border, #E0E0E0);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        .request-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .request-card.critical {
            border-left: 4px solid #dc3545;
        }
        .request-card.high {
            border-left: 4px solid #ffc107;
        }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        .stat-box {
            background: #fff;
            border: 1px solid var(--color-border, #E0E0E0);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-box .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-primary, #731209);
            line-height: 1;
            margin-bottom: 8px;
        }
        .stat-box .label {
            font-size: 14px;
            color: var(--color-text-muted, #333);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<?php 
$HEADER_MODE = 'dashboard'; 
require_once __DIR__ . '/../Common/header.php'; 
?>

<main class="worker-dashboard">
    <div class="container-fluid px-4">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="toolbar justify-content-between">
                <div class="title-wrap">
                    <h1>Review Requests</h1>
                    <p class="muted">Manage and approve worker review requests</p>
                </div>
                <div class="avatar" aria-hidden="true"><?php echo esc(strtoupper(substr($user, 0, 2))); ?></div>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo esc($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
            <?php 
            echo esc($_SESSION['flash_message']); 
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Summary Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="value text-secondary"><?php echo $status_counts['pending']; ?></div>
                    <div class="label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="value text-success"><?php echo $status_counts['approved']; ?></div>
                    <div class="label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="value text-warning"><?php echo $status_counts['changes_requested']; ?></div>
                    <div class="label">Changes Req.</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="value text-danger"><?php echo $status_counts['rejected']; ?></div>
                    <div class="label">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="statusFilter" id="filterAll" autocomplete="off" checked>
                <label class="btn btn-outline-primary btn-sm" for="filterAll">All</label>
                
                <input type="radio" class="btn-check" name="statusFilter" id="filterPending" autocomplete="off">
                <label class="btn btn-outline-secondary btn-sm" for="filterPending">Pending</label>
                
                <input type="radio" class="btn-check" name="statusFilter" id="filterApproved" autocomplete="off">
                <label class="btn btn-outline-success btn-sm" for="filterApproved">Approved</label>
            </div>
            
            <div>
                <select class="form-select form-select-sm" id="sortRequests">
                    <option value="urgency">Sort by Urgency</option>
                    <option value="date">Sort by Date</option>
                    <option value="project">Sort by Project</option>
                </select>
            </div>
        </div>

        <!-- Requests List -->
        <div class="row">
            <div class="col-12">
                <?php foreach ($requests as $request): ?>
                <div class="request-card <?php echo esc_attr($request['urgency']); ?>" data-status="<?php echo esc_attr($request['status']); ?>">
                    <div class="request-header">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h3 class="h5 mb-0"><?php echo esc($request['subject']); ?></h3>
                                <span class="badge bg-<?php echo get_urgency_class($request['urgency']); ?>">
                                    <?php echo esc(ucfirst($request['urgency'])); ?>
                                </span>
                                <span class="badge bg-<?php echo get_status_class($request['status']); ?>">
                                    <?php echo esc(ucfirst(str_replace('_', ' ', $request['status']))); ?>
                                </span>
                            </div>
                            
                            <p class="text-muted mb-2 small">
                                <i class="bi bi-folder me-1"></i>
                                <a href="project_details.php?id=<?php echo $request['project_id']; ?>" class="text-decoration-none">
                                    <?php echo esc($request['project_name']); ?>
                                </a>
                                &bull;
                                <i class="bi bi-gear me-1"></i><?php echo esc(ucfirst($request['phase'])); ?>
                                &bull;
                                <i class="bi bi-person me-1"></i><?php echo esc($request['submitted_by']); ?>
                            </p>
                            
                            <p class="mb-3"><?php echo esc($request['description']); ?></p>
                            
                            <p class="text-muted small mb-0">
                                <i class="bi bi-clock me-1"></i>
                                Submitted: <?php echo date('M d, Y g:i A', strtotime($request['created_at'])); ?>
                                <?php if ($request['updated_at'] !== $request['created_at']): ?>
                                &bull; Updated: <?php echo date('M d, Y g:i A', strtotime($request['updated_at'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <button 
                            class="btn btn-sm btn-outline-primary"
                            onclick="viewDetails(<?php echo $request['id']; ?>)"
                        >
                            <i class="bi bi-eye me-1"></i>View
                        </button>
                        
                        <?php if ($request['status'] === 'pending'): ?>
                        <button 
                            class="btn btn-sm btn-success"
                            onclick="openStatusModal(<?php echo $request['id']; ?>, '<?php echo esc_js($request['subject']); ?>', 'approved')"
                        >
                            <i class="bi bi-check-circle me-1"></i>Approve
                        </button>
                        <button 
                            class="btn btn-sm btn-warning"
                            onclick="openStatusModal(<?php echo $request['id']; ?>, '<?php echo esc_js($request['subject']); ?>', 'changes_requested')"
                        >
                            <i class="bi bi-exclamation-circle me-1"></i>Request Changes
                        </button>
                        <button 
                            class="btn btn-sm btn-danger"
                            onclick="openStatusModal(<?php echo $request['id']; ?>, '<?php echo esc_js($request['subject']); ?>', 'rejected')"
                        >
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($requests)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-check display-1 text-muted"></i>
                    <p class="text-muted mt-3">No review requests found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h4 mb-0">Update Request Status</h3>
            <button type="button" class="btn-close" onclick="closeStatusModal()"></button>
        </div>
        
        <form method="POST" id="statusForm">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="request_id" id="modal_request_id">
            <input type="hidden" name="status" id="modal_status">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Request</label>
                <p id="modal_request_subject" class="text-muted mb-0"></p>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">New Status</label>
                <p id="modal_status_display" class="mb-0"></p>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Admin Notes (Optional)</label>
                <textarea 
                    class="form-control" 
                    name="admin_notes" 
                    rows="4" 
                    placeholder="Add any notes or feedback for the worker..."
                ></textarea>
            </div>
            
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../Common/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Status modal functions
function openStatusModal(requestId, requestSubject, status) {
    document.getElementById('modal_request_id').value = requestId;
    document.getElementById('modal_request_subject').textContent = requestSubject;
    document.getElementById('modal_status').value = status;
    
    let statusDisplay = status.replace('_', ' ');
    statusDisplay = statusDisplay.charAt(0).toUpperCase() + statusDisplay.slice(1);
    document.getElementById('modal_status_display').textContent = statusDisplay;
    
    document.getElementById('statusModal').classList.add('show');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('show');
    document.getElementById('statusForm').reset();
}

function viewDetails(requestId) {
    alert('View details for request #' + requestId + '\n(Full implementation would show detailed modal or navigate to detail page)');
}

// Filter functionality
document.querySelectorAll('input[name="statusFilter"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const filter = this.id.replace('filter', '').toLowerCase();
        document.querySelectorAll('.request-card').forEach(card => {
            if (filter === 'all') {
                card.style.display = 'block';
            } else {
                const status = card.dataset.status;
                card.style.display = status === filter ? 'block' : 'none';
            }
        });
    });
});

// Sort functionality
document.getElementById('sortRequests')?.addEventListener('change', function() {
    alert('Sorting by: ' + this.value + '\n(Full implementation would need server-side sorting)');
});

// Close modal on outside click
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>
</body>
</html>