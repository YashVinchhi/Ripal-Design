<?php

$con = mysqli_connect("localhost", "root", "");
if(!$con){
    die("Connection Failed: " . mysqli_connect_error());
}else{
    echo "Connected successfully";
}

// $create_db = "CREATE DATABASE IF NOT EXISTS ripal_db";

// if(mysqli_query($con,$create_db)){
//     echo "Database created successfully";
// }else{
//     echo "Error creating database: ".mysqli_error($con);    
// }

// mysqli_select_db( $con, "ripal_db");

// $table = "CREATE TABLE IF NOT EXISTS signup(
//     id INT(11) AUTO_INCREMENT PRIMARY KEY,
//     first_name VARCHAR(255),
//     last_name VARCHAR(255),
//     email VARCHAR(255) NOT NULL UNIQUE,
//     password VARCHAR(255) NOT NULL,
//     phone VARCHAR(20),
    
// )";

if(mysqli_query($con, $update)){
    echo "Table updated successfully";
}

?>