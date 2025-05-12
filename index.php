<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Budget Buddy - Your Expense Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    body {
      background-color: #f9fafb;
      color: #333;
    }

    header {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    header h1 {
      color:rgb(24, 150, 35);
      font-size: 24px;
    }

    nav a {
      margin-left: 20px;
      text-decoration: none;
      color: rgb(18,99,149);
      font-weight: 500;
    }

    nav a:hover {
      color:rgb(13, 82, 117);
    }

    .btn-primary {
      background-color:rgb(18, 99, 149);
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
    }

    .btn-primary:hover {
      background-color:rgb(18, 99, 149);
    }

    .hero {
      text-align: center;
      padding: 80px 20px;
      background-color:rgb(18, 99, 149);
    }

    .hero h2 {
      font-size: 36px;
      color:white;
      margin-bottom: 20px;
    }

    .hero p {
      font-size: 18px;
      margin-bottom: 30px;
      color: white;
    }

    .features {
      padding: 60px 20px;
      background-color: #ffffff;
    }

    .features h3 {
      text-align: center;
      font-size: 28px;
      color:rgb(17, 48, 102);
      margin-bottom: 40px;
    }

    .feature-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
    }

    .feature {
      flex: 1 1 250px;
      max-width: 300px;
      text-align: center;
      padding: 20px;
      border-radius: 8px;
    }

    .feature-icon {
      font-size: 40px;
      margin-bottom: 15px;
      color:rgb(10, 38, 90);
    }

    .feature h4 {
      font-size: 20px;
      margin-bottom: 10px;
      color:rgb(12, 55, 147);
    }

    .feature p {
      font-size: 16px;
      color: #555;
    }

    footer {
      background-color: #f3f4f6;
      text-align: center;
      text-color: black
      padding: 20px;
      font-size: 14px;
      color: #555;
      margin-top: 40px;
    } 

    @media (max-width: 600px) {
      .feature-grid {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
    <h1>Budget Buddy</h1>
    <nav>
      <!-- <a href="#features">Features</a> -->
      <a href="login.php">Login</a>
      <a href="register.php" class="btn-primary">Get Started</a>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <h2>Take Control of Your Finances</h2>
    <p>Track expenses, set budgets, and gain insights with Budget Buddy.</p>
    <!-- <a href="register.php" class="btn-primary">Start Free</a> -->
  </section>

  <!-- Features Section -->
  <section class="features" id="features">
    <h3>Why Choose Budget Buddy?</h3>
    <div class="feature-grid">
      <div class="feature">
        <div class="feature-icon"></div>
        <h4>Smart Budgeting</h4>
        <p>Allocate budgets across categories and monitor your spending effectively.</p>
      </div>
      <div class="feature">
        <div class="feature-icon"></div>
        <h4>Expense Tracking</h4>
        <p>Track daily expenses with ease and get alerts on over-spending.</p>
      </div>
      
    </div>
  </section>
<?php include 'footer.php';?>


</body>
</html>
