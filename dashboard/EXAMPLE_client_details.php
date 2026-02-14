<?php
/**
 * EXAMPLE: Creating Another Dynamic Page - Client Details
 * 
 * This example shows how to apply the same dynamic pattern
 * used in project_details.php to create a client_details.php page
 * 
 * PATTERN USED:
 * 1. Get ID from URL parameter
 * 2. Create database table(s) if not exists
 * 3. Handle POST requests for create/update
 * 4. Load data from database based on ID
 * 5. Display with dynamic rendering
 */

require_once __DIR__ . '/../includes/init.php';

// Step 1: Get ID from URL
$clientId = $_GET['id'] ?? null;
$error = null;

// Step 2: Create database table
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      company VARCHAR(255),
      email VARCHAR(255),
      phone VARCHAR(20),
      address TEXT,
      status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Related table example
    $pdo->exec("CREATE TABLE IF NOT EXISTS client_projects (
      id INT AUTO_INCREMENT PRIMARY KEY,
      client_id INT NOT NULL,
      project_name VARCHAR(255) NOT NULL,
      project_value DECIMAL(15,2),
      start_date DATE,
      FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )");
    
  } catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
  }
}

// Step 3: Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'] ?? '';
  $company = $_POST['company'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $address = $_POST['address'] ?? '';
  $status = $_POST['status'] ?? 'active';
  
  if (empty($name)) {
    $error = 'Client name is required';
  } else {
    try {
      if ($clientId) {
        // Update existing client
        $stmt = $pdo->prepare('
          UPDATE clients 
          SET name = :name, company = :company, email = :email, 
              phone = :phone, address = :address, status = :status
          WHERE id = :id
        ');
        $stmt->execute([
          'id' => $clientId,
          'name' => $name,
          'company' => $company,
          'email' => $email,
          'phone' => $phone,
          'address' => $address,
          'status' => $status
        ]);
        $success = "Client updated successfully!";
      } else {
        // Create new client
        $stmt = $pdo->prepare('
          INSERT INTO clients (name, company, email, phone, address, status)
          VALUES (:name, :company, :email, :phone, :address, :status)
        ');
        $stmt->execute([
          'name' => $name,
          'company' => $company,
          'email' => $email,
          'phone' => $phone,
          'address' => $address,
          'status' => $status
        ]);
        $clientId = $pdo->lastInsertId();
        header("Location: client_details.php?id=$clientId");
        exit;
      }
    } catch (PDOException $e) {
      $error = "Error: " . $e->getMessage();
    }
  }
}

// Step 4: Load data from database
$client = null;
$clientProjects = [];

if ($clientId) {
  try {
    // Load main client data
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id');
    $stmt->execute(['id' => $clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
      $error = "Client not found";
    } else {
      // Load related projects
      $stmt = $pdo->prepare('SELECT * FROM client_projects WHERE client_id = :client_id ORDER BY start_date DESC');
      $stmt->execute(['client_id' => $clientId]);
      $clientProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
  }
}

// Step 5: Display with dynamic rendering
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $client ? htmlspecialchars($client['name']) : 'New Client'; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<div class="container mx-auto px-4 py-8">
  
  <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      <?php echo htmlspecialchars($error); ?>
    </div>
  <?php endif; ?>
  
  <?php if ($client): ?>
    <!-- Display Mode -->
    <div class="bg-white rounded-lg shadow p-6">
      <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($client['name']); ?></h1>
      
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
          <label class="font-semibold">Company:</label>
          <p><?php echo htmlspecialchars($client['company'] ?: 'N/A'); ?></p>
        </div>
        <div>
          <label class="font-semibold">Status:</label>
          <p>
            <span class="px-3 py-1 rounded-full text-sm <?php 
              echo $client['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                  ($client['status'] === 'inactive' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); 
            ?>">
              <?php echo ucfirst($client['status']); ?>
            </span>
          </p>
        </div>
        <div>
          <label class="font-semibold">Email:</label>
          <p><?php echo htmlspecialchars($client['email'] ?: 'N/A'); ?></p>
        </div>
        <div>
          <label class="font-semibold">Phone:</label>
          <p><?php echo htmlspecialchars($client['phone'] ?: 'N/A'); ?></p>
        </div>
      </div>
      
      <h2 class="text-xl font-semibold mb-3">Projects</h2>
      <?php if (count($clientProjects) > 0): ?>
        <div class="space-y-2">
          <?php foreach ($clientProjects as $project): ?>
            <div class="border rounded p-3">
              <h3 class="font-semibold"><?php echo htmlspecialchars($project['project_name']); ?></h3>
              <p class="text-sm text-gray-600">
                Value: ₹ <?php echo number_format($project['project_value'], 2); ?> | 
                Start: <?php echo date('M Y', strtotime($project['start_date'])); ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-500">No projects yet</p>
      <?php endif; ?>
    </div>
    
  <?php else: ?>
    <!-- Edit/Create Form Mode -->
    <div class="bg-white rounded-lg shadow p-6">
      <h1 class="text-3xl font-bold mb-4">
        <?php echo $clientId ? 'Edit Client' : 'New Client'; ?>
      </h1>
      
      <form method="POST">
        <div class="space-y-4">
          <div>
            <label class="block font-semibold mb-1">Name *</label>
            <input type="text" name="name" required 
                   class="w-full border rounded px-3 py-2"
                   value="<?php echo htmlspecialchars($client['name'] ?? ''); ?>">
          </div>
          
          <div>
            <label class="block font-semibold mb-1">Company</label>
            <input type="text" name="company" 
                   class="w-full border rounded px-3 py-2"
                   value="<?php echo htmlspecialchars($client['company'] ?? ''); ?>">
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold mb-1">Email</label>
              <input type="email" name="email" 
                     class="w-full border rounded px-3 py-2"
                     value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>">
            </div>
            <div>
              <label class="block font-semibold mb-1">Phone</label>
              <input type="tel" name="phone" 
                     class="w-full border rounded px-3 py-2"
                     value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
            </div>
          </div>
          
          <div>
            <label class="block font-semibold mb-1">Status</label>
            <select name="status" class="w-full border rounded px-3 py-2">
              <option value="active" <?php echo ($client['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo ($client['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
              <option value="pending" <?php echo ($client['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
          </div>
          
          <div>
            <label class="block font-semibold mb-1">Address</label>
            <textarea name="address" rows="3" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($client['address'] ?? ''); ?></textarea>
          </div>
          
          <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
            <?php echo $clientId ? 'Update Client' : 'Create Client'; ?>
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>
  
</div>

</body>
</html>

<?php
/**
 * KEY TAKEAWAYS FROM THIS PATTERN:
 * 
 * 1. Single file handles both display and edit modes
 * 2. ID parameter determines mode (view vs create)
 * 3. Database tables auto-created on first access
 * 4. POST handler manages create/update logic
 * 5. Conditional rendering based on loaded data
 * 6. Related data loaded via foreign keys
 * 7. Dynamic styling based on data values
 * 
 * APPLY THIS PATTERN TO:
 * - product_details.php
 * - worker_details.php
 * - invoice_details.php
 * - any other entity detail pages
 */
?>
