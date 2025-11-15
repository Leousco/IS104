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

// ✅ Detect route column names dynamically
$colCheck = $conn->query("SHOW COLUMNS FROM route");
$routeCols = [];
while ($row = $colCheck->fetch_assoc()) {
    $routeCols[] = strtolower($row['Field']);
}

$colOrigin = in_array('origin', $routeCols) ? 'Origin' :
             (in_array('startlocation', $routeCols) ? 'StartLocation' :
             (in_array('routename', $routeCols) ? 'RouteName' : 'RouteID'));

$colDestination = in_array('destination', $routeCols) ? 'Destination' :
                  (in_array('endlocation', $routeCols) ? 'EndLocation' : $colOrigin);

// ✅ Show both Bus (TypeID 1) and E-Jeep (TypeID 2)
$result = $conn->query("
  SELECT 
    s.ScheduleID,
    s.VehicleID,
    s.RouteID,
    s.DepartureTime,
    s.ArrivalTime,
    s.Date,
    s.Status,
    r.StartLocation,
    r.EndLocation,
    v.PlateNo,
    vt.TypeName
  FROM schedule s
  JOIN route r ON s.RouteID = r.RouteID
  JOIN vehicle v ON s.VehicleID = v.VehicleID
  JOIN vehicletype vt ON v.TypeID = vt.TypeID
");


$routes = $conn->query("SELECT RouteID, $colOrigin AS Origin, $colDestination AS Destination, TypeID FROM route");

$vehicles = $conn->query("SELECT VehicleID, PlateNo, TypeID FROM vehicle");


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Schedule Management</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
body { background-color: var(--bg); color: var(--text-dark); overflow-x: hidden; }
body::-webkit-scrollbar { display: none; width: 0; }

header { background-color: var(--header-green); color: #064e3b; display: flex; align-items: center; padding: 12px 16px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 20; }
header h1 { margin-left: 10px; font-size: 20px; font-weight: 600; display: flex; align-items: center; gap: 8px; }

.sidebar { position: fixed; top: 0; left: -280px; width: 280px; height: 100%; background-color: var(--sidebar-bg); color: white; padding: 20px; transition: left 0.3s ease; z-index: 30; box-shadow: 2px 0 10px rgba(0,0,0,0.3); display: flex; flex-direction: column; }
.sidebar.open { left: 0; }
.sidebar h2 { font-size: 22px; display: flex; align-items: center; gap: 10px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; font-weight: 700; }
.sidebar a { display: flex; align-items: center; color: white; text-decoration: none; padding: 12px 15px; border-radius: 6px; transition: background-color 0.2s ease, color 0.2s ease; margin-bottom: 5px; }
.sidebar a i { margin-right: 12px; width: 20px; text-align: center; }
.sidebar a:hover { background-color: rgba(255, 255, 255, 0.1); color: var(--accent-green); }

.menu-icon { font-size: 24px; cursor: pointer; color: #064e3b; }

.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: none; z-index: 25; opacity: 0; transition: opacity 0.3s ease; }
.overlay.show { display: block; opacity: 1; }

main { padding: 20px; animation: slideIn 0.6s ease; }
@keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

table {
  border-collapse:collapse;
  width:95%;
  margin:20px auto;
  background:#fff;
  border-radius:8px;
  overflow:hidden;
  box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
th, td { border:1px solid #ddd; padding:10px; text-align:center; }
th { background:#8de699; font-weight:bold; }

.btn { 
  padding:6px 12px; 
  border:none; 
  cursor:pointer; 
  border-radius:5px; 
  font-size:14px; 
}
.btn-add { 
  background:#90ee90; 
  margin-left: 35px; 
  margin-top: 30px; 
}
.btn-add:hover {
  background-color: green;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  color: white;
}
.btn-edit { background:orange; color:#fff; }
.btn-edit:hover{ background-color: brown; box-shadow: 0 4px 8px rgba(0,0,0,0.15); color: white; }
.btn-delete { background: red; color:#fff; }
.btn-delete:hover{ background-color: #870718; box-shadow: 0 4px 8px rgba(0,0,0,0.15); color: white; }

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(3px);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}
.modal-content {
  width: 400px;
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.modal-content h3 { 
  margin-top: 0;
  text-align: center;
 }
.modal-content label {
  display: block;
  margin: 8px 0 4px;
}
.modal-content input,
.modal-content select {
  width: 100%;
  padding: 6px;
  border-radius: 4px;
  border: 1px solid #aaa;
  margin-bottom: 10px;
}
.modal-buttons { 
  display: flex;
  justify-content: space-between;
  gap:10px;
  margin-top: 18px;
}
.btn-save{
  flex:1;
  background:#4ade80;
  color:#064e3b;
  border:none;
  padding:10px 0;
  border-radius:6px;
  font-weight:600;
  cursor:pointer;
  transition:background .2s,transform .1s;
}
.btn-cancel{
  flex:1;
  background:#9ca3af;
  color:white;
  border:none;
  padding:10px 0;
  border-radius:6px;
  font-weight:600;
  cursor:pointer;
  transition:background .2s,transform .1s;
}
.status { 
  display:flex; 
  justify-content:space-around; 
  margin-bottom:10px; 
}

.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
.card { background-color: var(--card); padding: 20px; border-radius: 12px; box-shadow: 0 6px 15px rgba(0,0,0,0.08); transition: transform 0.3s; border-left: 5px solid var(--accent-green); }
.card:hover { transform: translateY(-5px); }
.card h3 { font-size: 16px; color: #6b7280; font-weight: 500; }
.card p { font-size: 32px; font-weight: 800; margin-top: 8px; color: #065f46; }
</style>
</head>
<body>

<!-- HEADER -->
<header class="bg-green-200 text-green-900 p-4 shadow-md flex items-center">
    <div class="menu-icon" id="menuBtn"><i class="fas fa-bars"></i></div>
    <h1><i class="fas fa-calendar-alt text-green-700"></i> Schedule Management</h1>
</header>

<div class="sidebar" id="sidebar">
    <h2 class="text-green-400"><i class="fas fa-grip-vertical"></i> Menu</h2>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
    <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
    <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
    <a href="schedule_management.php" class="bg-green-600 rounded"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
    <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
</div>

<div class="overlay" id="overlay"></div>

<!-- MAIN CONTENT -->
<main>
  <h2 class="text-2xl font-bold mb-6 text-gray-800">Schedules Overview</h2>
    <section class="cards">
        <div class="card"><h3>Total Schedules</h3><p id="totalSchedules">0</p></div>
        <div class="card"><h3>Upcoming</h3><p id="upcomingSchedules">0</p></div>
        <div class="card"><h3>Completed</h3><p id="completedSchedules">0</p></div>
    </section>
  <!-- TABLE -->
  <button class="btn btn-add" onclick="showForm()">+ Add Schedule</button>
  <table>
    <thead>
      <tr>
        <th class="bg-green-200">ID</th>
        <th class="bg-green-200">Type</th>
        <th class="bg-green-200">Vehicle</th>
        <th class="bg-green-200">Origin</th>
        <th class="bg-green-200">Destination</th>
        <th class="bg-green-200">Date</th>
        <th class="bg-green-200">Departure</th>
        <th class="bg-green-200">Arrival</th>
        <th class="bg-green-200">Status</th>
        <th class="bg-green-200">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['ScheduleID'] ?></td>
        <td><?= htmlspecialchars($row['TypeName']) ?></td>
        <td><?= htmlspecialchars($row['PlateNo']) ?></td>
        <td><?= htmlspecialchars($row['StartLocation']) ?></td>
        <td><?= htmlspecialchars($row['EndLocation']) ?></td>
        <td><?= htmlspecialchars($row['Date']) ?></td>
        <td><?= htmlspecialchars($row['DepartureTime']) ?></td>
        <td><?= htmlspecialchars($row['ArrivalTime']) ?></td>
        <td><?= htmlspecialchars($row['Status']) ?></td>
        <td>
          <button class="btn btn-edit"
            onclick="editSchedule(<?= $row['ScheduleID'] ?>, '<?= $row['Date'] ?>', '<?= $row['DepartureTime'] ?>', '<?= $row['ArrivalTime'] ?>', '<?= $row['Status'] ?>')">
            Edit
          </button>
          <button class="btn btn-delete" onclick="deleteSchedule(<?= $row['ScheduleID'] ?>)">Remove</button>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</main>

<!-- FORM MODAL -->
<div id="formOverlay" class="modal-overlay">
  <div class="modal-content">
    <h3 id="formTitle">Add Schedule</h3>
    <form method="POST" id="scheduleForm">
      <input type="hidden" name="id" id="editId">

      <!-- Vehicle Type -->
      <div class="form-group">
        <label for="vehicleType">Vehicle Type</label>
        <select id="vehicleType" name="vehicleType" required>
          <option value=""> Select Vehicle Type </option>
          <option value="1">Bus</option>
          <option value="2">E-Jeep</option>
        </select>
      </div>

      <!-- Vehicle (filtered by type) -->
      <div class="form-group">
        <label for="vehicle">Vehicle</label>
        <select id="vehicle" name="vehicle_id" required>
          <option value=""> Select Vehicle </option>
          <?php while ($v = $vehicles->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($v['VehicleID']) ?>" data-type="<?= htmlspecialchars($v['TypeID']) ?>">
              <?= htmlspecialchars($v['PlateNo']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Route -->
      <div class="form-group">
        <label for="route">Route</label>
        <select id="route" name="route_id" required>
          <option value="">Select Route </option>
          <?php while ($r = $routes->fetch_assoc()): ?>
            <option 
              value="<?= htmlspecialchars($r['RouteID']) ?>" 
              data-type="<?= htmlspecialchars($r['TypeID']) ?>">
              <?= htmlspecialchars($r['Origin'] . ' → ' . $r['Destination']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Day of Week (instead of specific date) -->
      <div class="form-group">
        <label for="dayOfWeek">Day</label>
        <select id="dayOfWeek" name="date" required>
          <option value="">Select Day</option>
          <option value="Monday">Monday</option>
          <option value="Tuesday">Tuesday</option>
          <option value="Wednesday">Wednesday</option>
          <option value="Thursday">Thursday</option>
          <option value="Friday">Friday</option>
          <option value="Saturday">Saturday</option>
          <option value="Sunday">Sunday</option>
        </select>
      </div>

      <!-- Departure Time -->
        <div class="form-group">
          <label for="departure">Departure Time</label>
          <input type="time" id="departure" name="departure_time" required>
        </div>

        <!-- Arrival Time -->
        <div class="form-group">
          <label for="arrival">Arrival Time</label>
          <input type="time" id="arrival" name="arrival_time" required>
        </div>

        <!-- Status -->
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
          </div>

      <div class="modal-buttons">
        <button type="submit" class="btn-save">Save</button>
        <button type="button" class="btn-cancel" onclick="hideForm()">Close</button>
      </div>
    </form>
  </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function () {
  const typeSelect = document.getElementById('vehicleType');
  const vehicleSelect = document.getElementById('vehicle');
  const routeSelect = document.getElementById('route');

  // Clone the full list once, so you don't lose them when filtering repeatedly
  const allVehicles = Array.from(vehicleSelect.querySelectorAll('option')).map(opt => opt.cloneNode(true));
  const allRoutes = Array.from(routeSelect.querySelectorAll('option')).map(opt => opt.cloneNode(true));

  typeSelect.addEventListener('change', function () {
    const selectedType = this.value;

    // Filter vehicles
    vehicleSelect.innerHTML = '<option value=""> Select Vehicle </option>';
    allVehicles.forEach(opt => {
      const type = opt.getAttribute('data-type');
      if (!selectedType || selectedType === type) {
        vehicleSelect.appendChild(opt.cloneNode(true));
      }
    });

    // Filter routes
    routeSelect.innerHTML = '<option value=""> Select Route </option>';
    allRoutes.forEach(opt => {
      const type = opt.getAttribute('data-type');
      if (!selectedType || selectedType === type) {
        routeSelect.appendChild(opt.cloneNode(true));
      }
    });
  });

  // Run initial filter to reflect the default selection (if any)
  if (typeSelect.value) {
    typeSelect.dispatchEvent(new Event('change'));
  }
});

// SIDEBAR
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menuBtn');
const overlay = document.getElementById('overlay');
menuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('open');
  overlay.classList.toggle('show');
});
overlay.addEventListener('click', () => {
  sidebar.classList.remove('open');
  overlay.classList.remove('show');
});

// Modal & form elements
const formOverlay = document.getElementById('formOverlay');
const formEl = document.getElementById('scheduleForm');
const formTitle = document.getElementById('formTitle');
const hiddenId = document.getElementById('editId');

function showForm(edit = false) {
  // reset fields
  formEl.reset();
  hiddenId.value = '';
  formTitle.innerText = edit ? 'Edit Schedule' : 'Add Schedule';
  formOverlay.style.display = 'flex';
}

function hideForm() {
  formOverlay.style.display = 'none';
  formEl.reset();
  hiddenId.value = '';
}

function editSchedule(id, date, dep, arr, status) {
  showForm(true);
  hiddenId.value = id;
  // set date/day select (we use id="date" on the select)
  const dateEl = document.getElementById('date');
  if (dateEl) dateEl.value = date || '';
  document.getElementById('departure').value = dep || '';
  document.getElementById('arrival').value = arr || '';
  // status is a select in the form
  const statusEl = document.getElementById('status');
  if (statusEl) statusEl.value = status || 'Active';

  // If the schedule's vehicle/route and type need to be selected
  // you may want to fetch the schedule details via AJAX instead of relying on passed args.
}

// Submit handler — use FormData so PHP receives $_POST fields as usual
formEl.addEventListener('submit', async (e) => {
  e.preventDefault();

  const idVal = (hiddenId.value || '').trim();
  const isEdit = idVal.length > 0;

  // Build FormData (exactly like a normal form submit)
  const fd = new FormData(formEl);
  if (isEdit) fd.append('id', idVal);

  const url = isEdit ? 'update_schedule.php' : 'add_schedules.php';

  try {
    const res = await fetch(url, {
      method: 'POST',
      body: fd // no content-type header — browser sets multipart/form-data automatically
    });

    // try parse JSON response
    const resultText = await res.text();
    let result;
    try {
      result = JSON.parse(resultText);
    } catch (err) {
      console.error('Server returned non-JSON response:', resultText);
      alert('Server error — check server logs/console. Response was not JSON.');
      return;
    }

    if (result.success) {
      alert(isEdit ? 'Schedule updated successfully.' : 'Schedule added successfully.');
      hideForm();
      location.reload();
    } else {
      alert(result.error || 'Operation failed.');
    }
  } catch (err) {
    console.error('Network or fetch error:', err);
    alert('Could not connect to server. Check console for details.');
  }
});

async function deleteSchedule(id) {
  if (!confirm('Delete this schedule?')) return;
  try {
    const res = await fetch('delete_schedule.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if (result.success) {
      alert('Schedule deleted.');
      location.reload();
    } else {
      alert(result.error || 'Delete failed.');
    }
  } catch (err) {
    console.error('Delete error:', err);
    alert('Delete failed — check console.');
  }
}

</script>
</body>
</html>
