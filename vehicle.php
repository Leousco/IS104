<?php
include("config.php");
include("auth.php");

$loginPage = "/SADPROJ/login.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: $loginPage");
    exit();
}

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "PASSENGER") {
    header("Location: $loginPage?error=unauthorized");
    exit();
}

$balance = isset($_SESSION['balance']) ? $_SESSION['balance'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>Passenger Dashboard & Destinations</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      color: #333;
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

    .sidebar {
      height: 100%; width: 0; position: fixed; top: 0; left: 0;
      background-color: #1b1b1b; overflow-x: hidden; transition: 0.3s;
      padding-top: 60px; z-index: 1000;
    }
    .sidebar a {
      padding: 14px 28px; text-decoration: none; font-size: 18px; color: #ddd;
      display: block; transition: 0.3s;
      white-space: nowrap;
      overflow: hidden;
    }
    .sidebar a:hover { background: #2e7d32; color: #fff; padding-left: 35px; }
    .sidebar .closebtn { position: absolute; top: 10px; right: 20px; font-size: 30px; cursor: pointer; color: white; }

    .container {
    display: flex;
    flex-direction: column; 
    align-items: center;
    padding: 70px 20px;
    gap: 25px;
    max-width: 960px;
    width: 100%;
    margin: 0 auto;
    position: relative;
    z-index: 1;
    }

    @media (max-width: 480px) {
        .container {
            padding: 20px 15px;
            gap: 20px;
        }
    }

  /* --- 1. Container (.transport-options) --- */
.transport-options { 
    display: flex; 
    justify-content: center; 
    gap: 30px; /* Slight reduction in gap for tighter grouping */
    flex-wrap: wrap; 
    margin-top: 20px; /* Added margin for separation from content above */
}

/* --- 2. Option Link (a) --- */
.transport-options a {
    /* Layout and Sizing */
    text-decoration: none; 
    text-align: center; 
    width: 450px; /* Slightly reduced and fixed width */
    max-width: 100%; /* Ensures responsiveness on small screens */

    /* Aesthetics */
    color: #333; 
    background: #fff; 
    padding: 30px 20px; /* Slightly reduced vertical padding for tighter look */
    border-radius: 12px; /* Smoother, more consistent radius */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Cleaner, more subtle shadow */
    
    /* Transitions & Feedback */
    transition: all 0.3s ease-in-out;
    border: 1px solid transparent; /* Added border for smooth transition */
}

/* --- 3. Option Link Hover (:hover) --- */
.transport-options a:hover { 
    transform: translateY(-5px); /* Smooth lift effect */
    background: #fff; /* Keep background white for clarity */
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); /* Stronger shadow on lift */
    border: 1px solid var(--accent, #2e7d32); /* Highlight border with accent color */
}

/* --- 4. Heading (h3) inside Option Link --- */
.transport-options a h3 {
    font-size: 1.5rem; /* Reduced size for better visual hierarchy */
    font-weight: 700; /* Increased weight for emphasis */
    margin-top: 15px; 
    color: #1a1a1a; /* Darker text for readability */
}

/* --- 5. Image inside Option Link --- */
.transport-options img { 
    width: 120px; /* Reduced width */
    height: auto; /* Changed to auto to maintain aspect ratio */
    object-fit: contain; /* Ensures image scales properly without cropping */
    margin-bottom: 10px; 
    /* Add a slight filter for subtle effect */
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

    .cards { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
    .card { background: #fff; padding: 20px; border-radius: 12px; text-align: center; width: 300px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
    .card:hover { transform: translateY(-7px); box-shadow: 0 6px 16px rgba(0,0,0,0.18); }
    .card img { width: 100%; border-radius: 10px; margin-bottom: 10px; }
    .card p { font-size: 15px; color: #555; }

     .global-map-bg {
      position: fixed; top: 0; left: 0;
      width: 100%; height: 100%;
      background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/1280px-World_map_-_low_resolution.svg.png') center center no-repeat;
      background-size: cover; opacity: 0.1; z-index: -1;
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

 .hero-section {
  background: linear-gradient(to right, #2e7d32, #66bb6a);
  color: white;
  padding: 80px 40px;
  text-align: center;
}

.hero-content h2 {
  font-size: 48px;
  margin-bottom: 20px;
}

.hero-content p {
  font-size: 20px;
  margin-bottom: 30px;
}

.hero-btn {
  background: white;
  color: #2e7d32;
  padding: 12px 30px;
  font-weight: bold;
  border-radius: 8px;
  text-decoration: none;
  transition: background 0.3s ease;
}

.hero-btn:hover {
  background: #f1f8e9;
}
.features-grid {
  display: flex;
  justify-content: center;
  gap: 40px;
  padding: 60px 20px;
  background: #f5f7fa; /* light background for contrast */
  flex-wrap: wrap;
}

.feature-card {
  display: block; /* make the whole card clickable */
  background: #fff;
  padding: 30px 25px;
  border-radius: 14px;
  text-align: center;
  width: 280px;
  text-decoration: none; /* remove underline */
  color: inherit; /* keep text color consistent */
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor: pointer;
}

.feature-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 10px 24px rgba(0,0,0,0.2);
}

.feature-card img {
  width: 100px;
  height: auto;
  margin-bottom: 20px;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
  transition: transform 0.3s ease;
}

.feature-card:hover img {
  transform: scale(1.1);
}

.feature-card h3 {
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 12px;
  color: #2e7d32; /* Novacore green accent */
}

.feature-card p {
  font-size: 15px;
  color: #555;
  line-height: 1.4;
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

<!-- SIDEBAR -->
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
      
      <a href="buyCoin/buy_coins.php">
        <i class="fas fa-coins"></i> Buy Coins
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
          <a href="javascript:void(0);" onclick="showLogoutPopup()">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
      </div>

</div>



<!-- HEADER -->
<header>
   <div class="header-left">
    <div class="menu" onclick="openNav()"><i class="fas fa-grip-lines-vertical"></i></div>
    <span class="page-title">Vehicle Selection</span> <!-- or App Name -->
  </div>
    <div class="right-header">
        <a href="buyCoin/buy_coins.php" class="coin-balance">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" fill="#F4C542"/>
                <circle cx="12" cy="12" r="8.2" fill="#F9D66B"/>
                <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
            </svg>
            <span id="header-balance">0</span>
        </a>
        <div class="profile" onclick="window.location.href='user_prof.php'">👤</div>
    </div>
</header>

<!-- HOMEPAGE CONTENT -->
 <section class="hero-section">
  <div class="hero-content">
    <h2>Smart Urban Mobility Starts Here</h2>
    <p>Track, tap, and travel with ease. Your journey is just one click away.</p>
  </div>
  
</section>


<section class="features-grid">
  <a href="vehiclePage/Bus.php" class="feature-card">  
    <img src="img/bus.jpg" alt="Bus">
    <h3>Bus</h3>
    <p>Reliable routes across the city.</p>
  </a>

  <a href="vehiclePage/ejeep.php" class="feature-card">
    <img src="img/ejeep.png" alt="E-Jeep">
    <h3>E-Jeep</h3>
    <p>Eco-friendly rides for modern commuters.</p>
  </a>
</section>



<script>
// Hover sidebar functionality
const sidebar = document.getElementById("sidebar");
const hoverZone = document.getElementById("hover-zone");

hoverZone.addEventListener("mouseenter", () => {
    sidebar.style.width = "280px";
});

sidebar.addEventListener("mouseleave", () => {
    sidebar.style.width = "0";
});

// Sidebar navigation functions
function openNav() { 
    document.getElementById("sidebar").style.width = "280px"; 
    document.getElementById("sidebar").setAttribute("aria-hidden", "false"); 
}

function closeNav() { 
    document.getElementById("sidebar").style.width = "0"; 
    document.getElementById("sidebar").setAttribute("aria-hidden", "true"); 
}

// Fetch and display user balance
async function renderUserBalance() {
    console.log('Starting renderUserBalance()');
    
    const hb = document.getElementById('header-balance');
    
    if (!hb) {
        console.error('ERROR: header-balance element not found!');
        return;
    }
    
    hb.textContent = '...';
    
    try {
        console.log('Fetching balance...');
        const res = await fetch('get_passenger_data.php');
        
        console.log('Response status:', res.status);
        
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        
        const data = await res.json();
        console.log('Received data:', data);
        
        if (data.success) {
            const balance = parseFloat(data.user.balance || 0).toFixed(2);
            hb.textContent = balance;
            console.log('Balance updated:', balance);
        } else {
            console.error('API error:', data.error);
            hb.textContent = 'Err';
        }
    } catch (error) {
        console.error('Fetch error:', error);
        hb.textContent = 'Err';
    }
}

// Load balance when page loads
renderUserBalance();


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
