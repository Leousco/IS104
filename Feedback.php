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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FIX: Use the logged-in UserID instead of hardcoded '1'
    $passengerID = $_SESSION['UserID']; 
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $insertQuery = "INSERT INTO feedback (PassengerID, Message) VALUES ('$passengerID', '$message')";
    if ($conn->query($insertQuery)) {
        $_SESSION['feedback_success'] = true;
        header("Location: Feedback.php");
        exit;
    } else {
        // If this fails, check your Foreign Key constraints in the database
        echo "<script>alert('Error submitting feedback. Database error: " . $conn->error . "');</script>";
    }
}

// FIX: Join with users table to get Name and Profile Picture
// We use LEFT JOIN so legacy feedback (without matching users) still shows up
$query = "SELECT f.FeedbackID, f.Message, f.DateSubmitted, u.FirstName, u.LastName, u.ProfilePictureURL, f.PassengerID 
          FROM feedback f 
          LEFT JOIN users u ON f.PassengerID = u.UserID 
          ORDER BY f.DateSubmitted DESC";

$result = $conn->query($query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <title>User Feedback</title>
  <style>


.success-notification {
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

@keyframes fadeInOut {
  0% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
  10% { opacity: 1; transform: translateX(-50%) translateY(0); }
  90% { opacity: 1; }
  100% { opacity: 0; transform: translateX(-50%) translateY(-10px); }
}

   

    /* ... (Your CSS styles here) ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        body::-webkit-scrollbar {
    display: none; /* Hides the scrollbar */
    width: 0; /* Ensures no width space is reserved for the scrollbar */
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

        /* Right side header controls (coins + profile) matched from passenger_dashboard.php */
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

     /* SIDEBAR */
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

    .sidebar a i {
        width: 25px; 
        margin-right: 0px; 
        text-align: center; 
    }

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

.main-content {
  padding: 30px;
  max-width: 900px;
  margin: auto;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

/* Headings */
h2 {
  text-align: center;
  margin: 0 0 30px;
  font-size: 28px;
  font-weight: 700;
  color: #2e7d32;
  letter-spacing: 0.5px;
}

/* Feedback Form */
.feedback-box {
  background: #fff;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  margin-bottom: 40px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.feedback-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.feedback-box label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #444;
}

.feedback-box textarea {
  width: 100%;
  min-height: 140px;
  padding: 14px;
  border: 1px solid #ddd;
  border-radius: 10px;
  font-size: 15px;
  color: #333;
  background-color: #f9f9f9;
  resize: none;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
}
.feedback-box textarea:focus {
  border-color: #2e7d32;
  box-shadow: 0 0 0 3px rgba(46,125,50,0.25);
  background-color: #fff;
  outline: none;
}

.feedback-box button {
  width: 100%;
  background: linear-gradient(135deg, #2e7d32, #388e3c);
  color: #fff;
  font-size: 1rem;
  font-weight: 600;
  border: none;
  padding: 14px 20px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.3s ease, transform 0.2s ease;
  box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
.feedback-box button:hover {
  background: linear-gradient(135deg, #388e3c, #43a047);
  transform: translateY(-2px);
}

/* Reviews Section */
.reviews {
  margin-top: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.review {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: flex-start;
  gap: 18px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.review:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

.avatar {
  width: 55px;
  height: 55px;
  border-radius: 50%;
  background: linear-gradient(135deg, #66bb6a, #2e7d32);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  border: 2px solid #fff;
  flex-shrink: 0;
  overflow: hidden;
}
.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.review-content {
  flex: 1;
  font-size: 15px;
  color: #555;
  line-height: 1.6;
}

.review-name {
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 6px;
  color: #2e7d32;
  transition: color 0.2s ease;
}
.review-name:hover {
  color: #1b5e20;
}

.review-message {
  font-size: 14px;
  color: #444;
}


    .global-map-bg {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/1280px-World_map_-_low_resolution.svg.png') center center no-repeat;
      background-size: cover;
      opacity: 0.1;
      z-index: -1;
    }

    .bug-report-wrapper {
      font-style: 0.85rem; 
      color: #446a2b;
      text-align: center;
      margin-top: 0px; /* adjust spacing as needed */
      margin-bottom: 20px;
    }

    .bug-link {
      color: #4a6274;
      font-weight: 700;
      text-decoration: none;
      font-weight: 500;
      cursor: pointer;
    }

    .bug-link:hover {
      text-decoration: underline;
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

.review-name span {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  color: #2e7d32;
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

  <header>
    <div class="header-left">
    <div class="menu" onclick="openNav()"><i class="fas fa-grip-vertical"></i></div>
    <span class="page-title">Users' Feedback</span> <!-- or App Name -->
   </div>
    <div class="right-header">
      <a href="buyCoin/buy_coins.php" class="coin-balance">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" fill="#F4C542"/>
          <circle cx="12" cy="12" r="8.2" fill="#F9D66B"/>
          <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
        </svg>
        <span id="header-balance">0.00</span>
      </a>
      <div class="profile" onclick="window.location.href='user_prof.php'">👤</div>
    </div>
  </header>

  <?php if (isset($_SESSION['feedback_success'])): ?>
    <div class="success-notification">Success! Feedback submitted.</div>
    <?php unset($_SESSION['feedback_success']); ?>
  <?php endif; ?>


  <div class="main-content">
    <h2>User Feedback</h2>

    <!-- Feedback Form -->
    <div class="feedback-box">
      <form method="POST" action="Feedback.php">
        <label for="message">Your Feedback</label>

        <textarea name="message" id="message" rows="5" class=""required></textarea>

        <button type="submit">Submit Feedback</button>
      </form>
    </div>

    <!-- Reviews Section -->
    <div class="reviews" id="reviews">
      <?php while ($row = $result->fetch_assoc()): 
          // Determine display name
          $displayName = !empty($row['FirstName']) ? htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) : "Passenger " . htmlspecialchars($row['PassengerID']);
          
          // Determine profile image or fallback initial
          $hasProfilePic = !empty($row['ProfilePictureURL']);

          $feedbackDate = !empty($row['DateSubmitted']) 
          ? date("F j, Y", strtotime($row['DateSubmitted'])) 
          : '';
      ?>
        <div class="review">
          <div class="avatar">
              <?php if ($hasProfilePic): ?>
                  <img src="<?php echo htmlspecialchars($row['ProfilePictureURL']); ?>" alt="Profile">
              <?php else: ?>
                  <?php echo strtoupper(substr($displayName, 0, 1)); ?>
              <?php endif; ?>
          </div>

          
          <div class="review-content">
            <div class="review-name">
            <?php echo $displayName; ?>
            <?php if ($feedbackDate): ?>
              <span style="font-size: 0.85rem; color: #666; margin-left: 10px;">
                • <?php echo $feedbackDate; ?>
              </span>
            <?php endif; ?>
            </div>

            <div class="review-message"><?php echo htmlspecialchars($row['Message']); ?></div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
  
  <div class="bug-report-wrapper">
  <p>Encountered a techinal problem? <a href="report_bug.php" class="bug-link">Report a Bug</a></p>
  </div>

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

  

    async function renderUserBalance() {
      const hb = document.getElementById('header-balance');
      hb.textContent = '...';
      try {
        const res = await fetch('get_passenger_data.php');
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