<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
/**
 * Vendor Metric Entries and Scoring
 */
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_once PROJECT_ROOT . '/app/Core/Services/scoring.php';

require_login();

$currentUser = current_user();
$current_user = $currentUser['username'] ?? ($currentUser['email'] ?? 'Admin');
$isAdmin = strtolower((string)($currentUser['role'] ?? '')) === 'admin';

$vendors = [];
$allRatings = [];
if (db_connected()) {
    try {
        $db = get_db();
        $stmt = $db->query("SELECT v.id, v.name, v.contact_name, COALESCE(vs.decision_score,0) AS decision_score, COALESCE(vs.final_score,0) AS final_score, COALESCE(vs.confidence,0) AS confidence, COALESCE(vs.risk,0) AS risk, (SELECT COUNT(*) FROM vendor_metric_events vme WHERE vme.vendor_id = v.id) AS events_count FROM vendors v LEFT JOIN vendor_scores vs ON vs.vendor_id = v.id ORDER BY v.name ASC");
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($isAdmin) {
            $ratingsStmt = $db->query("SELECT vme.*, v.name AS vendor_name FROM vendor_metric_events vme INNER JOIN vendors v ON v.id = vme.vendor_id ORDER BY vme.created_at DESC LIMIT 200");
            $allRatings = $ratingsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        if (function_exists('app_log')) app_log('warning','vendor rating load failed',['ex'=>$e->getMessage()]);
    }
}

// Handle metric submission (admin/employee)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vendor_metric'])) {
    require_csrf();
    $vendor_id = intval($_POST['vendor_id'] ?? 0);
    $metrics = [
        'pricing' => floatval($_POST['pricing'] ?? 0),
        'product_quality' => floatval($_POST['product_quality'] ?? 0),
        'consistency' => floatval($_POST['consistency'] ?? 0),
        'delivery_reliability' => floatval($_POST['delivery_reliability'] ?? 0),
        'stock_availability' => floatval($_POST['stock_availability'] ?? 0),
        'variety' => floatval($_POST['variety'] ?? 0),
        'warranty_replacement' => floatval($_POST['warranty_replacement'] ?? 0),
        'communication' => floatval($_POST['communication'] ?? 0),
        'credit_terms' => floatval($_POST['credit_terms'] ?? 0),
        'logistics' => floatval($_POST['logistics'] ?? 0),
    ];

    if ($vendor_id && db_connected()) {
        try {
            $db = get_db();
            $stmt = $db->prepare("INSERT INTO vendor_metric_events (vendor_id, project_id, purchase_order_ref, priced_by, pricing, product_quality, consistency, delivery_reliability, stock_availability, variety, warranty_replacement, communication, credit_terms, logistics, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
            $stmt->execute([
                $vendor_id,
                $project_id ?: null,
                trim((string)($_POST['purchase_order_ref'] ?? '')),
                $current_user,
                $metrics['pricing'],
                $metrics['product_quality'],
                $metrics['consistency'],
                $metrics['delivery_reliability'],
                $metrics['stock_availability'],
                $metrics['variety'],
                $metrics['warranty_replacement'],
                $metrics['communication'],
                $metrics['credit_terms'],
                $metrics['logistics']
            ]);

            // Recalculate vendor aggregates
            $evStmt = $db->prepare('SELECT * FROM vendor_metric_events WHERE vendor_id = ? ORDER BY created_at DESC');
            $evStmt->execute([$vendor_id]);
            $events = $evStmt->fetchAll(PDO::FETCH_ASSOC);

            $normalizedEvents = [];
            foreach ($events as $ev) {
                $p = sc_compute_vbs($ev);
                $normalizedEvents[] = ['p' => $p, 'created_at' => $ev['created_at']];
            }

            list($filteredEvents, $outlierCount) = sc_filter_outliers($normalizedEvents, 1.5);

            $timeWeighted = sc_time_weighted_score($filteredEvents, 0.4);
            $consistency = sc_consistency($filteredEvents);
            $similarityWeighted = sc_similarity_weighted($filteredEvents, null, 0.4);

            // availability proxy: fraction of events with stock_availability >= 7
            $inStock = 0; foreach ($events as $ev) { if (isset($ev['stock_availability']) && floatval($ev['stock_availability']) >= 7.0) $inStock++; }
            $availability = sc_availability_factor($inStock, count($events) ?: 1);

            // risk proxies
            $avgDelivery = 0; $avgQuality = 0; $avgStock = 0; $n=0;
            foreach ($events as $ev) { $n++; $avgDelivery += floatval($ev['delivery_reliability']); $avgQuality += floatval($ev['product_quality']); $avgStock += floatval($ev['stock_availability']); }
            if ($n>0) { $avgDelivery /= $n; $avgQuality /= $n; $avgStock /= $n; }
            $risk = sc_compute_risk_vendor(['delivery_reliability' => $avgDelivery, 'product_quality' => $avgQuality, 'stock_availability' => $avgStock]);

            $final = sc_final_score($timeWeighted, $consistency, $similarityWeighted, $availability);
            $confidence = sc_confidence($consistency, count($filteredEvents), 5);
            $decision = sc_decision_score($final, $risk, $confidence);

            $up = $db->prepare('INSERT INTO vendor_scores (vendor_id, final_score, risk, confidence, decision_score, last_computed_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE final_score = VALUES(final_score), risk = VALUES(risk), confidence = VALUES(confidence), decision_score = VALUES(decision_score), last_computed_at = NOW()');
            $up->execute([$vendor_id, $final, $risk, $confidence, $decision]);

        } catch (Exception $e) {
            if (function_exists('app_log')) app_log('error','vendor metric save failed',['ex'=>$e->getMessage()]);
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vendor Metrics | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 pb-8 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-serif font-bold">Vendor Metrics</h1>
        <p class="text-gray-300 mt-2 text-sm">Record supplier batch evaluations and view aggregated scores.</p>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php foreach ($vendors as $v): ?>
          <div class="bg-white p-6 shadow-premium border border-gray-100">
            <h3 class="font-bold mb-2"><?php echo htmlspecialchars($v['name']); ?></h3>
            <p class="text-sm text-gray-500 mb-2">Contact: <?php echo htmlspecialchars($v['contact_name']); ?></p>
            <p class="text-xs text-gray-400 mb-2">Decision: <?php echo isset($v['decision_score']) ? round(floatval($v['decision_score'])*100,1) . '%' : 'N/A'; ?> • Final: <?php echo isset($v['final_score']) ? round(floatval($v['final_score'])*100,1) . '%' : 'N/A'; ?></p>
            <button onclick="document.getElementById('vendorMetricModal_<?php echo (int)$v['id']; ?>').classList.remove('hidden')" class="mt-4 px-4 py-2 bg-amber-500 text-white rounded text-sm">Record Metric</button>
          </div>

          <div id="vendorMetricModal_<?php echo (int)$v['id']; ?>" class="fixed inset-0 bg-foundation-grey/90 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white w-full max-w-md shadow-premium border border-gray-100 overflow-hidden transform-gpu">
              <div class="bg-foundation-grey p-6 text-white flex justify-between items-center">
                <h4 class="text-lg font-bold">Record Metric for <?php echo htmlspecialchars($v['name']); ?></h4>
                <button onclick="document.getElementById('vendorMetricModal_<?php echo (int)$v['id']; ?>').classList.add('hidden')" class="text-gray-400 hover:text-white">Close</button>
              </div>
              <form method="POST" class="p-6">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="submit_vendor_metric" value="1">
                <input type="hidden" name="vendor_id" value="<?php echo (int)$v['id']; ?>">
                <div class="grid grid-cols-1 gap-3">
                  <?php $vmetrics = ['pricing'=>'Pricing','product_quality'=>'Product Quality','consistency'=>'Consistency','delivery_reliability'=>'Delivery Reliability','stock_availability'=>'Stock Availability','variety'=>'Variety','warranty_replacement'=>'Warranty/Replacement','communication'=>'Communication','credit_terms'=>'Credit Terms','logistics'=>'Logistics'];
                  foreach ($vmetrics as $k=>$lab): ?>
                    <div><label class="text-xs font-bold"><?php echo $lab; ?> (0–10)</label><input type="number" name="<?php echo $k; ?>" min="0" max="10" step="0.1" value="0" class="w-full p-2 border"></div>
                  <?php endforeach; ?>
                </div>
                <div class="mt-4"><label class="text-xs font-bold">Purchase Order Ref</label><input name="purchase_order_ref" class="w-full p-2 border"></div>
                <div class="mt-4 flex gap-3 justify-end"><button type="button" onclick="document.getElementById('vendorMetricModal_<?php echo (int)$v['id']; ?>').classList.add('hidden')" class="px-3 py-2 border rounded">Cancel</button><button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded">Save</button></div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($isAdmin): ?>
        <section class="mt-10 bg-white p-6 shadow-premium border border-gray-100">
          <h3 class="text-lg font-bold mb-4">Recent Vendor Metrics</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-widest"><tr><th class="px-4 py-2">Vendor</th><th class="px-4 py-2">Score</th><th class="px-4 py-2">Key</th><th class="px-4 py-2">Recorded</th></tr></thead>
              <tbody>
                <?php if (empty($allRatings)): ?><tr><td colspan="4" class="px-4 py-6 text-gray-500">No entries.</td></tr><?php else: foreach ($allRatings as $e): $p = sc_compute_vbs($e); ?>
                  <tr class="border-t"><td class="px-4 py-3"><?php echo htmlspecialchars($e['vendor_name']); ?></td><td class="px-4 py-3"><?php echo round($p*100,1); ?>%</td><td class="px-4 py-3">Quality: <?php echo htmlspecialchars($e['product_quality']); ?> • Delivery: <?php echo htmlspecialchars($e['delivery_reliability']); ?></td><td class="px-4 py-3"><?php echo date('d M Y', strtotime($e['created_at'])); ?></td></tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

    </main>
    <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>
</body>
</html>
