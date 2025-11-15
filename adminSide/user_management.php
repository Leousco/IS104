<?php
include("../config.php");
include("../auth.php");
$loginPage = "/SADPROJ/login.php";

// Only allow logged-in users
if (!isset($_SESSION['UserID'])) {
    header("Location: $loginPage");
    exit();
}

// Optional: role-based protection
if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "ADMIN") {
    header("Location: $loginPage?error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Accounts</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --header-green: #a7f3d0;
      --accent-green: #4ade80;
      --bg: #f9fafb;
      --card: #ffffff;
      --text-dark: #111827;
      --sidebar-bg: #1f2937;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
    }

    body {
      background-color: var(--bg);
      color: var(--text-dark);
      overflow-x: hidden;
    }

    body::-webkit-scrollbar {
      display: none;
    }

    /* ✅ Header */
    header {
      background-color: var(--header-green);
      color: #064e3b;
      display: flex;
      align-items: center;
      padding: 12px 16px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 20;
    }

    header h1 {
      margin-left: 10px;
      font-size: 20px;
      font-weight: 600;
    }

    .menu-icon {
      font-size: 24px;
      cursor: pointer;
      color: #064e3b;
    }

    .profile {
      font-size: 18px;
      margin-right: 10px;
      white-space: nowrap;
    }

    /* ✅ Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: -280px;
      width: 280px;
      height: 100%;
      background-color: var(--sidebar-bg);
      color: white;
      padding: 20px;
      transition: left 0.3s ease;
      z-index: 30;
      box-shadow: 2px 0 10px rgba(0,0,0,0.3);
      display: flex;
      flex-direction: column;
    }

    .sidebar.open {
      left: 0;
    }

    .sidebar h2 {
      font-size: 22px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      margin-bottom: 20px;
      font-weight: 700;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      color: white;
      text-decoration: none;
      padding: 12px 15px;
      border-radius: 6px;
      transition: background-color 0.2s ease, color 0.2s ease;
      margin-bottom: 5px;
    }

    .sidebar a i {
      margin-right: 12px;
      width: 20px;
      text-align: center;
    }

    .sidebar a:hover {
      background-color: rgba(255, 255, 255, 0.1);
      color: var(--accent-green);
    }

    /* OVERLAY */
    .overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.4);
      /* backdrop-filter: blur(2px); */
      display: none;
      z-index: 25;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .overlay.show {
      display: block;
      opacity: 1;
    }

    main {
      padding: 20px;
      animation: slideIn 0.6s ease;
    }

    /* ✅ Page Title */
    h2 {
      margin: 85px 0 15px;
      text-align: center;
    }

    /* ✅ Table */
    .table-container {
      background: var(--card);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.08);
      margin-top: 20px;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 12px;
      text-align: center;
      border-bottom: 1px solid #e5e7eb;
    }

    th {
      background-color: var(--header-green);
      color: #064e3b;
      font-weight: 600;
    }

    tr:hover {
      background-color: #f1f5f9;
    }

    .no-data {
      text-align: center;
      color: #777;
      padding: 20px;
    }

    .balance {
      color: #2563eb;
      font-weight: 700;
    }

    main { padding: 20px; animation: slideIn 0.6s ease; }
    @keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
  </style>
</head>
<body>

<!-- HEADER AND SIDEBAR -->
  <header class="bg-green-200 text-green-900 p-4 shadow-md flex items-center">
    <div class="menu-icon" id="menuBtn">
      <i class="fas fa-bars"></i>
    </div>
    <h1 class="flex items-center">
      <i class="fas fa-users mr-2 text-green-700"></i>
      User Accounts
    </h1>
  </header>

  <div class="sidebar" id="sidebar">
    <h2 class="text-green-400">
      <i class="fas fa-grip-vertical"></i>
      Menu
    </h2>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
    <a href="user_management.php" class="bg-green-600 rounded"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
    <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
    <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
    <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
  </div>

  <div class="overlay" id="overlay"></div>




  <!-- ✅ User Table -->
<main>
  <div class="table-container">
      <table id="userTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Username</th>
            <th>Account Balance</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>johndenver.give@email.com</td>
            <td>John Denven Gice</td>
            <td class="balance">0.00</td>
          </tr>
          <tr>
            <td>2</td>
            <td>ryan.galvan@email.com</td>
            <td>Ryan Angelo</td>
            <td class="balance">1,000,000.00</td>
          </tr>
        </tbody>
      </table>
  </div>
</main>

  <script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const menuBtn = document.getElementById('menuBtn');

    function toggleSidebar() {
      const isOpen = sidebar.classList.contains('open');
      if (!isOpen) {
        sidebar.classList.add('open');
        overlay.classList.add('show');
      } else {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
      }
    }

    menuBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
  </script>

</body>
</html>
