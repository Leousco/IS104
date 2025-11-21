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

// Note: We don't fetch data here, as we'll use an AJAX endpoint.
$balance = isset($_SESSION['balance']) ? $_SESSION['balance'] : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>User — Redeem Voucher</title>
    <style>
        /* ... (Your CSS styles here) ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
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

    /* 2. Style the Icon element */
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

    h1 { 
        font-size: 24px;
        font-weight: 600;
        margin: 0; 
        color: #333;
    }
    a { 
        color: var(--accent, #2e7d32);
        text-decoration: none; 
        transition: color 0.2s ease;
    }
    a:hover {
        color: #388e3c;
    }

    .card { 
        background: #ffffff;
        border: 1px solid var(--border-color, #e0e0e0);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    label { 
        display: block; 
        font-size: 14px;
        margin-bottom: 6px; 
        font-weight: 500;
        color: var(--muted, #666);
    }
    input { 
        width: 100%; 
        padding: 10px;
        border: 1px solid #ccc; 
        border-radius: 8px;
        margin-bottom: 15px;
        background-color: #f9f9f9;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    input:focus {
        border-color: var(--accent, #2e7d32);
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
        outline: none;
        background-color: #fff;
    }

    .btn { 
        background: var(--accent, #2e7d32); 
        color: #fff; 
        border: none; 
        padding: 12px 18px;
        border-radius: 8px;
        cursor: pointer; 
        transition: all 0.2s ease;
        font-size: 16px;
        font-weight: 600;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
    }

    .btn.secondary { 
        background: #fff; 
        color: var(--accent, #2e7d32); 
        border: 1px solid var(--accent, #2e7d32);
        text-align: center; 
        text-decoration: none; 
        display: inline-block; 
        line-height: normal; 
        box-shadow: none;
    }

    .btn:hover { 
        opacity: 1;
        transform: translateY(-1px);
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    }
    .btn.secondary:hover {
        background: #f4fcf4;
        box-shadow: none;
        transform: translateY(-1px);
    }

       .container {
            min-width: 300px; 
            padding: 30px 24px; 
            max-width: 960px; /* Increased max-width for a more spacious desktop layout */
            margin: 0 auto; 
            box-sizing: border-box; 
        }
        footer { text-align: center; font-size: 13px; color: var(--muted); margin-top: 20px; }

        :root {
            --accent: #2e7d32;
            --muted: #666;
        }

        .global-map-bg {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/1280px-World_map_-_low_resolution.svg.png') center center no-repeat;
            background-size: cover; opacity: 0.1; z-index: -1;
        }

        .notification {
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
        <i class="fas fa-gift"></i> Buy Coins
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


<header>
    <div class="menu" onclick="openNav()"><i class="fas fa-grip-lines-vertical"></i></div>
    <div class="right-header">
        <a href="redeem_voucher.php" class="coin-balance">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="12" cy="12" r="10" fill="#F4C542" />
                <circle cx="12" cy="12" r="8.2" fill="#F9D66B" />
                <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
            </svg>
            <span id="header-balance"> 0</span>
        </a>
        <div class="profile" onclick="window.location.href='user_prof.php'">👤</div>
    </div>
</header>

<div id="voucher-success" class="notification success" style="display:none;"></div>
<div id="voucher-error" class="notification error" style="display:none;"></div>

<div class="container">

            <div class="balance-display">
                <div class="label">Wallet Balance</div>
                <div class="amount" id="user-balance"><i class="fas fa-coins"></i> 0.00</div>
            </div>

    <div class="card">
        <h3>Redeem Voucher</h3>
        <form id="redeem-form">
            <label>Voucher Code</label>
            <input id="redeem-code" type="text" placeholder="Enter code" required />
            <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
                <button type="submit" class="btn">Redeem</button>
            </div>
        </form>
    </div>

    <footer>Redeem your voucher code and get coins.</footer>
</div>

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
}

function closeNav() { 
    document.getElementById("sidebar").style.width = "0"; 
}

// Notification display function
function showNotification(id, message) {
    const box = document.getElementById(id);
    box.textContent = message;

    // Reset animation
    box.style.display = 'none';
    box.style.animation = 'none';
    void box.offsetWidth; // Force reflow
    box.style.display = 'block';
    box.style.animation = 'fadeInOut 4s ease forwards';
}

/**
 * Fetches the user's current balance from the server and displays it.
 */
async function renderUserBalance() {
    console.log('Starting renderUserBalance()');
    
    const userBalanceElement = document.getElementById('user-balance');
    const headerBalanceElement = document.getElementById('header-balance');
    
    // Show loading state
    if (userBalanceElement) userBalanceElement.innerHTML = '<i class="fas fa-coins"></i> Loading...';
    if (headerBalanceElement) headerBalanceElement.textContent = '...';

    try {
        console.log('Fetching balance from get_passenger_data.php...');
        const res = await fetch('get_passenger_data.php');
        
        console.log('Response status:', res.status);
        
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        
        const data = await res.json();
        console.log('Received data:', data);
        
        if (data.success) {
            let balance = Number(data.user.balance);
            console.log('Balance value:', balance);

            if (userBalanceElement) {
                userBalanceElement.innerHTML = '<i class="fas fa-coins"></i> ' + balance.toFixed(2);
            }

            if (headerBalanceElement) {
                headerBalanceElement.textContent = balance.toFixed(2);
            }
            
            console.log('Balance updated successfully');
        } else {
            // Handle failure
            console.error('API returned error:', data.error);
            if (userBalanceElement) userBalanceElement.innerHTML = '<i class="fas fa-coins"></i> Error';
            if (headerBalanceElement) headerBalanceElement.textContent = 'Error';
        }
    } catch (error) {
        // Handle Network Error
        console.error('Network error during fetch:', error);
        if (userBalanceElement) userBalanceElement.innerHTML = '<i class="fas fa-coins"></i> Network Error';
        if (headerBalanceElement) headerBalanceElement.textContent = 'Err';
    }
}

// Voucher redemption form handler
document.getElementById('redeem-form').addEventListener('submit', async e => {
    e.preventDefault();
    
    const codeInput = document.getElementById('redeem-code');
    const code = codeInput.value.trim().toUpperCase();

    if (!code) return alert('Enter a code.');

    const btn = e.submitter;
    btn.disabled = true;

    try {
        const res = await fetch('process_redemption.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code })
        });
        const result = await res.json();
        
        if (result.success) {
            const successBox = document.getElementById('voucher-success');
            successBox.textContent = `✅ Voucher redeemed successfully! Added: ₱${parseFloat(result.discount).toFixed(2)} to your balance.`;

            // Reset animation
            successBox.style.display = 'none';
            successBox.style.animation = 'none';
            void successBox.offsetWidth; // Force reflow
            successBox.style.display = 'block';
            successBox.style.animation = 'fadeInOut 4s ease forwards';

            codeInput.value = '';
            // Reload balance after successful redemption
            renderUserBalance();
        } else {
            const errorBox = document.getElementById('voucher-error');
            errorBox.textContent = result.error || '❌ Redemption failed.';

            // Reset animation
            errorBox.style.display = 'none';
            errorBox.style.animation = 'none';
            void errorBox.offsetWidth;
            errorBox.style.display = 'block';
            errorBox.style.animation = 'fadeInOut 4s ease forwards';
        }
    } catch (error) {
        console.error('Network or server error:', error);
        alert('A network error occurred. Please try again.');
    } finally {
        btn.disabled = false;
    }
});

// Initial call to load data when the page loads
renderUserBalance();
</script>
</body>
</html>