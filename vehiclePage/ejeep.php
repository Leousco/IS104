<?php
require_once '../config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>E-Jeep Routes & Schedules</title>  
  <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>

    * { 
      margin: 0; padding: 0; box-sizing: border-box; 
    }
    body {
      font-family: "Segoe UI", Tahoma, geneva, Verdana, sans-serif;
      background: #f5f7fa;
      color: #333;
    }
    .card { background: white; border-radius: 0.5rem; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
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
    .menu { font-size: 26px; cursor: pointer; transition: transform 0.2s; }
    .menu:hover { transform: scale(1.1); }
    .sidebar {
      height: 100%; width: 0; position: fixed; top: 0; left: 0;
      background-color: #1b1b1b; overflow-x: hidden; transition: 0.4s;
      padding-top: 60px; z-index: 1000;
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
        /* Set a fixed width to keep the text aligned, even if icons are different sizes */
        width: 25px; 
        /* Add some space between the icon and the link text */
        margin-right: 0px; 
        /* Vertically align the icon with the text */
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

    .profile:hover { background-color: #66bb6a; transform: scale(1.1); }
    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    /* CHECKBOX */

input[type="checkbox"].routeCheckbox {
  width: 18px;
  height: 18px;
  accent-color: #2e7d32; 
  cursor: pointer;
  transition: transform 0.2s;
}
input[type="checkbox"].routeCheckbox:hover {
  transform: scale(1.2);
}

.sidebar-power {
    position: absolute;
    bottom: 20px;
    left: 0;
    width: 100%;
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
    <div class="profile" onclick="location.href='../login.php'">👤</div>
  </header>

 <div id="sidebar" class="sidebar" aria-hidden="true">
  <span class="closebtn" onclick="closeNav()"><i class="fas fa-caret-right" style="font-size: 20px;"></i></span>
  
  <a href="../passenger_dashboard.php">
    <i class="fas fa-home"></i> Homepage
  </a>
  
  <!-- <a href="../vehicle.php">
    <i class="fas fa-bus"></i> Vehicles
  </a> -->
  
  <a href="Bus.php">
    <I class="fas fa-bus"></I> Bus Transit
  </a>

  <a href="ejeep.php">
    <i class="fas fa-car-side"></i> E-Jeep Transit
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
  
  <a href="../discountPage/discount_page.php">
    <i class="fas fa-percent"></i> Apply for a Discount
  </a>

  <div class="sidebar-power">
          <a href="../vehicle.php">
            <i class="fas fa-angle-left"></i> Back
          </a>
      </div>

</div>

<!-- MAIN -->
<div class="max-w-7xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-bold mt-6">🚙 E-Jeep Transit — Schedules & Routes</h1>
    <p class="text-sm text-gray-600">Select a route and filter schedules.</p>
  </div>

  <div class="grid grid-cols-12 gap-4">
    <!-- Routes -->
    <div class="col-span-4">
      <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold">Routes</h2>
          <button id="refreshRoutes" class="text-sm text-blue-600 hover:underline">Refresh</button>
        </div>
        <input id="routeSearch" placeholder="Search route number or name" class="w-full p-2 border rounded mb-3" />
        <div id="routesList" class="space-y-2 max-h-[60vh] overflow-auto"></div>
      </div>
    </div>

    <!-- Schedules -->
    <div class="col-span-5">
      <div class="card p-4">

        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold">Schedules</h2>
          <div>

            <label class="text-sm text-gray-600 mr-2">Day</label>
              <select id="dayPicker" class="p-1 border rounded">
                <option value="">All</option>
                <option value="Mon">Monday</option>
                <option value="Tue">Tuesday</option>
                <option value="Wed">Wednesday</option>
                <option value="Thu">Thursday</option>
                <option value="Fri">Friday</option>
                <option value="Sat">Saturday</option>
                <option value="Sun">Sunday</option>
              </select>

            <button id="refreshSchedules" class="ml-2 text-sm text-blue-600 hover:underline">Refresh</button>
          </div>
        </div>

        <div id="schedulesList" class="space-y-2 max-h-[70vh] overflow-auto"></div>
      </div>
    </div>

    <!-- Traffic + Map -->
    <div class="col-span-3 space-y-4">
      <div class="card p-4">
        <h3 class="font-semibold mb-2">Traffic Report</h3>
        <p id="trafficText" class="text-sm text-gray-600 mb-4">Select a route to see traffic status</p>
        <div id="trafficPill" class="inline-block px-3 py-1 rounded-full font-medium text-white"></div>
        <div class="mt-4">
          <h4 class="font-semibold text-sm mb-1">Traffic details</h4>
          <div id="trafficDetails" class="text-xs text-gray-600">No detailed traffic data available.</div>
        </div>
      </div>

      <div class="card p-4">
        <h3 class="font-semibold mb-2">Map</h3>
        <div id="mapPlaceholder" class="w-full h-56 bg-gray-200 flex items-center justify-center text-gray-500 text-center">
          Map temporarily removed — placeholder.
        </div>
      </div>
    </div>
  </div>
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

  
// Sidebar
function openNav(){
  document.getElementById("sidebar").style.width="280px";
}
function closeNav(){
  document.getElementById("sidebar").style.width="0";
}

const routesListEl=document.getElementById('routesList');
const schedulesListEl=document.getElementById('schedulesList');
const routeSearch=document.getElementById('routeSearch');
const datePicker=document.getElementById('dateSelect');
const trafficText=document.getElementById('trafficText');
const trafficPill=document.getElementById('trafficPill');

let routes=[],schedules=[],selectedRouteId=null;

function setTrafficUI(status){
  if(!status){trafficText.textContent='Traffic data not available.';trafficPill.textContent='N/A';trafficPill.style.backgroundColor='#6b7280';return;}
  trafficText.textContent=`Current traffic: ${status}`;trafficPill.textContent=status;
  trafficPill.style.backgroundColor=
    status==='LIGHT'?'#16a34a':status==='MODERATE'?'#f59e0b':status==='HEAVY'?'#dc2626':'#6b7280';
}



// RENDER ROUTES
function renderRoutes(list) {
  routesListEl.innerHTML = '';
  if (!list.length) {
    routesListEl.innerHTML = '<div class="text-sm text-gray-500">No routes found.</div>';
    return;
  }
  list.forEach(r => {
    const div = document.createElement('div');
    div.className = 'p-2 border rounded hover:bg-gray-50 flex justify-between items-center';
    div.innerHTML = `
      <div>
        <div class="font-medium">#${r.RouteID} — ${escapeHtml(r.StartLocation)} → ${escapeHtml(r.EndLocation)}</div>
        <div class="text-xs text-gray-500">Lat ${r.Latitude || '-'} · Lon ${r.Longitude || '-'}</div>
      </div>
      <div class="text-right">
        <input type="checkbox" class="routeCheckbox" data-id="${r.RouteID}" />
      </div>
    `;
    routesListEl.appendChild(div);

    // handle checkbox logic
    const checkbox = div.querySelector('.routeCheckbox');
    checkbox.addEventListener('change', () => {
      // uncheck all others
      document.querySelectorAll('.routeCheckbox').forEach(cb => {
        if (cb !== checkbox) cb.checked = false;
      });

      if (checkbox.checked) {
        selectedRouteId = r.RouteID;
        div.classList.add('bg-blue-50');
        loadSchedules(r.RouteID);
        setTrafficUI(r.traffic_status || null);
      } else {
        selectedRouteId = null;
        div.classList.remove('bg-blue-50');
        loadSchedules(); // show all schedules
        setTrafficUI(null);
      }

      // update highlighting
      Array.from(routesListEl.querySelectorAll('.p-2')).forEach(n => {
        if (n !== div) n.classList.remove('bg-blue-50');
      });
    });
  });
}



// RENDER SCHEDULES
function renderSchedules(list) {
  schedulesListEl.innerHTML = '';
  if (!list.length) {
    schedulesListEl.innerHTML = '<div class="text-sm text-gray-500">No schedules for selected filters.</div>';
    return;
  }
  list.forEach(s => {
    
    function formatTime(t) {
      if (!t) return '—';
      const [h, m] = t.split(':');
      const hour = parseInt(h);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const hr12 = hour % 12 || 12;
      return `${hr12}:${m} ${ampm}`;
    }

    const dep = s.DepartureTime ? formatTime(s.DepartureTime) : '—';
    const arr = s.ArrivalTime ? formatTime(s.ArrivalTime) : '—';

    const statusBadge = s.Status === 'CANCELLED' ? '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs">CANCELLED</span>' : '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">ACTIVE</span>';
    const html = document.createElement('div');
    html.className = 'p-3 border rounded bg-white';
    html.innerHTML = `
      <div class="flex justify-between items-start">
        <div>
          <div class="font-medium">#${s.ScheduleID} — ${escapeHtml(s.StartLocation)} → ${escapeHtml(s.EndLocation)}</div>
          <div class="text-xs text-gray-500">Vehicle: ${escapeHtml(s.PlateNo || '—')} · Type: ${escapeHtml(s.TypeName || '—')}</div>
        </div>
        <div class="text-right">${statusBadge}</div>
      </div>
      <div class="mt-2 text-sm text-gray-700">
        <div><strong>Depart:</strong> ${escapeHtml(dep)}</div>
        <div><strong>Arrive:</strong> ${escapeHtml(arr)}</div>
      </div>
    `;
    schedulesListEl.appendChild(html);
  });
}

function escapeHtml(s){return!s&&s!==0?'':String(s).replace(/[&<>"'`=\/]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','=':'&#x3D;','`':'&#x60;'}[c]));}

// ✅ Fetch routes (TypeID = 2 for E-Jeep)
async function loadRoutes(q = '') {
  const url = new URL('get_routes.php', window.location.href);
  url.searchParams.set('typeID', 2); 
  if (q) url.searchParams.set('q', q);

  const res = await fetch(url);
  const data = await res.json();

  if (!data.success) {
    console.error("Failed to fetch routes");
    return;
  }

  routes = data.routes || [];
  renderRoutes(routes);
}

// ✅ Fetch schedules for E-Jeep
async function loadSchedules(routeId=null){
  const url=new URL('get_schedules.php',window.location.href);
  url.searchParams.set('typeID',2);
  if(routeId)url.searchParams.set('route_id',routeId);

  const dayPicker = document.getElementById('dayPicker');
  if(dayPicker.value) url.searchParams.set('day', dayPicker.value);

  const res=await fetch(url);
  const data=await res.json();
  if(!data.success)return;
  schedules=data.schedules||[];
  renderSchedules(schedules);
}

// Events
routeSearch.addEventListener('input',()=>{const q=routeSearch.value.trim();if(!q)renderRoutes(routes);else{const f=routes.filter(r=>String(r.RouteID)===q||r.StartLocation.toLowerCase().includes(q.toLowerCase())||r.EndLocation.toLowerCase().includes(q.toLowerCase()));renderRoutes(f);}});
document.getElementById('refreshRoutes').addEventListener('click',()=>loadRoutes(routeSearch.value.trim()));
document.getElementById('refreshSchedules').addEventListener('click',()=>loadSchedules(selectedRouteId));
dayPicker.addEventListener('change', () => loadSchedules(selectedRouteId));

// Init
(async function init(){await loadRoutes();await loadSchedules();})();

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
</script>
</body>
</html>
