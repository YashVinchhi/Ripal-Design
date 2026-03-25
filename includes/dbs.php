<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$con = mysqli_connect("localhost", "root", "");
if (!$con) {
    fwrite(STDERR, "Connection Failed: " . mysqli_connect_error() . PHP_EOL);
    exit(1);
}

// $create_db = "CREATE DATABASE IF NOT EXISTS `Ripal-Design`";

// if(mysqli_query($con,$create_db)){
//     echo "Database created successfully";
// }else{
//     echo "Error creating database: ".mysqli_error($con);    
// }

// mysqli_select_db( $con, "Ripal-Design");

// $table = "CREATE TABLE IF NOT EXISTS signup(
//     id INT(11) AUTO_INCREMENT PRIMARY KEY,
//     first_name VARCHAR(255),
//     last_name VARCHAR(255),
//     email VARCHAR(255) NOT NULL UNIQUE,
//     password VARCHAR(255) NOT NULL,
//     phone VARCHAR(20),
    
// )";

mysqli_close($con);

?>