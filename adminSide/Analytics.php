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

// ✅ Fetch total active vehicles
$result = $conn->query("SELECT COUNT(*) AS total FROM vehicle WHERE Status='ACTIVE'");
$activeVehicles = $result ? $result->fetch_assoc()['total'] : 0;

// ✅ Fetch total tickets sold
$result = $conn->query("SELECT COUNT(*) AS total FROM ticket");
$ticketsSold = $result ? $result->fetch_assoc()['total'] : 0;

// ✅ Fetch total revenue
$result = $conn->query("SELECT SUM(FareAmount) AS revenue FROM ticket");
$totalRevenue = $result ? $result->fetch_assoc()['revenue'] : 0;
if (!$totalRevenue) $totalRevenue = 0;

// ✅ Fetch average rating
$result = $conn->query("SELECT AVG(Rating) AS avgRating FROM vehicletype");
$avgRating = $result ? $result->fetch_assoc()['avgRating'] : 0;
if (!$avgRating) $avgRating = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Analytics Report</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
        /* Custom Variables to match the Bright Green Theme from the image */
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
            background-color: var(--bg); 
            color: var(--text-dark); 
            overflow-x: hidden; 
        }

        body::-webkit-scrollbar {
            display: none; /* Hides the scrollbar */
            width: 0; /* Ensures no width space is reserved for the scrollbar */
        }

        /* --- Header Styling --- */
       header {
            background-color: var(--header-green);
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

        /* --- Sidebar Styling --- */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background-color: var(--sidebar-bg); /* Use dark variable */
            color: white;
            padding: 20px;
            transition: left 0.3s ease;
            z-index: 30;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column; /* Ensure content flows vertically */
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
        /* Link styling refinement for better layout */
        .sidebar a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            transition: background-color 0.2s ease, color 0.2s ease;
            margin-bottom: 5px;
            border-bottom: none; /* Removed the line border for cleaner look */
        }
        .sidebar a i {
            margin-right: 12px;
            width: 20px; /* Fixed width for icon alignment */
            text-align: center;
        }
        .sidebar a:hover { 
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--accent-green); 
        }

        .menu-icon {
            font-size: 24px;
            cursor: pointer;
            color: #064e3b;
        }

        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: none; z-index: 25; opacity: 0; transition: opacity 0.3s ease; }
        .overlay.show { display: block; opacity: 1; }

        /* Main Content */
        main { padding: 20px; transition: margin-left 0.3s ease-in-out; }
        
        

        /* --- Filter Button Styling (Matching the image) --- */
        .filters button {
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fff;
            color: #4a5568;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .filters button:hover {
            background: #f0f0f0;
        }
        .filters button.active {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }

        /* --- Stats Cards (Matching the image) --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .stat-card-analytics {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-card-analytics .icon {
            font-size: 30px; 
        }
        .stat-card-analytics .label {
            font-size: 14px;
            color: #4a5568;
        }
        .stat-card-analytics .value {
            font-size: 24px;
            font-weight: bold;
            color: #1a202c;
        }

        /* --- Chart Layout --- */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        @media (min-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .chart-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        main { padding: 20px; animation: slideIn 0.6s ease; }
        @keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>

<body>

    <!-- HEADER (Bright Green) -->
    <header class="bg-green-200 text-green-900 p-4 shadow-md flex items-center">
        <div class="menu-icon" id="menuBtn">
            <i class="fas fa-bars"></i>
        </div>
        <h1 class="flex items-center">
            <i class="fas fa-chart-line mr-2 text-green-700"></i>
            Analytics
        </h1>
    </header>
    <!-- Sidebar (Dark Gray, kept consistent) -->
    <div class="sidebar" id="sidebar">
        <h2 class="text-green-400">
            <i class="fas fa-grip-vertical"></i>
            Menu
        </h2>
        
        <nav>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="Analytics.php" class="bg-green-600 rounded"><i class="fas fa-chart-line"></i> Analytics</a>
            <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
            <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
            <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
            <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
            <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
            <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
            <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
        </nav>
    </div>
    <div class="overlay" id="overlay"></div>

    <!-- Main Content -->
    <main id="main">
        <h2 class="text-xl font-medium text-gray-700 mb-4">REPORT / ANALYTICS</h2>

        <!-- Filters -->
        <div class="filters flex flex-wrap gap-2">
            <button class="active">Today</button>
            <button>This Week</button>
            <button>This Month</button>
            <button>Custom Range</button>
        </div>

        <!-- Stats (Matching the image content) -->
        <div class="stats-grid">
            
            <!-- Active Vehicles -->
            <div class="stat-card-analytics">
                <div class="icon">🚌</div>
                <div>
                    <div class="label">Active Vehicles</div>
                    <div class="value"><?php echo $activeVehicles ?? '9'; ?></div>
                </div>
            </div>

            <!-- Tickets Sold -->
            <div class="stat-card-analytics">
                <div class="icon">🎟️</div>
                <div>
                    <div class="label">Tickets Sold</div>
                    <div class="value"><?php echo $ticketsSold ?? '0'; ?></div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="stat-card-analytics">
                <div class="icon">💰</div>
                <div>
                    <div class="label">Total Revenue</div>
                    <div class="value">₱<?php echo number_format($totalRevenue ?? 0.00, 2); ?></div>
                </div>
            </div>

            <!-- Passenger Rating -->
            <div class="stat-card-analytics">
                <div class="icon">⭐</div>
                <div>
                    <div class="label">Passenger Rating</div>
                    <div class="value"><?php echo number_format($avgRating ?? 0.0, 1); ?></div>
                </div>
            </div>
        </div>

        <!-- Charts (Line and Bar) -->
        <div class="charts-grid">
            <div class="chart-box">
                <canvas id="lineChart"></canvas>
            </div>
            <div class="chart-box">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        // --- Sidebar Toggle Logic ---
        const sidebar = document.getElementById('sidebar');
        const menuBtn = document.getElementById('menuBtn');
        const overlay = document.getElementById('overlay');
        const mainContent = document.getElementById('main');
        
        const isDesktop = window.matchMedia('(min-width: 1024px)');

        const toggleSidebar = () => {
             const isOpen = sidebar.classList.contains('open');
            if (!isOpen) {
                sidebar.classList.add('open');
                overlay.classList.add('show');
                if (!isDesktop.matches) {
                    mainContent.setAttribute('aria-hidden', 'true');
                }
            } else {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                mainContent.removeAttribute('aria-hidden');
            }
        };

        menuBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // --- Chart Initialization Logic ---
        function initializeCharts() {
            const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-green');
            const tealColor = '#4BC0C0'; // Matching the bar chart color in the image

            // Line Chart: Revenue Trend (Matching the blue/teal area in the image)
            new Chart(document.getElementById('lineChart'), {
                type: 'line',
                data: {
                    labels: ['4:00', '8:00', '12:00', '15:00', '18:00'],
                    datasets: [{
                        label: 'Revenue / Ticket Sales',
                        data: [150, 400, 600, 800, 1300],
                        borderColor: '#36A2EB', // Blue line color
                        backgroundColor: 'rgba(54, 162, 235, 0.2)', // Light blue fill
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 1400 } // Set max Y-axis value
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });

            // Bar Chart: Route Performance (Matching the teal bars in the image)
            new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels: ['Route 1', 'Route 2', 'Route 3', 'Route 4'],
                    datasets: [{
                        label: 'Rate of Passengers',
                        data: [55, 23, 35, 12],
                        backgroundColor: tealColor,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 60 } // Set max Y-axis value
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        }
        
        window.onload = initializeCharts;
    </script>
</body>
</html>

