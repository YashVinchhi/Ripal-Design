<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
/**
 * User Profile Page (Redesigned)
 * 
 * Allows users to view and edit their profile information.
 * Fixes header errors and adheres to the Rajkot Rust immersive design.
 */

require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';

// Get current user info from session (support multiple session shapes)
$session_user = $_SESSION['user'] ?? null;
$session_user_id = current_user_id();
$session_username = current_username();
$session_role = $_SESSION['role'] ?? ($session_user['role'] ?? null);

$current_user_id = (int)$session_user_id;
$current_username = (string)$session_username;
$current_role = $session_role ?? 'guest';

// Determine which profile to view: allow admins to view others via ?id or ?user
$view_user_id = $current_user_id;
$view_username = $current_username;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $requested_id = (int)$_GET['id'];
    if ($requested_id > 0 && $current_role === 'admin') {
        $view_user_id = $requested_id;
        $view_username = null;
    }
} elseif (!empty($_GET['user'])) {
    $requested_user = trim((string)$_GET['user']);
    if ($requested_user !== '' && $current_role === 'admin') {
        $view_username = $requested_user;
        $view_user_id = 0;
    }
}

// Legacy compatibility variables
$user = $current_username ?: ($view_username ?? 'employee01');
$user_id = $view_user_id ?? 0;

// Initialize user data default
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

// Load user data from database if available. Support admin viewing others via username or id.
if (db_connected()) {
    try {
        $db = get_db();
        $db_user = null;

        // If an admin requested a username, load by username
        if (!empty($view_username) && $current_role === 'admin') {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$view_username]);
            $db_user = $stmt->fetch();
        }

        // If a specific id is requested (admin) or fallback to resolved id
        if (empty($db_user) && $view_user_id > 0) {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$view_user_id]);
            $db_user = $stmt->fetch();
        }

        // Final fallback: if still empty but session user exists, load session user
        if (empty($db_user) && $current_user_id > 0) {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$current_user_id]);
            $db_user = $stmt->fetch();
        }

        if (!empty($db_user)) {
            $user_data = array_merge($user_data, $db_user);
        }
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'Profile load error', ['exception' => $e->getMessage()]);
        }
    }
}

if (empty($user_data['joined_date'])) {
    $user_data['joined_date'] = date('Y-m-d');
}

// Ensure downstream stats and updates use the actually loaded profile id.
$user_id = (int)($user_data['id'] ?? $user_id ?? 0);

// Normalize nullable DB fields to strings to avoid deprecation warnings in output helpers.
$profile_string_fields = ['username', 'full_name', 'email', 'phone', 'role', 'address', 'city', 'state', 'zip'];
foreach ($profile_string_fields as $field) {
    $user_data[$field] = (string)($user_data[$field] ?? '');
}

$projectStats = [
    'total_projects' => 0,
    'total_members' => 0,
];
$ratingStats = [
    'avg_rating' => 0,
    'total_ratings' => 0,
];
$userProjects = [];
$clientRateTargets = [];
$recentRatings = [];
$isOwnClientProfile = ($current_role === 'client' && (int)$current_user_id > 0 && (int)$user_id === (int)$current_user_id);

if (db_connected() && $user_id > 0) {
    try {
        $db = get_db();

        // Build dynamic project list based on profile role.
        if (($user_data['role'] ?? '') === 'client') {
            $projectStmt = $db->prepare("\n                SELECT p.id, p.name, p.status, p.progress, p.due,\n                       COUNT(DISTINCT pa.worker_id) AS member_count\n                FROM projects p\n                LEFT JOIN project_assignments pa ON pa.project_id = p.id\n                WHERE p.owner_email = :email\n                   OR p.owner_name = :full_name\n                   OR p.owner_name = :username\n                GROUP BY p.id, p.name, p.status, p.progress, p.due\n                ORDER BY p.created_at DESC, p.id DESC\n            ");
            $projectStmt->execute([
                ':email' => (string)($user_data['email'] ?? ''),
                ':full_name' => (string)($user_data['full_name'] ?? ''),
                ':username' => (string)($user_data['username'] ?? ''),
            ]);
            $userProjects = $projectStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if ($isOwnClientProfile) {
                $targetsStmt = $db->prepare("\n                    SELECT DISTINCT u.id, u.username, u.full_name, u.role, p.name AS project_name\n                    FROM projects p\n                    INNER JOIN project_assignments pa ON pa.project_id = p.id\n                    INNER JOIN users u ON u.id = pa.worker_id\n                    WHERE (p.owner_email = :email OR p.owner_name = :full_name OR p.owner_name = :username)\n                      AND u.id <> :self_id\n                    ORDER BY p.name ASC, u.username ASC\n                ");
                $targetsStmt->execute([
                    ':email' => (string)($user_data['email'] ?? ''),
                    ':full_name' => (string)($user_data['full_name'] ?? ''),
                    ':username' => (string)($user_data['username'] ?? ''),
                    ':self_id' => (int)$user_id,
                ]);
                $clientRateTargets = $targetsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } else {
            $projectStmt = $db->prepare("\n                SELECT p.id, p.name, p.status, p.progress, p.due,\n                       COUNT(DISTINCT pa2.worker_id) AS member_count\n                FROM projects p\n                LEFT JOIN project_assignments pa2 ON pa2.project_id = p.id\n                LEFT JOIN project_assignments pa ON pa.project_id = p.id\n                WHERE pa.worker_id = :user_id\n                   OR p.owner_email = :email\n                   OR p.owner_name = :full_name\n                   OR p.owner_name = :username\n                GROUP BY p.id, p.name, p.status, p.progress, p.due\n                ORDER BY p.created_at DESC, p.id DESC\n            ");
            $projectStmt->execute([
                ':user_id' => (int)$user_id,
                ':email' => (string)($user_data['email'] ?? ''),
                ':full_name' => (string)($user_data['full_name'] ?? ''),
                ':username' => (string)($user_data['username'] ?? ''),
            ]);
            $userProjects = $projectStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $projectStats['total_projects'] = count($userProjects);
        foreach ($userProjects as $projectRow) {
            $projectStats['total_members'] += (int)($projectRow['member_count'] ?? 0);
        }

        $ratingStmt = $db->prepare("\n            SELECT COUNT(*) AS total_ratings, AVG(rating) AS avg_rating\n            FROM worker_ratings\n            WHERE worker_id = :user_id\n        ");
        $ratingStmt->execute([':user_id' => (int)$user_id]);
        $ratingRow = $ratingStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $ratingStats['total_ratings'] = (int)($ratingRow['total_ratings'] ?? 0);
        $ratingStats['avg_rating'] = (float)($ratingRow['avg_rating'] ?? 0);

        $recentStmt = $db->prepare("\n            SELECT rating, comment, rated_by, created_at\n            FROM worker_ratings\n            WHERE worker_id = :user_id\n            ORDER BY created_at DESC\n            LIMIT 5\n        ");
        $recentStmt->execute([':user_id' => (int)$user_id]);
        $recentRatings = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'Profile dynamic stats error', ['exception' => $e->getMessage()]);
        }
    }
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_client_rating'])) {
    require_csrf();
    $member_id = (int)($_POST['member_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));

    if (!$isOwnClientProfile) {
        $message = 'Only clients can submit member ratings from their own profile.';
        $message_type = 'danger';
    } elseif ($member_id <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
        $message = 'Please select member, rating (1-5), and comment.';
        $message_type = 'danger';
    } elseif (db_connected()) {
        try {
            $db = get_db();
            $allowedStmt = $db->prepare("\n                SELECT 1\n                FROM projects p\n                INNER JOIN project_assignments pa ON pa.project_id = p.id\n                WHERE pa.worker_id = :member_id\n                  AND (p.owner_email = :email OR p.owner_name = :full_name OR p.owner_name = :username)\n                LIMIT 1\n            ");
            $allowedStmt->execute([
                ':member_id' => $member_id,
                ':email' => (string)($user_data['email'] ?? ''),
                ':full_name' => (string)($user_data['full_name'] ?? ''),
                ':username' => (string)($user_data['username'] ?? ''),
            ]);

            if (!$allowedStmt->fetch(PDO::FETCH_ASSOC)) {
                $message = 'Selected member is not linked to your projects.';
                $message_type = 'danger';
            } else {
                $ratedBy = (string)($current_username ?: ($user_data['username'] ?? 'client'));
                $insertStmt = $db->prepare("\n                    INSERT INTO worker_ratings (worker_id, rated_by, rating, comment, created_at)\n                    VALUES (:worker_id, :rated_by, :rating, :comment, NOW())\n                ");
                $insertStmt->execute([
                    ':worker_id' => $member_id,
                    ':rated_by' => $ratedBy,
                    ':rating' => $rating,
                    ':comment' => $comment,
                ]);

                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (Exception $e) {
            if (function_exists('app_log')) {
                app_log('warning', 'Client rating submit failed', ['exception' => $e->getMessage(), 'member_id' => (int)$member_id]);
            }
            $message = 'Unable to save rating now. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    require_csrf();
    $is_avatar_only = isset($_POST['avatar_only']) && (string)$_POST['avatar_only'] === '1';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    
    if (!$is_avatar_only && (empty($full_name) || empty($email))) {
        $message = 'Full Name and Email are required.';
        $message_type = 'danger';
    } else {
        if ($is_avatar_only) {
            $full_name = trim((string)($user_data['full_name'] ?? ''));
            $email = trim((string)($user_data['email'] ?? ''));
            $phone = (string)($user_data['phone'] ?? '');
            $address = (string)($user_data['address'] ?? '');
            $city = (string)($user_data['city'] ?? '');
            $state = (string)($user_data['state'] ?? '');
            $zip = (string)($user_data['zip'] ?? '');
        }

        $nameParts = preg_split('/\s+/', $full_name);
        $derivedFirstName = trim((string)($nameParts[0] ?? ''));
        $derivedLastName = trim((string)implode(' ', array_slice($nameParts ?: [], 1)));

        if (db_connected() && $user_id > 0) {
            try {
                $db = get_db();
                $avatar_updated = false;

                // Handle optional avatar upload
                if (isset($_FILES['avatar']) && is_array($_FILES['avatar']) && (int)($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $uploaded = $_FILES['avatar'];
                    $originalName = (string)($uploaded['name'] ?? 'avatar');
                    $tmpPath = (string)($uploaded['tmp_name'] ?? '');
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    if ($ext !== '' && in_array($ext, $allowed, true)) {
                        $safeBaseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                        $safeBaseName = $safeBaseName !== '' ? $safeBaseName : 'avatar';

                        $relativeDir = 'uploads/avatars/' . $user_id;
                        $absoluteDir = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

                        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
                            // Directory creation failed; skip saving avatar
                        } else {
                            $storedName = bin2hex(random_bytes(16)) . '.' . $ext;

                            $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $storedName;
                            if (move_uploaded_file($tmpPath, $absolutePath)) {
                                $publicPath = rtrim((string)BASE_PATH, '/') . '/' . $relativeDir . '/' . $storedName;

                                try {
                                    // Ensure avatar column exists (safe for existing DBs)
                                    $colStmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'avatar'");
                                    $colStmt->execute();
                                    $has = (bool)$colStmt->fetch(PDO::FETCH_ASSOC);
                                    if (!$has) {
                                        $db->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(500) DEFAULT NULL");
                                    }
                                } catch (Exception $e) {
                                    // Non-fatal: continue without altering schema
                                }

                                try {
                                    $upd = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                                    $upd->execute([$publicPath, $user_id]);
                                    $user_data['avatar'] = $publicPath;
                                    $avatar_updated = true;
                                    // Persist avatar in session for immediate propagation across pages
                                    if (!empty($_SESSION['user']) && (int)($_SESSION['user']['id'] ?? 0) === $user_id) {
                                        $_SESSION['user']['avatar'] = $publicPath;
                                    }
                                } catch (Exception $e) {
                                    if (function_exists('app_log')) {
                                        app_log('warning', 'Failed to save avatar path', ['exception' => $e->getMessage(), 'user_id' => (int)$user_id]);
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$is_avatar_only) {
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip = ? WHERE id = ?");
                    $stmt->execute([$full_name, $derivedFirstName, $derivedLastName, $email, $phone, $address, $city, $state, $zip, $user_id]);
                    $message = 'Profile updated successfully!';
                    $message_type = 'success';
                    // Refresh data
                    $user_data['full_name'] = $full_name;
                    $user_data['first_name'] = $derivedFirstName;
                    $user_data['last_name'] = $derivedLastName;
                    $user_data['email'] = $email;
                    $user_data['phone'] = $phone;
                    $user_data['address'] = $address;
                    $user_data['city'] = $city;
                    $user_data['state'] = $state;
                    $user_data['zip'] = $zip;

                    // Keep active session user names in sync when editing own profile.
                    if (!empty($_SESSION['user']) && (int)($_SESSION['user']['id'] ?? 0) === $user_id) {
                        $_SESSION['user']['full_name'] = $full_name;
                        $_SESSION['user']['first_name'] = $derivedFirstName;
                        $_SESSION['user']['last_name'] = $derivedLastName;
                        $sessionDisplayName = trim($derivedFirstName . ' ' . $derivedLastName);
                        if ($sessionDisplayName !== '') {
                            $_SESSION['user']['name'] = $sessionDisplayName;
                        }
                    }
                } else {
                    $message = $avatar_updated ? 'Profile photo updated successfully!' : 'Unable to update profile photo. Please try a JPG, PNG, WEBP, or GIF image.';
                    $message_type = $avatar_updated ? 'success' : 'danger';
                }
            } catch (Exception $e) {
                if (function_exists('app_log')) {
                    app_log('error', 'Profile update failed', ['exception' => $e->getMessage(), 'user_id' => (int)$user_id]);
                }
                $message = 'Unable to update profile right now. Please try again.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Profile updated (Demo Mode).';
            $message_type = 'success';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    require_csrf();
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'danger';
    } else {
        if (db_connected() && $user_id > 0) {
            try {
                $db = get_db();
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
                $message = 'Password changed successfully!';
                $message_type = 'success';
            } catch (Exception $e) {
                if (function_exists('app_log')) {
                    app_log('error', 'Password update failed', ['exception' => $e->getMessage(), 'user_id' => (int)$user_id]);
                }
                $message = 'Unable to change password right now. Please try again.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Password changed (Demo Mode).';
            $message_type = 'success';
        }
    }
}
?>
<?php
// Prepare avatar initials fallback (used in the profile sidebar/avatar preview)
$avatar_display_name = trim((string)($user_data['full_name'] ?? '')) !== '' ? trim((string)$user_data['full_name']) : trim((string)($user_data['username'] ?? ''));
$avatar_initials = '';
if ($avatar_display_name !== '') {
    $nameParts = preg_split('/\s+/', $avatar_display_name);
    $first = $nameParts[0] ?? '';
    $second = $nameParts[1] ?? '';
    if ($second !== '') {
        $avatar_initials = strtoupper(mb_substr($first, 0, 1) . mb_substr($second, 0, 1));
    } else {
        $avatar_initials = strtoupper(mb_substr($first, 0, 2));
    }
}
if ($avatar_initials === '') {
    $avatar_initials = strtoupper(mb_substr((string)($user_data['username'] ?? ''), 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Profile | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
    
    <div class="min-h-screen flex flex-col">
        <!-- Unified Dark Portal Header -->
        <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-12">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-serif font-bold">My Profile</h1>
                    <p class="text-gray-400 mt-2">Manage your identity and security settings in the Ripal Design ecosystem.</p>
                </div>
                <div class="bg-rajkot-rust px-4 py-2 rounded-sm shadow-lg">
                    <span class="text-[10px] font-black uppercase tracking-widest text-white/80">Membership Identity</span>
                    <p class="text-xs font-bold text-white"><?php echo htmlspecialchars(strtoupper((string)$user_data['role'])); ?> (<?php echo date('Y', strtotime($user_data['joined_date'])); ?>)</p>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
            
            <?php if ($message): ?>
                <?php $safeMessageType = in_array($message_type, ['success', 'danger', 'warning', 'info'], true) ? $message_type : 'info'; ?>
                <div class="alert alert-<?php echo esc_attr($safeMessageType); ?> shadow-premium mb-10 border-0 rounded-0 p-4 font-bold text-sm">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                
                <!-- Left Sidebar: Profile Summary -->
                <aside class="lg:col-span-4 space-y-8">
                    <div class="bg-white shadow-premium border border-gray-100 p-8 text-center">
                        <?php if (!empty($user_data['avatar'])): ?>
                            <img id="profileAvatarDisplay" src="<?php echo htmlspecialchars($user_data['avatar']); ?>" alt="Avatar" class="w-24 h-24 object-cover mx-auto mb-6 shadow-lg" onerror="this.style.display='none'; var el=document.getElementById('profileAvatarInitials'); if(el){ el.style.display='flex'; }">
                            <div id="profileAvatarInitials" class="w-24 h-24 bg-rajkot-rust text-white font-serif text-4xl font-bold flex items-center justify-center mx-auto mb-6 shadow-lg" style="display:none;">
                                <?php echo htmlspecialchars($avatar_initials); ?>
                            </div>
                        <?php else: ?>
                            <div id="profileAvatarDisplay" class="w-24 h-24 bg-rajkot-rust text-white font-serif text-4xl font-bold flex items-center justify-center mx-auto mb-6 shadow-lg">
                                <?php echo htmlspecialchars($avatar_initials); ?>
                            </div>
                        <?php endif; ?>

                        <form id="avatarFormSidebar" method="POST" enctype="multipart/form-data" class="mt-2" data-ajax="true">
                            <?php echo csrf_token_field(); ?>
                            <input type="hidden" name="update_profile" value="1">
                            <input type="hidden" name="avatar_only" value="1">
                            <input type="file" name="avatar" id="sidebarAvatarInput" accept="image/*" style="display:none">
                            <button type="button" id="avatarEditBtn" title="Edit profile photo" class="mx-auto inline-flex items-center gap-2 px-3 py-1 border rounded text-sm text-gray-600 hover:bg-gray-50">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                        </form>

                        <h2 class="text-2xl font-serif font-bold mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
                        <p class="text-sm text-gray-400 mb-6 font-mono">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                        
                        <div class="grid grid-cols-2 gap-4 border-t border-b border-gray-50 py-6 my-6">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Projects</span>
                                <span class="text-xl font-bold"><?php echo (int)$projectStats['total_projects']; ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Ratings</span>
                                <span class="text-xl font-bold text-approval-green"><?php echo $ratingStats['total_ratings'] > 0 ? number_format((float)$ratingStats['avg_rating'], 1) : '--'; ?></span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <a href="dashboard.php" class="w-full flex items-center justify-center gap-2 py-3 border border-gray-100 text-xs font-bold uppercase tracking-widest hover:border-rajkot-rust transition-all no-underline text-foundation-grey">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                            </a>
                            <a href="../public/logout.php" class="w-full flex items-center justify-center gap-2 py-3 border border-gray-100 text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition-all no-underline text-red-600">
                                <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                            </a>
                        </div>
                    </div>

                    <div class="bg-foundation-grey text-white p-8">
                        <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 mb-4">Security Overview</h3>
                        <p class="text-sm text-gray-300 mb-6 leading-relaxed">Ensure your credentials are updated periodically to maintain the integrity of your workspace.</p>
                        <div class="flex items-center gap-3 text-approval-green">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                            <span class="text-xs font-bold uppercase">Multi-factor enabled</span>
                        </div>
                    </div>
                </aside>

                <!-- Right Content: Form Sections -->
                <div class="lg:col-span-8 space-y-12">
                    <!-- Personal Info Section -->
                    <section class="bg-white shadow-premium border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-50">
                            <h3 class="text-xl font-serif font-bold">Personal Information</h3>
                        </div>
                        <form id="profileMainForm" method="POST" enctype="multipart/form-data" class="p-8 space-y-8" data-ajax="true">
                            <?php echo csrf_token_field(); ?>
                            <input type="hidden" name="update_profile" value="1">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Full Name</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Identity</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Phone Contact</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">System Role</label>
                                    <input type="text" value="<?php echo strtoupper($user_data['role']); ?>" disabled class="w-full p-4 bg-gray-100 border border-gray-100 text-gray-400 text-sm font-bold opacity-50 cursor-not-allowed">
                                </div>
                                
                            </div>
                            
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mailing Address</label>
                                <textarea name="address" rows="3" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">City</label>
                                    <input type="text" name="city" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">State</label>
                                    <input type="text" name="state" value="<?php echo htmlspecialchars($user_data['state'] ?? ''); ?>" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">ZIP Code</label>
                                    <input type="text" name="zip" value="<?php echo htmlspecialchars($user_data['zip'] ?? ''); ?>" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-50 flex justify-end">
                                <button type="submit" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-10 py-4 text-xs font-bold uppercase tracking-widest shadow-lg transition-all active:scale-95">
                                    Preserve Changes
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Security Section -->
                    <section class="bg-white shadow-premium border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-50">
                            <h3 class="text-xl font-serif font-bold">Projects and Members</h3>
                        </div>
                        <div class="p-8">
                            <?php if (empty($userProjects)): ?>
                                <p class="text-sm text-gray-500">No projects linked to this profile yet.</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($userProjects as $proj): ?>
                                        <div class="border border-gray-100 p-4 bg-gray-50/50">
                                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                                <div>
                                                    <p class="text-sm font-bold text-foundation-grey"><?php echo htmlspecialchars((string)$proj['name']); ?></p>
                                                    <p class="text-[11px] text-gray-500 uppercase tracking-wide"><?php echo htmlspecialchars((string)$proj['status']); ?> | Progress <?php echo (int)($proj['progress'] ?? 0); ?>%</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Members</p>
                                                    <p class="text-lg font-bold text-foundation-grey"><?php echo (int)($proj['member_count'] ?? 0); ?></p>
                                                </div>
                                            </div>
                                            <?php if ((int)($proj['member_count'] ?? 0) > 1): ?>
                                                <p class="mt-2 text-[11px] font-semibold text-approval-green uppercase tracking-wide">Multi-member project</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <?php if ($isOwnClientProfile): ?>
                        <section class="bg-white shadow-premium border border-gray-100 overflow-hidden">
                            <div class="px-8 py-6 border-b border-gray-50">
                                <h3 class="text-xl font-serif font-bold">Client Member Rating</h3>
                            </div>
                            <form method="POST" id="clientRatingForm" class="p-8 space-y-6">
                                <?php echo csrf_token_field(); ?>
                                <input type="hidden" name="submit_client_rating" value="1">

                                <?php if (empty($clientRateTargets)): ?>
                                    <p class="text-sm text-gray-500">No project members available for rating yet.</p>
                                <?php else: ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Project Member</label>
                                            <select name="member_id" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm" required>
                                                <option value="">Select member</option>
                                                <?php foreach ($clientRateTargets as $target): ?>
                                                    <?php $displayName = trim((string)($target['full_name'] ?? '')) !== '' ? (string)$target['full_name'] : (string)$target['username']; ?>
                                                    <option value="<?php echo (int)$target['id']; ?>"><?php echo htmlspecialchars($displayName . ' (' . $target['project_name'] . ')'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Rating</label>
                                            <select name="rating" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm" required>
                                                <option value="">Select rating</option>
                                                <option value="5">5 - Excellent</option>
                                                <option value="4">4 - Very Good</option>
                                                <option value="3">3 - Good</option>
                                                <option value="2">2 - Needs Improvement</option>
                                                <option value="1">1 - Poor</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Comment</label>
                                        <textarea name="comment" rows="3" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm" placeholder="Write your rating feedback" required></textarea>
                                    </div>

                                    <div class="pt-4 border-t border-gray-50 flex justify-end">
                                        <button type="submit" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-10 py-4 text-xs font-bold uppercase tracking-widest shadow-lg transition-all active:scale-95">
                                            Submit Rating
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </section>
                    <?php endif; ?>

                    <section class="bg-white shadow-premium border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between">
                            <h3 class="text-xl font-serif font-bold">Recent Ratings</h3>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total <?php echo (int)$ratingStats['total_ratings']; ?></span>
                        </div>
                        <div class="p-8">
                            <?php if (empty($recentRatings)): ?>
                                <p class="text-sm text-gray-500">No ratings found for this profile.</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentRatings as $entry): ?>
                                        <div class="border border-gray-100 p-4 bg-gray-50/50">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-sm font-bold text-foundation-grey"><?php echo (int)($entry['rating'] ?? 0); ?>/5</p>
                                                <p class="text-[11px] text-gray-500"><?php echo date('d M Y', strtotime((string)($entry['created_at'] ?? 'now'))); ?></p>
                                            </div>
                                            <p class="text-sm text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars((string)($entry['comment'] ?? ''))); ?></p>
                                            <p class="text-[11px] text-gray-500 mt-2">Rated by <?php echo htmlspecialchars((string)($entry['rated_by'] ?? 'Unknown')); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="bg-white shadow-premium border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-50">
                            <h3 class="text-xl font-serif font-bold">Account Security</h3>
                        </div>
                        <form method="POST" id="profilePasswordForm" class="p-8 space-y-8" data-ajax="true">
                            <?php echo csrf_token_field(); ?>
                            <input type="hidden" name="change_password" value="1">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">New Password</label>
                                    <input type="password" name="new_password" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Verify Password</label>
                                    <input type="password" name="confirm_password" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-50 flex justify-end">
                                <button type="submit" class="border-2 border-foundation-grey hover:bg-foundation-grey hover:text-white text-foundation-grey px-10 py-4 text-xs font-bold uppercase tracking-widest transition-all active:scale-95">
                                    Update Security Key
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </main>

        <script>
        document.addEventListener('DOMContentLoaded', function(){
            var editBtn = document.getElementById('avatarEditBtn');
            var fileInput = document.getElementById('sidebarAvatarInput');
            var avatarElem = document.getElementById('profileAvatarDisplay');

            if (editBtn && fileInput) {
                editBtn.addEventListener('click', function(){
                    fileInput.click();
                });
            }

            if (fileInput) {
                fileInput.addEventListener('change', function(){
                    var f = fileInput.files && fileInput.files[0];
                    if (!f) return;
                    var reader = new FileReader();
                    reader.onload = function(ev){
                        try {
                            if (avatarElem && avatarElem.tagName === 'IMG') {
                                avatarElem.src = ev.target.result;
                                avatarElem.style.display = '';
                                var initialsElem = document.getElementById('profileAvatarInitials');
                                if (initialsElem) initialsElem.style.display = 'none';
                            } else if (avatarElem) {
                                var img = document.createElement('img');
                                img.id = 'profileAvatarDisplay';
                                img.alt = 'Avatar';
                                img.className = 'w-24 h-24 object-cover mx-auto mb-6 shadow-lg';
                                img.src = ev.target.result;
                                img.onerror = function(){ this.style.display='none'; var el = document.getElementById('profileAvatarInitials'); if(el){ el.style.display='flex'; } };
                                avatarElem.replaceWith(img);
                            }
                        } catch (e) {
                            // ignore preview errors
                        }
                    };
                    reader.readAsDataURL(f);

                    // Auto-submit the small avatar form to persist on server (triggers AJAX submit handler)
                    var form = document.getElementById('avatarFormSidebar');
                    if (form) {
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            var ev = new Event('submit', { bubbles: true, cancelable: true });
                            var prevented = !form.dispatchEvent(ev);
                            if (!prevented) {
                                try { form.submit(); } catch (e) {}
                            }
                        }
                    }
                });
            }
        });
        </script>

        <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
    </div>

</body>
</html>
