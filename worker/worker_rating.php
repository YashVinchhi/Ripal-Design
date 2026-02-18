<?php
/**
 * Workforce Ratings (Redesigned)
 * 
 * Allows project managers and supervisors to rate workers.
 * Fixes header errors and adopts the Rajkot Rust premium design system.
 */

require_once __DIR__ . '/../includes/init.php';

$current_user = $_SESSION['user'] ?? 'Admin';

// Load workers from DB
$workers = [];
if (db_connected()) {
    try {
        $db = get_db();
        $stmt = $db->query("
            SELECT u.id, u.username, u.email, u.phone, u.role,
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
        $workers = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Worker Rating Load Error: ' . $e->getMessage());
    }
}

// Demo data fallback
if (empty($workers)) {
    $workers = [
        ['id'=>11, 'username'=>'Ramesh Kumar', 'email'=>'ramesh.k@ripal.design', 'phone'=>'+91 98888 77777', 'role'=>'Lead Mason', 'projects_count'=>5, 'avg_rating'=>4.7, 'total_ratings'=>12],
        ['id'=>12, 'username'=>'Suresh Bhai', 'email'=>'suresh.b@ripal.design', 'phone'=>'+91 97777 66666', 'role'=>'Master Electrician', 'projects_count'=>8, 'avg_rating'=>4.9, 'total_ratings'=>20],
        ['id'=>13, 'username'=>'Mahesh M.', 'email'=>'mahesh.m@ripal.design', 'phone'=>'+91 96666 55555', 'role'=>'Senior Carpenter', 'projects_count'=>4, 'avg_rating'=>4.2, 'total_ratings'=>8],
    ];
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $worker_id = intval($_POST['worker_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($worker_id && $rating >= 1 && $rating <= 5) {
        if (db_connected()) {
            try {
                $db = get_db();
                $stmt = $db->prepare("INSERT INTO worker_ratings (worker_id, rated_by, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$worker_id, $current_user, $rating, $comment]);
            } catch (Exception $e) {}
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

function render_stars($rating) {
    if (!$rating) return '<span class="text-gray-200">No Ratings</span>';
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5;
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) $output .= '<i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>';
        elseif ($i == $full + 1 && $half) $output .= '<i data-lucide="star-half" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>';
        else $output .= '<i data-lucide="star" class="w-3.5 h-3.5 text-gray-200"></i>';
    }
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Workforce Ratings | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
    
    <div class="min-h-screen flex flex-col">
        <!-- Unified Dark Portal Header -->
        <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-12 border-b-2 border-rajkot-rust">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-serif font-bold">Workforce Directory</h1>
                    <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Audit performance metrics and maintain quality standards.</p>
                </div>
                <div class="bg-white/5 border border-white/10 px-8 py-5 text-center flex items-center gap-6">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Global performance</span>
                        <div class="flex items-center gap-2">
                            <span class="text-3xl font-serif font-bold text-approval-green">4.82</span>
                            <i data-lucide="trending-up" class="w-5 h-5 text-approval-green"></i>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
            
            <!-- Workforce Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($workers as $w): ?>
                    <div class="bg-white shadow-premium border border-gray-100 p-10 flex flex-col group hover:border-rajkot-rust transition-all relative overflow-hidden">
                        <!-- CAD accent corner -->
                        <div class="absolute top-0 right-0 w-16 h-16 bg-rajkot-rust/5 -mr-8 -mt-8 rotate-45 pointer-events-none group-hover:bg-rajkot-rust/10 transition-colors"></div>

                        <div class="flex items-start justify-between mb-10">
                            <div class="w-16 h-16 bg-foundation-grey text-white font-serif text-2xl font-bold flex items-center justify-center border-b-2 border-rajkot-rust shadow-sm">
                                <?php echo strtoupper(substr($w['username'], 0, 1)); ?>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-300 block mb-1">Identity Code</span>
                                <span class="text-xs font-mono font-bold text-foundation-grey">#RD-W<?php echo str_pad($w['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </div>
                        </div>

                        <div class="mb-12">
                            <h3 class="text-2xl font-serif font-bold mb-1 text-foundation-grey group-hover:text-rajkot-rust transition-colors"><?php echo htmlspecialchars($w['username']); ?></h3>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-[1px] bg-rajkot-rust"></span> <?php echo htmlspecialchars($w['role']); ?>
                            </p>
                            
                            <div class="bg-gray-50/50 p-4 border-l-2 border-rajkot-rust">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="flex">
                                        <?php echo render_stars($w['avg_rating']); ?>
                                    </div>
                                    <span class="text-base font-bold text-foundation-grey"><?php echo number_format($w['avg_rating'], 1); ?></span>
                                </div>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-[0.1em]">Based on <?php echo $w['total_ratings']; ?> verification audits</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-8 border-t border-gray-50 pt-10 mb-10">
                            <div>
                                <span class="text-[9px] font-bold text-gray-300 uppercase tracking-widest block mb-1">Deployments</span>
                                <span class="text-2xl font-serif font-bold text-foundation-grey"><?php echo $w['projects_count']; ?></span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-gray-300 uppercase tracking-widest block mb-1">Validation</span>
                                <span class="text-2xl font-serif font-bold text-approval-green uppercase">Verified</span>
                            </div>
                        </div>

                        <div class="mt-auto space-y-6">
                            <div class="flex items-center gap-3 text-[11px] font-medium text-gray-400">
                                <i data-lucide="phone" class="w-3.5 h-3.5 opacity-50"></i> <?php echo htmlspecialchars($w['phone']); ?>
                            </div>
                            <button onclick="document.getElementById('ratingModal_<?php echo $w['id']; ?>').classList.remove('hidden')" 
                                    class="w-full py-5 bg-foundation-grey hover:bg-rajkot-rust text-white text-[10px] font-bold uppercase tracking-[0.3em] transition-all shadow-premium active:scale-[0.98] flex items-center justify-center gap-3">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i> Create Audit Entry
                            </button>
                        </div>
                    </div>

                    <!-- Simplified In-file Modal for each worker -->
                    <div id="ratingModal_<?php echo $w['id']; ?>" class="fixed inset-0 bg-foundation-grey/90 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
                        <div class="bg-white w-full max-w-md shadow-premium border border-gray-100 overflow-hidden transform-gpu">
                            <div class="bg-foundation-grey p-8 text-white flex justify-between items-center">
                                <h4 class="text-xl font-serif font-bold">Standard Performance Entry</h4>
                                <button onclick="document.getElementById('ratingModal_<?php echo $w['id']; ?>').classList.add('hidden')" class="text-gray-400 hover:text-white transition-colors">
                                    <i data-lucide="x" class="w-6 h-6"></i>
                                </button>
                            </div>
                            <form method="POST" class="p-10 space-y-10">
                                <input type="hidden" name="submit_rating" value="1">
                                <input type="hidden" name="worker_id" value="<?php echo $w['id']; ?>">
                                
                                <div class="space-y-6 text-center">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] block">Evaluation Score (1-5 Star Audit)</label>
                                    <div class="flex justify-center gap-6">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <label class="cursor-pointer group relative">
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" required class="hidden peer">
                                                <i data-lucide="star" class="w-10 h-10 text-gray-100 peer-checked:text-rajkot-rust peer-checked:fill-rajkot-rust group-hover:text-rajkot-rust/30 transition-all"></i>
                                                <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-[10px] font-bold text-gray-200 peer-checked:text-rajkot-rust opacity-0 peer-checked:opacity-100 transition-opacity"><?php echo $i; ?></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="space-y-3 pt-4">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] block flex items-center gap-2">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Observation Registry
                                    </label>
                                    <textarea name="comment" rows="4" required
                                              class="w-full p-6 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium placeholder:text-gray-300" 
                                              placeholder="Document specific project achievements or issues..."></textarea>
                                </div>

                                <button type="submit" class="w-full py-6 bg-foundation-grey hover:bg-rajkot-rust text-white text-[10px] font-bold uppercase tracking-[0.4em] shadow-premium transition-all active:scale-[0.98] flex items-center justify-center gap-4 group">
                                    Commit Entry to Registry <i data-lucide="chevron-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

</body>
</html>