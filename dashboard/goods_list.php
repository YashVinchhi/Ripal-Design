<?php
// Redirect goods_list.php to goods_invoice.php — keep one canonical invoice route
session_start();
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($project_id) {
    header('Location: goods_invoice.php?project_id=' . $project_id);
} else {
    header('Location: dashboard.php');
}
exit;
