<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
/**
 * Add New User Page
 * 
 * Allows administrators to create new user accounts in the system.
 * Adheres to the "Rajkot Rust" immersive design system.
 * 
 * @package RipalDesign
 * @subpackage Admin
 */

require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();
require_role('admin');

$error = '';
$success = '';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $editId > 0;

$form = [
    'firstName' => '',
    'lastName' => '',
    'username' => '',
    'email' => '',
    'role' => 'client',
    'password' => '',
];

/**
 * Build a normalized username base from names.
 */
function build_username_base(string $firstName, string $lastName): string
{
    $base = strtolower(trim($firstName . '.' . $lastName));
    $base = preg_replace('/[^a-z0-9._-]+/', '', $base ?? '') ?: '';
    if (strlen($base) < 3) {
        $base = 'user';
    }
    return substr($base, 0, 30);
}

/**
 * Ensure username uniqueness in users table.
 */
function next_available_username(PDO $db, string $base, int $excludeId = 0): string
{
    $base = $base !== '' ? $base : 'user';
    $candidate = substr($base, 0, 30);
    $suffix = 1;

    while (true) {
        if ($excludeId > 0) {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
            $stmt->execute([$candidate, $excludeId]);
        } else {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$candidate]);
        }

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $candidate;
        }

        $suffixText = (string)$suffix;
        $maxBaseLen = max(1, 30 - strlen($suffixText));
        $candidate = substr($base, 0, $maxBaseLen) . $suffixText;
        $suffix++;
    }
}

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $db = get_db();
        if ($db) {
            $stmt = $db->prepare('SELECT id, first_name, last_name, full_name, email, username, role FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$editId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                set_flash('User not found.', 'error');
                header('Location: user_management.php');
                exit;
            }

            $firstName = trim((string)($user['first_name'] ?? ''));
            $lastName = trim((string)($user['last_name'] ?? ''));

            if ($firstName === '' && $lastName === '') {
                $fullName = trim((string)($user['full_name'] ?? ''));
                if ($fullName !== '') {
                    $parts = preg_split('/\s+/', $fullName);
                    $firstName = (string)($parts[0] ?? '');
                    $lastName = (string)implode(' ', array_slice($parts, 1));
                }
            }

            $form['firstName'] = $firstName;
            $form['lastName'] = $lastName;
            $form['username'] = (string)($user['username'] ?? '');
            $form['email'] = (string)($user['email'] ?? $user['username'] ?? '');
            $form['role'] = (string)($user['role'] ?? 'client');
        }
    } catch (PDOException $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'Load user for edit failed', ['exception' => $e->getMessage()]);
        }
        set_flash('Unable to load user for editing right now.', 'error');
        header('Location: user_management.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $editId = isset($_POST['id']) ? (int)$_POST['id'] : $editId;
    $isEdit = $editId > 0;

    $form['firstName'] = trim($_POST['firstName'] ?? '');
    $form['lastName'] = trim($_POST['lastName'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['password'] = $_POST['password'] ?? '';
    $form['role'] = $_POST['role'] ?? 'client';

    $firstName = $form['firstName'];
    $lastName = $form['lastName'];
    $email = $form['email'];
    $password = $form['password'];
    $role = $form['role'];
    
    // Simple validation
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = 'All fields are required.';
    } elseif (!$isEdit && empty($password)) {
        $error = 'Password is required for new users.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db = get_db();
            if ($db) {
                if ($isEdit) {
                    $checkStmt = $db->prepare('SELECT id, username FROM users WHERE id = ? LIMIT 1');
                    $checkStmt->execute([$editId]);
                    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$existingUser) {
                        $error = 'User not found.';
                    } else {
                        $currentUsername = trim((string)($existingUser['username'] ?? ''));
                        if ($currentUsername === '') {
                            $currentUsername = next_available_username($db, build_username_base($firstName, $lastName), $editId);
                        }

                        $dupeStmt = $db->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
                        $dupeStmt->execute([$currentUsername, $email, $editId]);

                        if ($dupeStmt->fetch()) {
                            $error = 'A user with this email already exists.';
                        } else {
                            $fullName = trim($firstName . ' ' . $lastName);
                            $actorId = (int)(current_user()['id'] ?? 0);

                            $db->beginTransaction();

                            try {
                                if ($password !== '') {
                                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                                    $updateStmt = $db->prepare('UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, full_name = ?, role = ?, password_hash = ?, updated_at = NOW() WHERE id = ?');
                                    $updateStmt->execute([$currentUsername, $email, $firstName, $lastName, $fullName, $role, $passwordHash, $editId]);
                                } else {
                                    $updateStmt = $db->prepare('UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, full_name = ?, role = ?, updated_at = NOW() WHERE id = ?');
                                    $updateStmt->execute([$currentUsername, $email, $firstName, $lastName, $fullName, $role, $editId]);
                                }

                                if (!auth_sync_user_role_links($editId, $role, $actorId > 0 ? $actorId : null)) {
                                    // Keep core user update successful; RBAC sync issues are logged for follow-up.
                                    if (function_exists('app_log')) {
                                        app_log('warning', 'Role sync warning for updated user', ['user_id' => (int)$editId]);
                                    }
                                }

                                $db->commit();
                            } catch (Throwable $inner) {
                                if ($db->inTransaction()) {
                                    $db->rollBack();
                                }
                                throw $inner;
                            }

                            set_flash('User updated successfully!', 'success');
                            header('Location: user_management.php');
                            exit;
                        }
                    }
                } else {
                    $emailCheckStmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $emailCheckStmt->execute([$email]);
                    if ($emailCheckStmt->fetch()) {
                        $error = 'A user with this email already exists.';
                    } else {
                        $newUsername = next_available_username($db, build_username_base($firstName, $lastName));
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $fullName = trim($firstName . ' ' . $lastName);
                        $actorId = (int)(current_user()['id'] ?? 0);

                        $db->beginTransaction();

                        try {
                            $stmt = $db->prepare('INSERT INTO users (username, full_name, first_name, last_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
                            $stmt->execute([$newUsername, $fullName, $firstName, $lastName, $email, $passwordHash, $role]);

                            $newUserId = (int)$db->lastInsertId();
                            if ($newUserId > 0) {
                                if (!auth_sync_user_role_links($newUserId, $role, $actorId > 0 ? $actorId : null)) {
                                    // Keep user creation successful; RBAC sync issues are logged for follow-up.
                                    if (function_exists('app_log')) {
                                        app_log('warning', 'Role sync warning for new user', ['user_id' => (int)$newUserId]);
                                    }
                                }
                            }

                            $db->commit();
                        } catch (Throwable $inner) {
                            if ($db->inTransaction()) {
                                $db->rollBack();
                            }
                            throw $inner;
                        }

                        set_flash('User created successfully!', 'success');
                        header('Location: user_management.php');
                        exit;
                    }
                }
            } else {
                // Demo mode fallback if DB not connected
                set_flash($isEdit ? 'User updated successfully! (Demo Mode)' : 'User created successfully! (Demo Mode)', 'success');
                header('Location: user_management.php');
                exit;
            }
        } catch (Throwable $e) {
            if (function_exists('app_log')) {
                app_log('error', 'Add user failed', ['exception' => $e->getMessage()]);
            }
            $error = $isEdit
                ? 'Unable to update user right now. Please try again.'
                : 'Unable to create user right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $isEdit ? 'Edit User | Ripal Design' : 'Add New User | Ripal Design'; ?></title>
    <?php require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
    
    <div class="min-h-screen flex flex-col">
        <!-- Unified Dark Portal Header -->
        <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
            <div class="max-w-3xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-serif font-bold"><?php echo $isEdit ? 'Edit User Permissions' : 'Add New User'; ?></h1>
                    <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70"><?php echo $isEdit ? 'Identity Update Portal' : 'Identity Creation Portal'; ?></p>
                </div>
                <div>
                    <a href="user_management.php" class="bg-white/10 hover:bg-white/20 border border-white/10 md:border-0 md:bg-transparent text-white md:text-gray-400 hover:text-rajkot-rust px-4 py-2 md:p-0 rounded transition-colors flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] no-underline">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> <span class="md:inline">Back to Registry</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <div class="bg-white shadow-premium border border-gray-100 p-6 md:p-12 relative overflow-hidden">
                <!-- CAD-style background accent -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-rajkot-rust/5 -mr-16 -mt-16 rotate-45 pointer-events-none"></div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-rajkot-rust text-foundation-grey p-4 md:p-5 mb-6 md:mb-8 text-[12px] font-bold flex items-center gap-4" role="alert">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-rajkot-rust shrink-0"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-approval-green/10 border-l-4 border-approval-green text-foundation-grey p-4 md:p-5 mb-6 md:mb-8 text-[12px] font-bold flex items-center gap-4" role="alert">
                        <i data-lucide="check-circle" class="w-5 h-5 text-approval-green shrink-0"></i>
                        <span><?php echo htmlspecialchars($success); ?> Redirecting to registry...</span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6 md:space-y-10" id="addUserForm">
                    <?php echo csrf_token_field(); ?>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editId; ?>">
                    <?php endif; ?>
                    <!-- Name Group -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10">
                        <div class="space-y-3">
                            <label for="firstName" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i> First Name
                            </label>
                            <input type="text" id="firstName" name="firstName" required
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                                placeholder="e.g. Ramesh"
                                value="<?php echo htmlspecialchars($form['firstName']); ?>">
                        </div>
                        <div class="space-y-3">
                            <label for="lastName" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="user" class="w-3.5 h-3.5 opacity-0 hidden md:inline"></i> Last Name
                            </label>
                            <input type="text" id="lastName" name="lastName" required
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                                placeholder="e.g. Kumar"
                                value="<?php echo htmlspecialchars($form['lastName']); ?>">
                        </div>
                    </div>

                    <!-- Contact Group -->
                    <div class="space-y-3">
                        <label for="email" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                            <i data-lucide="mail" class="w-3.5 h-3.5"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                            placeholder="user@ripaldesign.studio"
                            value="<?php echo htmlspecialchars($form['email']); ?>">
                    </div>

                    <!-- Security & Access -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10">
                        <div class="space-y-3">
                            <label for="password" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="lock" class="w-3.5 h-3.5"></i> <?php echo $isEdit ? 'Security Password (Optional)' : 'Security Password'; ?>
                            </label>
                            <input type="password" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?> minlength="8"
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                                placeholder="<?php echo $isEdit ? 'Leave blank to keep current password' : 'Min. 8 characters'; ?>">
                        </div>
                        <div class="space-y-3">
                            <label for="role" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="shield" class="w-3.5 h-3.5"></i> System Authorization
                            </label>
                            <select id="role" name="role" required
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-bold uppercase tracking-widest cursor-pointer">
                                <option value="client" <?php echo $form['role'] === 'client' ? 'selected' : ''; ?>>Client (Guest/Govt)</option>
                                <option value="worker" <?php echo $form['role'] === 'worker' ? 'selected' : ''; ?>>Worker (Mobile/Field)</option>
                                <option value="employee" <?php echo $form['role'] === 'employee' ? 'selected' : ''; ?>>Employee (Architect/PM)</option>
                                <option value="admin" <?php echo $form['role'] === 'admin' ? 'selected' : ''; ?>>Administrator (Firm Owner)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-6 md:pt-10">
                        <button type="submit" 
                            class="w-full bg-foundation-grey hover:bg-rajkot-rust text-white py-5 md:py-6 text-[10px] font-bold uppercase tracking-[0.3em] shadow-premium transition-all flex items-center justify-center gap-4 active:scale-[0.98] group">
                            <?php echo $isEdit ? 'Update Identity Permissions' : 'Create System Identity'; ?> <i data-lucide="chevron-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
    </div>

    <script>
        // Simple client-side validation feedback
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            const isEditMode = <?php echo $isEdit ? 'true' : 'false'; ?>;
            btn.innerHTML = isEditMode
                ? '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Updating Identity...'
                : '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Initializing Account...';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
        });
    </script>
</body>
</html>
