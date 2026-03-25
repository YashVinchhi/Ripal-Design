<?php

session_start();    
require_once "../sql/db_config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $status = trim($_POST['status'] ?? 'planning');
    $due = trim($_POST['due'] ?? '');
    $progress = isset($_POST['progress']) ? (int)$_POST['progress'] : 0;
    $location = trim($_POST['location'] ?? '');

    // Basic server-side validation
    if ($name === '' || $status === '' || $location === '') {
        $_SESSION['project_error'] = 'Please fill all required fields.';
        $_SESSION['active_form'] = 'projects';
        header('Location: project_details.php');
        exit();
    }

    if (!$conn || $conn->connect_error) {
        $_SESSION['project_error'] = 'Database connection unavailable. Please try later.';
        $_SESSION['active_form'] = 'projects';
        header('Location: project_details.php');
        exit();
    }

    // Keep progress in allowed range.
    $progress = max(0, min(100, $progress));

    // Ensure projects table exists so insert works on fresh DB.
    $conn->query("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        status VARCHAR(50) DEFAULT 'planning',
        due DATE DEFAULT NULL,
        progress INT DEFAULT 0,
        location VARCHAR(255) DEFAULT NULL,
        owner_name VARCHAR(255) DEFAULT NULL,
        owner_contact VARCHAR(100) DEFAULT NULL,
        worker_name VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check duplicate project name
    $chk = $conn->prepare('SELECT 1 FROM projects WHERE name = ? LIMIT 1');
    $chk->bind_param('s', $name);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        $_SESSION['project_error'] = 'Project name already exists. Please use a different name.';
        $_SESSION['active_form'] = 'projects';
        header('Location: project_details.php');
        exit();
    }
    $chk->close();

    $dueValue = $due !== '' ? $due : null;
    $ins = $conn->prepare('INSERT INTO projects (name, status, due, progress, location) VALUES (?, ?, ?, ?, ?)');
    $ins->bind_param('sssis', $name, $status, $dueValue, $progress, $location);

    if ($ins->execute()) {
        $project_id = $conn->insert_id;
        $ins->close();

        $_SESSION['project_success'] = 'Project created successfully.';
        $_SESSION['project_id'] = $project_id;
        header('Location: ../dashboard/dashboard.php');
        exit();
    }

    $ins->close();
    $_SESSION['project_error'] = 'Failed to create project. Please try again.';
    $_SESSION['active_form'] = 'projects';
    header('Location: project_details.php');
    exit();
}

header('Location: project_details.php');
exit();
        