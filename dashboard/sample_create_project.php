<?php
/**
 * Sample file to demonstrate creating projects dynamically
 * This shows how to populate the database with sample project data
 */

require_once __DIR__ . '/../includes/init.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Database connection not available');
}

try {
    // Sample Project 1: Renovation
    $stmt = $pdo->prepare('
        INSERT INTO projects (name, status, progress, budget, due, location, latitude, longitude, owner_name, owner_contact, owner_email)
        VALUES (:name, :status, :progress, :budget, :due, :location, :latitude, :longitude, :owner_name, :owner_contact, :owner_email)
    ');
    
    $project1 = [
        'name' => 'Renovation — Oak Street Residence',
        'status' => 'ongoing',
        'progress' => 45,
        'budget' => 4500000, // ₹ 45,00,000
        'due' => '2026-06-30',
        'location' => '123 Oak St, Rajkot, Gujarat',
        'latitude' => 22.3039,
        'longitude' => 70.8022,
        'owner_name' => 'Amitbhai Patel',
        'owner_contact' => '+91 98765 43210',
        'owner_email' => 'amit.patel@example.com'
    ];
    
    $stmt->execute($project1);
    $project1Id = $pdo->lastInsertId();
    echo "Created Project 1: ID = $project1Id\n";
    
    // Add milestones for Project 1
    $milestoneStmt = $pdo->prepare('
        INSERT INTO project_milestones (project_id, title, target_date, status)
        VALUES (:project_id, :title, :target_date, :status)
    ');
    
    $milestones1 = [
        ['title' => 'Foundation Completion', 'target_date' => '2026-02-28', 'status' => 'active'],
        ['title' => 'Material Procurement', 'target_date' => '2026-03-15', 'status' => 'pending'],
        ['title' => 'Electrical Rough-in', 'target_date' => '2026-04-05', 'status' => 'pending']
    ];
    
    foreach ($milestones1 as $milestone) {
        $milestone['project_id'] = $project1Id;
        $milestoneStmt->execute($milestone);
    }
    echo "Added " . count($milestones1) . " milestones to Project 1\n";
    
    // Add workers for Project 1
    $workerStmt = $pdo->prepare('
        INSERT INTO project_workers (project_id, worker_name, worker_role, worker_contact)
        VALUES (:project_id, :worker_name, :worker_role, :worker_contact)
    ');
    
    $workers1 = [
        ['worker_name' => 'Ramesh Kumar', 'worker_role' => 'Plumber', 'worker_contact' => '+91 98765 11111'],
        ['worker_name' => 'Suresh Bhai', 'worker_role' => 'Electrician', 'worker_contact' => '+91 98765 22222']
    ];
    
    foreach ($workers1 as $worker) {
        $worker['project_id'] = $project1Id;
        $workerStmt->execute($worker);
    }
    echo "Added " . count($workers1) . " workers to Project 1\n";
    
    // Sample Project 2: New Construction
    $project2 = [
        'name' => 'New Construction — Satellite Township',
        'status' => 'planning',
        'progress' => 10,
        'budget' => 12500000, // ₹ 1,25,00,000
        'due' => '2027-03-31',
        'location' => 'Satellite Road, Ahmedabad, Gujarat',
        'latitude' => 23.0225,
        'longitude' => 72.5714,
        'owner_name' => 'Rajesh Mehta',
        'owner_contact' => '+91 99999 88888',
        'owner_email' => 'rajesh.mehta@example.com'
    ];
    
    $stmt->execute($project2);
    $project2Id = $pdo->lastInsertId();
    echo "Created Project 2: ID = $project2Id\n";
    
    // Add milestones for Project 2
    $milestones2 = [
        ['title' => 'Site Survey', 'target_date' => '2026-03-01', 'status' => 'pending'],
        ['title' => 'Design Approval', 'target_date' => '2026-04-15', 'status' => 'pending'],
        ['title' => 'Foundation Work', 'target_date' => '2026-06-01', 'status' => 'pending']
    ];
    
    foreach ($milestones2 as $milestone) {
        $milestone['project_id'] = $project2Id;
        $milestoneStmt->execute($milestone);
    }
    echo "Added " . count($milestones2) . " milestones to Project 2\n";
    
    // Sample Project 3: Commercial Building
    $project3 = [
        'name' => 'Commercial Complex — City Center',
        'status' => 'completed',
        'progress' => 100,
        'budget' => 8500000, // ₹ 85,00,000
        'due' => '2026-01-31',
        'location' => 'City Center, Rajkot, Gujarat',
        'latitude' => 22.2912,
        'longitude' => 70.7954,
        'owner_name' => 'Priya Shah',
        'owner_contact' => '+91 97654 32100',
        'owner_email' => 'priya.shah@example.com'
    ];
    
    $stmt->execute($project3);
    $project3Id = $pdo->lastInsertId();
    echo "Created Project 3: ID = $project3Id\n";
    
    echo "\nSample projects created successfully!\n";
    echo "Visit: project_details.php?id=$project1Id\n";
    echo "Visit: project_details.php?id=$project2Id\n";
    echo "Visit: project_details.php?id=$project3Id\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
