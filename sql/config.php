<?php
$host = "localhost";
$username = "root";
$password = "";
$datbase = "ripal_db_user";

$conn = new mysqli($host, $username, $password, $datbase);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}
?>
