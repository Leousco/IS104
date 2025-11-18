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

// ✅ Fetch all coin deals
$result = $conn->query("
  SELECT 
    DealID, DealName, CoinAmount, Price, DealType, ValidFrom, ValidTo, Status
  FROM coin_deals
  ORDER BY DealType ASC, DealID DESC
");

// ✅ Get statistics
$stats = $conn->query("
  SELECT 
    COUNT(*) as total_deals,
    SUM(CASE WHEN Status = 'ACTIVE' THEN 1 ELSE 0 END) as active_deals,
    SUM(CASE WHEN DealType = 'STANDARD' THEN 1 ELSE 0 END) as standard_deals,
    SUM(CASE WHEN DealType = 'LIMITED' THEN 1 ELSE 0 END) as limited_deals,
    SUM(CASE WHEN Status = 'EXPIRED' THEN 1 ELSE 0 END) as expired_deals
  FROM coin_deals
")->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coin Deal Management</title>
  
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
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
    body { background-color: var(--bg); color: var(--text-dark); overflow-x: hidden; }
    body::-webkit-scrollbar { display: none; width: 0; }
    
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
    .sidebar.open { left: 0; }

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

    .menu-icon {
      font-size: 24px;
      cursor: pointer;
      color: #064e3b;
    }

    main { padding: 20px; animation: slideIn 0.6s ease; }
    @keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .cards { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
      gap: 20px; 
      margin-top: 20px; 
    }
    .card { 
      background-color: var(--card); 
      padding: 20px; 
      border-radius: 12px; 
      box-shadow: 0 6px 15px rgba(0,0,0,0.08); 
      transition: transform 0.3s; 
      border-left: 5px solid var(--accent-green); 
    }
    .card:hover { transform: translateY(-5px); }
    .card h3 { font-size: 16px; color: #6b7280; font-weight: 500; }
    .card p { font-size: 32px; font-weight: 800; margin-top: 8px; color: #065f46; }

    table {
      border-collapse: collapse;
      width: 95%;
      margin: 20px auto;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
    th { background: #8de699; font-weight: bold; }

    .badge {
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }
    .badge-standard { background: #dbeafe; color: #1e40af; }
    .badge-limited { background: #fef3c7; color: #92400e; }
    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; }
    .badge-expired { background: #fee2e2; color: #991b1b; }

    .btn { 
      padding: 6px 12px; 
      border: none; 
      cursor: pointer; 
      border-radius: 5px; 
      font-size: 14px; 
      transition: all 0.2s;
    }
    .btn-add { 
      background: #90ee90; 
      margin-left: 35px; 
      margin-top: 30px; 
      padding: 10px 20px;
      font-weight: 600;
    }
    .btn-add:hover { 
      background-color: green; 
      transform: translateY(-2px); 
      box-shadow: 0 4px 8px rgba(0,0,0,0.15); 
      color: white; 
    }
    .btn-edit { background: orange; color: #fff; }
    .btn-edit:hover { 
      background-color: #c2410c; 
      box-shadow: 0 4px 8px rgba(0,0,0,0.15); 
    }
    .btn-delete { background: red; color: #fff; }
    .btn-delete:hover { 
      background-color: #870718; 
      box-shadow: 0 4px 8px rgba(0,0,0,0.15); 
    }

    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(3px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
    }
    .modal-content {
      width: 400px;
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    .modal-content h3 { margin-top: 0; text-align: center; margin-bottom: 20px; }
    .modal-content label { 
      display: block; 
      margin: 12px 0 4px; 
      font-weight: 600;
      color: #374151;
    }
    .modal-content input, .modal-content select {
      width: 100%; 
      padding: 8px; 
      border-radius: 4px; 
      border: 1px solid #d1d5db; 
      margin-bottom: 10px;
      font-size: 14px;
    }
    .modal-content input:focus, .modal-content select:focus {
      outline: none;
      border-color: #4ade80;
      box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
    }
    .modal-buttons {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      margin-top: 20px;
    }
    .btn-save {
      flex: 1;
      background: #4ade80;
      color: #064e3b;
      border: none;
      padding: 10px 0;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-save:hover {
      background: #22c55e;
    }
    .btn-cancel {
      flex: 1;
      background: #9ca3af;
      color: white;
      border: none;
      padding: 10px 0;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-cancel:hover {
      background: #6b7280;
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
    .overlay.show { display: block; opacity: 1; }

    .date-fields {
      display: none;
      padding: 15px;
      background: #f3f4f6;
      border-radius: 6px;
      margin-top: 10px;
    }
    .date-fields.show {
      display: block;
    }

    .help-text {
      font-size: 12px;
      color: #6b7280;
      margin-top: 4px;
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header>
    <div class="menu-icon" id="menuBtn"><i class="fas fa-bars"></i></div>
    <h1><i class="fas fa-coins text-green-700"></i> Coin Deal Management</h1>
</header>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <h2 class="text-green-400"><i class="fas fa-grip-vertical"></i> Menu</h2>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
    <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="coin_deal_management.php" class="bg-green-600 rounded"><i class="fas fa-coins"></i> Coin Deal Management</a>
    <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
    <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
    <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions </a>
    <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
</div>

<div class="overlay" id="overlay"></div>

<!-- MAIN CONTENT -->
<main>
  <h2 class="text-2xl font-bold mb-6 text-gray-800">Coin Deals Overview</h2>
  
  <!-- Statistics Cards -->
  <section class="cards">
    <div class="card">
      <h3>Total Deals</h3>
      <p><?= $stats['total_deals'] ?? 0 ?></p>
    </div>
    <div class="card">
      <h3>Active Deals</h3>
      <p><?= $stats['active_deals'] ?? 0 ?></p>
    </div>
    <div class="card">
      <h3>Standard Deals</h3>
      <p><?= $stats['standard_deals'] ?? 0 ?></p>
    </div>
    <div class="card">
      <h3>Limited Deals</h3>
      <p><?= $stats['limited_deals'] ?? 0 ?></p>
    </div>
    <div class="card">
      <h3>Expired Deals</h3>
      <p><?= $stats['expired_deals'] ?? 0 ?></p>
    </div>
  </section>

  <button class="btn btn-add" onclick="showForm()">
    <i class="fas fa-plus"></i> Add Coin Deal
  </button>

  <!-- Deals Table -->
  <table>
    <thead>
      <tr>
        <th class="bg-green-200">ID</th>
        <th class="bg-green-200">Deal Name</th>
        <th class="bg-green-200">Coins</th>
        <th class="bg-green-200">Price (₱)</th>
        <th class="bg-green-200">Type</th>
        <th class="bg-green-200">Valid Period</th>
        <th class="bg-green-200">Status</th>
        <th class="bg-green-200">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['DealID'] ?></td>
            <td><strong><?= htmlspecialchars($row['DealName']) ?></strong></td>
            <td><i class="fas fa-coins" style="color: #f59e0b;"></i> <?= number_format($row['CoinAmount']) ?></td>
            <td>₱<?= number_format($row['Price'], 2) ?></td>
            <td>
              <span class="badge badge-<?= strtolower($row['DealType']) ?>">
                <?= $row['DealType'] ?>
              </span>
            </td>
            <td>
              <?php if ($row['DealType'] === 'LIMITED'): ?>
                <?= date('M d, Y', strtotime($row['ValidFrom'])) ?><br>
                <small>to</small><br>
                <?= date('M d, Y', strtotime($row['ValidTo'])) ?>
              <?php else: ?>
                <span style="color: #6b7280;">Permanent</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge badge-<?= strtolower($row['Status']) ?>">
                <?= $row['Status'] ?>
              </span>
            </td>
            <td>
              <button class="btn btn-edit" onclick='editDeal(<?= json_encode($row) ?>)'>
                <i class="fas fa-edit"></i> Edit
              </button>
              <button class="btn btn-delete" onclick="deleteDeal(<?= $row['DealID'] ?>)">
                <i class="fas fa-trash"></i> Delete
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="8" style="text-align: center; color: #6b7280; padding: 40px;">
            <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3;"></i>
            <p style="margin-top: 10px;">No coin deals available. Add your first deal!</p>
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>

<!-- Modal Form -->
<div id="formOverlay" class="modal-overlay">
  <div class="modal-content">
    <h3 id="formTitle"><i class="fas fa-coins"></i> Add Coin Deal</h3>
    <form id="dealForm">
      <input type="hidden" id="editId" name="id">

      <label>Deal Name</label>
      <input type="text" id="dealName" name="dealName" placeholder="e.g., Starter Pack, Mega Bundle" required>
      

      <label>Coin Amount</label>
      <input type="number" id="coinAmount" name="coinAmount" min="1" placeholder="e.g., 100" required>
      

      <label>Price (₱)</label>
      <input type="number" id="price" name="price" step="0.01" min="0.01" placeholder="e.g., 50.00" required>
      

      <label>Deal Type</label>
      <select id="dealType" name="dealType" required onchange="toggleDateFields()">
        <option value="STANDARD">Standard (No Expiry)</option>
        <option value="LIMITED">Limited (Time-Limited)</option>
      </select>
      

      <div id="dateFields" class="date-fields">
        <label>Valid From *</label>
        <input type="datetime-local" id="validFrom" name="validFrom">
        
        <label>Valid To *</label>
        <input type="datetime-local" id="validTo" name="validTo">
      </div>

      <label>Status</label>
      <select id="status" name="status" required>
        <option value="ACTIVE">Active</option>
        <option value="INACTIVE">Inactive</option>
      </select>
      

      <div class="modal-buttons">
        <button type="submit" class="btn-save">
          <i></i> Save Deal
        </button>
        <button type="button" class="btn-cancel" onclick="hideForm()">
          <i></i> Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Sidebar and Overlay Toggle
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

// Modal Logic
const formOverlay = document.getElementById('formOverlay');
const formEl = document.getElementById('dealForm');
const formTitle = document.getElementById('formTitle');
const hiddenId = document.getElementById('editId');
const dateFields = document.getElementById('dateFields');

function toggleDateFields() {
  const dealType = document.getElementById('dealType').value;
  if (dealType === 'LIMITED') {
    dateFields.classList.add('show');
    document.getElementById('validFrom').required = true;
    document.getElementById('validTo').required = true;
  } else {
    dateFields.classList.remove('show');
    document.getElementById('validFrom').required = false;
    document.getElementById('validTo').required = false;
  }
}

function showForm(edit = false) {
  formOverlay.style.display = 'flex';
  formTitle.innerHTML = edit ? '<i class="fas fa-edit"></i> Edit Coin Deal' : '<i></i> Add Coin Deal';
  formEl.reset();
  hiddenId.value = '';
  toggleDateFields();
}

function hideForm() {
  formOverlay.style.display = 'none';
  formEl.reset();
  hiddenId.value = '';
}

function editDeal(deal) {
  showForm(true);
  hiddenId.value = deal.DealID;
  document.getElementById('dealName').value = deal.DealName;
  document.getElementById('coinAmount').value = deal.CoinAmount;
  document.getElementById('price').value = deal.Price;
  document.getElementById('dealType').value = deal.DealType;
  document.getElementById('status').value = deal.Status;
  
  if (deal.DealType === 'LIMITED') {
    if (deal.ValidFrom) {
      document.getElementById('validFrom').value = deal.ValidFrom.replace(' ', 'T').slice(0, 16);
    }
    if (deal.ValidTo) {
      document.getElementById('validTo').value = deal.ValidTo.replace(' ', 'T').slice(0, 16);
    }
  }
  
  toggleDateFields();
}

formEl.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const fd = new FormData(formEl);
  const payload = Object.fromEntries(fd.entries());
  const idVal = (hiddenId.value || '').trim();
  const isEdit = idVal.length > 0;
  
  if (isEdit) payload.id = idVal;

  // Validate limited deals have dates
  if (payload.dealType === 'LIMITED') {
    if (!payload.validFrom || !payload.validTo) {
      alert('Please set both start and end dates for limited deals.');
      return;
    }
    if (new Date(payload.validFrom) >= new Date(payload.validTo)) {
      alert('End date must be after start date.');
      return;
    }
  }

  const url = isEdit ? 'update_coin_deal.php' : 'add_coin_deal.php';
  
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    
    const result = await res.json();
    
    if (result.success) {
      alert(isEdit ? 'Coin deal updated successfully!' : 'Coin deal added successfully!');
      hideForm();
      location.reload();
    } else {
      alert(result.error || 'Operation failed. Please try again.');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  }
});

async function deleteDeal(id) {
  if (!confirm('Are you sure you want to delete this coin deal? This action cannot be undone.')) {
    return;
  }
  
  try {
    const res = await fetch('delete_coin_deal.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    
    const result = await res.json();
    
    if (result.success) {
      alert('Coin deal deleted successfully!');
      location.reload();
    } else {
      alert(result.error || 'Delete failed. Please try again.');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  }
}

// Initialize date fields visibility on page load
document.addEventListener('DOMContentLoaded', toggleDateFields);
</script>

</body>
</html>