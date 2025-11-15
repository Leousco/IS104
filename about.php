<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
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

    /* PAGE STYLES */
    :root {
      --bg-top: #eef6ff;
      --bg-bottom: #f6fbf8;
      --card: #ffffff;
      --muted: #556070;
      --text: #0f1720;
      --shadow-md: 0 8px 28px rgba(15,23,32,0.06);
      --radius: 14px;
      --container-pad: 28px;
      --avatar-size: 64px;
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

    .site {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 30px;
      max-width: 900px;
      margin: 40px auto;
      padding: 20px;
    }

    .card {
      width: 100%;
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow-md);
      padding: var(--container-pad);
      border: 1px solid rgba(15,23,32,0.05);
    }

    .section-heading {
      font-size: 28px;
      font-weight: 600;
      text-align: center;
      margin: 20px 0;
    }
    .highlight {
      background-color: #66bb6a;
      color: #fff;
      padding: 2px 10px;
      border-radius: 12px;
    }

    .lead {
      color: var(--muted);
      font-size: 15px;
      text-align: justify;
      line-height: 1.6;
    }

    .members-title {
      font-size: 30px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 30px;
    }

    .member-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      width: 100%;
    }

    .member {
      display: flex;
      align-items: center;
      gap: 15px;
      background: #fff;
      padding: 15px;
      border-radius: 10px;
      border: 1px solid rgba(0,0,0,0.05);
      transition: all 0.3s;
    }
    .member:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-md);
    }

    .avatar {
      width: var(--avatar-size);
      height: var(--avatar-size);
      border-radius: 999px;
      object-fit: cover;
      flex-shrink: 0;
    }

    .member-name {
      font-weight: 700;
      color: var(--text);
    }
    .member-role {
      color: var(--muted);
      font-size: 13px;
    }

    .foot {
      text-align: center;
      color: var(--muted);
      font-size: 13px;
      margin-top: 30px;
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

  <!-- HEADER -->
  <header>
    <div class="menu" onclick="openNav()">☰</div>
    <div class="right-header">
      <a href="redeem_voucher.php" class="coin-balance">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
          xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" fill="#F4C542"/>
          <circle cx="12" cy="12" r="8.2" fill="#F9D66B"/>
          <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
        </svg>
        <span id="header-balance">₱0.00</span>
      </a>
      <div class="profile" onclick="window.location.href='user_prof.php'">👤</div>
    </div>
  </header>

  <!-- SIDEBAR -->
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
    <a href="about.php"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>


</div>

  <!-- MAIN CONTENT -->
  <main class="site">
    <section class="card about">
      <h1 class="section-heading">About <span class="highlight">NovaCore</span></h1>
      <p class="lead">
        Welcome to NovaCore: City Transportation Management System.  
        This system is designed to make city commuting easier, faster, and more reliable. NovaCore delivers digital solutions for bus, train, and e-jeep services—enabling passengers to view schedules, purchase tickets, and access real-time travel updates.
      </p>
      <p class="lead" style="margin-top:8px;">
        Our team from Quezon City University developed this system to promote smart city transport, reduce waiting times, and ensure a seamless travel experience for everyone.
      </p>
    </section>

    <aside class="card members-card">
      <h2 class="members-title">Meet our <span class="highlight">team</span></h2>
      <div class="member-grid">
        <div class="member"><img class="avatar" src="1X1PICTURE/Dormetorio.jpg"><div><div class="member-name"><b>Dormetorio</b>, Aivan Chauncy G.</div><div class="member-role">Project Manager</div></div></div>
        <div class="member"><img class="avatar" src="1X1PICTURE/Alcantra.jpg"><div><div class="member-name"><b>Alcantra</b>, Angela P.</div><div class="member-role">UI/UX Designer</div></div></div>
        <div class="member"><img class="avatar" src="1X1PICTURE/Dans.jpg"><div><div class="member-name"><b>Dans</b>, Anne Pierre A.</div><div class="member-role">UI/UX Designer</div></div></div>
        <div class="member"><img class="avatar" src="1X1PICTURE/Galvan.jpg"><div><div class="member-name"><b>Galvan</b>, Ryan Angelo V.</div><div class="member-role">Front-End Developer</div></div></div>
        <div class="member"><img class="avatar" src="1X1PICTURE/Gica.jpg"><div><div class="member-name"><b>Gica</b>, John Denver I.</div><div class="member-role">System Analyst</div></div></div>
        <div class="member"><img class="avatar" src="1X1PICTURE/layaan.jpg"><div><div class="member-name"><b>Laya-an</b>, Mark Jemuel M.</div><div class="member-role">Front-End Developer</div></div></div>
        <div class="member"><img class="avatar" src="1X1PICTURE/Pedro.jpg"><div><div class="member-name"><b>Pedro</b>, Marcus Daniele R.</div><div class="member-role">Back-End Developer</div></div></div>
        <div class="member"><img class="avatar" src="https://via.placeholder.com/128?text=R"><div><div class="member-name"><b>Sequitin</b>, Sean Terrence T.</div><div class="member-role">Database Administrator</div></div></div>
        <div class="member"><img class="avatar" src="1X1PICTURE/Villete.jpg"><div><div class="member-name"><b>Villete</b>, Leonardo Enrico B.</div><div class="member-role">Lead Developer</div></div></div>
      </div>
    </aside>

    <div class="foot">© Quezon City University — City Transport. Built to improve urban mobility.</div>
  </main>

  <!-- JS -->
  <script>
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

    function openNav() {
      document.getElementById("sidebar").style.width = "280px";
    }
    function closeNav() {
      document.getElementById("sidebar").style.width = "0";
    }

  document.getElementById('power-toggle').addEventListener('click', function () {
    const menu = document.getElementById('power-menu');
    menu.classList.toggle('hidden');
  });

  </script>
</body>
</html>
