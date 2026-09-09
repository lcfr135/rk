<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "casino";


$conn = new mysqli(
    $host,
    $user,
    $pass,
    $db
);


if($conn->connect_error){

    die("Database gagal terhubung");

}

?>