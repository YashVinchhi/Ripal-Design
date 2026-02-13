<?php
// dashboard/goods_invoice.php - redesigned invoice layout (printable + responsive)
session_start();
require_once __DIR__ . '/../includes/init.php';
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if (!$project_id) { header('Location: dashboard.php'); exit; }

// ensure project_goods table exists with required columns (in case goods_manage wasn't used)
if (isset($pdo) && $pdo instanceof PDO) {
  $pdo->exec("CREATE TABLE IF NOT EXISTS project_goods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    unit VARCHAR(50) DEFAULT 'pcs',
    quantity INT DEFAULT 1,
    unit_price DECIMAL(12,2) DEFAULT 0,
    total_price DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(project_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// ensure projects table has worker_name column; add if missing
if (isset($pdo) && $pdo instanceof PDO) {
  $colCheck = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'worker_name'");
  try { $colCheck->execute(); $cnt = (int)$colCheck->fetchColumn(); } catch(Exception $e){ $cnt = 0; }
  if ($cnt === 0) {
    try { $pdo->exec("ALTER TABLE projects ADD COLUMN worker_name VARCHAR(255) DEFAULT NULL"); } catch(Exception $e) { /* ignore */ }
  }
}

// Handle form submissions: add item or save meta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add_item') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit = trim($_POST['unit'] ?? 'pcs');
    $quantity = max(0, (int)($_POST['quantity'] ?? 0));
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $total_price = round($quantity * $unit_price, 2);
    if ($name && isset($pdo) && $pdo instanceof PDO) {
      $ins = $pdo->prepare('INSERT INTO project_goods (project_id,sku,name,description,unit,quantity,unit_price,total_price) VALUES (:pid,:sku,:name,:description,:unit,:quantity,:unit_price,:total)');
      $ins->execute(['pid'=>$project_id,'sku'=>$sku,'name'=>$name,'description'=>$description,'unit'=>$unit,'quantity'=>$quantity,'unit_price'=>$unit_price,'total'=>$total_price]);
    }
    header('Location: goods_invoice.php?project_id=' . $project_id);
    exit;
  } elseif ($action === 'save_meta') {
    $client = trim($_POST['client_name'] ?? '');
    $worker = trim($_POST['worker_name'] ?? '');
    if (isset($pdo) && $pdo instanceof PDO) {
      $upd = $pdo->prepare('UPDATE projects SET owner_name = :client, worker_name = :worker WHERE id = :id');
      $upd->execute(['client'=>$client,'worker'=>$worker,'id'=>$project_id]);
    }
    header('Location: goods_invoice.php?project_id=' . $project_id);
    exit;
  }
}

// Load project
$project = ['id'=>$project_id,'name'=>'Project '.$project_id,'owner_name'=>'Client','owner_contact'=>''];
if (isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare('SELECT id,name,owner_name,owner_contact,location FROM projects WHERE id = :id LIMIT 1');
    $stmt->execute(['id'=>$project_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) $project = $r;
}

// Load goods
$goods = [];
$subtotal = 0.0;
if (isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare('SELECT id,sku,name,description,unit,quantity,unit_price,total_price FROM project_goods WHERE project_id = :pid ORDER BY created_at ASC');
    $stmt->execute(['pid'=>$project_id]);
    $goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($goods as $g) { $subtotal += (float)$g['total_price']; }
}

// Invoice calculations
$tax_rate = 0.18; // example 18%
$tax = round($subtotal * $tax_rate, 2);
$total = round($subtotal + $tax, 2);
$invoice_id = 'INV-' . str_pad($project_id, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd');
$share_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/goods_invoice.php?project_id=' . $project_id;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Invoice <?php echo h($invoice_id); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    :root{--brand:#731209;--muted:#666;--card-bg:#fff;--surface:#f8f9fa}
    body{background:var(--surface);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;color:#222;margin:0;padding:18px}
    .invoice-wrap{max-width:980px;margin:18px auto;padding:20px;background:var(--card-bg);box-shadow:0 6px 18px rgba(0,0,0,0.06);border-radius:8px}
    .invoice-top{display:flex;gap:16px;align-items:center;justify-content:space-between}
    .brand{display:flex;gap:12px;align-items:center}
    .brand .logo{width:64px;height:64px;background:linear-gradient(135deg,var(--brand),#a52a2a);border-radius:8px;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700}
    .brand h1{margin:0;font-size:18px}
    .meta{text-align:right}
    .meta .id{font-weight:700;color:var(--brand)}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:18px}
    .panel{background:#fbfbfb;padding:12px;border-radius:6px}
    table{width:100%;border-collapse:collapse;margin-top:14px}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
    th{background:#fafafa;font-weight:600}
    tfoot td{border-top:2px solid #e9e9e9}
    .right{text-align:right}
    .totals{max-width:360px;margin-left:auto;margin-top:12px}
    .actions{margin-top:18px;display:flex;gap:10px;align-items:center}
    .btn{display:inline-block;padding:8px 12px;border-radius:6px;text-decoration:none;border:1px solid #ddd;background:#fff;color:#222}
    .btn.primary{background:var(--brand);color:#fff;border-color:transparent}
    .small{font-size:13px;color:var(--muted)}
    @media (max-width:720px){.invoice-top{flex-direction:column;align-items:flex-start}.grid{grid-template-columns:1fr}}
    @media print{
      body{background:#fff;padding:0}
      .invoice-wrap{box-shadow:none;border-radius:0;padding:8px}
      .no-print{display:none}
    }
  </style>
</head>
<body>
<div class="invoice-wrap" role="main">
  <div class="invoice-top">
    <div class="brand">
      <div class="logo">RD</div>
      <div>
        <h1>Ripal Design</h1>
        <div class="small">Architects &amp; Interior Design</div>
        <div class="small">contact@ripaldesign.example | +91 12345 67890</div>
      </div>
    </div>

    <div class="meta">
      <div>Invoice</div>
      <div class="id"><?php echo h($invoice_id); ?></div>
      <div class="small"><?php echo date('F j, Y'); ?></div>
      <div class="small">Project ID: <?php echo (int)$project['id']; ?></div>
    </div>
  </div>

  <div class="grid" style="margin-top:18px">
    <div class="panel">
      <div style="font-weight:700">Bill To</div>
      <div style="margin-top:6px;font-size:15px"><?php echo h($project['owner_name'] ?? 'Client'); ?></div>
      <?php if (!empty($project['owner_contact'])): ?><div class="small"><?php echo h($project['owner_contact']); ?></div><?php endif; ?>
      <?php if (!empty($project['location'])): ?><div class="small" style="margin-top:6px"><?php echo h($project['location']); ?></div><?php endif; ?>
      <form method="post" style="margin-top:10px">
        <input type="hidden" name="action" value="save_meta">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <input name="client_name" placeholder="Client name" class="form-control" value="<?php echo h($project['owner_name'] ?? ''); ?>">
          <input name="worker_name" placeholder="Worker name" class="form-control" value="<?php echo h($project['worker_name'] ?? ''); ?>">
          <button class="btn" type="submit">Save</button>
        </div>
      </form>
    </div>

    <div class="panel">
      <div style="font-weight:700">Project</div>
      <div style="margin-top:6px;font-size:15px"><?php echo h($project['name']); ?></div>
      <div class="small">Project ID: <?php echo (int)$project['id']; ?></div>
      <div style="margin-top:8px">
        <div class="small">Invoice total</div>
        <div style="font-size:18px;font-weight:700">₹ <?php echo number_format($total,2); ?></div>
      </div>
    </div>
  </div>

  <table aria-labelledby="items">
    <thead>
      <tr>
        <th style="width:6%">#</th>
        <th>Item description</th>
        <th style="width:12%">Qty</th>
        <th style="width:16%">Unit price</th>
        <th style="width:16%" class="right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($goods)): ?>
        <tr><td colspan="5" class="small">No items added for this project.</td></tr>
      <?php else: $i=1; foreach($goods as $g): ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td>
            <div style="font-weight:600"><?php echo h($g['name']); ?><?php if(!empty($g['sku'])): ?> <small class="small muted">(<?php echo h($g['sku']); ?>)</small><?php endif; ?></div>
            <?php if (!empty($g['description'])): ?><div class="small muted" style="margin-top:6px"><?php echo h($g['description']); ?></div><?php endif; ?>
          </td>
          <td><?php echo intval($g['quantity']); ?> <?php echo h($g['unit'] ?? 'pcs'); ?></td>
          <td>₹ <?php echo number_format($g['unit_price'],2); ?></td>
          <td class="right">₹ <?php echo number_format($g['total_price'],2); ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <div class="totals">
    <table>
      <tbody>
        <tr><td class="small">Subtotal</td><td class="right">₹ <?php echo number_format($subtotal,2); ?></td></tr>
        <tr><td class="small">Tax (<?php echo ($tax_rate*100); ?>%)</td><td class="right">₹ <?php echo number_format($tax,2); ?></td></tr>
        <tr><td style="font-weight:700">Total</td><td class="right" style="font-weight:700">₹ <?php echo number_format($total,2); ?></td></tr>
      </tbody>
    </table>
  </div>

  <div style="clear:both"></div>

  <div style="margin-top:18px; display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap">
    <div class="small">Notes: Goods are for procurement by worker. Prices are estimates and may change at purchase.</div>
    <div class="no-print actions">
      <button class="btn primary" onclick="window.print()">Print / Save PDF</button>
      <a class="btn" href="dashboard.php">Back to Dashboard</a>
      <button class="btn" id="copyLink">Copy Invoice Link</button>
      <button class="btn" id="emailLink">Share by Email</button>
    </div>
  </div>
  
  <div style="margin-top:18px" class="panel">
    <h3 style="margin:0 0 8px">Add item</h3>
    <form id="addItemForm" method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <input type="hidden" name="action" value="add_item">
      <input id="sku" name="sku" class="form-control" placeholder="SKU (optional)" style="width:120px">
      <input id="name" name="name" class="form-control" placeholder="Item name" required style="min-width:220px;flex:1">
      <input id="unit" name="unit" class="form-control" placeholder="Unit (pcs/kg)" style="width:90px">
      <input id="quantity" name="quantity" type="number" class="form-control" min="1" value="1" style="width:90px">
      <input id="unit_price" name="unit_price" type="number" step="0.01" class="form-control" min="0" value="0" style="width:140px">
      <div id="lineTotal" class="small" style="min-width:140px; text-align:right">Line total: ₹ 0.00</div>
      <button id="addBtn" class="btn primary" type="submit">Add</button>
      <div id="addError" class="small" style="color:#b00020; width:100%; display:none; margin-top:8px"></div>
    </form>
  </div>

</div>
<script>
(function(){
  const shareUrl = '<?php echo addslashes($share_url); ?>';
  document.getElementById('copyLink').addEventListener('click', function(){
    navigator.clipboard?.writeText(shareUrl).then(()=>{ alert('Invoice link copied to clipboard'); }).catch(()=>{ prompt('Copy this link', shareUrl); });
  });
  document.getElementById('emailLink').addEventListener('click', function(){
    const subject = encodeURIComponent('Goods Invoice: <?php echo addslashes($project['name']); ?>');
    const body = encodeURIComponent('Please view the invoice at: ' + shareUrl + '\n\nYou can print or save as PDF from the page.');
    window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
  });

  // Auto-calc line total and client-side validation for Add Item form
  const qtyEl = document.getElementById('quantity');
  const priceEl = document.getElementById('unit_price');
  const lineTotalEl = document.getElementById('lineTotal');
  const addForm = document.getElementById('addItemForm');
  const addError = document.getElementById('addError');

  function formatCurrency(v){ return '₹ ' + Number(v || 0).toFixed(2); }
  function computeLineTotal(){
    const q = Math.max(0, parseFloat(qtyEl.value) || 0);
    const p = Math.max(0, parseFloat(priceEl.value) || 0);
    const t = q * p;
    lineTotalEl.textContent = 'Line total: ' + formatCurrency(t);
    return t;
  }

  // initialize
  try { computeLineTotal(); } catch(e) {}

  qtyEl?.addEventListener('input', ()=>{ computeLineTotal(); addError.style.display = 'none'; });
  priceEl?.addEventListener('input', ()=>{ computeLineTotal(); addError.style.display = 'none'; });

  addForm?.addEventListener('submit', function(ev){
    const nameEl = document.getElementById('name');
    const errors = [];
    if (!nameEl || !nameEl.value.trim()) errors.push('Enter item name');
    const q = parseFloat(qtyEl.value) || 0;
    if (q < 1) errors.push('Quantity must be at least 1');
    const p = parseFloat(priceEl.value);
    if (isNaN(p) || p < 0) errors.push('Unit price must be 0 or greater');
    if (errors.length){
      ev.preventDefault();
      addError.textContent = errors.join(' — ');
      addError.style.display = 'block';
      if (!nameEl || !nameEl.value.trim()) nameEl?.focus();
      return false;
    }
    // allow submit
  });
})();
</script>
</body>
</html>
