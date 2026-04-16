<?php
// One-off script to insert or update an admin user (safe to run multiple times)
require_once __DIR__ . '/../includes/init.php';

$targetEmail = 'yashhvinchhi@gmail.com';
$username = 'yashhvinchhi';
$password = 'Ro0t@1234';
$role = 'admin';

$db = get_db();
if (!($db instanceof PDO)) {
    fwrite(STDERR, "Database connection unavailable\n");
    exit(1);
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$targetEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($user) {
        $id = (int)$user['id'];
        $update = $db->prepare('UPDATE users SET username = ?, password_hash = ?, role = ?, status = ? WHERE id = ?');
        $update->execute([$username, $hash, $role, 'active', $id]);
        echo "Updated existing user id $id\n";
    } else {
        $insert = $db->prepare('INSERT INTO users (username, full_name, first_name, last_name, email, phone, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $insert->execute([$username, 'Yash Vinchhi', 'Yash', 'Vinchhi', $targetEmail, '', $hash, $role, 'active']);
        $id = (int)$db->lastInsertId();
        echo "Inserted new user id $id\n";
    }

    $db->commit();

    $stmt = $db->prepare('SELECT id, username, email, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "User: " . json_encode($row) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Database error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
