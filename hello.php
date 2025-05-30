<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

  <!-- Sidebar -->
  <div class="flex h-screen">
    <aside class="w-64 bg-white shadow-lg">
      <div class="p-4 text-2xl font-bold text-blue-600">Budget Buddy</div>
      <nav class="mt-6 space-y-2">
        <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-100">
          <span class="material-icons mr-3">dashboard</span> Dashboard
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-100">
          <span class="material-icons mr-3">add</span> Add Expenses
        </a>
        <a href="#" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-100">
          <span class="material-icons mr-3">pie_chart</span> Reports
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
      <!-- Top Navbar -->
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
        <div class="text-gray-600">Welcome, User</div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-4 shadow rounded-lg">
          <h2 class="text-gray-500 text-sm">Total Budget</h2>
          <p class="text-xl font-bold text-green-600">₹50,000</p>
        </div>
        <div class="bg-white p-4 shadow rounded-lg">
          <h2 class="text-gray-500 text-sm">Total Expenses</h2>
          <p class="text-xl font-bold text-red-500">₹35,000</p>
        </div>
        <div class="bg-white p-4 shadow rounded-lg">
          <h2 class="text-gray-500 text-sm">Remaining</h2>
          <p class="text-xl font-bold text-blue-500">₹15,000</p>
        </div>
      </div>

      <!-- Add more dashboard sections here -->
    </main>
  </div>

  <!-- Material Icons CDN -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</body>
</html>
