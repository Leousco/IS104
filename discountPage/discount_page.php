<?php
include("../config.php");
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

$user_id = $_SESSION['UserID'];

if (!$conn) {
    die("Database connection failed. Please check your config.php.");
}

// Check for any active application (Pending or Approved)
$activeCategory = null;
$activeStatus = null;
$sql = "SELECT Category, Status FROM discount_applications 
        WHERE UserID = ? AND Status IN ('Pending','Approved') LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();
if ($app) {
    $activeCategory = $app['Category'];
    $activeStatus = $app['Status'];
}
$stmt->close();

$conn->close();

// Helper function to check if a button should be disabled
function isDisabled($category) {
    global $activeCategory;
    return ($activeCategory && $activeCategory !== $category);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Discount Page</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            overflow: hidden;
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
            overflow: hidden;
            transition: 0.3s;
            padding-top: 60px;
            z-index: 1000;
            transition: width 0.3s ease, background 0.3s ease;
        }
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
        .sidebar a i {
            width: 10px;
            margin-right: 10px;
            text-align: center;
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
            cursor: pointer;
            color: white;
        }

        .disabled-button {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
            background: #d3d3d3 !important;
        }
        .disabled-button:hover {
            background: #d3d3d3 !important;
            transform: none !important;
        }

        .status-note {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 8px;
            color: #856404;
            text-align: center;
        }

        .status-note.approved {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .status-note.pending {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }

        .header-left {
          display: flex;
          align-items: center;
          gap: 20px;
        }

        .page-title {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
          font-size: 1.3rem;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 2px;
          color: #ffffff;
          position: relative;
          display: flex;
          align-items: center;
          gap: 8px;
          opacity: 0;
          transform: translateX(-20px);
          animation: slideInTransit 1s ease forwards 0.3s;
        }

        /* Entry animation: slide in like a train arriving */
        @keyframes slideInTransit {
          from { opacity: 0; transform: translateX(-40px); }
          to   { opacity: 1; transform: translateX(0); }
        }

        /* Icon bounce animation (like wheels turning) */
        @keyframes bounceWheel {
          0%, 100% { transform: translateY(0); }
          50%      { transform: translateY(-4px); }
        }

        /* Glow underline accent like a transit line */
        .page-title::after {
          content: "";
          position: absolute;
          bottom: -6px;
          left: 0;
          width: 100%;
          height: 3px;
          background: linear-gradient(90deg, #2e7d32, #66bb6a, #2196f3);
          transform: scaleX(0);
          transform-origin: left;
          transition: transform 0.5s ease;
        }

        .page-title:hover::after {
          transform: scaleX(1);
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


/* LOGOUT CSS */
/* Logout Popup Styles */
.logout-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.logout-modal {
  background: white;
  padding: 40px 30px;
  border-radius: 16px;
  text-align: center;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 12px 36px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(50px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.logout-icon i {
  font-size: 32px;
  color: white;
}

.logout-modal h3 {
  font-size: 24px;
  color: #333;
  margin-bottom: 10px;
  font-weight: 700;
}

.logout-modal p {
  font-size: 16px;
  color: #666;
  margin-bottom: 30px;
}

.logout-buttons {
  display: flex;
  gap: 15px;
  justify-content: center;
}

/* Buttons */
.btn-cancel, .btn-confirm {
  padding: 12px 30px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s ease;
}

/* CANCEL BUTTON - RED */
.btn-cancel {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  color: white;
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.btn-cancel:hover {
  background: linear-gradient(135deg, #c0392b, #a93226);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(231, 76, 60, 0.4);
}

/* CONFIRM BUTTON - GREEN */
.btn-confirm {
  background: linear-gradient(135deg, #28a745, #218838);
  color: white;
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-confirm:hover {
  background: linear-gradient(135deg, #218838, #1e7e34);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
}

  </style>
</head>
<body>

<!-- CONFIRMATION POPUP -->
<div id="logout-popup" class="logout-overlay" style="display: none;">
  <div class="logout-modal">
    <div class="logout-icon"> 
    </div>
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="logout-buttons">
      <button class="btn-cancel" onclick="closeLogoutPopup()">Cancel</button>
      <button class="btn-confirm" onclick="confirmLogout()">Yes, Logout</button>
    </div>
  </div>
</div>

<div id="hover-zone"></div>

  <!-- HEADER -->
  <header>
  <div class="header-left">
    <div class="menu" onclick="openNav()"><i class="fas fa-grip-lines-vertical"></i></div>
    <span class="page-title">Application for discount</span> <!-- or App Name -->
   </div>
    <div class="right-header">
      <a href="../buyCoin/buy_coins.php" class="coin-balance">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
          xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" fill="#F4C542" />
          <circle cx="12" cy="12" r="8.2" fill="#F9D66B" />
          <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z"
            fill="#D39C12" opacity="0.9" />
        </svg>
        <span id="header-balance">0</span>
      </a>
      <div class="profile" onclick="location.href='../user_prof.php'">👤</div>
    </div>
  </header>

  <!-- SIDEBAR -->
  <div id="sidebar" class="sidebar" aria-hidden="true">
    <span class="closebtn" onclick="closeNav()"><i class="fas fa-caret-right" style="font-size: 20px;"></i></span>
    
    <a href="../passenger_dashboard.php">
      <i class="fas fa-home"></i> Homepage
    </a>
    
    <a href="../vehicle.php">
      <i class="fas fa-bus"></i> Vehicles
    </a>
    
    <a href="../ticketing/ticketing.php">
      <i class="fas fa-ticket-alt"></i> Buy Ticket
    </a>
    
    <a href="../buyCoin/buy_coins.php">
        <i class="fas fa-coins"></i> Buy Coins
      </a>
    
    <a href="../feedback.php">
      <i class="fas fa-comment-dots"></i> Feedback
    </a>
    
    <a href="../about.php">
      <i class="fas fa-info-circle"></i> About Us
    </a>
    
    <a href="discount_page.php">
      <i class="fas fa-percent"></i> Apply for a Discount
    </a>

    <div class="sidebar-power">
          <a href="javascript:void(0);" onclick="showLogoutPopup()">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
      </div>

  </div>

<main id="main-content" class="flex flex-col items-center justify-center p-6 bg-gray-50 min-h-screen">
  <!-- MAIN CONTENT -->
  <div class="flex flex-col items-center bg-gray-50 p-6">
    <h1 class="text-2xl font-bold mb-2">APPLY FOR A DISCOUNT</h1>
    <p class="text-gray-600 mb-4 text-center">
      Choose the discount category and submit your required ID.
    </p>

    <?php if ($activeCategory): ?>
      <div class="status-note <?php echo $activeStatus === 'Approved' ? 'approved' : 'pending'; ?> max-w-2xl w-full mb-8">
        <strong>
          <?php if ($activeStatus === 'Approved'): ?>
            <i class="fas fa-check-circle"></i> You have an approved discount for "<?php echo htmlspecialchars($activeCategory); ?>". 
            Other categories are disabled.
          <?php else: ?>
            <i class="fas fa-clock"></i> You have a pending application for "<?php echo htmlspecialchars($activeCategory); ?>". 
            You cannot apply for another discount until this application is processed.
          <?php endif; ?>
        </strong>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">

      <!-- Student -->
      <button class="bg-green-200 hover:bg-green-300 transition rounded-xl p-8 flex flex-col items-center justify-center shadow-md <?php echo isDisabled('Student') ? 'disabled-button' : ''; ?>"
        onclick="<?php echo isDisabled('Student') ? '' : "location.href='student.php'"; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"
          class="w-10 h-10 mb-3">
          <path d="M12 2L1 7l11 5 9-4.09V17h2V7L12 2z" />
        </svg>
        <span class="font-bold text-lg">STUDENT</span>
        <?php if ($activeCategory === 'Student' && $activeStatus === 'Approved'): ?>
          <span class="text-green-600 font-semibold">(Approved)</span>
        <?php elseif ($activeCategory === 'Student' && $activeStatus === 'Pending'): ?>
          <span class="text-yellow-600 font-semibold">(Pending)</span>
        <?php endif; ?>
      </button>

      <!-- PWD -->
      <button class="bg-green-200 hover:bg-green-300 transition rounded-xl p-8 flex flex-col items-center justify-center shadow-md <?php echo isDisabled('PWD') ? 'disabled-button' : ''; ?>"
        onclick="<?php echo isDisabled('PWD') ? '' : "location.href='PWD_Verification.php'"; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"
          class="w-10 h-10 mb-3">
          <path d="M12 4a2 2 0 110-4 2 2 0 010 4zm7.07 9.25l-1.41 1.41L14 11.41V15h-2V9h2l3.66 3.66 1.41-1.41 1.41 1.41z" />
        </svg>
        <span class="font-bold text-lg">PWD (PERSON WITH DISABILITY)</span>
        <?php if ($activeCategory === 'PWD' && $activeStatus === 'Approved'): ?>
          <span class="text-green-600 font-semibold">(Approved)</span>
        <?php elseif ($activeCategory === 'PWD' && $activeStatus === 'Pending'): ?>
          <span class="text-yellow-600 font-semibold">(Pending)</span>
        <?php endif; ?>
      </button>

      <!-- Senior Citizen -->
      <button class="bg-green-200 hover:bg-green-300 transition rounded-xl p-8 flex flex-col items-center justify-center shadow-md <?php echo isDisabled('Senior') ? 'disabled-button' : ''; ?>"
        onclick="<?php echo isDisabled('Senior') ? '' : "location.href='Senior_Citizen_Verification.php'"; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"
          class="w-10 h-10 mb-3">
          <path d="M12 2a2 2 0 110 4 2 2 0 010-4zm2 6h-4v14h2v-6h2v6h2V8z" />
        </svg>
        <span class="font-bold text-lg">SENIOR CITIZEN</span>
        <?php if ($activeCategory === 'Senior' && $activeStatus === 'Approved'): ?>
          <span class="text-green-600 font-semibold">(Approved)</span>
        <?php elseif ($activeCategory === 'Senior' && $activeStatus === 'Pending'): ?>
          <span class="text-yellow-600 font-semibold">(Pending)</span>
        <?php endif; ?>
      </button>

      <!-- Gov Employee -->
      <button class="bg-green-200 hover:bg-green-300 transition rounded-xl p-8 flex flex-col items-center justify-center shadow-md <?php echo isDisabled('Government') ? 'disabled-button' : ''; ?>"
        onclick="<?php echo isDisabled('Government') ? '' : "location.href='Government_Verification.php'"; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 24 24"
          class="w-10 h-10 mb-3">
          <path d="M12 2a2 2 0 110 4 2 2 0 010-4zm2 6h-4v14h2v-6h2v6h2V8z" />
        </svg>
        <span class="font-bold text-lg">GOVERNMENT EMPLOYEE</span>
        <?php if ($activeCategory === 'Government' && $activeStatus === 'Approved'): ?>
          <span class="text-green-600 font-semibold">(Approved)</span>
        <?php elseif ($activeCategory === 'Government' && $activeStatus === 'Pending'): ?>
          <span class="text-yellow-600 font-semibold">(Pending)</span>
        <?php endif; ?>
      </button>

    </div>
  </div>
</main>

  <!-- JS -->
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


    async function renderUserBalance() {
      const hb = document.getElementById('header-balance');
      hb.textContent = '...';
      try {
        const res = await fetch('../get_passenger_data.php');
        const data = await res.json();
        if (data.success) {
          const balance = parseFloat(data.user.balance || 0).toFixed(2);
          hb.textContent = '' + balance;
        } else hb.textContent = '0.00';
      } catch {
        hb.textContent = '0.00';
      }
    }
    renderUserBalance();

    function openNav() {
      document.getElementById("sidebar").style.width = "280px";
    }
    function closeNav() {
      document.getElementById("sidebar").style.width = "0";
    }


            // Wait until the DOM is fully loaded
        document.addEventListener("DOMContentLoaded", function () {
            // Grab the toggle button and the menu
            const powerToggle = document.getElementById("power-toggle");
            const powerMenu = document.getElementById("power-menu");

            // Add a click event listener to the button
            powerToggle.addEventListener("click", function () {
            // Toggle the "hidden" class on the menu
            powerMenu.classList.toggle("hidden");
            });

            // Optional: close menu if user clicks outside
            document.addEventListener("click", function (event) {
            if (!powerMenu.contains(event.target) && !powerToggle.contains(event.target)) {
                powerMenu.classList.add("hidden");
            }
            });
        });


// CONFIRMATION POPUP FUNCTION
// Logout popup functions
function showLogoutPopup() {
  document.getElementById('logout-popup').style.display = 'flex';
  // Close sidebar when showing popup
  closeNav();
}

function closeLogoutPopup() {
  document.getElementById('logout-popup').style.display = 'none';
}

function confirmLogout() {
  // Redirect to logout/login page
  window.location.href = '../login.php';
}

// Close popup when clicking outside the modal
document.addEventListener('click', function(event) {
  const popup = document.getElementById('logout-popup');
  const modal = document.querySelector('.logout-modal');
  
  if (event.target === popup) {
    closeLogoutPopup();
  }
});
  </script>

</body>
</html>