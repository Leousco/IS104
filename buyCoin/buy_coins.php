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

// Fetch active coin deals from the database
$query = "SELECT * FROM coin_deals 
          WHERE Status = 'ACTIVE' 
          AND (
            DealType = 'STANDARD' 
            OR (DealType = 'LIMITED' AND NOW() BETWEEN ValidFrom AND ValidTo)
          )
          ORDER BY 
            CASE WHEN DealType = 'STANDARD' THEN 1
                 WHEN DealType = 'LIMITED' THEN 2
            END, 
            Price ASC";
$result = $conn->query($query);

// Get user's current coin balance
$user_id = $_SESSION['UserID'];
$balance_query = "SELECT balance FROM users WHERE UserID = ?";
$stmt = $conn->prepare($balance_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$current_balance = $user_data['balance'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Buy Coins - Passenger Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      color: #333;
      overflow-x: hidden;
      -ms-overflow-style: none;
    }
.global-map-bg {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/1280px-World_map_-_low_resolution.svg.png') center/cover no-repeat;
  opacity: 0.1;
  z-index: -1;
}

        body::-webkit-scrollbar {
          display: none; /* Hides the scrollbar */
          width: 0; /* Ensures no width space is reserved for the scrollbar */
        }

    header {
      background: linear-gradient(90deg, #2e7d32, #66bb6a);
      padding: 15px 20px;
      display: flex; align-items: center; justify-content: space-between;
      color: white;
      box-shadow: 0 3px 6px rgba(0,0,0,0.15);
      position: sticky; top: 0; z-index: 10;
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
    .menu { font-size: 26px; cursor: pointer; transition: transform 0.2s; }
    .menu:hover { transform: scale(1.1); }
    .profile {
      width: 35px; height: 35px; background-color: #2e7d32; color: white;
      font-size: 22px; display: flex; justify-content: center; align-items: center;
      border-radius: 50%; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      transition: all 0.3s ease; margin-right: 10px;
    }
    .profile:hover { background-color: #66bb6a; transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.3); }

/* Sidebar */
.sidebar { height: 100%; width: 0; position: fixed; top: 0; left: 0; background-color: #1b1b1b; overflow: hidden; transition: width 0.3s ease; padding-top: 60px; z-index: 1000; }
.sidebar a { display: block; padding: 14px 28px; font-size: 18px; color: #ddd; text-decoration: none; transition: 0.3s; white-space: nowrap; overflow: hidden; }
.sidebar a:hover { background: #2e7d32; color: white; padding-left: 35px; }
.sidebar .closebtn { position: absolute; top: 10px; right: 20px; font-size: 30px; cursor: pointer; color: white; }
.sidebar-power { position: absolute; bottom: 20px; left: 0; width: 100%; }
#power-toggle { background: none; border: none; color: #ddd; font-size: 20px; cursor: pointer; width: 100%; text-align: left; }
#power-toggle:hover { color: #2e7d32; }
.power-menu { margin-top: 10px; display: flex; flex-direction: column; gap: 8px; }
.power-menu a { font-size: 18px; color: #ddd; text-decoration: none; display: flex; align-items: center; transition: 0.3s; }
.power-menu a i { width: 25px; margin-right: 10px; text-align: center; }
.power-menu a:hover { background: #2e7d32; color: white; padding-left: 7px; border-radius: 6px; }
.hidden { display: none; }

/* Main Content */
.container { max-width: 1200px; margin: 40px auto; padding: 20px; }
h1 { 
  text-align: center; 
  margin-bottom: 10px; 
  color: #333; 
  font-size: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.subtitle {
  text-align: center;
  color: #666;
  margin-bottom: 30px;
  font-size: 16px;
}

.balance-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 30px;
  color: white;
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.balance-info h3 {
  font-size: 14px;
  font-weight: 400;
  opacity: 0.9;
  margin-bottom: 8px;
}
.balance-amount {
  font-size: 42px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 12px;
}
.balance-icon {
  font-size: 48px;
  opacity: 0.2;
}

.deal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    place-items: center;
}

.deal-card { 
  background: white; 
  border-radius: 16px; 
  box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
  padding: 24px; 
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  position: relative;
  overflow: hidden;
  width: 100%;
  max-width: 320px;
}
.deal-card:hover { 
  transform: translateY(-8px); 
  box-shadow: 0 8px 24px rgba(0,0,0,0.15); 
}

.deal-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.deal-badge.standard {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}
.deal-badge.limited {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.deal-icon-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}
.deal-icon { 
  font-size: 64px; 
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.3));
}

.deal-title { 
  font-weight: 700; 
  font-size: 22px; 
  margin-bottom: 8px; 
  color: #111; 
  text-align: center;
}
.deal-coins {
  text-align: center;
  font-size: 18px;
  color: #666;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.deal-coins i {
  color: #f59e0b;
  font-size: 20px;
}
.deal-coins strong {
  color: #111;
  font-size: 24px;
  font-weight: 800;
}

.deal-info { 
  font-size: 13px; 
  color: #666; 
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
  text-align: center;
}
.deal-info strong {
  color: #111;
}

.deal-price { 
  font-size: 32px; 
  font-weight: 800; 
  color: #22c55e; 
  margin-bottom: 20px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}
.deal-price-label {
  font-size: 14px;
  font-weight: 400;
  color: #666;
  margin-bottom: 4px;
}

.buy-btn { 
  display: block;
  text-align: center; 
  width: 100%; 
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white; 
  padding: 14px; 
  border-radius: 10px; 
  text-decoration: none; 
  transition: all 0.2s ease; 
  font-weight: 600;
  font-size: 16px;
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
  border: none;
  cursor: pointer;
}
.buy-btn:hover { 
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
}

.no-deals {
    margin: 60px auto 0;
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    max-width: 500px;
}
.no-deals i {
  font-size: 64px;
  color: #d1d5db;
  margin-bottom: 16px;
}
.no-deals h3 {
  color: #6b7280;
  margin-bottom: 8px;
}
.no-deals p {
  color: #9ca3af;
}

/* Color-coded card borders */
.deal-card.STANDARD { border-top: 4px solid #667eea; }
.deal-card.LIMITED { border-top: 4px solid #f5576c; }

.limited-timer {
  background: #fef3c7;
  color: #92400e;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.limited-timer i {
  color: #f59e0b;
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

<div class="global-map-bg"></div>

<div id="sidebar" class="sidebar" aria-hidden="true">
  <span class="closebtn" onclick="closeNav()"><i class="fas fa-caret-right" style="font-size: 20px;"></i></span>
  <a href="../passenger_dashboard.php"><i class="fas fa-home"></i> Homepage</a>
  <a href="../vehicle.php"><i class="fas fa-bus"></i> Vehicles</a>
  <a href="../ticketing/ticketing.php"><i class="fas fa-ticket-alt"></i> Buy Ticket</a>
  <a href="buy_coins.php"><i class="fas fa-coins"></i> Buy Coins</a>
  <a href="../feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a>
  <a href="../about.php"><i class="fas fa-info-circle"></i> About Us</a>
  <a href="../discountPage/discount_page.php"><i class="fas fa-percent"></i> Apply for a Discount</a>

  <div class="sidebar-power">
          <a href="javascript:void(0);" onclick="showLogoutPopup()">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
      </div>
</div>

<header>
  <div class="header-left">
    <div class="menu" onclick="openNav()"><i class="fas fa-grip-lines-vertical"></i></div>
     <span class="page-title">Buy Coins</span> <!-- or App Name -->
  </div>
  <div class="right-header">
            <a href="buy_coins.php" class="coin-balance">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="#F4C542"/>
                    <circle cx="12" cy="12" r="8.2" fill="#F9D66B"/>
                    <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
                </svg>
                <span id="header-balance"><?= number_format($current_balance, 2) ?></span>
            </a>
            <div class="profile" onclick="window.location.href='../user_prof.php'">👤</div>
        </div>
</header>

<div class="container">
  <h1>
    <i class="fas fa-store"></i>
    Coin Store
  </h1>
  <p class="subtitle">Purchase coins to use for tickets</p>


  <div class="deal-grid">
    <?php if ($result->num_rows > 0): ?>
      <?php while($deal = $result->fetch_assoc()): ?>
        <div class="deal-card <?= htmlspecialchars($deal['DealType']) ?>">
          <span class="deal-badge <?= strtolower($deal['DealType']) ?>">
            <?= htmlspecialchars($deal['DealType']) ?>
          </span>
          
          <div class="deal-icon-wrapper">
            <i class="fas fa-coins deal-icon"></i>
          </div>
          
          <div class="deal-title"><?= htmlspecialchars($deal['DealName']) ?></div>
          
          <div class="deal-coins">
            <i class="fas fa-coins"></i>
            <strong><?= number_format($deal['CoinAmount']) ?></strong> Coins
          </div>

          <?php if($deal['DealType'] === 'LIMITED'): ?>
            <div class="deal-info">
              <strong>Limited Offer!</strong><br>
              Valid until: <?= date('M d, Y', strtotime($deal['ValidTo'])) ?>
              <div class="limited-timer">
                <i class="fas fa-clock"></i>
                Expires soon!
              </div>
            </div>
          <?php else: ?>
            <div class="deal-info">
              <strong>Available Anytime</strong><br>
              No expiration date
            </div>
          <?php endif; ?>

          <div style="text-align: center;">
            <div class="deal-price-label">Price</div>
            <div class="deal-price">₱<?= number_format($deal['Price'], 2) ?></div>
          </div>

          <button class="buy-btn" onclick="buyDeal(<?= $deal['DealID'] ?>, '<?= htmlspecialchars($deal['DealName']) ?>', <?= $deal['Price'] ?>)">
            <i class="fas fa-shopping-cart"></i> Purchase Now
          </button>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-deals">
        <i class="fas fa-box-open"></i>
        <h3>No Coin Deals Available</h3>
        <p>Check back later for exciting coin deals!</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>

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
            hb.textContent = '₱' + balance;
        } else hb.textContent = 'Err';
    } catch {
        hb.textContent = 'Err';
    }
}

function openNav() {
  const sidebar = document.getElementById("sidebar");
  sidebar.style.width = "280px";
  sidebar.setAttribute("aria-hidden", "false");
}

function closeNav() {
  const sidebar = document.getElementById("sidebar");
  sidebar.style.width = "0";
  sidebar.setAttribute("aria-hidden", "true");
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


function buyDeal(dealId, dealName, price) {
  if (confirm(`Purchase ${dealName} for ₱${price.toFixed(2)}?`)) {
    window.location.href = `process_payment.php?deal_id=${dealId}`;
  }
}


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
  window.location.href = 'login.php';
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