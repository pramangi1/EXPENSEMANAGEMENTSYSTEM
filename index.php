<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Budget Buddy - Your Expense Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- ✅ Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

    .branding {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 24px;
      font-weight: bold;
      color: rgb(24, 150, 35);
    }

    .logo {
      height: 40px;
      width: 40px;
      object-fit: contain;
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

  <header>
    <div class="branding">
      <img src="image/expenseslogo1.png" alt="Logo" class="logo">
      <span>Budget Buddy</span>
    </div>

    <nav>
      <a href="login.php">Login</a>
      <a href="register.php" class="btn-primary">Get Started</a>
    </nav>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <h2>Take Control of Your Finances</h2>
    <p>Track expenses, set budgets, and gain insights with Budget Buddy.</p>
  </section>

  
<section class="features" id="features">
  <h3>Why Choose Budget Buddy?</h3>
  <div class="feature-grid">
    <div class="feature">
      <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
      <h4>Smart Budgeting</h4>
      <p>Allocate budgets across categories and monitor your spending effectively.</p>
    </div>
    <div class="feature">
      <div class="feature-icon"><i class="fas fa-wallet"></i></div>
      <h4>Expense Tracking</h4>
      <p>Track daily expenses with ease and get alerts on over-spending.</p>
    </div>
    <div class="feature">
      <div class="feature-icon"><i class="fas fa-piggy-bank"></i></div>
      <h4>Monthly Savings</h4>
      <p>Save automatically each month and watch your financial health grow.</p>
    </div>
    <div class="feature">
      <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
      <h4>Insightful Reports</h4>
      <p>Visualize where your money goes and plan better with rich reports.</p>
    </div>
  </div>
</section>

  <?php include 'footer.php'; ?>

</body>
</html>
