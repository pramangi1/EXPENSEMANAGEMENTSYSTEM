<?php
$servername = "localhost";
$user = "root";
$pass= "";
$dbname = "expensedaily";

// Connect to MySQL
$conn = new mysqli($servername, $user, $pass);

$conn->select_db($dbname);

?>