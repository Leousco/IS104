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

// Handle Approve/Reject actions (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
  $id = intval($_POST['id']);
  $status = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';

  // 1️⃣ Update application status
  $stmt = $conn->prepare("UPDATE discount_applications SET Status = ?, ReviewedAt = NOW() WHERE ApplicationID = ?");
  $stmt->bind_param("si", $status, $id);
  $stmt->execute();


  if ($status === 'Approved') {
      $conn->query("UPDATE users u
                    JOIN discount_applications d ON u.Email = d.Email
                    SET u.HasDiscount = 1
                    WHERE d.ApplicationID = $id");
  } else {
      $conn->query("UPDATE users u
                    JOIN discount_applications d ON u.Email = d.Email
                    SET u.HasDiscount = 0
                    WHERE d.ApplicationID = $id");
  }

  // 3️⃣ Return success response
  echo json_encode(['success' => true]);
  exit;
}


// Fetch all by category
$categories = ['Student', 'PWD', 'Senior', 'Government'];
$data = [];
foreach ($categories as $cat) {
    $stmt = $conn->prepare("SELECT * FROM discount_applications WHERE Category = ? ORDER BY SubmittedAt DESC");
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $data[$cat] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Discount Applications</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<style>
:root {
  --header-green: #a7f3d0;
  --accent-green: #4ade80;
  --bg: #f9fafb;
  --card: #ffffff;
  --text-dark: #111827;
  --sidebar-bg: #1f2937;
}

/* --- Base Styles --- */
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
  width: 0;
}

/* --- Header --- */
header {
  background-color: var(--header-green);
  color: #064e3b;
  display: flex;
  align-items: center;
  padding: 12px 16px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 20;
}

header h1 {
  margin-left: 10px;
  font-size: 20px;
  font-weight: 600;
}

/* --- Sidebar --- */
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
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
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
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
  border-bottom: none;
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

/* --- Main --- */
main {
  padding: 20px;
  animation: slideIn 0.6s ease;
}

@keyframes slideIn {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* --- Menu Icon --- */
.menu-icon {
  font-size: 24px;
  cursor: pointer;
  color: #064e3b;
}

/* --- Overlay --- */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: none;
  z-index: 25;
}

.overlay.show {
  display: block;
}

/* --- Category Cards --- */
.category-card {
  transition: all 0.2s ease;
  cursor: pointer;
}

.category-card:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
</style>
</head>

<body>

<!-- HEADER -->
<header class="bg-green-200 text-green-900 p-4 shadow-md flex items-center">
    <div class="menu-icon" id="menuBtn"><i class="fas fa-bars"></i></div>
    <h1><i class="fas fa-ticket-alt text-green-700"></i> Voucher Management</h1>
</header>

<div class="sidebar" id="sidebar">
    <h2 class="text-green-400"><i class="fas fa-grip-vertical"></i> Menu</h2>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
    <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
    <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
    <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
    <a href="discount_applications.php" class="bg-green-600 rounded"><i class="fas fa-tags"></i> Discount Applications</a>
</div>

<div class="overlay" id="overlay"></div>


<!-- MAIN CONTENT -->
<main>
<div class="p-6 space-y-6">
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="category-card bg-white border-l-4 border-green-600 p-4 rounded-lg text-center" onclick="toggleTable('Student')">
      <h2 class="text-lg font-semibold text-green-800">🎓 Student</h2>
      <p class="text-gray-500 text-sm"><?php echo count($data['Student']); ?> applications</p>
    </div>
    <div class="category-card bg-white border-l-4 border-blue-600 p-4 rounded-lg text-center" onclick="toggleTable('PWD')">
      <h2 class="text-lg font-semibold text-blue-800">♿ PWD</h2>
      <p class="text-gray-500 text-sm"><?php echo count($data['PWD']); ?> applications</p>
    </div>
    <div class="category-card bg-white border-l-4 border-yellow-600 p-4 rounded-lg text-center" onclick="toggleTable('Senior')">
      <h2 class="text-lg font-semibold text-yellow-800">👴 Senior Citizen</h2>
      <p class="text-gray-500 text-sm"><?php echo count($data['Senior']); ?> applications</p>
    </div>
    <div class="category-card bg-white border-l-4 border-purple-600 p-4 rounded-lg text-center" onclick="toggleTable('Government')">
      <h2 class="text-lg font-semibold text-purple-800">🏛️ Government</h2>
      <p class="text-gray-500 text-sm"><?php echo count($data['Government']); ?> applications</p>
    </div>
  </div>

  <!-- CATEGORY TABLES -->
  <?php foreach ($categories as $cat): ?>
  <div id="table-<?php echo $cat; ?>" class="hidden bg-white shadow rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold text-green-800"><?php echo $cat; ?> Applications</h2>
      <button onclick="toggleTable('<?php echo $cat; ?>')" class="text-sm text-gray-500 hover:text-green-600">✖ Close</button>
    </div>

    <?php if (!empty($data[$cat])): ?>
    <div class="overflow-x-auto">
      <table class="min-w-full border border-gray-200 rounded-lg text-sm">
        <thead class="bg-green-100 text-green-900">
          <tr>
            <th class="px-4 py-2 border">#</th>
            <th class="px-4 py-2 border">Applicant</th>
            <th class="px-4 py-2 border">Email</th>
            <th class="px-4 py-2 border">Documents</th>
            <th class="px-4 py-2 border">Notes</th>
            <th class="px-4 py-2 border">Status</th>
            <th class="px-4 py-2 border">Submitted</th>
            <th class="px-4 py-2 border">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($data[$cat] as $row): ?>
          <tr class="hover:bg-gray-50">
            <td class="border px-3 py-2 text-center"><?php echo $row['ApplicationID']; ?></td>
            <td class="border px-3 py-2"><?php echo htmlspecialchars($row['FullName']); ?></td>
            <td class="border px-3 py-2"><?php echo htmlspecialchars($row['Email']); ?></td>
            <td class="border px-3 py-2 text-sm space-y-1">
              <?php if ($row['ID_Front']): ?><a href="../<?php echo $row['ID_Front']; ?>" target="_blank" class="text-blue-600 hover:underline">ID Front</a><br><?php endif; ?>
              <?php if ($row['ID_Back']): ?><a href="../<?php echo $row['ID_Back']; ?>" target="_blank" class="text-blue-600 hover:underline">ID Back</a><br><?php endif; ?>
              <?php if ($row['ProofOfEnrollment']): ?><a href="../<?php echo $row['ProofOfEnrollment']; ?>" target="_blank" class="text-blue-600 hover:underline">Supporting Doc</a><?php endif; ?>
            </td>
            <td class="border px-3 py-2 text-sm"><?php echo nl2br(htmlspecialchars($row['Notes'])); ?></td>
            <td class="border px-3 py-2 text-center">
              <span class="px-3 py-1 rounded-full text-xs font-semibold
                <?php echo $row['Status'] === 'Approved' ? 'bg-green-100 text-green-800' :
                      ($row['Status'] === 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                <?php echo $row['Status'] ?: 'Pending'; ?>
              </span>
            </td>
            <td class="border px-3 py-2 text-xs text-center"><?php echo date("M d, Y", strtotime($row['SubmittedAt'])); ?></td>
            <td class="border px-3 py-2 text-center">
              <?php if ($row['Status'] === 'Pending'): ?>
              <button data-id="<?php echo $row['ApplicationID']; ?>" data-act="approve" class="actionBtn bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">Approve</button>
              <button data-id="<?php echo $row['ApplicationID']; ?>" data-act="reject" class="actionBtn bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs ml-2">Reject</button>
              <?php else: ?>
              <span class="text-gray-500 italic">No action</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-gray-500 text-center py-6">No <?php echo strtolower($cat); ?> applications found.</p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
</main>

<script>

// HEADER SCRIPT
const sidebar = document.getElementById('sidebar');
    const menuBtn = document.getElementById('menuBtn');
    const overlay = document.getElementById('overlay');

    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('show');
    });

    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });


// Toggle category tables
function toggleTable(cat) {
  document.querySelectorAll('[id^="table-"]').forEach(t => t.classList.add('hidden'));
  const el = document.getElementById('table-' + cat);
  if (el) el.classList.toggle('hidden');
}

// Handle approve/reject
document.querySelectorAll('.actionBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    const act = btn.dataset.act;
    if (!confirm(`Are you sure you want to ${act} this application?`)) return;

    fetch('discount_applications.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({action: act, id: id})
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) location.reload();
      else alert("Failed to update application.");
    })
    .catch(err => alert("Error: " + err));
  });
});
</script>

</body>
</html>
