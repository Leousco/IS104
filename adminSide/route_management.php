<?php
include("../config.php");
include("../auth.php");
$loginPage = "/SADPROJ/login.php";

// Only allow logged-in users
if (!isset($_SESSION['UserID'])) {
    header("Location: $loginPage");
    exit();
}

// Optional: role-based protection
if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "ADMIN") {
    header("Location: $loginPage?error=unauthorized");
    exit();
}

// ✅ Fetch Routes with Vehicle Type
$sql = "
    SELECT 
        r.RouteID, 
        r.StartLocation, 
        r.EndLocation, 
        r.Latitude, 
        r.Longitude,
        COALESCE(r.traffic_status,'UNKNOWN') AS traffic_status,
        COALESCE(vt.TypeName, 'Unassigned') AS VehicleType
    FROM route r
    LEFT JOIN vehicletype vt ON r.TypeID = vt.TypeID
    ORDER BY r.RouteID ASC
";
$result = $conn->query($sql);
if ($result === false) die('Database query error: ' . $conn->error);

// ✅ Fetch Vehicle Types for dropdown
$types = $conn->query("SELECT TypeID, TypeName FROM vehicletype ORDER BY TypeID ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Route Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<style>
:root {
  --header-green: #a7f3d0;
  --accent-green: #4ade80;
  --bg: #f9fafb;
  --card: #ffffff;
  --text-dark: #111827;
  --sidebar-bg: #1f2937;
}

/* --- Base Styles --- */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Inter', sans-serif;
}

body {
  background-color: var(--bg);
  color: var(--text-dark);
  overflow-x: hidden;
}

body::-webkit-scrollbar {
  display: none;
  width: 0;
}

/* --- Header --- */
header {
  background-color: var(--header-green);
  color: #064e3b;
  display: flex;
  align-items: center;
  padding: 12px 16px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 20;
}

header h1 {
  margin-left: 10px;
  font-size: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* --- Sidebar --- */
.sidebar {
  position: fixed;
  top: 0;
  left: -280px;
  width: 280px;
  height: 100%;
  background-color: var(--sidebar-bg);
  color: white;
  padding: 20px;
  transition: left 0.3s ease;
  z-index: 30;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
}

.sidebar.open {
  left: 0;
}

.sidebar h2 {
  font-size: 22px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 20px;
  font-weight: 700;
}

.sidebar a {
  display: flex;
  align-items: center;
  color: white;
  text-decoration: none;
  padding: 12px 15px;
  border-radius: 6px;
  transition: background-color 0.2s ease, color 0.2s ease;
  margin-bottom: 5px;
}

.sidebar a i {
  margin-right: 12px;
  width: 20px;
  text-align: center;
}

.sidebar a:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: var(--accent-green);
}

/* --- Menu Icon --- */
.menu-icon {
  font-size: 24px;
  cursor: pointer;
  color: #064e3b;
}

/* --- Overlay --- */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: none;
  z-index: 25;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.overlay.show {
  display: block;
  opacity: 1;
}

/* --- Buttons --- */
.btn {
  padding: 6px 12px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
}

.btn-add {
  background: #90ee90;
  margin-left: 35px;
  margin-top: 30px;
}

.btn-add:hover {
  background: #16a34a;
  color: white;
  transform: translateY(-2px);
}

.btn-edit {
  background: orange;
  color: white;
}

.btn-delete {
  background: red;
  color: white;
}

.btn-edit:hover {
  background: brown;
}

.btn-delete:hover {
  background: #7f1d1d;
}

/* --- Table --- */
table {
  border-collapse: collapse;
  width: 95%;
  margin: 20px auto;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

th,
td {
  border: 1px solid #ddd;
  padding: 10px;
  text-align: center;
}

th {
  background: #8de699;
}

/* --- Modal --- */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(3px);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.modal-content {
  background: #fff;
  border-radius: 12px;
  padding: 24px 28px;
  width: 420px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.modal-content h3 {
  margin-bottom: 15px;
  font-size: 1.2rem;
  font-weight: 600;
  color: #065f46;
  text-align: center;
}

.modal-content label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-top: 10px;
  margin-bottom: 4px;
}

.modal-content input,
.modal-content select {
  width: 100%;
  padding: 8px 10px;
  border-radius: 6px;
  border: 1px solid #d1d5db;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.modal-content input:focus,
.modal-content select:focus {
  border-color: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
  outline: none;
}

.modal-buttons {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-top: 18px;
}

.btn-save {
  flex: 1;
  background: #4ade80;
  color: #064e3b;
  border: none;
  padding: 10px 0;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel {
  flex: 1;
  background: #9ca3af;
  color: white;
  border: none;
  padding: 10px 0;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

main { padding: 20px; animation: slideIn 0.6s ease; }
@keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
.card { background-color: var(--card); padding: 20px; border-radius: 12px; box-shadow: 0 6px 15px rgba(0,0,0,0.08); transition: transform 0.3s; border-left: 5px solid var(--accent-green); }
.card:hover { transform: translateY(-5px); }
.card h3 { font-size: 16px; color: #6b7280; font-weight: 500; }
.card p { font-size: 32px; font-weight: 800; margin-top: 8px; color: #065f46; }

</style>

</head>

<body>

<header class="bg-green-200 text-green-900 p-4 shadow-md flex items-center">
    <div class="menu-icon" id="menuBtn"><i class="fas fa-bars"></i></div>
    <h1><i class="fas fa-route text-green-700"></i> Route Management</h1>
</header>

<div class="sidebar" id="sidebar">
    <h2 class="text-green-400"><i class="fas fa-grip-vertical"></i> Menu</h2>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
    <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
    <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
    <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="route_management.php" class="bg-green-600 rounded"><i class="fas fa-route"></i> Route Management</a>
    <a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions </a>
    <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
</div>

<div class="overlay" id="overlay"></div>
  
<div class="overlay" id="overlay"></div>

<!-- MAIN CONTENT -->

<main>
  <h2 class="text-2xl font-bold mb-6 text-gray-800">Routes Overview</h2>
    <section class="cards">
        <div class="card"><h3>Total Routes</h3><p id="totalRoutes">0</p></div>
        <div class="card"><h3>Light Traffic</h3><p id="lightTraffic">0</p></div>
        <div class="card"><h3>Heavy Traffic</h3><p id="heavyTraffic">0</p></div>
        <div class="card"><h3>Moderate Traffic</h3><p id="moderateTraffic">0</p></div>
    </section>
  <div>
    <button class="btn btn-add" onclick="showForm()">+ Add Route</button>
  </div>

  <table>
    <thead>
      <tr>
        <th class="bg-green-200">ID</th>
        <th class="bg-green-200">Start</th>
        <th class="bg-green-200">End</th>
        <th class="bg-green-200">Latitude</th>
        <th class="bg-green-200">Longitude</th>
        <th class="bg-green-200">Traffic Status</th>
        <th class="bg-green-200">Vehicle Type</th> 
        <th class="bg-green-200">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php while($row=$result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['RouteID'] ?></td>
        <td><?= htmlspecialchars($row['StartLocation']) ?></td>
        <td><?= htmlspecialchars($row['EndLocation']) ?></td>
        <td><?= htmlspecialchars($row['Latitude']) ?></td>
        <td><?= htmlspecialchars($row['Longitude']) ?></td>
        <td><?= htmlspecialchars($row['traffic_status']) ?></td>
        <td><?= htmlspecialchars($row['VehicleType']) ?></td> <!-- ✅ Vehicle type -->
        <td>
          <button class="btn btn-edit"
            onclick="editRoute(<?= $row['RouteID']?>,
            '<?=addslashes($row['StartLocation'])?>',
            '<?=addslashes($row['EndLocation'])?>',
            '<?=$row['Latitude']?>',
            '<?=$row['Longitude']?>',
            '<?=$row['traffic_status']?>',
            '<?=htmlspecialchars($row['VehicleType'])?>')">Edit</button>
          <button class="btn btn-delete" onclick="deleteRoute(<?= $row['RouteID']?>)">Delete</button>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</main>

<!-- ✅ Modal -->
<div id="formOverlay" class="modal-overlay">
  <div class="modal-content">
    <h3 id="formTitle">Add Route</h3>
    <form id="routeForm">
      <input type="hidden" name="id" id="editId">
      <label>Start Location</label><input type="text" name="start" id="start" required>
      <label>End Location</label><input type="text" name="end" id="end" required>
      <label>Latitude</label><input type="text" name="lat" id="lat" required>
      <label>Longitude</label><input type="text" name="lon" id="lon" required>
      <label>Traffic Status</label>
      <select name="status" id="status" required>
        <option value="LIGHT">LIGHT</option>
        <option value="MODERATE">MODERATE</option>
        <option value="HEAVY">HEAVY</option>
      </select>
      <label>Vehicle Type</label> <!-- ✅ NEW FIELD -->
      <select name="typeID" id="typeID" required>
        <option value="">Select Type</option>
        <?php while($t=$types->fetch_assoc()): ?>
          <option value="<?= $t['TypeID'] ?>"><?= htmlspecialchars($t['TypeName']) ?></option>
        <?php endwhile; ?>
      </select>
      <div class="modal-buttons">
        <button type="submit" class="btn-save">Save</button>
        <button type="button" class="btn-cancel" onclick="hideForm()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
  // ✅ UPDATE OVERVIEW CARDS
  const rows = document.querySelectorAll('tbody tr');
  let total = 0;
  let light = 0;
  let moderate = 0;
  let heavy = 0;

  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    if (cells.length > 0) {
      total++;
      const traffic = cells[5].textContent.trim().toUpperCase(); // Traffic Status column
      
      if (traffic === 'LIGHT') {
        light++;
      } else if (traffic === 'MODERATE') {
        moderate++;
      } else if (traffic === 'HEAVY') {
        heavy++;
      }
    }
  });

  // Update the cards
  document.getElementById('totalRoutes').textContent = total;
  document.getElementById('lightTraffic').textContent = light;
  document.getElementById('heavyTraffic').textContent = heavy;
  document.getElementById('moderateTraffic').textContent = moderate;
});

const sidebar=document.getElementById('sidebar');
const overlay=document.getElementById('overlay');
document.getElementById('menuBtn').onclick=()=>{sidebar.classList.toggle('open');overlay.classList.toggle('show');};
overlay.onclick=()=>{sidebar.classList.remove('open');overlay.classList.remove('show');};

const formOverlay=document.getElementById('formOverlay');
const routeForm=document.getElementById('routeForm');
const formTitle=document.getElementById('formTitle');
const editId=document.getElementById('editId');

function showForm(edit=false){
  formOverlay.style.display='flex';
  routeForm.reset();
  formTitle.textContent=edit?'Edit Route':'Add Route';
  if(!edit)editId.value='';
}
function hideForm(){formOverlay.style.display='none';}

function editRoute(id,start,end,lat,lon,status,typeName){
  showForm(true);
  editId.value=id;
  document.getElementById('start').value=start;
  document.getElementById('end').value=end;
  document.getElementById('lat').value=lat;
  document.getElementById('lon').value=lon;
  document.getElementById('status').value=status;

  // match dropdown option
  const opts=document.getElementById('typeID').options;
  for(let i=0;i<opts.length;i++){
    if(opts[i].text===typeName){opts[i].selected=true;break;}
  }
}

routeForm.addEventListener('submit',async e=>{
  e.preventDefault();
  const data=Object.fromEntries(new FormData(routeForm).entries());
  const isEdit=data.id&&parseInt(data.id)>0;
  const url=isEdit?'update_route.php':'add_route.php';
  const res=await fetch(url,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(data)
  });
  const result=await res.json();
  if(result.success){alert(isEdit?'Route updated!':'Route added!');location.reload();}
  else alert(result.error||'Failed.');
});

async function deleteRoute(id){
  if(!confirm('Delete this route?'))return;
  const res=await fetch('delete_route.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({id})
  });
  const result=await res.json();
  if(result.success){alert('Route deleted.');location.reload();}
  else alert(result.error||'Failed.');
}
</script>
</body>
</html>