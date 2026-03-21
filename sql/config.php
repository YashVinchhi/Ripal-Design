<?php
$host = "localhost";
$username = "devadmin";
$password = "Ro0t1234";
$database = "ripal_db_user";

$conn = null;
try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        $conn = null;
    }
} catch (Exception $e) {
    $conn = null;
}
