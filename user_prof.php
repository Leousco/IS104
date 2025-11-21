<?php
// Start the session and include the database connection
session_start();
// Ensure 'db_connection.php' is available and correctly configured
include 'db_connection.php';

// 1. CHECK FOR LOGGED-IN USER
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['UserID'];
$message = ""; 
$is_error = false;

// Initialize variables
$currentBalance = '0.00'; 
$loyaltyLevel = 'Not Verified'; 
$email = ''; 
$fullName = '';
$userFound = false; 
$profile_picture_url = 'https://cdn-icons-png.flaticon.com/512/847/847969.png'; // Default avatar

// 2. UNIVERSAL POST HANDLER
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- HANDLE NAME UPDATE ---
    if (isset($_POST['save_profile'])) {
        $fullName = trim($_POST['name']);
        
        // Split full name into first and last name
        $nameParts = array_pad(explode(' ', $fullName, 2), 2, null);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1];

        if (!empty($firstName)) {
            $stmt = $conn->prepare("UPDATE users SET FirstName = ?, LastName = ? WHERE UserID = ?");
            $stmt->bind_param("ssi", $firstName, $lastName, $user_id);
            
            if ($stmt->execute()) {
                $message = "Profile name saved successfully!";
            } else {
                $message = "Error updating profile name: " . $conn->error;
                $is_error = true;
            }
            $stmt->close();
        } else {
            $message = "Name cannot be empty.";
            $is_error = true;
        }
    }
    
    // --- HANDLE PROFILE PICTURE UPDATE (via AJAX/Fetch) ---
    // --- HANDLE PROFILE PICTURE UPDATE (via AJAX/Fetch) ---
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile_picture') {
      header('Content-Type: application/json');
      
      $new_url = $_POST['url'] ?? '';
      
      // Validate that URL is not empty
      if (empty($new_url)) {
          echo json_encode(['success' => false, 'message' => 'No image data provided.']);
          exit;
      }
      
      // Validate Base64 format or URL
      $isValidBase64 = (strpos($new_url, 'data:image') === 0);
      $isValidURL = filter_var($new_url, FILTER_VALIDATE_URL);
      
      if (!$isValidBase64 && !$isValidURL) {
          echo json_encode(['success' => false, 'message' => 'Invalid image format.']);
          exit;
      }
      
      try {
          // Check if column exists, if not create it
          $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'ProfilePictureURL'");
          
          if ($check_column->num_rows == 0) {
              // Column doesn't exist, create it
              $conn->query("ALTER TABLE users ADD COLUMN ProfilePictureURL TEXT NULL");
          }
          
          // Update the profile picture URL
          $stmt = $conn->prepare("UPDATE users SET ProfilePictureURL = ? WHERE UserID = ?");
          $stmt->bind_param("si", $new_url, $user_id);
          
          if ($stmt->execute()) {
              // Update session variable for immediate reflection on other pages
              $_SESSION['ProfilePictureURL'] = $new_url;
              
              echo json_encode([
                  'success' => true, 
                  'message' => 'Profile picture updated successfully!'
              ]);
          } else {
              echo json_encode([
                  'success' => false, 
                  'message' => 'Database error: ' . $conn->error
              ]);
          }
          $stmt->close();
          
      } catch (Exception $e) {
          echo json_encode([
              'success' => false, 
              'message' => 'Error: ' . $e->getMessage()
          ]);
      }
      
      exit; // Exit after handling AJAX
    }

    // --- HANDLE PASSWORD CHANGE (via AJAX/Fetch) ---
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        header('Content-Type: application/json');
        
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $db_hashed_password = null;
        
        // 1. Fetch current hashed password from database
        $stmt_fetch = $conn->prepare("SELECT Password FROM users WHERE UserID = ?");
        $stmt_fetch->bind_param("i", $user_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        
        if ($result_fetch->num_rows === 1) {
            $db_hashed_password = $result_fetch->fetch_assoc()['Password'];
        }
        $stmt_fetch->close();

        // 2. Verify current password
        if (empty($db_hashed_password) || !password_verify($currentPass, $db_hashed_password)) {
            echo json_encode(['success' => false, 'message' => 'The current password you entered is incorrect.']);
            exit;
        }
        
        // 3. Hash the new password and update
        $new_hashed_password = password_hash($newPass, PASSWORD_DEFAULT);
        
        $stmt_update = $conn->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
        $stmt_update->bind_param("si", $new_hashed_password, $user_id);
        
        if ($stmt_update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error while updating password: ' . $conn->error]);
        }
        $stmt_update->close();
        exit; // Exit after handling AJAX
    }
}

// 3. FETCH CURRENT USER DATA (Reload data after POST or on initial load)
$sql = "
    SELECT 
        FirstName, 
        LastName, 
        Email, 
        Balance, 
        HasDiscount,
        ProfilePictureURL 
    FROM 
        users 
    WHERE 
        UserID = ?
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('FATAL ERROR: SQL Prepare Failed for Users Table. Check database configuration.'); 
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $fullName = $user['FirstName'] . ' ' . $user['LastName'];
    $email = $user['Email'];
    
    // Set Balance
    $currentBalance = number_format($user['Balance'] ?? 0, 2);
    
    // Set Loyalty/Discount
    $hasDiscount = $user['HasDiscount'] ?? 0;
    $loyaltyLevel = ($hasDiscount == 1) ? 'User is Verified (Discount Approved)' : 'Not Verified';

    // Set Profile Picture URL, using default if NULL or empty
    if (!empty($user['ProfilePictureURL'])) {
        $profile_picture_url = $user['ProfilePictureURL'];
    }

    $userFound = true; 
} else {
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();

// 4. FETCH ALL BOOKED TICKETS (using the fixed query from previous step)
$active_tickets = [];

$tickets_sql = "
    SELECT 
        t.TicketID,
        r.StartLocation AS Origin,
        r.EndLocation AS Destination,
        s.DayOfWeek,
        s.DepartureTime,
        s.ArrivalTime
    FROM 
        ticket t
    JOIN 
        schedules s ON t.ScheduleID = s.ScheduleID
    JOIN 
        route r ON s.RouteID = r.RouteID
    WHERE 
        t.PassengerID = ?
    ORDER BY
        s.DayOfWeek ASC, s.DepartureTime ASC
";

$tickets_stmt = $conn->prepare($tickets_sql);

if ($tickets_stmt) {
    $tickets_stmt->bind_param("i", $user_id);
    $tickets_stmt->execute();
    $tickets_result = $tickets_stmt->get_result();
    
    while ($row = $tickets_result->fetch_assoc()) {
        $active_tickets[] = $row;
    }
    
    $tickets_stmt->close();
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Profile</title>
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    /* ---------------------------------
       CSS from userprof.html (Green/Tan Theme) 
    --------------------------------- */
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
    body, html{height:100%;width:100%;}
    body{
      display:flex;justify-content:center;align-items:flex-start;
      background:#d9eab1; /* Light Tan/Green Background */
      padding:20px;
    }

            body::-webkit-scrollbar {
          display: none; /* Hides the scrollbar */
          width: 0; /* Ensures no width space is reserved for the scrollbar */
        }
    /* HEADER */
    header{
      position:fixed;
      top:0; left:0;
      width:100%; height:60px;
      background:linear-gradient(90deg, #2e7d32, #66bb6a);
      color:white;
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:15px 20px;;
      z-index:100;
      box-shadow:0 2px 8px rgba(0,0,0,.15);
    }
    header .logo{
      font-size:20px;
      font-weight:600;
    }
    header .header-right{
      display:flex;
      align-items:center;
      gap:15px;
    }
    header .header-right img{
      width:35px;
      height:35px;
      border-radius:50%;
      object-fit:cover;
      cursor:pointer;
    }
    header .header-right .fa-bars{
      font-size:22px;
      cursor:pointer;
      display:none;
    }

    .container{
      display:flex;gap:20px;
      width:100%;max-width:1400px;
      height:100%;
      margin-top:60px; /* push content below header */
    }
    .sidebar{
      flex:0 0 250px;
      background:white;border-radius:10px;overflow:hidden;
      display:flex;flex-direction:column;justify-content:space-between;
      padding:20px 0;box-shadow:0 4px 12px rgba(0,0,0,.1);
    }
    .profile{text-align:center;padding:0 20px 10px;}
    .avatar{position:relative;width:90px;height:90px;margin:0 auto 10px;}
    .avatar img{
      width:90px;height:90px;border-radius:50%;object-fit:cover;
      border:3px solid #eee;
    }
    .avatar button{
      position:absolute;bottom:0;right:0;background:#4b9a39;color:white;
      border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;
      display:flex;align-items:center;justify-content:center;font-size:14px;
      transition:0.3s;
    }
    .avatar button:hover{background:#39842a}
    .menu{padding:0 20px;flex:1;overflow-y:auto;}
    .menu a{
      display:flex;align-items:center;gap:10px;padding:10px;
      color:#333;text-decoration:none;border-radius:6px;transition:.3s;
    }
    .menu a:hover{background:#e8f3db}
    .menu a.active{background:#e8f3db;font-weight:600}
    .logout{
      border-top:1px solid #eee;padding:10px 20px;
    }
    .logout a{text-decoration:none;color:#333;display:flex;align-items:center;gap:8px;cursor:pointer;}
    .content{
      flex:1;
      background:white;border-radius:12px;
      padding:25px 30px;box-shadow:0 4px 12px rgba(0,0,0,.1);
      overflow-y:auto;
    }
    .content h2{margin-bottom:20px;font-size:20px}
    /* Profile Info Adjustments for Form */
    .profile-info{margin-bottom:15px;position:relative;}
    .profile-info label{display:block;color:#555;margin-bottom:5px;font-size:14px;width:100%;}
    .profile-info input{
      width:100%;padding:10px 8px;border:1px solid #ccc;border-radius:6px;background:#f8f8f8;
      font-size: 15px; /* Added for clarity */
    }
    .profile-info input[readonly]{
        background:#e8e8e8; /* Slightly darker for non-editable fields */
    }
    /* Additional style for button visibility */
    .profile-info.button-row {
        margin-top: 25px;
        text-align: right;
    }
    .toggle-eye{
      position:absolute;right:10px;cursor:pointer;color:#666;font-size:14px;
      transition:0.3s;
      top:60%; transform:translateY(-50%);
    }
    .change-pass-link{
      display:inline-block;margin-top:8px;color:#0077cc;cursor:pointer;font-size:14px;
    }
    .change-pass-link:hover{text-decoration:underline}
    .tickets h3{margin:20px 0 10px}
    .ticket{
      border:1px solid #eee;border-radius:10px;padding:12px 14px;margin-bottom:10px;
      display:flex;justify-content:space-between;align-items:center;
    }
    .ticket span.status{
      padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;
    }
    .status.open{background:#e0f0ff;color:#0077cc}
    .status.resolved{background:#e0f6e0;color:#2e8b57}
    .status.pending{background:#fff4d9;color:#b88a00}
    .status.active{background:#e0f6e0;color:#2e8b57} /* Used for Active Tickets */

    /* Modal */
    .modal{
      position:fixed;top:0;left:0;width:100%;height:100%;
      background:rgba(0,0,0,.3);backdrop-filter:blur(2px);
      display:flex;align-items:center;justify-content:center;z-index:1000;
      opacity:0;
      pointer-events:none;
      transition:opacity 0.3s ease;
    }
    .modal.active{
      opacity:1;
      pointer-events:auto;
    }
    .modal-content{
      background:rgba(255,255,255,.9);backdrop-filter:blur(2px);
      padding:25px 30px;border-radius:15px;width:320px;
      box-shadow:0 6px 16px rgba(0,0,0,.2);
      transform:scale(0.95);
      opacity:0;
      transition:all 0.25s ease;
      position:relative;
    }
    .modal.active .modal-content{
      transform:scale(1);
      opacity:1;
    }
    .modal-content h3{text-align:center;margin-bottom:20px;}
    .modal-content button{
      width:100%;padding:10px;border:none;border-radius:8px;margin-bottom:8px;
      cursor:pointer;transition:.3s;
    }
    .btn-primary{background:#4b9a39;color:#fff}
    .btn-danger{background:#cc3333;color:#fff}
    .close-btn{position:absolute;top:15px;right:20px;cursor:pointer;font-size:18px}

    /* Change Password */
    .modal-content input{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;margin-bottom:10px;}
    .strength{font-size:13px;margin-bottom:10px;font-weight:600}
    .password-requirements ul{list-style:none;padding-left:0;margin-bottom:10px;font-size:13px;}
    .loading-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255, 255, 255, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
    }
    /* Loader Spinner */
    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #4b9a39;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* MOBILE RESPONSIVE */
    @media (max-width:900px){
      header .header-right .fa-bars{display:block;}
      .sidebar{position:fixed;top:60px;left:0;height:calc(100vh - 60px);z-index:99;transition:transform 0.3s ease-in-out;}
      .sidebar.hidden{transform:translateX(-260px);}
      .container{margin-left:0;flex-direction:column;}
    }

.logout a {
    padding: 6px 8px;
    border-radius: 6px;
    transition: background-color 0.2s ease, transform 0.15s ease;
}

.logout a:hover {
    background-color: rgba(0, 0, 0, 0.08);
    transform: translateX(3px);
}

  </style>
</head>
<body>
  <header>
    <div class="logo">User Dashboard</div>
    <div class="header-right">
      <img id="headerUserImg" src="<?php echo htmlspecialchars($profile_picture_url); ?>" alt="user">
      <i class="fa fa-bars" id="hamburgerBtn"></i>
    </div>
  </header>

  <div class="container">
    <div class="sidebar">
      <div>
        <div class="profile">
          <div class="avatar">
            <img id="profileImg" src="<?php echo htmlspecialchars($profile_picture_url); ?>" alt="avatar">
            <button id="photoBtn"><i class="fa fa-plus"></i></button>
          </div>
          <h4><?php echo htmlspecialchars($fullName); ?></h4>
          <p style="font-size:13px;color:#666;"><?php echo htmlspecialchars($email); ?></p>
        </div>
        <div class="menu">
          <a href="#" class="active"><i class="fa fa-user"></i> My Profile</a>
          <a href="passenger_dashboard.php"><i class="fa fa-home"></i> Homepage</a>
          <a href="vehicle.php"><i class="fa fa-bus"></i> Vehicles</a>
          <a href="ticketing/ticketing.php"><i class="fa fa-ticket"></i> Buy Ticket</a>
          <a href="buyCoin/buy_coins.php"><i class="fa fa-coins"></i> Buy Coins</a>
          <a href="Feedback.php"><i class="fa fa-comment"></i> Feedback</a>
          <a href="about.php"><i class="fa fa-info-circle"></i> About Us</a>
        </div>
      </div>

    <div class="logout" style="display: flex; flex-direction: column; gap: 10px; padding: 10px 20px;">

        <a id="logoutBtn" style="text-decoration:none;color:#333;display:flex;align-items:center;gap:8px;cursor:pointer;">
            <i class="fa fa-sign-out-alt"></i> Log Out
        </a>
    </div>

    </div>

    <div class="content">
      <form method="POST" action="user_prof.php">
        <h2>Profile Detail</h2>
        
        <div class="profile-info">
          <label>Name (Editable)</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($fullName); ?>">
        </div>
        
        <div class="profile-info">
          <label>Email</label>
          <input type="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
        </div>
        
        <div class="profile-info">
          <label>Discount Info</label>
          <input type="text" value="<?php echo htmlspecialchars($loyaltyLevel); ?>" readonly>
        </div>

        <div class="profile-info">
            <label>Current Balance</label>
            <input type="text" value="₱ <?php echo htmlspecialchars($currentBalance); ?>" readonly>
        </div>
        
        <div class="profile-info button-row">
            <button type="submit" name="save_profile" class="btn-primary" style="width: 150px;">Save Name</button>
        </div>
        
      </form>
      
      <span class="change-pass-link" id="changePassLink">Change Password</span>

      <div class="tickets">
        <h3>Active Tickets</h3>
        <?php if (!empty($active_tickets)): ?>
            <?php foreach ($active_tickets as $ticket): ?>
                <div class="ticket">
                    <span>
                        <?php echo htmlspecialchars($ticket['Origin']) . ' &rarr; ' . htmlspecialchars($ticket['Destination']); ?><br>
                        <small>Departure: <strong><?php echo htmlspecialchars($ticket['DayOfWeek']); ?></strong> at <?php echo htmlspecialchars(date('g:i A', strtotime($ticket['DepartureTime']))); ?></small>
                    </span>
                    <span class="status active">Active</span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
             <div class="ticket"><span>No active tickets found for future travel.</span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="modal" id="photoModal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('photoModal')">&times;</span>
      <div class="loading-overlay"><div class="loader"></div></div>
      <h3>Profile Photo</h3>
      <button class="btn-primary" onclick="choosePhoto()">Choose from Library</button>
      <button class="btn-primary" onclick="takePhoto()">Take a Photo</button>
      <button class="btn-danger" id="removePhotoBtn" onclick="removePhoto()">Remove Existing Photo</button>
      <input type="file" id="fileInput" accept="image/*" style="display:none">
    </div>
  </div>

  <div class="modal" id="passModal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('passModal')">&times;</span>
      <div class="loading-overlay" id="passLoader"><div class="loader"></div></div>
      <h3>Change Password</h3>
      <div class="profile-info">
        <input type="password" id="currentPass" placeholder="Current Password">
        <i class="fa fa-eye toggle-eye" onclick="toggleEye('currentPass', this)"></i>
      </div>
      <div class="profile-info">
        <input type="password" id="newPass" placeholder="New Password" oninput="checkStrength()">
        <i class="fa fa-eye toggle-eye" onclick="toggleEye('newPass', this)"></i>
      </div>
      <div class="profile-info">
        <input type="password" id="confirmPass" placeholder="Confirm Password">
        <i class="fa fa-eye toggle-eye" onclick="toggleEye('confirmPass', this)"></i>
      </div>

      <div class="password-requirements">
        <ul>
          <li id="reqLength" style="color:red">• At least 8 characters</li>
          <li id="reqUpper" style="color:red">• At least one uppercase letter</li>
          <li id="reqNumber" style="color:red">• At least one number</li>
          <li id="reqSpecial" style="color:red">• At least one special character</li>
        </ul>
      </div>
      <div class="strength" id="strengthText">Strength: —</div>
      <button class="btn-primary" onclick="savePass()">Save Password</button>
    </div>
  </div>

  <div class="modal" id="logoutModal">
    <div class="modal-content">
      <h3>Are you sure you want to log out?</h3>
      <button class="btn-primary" onclick="confirmLogout()">Yes</button>
      <button class="btn-danger" onclick="closeModal('logoutModal')">Cancel</button>
    </div>
  </div>
  
  <div class="modal" id="phpAlertModal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('phpAlertModal')">&times;</span>
      <h3 id="alert-title" style="color: #4b9a39;"><i class="fa fa-check-circle"></i> Success</h3>
      <p id="alert-message" style="margin-bottom: 20px; text-align: center;"></p>
      <button class="btn-primary" onclick="closeModal('phpAlertModal')">OK</button>
    </div>
  </div>


  <script>
    // --- Global Variables ---
    const DEFAULT_AVATAR = 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
    
    // --- Element Selectors ---
    const photoBtn=document.getElementById("photoBtn"),
          photoModal=document.getElementById("photoModal"),
          fileInput=document.getElementById("fileInput"),
          profileImg=document.getElementById("profileImg"),
          headerUserImg=document.getElementById("headerUserImg"),
          removePhotoBtn=document.getElementById("removePhotoBtn"),
          changePassLink=document.getElementById("changePassLink"),
          passModal=document.getElementById("passModal"),
          logoutBtn=document.getElementById("logoutBtn"),
          logoutModal=document.getElementById("logoutModal"),
          hamburgerBtn=document.getElementById("hamburgerBtn"),
          sidebar=document.querySelector(".sidebar"),
          phpAlertModal = document.getElementById('phpAlertModal'),
          alertTitle = document.getElementById('alert-title'),
          alertMessage = document.getElementById('alert-message');

    // --- Utility Functions ---
    function openModal(id){
        const modal = document.getElementById(id);
        if(modal) modal.classList.add("active");
    }

    function closeModal(id){
      const modal=document.getElementById(id);
      if(modal) modal.classList.remove("active");
    }

    function showLoader(modalId, show) {
        const modal = document.getElementById(modalId);
        const loader = modal ? modal.querySelector('.loading-overlay') : null;
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    function showAlert(message, isError = false) {
        alertMessage.textContent = message;
        if (isError) {
            alertTitle.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Error';
            alertTitle.style.color = '#cc3333';
        } else {
            alertTitle.innerHTML = '<i class="fa fa-check-circle"></i> Success';
            alertTitle.style.color = '#4b9a39';
        }
        openModal('phpAlertModal');
    }

    function toggleEye(inputId,icon){
      const input=document.getElementById(inputId);
      if(input.type==="password"){
        input.type="text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type="password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }

    // --- Event Listeners ---
    photoBtn.onclick = () => {
        openModal("photoModal");
        // Hide remove button if default image is used
        removePhotoBtn.style.display = (profileImg.src.includes(DEFAULT_AVATAR)) ? "none" : "flex";
    }
    changePassLink.onclick=()=>{openModal("passModal");}
    logoutBtn.onclick=()=>{openModal("logoutModal");}
    hamburgerBtn.onclick = ()=>{sidebar.classList.toggle("hidden");}
    


// --- Profile Picture Functions (Improved Version) ---
async function updateProfilePicture(url) {
    console.log('Updating profile picture...');
    showLoader('photoModal', true);
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_profile_picture');
        formData.append('url', url);

        const response = await fetch('user_prof.php', {
            method: 'POST',
            body: formData,
        });

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Server response:', result);
        
        if (result.success) {
            // Update images on success
            profileImg.src = url;
            headerUserImg.src = url;
            showAlert(result.message);
        } else {
            showAlert(result.message || 'Failed to update profile picture.', true);
        }
    } catch (e) {
        console.error('Profile picture update error:', e);
        showAlert("Failed to connect to the server: " + e.message, true);
    } finally {
        showLoader('photoModal', false);
        closeModal("photoModal");
    }
}

function choosePhoto() {
    fileInput.click();
}

fileInput.addEventListener("change", e => {
    const file = e.target.files[0];
    
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        showAlert('Please select a valid image file.', true);
        return;
    }
    
    // Validate file size (max 2MB)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        showAlert('Image size must be less than 2MB.', true);
        return;
    }
    
    const reader = new FileReader();
    
    reader.onload = function(evt) {
        // Compress and resize the image before saving
        compressImage(evt.target.result, (compressedBase64) => {
            updateProfilePicture(compressedBase64);
        });
    };
    
    reader.onerror = function() {
        showAlert('Failed to read the image file.', true);
    };
    
    reader.readAsDataURL(file);
});

// Image compression function to reduce database size
function compressImage(base64, callback) {
    const img = new Image();
    img.onload = function() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Set maximum dimensions
        const MAX_WIDTH = 400;
        const MAX_HEIGHT = 400;
        
        let width = img.width;
        let height = img.height;
        
        // Calculate new dimensions while maintaining aspect ratio
        if (width > height) {
            if (width > MAX_WIDTH) {
                height *= MAX_WIDTH / width;
                width = MAX_WIDTH;
            }
        } else {
            if (height > MAX_HEIGHT) {
                width *= MAX_HEIGHT / height;
                height = MAX_HEIGHT;
            }
        }
        
        canvas.width = width;
        canvas.height = height;
        
        // Draw and compress
        ctx.drawImage(img, 0, 0, width, height);
        
        // Convert to base64 with compression (0.7 quality = 70%)
        const compressedBase64 = canvas.toDataURL('image/jpeg', 0.7);
        
        callback(compressedBase64);
    };
    
    img.onerror = function() {
        showAlert('Failed to process the image.', true);
    };
    
    img.src = base64;
}

// Actual camera capture function
function takePhoto() {
    // Check if browser supports camera
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showAlert('Camera access is not supported in this browser.', true);
        return;
    }
    
    // Create a video element for camera preview
    const video = document.createElement('video');
    video.autoplay = true;
    video.style.maxWidth = '100%';
    video.style.marginBottom = '10px';
    
    const captureBtn = document.createElement('button');
    captureBtn.textContent = 'Capture Photo';
    captureBtn.className = 'btn-primary';
    captureBtn.style.marginBottom = '10px';
    
    const modalContent = photoModal.querySelector('.modal-content');
    
    // Clear modal and add camera view
    const originalContent = modalContent.innerHTML;
    modalContent.innerHTML = '';
    modalContent.appendChild(video);
    modalContent.appendChild(captureBtn);
    
    const backBtn = document.createElement('button');
    backBtn.textContent = 'Cancel';
    backBtn.className = 'btn-danger';
    modalContent.appendChild(backBtn);
    
    // Request camera access
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => {
            video.srcObject = stream;
            
            captureBtn.onclick = () => {
                // Create canvas and capture frame
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0);
                
                // Stop camera
                stream.getTracks().forEach(track => track.stop());
                
                // Compress and save
                compressImage(canvas.toDataURL('image/jpeg'), (compressed) => {
                    modalContent.innerHTML = originalContent;
                    updateProfilePicture(compressed);
                });
            };
            
            backBtn.onclick = () => {
                stream.getTracks().forEach(track => track.stop());
                modalContent.innerHTML = originalContent;
            };
        })
        .catch(err => {
            console.error('Camera error:', err);
            showAlert('Failed to access camera: ' + err.message, true);
            modalContent.innerHTML = originalContent;
        });
}

function removePhoto() {
    if (confirm('Are you sure you want to remove your profile picture?')) {
        updateProfilePicture(DEFAULT_AVATAR);
    }
}



    // --- Password Functions (Server communication added) ---
    function checkStrength(){
      const val = document.getElementById("newPass").value;
      const reqLength = document.getElementById("reqLength");
      const reqUpper = document.getElementById("reqUpper");
      const reqNumber = document.getElementById("reqNumber");
      const reqSpecial = document.getElementById("reqSpecial");
      const strengthText = document.getElementById("strengthText");
      let score = 0;
      if(val.length>=8){reqLength.style.color="green";score++;}else{reqLength.style.color="red";}
      if(/[A-Z]/.test(val)){reqUpper.style.color="green";score++;}else{reqUpper.style.color="red";}
      if(/[0-9]/.test(val)){reqNumber.style.color="green";score++;}else{reqNumber.style.color="red";}
      if(/[^A-Za-z0-9]/.test(val)){reqSpecial.style.color="green";score++;}else{reqSpecial.style.color="red";}
      
      const isStrong = (score === 4);
      if(score<=2){strengthText.textContent="Strength: Weak";strengthText.style.color="red";}
      else if(score===3){strengthText.textContent="Strength: Moderate";strengthText.style.color="orange";}
      else{strengthText.textContent="Strength: Strong";strengthText.style.color="green";}
      return isStrong;
    }

    async function savePass(){
      const currentP = document.getElementById("currentPass").value;
      const newP = document.getElementById("newPass").value;
      const conf = document.getElementById("confirmPass").value;
      
      if (currentP.trim() === '') {
        showAlert("Please enter your current password.", true);
        return;
      }
      
      if (newP !== conf) {
        showAlert("New passwords do not match.", true);
        return;
      }
      
      if (newP === currentP) {
        showAlert("New password cannot be the same as the current one.", true);
        return;
      }

      if (!checkStrength()) {
        showAlert("New password does not meet all strength requirements.", true);
        return;
      }

      showLoader('passModal', true);

      try {
        const formData = new FormData();
        formData.append('action', 'change_password');
        formData.append('current_password', currentP);
        formData.append('new_password', newP);

        const response = await fetch('user_prof.php', {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message);
            // Clear inputs on success
            document.getElementById("currentPass").value = '';
            document.getElementById("newPass").value = '';
            document.getElementById("confirmPass").value = '';
            checkStrength(); // Reset strength text
        } else {
            showAlert(result.message, true);
        }

      } catch (e) {
        showAlert("Failed to connect to the server for password change.", true);
      } finally {
        showLoader('passModal', false);
        closeModal("passModal");
      }
    }

    function confirmLogout(){
      closeModal('logoutModal');
      // Redirect to actual logout script
      window.location.href="login.php"; 
    }
    
    // --- Initial PHP Message Handling ---
    <?php if (!empty($message)): ?>
    document.addEventListener('DOMContentLoaded', () => {
        showAlert('<?php echo addslashes($message); ?>', <?php echo $is_error ? 'true' : 'false'; ?>);
    });
    <?php endif; ?>
    
  </script>
</body>
</html>