<?php
require_once '../config.php';
include("../auth.php");

$loginPage = "/SADPROJ/login.php";

// Access control
if (!isset($_SESSION['UserID'])) {
    header("Location: $loginPage");
    exit();
}

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "PASSENGER") {
    header("Location: $loginPage?error=unauthorized");
    exit();
}

$user_id = $_SESSION['UserID'];

// Fetch latest discount application status
$discountQuery = $conn->prepare("
    SELECT Status 
    FROM discount_applications 
    WHERE UserID = ? 
    ORDER BY ApplicationID DESC 
    LIMIT 1
");
$discountQuery->bind_param("i", $user_id);
$discountQuery->execute();
$discountResult = $discountQuery->get_result();
$discountRow = $discountResult->fetch_assoc();
$discountStatus = $discountRow['Status'] ?? 'none';
$isDiscountApproved = ($discountStatus === 'Approved');
$discountQuery->close();

// Fetch user details
$userDetailsQuery = $conn->prepare("
    SELECT FirstName, LastName, Email 
    FROM users 
    WHERE UserID = ?
");
$userDetailsQuery->bind_param("i", $user_id);
$userDetailsQuery->execute();
$userDetailsResult = $userDetailsQuery->get_result();
$userDetails = $userDetailsResult->fetch_assoc();
$userDetailsQuery->close();

$userFirstName = htmlspecialchars($userDetails['FirstName'] ?? '');
$userLastName  = htmlspecialchars($userDetails['LastName'] ?? '');
$userEmail     = htmlspecialchars($userDetails['Email'] ?? '');

// Fetch schedules
// Fetch schedules with fare from fare table
$schedules = $conn->query("
    SELECT 
        s.ScheduleID,
        s.VehicleID,
        s.RouteID,
        s.DayOfWeek,
        s.DepartureTime,
        s.ArrivalTime,
        f.Amount AS Fare,       
        r.StartLocation,
        r.EndLocation,
        v.TypeID
    FROM schedules s
    JOIN route r ON s.RouteID = r.RouteID
    JOIN vehicle v ON s.VehicleID = v.VehicleID
    LEFT JOIN fare f 
        ON f.RouteID = s.RouteID 
       AND f.TypeID = v.TypeID
    ORDER BY FIELD(s.DayOfWeek,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.DepartureTime
");

// Fetch all routes with associated vehicle types
$routeQuery = $conn->query("
    SELECT r.RouteID, r.StartLocation, r.EndLocation, GROUP_CONCAT(DISTINCT v.TypeID) AS TypeIDs
    FROM route r
    JOIN schedules s ON r.RouteID = s.RouteID
    JOIN vehicle v ON s.VehicleID = v.VehicleID
    GROUP BY r.RouteID
");

$routesData = [];
while ($r = $routeQuery->fetch_assoc()) {
    $r['TypeIDs'] = explode(',', $r['TypeIDs']);
    $routesData[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Book Ticket - Ticketing System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* === GLOBAL STYLES === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .global-map-bg {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/World_map_-_low_resolution.svg/1280px-World_map_-_low_resolution.svg.png') center/cover no-repeat;
            opacity: 0.1;
            z-index: -1;
        }

        body::-webkit-scrollbar {
            display: none;
            width: 0;
        }

        /* === HEADER === */
        header {
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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

        .menu {
            font-size: 26px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .menu:hover {
            transform: scale(1.1);
        }

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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .profile:hover {
            background-color: #66bb6a;
            transform: scale(1.1);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        /* === SIDEBAR === */
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            top: 0;
            left: 0;
            background: #1b1b1b;
            overflow-x: hidden;
            transition: 0.4s;
            padding-top: 60px;
            z-index: 1000;
        }

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
            padding-left: 35px;
        }

        .sidebar .closebtn {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            color: white;
            cursor: pointer;
        }

        /* === LAYOUT === */
        .main-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
        }

        @media (max-width: 968px) {
            .main-container {
                grid-template-columns: 1fr;
                margin: 20px auto;
            }
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9e9e9;
            transition: transform 0.3s ease;
        }

        h3 {
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 700;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            margin-bottom: 8px;
            color: #666;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            background-color: #fcfcfc;
            transition: all 0.2s ease;
        }

        input:focus,
        select:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 3px #2e7d3233;
            background: #fff;
        }

        input[readonly] {
            background: #f7f7f7;
            color: #4a4a4a;
            border-color: #e0e0e0 !important;
            box-shadow: none !important;
            cursor: not-allowed;
        }

        .btn {
            background: #2e7d32;
            color: #fff;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
            width: 100%;
            margin-top: 20px;
        }

        .btn:hover {
            background: #388e3c;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .btn:disabled {
            background: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn.secondary {
            background: #fff;
            color: #2e7d32;
            border: 1px solid #2e7d32;
            box-shadow: none;
            margin-top: 10px;
            padding: 5px 10px;
            flex: 1;
        }

        .btn.secondary:hover {
            background: #f0fdf0;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* === FARE BOX === */
        .fare-box {
            margin-top: 24px;
            padding: 18px 20px;
            background: #e6f5e6;
            border: 2px solid #2e7d32;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .fare-box span:first-child {
            color: #1b5e20;
            font-weight: 600;
            font-size: 18px;
        }

        #fareDisplay {
            font-weight: 800;
            font-size: 26px;
            color: #1b5e20;
        }

        /* === TICKET PREVIEW === */
        .ticket-preview-box {
            background: #f0fdf0;
            border: 2px solid #2e7d32;
            border-radius: 10px;
            padding: 10px;
            display: flex;
            flex-direction: column; /* horizontal layout */
            justify-content: center;
            align-items: center;
            max-height: 80vh;
            overflow: hidden;
            font-size: 0.8rem;
            gap: 20px; /* space between details and QR */
            text-align: center;
        }
        .ticket-preview-box .ticket-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
        }
        .ticket-preview-box .ticket-qr-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .ticket-qr {
            width: 150px; /* adjust size */
            height: 150px;
            border: 2px solid #2e7d32;
            border-radius: 4px;
            padding: 2px;
            align-items: center;
        }



        /* === STATUS MESSAGES === */
        .status-message {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* === RIGHT COLUMN === */
        .right-column {
            position: sticky;
            top: 90px;
            max-height: calc(100vh - 110px);
            overflow: visible;
        }

        .right-column h3 {
            text-align: center;
            margin-bottom: 10px;
        }

        .right-column .card {
            display: flex;
            flex-direction: column;
            max-height: 100%;
        }

        /* === BALANCE DISPLAY === */
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

        /* === SCHEDULE CONTAINER === */
        .schedule-wrapper {
            border: 2px solid #d7edca;
            border-radius: 12px;
            background-color: #f9fff9;
            padding: 4px;
            max-height: 328px;
            box-sizing: border-box;
        }

        .schedule-options {
            max-height: 320px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px;
            border-radius: 8px;
        }

        .schedule-options::-webkit-scrollbar {
            width: 8px;
        }

        .schedule-options::-webkit-scrollbar-track {
            background: #e8f5e9;
            border-radius: 10px;
        }

        .schedule-options::-webkit-scrollbar-thumb {
            background: #2e7d32;
            border-radius: 10px;
        }

        .schedule-options::-webkit-scrollbar-thumb:hover {
            background: #1b5e20;
        }

        .schedule-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .schedule-box:hover {
            background: #f0fdf0;
            border-color: #2e7d32;
        }

        .schedule-box.selected {
            background: #e6f5e6;
            border-color: #2e7d32;
        }

        .schedule-box input[type="radio"] {
            width: 18px;
            height: 18px;
            margin: 0;
            flex-shrink: 0;
            accent-color: #2e7d32;
        }

        .schedule-info {
            display: flex;
            flex-direction: column;
            font-size: 14px;
            color: #333;
            gap: 2px;
        }

        /* === NO SCHEDULE MESSAGE === */
        #no-match-message,
        .schedule-options p.muted {
            display: block;
            text-align: center;
            padding: 20px;
            border: 2px dashed #dcdcdc;
            border-radius: 12px;
            background-color: #ffffff;
            color: #999;
            font-weight: 600;
            font-size: 14px;
            margin: 0;
        }

        /* === MODAL === */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px 20px;
            max-width: 480px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9e9e9;
        }

        .modal-header h3 {
            /* margin: 0; */
            color: #2e7d32;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 32px;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            border-radius: 50%;
        }

        .modal-close:hover {
            color: #333;
            background: #f5f5f5;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions .btn {
            margin: 0;
            padding: 10px 14px;
            flex: 1;
        }

        .btn.cancel {
            background: #fff;
            color: #666;
            border: 2px solid #dcdcdc;
        }

        .btn.cancel:hover {
            background: #f5f5f5;
            color: #333;
            border-color: #999;
            transform: translateY(-1px);
        }

        /* === STATUS BADGES === */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-paid {
            background: #cce5ff;
            color: #004085;
        }

        /* === MODAL SCROLLBAR === */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #2e7d32;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #1b5e20;
        }

        /* ticket card */
        .ticket-card-qr{
            display: grid;
            grid-template-columns: 1.3fr 1fr; /* two equal columns */
            gap: 16px; /* space between columns */
            width: 100%;
            max-width: 500px; /* optional, to prevent it from being too wide */
            margin: 0 auto;
        }
        .ticket-detail {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;               /* spacing between lines */
            font-size: 15px;
        }

        .ticket-detail strong {
            color: #2e7d32;              /* highlight labels */
        }

        .status-confirmed {
            color: #2e7d32;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="global-map-bg"></div>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar" aria-hidden="true">
        <span class="closebtn" onclick="closeNav()">&times;</span>
        <a href="../passenger_dashboard.php"><i class="fas fa-home"></i> Homepage</a>
        <a href="../vehicle.php"><i class="fas fa-bus"></i> Vehicles</a>
        <a href="ticketing.php"><i class="fas fa-ticket-alt"></i> Buy Ticket</a>
        <a href="../buyCoin/buy_coins.php"><i class="fas fa-coins"></i> Buy Coins</a>
        <a href="../feedback.php"><i class="fas fa-comment-dots"></i> Feedback</a>
        <a href="../about.php"><i class="fas fa-info-circle"></i> About Us</a>
        <a href="../discountPage/discount_page.php"><i class="fas fa-percent"></i> Apply for a Discount</a>
    </div>

    <!-- Header -->
    <header>
        <div class="menu" onclick="openNav()">☰</div>
        <div class="right-header">
            <a href="../redeem_voucher.php" class="coin-balance">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" fill="#F4C542" />
                    <circle cx="12" cy="12" r="8.2" fill="#F9D66B" />
                    <path d="M8 12c0-2 3-2 4-2s4 0 4 2-3 2-4 2-4 0-4-2z" fill="#D39C12" opacity="0.9" />
                </svg>
                <span id="header-balance"> <i class="fas fa-coins"></i>0</span>
            </a>
            <div class="profile" onclick="window.location.href='../user_prof.php'">👤</div>
        </div>
    </header>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="modal-overlay" onclick="closeModalOnBackdrop(event)">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-ticket-alt"></i> Confirm Ticket Booking</h3>
                <button class="modal-close" onclick="closeConfirmationModal()">&times;</button>
            </div>
            <div id="modal-ticket-details">
                <!-- Details populated by JavaScript -->
            </div>
            <div class="modal-actions">
                <button class="btn cancel" onclick="closeConfirmationModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn" onclick="generateTicket()">
                    <i class="fas fa-check-circle"></i> Generate Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- LEFT COLUMN -->
        <div>
            <div class="balance-display">
                <div class="label">Wallet Balance</div>
                <div class="amount" id="user-balance"><i class="fas fa-coins"></i> 0.00</div>
            </div>

            <div class="card ticket-card">
                <h3>Book a Ticket</h3>

                <?php if ($isDiscountApproved): ?>
                    <div class="status-message status-approved">
                        <i class="fas fa-check-circle"></i> Your discount application was approved! (20% off applied)
                    </div>
                <?php elseif ($discountStatus === 'Pending'): ?>
                    <div class="status-message status-pending">
                        <i class="fas fa-clock"></i> Your discount application is still under review.
                    </div>
                <?php elseif ($discountStatus === 'Rejected'): ?>
                    <div class="status-message status-rejected">
                        <i class="fas fa-times-circle"></i> Your discount application was rejected.
                    </div>
                <?php endif; ?>

                <form id="booking-form">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="first-name" required value="<?= $userFirstName ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="last-name" required value="<?= $userLastName ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="email" required value="<?= $userEmail ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label for="vehicleType">Vehicle Type</label>
                        <select id="vehicleType" name="vehicleType">
                            <option value="">Select vehicle type</option>
                            <option value="1">Bus</option>
                            <option value="2">E-Jeep</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="routeSelect">Route</label>
                        <select id="routeSelect" name="routeSelect">
                            <option value="">Select Route</option>
                            <?php
                            $routes = $conn->query("SELECT RouteID, StartLocation, EndLocation FROM route");
                            while ($r = $routes->fetch_assoc()) {
                                echo "<option value='{$r['RouteID']}'>" . htmlspecialchars($r['StartLocation'] . ' → ' . $r['EndLocation']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="daySelect">Day of the Week</label>
                        <select id="daySelect" name="daySelect">
                            <option value="">Select Day</option>
                            <option value="Mon">Monday</option>
                            <option value="Tue">Tuesday</option>
                            <option value="Wed">Wednesday</option>
                            <option value="Thur">Thursday</option>
                            <option value="Fri">Friday</option>
                            <option value="Sat">Saturday</option>
                            <option value="Sun">Sunday</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Select Schedule</label>
                        <div class="schedule-wrapper">
                            <div class="schedule-options" id="schedule-container">
                                <?php if ($schedules->num_rows > 0): ?>
                                    <?php while ($sch = $schedules->fetch_assoc()): ?>
                                        <label class="schedule-box" 
                                               data-type="<?= $sch['TypeID'] ?>" 
                                               data-route="<?= $sch['RouteID'] ?>" 
                                               data-day="<?= $sch['DayOfWeek'] ?>" 
                                               data-fare="<?= $sch['Fare'] ?>" 
                                               data-departure="<?= $sch['DepartureTime'] ?>" 
                                               data-arrival="<?= $sch['ArrivalTime'] ?>" 
                                               data-destination="<?= htmlspecialchars($sch['StartLocation'] . ' to ' . $sch['EndLocation']) ?>">
                                            <input type="radio" name="schedule_id" value="<?= $sch['ScheduleID'] ?>" data-fare="<?= $sch['Fare'] ?>" required>
                                            <div class="schedule-info">
                                                <div><strong><?= htmlspecialchars($sch['StartLocation'] . ' → ' . $sch['EndLocation']) ?></strong></div>
                                                <div><?= $sch['DayOfWeek'] ?></div>
                                                <div><?= htmlspecialchars($sch['DepartureTime'] . ' → ' . $sch['ArrivalTime']) ?></div>
                                                <div><?= $sch['TypeID'] == 1 ? 'Bus' : 'E-Jeep' ?> |  <i class="fas fa-coins"></i> <?= number_format($sch['Fare'], 2) ?></div>
                                            </div>
                                        </label>
                                    <?php endwhile; ?>
                                    <p class="muted" id="no-match-message" style="display:none;">No schedules match your selection.</p>
                                <?php else: ?>
                                    <p class="muted" id="no-schedule-message">No schedules available at this time.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="fare-box">
                        <span>Fare:</span>
                        <span id="fareDisplay"><i class="fas fa-coins"></i> 0.00</span>
                    </div>

                    <button type="submit" class="btn">Book Ticket</button>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="right-column">
            <div class="card">
                <h3>Your Ticket</h3>
                <div id="ticket-preview" class="ticket-preview-box empty">
                    <p class="muted">No ticket booked yet.</p>
                </div>
            </div>
        </div>
    </div>

    <script>

        // === VARIABLES ===
        const vehicleSelect = document.getElementById('vehicleType');
        const routeSelect = document.getElementById('routeSelect');
        const daySelect = document.getElementById('daySelect');
        const fareDisplay = document.getElementById('fareDisplay');
        const isDiscountApproved = <?= $isDiscountApproved ? 'true' : 'false' ?>;
        const allRoutes = <?= json_encode($routesData) ?>;
        
        let pendingBookingData = null;

        // === POPULATE ROUTES ===
        function populateRoutes() {
            const type = vehicleSelect.value;
            routeSelect.innerHTML = '<option value="">Select Route</option>';

            allRoutes.forEach(r => {
                if (!type || r.TypeIDs.includes(type)) {
                    const option = document.createElement('option');
                    option.value = r.RouteID;
                    option.textContent = `${r.StartLocation} → ${r.EndLocation}`;
                    routeSelect.appendChild(option);
                }
            });
        }

        // === EVENT LISTENERS ===
        vehicleSelect.addEventListener('change', () => {
            populateRoutes();
            routeSelect.value = '';
            filterSchedules();
        });

        routeSelect.addEventListener('change', filterSchedules);
        daySelect.addEventListener('change', filterSchedules);

        // === SIDEBAR FUNCTIONS ===
        function openNav() {
            document.getElementById("sidebar").style.width = "280px";
        }

        function closeNav() {
            document.getElementById("sidebar").style.width = "0";
        }

        // === FILTER SCHEDULES ===
        function filterSchedules() {
            const boxes = document.querySelectorAll('.schedule-box');
            const type = vehicleSelect.value;
            const route = routeSelect.value;
            const day = daySelect.value;

            let anyVisible = false;

            boxes.forEach(box => {
                const radio = box.querySelector('input[type="radio"]');
                const match = (!type || box.dataset.type === type.toString()) &&
                              (!route || box.dataset.route === route.toString()) &&
                              (!day || box.dataset.day === day);

                box.style.display = match ? 'flex' : 'none';
                if (match) anyVisible = true;
                if (!match) radio.checked = false;
            });

            // Handle no-match vs no-schedule messages
            const noMatchMsg = document.getElementById('no-match-message');
            const noScheduleMsg = document.getElementById('no-schedule-message');

            if (boxes.length === 0) {
                if (noScheduleMsg) noScheduleMsg.style.display = 'block';
                if (noMatchMsg) noMatchMsg.style.display = 'none';
            } else {
                if (noMatchMsg) noMatchMsg.style.display = anyVisible ? 'none' : 'block';
                if (noScheduleMsg) noScheduleMsg.style.display = 'none';
            }

            updateFareDisplay();
        }

        // === SCHEDULE SELECTION ===
        document.getElementById('schedule-container').addEventListener('click', function (e) {
            const box = e.target.closest('.schedule-box');
            if (!box) return;

            document.querySelectorAll('.schedule-box').forEach(b => b.classList.remove('selected'));
            box.classList.add('selected');

            const radio = box.querySelector('input[type="radio"]');
            radio.checked = true;

            updateFareDisplay();
        });

        // === UPDATE FARE DISPLAY ===
        function updateFareDisplay() {
            const selected = document.querySelector('input[name="schedule_id"]:checked');
            if (!selected) {
                fareDisplay.innerHTML = '<i class="fas fa-coins"></i> 0.00';
                return;
            }

            const base = parseFloat(selected.dataset.fare) || 0;
            const finalFare = isDiscountApproved ? base * 0.8 : base;

            if (isDiscountApproved) {
                fareDisplay.innerHTML = `<span style="text-decoration:line-through;opacity:0.6;"><i class="fas fa-coins"></i> ${base.toFixed(2)}</span> <i class="fas fa-coins"></i> ${finalFare.toFixed(2)}`;
            } else {
                fareDisplay.innerHTML = `<i class="fas fa-coins"></i> ${finalFare.toFixed(2)}`;
            }
        }

        // === FETCH USER BALANCE ===
        async function renderUserBalance() {
            const ub = document.getElementById('user-balance');
            const hb = document.getElementById('header-balance');

            ub.textContent = 'Loading...';
            if (hb) hb.textContent = '...';

            try {
                const res = await fetch('../get_passenger_data.php');
                const data = await res.json();

                if (data.success) {
                    const bal = parseFloat(data.user.balance || 0).toFixed(2);
                    ub.innerHTML = `<i class="fas fa-coins"></i> ${bal}`;
                    if (hb) hb.innerHTML = ` ${bal}`;
                }
                else {
                    ub.textContent = 'Error';
                    if (hb) hb.textContent = 'Error';
                    console.error(data.error);
                }
            } catch (e) {
                ub.textContent = 'Network Error';
                if (hb) hb.textContent = 'Error';
                console.error(e);
            }
        }

        // === SHOW CONFIRMATION MODAL ===
        function showConfirmationModal() {
            if (!pendingBookingData) {
                console.error('No pending booking data');
                return;
            }

            const modal = document.getElementById('confirmation-modal');
            const detailsContainer = document.getElementById('modal-ticket-details');

            detailsContainer.innerHTML = `
                <div class="ticket-detail">
                    <strong>Passenger Name:</strong>
                    <span>${pendingBookingData.first_name} ${pendingBookingData.last_name}</span>
                </div>
                <div class="ticket-detail">
                    <strong>Email:</strong>
                    <span>${pendingBookingData.email}</span>
                </div>
                <div class="ticket-detail">
                    <strong>Vehicle Type:</strong>
                    <span>${pendingBookingData.vehicleType}</span>
                </div>
                <div class="ticket-detail">
                    <strong>Route:</strong>
                    <span>${pendingBookingData.route}</span>
                </div>
                <div class="ticket-detail">
                    <strong>Day:</strong>
                    <span>${pendingBookingData.day}</span>
                </div>
                <div class="ticket-detail">
                    <strong>Time:</strong>
                    <span>${pendingBookingData.time}</span>
                </div>
                <div class="ticket-detail">
                    <strong>Fare:</strong>
                    <span> <i class="fas fa-coins"></i> ${parseFloat(pendingBookingData.fare).toFixed(2)}</span>
                </div>
                <div class="ticket-detail"><strong>Quantity:</strong> <span></span> (Feature TBI) </div>
                <div class="ticket-detail">
                    <strong>Status:</strong>
                    <span><span class="status-badge status-active">Active</span></span>
                </div>
            `;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // === CLOSE CONFIRMATION MODAL ===
        function closeConfirmationModal() {
            const modal = document.getElementById('confirmation-modal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // === CLOSE MODAL ON BACKDROP CLICK ===
        function closeModalOnBackdrop(event) {
            if (event.target.id === 'confirmation-modal') {
                closeConfirmationModal();
            }
        }

        // === GENERATE TICKET ===

// === GENERATE TICKET (Updated with email notification) ===
async function generateTicket() {
    if (!pendingBookingData) {
        alert('No booking data available');
        return;
    }

    const modal = document.getElementById('confirmation-modal');
    const generateBtn = modal.querySelector('.modal-actions .btn:not(.cancel)');
    const origText = generateBtn.innerHTML;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    generateBtn.disabled = true;

    const formData = new FormData();
    formData.append('schedule_id', pendingBookingData.schedule_id);
    formData.append('fare', pendingBookingData.fare);
    formData.append('destination', pendingBookingData.destination);
    formData.append('first_name', pendingBookingData.first_name);
    formData.append('last_name', pendingBookingData.last_name);
    formData.append('email', pendingBookingData.email);

    try {
        const res = await fetch('process_ticket.php', {
            method: 'POST',
            body: formData
        });
        const text = await res.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (err) {
            console.error('Invalid JSON', text);
            alert('Server returned invalid response');
            generateBtn.innerHTML = origText;
            generateBtn.disabled = false;
            return;
        }

        if (!data.success) {
            alert(data.error || 'Booking failed');
            generateBtn.innerHTML = origText;
            generateBtn.disabled = false;
            return;
        }

        // Update balance
        if (data.balance !== undefined) {
            const newBal = parseFloat(data.balance).toFixed(2);
            document.getElementById('user-balance').innerHTML = `<i class="fa-solid fa-coins"></i> ${newBal}`;
            document.getElementById('header-balance').innerHTML = ` ${newBal}`;
        }


        // Show email status message
        let emailStatusMsg = '';
        if (data.email_sent) {
            emailStatusMsg = `
                <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> Check your inbox at <strong>${pendingBookingData.email}</strong>
                </div>
            `;
        } else if (data.email_error) {
            emailStatusMsg = `
                <div style="background-color: #fff3cd; color: #856404; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #ffeaa7;">
                    <i class="fas fa-exclamation-triangle"></i> Ticket generated but email could not be sent. Please check your email settings.
                </div>
            `;
        }

        // Update ticket preview
        const ticketPreview = document.getElementById('ticket-preview');
        ticketPreview.className = 'ticket-preview-box';
        ticketPreview.innerHTML = `
            <div style="width:100%; text-align:center;">

                    ${emailStatusMsg}

                <div class="ticket-card-qr">
                    
                    <!-- Left column -->
                    <div>
                        <div class="ticket-detail"><strong>Ticket ID:</strong> <span>#${data.ticket_id}</span></div>
                        <div class="ticket-detail"><strong>Passenger:</strong> <span>${data.name}</span></div>
                        <div class="ticket-detail"><strong>Email:</strong> <span>${pendingBookingData.email}</span></div>
                        <div class="ticket-detail"><strong>Destination:</strong> <span>${data.destination}</span></div>
                        <div class="ticket-detail"><strong>Vehicle:</strong> <span>${data.vehicle_type}</span></div>
                    </div>

                    <!-- Right column -->
                    <div>
                        <div class="ticket-detail"><strong>Date:</strong> <span>${data.date}</span></div>
                        <div class="ticket-detail"><strong>Time:</strong> <span>${data.departure_time} → ${data.arrival_time}</span></div>
                        <div class="ticket-detail"><strong>Fare:</strong> <span> <i class="fas fa-coins"></i> ${parseFloat(data.fare).toFixed(2)}</span></div>
                        <div class="ticket-detail"><strong>Quantity:</strong> <span></span> (Feature TBI) </div>
                        <div class="ticket-detail"><strong>Status:</strong> <span class="status-confirmed">✓ Confirmed</span></div>
                    </div>
                </div>

                
                ${data.qr ? `<div style="text-align:center; margin-top:20px;">
                    <p style="margin-bottom:10px;font-weight:600;color:#2e7d32;">Scan this QR Code:</p>

                    <div class="ticket-qr-container">
                        <img src="${data.qr}" alt="Ticket QR Code" class="ticket-qr">
                    </div>

                    <button class="btn secondary" onclick="downloadTicket('${data.qr}','${data.name}','${data.destination}')">
                        <i class="fas fa-download"></i> Download Ticket
                    </button>
                </div>` : '<p style="color:orange;text-align:center;">QR Code generation in progress...</p>'}
            </div>
        `;

        const emailForMsg = pendingBookingData.email;

        // Reset form
        document.getElementById('booking-form').reset();
        vehicleSelect.value = '';
        routeSelect.value = '';
        daySelect.value = '';
        filterSchedules();
        fareDisplay.innerHTML = '<i class="fas fa-coins"></i> 0.00';

        // Close modal
        closeConfirmationModal();
        pendingBookingData = null;

        // Show success message with email status
        let successMsg = 'Ticket generated successfully!';
        if (data.email_sent) {
            successMsg += '\n\nA confirmation email has been sent to ' + emailForMsg;
        }
        alert(successMsg);

    } catch (error) {
        console.error(error);
        alert('Could not connect to server');
        generateBtn.innerHTML = origText;
        generateBtn.disabled = false;
    }
}


        // === DOWNLOAD TICKET ===
        function downloadTicket(qr, name, destination) {
            const link = document.createElement('a');
            link.href = qr;
            link.download = `Ticket_${name}_${destination}.png`;
            link.click();
        }

        // === BOOKING FORM SUBMISSION ===
        document.getElementById('booking-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const selectedRadio = document.querySelector('input[name="schedule_id"]:checked');
            if (!selectedRadio) {
                alert('Please select a schedule');
                return;
            }

            const box = selectedRadio.closest('.schedule-box');
            if (!box) {
                alert('Schedule information not found');
                return;
            }

            const scheduleId = selectedRadio.value;
            const baseFare = parseFloat(selectedRadio.dataset.fare) || 0;
            const finalFare = isDiscountApproved ? baseFare * 0.8 : baseFare;

            const firstName = document.getElementById('first-name').value;
            const lastName = document.getElementById('last-name').value;
            const email = document.getElementById('email').value;

            // Get schedule details from data attributes
            const destination = box.dataset.destination || 'N/A';
            const departure = box.dataset.departure || 'N/A';
            const arrival = box.dataset.arrival || 'N/A';
            const day = box.dataset.day || 'N/A';
            const vehicleType = box.dataset.type == '1' ? 'Bus' : 'E-Jeep';

            // Get route from schedule info
            const scheduleInfo = box.querySelector('.schedule-info');
            const routeText = scheduleInfo ? scheduleInfo.querySelector('div strong')?.textContent : destination;

            // Store pending booking data
            pendingBookingData = {
                schedule_id: scheduleId,
                fare: finalFare.toFixed(2),
                destination: destination,
                first_name: firstName,
                last_name: lastName,
                email: email,
                route: routeText || destination,
                day: day,
                time: `${departure} → ${arrival}`,
                vehicleType: vehicleType,
                baseFare: baseFare
            };

            console.log('Pending Booking Data:', pendingBookingData);

            // Show confirmation modal
            showConfirmationModal();
        });

        // === INITIALIZE ===
        filterSchedules();
        renderUserBalance();
    </script>
</body>

</html>