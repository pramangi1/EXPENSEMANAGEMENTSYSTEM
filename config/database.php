<?php
$servername = "localhost";
$user = "root";
$pass = "";
$dbname = "expensedaily";

// Connect to MySQL
$conn = new mysqli($servername, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    die("Database creation failed: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    u_id INT AUTO_INCREMENT PRIMARY KEY,
    u_name VARCHAR(100) NOT NULL,
    u_email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Insert Default User if not exists
$default_email = 'user@gmail.com';
$result = $conn->query("SELECT u_id FROM users WHERE u_email = '$default_email' LIMIT 1");

if ($result->num_rows === 0) {
    $hashed_password = password_hash('user123', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (u_name, u_email, password_hash) 
                  VALUES ('Default User', '$default_email', '$hashed_password')");
}

// Get Default User ID
$result = $conn->query("SELECT u_id FROM users WHERE u_email = '$default_email' LIMIT 1");
$row = $result->fetch_assoc();
$default_u_id = $row['u_id'] ?? 1; // Fallback if user is missing

// Create Budget Year Table
$sql = "CREATE TABLE IF NOT EXISTS budget_year (
    year_id INT AUTO_INCREMENT PRIMARY KEY,
    u_id INT NOT NULL, 
    year_name VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE
)";
$conn->query($sql);

// Create Budget Month Table
$sql = "CREATE TABLE IF NOT EXISTS budget_month (
    month_id INT AUTO_INCREMENT PRIMARY KEY,
    month_name VARCHAR(20) NOT NULL,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create Budget Table
$sql = "CREATE TABLE IF NOT EXISTS budget (
    budget_id INT AUTO_INCREMENT PRIMARY KEY,
    month_id INT NOT NULL,
    year_id INT NOT NULL,
    u_id INT NOT NULL,
    bname VARCHAR(100) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE CASCADE,
    FOREIGN KEY (year_id) REFERENCES budget_year(year_id) ON DELETE CASCADE,
    FOREIGN KEY (month_id) REFERENCES budget_month(month_id) ON DELETE CASCADE
)";
$conn->query($sql);

// Create Budget Head Table - This part is correct
$sql = "CREATE TABLE IF NOT EXISTS budget_head (
    bhid INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    is_mandatory BOOLEAN NOT NULL DEFAULT FALSE,
    u_id INT NULL,
    FOREIGN KEY (u_id) REFERENCES users(u_id) ON DELETE SET NULL
) ";

$conn->query($sql);


// Create Budget Head Amount Allocation Table
$sql = "CREATE TABLE IF NOT EXISTS budget_head_amount (
    bha_id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    bhid INT NOT NULL,
    allocated_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budget(budget_id) ON DELETE CASCADE,
    FOREIGN KEY (bhid) REFERENCES budget_head(bhid) ON DELETE CASCADE
)";
$conn->query($sql);

// Create Expenses Table
$sql = "CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    bhid INT NOT NULL,
    budget_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budget(budget_id) ON DELETE CASCADE,
    FOREIGN KEY (bhid) REFERENCES budget_head(bhid) ON DELETE CASCADE
)";
$conn->query($sql);
 

 $sql="CREATE TABLE IF NOT EXISTS saving (
    saving_id INT AUTO_INCREMENT PRIMARY KEY,
    bha_id INT NOT NULL,
    allocated_amount DECIMAL(10,2) NOT NULL,
    saved_amount DECIMAL(10,2) NOT NULL,
    saved_month INT NOT NULL,
    saved_year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bha_id) REFERENCES budget_head_amount(bha_id) ON DELETE CASCADE
)";
$conn->query($sql);
// Close connection
$conn->close();

// echo "All tables created successfully and default data inserted.";
?>
