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

$userID = $_SESSION['UserID'];

$sql = "SELECT Role FROM users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || $user['Role'] !== 'ADMIN') {
    die("Access denied. Admins only.");
}

// Handle actions (Mark Fixed or Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'fix' && $id > 0) {
        // Mark as Fixed
        $sql = "UPDATE bug_reports SET status = 'Fixed', date_fixed = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'delete' && $id > 0) {
        // Delete report
        $sql = "DELETE FROM bug_reports WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// Fetch all bug reports
$sql = "SELECT id, title, description, reported_by, status, date_reported, date_fixed FROM bug_reports ORDER BY date_reported DESC";
$result = $conn->query($sql);
$reports = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bug Reports</title>

  <script src="https://cdn.tailwindcss.com"></script>
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

    .overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.4);
      display: none;
      z-index: 25;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .overlay.show {
      display: block;
      opacity: 1;
    }

    /* ✅ Page Title */
    h2 {
      margin: 85px 0 15px;
      text-align: center;
    }

    main {
      padding: 20px;
      animation: slideIn 0.6s ease;
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

    /* ✅ Buttons */
    .btn {
      padding: 6px 12px;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      font-size: 14px;
      color: #fff;
    }

    .btn-fix {
      background: #007bff;
    }

    .btn-delete {
      background: red;
    }

    .btn:hover {
      opacity: 0.8;
    }

    /* ✅ Status Styles */
    .status-fixed {
      color: green;
      font-weight: bold;
    }

    .status-pending {
      color: orange;
      font-weight: bold;
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
      <i class="fas fa-bug mr-2 text-green-700"></i>
      Bug Reports
    </h1>
  </header>

  <div class="sidebar" id="sidebar">
    <h2 class="text-green-400">
      <i class="fas fa-grip-vertical"></i>
      Menu
    </h2>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="admin_bugreport.php" class="bg-green-600 rounded"><i class="fas fa-bug"></i> User Report</a>
    <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
    <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
    <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
    <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions </a>
    <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
  </div>

  <div class="overlay" id="overlay"></div>

  <!-- ✅ Bug Report Table -->
<main>
  <h2 class="text-2xl font-bold mb-6 text-gray-800">Bug Reports</h2>
  <div class="table-container">
    <table id="bugTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Description</th>
          <th>Reported By</th>
          <th>Status</th>
          <th>Date Reported</th>
          <th>Fixed Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reports)): ?>
          <tr>
            <td colspan="8" class="no-data text-center py-4">No bug reports yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($reports as $report): ?>
            <tr data-id="<?php echo $report['id']; ?>">
              <td><?php echo htmlspecialchars($report['id']); ?></td>
              <td><?php echo htmlspecialchars($report['title']); ?></td>
              <td><?php echo htmlspecialchars($report['description']); ?></td>
              <td><?php echo htmlspecialchars($report['reported_by']); ?></td>
              <td class="<?php echo $report['status'] === 'Fixed' ? 'status-fixed' : 'status-pending'; ?>">
                <?php echo htmlspecialchars($report['status']); ?>
              </td>
              <td><?php echo htmlspecialchars(date('m-d-Y g:i A', strtotime($report['date_reported']))); ?></td>
              <td><?php echo $report['date_fixed'] ? htmlspecialchars(date('m-d-Y g:i A', strtotime($report['date_fixed']))) : '-'; ?></td>
              <td>
                <?php if ($report['status'] !== 'Fixed'): ?>
                  <button class="btn btn-fix" onclick="markFixed(<?php echo $report['id']; ?>, this)">Mark as Fixed</button>
                <?php endif; ?>
                <button class="btn btn-delete" onclick="deleteRow(<?php echo $report['id']; ?>, this)">Delete</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
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

    // ✅ Mark a bug as fixed (AJAX)
    function markFixed(id, button) {
      if (confirm("Mark this report as fixed?")) {
        fetch('admin_bugreport.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=fix&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const row = button.closest("tr");
            const statusCell = row.querySelector("td:nth-child(5)");
            const fixedDateCell = row.querySelector("td:nth-child(7)");
            statusCell.textContent = "Fixed";
            statusCell.className = "status-fixed";
            const now = new Date();
            fixedDateCell.textContent = now.toLocaleDateString() + " " + now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
            button.style.display = "none";
          } else {
            alert("Error updating report.");
          }
        });
      }
    }

    // ✅ Delete a bug report row (AJAX)
    function deleteRow(id, button) {
      if (confirm("Are you sure you want to delete this bug report?")) {
        fetch('admin_bugreport.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=delete&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const row = button.closest("tr");
            row.remove();
            const tableBody = document.querySelector("#bugTable tbody");
            if (tableBody.children.length === 0) {
              tableBody.innerHTML = `<tr><td colspan="8" class="no-data text-center py-4">No bug reports yet.</td></tr>`;
            }
          } else {
            alert("Error deleting report.");
          }
        });
      }
    }
</script>

</body>
</html>
