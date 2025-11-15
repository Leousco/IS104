<?php
require_once '../config.php';
include("../auth.php");

$loginPage = "/SADPROJ/login.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: $loginPage");
    exit();
}

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "PASSENGER") {
    header("Location: $loginPage?error=unauthorized");
    exit();
}

// CRITICAL FIX: Define $user_id from session
$user_id = $_SESSION['UserID'];

// Fetch discount status for user from discount_applications table
$discountQuery = $conn->prepare("
    SELECT Status 
    FROM discount_applications 
    WHERE UserID = ? 
    ORDER BY ApplicationID DESC 
    LIMIT 1
");
$discountQuery->bind_param("i", $user_id);
$discountQuery->execute();
$discountResult = $discountQuery->get_result();
$discountRow = $discountResult->fetch_assoc();
$discountStatus = $discountRow['Status'] ?? 'none';
$discountQuery->close();

// Determine discount eligibility
$isDiscountApproved = ($discountStatus === 'Approved');

// Fetch user details for autofill
$userDetailsQuery = $conn->prepare("
    SELECT FirstName, LastName, Email 
    FROM users 
    WHERE UserID = ?
");
$userDetailsQuery->bind_param("i", $user_id);
$userDetailsQuery->execute();
$userDetailsResult = $userDetailsQuery->get_result();
$userDetails = $userDetailsResult->fetch_assoc();
$userDetailsQuery->close();

// Set variables for use in HTML
$userFirstName = htmlspecialchars($userDetails['FirstName'] ?? '');
$userLastName  = htmlspecialchars($userDetails['LastName'] ?? '');
$userEmail     = htmlspecialchars($userDetails['Email'] ?? '');

// Fetch schedules with correct fare matching
$schedules = $conn->query("
    SELECT 
        s.ScheduleID, 
        s.DepartureTime, 
        s.ArrivalTime, 
        s.Date,
        r.RouteID, 
        r.StartLocation, 
        r.EndLocation, 
        r.TypeID, 
        f.Amount AS Fare
    FROM schedule s
    JOIN route r ON s.RouteID = r.RouteID
    JOIN fare f ON r.RouteID = f.RouteID AND f.TypeID = r.TypeID
    ORDER BY s.Date, s.DepartureTime
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Book Ticket - Ticketing System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f7fa;
        color: #333;
    }

    body::-webkit-scrollbar {
      display: none; 
      width: 0; 
    }

    header {
        background: linear-gradient(90deg, #2e7d32, #66bb6a);
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
        box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .right-header {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .coin-balance {
        display: flex;
        align-items: center;
        background: #ffffff22;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: bold;
        transition: all 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        cursor: pointer;
        text-decoration: none;
        color: white;
    }
    .coin-balance:hover {
        background: #ffffff33;
        transform: scale(1.05);
    }
    .coin-balance img {
        width: 22px;
        height: 22px;
        margin-right: 8px;
    }

    .menu { font-size: 26px; cursor: pointer; transition: transform 0.2s; }
    .menu:hover { transform: scale(1.1); }

    .profile {
        width: 35px;
        height: 35px;
        background-color: #2e7d32;
        color: white;
        font-size: 22px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        margin-right: 10px;
    }
    .profile:hover {
        background-color: #66bb6a;
        transform: scale(1.1);
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    }

    .sidebar {
      height: 100%;
      width: 0;
      position: fixed;
      top: 0;
      left: 0;
      background-color: #1b1b1b;
      overflow-x: hidden;
      transition: 0.4s;
      padding-top: 60px;
      z-index: 1000;
    }

    .sidebar a {
      padding: 14px 28px;
      text-decoration: none;
      font-size: 18px;
      color: #ddd;
      display: block;
      transition: 0.3s;
    }

    .sidebar a:hover {
      background: #2e7d32;
      color: #fff;
      padding-left: 35px;
    }

    .sidebar .closebtn {
      position: absolute;
      top: 10px;
      right: 20px;
      font-size: 30px;
      color: white;
      cursor: pointer;
    }

    :root {
      --accent: #2e7d32;
      --muted: #666;
    }

    .main-container {
      max-width: 1400px;
      margin: 30px auto;
      padding: 0 24px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      align-items: start;
    }

    @media (max-width: 968px) {
      .main-container {
        grid-template-columns: 1fr;
        margin: 20px auto;
      }
    }

    .card {
      background: #ffffff;
      border-radius: 16px;
      padding: 28px;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); 
      border: 1px solid #e9e9e9; 
      transition: transform 0.3s ease;
    }

    h3 {
      margin-bottom: 20px;
      font-size: 22px;
      font-weight: 700;
      color: #333;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-size: 14px;
      margin-bottom: 8px;
      color: var(--muted, #666);
      font-weight: 600;
    }

    input,
    select {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #dcdcdc;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      background-color: #fcfcfc;
      transition: all 0.2s ease;
    }

    input:focus,
    select:focus {
      border-color: var(--accent, #2e7d32);
      outline: none;
      box-shadow: 0 0 0 3px #2e7d3233;
      background-color: #fff;
    }

    input[readonly] {
      background-color: #f7f7f7;
      color: #4a4a4a;
      border-color: #e0e0e0 !important; 
      box-shadow: none !important;
      cursor: not-allowed;
    }

    .btn {
      background: var(--accent, #2e7d32);
      color: #fff;
      border: none;
      padding: 14px 24px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
      font-weight: 600;
      font-size: 16px;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
      width: 100%;
      margin-top: 20px;
    }

    .btn:hover {
      background: #388e3c;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    .btn:disabled {
      background: #999;
      cursor: not-allowed;
      transform: none;
      opacity: 0.6;
    }

    .btn.secondary {
      background: #fff;
      color: var(--accent, #2e7d32);
      border: 1px solid var(--accent, #2e7d32);
      box-shadow: none;
      margin-top: 10px;
    }

    .btn.secondary:hover {
      background: #f0fdf0;
      transform: translateY(-1px);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .balance-display {
      background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%);
      color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
    }

    .balance-display .amount {
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 5px;
    }

    .balance-display .label {
      font-size: 14px;
      opacity: 0.9;
    }

    .fare-box {
      margin-top: 24px;
      padding: 18px 20px;
      background: #e6f5e6;
      border: 2px solid var(--accent, #2e7d32);
      border-radius: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .fare-box span:first-child {
      color: #1b5e20;
      font-weight: 600;
      font-size: 18px;
    }

    #fareDisplay {
      font-weight: 800;
      font-size: 26px;
      color: #1b5e20;
    }

    .schedule-options {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 20px;
    }

    .schedule-box {
      display: flex;
      align-items: center;
      border: 2px solid #ddd;
      border-radius: 10px;
      padding: 14px 16px;
      cursor: pointer;
      background: #f9f9f9;
      transition: all 0.2s;
    }

    .schedule-box:hover {
      background: #e6f5e6;
      border-color: #2e7d32;
    }

    .schedule-box input[type="radio"] {
      margin-right: 14px;
      cursor: pointer;
      width: 20px;
      height: 20px;
      flex-shrink: 0;
    }

    .schedule-box input[type="radio"]:checked {
      accent-color: #2e7d32;
    }

    .schedule-box.selected {
      background: #e6f5e6;
      border-color: #2e7d32;
      border-width: 2px;
    }

    .schedule-info {
      flex: 1;
    }

    .schedule-info div {
      font-size: 14px;
      line-height: 1.6;
      color: #333;
    }

    .schedule-info div:first-child {
      font-weight: 700;
      color: #2e7d32;
      font-size: 15px;
      margin-bottom: 4px;
    }

    .muted {
      color: #666;
      font-size: 14px;
    }

    .status-message {
      padding: 14px 18px;
      border-radius: 10px;
      margin-bottom: 24px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .status-approved {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
      border: 1px solid #ffeaa7;
    }

    .status-rejected {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .ticket-qr {
      max-width: 250px;
      margin: 15px auto;
      display: block;
      border: 2px solid #2e7d32;
      border-radius: 8px;
      padding: 10px;
      background: white;
    }

    .notification {
      position: fixed;
      top: 80px;
      left: 50%;
      transform: translateX(-50%);
      background-color: #28a745;
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      font-weight: bold;
      font-size: 16px;
      z-index: 1000;
      animation: fadeInOut 4s ease forwards;
    }

    .notification.success {
      background-color: #28a745;
      color: white;
    }

    .notification.error {
      background-color: #dc3545;
      color: white;
    }

    @keyframes fadeInOut {
      0% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
      10% { opacity: 1; transform: translateX(-50%) translateY(0); }
      90% { opacity: 1; }
      100% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
    }

    .sidebar-power {
      position: absolute;
      bottom: 20px;
      left: 0;
      width: 100%;
      padding: 0 20px;
    }

    #power-toggle {
      background: none;
      border: none;
      color: #ddd;
      font-size: 20px;
      cursor: pointer;
      width: 100%;
      text-align: left;
    }

    #power-toggle:hover {
      color: #2e7d32;
    }

    .power-menu {
      margin-top: 10px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .power-menu a {
      font-size: 18px;
      color: #ddd;
      text-decoration: none;
      display: flex;
      align-items: center;
      transition: 0.3s;
    }

    .power-menu a i {
      width: 25px;
      margin-right: 10px;
      text-align: center;
    }

    .power-menu a:hover {
      background: #2e7d32;
      color: #fff;
      padding-left: 7px;
      border-radius: 6px;
    }

    .hidden {
      display: none;
    }

    .ticket-preview-box {
      background: #f0fdf0;
      border: 2px solid #2e7d32;
      border-radius: 12px;
      padding: 24px;
      min-height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .ticket-preview-box.empty {
      background: #f9f9f9;
      border: 2px dashed #ddd;
      color: #999;
    }

    .ticket-detail {
      margin-bottom: 12px;
      font-size: 15px;
      line-height: 1.8;
    }

    .ticket-detail strong {
      color: #2e7d32;
      font-weight: 600;
    }

    .right-column {
      position: sticky;
      top: 90px;
    }

    .right-column h3{
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div id="sidebar" class="sidebar" aria-hidden="true">
  <span class="closebtn" onclick="closeNav()">&times;</span>
  <a href="../passenger_dashboard.php"><i class="fas fa-home"></i> Homepage</a>
  <a href="../vehicle.php"><i class="fas fa-bus"></i> Vehicles</a>
  <a href="ticketing.php"><i class="fas fa-ticket-alt"></i> Buy Ticket</a>
  <a href="../buyCoin/buy_coins.php"><i class="fas fa-coins"></i> Buy Coins</a>
  <a href="../feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a>
  <a href="../about.php"><i class="fas fa-info-circle"></i> About Us</a>
  <a href="../discountPage/discount_page.php"><i class="fas fa-percent"></i> Apply for a Discount</a>
  <div class="sidebar-power">
    <button id="power-toggle">
      <i class="fas fa-sign-out-alt"></i>
    </button>
    <div id="power-menu" class="power-menu hidden">
      <a href="../login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <a href="ticketing.php"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
  </div>
</div>

<header>
  <div class="menu" onclick="openNav()">☰</div>
  <div class="right-header">
    <a href="../redeem_voucher.php" class="coin-balance">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="12" cy="12" r="10" fill="#F4C542" />
        <circle cx="12" cy="12" r="8.2" fill="#F9D66B" />
        <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
      </svg>
      <span id="header-balance">₱0</span>
    </a>
    <div class="profile" onclick="window.location.href='../user_prof.php'">👤</div>
  </div>
</header>

<div id="ticket-success" class="notification success" style="display:none;">
  Ticket booked successfully!
</div>

<div id="ticket-error" class="notification error" style="display:none;">
   Ticket booking failed. Please try again.
</div>

<div class="main-container">
  <!-- LEFT COLUMN: Booking Form -->
  <div>
    <div class="balance-display">
      <div class="label">Wallet Balance</div>
      <div class="amount" id="user-balance">₱0.00</div>
    </div>

    <div class="card ticket-card">
      <h3>Book a Ticket</h3>

      <?php if (isset($_SESSION['UserID'])): ?>
        <?php if ($isDiscountApproved): ?>
          <div class="status-message status-approved">
            <i class="fas fa-check-circle"></i> Your discount application was approved! (20% off applied)
          </div>
        <?php elseif ($discountStatus === 'Pending'): ?>
          <div class="status-message status-pending">
            <i class="fas fa-clock"></i> Your discount application is still under review.
          </div>
        <?php elseif ($discountStatus === 'Rejected'): ?>
          <div class="status-message status-rejected">
            <i class="fas fa-times-circle"></i> Your discount application was rejected.
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <form id="booking-form">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" id="first-name" required value="<?= $userFirstName ?>" readonly />
        </div>
        
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" id="last-name" required value="<?= $userLastName ?>" readonly />
        </div>
        
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="email" required value="<?= $userEmail ?>" readonly />
        </div>

        <div class="form-group">
          <label for="vehicleType">Vehicle Type</label>
          <select id="vehicleType" name="vehicleType" required>
            <option value="">Select vehicle type</option>
            <option value="1">Bus</option>
            <option value="2">E-Jeep</option>
          </select>
        </div>

        <div class="form-group">
          <label>Select Schedule</label>
          <div class="schedule-options" id="schedule-container">
            <?php if ($schedules->num_rows > 0): ?>
              <?php while($sch = $schedules->fetch_assoc()): ?>
                <label class="schedule-box" 
                       data-type="<?= $sch['TypeID'] ?>" 
                       data-fare="<?= $sch['Fare'] ?>"
                       data-destination="<?= htmlspecialchars($sch['StartLocation'] . ' to ' . $sch['EndLocation']) ?>">
                  <input type="radio" name="schedule_id" value="<?= $sch['ScheduleID'] ?>" data-fare="<?= $sch['Fare'] ?>" required>
                  <div class="schedule-info">
                    <div><?= htmlspecialchars($sch['StartLocation'] . ' → ' . $sch['EndLocation']) ?></div>
                    <div><?= date('l, M d, Y', strtotime($sch['Date'])) ?></div>
                    <div><?= htmlspecialchars($sch['DepartureTime'] . ' → ' . $sch['ArrivalTime']) ?></div>
                    <div><?= $sch['TypeID'] == 1 ? 'Bus' : 'E-Jeep' ?> | ₱<?= number_format($sch['Fare'], 2) ?></div>
                  </div>
                </label>
              <?php endwhile; ?>
            <?php else: ?>
              <p class="muted">No schedules available at this time.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="fare-box">
          <span>Total Fare:</span>
          <span id="fareDisplay">₱0.00</span>
        </div>

        <button type="submit" class="btn">Book Ticket</button>
      </form>
    </div>
  </div>

  <!-- RIGHT COLUMN: Ticket Preview -->
  <div class="right-column">
    <div class="card">
      <h3>Your Ticket</h3>
      <div id="ticket-preview" class="ticket-preview-box empty">
        <p class="muted">No ticket booked yet.</p>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('power-toggle').addEventListener('click', function () {
    const menu = document.getElementById('power-menu');
    menu.classList.toggle('hidden');
  });

  function showNotification(id, message) {
    const box = document.getElementById(id);
    box.textContent = message;
    box.style.display = 'none';
    box.style.animation = 'none';
    void box.offsetWidth;
    box.style.display = 'block';
    box.style.animation = 'fadeInOut 4s ease forwards';
  }

  // Menu functions
  function openNav() { 
    document.getElementById("sidebar").style.width = "280px"; 
  }

  function closeNav() { 
    document.getElementById("sidebar").style.width = "0"; 
  }

  // Global variables
  const vehicleSelect = document.getElementById('vehicleType');
  const scheduleBoxes = document.querySelectorAll('.schedule-box');
  const fareDisplay = document.getElementById('fareDisplay');
  const isDiscountApproved = <?= $isDiscountApproved ? 'true' : 'false' ?>;

  // Filter schedules by vehicle type
  function filterSchedules() {
    const selectedType = vehicleSelect.value;

    scheduleBoxes.forEach(box => {
      const radio = box.querySelector('input[type="radio"]');
      if (!selectedType) {
        box.style.display = 'flex';
      } else if (String(box.dataset.type) === selectedType) {
        box.style.display = 'flex';
      } else {
        box.style.display = 'none';
        radio.checked = false;
      }
    });

    updateFareDisplay();
  }

  // Update fare display
  function updateFareDisplay() {
    const selectedRadio = document.querySelector('input[name="schedule_id"]:checked');
    
    if (!selectedRadio) {
      fareDisplay.textContent = '₱0.00';
      return;
    }

    let baseFare = parseFloat(selectedRadio.dataset.fare) || 0;
    let finalFare = baseFare;

    if (isDiscountApproved) {
      finalFare = baseFare * 0.8;
      fareDisplay.innerHTML = `<span style="text-decoration:line-through;opacity:0.6;">₱${baseFare.toFixed(2)}</span> ₱${finalFare.toFixed(2)}`;
    } else {
      fareDisplay.textContent = `₱${finalFare.toFixed(2)}`;
    }
  }

  // Add event listeners
  vehicleSelect.addEventListener('change', filterSchedules);

  scheduleBoxes.forEach(box => {
    box.addEventListener('click', function() {
      scheduleBoxes.forEach(b => b.classList.remove('selected'));
      this.classList.add('selected');
      updateFareDisplay();
    });
    
    const radio = box.querySelector('input[type="radio"]');
    radio.addEventListener('change', updateFareDisplay);
  });

  // Fetch and render user balance
  async function renderUserBalance() {
    document.getElementById('user-balance').textContent = 'Loading...';
    const hb = document.getElementById('header-balance');
    if (hb) hb.textContent = '...';

    try {
      const res = await fetch('../get_passenger_data.php');
      const data = await res.json();
      
      if (data.success) {
        const balance = data.user.balance || 0; 
        const formattedBalance = '₱' + parseFloat(balance).toFixed(2);

        document.getElementById('user-balance').textContent = formattedBalance;
        if (hb) hb.textContent = formattedBalance;
      } else {
        document.getElementById('user-balance').textContent = 'Error loading balance';
        if (hb) hb.textContent = 'Error';
        console.error('Failed to load user data:', data.error);
      }
    } catch (error) {
      document.getElementById('user-balance').textContent = 'Network Error';
      if (hb) hb.textContent = 'Error';
      console.error('Network error during fetch:', error);
    }
  }

  // Handle ticket booking
  document.getElementById('booking-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const selectedRadio = document.querySelector('input[name="schedule_id"]:checked');
    if (!selectedRadio) {
      alert('Please select a schedule');
      return;
    }

    const selectedBox = selectedRadio.closest('.schedule-box');
    const destination = selectedBox.dataset.destination;
    const scheduleId = selectedRadio.value;
    const baseFare = parseFloat(selectedRadio.dataset.fare) || 0;
    
    // Apply discount if approved
    let finalFare = baseFare;
    if (isDiscountApproved) {
      finalFare = baseFare * 0.8;
    }
    
    const firstName = document.getElementById('first-name').value;
    const lastName = document.getElementById('last-name').value;
    const email = document.getElementById('email').value;

    const formData = new FormData();
    formData.append('schedule_id', scheduleId);
    formData.append('fare', finalFare.toFixed(2));
    formData.append('destination', destination);
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('email', email);

    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;

    try {
      const res = await fetch('process_ticket.php', { 
        method: 'POST', 
        body: formData 
      });

      const text = await res.text();
      console.log('Server response:', text);
      
      let data;
      try {
        data = JSON.parse(text);
      } catch (jsonError) {
        console.error('Invalid JSON returned by server:', text);
        showNotification('ticket-error', 'Server returned invalid response. Check console.');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        return;
      }

      if (!data.success) {
        showNotification('ticket-error', data.error || 'Booking failed.');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        return;
      }

      // Update balance
      if (data.balance !== undefined && !isNaN(data.balance)) {
        const newBalance = parseFloat(data.balance).toFixed(2);
        document.getElementById('user-balance').textContent = '₱' + newBalance;
        document.getElementById('header-balance').textContent = '₱' + newBalance;
      }
      
      // Update ticket preview with QR code
      const ticketPreview = document.getElementById('ticket-preview');
      ticketPreview.className = 'ticket-preview-box';
      ticketPreview.innerHTML = `
        <div style="width: 100%; text-align: left;">
          <div class="ticket-detail"><strong>Ticket ID:</strong> #${data.ticket_id}</div>
          <div class="ticket-detail"><strong>Passenger:</strong> ${data.name}</div>
          <div class="ticket-detail"><strong>Email:</strong> ${email}</div>
          <div class="ticket-detail"><strong>Destination:</strong> ${data.destination}</div>
          <div class="ticket-detail"><strong>Fare:</strong> ₱${parseFloat(data.fare).toFixed(2)}</div>
          <div class="ticket-detail"><strong>Status:</strong> <span style="color:#2e7d32;">✓ Confirmed</span></div>
          ${data.qr ? `
            <div style="text-align:center; margin-top: 20px;">
              <p style="margin-bottom:10px;font-weight:600;color:#2e7d32;">Scan this QR Code:</p>
              <img src="${data.qr}" alt="Ticket QR Code" class="ticket-qr">
              <button class="btn secondary" onclick="downloadTicket('${data.qr}', '${data.name}', '${data.destination}')">
                <i class="fas fa-download"></i> Download Ticket
              </button>
            </div>
          ` : '<p style="color:orange; text-align:center;">QR Code generation in progress...</p>'}
        </div>
      `;
      
      // Reset form
      this.reset();
      vehicleSelect.value = '';
      scheduleBoxes.forEach(b => b.classList.remove('selected'));
      filterSchedules(); 
      fareDisplay.textContent = '₱0.00';
      
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
      
      showNotification('ticket-success', 'Ticket booked successfully!');

      // Scroll to ticket preview
      ticketPreview.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      
    } catch (error) {
      console.error('Network or fetch error:', error);
      showNotification('ticket-error', 'Could not connect to the server. Check console for details.');
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  });

  // Download ticket function
  function downloadTicket(qrPath, name, destination) {
    const link = document.createElement('a');
    link.href = qrPath;
    link.download = `Ticket_${name}_${destination}.png`;
    link.click();
  }

  // Initialize
  filterSchedules();
  renderUserBalance();
</script>
</body>
</html>