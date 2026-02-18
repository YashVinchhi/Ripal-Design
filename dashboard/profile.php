<?php
/**
 * User Profile Page (Redesigned)
 * 
 * Allows users to view and edit their profile information.
 * Fixes header errors and adheres to the Rajkot Rust immersive design.
 */

require_once __DIR__ . '/../includes/init.php';

// Get current user info from session
$user = $_SESSION['user'] ?? 'employee01';
$user_id = $_SESSION['user_id'] ?? 0;

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

// Load user data from database if available
if (db_connected() && $user_id > 0) {
    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $db_user = $stmt->fetch();
        if ($db_user) {
            $user_data = array_merge($user_data, $db_user);
        }
    } catch (Exception $e) {
        error_log('Profile Load Error: ' . $e->getMessage());
    }
}

// Demo data if DB is empty or not connected
if (empty($user_data['full_name'])) {
    $user_data['full_name'] = 'Yashbhai Vinchhi';
    $user_data['email'] = 'yash.vinchhi@ripal.design';
    $user_data['joined_date'] = '2024-01-15';
}

$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    
    if (empty($full_name) || empty($email)) {
        $message = 'Full Name and Email are required.';
        $message_type = 'danger';
    } else {
        if (db_connected() && $user_id > 0) {
            try {
                $db = get_db();
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $address, $city, $state, $zip, $user_id]);
                $message = 'Profile updated successfully!';
                $message_type = 'success';
                // Refresh data
                $user_data['full_name'] = $full_name;
                $user_data['email'] = $email;
                $user_data['phone'] = $phone;
                $user_data['address'] = $address;
                $user_data['city'] = $city;
                $user_data['state'] = $state;
                $user_data['zip'] = $zip;
            } catch (Exception $e) {
                $message = 'Error updating profile: ' . $e->getMessage();
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
                $message = 'Error changing password: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = 'Password changed (Demo Mode).';
            $message_type = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Profile | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
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
                    <p class="text-xs font-bold text-white"><?php echo strtoupper($user_data['role']); ?> (<?php echo date('Y', strtotime($user_data['joined_date'])); ?>)</p>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> shadow-premium mb-10 border-0 rounded-0 p-4 font-bold text-sm">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                
                <!-- Left Sidebar: Profile Summary -->
                <aside class="lg:col-span-4 space-y-8">
                    <div class="bg-white shadow-premium border border-gray-100 p-8 text-center">
                        <div class="w-24 h-24 bg-rajkot-rust text-white font-serif text-4xl font-bold flex items-center justify-center mx-auto mb-6 shadow-lg">
                            <?php echo strtoupper(substr($user_data['username'], 0, 1)); ?>
                        </div>
                        <h2 class="text-2xl font-serif font-bold mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
                        <p class="text-sm text-gray-400 mb-6 font-mono">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                        
                        <div class="grid grid-cols-2 gap-4 border-t border-b border-gray-50 py-6 my-6">
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Projects</span>
                                <span class="text-xl font-bold">12</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Ratings</span>
                                <span class="text-xl font-bold text-approval-green">4.9</span>
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
                        <form method="POST" class="p-8 space-y-8">
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
                            <h3 class="text-xl font-serif font-bold">Account Security</h3>
                        </div>
                        <form method="POST" class="p-8 space-y-8">
                            <input type="hidden" name="change_password" value="1">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">New Password</label>
                                    <input type="password" name="new_password" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm" placeholder="••••••••">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Verify Password</label>
                                    <input type="password" name="confirm_password" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm" placeholder="••••••••">
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

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

</body>
</html>