<?php
// dashboard/goods_manage.php - admin page to add goods with SKU/description/unit
session_start();
require_once __DIR__ . '/../includes/init.php';
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if (!$project_id) { header('Location: dashboard.php'); exit; }

// Ensure table exists with extended columns
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

// handle POST add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit = trim($_POST['unit'] ?? 'pcs');
    $quantity = max(0, (int)($_POST['quantity'] ?? 0));
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $total = round($quantity * $unit_price, 2);
    if ($name && isset($pdo) && $pdo instanceof PDO) {
        $ins = $pdo->prepare('INSERT INTO project_goods (project_id,sku,name,description,unit,quantity,unit_price,total_price) VALUES (:pid,:sku,:name,:description,:unit,:quantity,:unit_price,:total)');
        $ins->execute(['pid'=>$project_id,'sku'=>$sku,'name'=>$name,'description'=>$description,'unit'=>$unit,'quantity'=>$quantity,'unit_price'=>$unit_price,'total'=>$total]);
    }
    header('Location: goods_manage.php?project_id=' . $project_id);
    exit;
}

// handle delete
if (isset($_GET['action']) && $_GET['action']==='delete' && isset($_GET['id'])) {
    if (isset($pdo) && $pdo instanceof PDO) {
        $del = $pdo->prepare('DELETE FROM project_goods WHERE id = :id AND project_id = :pid');
        $del->execute(['id'=>(int)$_GET['id'],'pid'=>$project_id]);
    }
    header('Location: goods_manage.php?project_id=' . $project_id);
    exit;
}

// load project and goods
$project = ['id'=>$project_id,'name'=>'Project '.$project_id];
if (isset($pdo) && $pdo instanceof PDO) {
    $s = $pdo->prepare('SELECT id,name FROM projects WHERE id = :id LIMIT 1'); $s->execute(['id'=>$project_id]); $r=$s->fetch(PDO::FETCH_ASSOC); if($r) $project=$r;
    $gq = $pdo->prepare('SELECT * FROM project_goods WHERE project_id = :pid ORDER BY created_at DESC'); $gq->execute(['pid'=>$project_id]); $goods = $gq->fetchAll(PDO::FETCH_ASSOC);
} else { $goods = []; }
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Goods — <?php echo htmlspecialchars($project['name']); ?></title>
<link rel="stylesheet" href="../styles.css">
</head>
<body>
<?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header_alt.php'; ?>
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg mb-12">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-serif font-bold">Inventory Control</h1>
                <p class="text-gray-400 mt-2">Managing goods and materials for: <span class="text-rajkot-rust font-bold"><?php echo htmlspecialchars($project['name']); ?></span></p>
            </div>
            <div class="bg-white/5 border border-white/10 px-4 py-2 rounded">
                 <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-1">SKU Count</p>
                 <p class="text-xl font-bold text-rajkot-rust"><?php echo count($goods); ?></p>
            </div>
        </div>
    </header>

<main class="worker-dashboard">
  <div class="container">

    <section class="info-card">
      <form method="post" class="row g-2">
        <div class="col-md-3"><input name="sku" class="form-control" placeholder="SKU (optional)"></div>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="Item name (required)" required></div>
        <div class="col-md-5"><input name="unit" class="form-control" placeholder="Unit (e.g. pcs, kg, bag)" value="pcs"></div>
        <div class="col-md-8" style="margin-top:8px"><input name="description" class="form-control" placeholder="Short description (optional)"></div>
        <div class="col-md-2" style="margin-top:8px"><input name="quantity" type="number" min="1" value="1" class="form-control"></div>
        <div class="col-md-2" style="margin-top:8px"><input name="unit_price" type="number" step="0.01" min="0" value="0" class="form-control"></div>
        <div class="col-md-12" style="margin-top:8px">
          <button class="btn btn-primary">Add Item</button>
          <a class="btn outline" href="goods_invoice.php?project_id=<?php echo $project_id; ?>">View Invoice</a>
          <a class="btn" href="dashboard.php">Back</a>
        </div>
      </form>
    </section>

    <section class="info-card mt-3">
      <h3>Existing Items</h3>
      <?php if (empty($goods)): ?><p class="muted">No items yet.</p><?php else: ?>
        <table class="table"><thead><tr><th>SKU</th><th>Item</th><th>Unit</th><th>Qty</th><th>Unit</th><th>Total</th><th></th></tr></thead><tbody>
        <?php foreach($goods as $g): ?>
          <tr>
            <td><?php echo htmlspecialchars($g['sku']); ?></td>
            <td><?php echo htmlspecialchars($g['name']); ?><div class="small muted"><?php echo htmlspecialchars($g['description']); ?></div></td>
            <td><?php echo htmlspecialchars($g['unit']); ?></td>
            <td><?php echo intval($g['quantity']); ?></td>
            <td>₹ <?php echo number_format($g['unit_price'],2); ?></td>
            <td>₹ <?php echo number_format($g['total_price'],2); ?></td>
            <td><a class="btn outline btn-sm" href="goods_manage.php?project_id=<?php echo $project_id; ?>&action=delete&id=<?php echo $g['id']; ?>" onclick="return confirm('Delete item?')">Delete</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>