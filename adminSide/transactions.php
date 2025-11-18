<?php
include("../config.php");
include("../auth.php");
$loginPage = "/SADPROJ/login.php";

if (!isset($_SESSION['UserID'])) {
    header("Location: $loginPage");
    exit();
}

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "ADMIN") {
    header("Location: $loginPage?error=unauthorized");
    exit();
}

// fetch ticket transactions
$ticketQuery = $conn->query("SELECT * FROM ticket ORDER BY TicketID DESC");

// fetch coin transactions
$coinQuery = $conn->query("SELECT *FROM coin_transactions ORDER BY TransactionDate DESC")
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --header-green: #a7f3d0;
            --accent-green: #4ade80;
            --bg: #f9fafb;
            --card: #ffffff;
            --text-dark: #111827;
            --sidebar-bg: #1f2937;
        }

        * {
            box-sizing: border-box;
            margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        body::-webkit-scrollbar { display: none; }

        header {
            background: var(--header-green);
            color: #064e3b;
            display: flex;
            align-items: center;
            padding: 12px 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        header h1 {
            margin-left: 10px;
            font-size: 20px;
            font-weight: 600;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: var(--sidebar-bg);
            color: white;
            padding: 20px;
            transition: left 0.3s ease;
            z-index: 30;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
        }
        .sidebar.open { left: 0; }

        .sidebar h2 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
            transition: background 0.2s ease;
            margin-bottom: 5px;
        }
        .sidebar a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: var(--accent-green);
        }

        .menu-icon {
            font-size: 24px;
            cursor: pointer;
            color: #064e3b;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            display: none;
            z-index: 25;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .overlay.show {
            display: block;
            opacity: 1;
        }


/* TABLES */

/* Tables styling */
table {
    width: 100%;
    border-collapse: collapse;
}

thead th {
    position: sticky;
    top: 0;
    background-color: #d1fae5; /* Tailwind green-100 */
    z-index: 10;
    text-align: left;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151; /* gray-700 */
}

tbody td {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #111827; /* text-dark */
}

tbody tr:nth-child(even) {
    background-color: #f9fafb; /* light stripe */
}

div.overflow-auto {
    max-height: 500px; /* limit height and enable scrolling */
    overflow-y: auto;
    border: 1px solid #e5e7eb; /* subtle border around table container */
    border-radius: 0.5rem;
}

/* Add hover effect */
tbody tr:hover {
    background-color: #d1fae5; /* subtle hover highlight */
}

/* Responsive adjustments */
@media (max-width: 768px) {
    table, thead, tbody, th, td, tr {
        display: block;
    }

    thead {
        display: none;
    }

    tbody tr {
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.5rem;
        background-color: #ffffff;
    }

    tbody td {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
        position: relative;
    }

    tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6b7280; /* gray-500 */
        flex: 1 1 40%; /* takes 40% of row width */
    }

    tbody td:last-child {
        border-bottom: 0;
    }
}


    </style>
</head>

<body>

<header class="bg-green-200 text-green-900 p-4 shadow-md flex items-center">
    <div class="menu-icon" id="menuBtn">
        <i class="fas fa-bars"></i>
    </div>
    <h1 class="flex items-center">
        <i class="fas fa-exchange-alt mr-2 text-green-700"></i>
        Transactions
    </h1>
</header>

<!-- HEADER AND SIDEBAR -->

<div class="sidebar" id="sidebar">
    <h2 class="text-green-400">
        <i class="fas fa-grip-vertical"></i>
        Menu
    </h2>

    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
    <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
    <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
    <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
    <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
    <a href="transactions.php" class="bg-green-600 rounded">
        <i class="fas fa-exchange-alt"></i> Transactions
    </a>
    <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
</div>

<div class="overlay" id="overlay"></div>



<!-- MAIN CONTENT -->
<div class="ml-0 md:ml-0 p-6 max-w-full">
    <!-- Ticket Transactions Table -->
    <div class="bg-white shadow rounded-lg p-4 overflow-auto max-h-[14rem] mb-6 w-full">
        <h2 class="text-lg font-semibold mb-4">Ticket Transactions</h2>
        <div class ="overflow-auto max-h-96">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-green-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Ticket ID</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Passenger ID</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Schedule ID</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">QR Code</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Fare Amount</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Payment Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($ticket = $ticketQuery->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Ticket ID" class="px-4 py-2 text-sm"><?= $ticket['TicketID'] ?></td>
                        <td data-label="Passenger ID" class="px-4 py-2 text-sm"><?= $ticket['PassengerID'] ?></td>
                        <td data-label="Schedule ID" class="px-4 py-2 text-sm"><?= $ticket['ScheduleID'] ?></td>
                        <td data-label="QR Code" class="px-4 py-2 text-sm"><?= $ticket['QR_Code'] ?></td>
                        <td data-label="Fare Amount" class="px-4 py-2 text-sm"><?= $ticket['FareAmount'] ?></td>
                        <td data-label="Payment Status" class="px-4 py-2 text-sm"><?= $ticket['PaymentStatus'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Coin Transactions Table -->
    <div class="bg-white shadow rounded-lg p-4 overflow-auto max-h-[14rem] mb-6 w-full">
        <h2 class="text-lg font-semibold mb-4">Coin Transactions</h2>
        <div class="overflow-auto max-h-96">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-green-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Transaction ID</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">User ID</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Deal ID</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Coin Amount</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Amount Paid</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Payment Method</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Transaction Date</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($coin = $coinQuery->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Transaction ID" class="px-4 py-2 text-sm"><?= $coin['TransactionID'] ?></td>
                        <td data-label="User ID" class="px-4 py-2 text-sm"><?= $coin['UserID'] ?></td>
                        <td data-label="Deal ID" class="px-4 py-2 text-sm"><?= $coin['DealID'] ?></td>
                        <td data-label="Coin Amount" class="px-4 py-2 text-sm"><?= $coin['CoinAmount'] ?></td>
                        <td data-label="Amount Paid" class="px-4 py-2 text-sm"><?= $coin['AmountPaid'] ?></td>
                        <td data-label="Payment Method" class="px-4 py-2 text-sm"><?= $coin['PaymentMethod'] ?></td>
                        <td data-label="Transaction Date" class="px-4 py-2 text-sm"><?= $coin['TransactionDate'] ?></td>
                        <td data-label="Status" class="px-4 py-2 text-sm"><?= $coin['Status'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>






<script>

// SIDEBAR FUNCTIONS
(function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const menuBtn = document.getElementById('menuBtn');

    function toggleSidebar() {
        const open = sidebar.classList.contains('open');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }

    menuBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
})();

</script>

</body>
</html>
