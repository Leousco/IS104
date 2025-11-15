<?php


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
      background-color: #1b1b1b; overflow-x: hidden; transition: 0.4s;
      padding-top: 60px; z-index: 1000;
    }
    .sidebar a {
      padding: 14px 28px; text-decoration: none; font-size: 18px; color: #ddd;
      display: block; transition: 0.3s;
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
        <a href="vehicle.php"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
    </div>

    </div>



<!-- HEADER -->
<header>
   <div class="menu" onclick="openNav()">☰</div>
    <div class="right-header">
        <a href="redeem_voucher.php" class="coin-balance">
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

<!-- HOMEPAGE CONTENT -->
<div class="container" id="homepage-content">
  <div class="transport-options">
    <a href="vehiclePage/Bus.php"><img src="img/bus.jpg"  alt="Bus"><h3>Bus</h3></a>
    <a href="vehiclePage/ejeep.php"><img src="img/ejeep.png" alt="E-Jeep"><h3>E-Jeep</h3></a>
  </div>
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
            hb.textContent = '₱' + balance;
        } else hb.textContent = 'Err';
    } catch {
        hb.textContent = 'Err';
    }
}
renderUserBalance();
  function openNav() { document.getElementById("sidebar").style.width = "280px"; document.getElementById("sidebar").setAttribute("aria-hidden", "false"); }
  function closeNav() { document.getElementById("sidebar").style.width = "0"; document.getElementById("sidebar").setAttribute("aria-hidden", "true"); }

 
  const exploreBtn = document.getElementById('explore-btn');
  const homepageContent = document.getElementById('homepage-content');
  const slideshowContainer = document.getElementById('slideshow-container');
  const destinations = document.querySelectorAll('.destination');
  let currentIndex = 0;
  let interval;


let imageIntervals = [];
const DEST_IMAGE_VISIBLE = 1; 

function startImageCarousel(dest) {
  const thumbnails = dest.querySelectorAll('.thumb');
  let imgIndex = 0;

 
  thumbnails.forEach(t => t.style.display = 'none');


  function showImages() {
  thumbnails.forEach((t, i) => {
    t.classList.remove('show');
    t.style.display = 'none';
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
    if(i === index){
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

  function startSlideshow() {
  homepageContent.style.display = 'none';
  slideshowContainer.style.display = 'flex';
  showDestination(currentIndex);
  interval = setInterval(() => {
    stopAllImageCarousels();
    currentIndex = (currentIndex + 1) % destinations.length;
    showDestination(currentIndex);
  }, 10000); 
}

function backToHomepage() {
  clearInterval(interval);
  stopAllImageCarousels();
  slideshowContainer.style.display = 'none';
  homepageContent.style.display = 'flex';
  currentIndex = 0;
}

  exploreBtn.addEventListener('click', startSlideshow);
</script>

</body>
</html>
