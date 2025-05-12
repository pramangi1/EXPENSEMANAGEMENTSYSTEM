<?php
$servername = "localhost";
$user = "root";
$pass= "";
$dbname = "expensedaily";

// Connect to MySQL
$conn = new mysqli($servername, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert month details into budget_month table


 $sql="INSERT INTO budget_month (month_name) VALUES 
('January'), ('February'), ('March'), ('April'),
 ('May'), ('June'), ('July'), ('August'),
 ('September'), ('October'), ('November'), ('December')";
$conn->query($sql);

?>