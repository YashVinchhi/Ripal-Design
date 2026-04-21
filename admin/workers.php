<?php
$projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 1);
require_once $projectRoot . '/app/Core/Bootstrap/init.php';

require_login();
require_role('admin');
require_once PROJECT_ROOT . '/app/Core/Services/scoring.php';

// Handle worker metric submission from admin page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_worker_metric') {
  require_csrf();
  $worker_id = intval($_POST['worker_id'] ?? 0);
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
      $currentUser = current_user();
      $current_user = $currentUser['username'] ?? ($currentUser['email'] ?? 'Admin');
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

      list($filteredEvents, $outlierCount) = sc_filter_outliers($normalizedEvents, 1.5);

      $timeWeighted = sc_time_weighted_score($filteredEvents, 0.4);
      $consistency = sc_consistency($filteredEvents);
      $similarityWeighted = sc_similarity_weighted($filteredEvents, null, 0.4);

      // availability approximation: count of events with reliability >= 7
      $onTimeCount = 0; foreach ($events as $ev) { if (isset($ev['reliability']) && floatval($ev['reliability']) >= 7.0) $onTimeCount++; }
      $projectCountRow = $db->prepare('SELECT COUNT(*) AS c FROM project_assignments WHERE worker_id = ?');
      $projectCountRow->execute([$worker_id]);
      $projectCount = (int)($projectCountRow->fetchColumn() ?: 0);
      $availability = sc_availability_factor($onTimeCount, $projectCount ?: max(1, count($events)));

      // risk proxies using average metrics
      $avgSpeed = 0; $avgRework = 0; $n=0;
      foreach ($events as $ev) { $n++; $avgSpeed += floatval($ev['speed_timing']); $avgRework += floatval($ev['rework_rate']); }
      if ($n>0) { $avgSpeed /= $n; $avgRework /= $n; }
      $risk = sc_compute_risk_worker(['speed_timing' => $avgSpeed, 'rework_rate_metric' => $avgRework]);

      $final = sc_final_score($timeWeighted, $consistency, $similarityWeighted, $availability);
      $confidence = sc_confidence($consistency, count($filteredEvents), 5);
      $decision = sc_decision_score($final, $risk, $confidence);

      $up = $db->prepare('INSERT INTO worker_scores (worker_id, final_score, risk, confidence, decision_score, last_computed_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE final_score = VALUES(final_score), risk = VALUES(risk), confidence = VALUES(confidence), decision_score = VALUES(decision_score), last_computed_at = NOW()');
      $up->execute([$worker_id, $final, $risk, $confidence, $decision]);
    } catch (Exception $e) {
      if (function_exists('app_log')) app_log('error','admin worker metric save failed',['ex'=>$e->getMessage()]);
    }
  }
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit;
}

$db = get_db();
$message = '';

$workers = [];
if ($db instanceof PDO) {
    try {
        $workers = $db->query("SELECT u.id, COALESCE(u.first_name, '') AS first_name, COALESCE(u.last_name, '') AS last_name, u.username, u.email, u.phone, COALESCE(ws.decision_score,0) AS decision_score, COALESCE(ws.final_score,0) AS final_score, COALESCE(ws.confidence,0) AS confidence, COALESCE(ws.risk,0) AS risk, (SELECT COUNT(*) FROM worker_metric_events wme WHERE wme.worker_id = u.id) AS events_count FROM users u LEFT JOIN worker_scores ws ON ws.worker_id = u.id WHERE u.role = 'worker' ORDER BY u.username ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ignore
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Workers | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
  <style>
    /* Make main content use 80% width on desktop with 10% margins */
    .page-80 { width: 100%; max-width: none !important; }
    @media (min-width: 768px) {
      .page-80 { width: 80% !important; margin-left: 10% !important; margin-right: 10% !important; }
    }
  </style>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 pb-8 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-serif font-bold">Worker Management</h1>
        <p class="text-gray-300 mt-2 text-sm">Manage field workers and review performance summaries.</p>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 page-80">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 shadow-premium border border-gray-100">
          <h2 class="text-xl font-bold mb-4">Add Worker</h2>
          <p class="text-sm text-gray-500 mb-4">Create a new system identity for field staff.</p>
          <a href="add_user.php?role=worker" class="inline-block bg-foundation-grey text-white px-6 py-3 font-bold">Open Add Worker</a>
        </div>

        <div class="bg-white p-6 shadow-premium border border-gray-100">
          <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
          <p class="text-sm text-gray-500 mb-4">Navigate to worker tools.</p>
          <div class="flex gap-3">
            <a href="/worker/worker_rating.php" class="px-4 py-2 border border-gray-200 hover:bg-gray-50 text-sm no-underline">Open Worker Audit</a>
            <a href="/app/Domains/Admin/Controllers/user_management.php?role=worker" class="px-4 py-2 border border-gray-200 hover:bg-gray-50 text-sm no-underline">Worker Registry</a>
          </div>
        </div>
      </div>

      <section class="mt-10 bg-white p-6 shadow-premium border border-gray-100">
        <h3 class="text-lg font-bold mb-4">Existing Workers</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-widest">
              <tr><th class="px-4 py-2">Worker</th><th class="px-4 py-2">Contact</th><th class="px-4 py-2">Phone</th><th class="px-4 py-2">Score</th><th class="px-4 py-2">Action</th></tr>
            </thead>
            <tbody>
              <?php if (empty($workers)): ?>
                <tr><td colspan="5" class="px-4 py-6 text-gray-500">No workers found.</td></tr>
              <?php else: ?>
                <?php foreach ($workers as $w): ?>
                  <?php $fullName = trim((string)$w['first_name'] . ' ' . (string)$w['last_name']); ?>
                  <tr class="border-t">
                    <td class="px-4 py-3 font-semibold"><?php echo esc($fullName !== '' ? $fullName : $w['username']); ?></td>
                    <td class="px-4 py-3"><?php echo esc($w['email']); ?></td>
                    <td class="px-4 py-3"><?php echo esc($w['phone']); ?></td>
                    <td class="px-4 py-3"><?php echo isset($w['decision_score']) ? round(floatval($w['decision_score'])*100,1) . '%' : 'N/A'; ?> (<?php echo (int)$w['events_count']; ?>)</td>
                    <td class="px-4 py-3"><button onclick="document.getElementById('workerMetricModal_<?php echo (int)$w['id']; ?>').classList.remove('hidden')" class="px-3 py-2 bg-amber-500 text-white rounded text-sm">Record Metric</button></td>
                  </tr>

                  <div id="workerMetricModal_<?php echo (int)$w['id']; ?>" class="fixed inset-0 bg-foundation-grey/90 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
                    <div class="bg-white w-full max-w-md shadow-premium border border-gray-100 overflow-hidden transform-gpu">
                      <div class="bg-foundation-grey p-6 text-white flex justify-between items-center">
                        <h4 class="text-lg font-bold">Record Metric for <?php echo htmlspecialchars($fullName !== '' ? $fullName : $w['username']); ?></h4>
                        <button onclick="document.getElementById('workerMetricModal_<?php echo (int)$w['id']; ?>').classList.add('hidden')" class="text-gray-400 hover:text-white">Close</button>
                      </div>
                      <form method="POST" class="p-6">
                        <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="submit_worker_metric">
                        <input type="hidden" name="worker_id" value="<?php echo (int)$w['id']; ?>">
                        <div class="grid grid-cols-1 gap-3">
                          <?php $metrics = ['charges_efficiency'=>'Charges Efficiency','work_quality'=>'Work Quality','experience'=>'Experience','speed_timing'=>'Speed (Timing)','reliability'=>'Reliability','rework_rate'=>'Rework Rate','communication'=>'Communication','client_feedback'=>'Client Feedback','flexibility'=>'Flexibility','safety'=>'Safety'];
                          foreach ($metrics as $k=>$lab): ?>
                            <div><label class="text-xs font-bold"><?php echo $lab; ?> (0–10)</label><input type="number" name="<?php echo $k; ?>" min="0" max="10" step="0.1" value="0" class="w-full p-2 border"></div>
                          <?php endforeach; ?>
                        </div>
                        <div class="mt-4"><label class="text-xs font-bold">Project (optional)</label><input name="project_id" class="w-full p-2 border" placeholder="Project ID"></div>
                        <div class="mt-4 flex gap-3 justify-end"><button type="button" onclick="document.getElementById('workerMetricModal_<?php echo (int)$w['id']; ?>').classList.add('hidden')" class="px-3 py-2 border rounded">Cancel</button><button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded">Save</button></div>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>
</body>
</html>
