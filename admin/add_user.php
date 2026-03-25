<?php
/**
 * Add New User Page
 * 
 * Allows administrators to create new user accounts in the system.
 * Adheres to the "Rajkot Rust" immersive design system.
 * 
 * @package RipalDesign
 * @subpackage Admin
 */

require_once __DIR__ . '/../includes/init.php';
require_login();
require_role('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'client';
    
    // Simple validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db = get_db();
            if ($db) {
                // Check if user already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'A user with this email already exists.';
                } else {
                    // In a real app, combine firstName and lastName if needed, or update schema
                    // For now, we'll use email as username as seen in signup/login context
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
                    $stmt->execute([$email, $passwordHash, $role]);
                    
                    set_flash('User created successfully!', 'success');
                    header('Location: user_management.php');
                    exit;
                }
            } else {
                // Demo mode fallback if DB not connected
                set_flash('User created successfully! (Demo Mode)', 'success');
                header('Location: user_management.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Add user failed: ' . $e->getMessage());
            $error = 'Unable to create user right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add New User | Ripal Design</title>
    <?php require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
    
    <div class="min-h-screen flex flex-col">
        <!-- Unified Dark Portal Header -->
        <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
            <div class="max-w-3xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-serif font-bold">Add New User</h1>
                    <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Identity Creation Portal</p>
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
                    <!-- Name Group -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10">
                        <div class="space-y-3">
                            <label for="firstName" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i> First Name
                            </label>
                            <input type="text" id="firstName" name="firstName" required
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                                placeholder="e.g. Ramesh">
                        </div>
                        <div class="space-y-3">
                            <label for="lastName" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="user" class="w-3.5 h-3.5 opacity-0 hidden md:inline"></i> Last Name
                            </label>
                            <input type="text" id="lastName" name="lastName" required
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                                placeholder="e.g. Kumar">
                        </div>
                    </div>

                    <!-- Contact Group -->
                    <div class="space-y-3">
                        <label for="email" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                            <i data-lucide="mail" class="w-3.5 h-3.5"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                            placeholder="user@ripaldesign.in">
                    </div>

                    <!-- Security & Access -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10">
                        <div class="space-y-3">
                            <label for="password" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="lock" class="w-3.5 h-3.5"></i> Security Password
                            </label>
                            <input type="password" id="password" name="password" required minlength="8"
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium"
                                placeholder="Min. 8 characters">
                        </div>
                        <div class="space-y-3">
                            <label for="role" class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i data-lucide="shield" class="w-3.5 h-3.5"></i> System Authorization
                            </label>
                            <select id="role" name="role" required
                                class="w-full px-5 py-3 md:py-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-bold uppercase tracking-widest cursor-pointer">
                                <option value="client">Client (Guest/Govt)</option>
                                <option value="worker">Worker (Mobile/Field)</option>
                                <option value="employee">Employee (Architect/PM)</option>
                                <option value="admin">Administrator (Firm Owner)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-6 md:pt-10">
                        <button type="submit" 
                            class="w-full bg-foundation-grey hover:bg-rajkot-rust text-white py-5 md:py-6 text-[10px] font-bold uppercase tracking-[0.3em] shadow-premium transition-all flex items-center justify-center gap-4 active:scale-[0.98] group">
                            Create System Identity <i data-lucide="chevron-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

    <script>
        // Simple client-side validation feedback
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Initializing Account...';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
        });
    </script>
</body>
</html>
