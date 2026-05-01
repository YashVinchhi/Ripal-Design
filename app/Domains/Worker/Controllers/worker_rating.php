<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
/**
 * Workforce Ratings (Redesigned)
 * 
 * Allows project managers and supervisors to rate workers.
 * Fixes header errors and adopts the Rajkot Rust premium design system.
 */

require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_once PROJECT_ROOT . '/app/Core/Services/scoring.php';

require_login();

$currentUser = current_user();
$current_user = $currentUser['username'] ?? ($currentUser['email'] ?? 'Admin');
$isAdmin = strtolower((string)($currentUser['role'] ?? '')) === 'admin';

// Load workers and compute scores from metric events
$workers = [];
$allRatings = [];
if (db_connected()) {
    try {
        $db = get_db();
        // Fetch basic worker info and project counts
        $stmt = $db->query("SELECT u.id, u.username, u.email, u.phone, u.role, COUNT(DISTINCT pa.project_id) as projects_count, COALESCE(ws.decision_score,0) AS decision_score, COALESCE(ws.final_score,0) AS final_score, COALESCE(ws.confidence,0) AS confidence, COALESCE(ws.risk,0) AS risk, (SELECT COUNT(*) FROM worker_metric_events wme WHERE wme.worker_id = u.id) AS events_count FROM users u LEFT JOIN project_assignments pa ON pa.worker_id = u.id LEFT JOIN worker_scores ws ON ws.worker_id = u.id WHERE u.role <> 'admin' GROUP BY u.id ORDER BY u.username ASC");
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // For admin: fetch latest metric events
        if ($isAdmin) {
            $ratingsStmt = $db->query("SELECT wme.*, u.username AS member_name, u.role AS member_role FROM worker_metric_events wme INNER JOIN users u ON u.id = wme.worker_id ORDER BY wme.created_at DESC LIMIT 200");
            $allRatings = $ratingsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'Worker rating load error', ['exception' => $e->getMessage()]);
        }
    }
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    require_csrf();
    $worker_id = intval($_POST['worker_id'] ?? 0);
    // collect metrics (expected 0-10)
    $metrics = [
        'charges_efficiency' => floatval($_POST['charges_efficiency'] ?? 0),
        'work_quality' => floatval($_POST['work_quality'] ?? 0),
        'experience' => floatval($_POST['experience'] ?? 0),
        'speed_timing' => floatval($_POST['speed_timing'] ?? 0),
        'reliability' => floatval($_POST['reliability'] ?? 0),
        'rework_rate' => floatval($_POST['rework_rate'] ?? 0),
        'communication' => floatval($_POST['communication'] ?? 0),
        'client_feedback' => floatval($_POST['client_feedback'] ?? 0),
        'flexibility' => floatval($_POST['flexibility'] ?? 0),
        'safety' => floatval($_POST['safety'] ?? 0),
    ];

    if ($worker_id && db_connected()) {
        try {
            $db = get_db();
            $stmt = $db->prepare("INSERT INTO worker_metric_events (worker_id, project_id, rated_by, charges_efficiency, work_quality, experience, speed_timing, reliability, rework_rate, communication, client_feedback, flexibility, safety, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            $stmt->execute([
                $worker_id,
                $project_id ?: null,
                $current_user,
                $metrics['charges_efficiency'],
                $metrics['work_quality'],
                $metrics['experience'],
                $metrics['speed_timing'],
                $metrics['reliability'],
                $metrics['rework_rate'],
                $metrics['communication'],
                $metrics['client_feedback'],
                $metrics['flexibility'],
                $metrics['safety']
            ]);

            // Recalculate aggregate scores for this worker
            $evStmt = $db->prepare('SELECT * FROM worker_metric_events WHERE worker_id = ? ORDER BY created_at DESC');
            $evStmt->execute([$worker_id]);
            $events = $evStmt->fetchAll(PDO::FETCH_ASSOC);

            $normalizedEvents = [];
            foreach ($events as $ev) {
                $p = sc_compute_wps($ev);
                $normalizedEvents[] = ['p' => $p, 'created_at' => $ev['created_at']];
            }

            // Remove statistical outliers before aggregation (Layer 11)
            list($filteredEvents, $outlierCount) = sc_filter_outliers($normalizedEvents, 1.5);

            $timeWeighted = sc_time_weighted_score($filteredEvents, 0.4);
            $consistency = sc_consistency($filteredEvents);
            // similarityWeighted: for now attempt category-aware similarity when project context available
            $similarityWeighted = sc_similarity_weighted($filteredEvents, null, 0.4);

            // availability approximation: count of events with reliability >= 7 over assigned projects (use original events)
            $onTimeCount = 0;
            foreach ($events as $ev) { if (isset($ev['reliability']) && floatval($ev['reliability']) >= 7.0) $onTimeCount++; }
            $projectCountRow = $db->prepare('SELECT COUNT(*) AS c FROM project_assignments WHERE worker_id = ?');
            $projectCountRow->execute([$worker_id]);
            $projectCount = (int)($projectCountRow->fetchColumn() ?: 0);
            $availability = sc_availability_factor($onTimeCount, $projectCount ?: max(1, count($events)));

            // risk proxies using average metrics (original events)
            $avgSpeed = 0; $avgRework = 0; $n=0;
            foreach ($events as $ev) { $n++; $avgSpeed += floatval($ev['speed_timing']); $avgRework += floatval($ev['rework_rate']); }
            if ($n>0) { $avgSpeed /= $n; $avgRework /= $n; }
            $risk = sc_compute_risk_worker(['speed_timing' => $avgSpeed, 'rework_rate_metric' => $avgRework]);

            $final = sc_final_score($timeWeighted, $consistency, $similarityWeighted, $availability);
            $confidence = sc_confidence($consistency, count($filteredEvents), 5);
            $decision = sc_decision_score($final, $risk, $confidence);

            // Upsert into worker_scores
            $up = $db->prepare('INSERT INTO worker_scores (worker_id, final_score, risk, confidence, decision_score, last_computed_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE final_score = VALUES(final_score), risk = VALUES(risk), confidence = VALUES(confidence), decision_score = VALUES(decision_score), last_computed_at = NOW()');
            $up->execute([$worker_id, $final, $risk, $confidence, $decision]);

        } catch (Exception $e) {
            if (function_exists('app_log')) app_log('error','worker rating save failed',['ex'=>$e->getMessage()]);
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function render_stars($rating)
{
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
    <?php $HEADER_MODE = 'dashboard';
    require_once PROJECT_ROOT . '/Common/header.php'; ?>
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
                <?php foreach ($workers as $w): ?>
                    <div class="bg-white shadow-premium border border-gray-100 p-10 flex flex-col group hover:border-rajkot-rust transition-all relative overflow-hidden">
                        <!-- CAD accent corner -->
                        <div class="absolute top-0 right-0 w-16 h-16 bg-rajkot-rust/5 -mr-8 -mt-8 rotate-45 pointer-events-none group-hover:bg-rajkot-rust/10 transition-colors"></div>

                        <div class="flex items-start justify-between mb-10">
                            <div class="w-16 h-16 bg-foundation-grey text-white font-serif text-2xl font-bold flex items-center justify-center border-b-2 border-rajkot-rust shadow-sm">
                                <?php echo htmlspecialchars(strtoupper(substr((string)$w['username'], 0, 1))); ?>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-300 block mb-1">Identity Code</span>
                                <span class="text-xs font-mono font-bold text-foundation-grey">#RD-W<?php echo str_pad((string)(int)$w['id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </div>
                        </div>

                        <div class="mb-12">
                            <h3 class="text-2xl font-serif font-bold mb-1 text-foundation-grey group-hover:text-rajkot-rust transition-colors"><?php echo htmlspecialchars($w['username']); ?></h3>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-[1px] bg-rajkot-rust"></span> <?php echo htmlspecialchars($w['role']); ?>
                            </p>

                            <div class="bg-gray-50/50 p-4 border-l-2 border-rajkot-rust">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Decision Score</span>
                                            <span class="text-2xl font-serif font-bold text-approval-green"><?php echo isset($w['decision_score']) ? (round(floatval($w['decision_score']) * 100, 1) . '%') : 'N/A'; ?></span>
                                        </div>
                                        <div class="ml-6">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Events</span>
                                            <span class="text-lg font-bold text-foundation-grey"><?php echo isset($w['events_count']) ? (int)$w['events_count'] : 0; ?></span>
                                        </div>
                                    </div>
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-[0.1em]">Final: <?php echo isset($w['final_score']) ? round(floatval($w['final_score']) * 100,1) . '%' : 'N/A'; ?> • Confidence: <?php echo isset($w['confidence']) ? round(floatval($w['confidence'])*100,1) . '%' : '0%'; ?> • Risk: <?php echo isset($w['risk']) ? round(floatval($w['risk'])*100,1) . '%' : '0%'; ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-8 border-t border-gray-50 pt-10 mb-10">
                            <div>
                                <span class="text-[9px] font-bold text-gray-300 uppercase tracking-widest block mb-1">Deployments</span>
                                <span class="text-2xl font-serif font-bold text-foundation-grey"><?php echo (int)$w['projects_count']; ?></span>
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
                            <button onclick="document.getElementById('ratingModal_<?php echo (int)$w['id']; ?>').classList.remove('hidden')"
                                class="w-full py-5 bg-foundation-grey hover:bg-rajkot-rust text-white text-[10px] font-bold uppercase tracking-[0.3em] transition-all shadow-premium active:scale-[0.98] flex items-center justify-center gap-3">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i> Create Audit Entry
                            </button>
                        </div>
                    </div>

                    <!-- Simplified In-file Modal for each worker -->
                    <div id="ratingModal_<?php echo (int)$w['id']; ?>" class="fixed inset-0 bg-foundation-grey/90 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
                        <div class="bg-white w-full max-w-md shadow-premium border border-gray-100 overflow-hidden transform-gpu">
                            <div class="bg-foundation-grey p-8 text-white flex justify-between items-center">
                                <h4 class="text-xl font-serif font-bold">Standard Performance Entry</h4>
                                <button onclick="document.getElementById('ratingModal_<?php echo (int)$w['id']; ?>').classList.add('hidden')" class="text-gray-400 hover:text-white transition-colors">
                                    <i data-lucide="x" class="w-6 h-6"></i>
                                </button>
                            </div>
                            <form method="POST" class="p-10 space-y-6">
                                <?php echo csrf_token_field(); ?>
                                <input type="hidden" name="submit_rating" value="1">
                                <input type="hidden" name="worker_id" value="<?php echo (int)$w['id']; ?>">
                                <input type="hidden" name="project_id" value="">

                                <div class="grid grid-cols-1 gap-4">
                                    <?php
                                    $metrics = [
                                      'charges_efficiency' => 'Charges Efficiency',
                                      'work_quality' => 'Work Quality',
                                      'experience' => 'Experience',
                                      'speed_timing' => 'Speed (Timing)',
                                      'reliability' => 'Reliability',
                                      'rework_rate' => 'Rework Rate',
                                      'communication' => 'Communication',
                                      'client_feedback' => 'Client Feedback',
                                      'flexibility' => 'Flexibility',
                                      'safety' => 'Safety',
                                    ];
                                    foreach ($metrics as $key => $label): ?>
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em] mb-1"><?php echo $label; ?> (0–10)</label>
                                            <input type="number" name="<?php echo $key; ?>" min="0" max="10" step="0.1" value="0" class="w-full p-3 border bg-gray-50">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] block mb-1">Observation / Notes</label>
                                    <textarea name="comment" rows="4" class="w-full p-4 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium placeholder:text-gray-300" placeholder="Document project-specific notes..."></textarea>
                                </div>

                                <button type="submit" class="w-full py-5 bg-foundation-grey hover:bg-rajkot-rust text-white text-[10px] font-bold uppercase tracking-[0.4em] shadow-premium transition-all active:scale-[0.98]">Commit Entry to Registry</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($isAdmin): ?>
                <section class="mt-14 bg-white shadow-premium border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <h2 class="text-2xl font-serif font-bold text-foundation-grey">All Member Ratings</h2>
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400 mt-1">Latest <?php echo count($allRatings); ?> rating entries</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[860px]">
                            <thead class="bg-gray-50 text-[10px] uppercase tracking-[0.15em] text-gray-500">
                                <tr>
                                    <th class="px-6 py-4">Member</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Event Score</th>
                                    <th class="px-6 py-4">Key Metrics</th>
                                    <th class="px-6 py-4">Recorded By</th>
                                    <th class="px-6 py-4">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allRatings)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-sm text-gray-500">No rating entries found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allRatings as $entry):
                                        $p = sc_compute_wps($entry);
                                    ?>
                                        <tr class="border-t border-gray-100 align-top">
                                            <td class="px-6 py-4 text-sm font-semibold text-foundation-grey"><?php echo htmlspecialchars($entry['member_name']); ?></td>
                                            <td class="px-6 py-4 text-xs uppercase tracking-wider text-gray-500"><?php echo htmlspecialchars($entry['member_role']); ?></td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-semibold text-foundation-grey"><?php echo round($p * 100, 1); ?>%</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 max-w-[320px]">
                                                <?php echo 'Quality: ' . htmlspecialchars((string)$entry['work_quality']) . ' • Reliability: ' . htmlspecialchars((string)$entry['reliability']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars((string)$entry['rated_by']); ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('d M Y, h:i A', strtotime((string)$entry['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>

        <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
    </div>

    <script>
        // Multi-metric rating forms are simple HTML inputs; no JS required here.
    </script>

</body>

</html>