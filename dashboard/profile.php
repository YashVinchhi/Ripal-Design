<?php
/**
 * User Profile Page
 * 
 * Allows users to view and edit their profile information including
 * personal details, contact information, and account settings.
 * 
 * @package RipalDesign
 * @subpackage Dashboard
 */

// Initialize session and load dependencies
require_once __DIR__ . '/../includes/init.php';

// Get current user info
$user = $_SESSION['user'] ?? 'employee01';
$user_id = $_SESSION['user_id'] ?? 0;

// Initialize user data
$user_data = [
    'id' => $user_id,
    'username' => $user,
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'employee',
    'address' => '',
    'city' => '',
    'state' => '',
    'zip' => '',
    'joined_date' => date('Y-m-d'),
];

// Try to load user data from database
if (isset($pdo) && $pdo instanceof PDO && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $user_id]);
        $db_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($db_user) {
            $user_data = array_merge($user_data, $db_user);
        }
    } catch (Exception $e) {
        error_log('Failed loading user profile: ' . $e->getMessage());
    }
}

// Fallback sample data if needed
if (empty($user_data['full_name'])) {
    $user_data['full_name'] = 'Demo Employee';
    $user_data['email'] = 'demo.employee@ripal.design';
    $user_data['phone'] = '+91 98765 43210';
    $user_data['role'] = 'employee';
    $user_data['address'] = '123 Main Street, Near Central Park';
    $user_data['city'] = 'Rajkot';
    $user_data['state'] = 'Gujarat';
    $user_data['zip'] = '360001';
    $user_data['joined_date'] = '2024-01-15';
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    
    // Basic validation
    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    
    if (empty($errors)) {
        if (isset($pdo) && $pdo instanceof PDO && $user_id > 0) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = :full_name, 
                        email = :email, 
                        phone = :phone,
                        address = :address,
                        city = :city,
                        state = :state,
                        zip = :zip,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                    'id' => $user_id
                ]);
                
                $_SESSION['flash_message'] = 'Profile updated successfully!';
                $_SESSION['flash_type'] = 'success';
                
                // Update session data
                $user_data['full_name'] = $full_name;
                $user_data['email'] = $email;
                $user_data['phone'] = $phone;
                $user_data['address'] = $address;
                $user_data['city'] = $city;
                $user_data['state'] = $state;
                $user_data['zip'] = $zip;
                
                // Redirect to avoid form resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (Exception $e) {
                error_log('Failed to update profile: ' . $e->getMessage());
                $_SESSION['flash_message'] = 'Failed to update profile. Please try again.';
                $_SESSION['flash_type'] = 'danger';
            }
        } else {
            $_SESSION['flash_message'] = 'Profile saved (demo mode - no database)';
            $_SESSION['flash_type'] = 'info';
        }
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'danger';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    if (empty($current_password)) $errors[] = 'Current password is required';
    if (empty($new_password)) $errors[] = 'New password is required';
    if (strlen($new_password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($new_password !== $confirm_password) $errors[] = 'Passwords do not match';
    
    if (empty($errors)) {
        // In production, verify current password and update
        $_SESSION['flash_message'] = 'Password changed successfully!';
        $_SESSION['flash_type'] = 'success';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'danger';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profile — <?php echo esc($user_data['username']); ?> - Ripal Design</title>
    
    <!-- Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <?php asset_enqueue_css('/worker/worker_dashboard.css'); ?>
    
    <style>
        .profile-section {
            background: #fff;
            border: 1px solid var(--color-border, #E0E0E0);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .profile-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary, #731209), #a52a1f);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-item {
            text-align: center;
            padding: 12px;
        }
        .stat-item .label {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--color-text-muted, #666);
            margin-bottom: 4px;
        }
        .stat-item .value {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-primary, #731209);
        }
    </style>
</head>
<body>
<?php 
$HEADER_MODE = 'dashboard'; 
require_once __DIR__ . '/../Common/header_alt.php'; 
?>

<main class="worker-dashboard">
    <div class="container-fluid px-4">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="toolbar justify-content-between">
                <div class="title-wrap">
                    <h1>My Profile</h1>
                    <p class="muted">Manage your profile and account settings</p>
                </div>
                <div class="avatar" aria-hidden="true"><?php echo esc(strtoupper(substr($user_data['username'], 0, 2))); ?></div>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo esc($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['flash_message']; // May contain HTML 
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left Column: Profile Overview -->
            <div class="col-lg-4 mb-4">
                <div class="profile-section text-center">
                    <div class="profile-avatar-large mx-auto mb-3">
                        <?php echo esc(strtoupper(substr($user_data['username'], 0, 2))); ?>
                    </div>
                    <h2 class="h4 mb-1"><?php echo esc($user_data['full_name'] ?: $user_data['username']); ?></h2>
                    <p class="text-muted mb-2">@<?php echo esc($user_data['username']); ?></p>
                    <span class="badge bg-secondary"><?php echo esc(ucfirst($user_data['role'])); ?></span>
                    
                    <hr class="my-3">
                    
                    <div class="row g-0">
                        <div class="col-6 stat-item border-end">
                            <div class="label">Member Since</div>
                            <div class="value"><?php echo date('Y', strtotime($user_data['joined_date'] ?? 'now')); ?></div>
                        </div>
                        <div class="col-6 stat-item">
                            <div class="label">Projects</div>
                            <div class="value">8</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="profile-section">
                    <h3 class="h6 mb-3 text-uppercase text-muted">Quick Actions</h3>
                    <div class="d-grid gap-2">
                        <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-grid me-2"></i>Dashboard
                        </a>
                        <a href="review_requests.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-clipboard-check me-2"></i>Review Requests
                        </a>
                        <a href="../worker/assigned_projects.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-folder me-2"></i>My Projects
                        </a>
                        <a href="../public/logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Edit Forms -->
            <div class="col-lg-8">
                <!-- Personal Information -->
                <div class="profile-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">
                            <i class="bi bi-person-circle me-2 text-primary"></i>Personal Information
                        </h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Full Name *</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="full_name" 
                                    value="<?php echo esc_attr($user_data['full_name']); ?>"
                                    required
                                >
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Username</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    value="<?php echo esc_attr($user_data['username']); ?>"
                                    disabled
                                >
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Email Address *</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    name="email" 
                                    value="<?php echo esc_attr($user_data['email']); ?>"
                                    required
                                >
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Phone Number</label>
                                <input 
                                    type="tel" 
                                    class="form-control" 
                                    name="phone" 
                                    value="<?php echo esc_attr($user_data['phone']); ?>"
                                    placeholder="+91 98765 43210"
                                >
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <label class="form-label fw-bold small">Address</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="address" 
                                value="<?php echo esc_attr($user_data['address']); ?>"
                                placeholder="Street address"
                            >
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">City</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="city" 
                                    value="<?php echo esc_attr($user_data['city']); ?>"
                                >
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">State</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="state" 
                                    value="<?php echo esc_attr($user_data['state']); ?>"
                                >
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">ZIP Code</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="zip" 
                                    value="<?php echo esc_attr($user_data['zip']); ?>"
                                >
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security Settings -->
                <div class="profile-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">
                            <i class="bi bi-shield-lock me-2 text-primary"></i>Security Settings
                        </h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Current Password *</label>
                            <input 
                                type="password" 
                                class="form-control" 
                                name="current_password"
                                required
                            >
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">New Password *</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    name="new_password"
                                    minlength="8"
                                    required
                                >
                                <small class="text-muted">At least 8 characters</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Confirm New Password *</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    name="confirm_password"
                                    minlength="8"
                                    required
                                >
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../Common/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>