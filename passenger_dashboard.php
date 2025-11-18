<?php
session_start();
if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "PASSENGER") {
    header("Location: login.php?error=unauthorized");
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
  <title>Passenger Dashboard</title>
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
    width: 0; 
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


    .container { 
      display: flex; 
      flex-direction: column; 
      align-items: center; 
      padding: 40px 20px; gap: 30px; 
      position: relative; 
      z-index: 1; }
      
    .slideshow-container {
      min-width: 320px; 
      min-height: calc(100vh - 70px); 
      display: flex; 
      flex-direction: column; 
      align-items: center; 
      justify-content: center; 
      padding: 30px 20px; 
      gap: 30px; 
      will-change: transform, min-height;
    } 
    .destination { display: flex; width: 100%; }
    .destination-flex {
      display: flex;
      gap: 40px;
      justify-content: space-between;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      align-items: flex-start;
    }


.destination-text {
  flex: 1;
  padding-right: 40px;
}
    .description { flex: 1; font-size: 30px; color: #333; opacity: 0; transition: opacity 1s ease-in; }
    .description h2 { font-size: 50px; color: #2e7d32; margin-bottom: 15px; }
    .description ul { margin-top: 10px; padding-left: 20px; list-style: disc; }
   .thumbnails-pattern { flex: 1; display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
  .thumb { border-radius: 12px; object-fit: cover; opacity: 0; transition: opacity 0.5s ease-in; }
.thumb.show {
  display: block;
  opacity: 1;
}
    .thumb-small { width: 90px; height: 60px; }
    .thumb-medium { width: 120px; height: 80px; }
   .thumb-large {
  width: 500px;  
  height: 400px;  
  border-radius: 15px;
  object-fit: cover;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  transition: transform 0.3s ease;
}

.thumb-large:hover {
  transform: scale(1.05);
}
    .back-btn { padding: 15px 25px; background: #2e7d32; color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 15px; transition: 0.3s; }
    .back-btn:hover { background: #66bb6a; transform: scale(1.05); }
@keyframes typewriter { from { width: 0; } to { width: 100%; } }
    @keyframes blink { 50% { border-color: transparent; } }
    .typing {
      display: inline-block;
      overflow: hidden;
      white-space: nowrap;
      border-right: .15em solid orange;
      animation: typewriter 2.5s steps(60) 1 forwards, blink .75s step-end infinite;
    }

   .transport-buttons {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 40px;
      flex-wrap: wrap;
      margin-top: 40px;
      text-align: center;
    }

    .transport-buttons a {
      text-decoration: none;
      color: #333;
      background: #fff;
      padding: 40px 20px;
      border-radius: 15px;
      text-align: center;
      transition: all 0.3s;
      box-shadow: 0 3px 8px rgba(0,0,0,0.13);
      width: 300px;
    }

    .transport-buttons a:hover {
      transform: translateY(-6px);
      background: #f1f8e9;
    }

    .transport-buttons img {
      width: 150px;
      height: 100px;
      margin-bottom: 10px;
    }

    .transport-buttons a h3 {
      font-size: 2.4rem;
      font-weight: bold;
      margin-top: 10px;
      color: #121312;
    }

    /* Container to fix the button position */
.fixed-booking-container {
    position: fixed;
    bottom: 150px; /* Distance from the bottom edge */
    left: 80px;   /* Distance from the left edge */
    z-index: 1000; /* Ensures it floats above other page content */
}

/* Styling for the specific button class */
.btn-book {
    /* Visual styling */
    background-color: #2e7d32; /* Primary green color */
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    border: none;
    padding: 10px 30px; 
    border-radius: 10px; 
    cursor: pointer;
    text-transform: uppercase;
    /* Effects and prominence */
    box-shadow: 0 4px 15px rgba(46, 125, 50, 0.5); /* Green shadow */
    transition: all 0.3s ease;
    width: auto;
    display: block; 
}

.btn-book:hover {
    background-color: #388e3c; /* Darker green on hover */
    transform: scale(1.05); /* Slight enlargement effect */
    box-shadow: 0 6px 20px rgba(46, 125, 50, 0.7);
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
  background: #f5f7fa;
}

.feature-card {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  text-align: center;
  width: 300px;
}

.feature-card img {
  width: 60px;
  margin-bottom: 20px;
}

.feature-card h3 {
  font-size: 22px;
  margin-bottom: 10px;
  color: #2e7d32;
}

.main-footer {
    background: linear-gradient(90deg, #2e7d32, #66bb6a);
  color: white;
  padding: 10px 20px;
  text-align: center;
  margin-top: 100px;
  box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
}

.footer-content {
  max-width: 960px;
  margin: 0 auto;
}

.footer-content p {
  font-size: 14px;
  margin-bottom: 10px;
}

.footer-links {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
}

.footer-links a {
  color: #f1f8e9;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: #ffffff;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 30px;
  padding: 60px 20px;
  background: #f5f7fa;
  max-width: 1000px;
  margin: 0 auto;
}

.grid-card {
  background: white;
  text-decoration: none;
  color: #333;
  padding: 30px 20px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.grid-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  background: #f1f8e9;
}

.grid-card i {
  font-size: 36px;
  color: #2e7d32;
  margin-bottom: 15px;
}

.grid-card h3 {
  font-size: 16px;
  font-weight: 600;
}


  </style>
</head>
<body>
  

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
      <button id="power-toggle">
        <i class="fas fa-sign-out-alt"></i>
      </button>
      <div id="power-menu" class="power-menu hidden">
        <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <a href="passenger_dashboard.php"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>

    </div>


  <header>
     <div class="menu" onclick="openNav()">☰</div>
    <div class="right-header">
        <a href="buyCoin/buy_coins.php" class="coin-balance">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" fill="#F4C542"/>
                <circle cx="12" cy="12" r="8.2" fill="#F9D66B"/>
                <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9"/>
            </svg>
            <span id="header-balance">₱0</span>
        </a>
        <div class="profile" onclick="window.location.href='user_prof.php'">👤</div>
    </div>
  </header>
  <section class="hero-section">
  <div class="hero-content">
    <h2>Smart Urban Mobility Starts Here</h2>
    <p>Track, tap, and travel with ease. Your journey is just one click away.</p>
    <a href="vehicle.php" class="hero-btn">Plan Your Trip</a>
  </div>
  
</section>

<section class="features-grid">
  <div class="feature-card">
    <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" alt="Routes" />
    <h3>Real-Time Routes</h3>
    <p>Track buses and E-jeep live with precision.</p>
  </div>
  <div class="feature-card">
    <img src="https://cdn-icons-png.flaticon.com/512/1041/1041916.png" alt="Passes" />
    <h3>Digital Passes</h3>
    <p>No paper, no hassle.</p>
  </div>
  <div class="feature-card">
    <img src="https://cdn-icons-png.flaticon.com/512/1828/1828884.png" alt="24/7" />
    <h3>24/7 Service</h3>
    <p>Plan trips anytime, anywhere — day or night.</p>
  </div>
</section>

<section class="dashboard-grid">
  <a href="vehicle.php" class="grid-card">
    <i class="fas fa-bus"></i>
    <h3>Vehicles</h3>
  </a>
  <a href="ticketing/ticketing.php" class="grid-card">
    <i class="fas fa-ticket-alt"></i>
    <h3>Buy Ticket</h3>
  </a>
  <a href="buyCoin/buy_coins.php" class="grid-card">
    <i class="fas fa-coins"></i>
    <h3>Buy Coins</h3>
  </a>
  <a href="redeem_voucher.php" class="grid-card">
    <i class="fas fa-gift"></i>
    <h3>Redeem Voucher</h3>
  </a>
  <a href="feedback.php" class="grid-card">
    <i class="fas fa-comment-dots"></i>
    <h3>Feedback</h3>
  </a>
  <a href="discountPage/discount_page.php" class="grid-card">
    <i class="fas fa-percent"></i>
    <h3>Apply Discount</h3>
  </a>
  <a href="user_prof.php" class="grid-card">
    <i class="fas fa-user"></i>
    <h3>Profile</h3>
  </a>
  <a href="about.php" class="grid-card">
    <i class="fas fa-info-circle"></i>
    <h3>About Us</h3>
  </a>
</section>



<footer class="main-footer">
  <div class="footer-content">
    <p>&copy; 2025 Novacore City Transportation. All rights reserved.</p>
  </div>
</footer>

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
        } else hb.textContent = 'Err';
    } catch {
        hb.textContent = 'Err';
    }
}
renderUserBalance();
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

  const homepageContent = document.getElementById('homepage-content');
  const slideshowContainer = document.getElementById('slideshow-container');
  const destinations = document.querySelectorAll('.destination');
  let currentIndex = 0;
  let interval;
  let imageIntervals = [];

  function startImageCarousel(dest) {
    const thumbnails = dest.querySelectorAll('.thumb');
    let imgIndex = 0;

    thumbnails.forEach(t => {
      t.style.display = 'none';
      t.classList.remove('show');
    });

    function showImages() {
      thumbnails.forEach(t => {
        t.style.display = 'none';
        t.classList.remove('show');
      });

      const showIdx = imgIndex % thumbnails.length;
      thumbnails[showIdx].style.display = 'block';
      setTimeout(() => thumbnails[showIdx].classList.add('show'), 10);
      imgIndex = (imgIndex + 1) % thumbnails.length;
    }

    showImages();
    const intv = setInterval(showImages, 2000);
    imageIntervals.push(intv);
  }

  function stopAllImageCarousels() {
    imageIntervals.forEach(clearInterval);
    imageIntervals = [];
  }

  function showDestination(index) {
    destinations.forEach((dest, i) => {
      dest.style.display = i === index ? 'flex' : 'none';

      const desc = dest.querySelector('.description');
      const thumbs = dest.querySelectorAll('.thumb');

      if (i === index) {
        desc.style.opacity = 0;
        thumbs.forEach(t => t.style.opacity = 0);

        setTimeout(() => {
          desc.style.opacity = 1;
          thumbs.forEach(t => t.style.opacity = 1);
        }, 500);

        startImageCarousel(dest);
      }
    });
  }

  function backToHomepage() {
    clearInterval(interval);
    stopAllImageCarousels();
    currentIndex = 0;
  }

  document.addEventListener('DOMContentLoaded', () => {
    slideshowContainer.style.display = 'flex';
    showDestination(currentIndex);

    interval = setInterval(() => {
      stopAllImageCarousels();
      currentIndex = (currentIndex + 1) % destinations.length;
      showDestination(currentIndex);
    }, 10000);

    // 🔽 Auto-scroll after slideshow finishes one full loop
    setTimeout(() => {
      if (homepageContent) {
        homepageContent.scrollIntoView({ behavior: 'smooth' });
      }
    }, destinations.length * 10000); // 10s per slide
  });

  document.addEventListener('DOMContentLoaded', () => {
    const bookButton = document.querySelector('.btn-book');
    
    if (bookButton) {
        bookButton.addEventListener('click', () => {
            window.location.href = 'vehicle.php';
        });
    }
});


  document.getElementById('power-toggle').addEventListener('click', function () {
    const menu = document.getElementById('power-menu');
    menu.classList.toggle('hidden');
  });

</script>
</body>
</html>


 
