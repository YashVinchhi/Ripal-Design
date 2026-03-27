<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "Ripal-Design";

// Do not instantiate connections at include-time if extensions are missing.
$conn = new mysqli($host, $username, $password, $database) or die("Connection failed: " . mysqli_connect_error());

// Must be called after every $stmt->execute() that uses CALL ProcedureName()
function flush_stored_results($conn)
{
    while ($conn->more_results() && $conn->next_result()) {
        $extra = $conn->use_result();
        if ($extra instanceof mysqli_result) {
            $extra->free();
        }
    }
}
?>