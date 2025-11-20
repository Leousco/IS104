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
$query = "SELECT f.FeedbackID, f.Message, u.FirstName, u.LastName, u.ProfilePictureURL, f.PassengerID 
          FROM feedback f 
          LEFT JOIN users u ON f.PassengerID = u.UserID 
          ORDER BY f.FeedbackID DESC";
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
        transition: 0.4s;
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
      padding: 20px;
      max-width: 800px;
      margin: auto;
    }
    h2 {
      text-align: center;
      margin-top: 0px;
      margin-bottom: 40px;
    }

    .feedback-box {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      margin-bottom: 30px;
    }
    .feedback-box label {
      display: block;
      margin: 10px 0 10px;
      font-weight: bold;
    }
    .feedback-box input,
    .feedback-box textarea {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    width: 100%;
    min-height: 150px; 
    padding: 12px; 
    margin-bottom: 20px; 
    border: 1px solid #ddd; 
    border-radius: 8px; 
    font-size: 15px; 
    color: #333; 
    background-color: #f9f9f9; 
    resize: none; 
    transition: all 0.2s ease-in-out;
    }

    .feedback-box textarea:focus {
        border-color: #2e7d32;
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.3);
        background-color: #fff;
        outline: none;
    }
    .feedback-box button {
      width: 100%;
      background-color: #0a0a0a;
      color: #f6f7ec;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      color: white;
      box-shadow: 1px 2px 6px rgba(0,0,0,0.3);
    }
    .feedback-box button:hover {
     background-color: #222;
    }

    .reviews {
      margin-top: 10px;
    }

    .review {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to bottom right, #e6fce9, #d8fada);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: flex-start;
      gap: 18px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
      .review:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    }
    .avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #66bb6a, #2e7d32);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        border: 2px solid #ffffff;
        flex-shrink: 0;
        overflow: hidden; /* Ensures image stays within circle */
    }

    /* Added style for profile image inside avatar */
    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .review-content {
        flex: 1;
        font-size: 16px;
        color: #555;
        line-height: 1.7;
        margin-left: 15px;
    }

    .review-name {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 4px;
        color: #2c3e50;
        transition: color 0.2s ease;
    }

    .review-item {
        display: flex;
        align-items: flex-start;
        padding: 20px;
        border: 1px solid #eee;
        border-radius: 10px;
        margin-bottom: 15px;
        background-color: #fff;
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


  </style>
</head>

<body>
      <div class="global-map-bg"></div>

      <div id="sidebar" class="sidebar" aria-hidden="true">
      <span class="closebtn" onclick="closeNav()">&times;</span>
      
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
  <button id="power-toggle">
    <i class="fas fa-sign-out-alt"></i>
  </button>
  <div id="power-menu" class="power-menu hidden">
    <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <a href="Feedback.php"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>
    </div>

  <header>
    <div class="menu" onclick="openNav()">☰</div>
    <div class="right-header">
      <a href="redeem_voucher.php" class="coin-balance">
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
            <div class="review-name"><?php echo $displayName; ?></div>
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
  </script>
</body>
</html>