<?php
$host = "192.168.1.64";
$username = "devadmin";
$password = "";
$datbase = "ripal_db_user";

$conn = new mysqli($host, $username, $password, $datbase);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}
?>
