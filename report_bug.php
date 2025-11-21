  <?php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  session_start();
  require_once "config.php";  // Adjust path if needed

$userFullName = '';
 
if (isset($_SESSION['UserID'])) {
    $userID = $_SESSION['UserID'];
    $stmt = $conn->prepare("SELECT FirstName, LastName FROM users WHERE UserID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName);
    if ($stmt->fetch()) {
        $userFullName = $firstName . ' ' . $lastName;
    }
    $stmt->close();
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $reported_by = trim($_POST['username'] ?? '');
    $date_time_occurred = $_POST['date_time_occurred'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($reported_by) || empty($date_time_occurred) || empty($description)) {
        $_SESSION['bug_report_error'] = "All fields are required.";
        header("Location: report_bug.php");
        exit;
    }

    if (!$conn) {
        $_SESSION['bug_report_error'] = "Database connection failed.";
        header("Location: report_bug.php");
        exit;
    }

    $sql = "INSERT INTO bug_reports (title, description, reported_by, date_time_occurred) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['bug_report_error'] = "Prepare failed: " . $conn->error;
        header("Location: report_bug.php");
        exit;
    }

    $stmt->bind_param("ssss", $title, $description, $reported_by, $date_time_occurred);

    if ($stmt->execute()) {
        $_SESSION['bug_report_success'] = "Success! Bug report submitted.";
    } else {
        $_SESSION['bug_report_error'] = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: report_bug.php");
    exit;
}
?>


  
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report a Bug</title>
  <style>
    
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; }

     body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background:  #d9eab1;
      color: #333;
      margin: 0;
      padding: 0;
      margin-top: 0;
      overflow-x: hidden;
      -ms-overflow-style: none;
    }

    body::-webkit-scrollbar {
    display: none; /* Hides the scrollbar */
    width: 0; /* Ensures no width space is reserved for the scrollbar */
    }

     .global-map-bg {
      position: fixed; top: 0; left: 0;
      width: 100%; height: 100%;
      background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/1280px-World_map_-_low_resolution.svg.png') center center no-repeat;
      background-size: cover; opacity: 0.1; z-index: -1;
    }

     .right-header { display: flex; align-items: center; gap: 15px; }
    .coin-balance {
      display: flex; align-items: center; background: #ffffff22;
      padding: 6px 12px; border-radius: 20px; font-weight: bold;
      cursor: pointer; text-decoration: none; color: white;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      transition: all 0.3s;
    }
    .coin-balance:hover { background: #ffffff33; transform: scale(1.05); }

    
    .profile {
      width: 35px; height: 35px; background-color: #2e7d32; color: white;
      font-size: 22px; display: flex; justify-content: center; align-items: center;
      border-radius: 50%; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      transition: all 0.3s ease; margin-right: 10px;
    }
    .profile:hover { background-color: #66bb6a; transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.3); }


    header h1 {
      margin-left: 10px;
      font-size: 20px;
    }
    
     /* SIDEBAR */
    .sidebar {
      height: 100%;
        width: 0px;
        position: fixed;
        top: 0;
        left: 0;
        background-color: #1b1b1b;
        overflow: hidden;
        padding-top: 60px;
        z-index: 1000;
        transition: width 0.3s ease, background 0.3s ease;
    }
    /* 1. Update padding to accommodate the icon spacing */
    .sidebar a {
        padding: 14px 28px;
        text-decoration: none;
        font-size: 18px;
        color: #ddd;
        display: block;
        transition: 0.3s;
        white-space: nowrap; 
        overflow: hidden;
    }

    /* 2. Style the Icon element */
    /* .sidebar a i {
        width: 25px; 
        margin-right: 10px; 
        text-align: center; 
    } */

    .sidebar a:hover {
        background: #2e7d32;
        color: #fff;
        padding-left: 35px; /* This is kept for the sliding hover effect */
    }

    .sidebar .closebtn {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 30px;
        cursor: pointer;
        color: white;
    }

    header {
      background: linear-gradient(90deg, #2e7d32, #66bb6a);
      padding: 15px 25px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: white;
      box-shadow: 0 3px 6px rgba(0,0,0,0.15);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .menu {
      font-size: 26px;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .menu:hover { transform: scale(1.1); }

    main {
      padding: 0px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 80vh;
      animation: slideIn 0.6s ease;
    }
    @keyframes slideIn {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .main-content {
      padding-top: 0px;
      padding: 20px;
      max-width: 800px;
      margin: auto;
      position: fixed;
    }

   
    .reportbug-box {
        /* Adjusted top margin to look better under the fixed header */
        margin-top: 20px; 
        
        background: #ffffff; /* Explicitly set a bright white background */
        padding: 30px; /* Increased padding for more internal space */
        
        /* Increased border-radius for softer, more modern corners */
        border-radius: 12px; 
        
        /* Enhanced, slightly lifted shadow for better depth */
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); 
        
        margin-bottom: 25px; /* Added some spacing below the box */
        
        /* Added a subtle, light border for definition */
        border: 1px solid #e0e0e0; 

    }
    .h2 {
        text-align: center;
        margin-bottom: 25px;
    }

    label {
        display: block;
        margin: 12px 0 8px;
        font-weight: 600;
        color: #333;
    }

    input, textarea, select {
        width: 100%;
        padding: 10px;
        margin-top: 0;
        border-radius: 8px;
        border: 1px solid #ddd;
        font-size: 15px;
        background: #f9f9f9;
        transition: all 0.2s ease-in-out;
        color: #333;
    }

    input:focus, textarea:focus, select:focus {
        border-color: #2e7d32;
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
        background-color: #fff;
        outline: none;
    }

    textarea {
        resize: vertical;
        min-height: 120px;
    }

    button {
        width: 100%;
        background-color: #0a0a0a;
        color: white;
        font-size: 1.1rem;
        font-weight: 700;
        border: none;
        padding: 12px 0;
        margin-top: 25px;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #222;
    }

    @media (max-width: 600px) {
        main { padding: 12px; }
    }

    .popup {
    position: fixed;
    top: 500px;
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
    .popup.success {
      background: #4caf50;
      color: white;
    }
    .popup.error {
      background: #f44336;
      color: white;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translate(-50%, -60%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
    }

    input, textarea, select {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      resize: none;
    }

    .sidebar-power {
    position: absolute;
    bottom: 20px;
    left: 0;
    width: 100%;
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

  #hover-zone {
    position: fixed;
    top: 0;
    left: 0;
    width: 10px;     /* hover are width */
    height: 100vh;
    z-index: 999;    
}

  </style>

  
</head>
<body>

<div id="hover-zone"></div>

        <header>
    <div class="menu" onclick="openNav()"><i class="fas fa-grip-lines-vertical"></i></div>
    <div class="right-header">
      <a href="redeem_voucher.php" class="coin-balance">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" fill="#F4C542"/>
          <circle cx="12" cy="12" r="8.2" fill="#F9D66B"/>
          <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
        </svg>
        <span id="header-balance">₱0.00</span>
      </a>
      <div class="profile" onclick="window.location.href='user_prof.php'">👤</div>
    </div>
  </header>

  <?php if (!empty($_SESSION['bug_report_success'])): ?>
    <div id="success-popup" class="popup success">
      <?= htmlspecialchars($_SESSION['bug_report_success']) ?>
    </div>
    <script>
      setTimeout(() => {
        document.getElementById('success-popup')?.remove();
      }, 3000);
    </script>
    <?php unset($_SESSION['bug_report_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['bug_report_error'])): ?>
    <div id="error-popup" class="popup error">
      <?= htmlspecialchars($_SESSION['bug_report_error']) ?>
    </div>
    <script>
      setTimeout(() => {
        document.getElementById('error-popup')?.remove();
      }, 4000);
    </script>
    <?php unset($_SESSION['bug_report_error']); ?>
  <?php endif; ?>


      <div class="global-map-bg"></div>

      <div id="sidebar" class="sidebar" aria-hidden="true">
      <span class="closebtn" onclick="closeNav()"><i class="fas fa-caret-right" style="font-size: 20px;"></i></span>
      
      <a href="passenger_dashboard.php">
        <i class="fas fa-home"></i> Homepage
      </a>
      
      <a href="vehicle.php">
        <i class="fas fa-bus"></i> Vehicles
      </a>
      
      <a href="ticketing/ticketing.php">
        <i class="fas fa-ticket-alt"></i> Buy Ticket
      </a>
      
      <a href="redeem_voucher.php">
        <i class="fas fa-gift"></i> Redeem Voucher
      </a>
      
      <a href="feedback.php">
        <i class="fas fa-comment-dots"></i> Feedback
      </a>
      
      <a href="about.php">
        <i class="fas fa-info-circle"></i> About Us
      </a>
      
      <a href="discountPage/discount_page.php">
        <i class="fas fa-percent"></i> Apply for a Discount
      </a>

      <div class="sidebar-power">
          <a href="passenger_dashboard.php">
            <i class="fas fa-angle-left"></i> Back
          </a>
      </div>
</div>

 
  <main>
  <div class="main-content">
   
       <div class="reportbug-box">
        <div class="h2"> <h2>🐞 Report a Bug</h2> </div>
      <form method="POST" action="report_bug.php">
        <label>
          Title:
          <input type="text" name="title" required placeholder="e.g. Redemption code issue, Booking bug">
        </label>

        <label>
          Your Name:
          <input type="text" name="username" required readonly
          value="<?= htmlspecialchars($userFullName) ?>">

        </label>
        
        <!-- ✅ Date + Time input -->
        <label>
          Date & Time Occurred:
          <input type="datetime-local" name="date_time_occurred" required>
        </label>

        <label>
          Bug Description:
          <textarea name="description" required placeholder="Describe the bug in detail..."></textarea>
        </label>

        <button type="submit">Submit Report</button>
      </form>
    </div>
    </div>
  </main>

 <script>

// hover sidebar
const sidebar = document.getElementById("sidebar");
const hoverZone = document.getElementById("hover-zone");

hoverZone.addEventListener("mouseenter", () => {
    sidebar.style.width = "280px";
});

sidebar.addEventListener("mouseleave", () => {
    sidebar.style.width = "0";
});


  document.getElementById('power-toggle').addEventListener('click', function () {
    const menu = document.getElementById('power-menu');
    menu.classList.toggle('hidden');
  });

   async function renderUserBalance() {
      const hb = document.getElementById('header-balance');
      hb.textContent = '...';
      try {
        const res = await fetch('get_passenger_data.php');
        const data = await res.json();
        if (data.success) {
          const balance = parseFloat(data.user.balance || 0).toFixed(2);
          hb.textContent = '₱' + balance;
        } else hb.textContent = '₱0.00';
      } catch {
        hb.textContent = '₱0.00';
      }
    }
    renderUserBalance();

    function openNav(){
      const sb = document.getElementById('sidebar');
      sb.style.width = '280px';
      sb.setAttribute('aria-hidden','false');
    }
    function closeNav(){
      const sb = document.getElementById('sidebar');
      sb.style.width = '0';
      sb.setAttribute('aria-hidden','true');
    }

    
  window.addEventListener('DOMContentLoaded', () => {
    const dtInput = document.querySelector('input[name="date_time_occurred"]');
    if (dtInput) {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const localDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
      dtInput.value = localDateTime;
    }
  });


    </script>
  
</body>
</html>
