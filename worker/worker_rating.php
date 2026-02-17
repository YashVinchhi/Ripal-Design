<?php
/**
 * Worker Rating System
 * 
 * This page allows project managers and supervisors to rate workers
 * on their completed projects. It displays a list of workers with their
 * average ratings and allows for submitting new ratings.
 * 
 * @package RipalDesign
 * @subpackage Worker
 */

// Initialize session and load dependencies
require_once __DIR__ . '/../includes/init.php';

// Get current user info
$current_user = $_SESSION['user'] ?? 'Admin';

// Try to load workers and their ratings from database
$workers = [];
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // Query to get workers with their average rating
        $stmt = $pdo->query("
            SELECT 
                u.id, 
                u.username, 
                u.email,
                u.phone,
                u.role,
                COUNT(DISTINCT pa.project_id) as projects_count,
                AVG(wr.rating) as avg_rating,
                COUNT(wr.id) as total_ratings
            FROM users u
            LEFT JOIN project_assignments pa ON pa.worker_id = u.id
            LEFT JOIN worker_ratings wr ON wr.worker_id = u.id
            WHERE u.role = 'worker'
            GROUP BY u.id
            ORDER BY u.username ASC
        ");
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading workers for rating: ' . $e->getMessage());
        // Fall back to sample data
        $workers = [];
    }
}

// Fallback sample data if DB not available or empty
if (empty($workers)) {
    $workers = [
        [
            'id' => 11,
            'username' => 'Ramesh Kumar',
            'email' => 'ramesh.kumar@ripal.design',
            'phone' => '+91 98989 89898',
            'role' => 'Plumber',
            'projects_count' => 5,
            'avg_rating' => 4.5,
            'total_ratings' => 8
        ],
        [
            'id' => 12,
            'username' => 'Suresh Bhai',
            'email' => 'suresh.b@ripal.design',
            'phone' => '+91 97979 79797',
            'role' => 'Electrician',
            'projects_count' => 8,
            'avg_rating' => 4.8,
            'total_ratings' => 12
        ],
        [
            'id' => 13,
            'username' => 'Mahesh M.',
            'email' => 'mahesh.m@ripal.design',
            'phone' => '+91 96969 69696',
            'role' => 'Carpenter',
            'projects_count' => 6,
            'avg_rating' => 4.2,
            'total_ratings' => 10
        ],
        [
            'id' => 14,
            'username' => 'Rajesh Patel',
            'email' => 'rajesh.p@ripal.design',
            'phone' => '+91 95959 59595',
            'role' => 'Mason',
            'projects_count' => 4,
            'avg_rating' => 4.6,
            'total_ratings' => 6
        ],
        [
            'id' => 15,
            'username' => 'Dinesh Shah',
            'email' => 'dinesh.s@ripal.design',
            'phone' => '+91 94949 49494',
            'role' => 'Painter',
            'projects_count' => 3,
            'avg_rating' => 4.0,
            'total_ratings' => 5
        ],
    ];
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $worker_id = intval($_POST['worker_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $project_id = intval($_POST['project_id'] ?? 0);
    
    if ($worker_id && $rating >= 1 && $rating <= 5) {
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO worker_ratings (worker_id, rated_by, project_id, rating, comment, created_at)
                    VALUES (:worker_id, :rated_by, :project_id, :rating, :comment, NOW())
                ");
                $stmt->execute([
                    'worker_id' => $worker_id,
                    'rated_by' => $current_user,
                    'project_id' => $project_id > 0 ? $project_id : null,
                    'rating' => $rating,
                    'comment' => $comment
                ]);
                
                $_SESSION['flash_message'] = 'Rating submitted successfully!';
                $_SESSION['flash_type'] = 'success';
                
                // Redirect to avoid form resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (Exception $e) {
                error_log('Failed to save rating: ' . $e->getMessage());
                $_SESSION['flash_message'] = 'Failed to save rating. Please try again.';
                $_SESSION['flash_type'] = 'danger';
            }
        } else {
            $_SESSION['flash_message'] = 'Rating saved (demo mode - no database)';
            $_SESSION['flash_type'] = 'info';
        }
    } else {
        $_SESSION['flash_message'] = 'Invalid rating data provided.';
        $_SESSION['flash_type'] = 'danger';
    }
}

// Function to render star rating display
function render_stars($rating, $max = 5) {
    $rating = floatval($rating);
    $output = '';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= floor($rating)) {
            $output .= '<i class="bi bi-star-fill text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $output .= '<i class="bi bi-star-half text-warning"></i>';
        } else {
            $output .= '<i class="bi bi-star text-muted"></i>';
        }
    }
    return $output;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Worker Ratings - Ripal Design</title>
    
    <!-- Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <?php asset_enqueue_css('/worker/worker_dashboard.css'); ?>
    
    <style>
        .rating-card {
            background: #fff;
            border: 1px solid var(--color-border, #E0E0E0);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        .rating-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-color: var(--color-primary, #731209);
        }
        .worker-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary, #731209), #a52a1f);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .rating-stars {
            font-size: 18px;
            line-height: 1;
        }
        .stat-badge {
            background: #f8f9fa;
            border: 1px solid var(--color-border, #E0E0E0);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            white-space: nowrap;
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
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .star-rating-input {
            font-size: 32px;
            cursor: pointer;
        }
        .star-rating-input i {
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating-input i:hover,
        .star-rating-input i.active {
            color: #ffc107;
        }
    </style>
</head>
<body>
<?php 
$HEADER_MODE = 'dashboard'; 
require_once __DIR__ . '/../common/header_alt.php'; 
?>

<main class="worker-dashboard">
    <div class="container-fluid px-4">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="toolbar justify-content-between">
                <div class="title-wrap">
                    <h1>Worker Ratings</h1>
                    <p class="muted">Rate and review workers based on their project performance</p>
                </div>
                <div class="avatar" aria-hidden="true"><?php echo esc(strtoupper(substr($current_user, 0, 2))); ?></div>
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
                <div class="stat-badge w-100 text-center">
                    <div class="text-muted small">Total Workers</div>
                    <div class="fs-4 fw-bold text-primary"><?php echo count($workers); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge w-100 text-center">
                    <div class="text-muted small">Average Rating</div>
                    <div class="fs-4 fw-bold text-warning">
                        <?php 
                        $total_avg = 0;
                        $count = 0;
                        foreach ($workers as $w) {
                            if ($w['avg_rating']) {
                                $total_avg += $w['avg_rating'];
                                $count++;
                            }
                        }
                        echo $count > 0 ? number_format($total_avg / $count, 1) : 'N/A';
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge w-100 text-center">
                    <div class="text-muted small">Total Projects</div>
                    <div class="fs-4 fw-bold text-info">
                        <?php echo array_sum(array_column($workers, 'projects_count')); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge w-100 text-center">
                    <div class="text-muted small">Total Ratings</div>
                    <div class="fs-4 fw-bold text-success">
                        <?php echo array_sum(array_column($workers, 'total_ratings')); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workers List -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0">All Workers</h2>
                    <div>
                        <select class="form-select form-select-sm" id="sortWorkers">
                            <option value="name">Sort by Name</option>
                            <option value="rating">Sort by Rating</option>
                            <option value="projects">Sort by Projects</option>
                        </select>
                    </div>
                </div>
                
                <?php foreach ($workers as $worker): ?>
                <div class="rating-card">
                    <div class="d-flex gap-3">
                        <div class="worker-avatar">
                            <?php echo esc(strtoupper(substr($worker['username'], 0, 2))); ?>
                        </div>
                        
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h3 class="h5 mb-1"><?php echo esc($worker['username']); ?></h3>
                                    <p class="text-muted mb-0 small">
                                        <i class="bi bi-briefcase me-1"></i><?php echo esc($worker['role']); ?>
                                        <?php if (!empty($worker['email'])): ?>
                                        &bull; <i class="bi bi-envelope me-1"></i><?php echo esc($worker['email']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <button 
                                    class="btn btn-sm btn-primary"
                                    onclick="openRatingModal(<?php echo $worker['id']; ?>, '<?php echo esc_js($worker['username']); ?>')"
                                >
                                    <i class="bi bi-star me-1"></i> Rate Worker
                                </button>
                            </div>
                            
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="rating-stars">
                                    <?php echo render_stars($worker['avg_rating'] ?? 0); ?>
                                    <span class="ms-2 fw-bold"><?php echo number_format($worker['avg_rating'] ?? 0, 1); ?></span>
                                    <span class="text-muted small">(<?php echo $worker['total_ratings']; ?> reviews)</span>
                                </div>
                                
                                <div class="stat-badge">
                                    <i class="bi bi-folder me-1"></i>
                                    <?php echo $worker['projects_count']; ?> Projects
                                </div>
                                
                                <?php if (!empty($worker['phone'])): ?>
                                <a href="tel:<?php echo esc_attr($worker['phone']); ?>" class="text-decoration-none stat-badge">
                                    <i class="bi bi-telephone me-1"></i>
                                    <?php echo esc($worker['phone']); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($workers)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people display-1 text-muted"></i>
                    <p class="text-muted mt-3">No workers found in the system.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Rating Modal -->
<div id="ratingModal" class="modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h4 mb-0">Rate Worker</h3>
            <button type="button" class="btn-close" onclick="closeRatingModal()"></button>
        </div>
        
        <form method="POST" id="ratingForm">
            <input type="hidden" name="worker_id" id="modal_worker_id">
            <input type="hidden" name="rating" id="modal_rating" value="0">
            <input type="hidden" name="submit_rating" value="1">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Worker</label>
                <p id="modal_worker_name" class="text-muted mb-0"></p>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Project (Optional)</label>
                <select class="form-select" name="project_id">
                    <option value="0">General Rating</option>
                    <option value="101">Renovation — Oak Street Residence</option>
                    <option value="102">Shop Fitout — Market Road</option>
                    <option value="103">New Build — Riverfront Villa</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Your Rating</label>
                <div class="star-rating-input" id="starRating">
                    <i class="bi bi-star" data-rating="1"></i>
                    <i class="bi bi-star" data-rating="2"></i>
                    <i class="bi bi-star" data-rating="3"></i>
                    <i class="bi bi-star" data-rating="4"></i>
                    <i class="bi bi-star" data-rating="5"></i>
                </div>
                <small class="text-muted">Click to rate from 1 to 5 stars</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Comments (Optional)</label>
                <textarea 
                    class="form-control" 
                    name="comment" 
                    rows="4" 
                    placeholder="Share your feedback about this worker's performance..."
                ></textarea>
            </div>
            
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-secondary" onclick="closeRatingModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Rating</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../common/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Rating modal functions
function openRatingModal(workerId, workerName) {
    document.getElementById('modal_worker_id').value = workerId;
    document.getElementById('modal_worker_name').textContent = workerName;
    document.getElementById('modal_rating').value = 0;
    document.getElementById('ratingModal').classList.add('show');
    resetStars();
}

function closeRatingModal() {
    document.getElementById('ratingModal').classList.remove('show');
    document.getElementById('ratingForm').reset();
}

function resetStars() {
    document.querySelectorAll('.star-rating-input i').forEach(star => {
        star.classList.remove('active', 'bi-star-fill');
        star.classList.add('bi-star');
    });
}

// Star rating interaction
document.querySelectorAll('.star-rating-input i').forEach(star => {
    star.addEventListener('click', function() {
        const rating = parseInt(this.dataset.rating);
        document.getElementById('modal_rating').value = rating;
        
        // Update star display
        document.querySelectorAll('.star-rating-input i').forEach((s, index) => {
            if (index < rating) {
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill', 'active');
            } else {
                s.classList.remove('bi-star-fill', 'active');
                s.classList.add('bi-star');
            }
        });
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        document.querySelectorAll('.star-rating-input i').forEach((s, index) => {
            if (index < rating) {
                s.style.color = '#ffc107';
            }
        });
    });
});

document.querySelector('.star-rating-input').addEventListener('mouseleave', function() {
    const currentRating = parseInt(document.getElementById('modal_rating').value);
    document.querySelectorAll('.star-rating-input i').forEach((s, index) => {
        if (index >= currentRating) {
            s.style.color = '';
        }
    });
});

// Sort functionality
document.getElementById('sortWorkers')?.addEventListener('change', function() {
    // Placeholder for sorting - would need server-side implementation
    alert('Sorting feature: ' + this.value + ' (requires server-side implementation)');
});

// Close modal on outside click
document.getElementById('ratingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRatingModal();
    }
});
</script>
</body>
</html>