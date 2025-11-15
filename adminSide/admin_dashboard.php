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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
        /* Custom Variables for Theming */
        :root {
            --header-green: #a7f3d0; /* Tailwind 'green-200' */
            --accent-green: #4ade80; /* Tailwind 'green-400' */
            --bg: #f9fafb; /* Light background */
            --card: #ffffff; /* Card background */
            --text-dark: #111827; /* Dark text */
            --sidebar-bg: #1f2937; /* Darker background for sidebar */
        }
        /* Using Inter font for better clarity */
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

        /* --- Main Content & Cards --- */
        main {
            padding: 20px;
            animation: slideIn 0.6s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Adjusted min size */
            gap: 20px; /* Increased gap */
            margin-top: 20px;
        }
        .card {
            background-color: var(--card);
            padding: 20px; /* Increased padding */
            border-radius: 12px; /* Smoother corners */
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            border-left: 5px solid var(--accent-green); /* Accent border */
        }
        .card:hover { transform: translateY(-5px); }

        .card h3 {
            font-size: 16px; /* Larger title */
            color: #6b7280;
            font-weight: 500;
        }
        .card p {
            font-size: 32px; /* Larger number */
            font-weight: 800;
            margin-top: 8px;
            color: #065f46;
        }

        /* Chart Container */
        .chart-container {
            margin-top: 30px;
            background: var(--card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            animation: slideIn 1s ease;
        }
        .chart-container h3 {
             font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--bg);
            color: var(--text-dark);
        }
        #statusChart {
            /* Chart.js handles responsiveness, setting height for initial space */
            max-height: 400px; 
        }

        /* Overlay for Mobile */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            /* backdrop-filter: blur(2px); */
            display: none;
            z-index: 25;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .overlay.show { 
            display: block; 
            opacity: 1;
        }
        
    </style>
</head>

<body>

    <header class="bg-green-200 text-green-900 p-4 shadow-md flex items-center">
        <div class="menu-icon" id="menuBtn">
            <i class="fas fa-bars"></i>
        </div>
        <h1 class="flex items-center">
            <i class="fas fa-bus-alt mr-2 text-green-700"></i>
            Transit Admin Dashboard
        </h1>
    </header>

    <div class="sidebar" id="sidebar">
        <h2 class="text-green-400">
            <i class="fas fa-grip-vertical"></i>
            Menu
        </h2>
        <a href="admin_dashboard.php" class="bg-green-600 rounded"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="admin_bugreport.php"><i class="fas fa-bug"></i> User Report</a>
        <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
        <a href="coin_deal_management.php"><i class="fas fa-ticket-alt"></i> Coin Deals Management</a>
        <a href="vehicle_management.php"><i class="fas fa-car-side"></i> Vehicle Management</a>
        <a href="schedule_management.php"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
        <a href="route_management.php"><i class="fas fa-route"></i> Routes Management</a>
        <a href="discount_applications.php"><i class="fas fa-tags"></i> Discount Applications</a>
    </div>


    <div class="overlay" id="overlay"></div>

    <main>
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Overview</h2>
        <section class="cards">
            <div class="card">
                <h3>Available Vehicles</h3>
                <p id="availableVehicles">0</p>
            </div>
            <div class="card">
                <h3>On-Going Vehicles</h3>
                <p id="onGoingVehicles">0</p>
            </div>
            <div class="card">
                <h3>Total Schedules</h3>
                <p id="totalSchedules">0</p>
            </div>
            <div class="card">
                <h3>Total Routes</h3>
                <p id="totalRoutes">0</p>
            </div>
        </section>

        <section class="chart-container">
            <h3>Vehicle Status Distribution</h3>
            <!-- Chart.js will draw the chart here -->
            <div class="flex justify-center h-[350px]">
                <canvas id="statusChart"></canvas>
            </div>
        </section>
    </main>

    <script>
        // Use a self-invoking function for scope protection
        (function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const menuBtn = document.getElementById('menuBtn');
            const main = document.querySelector('main');
            
            // Match media for responsiveness check
            const isDesktop = window.matchMedia('(min-width: 1024px)');
            let dashboardData = {};
            let chartInstance = null; // To store the Chart.js instance

            // --- Sidebar Toggle Logic ---
            function toggleSidebar() {
                const isOpen = sidebar.classList.contains('open');
                if (!isOpen) {
                    sidebar.classList.add('open');
                    overlay.classList.add('show');
                    // Prevent main content interaction on mobile
                    if (!isDesktop.matches) {
                        main.setAttribute('aria-hidden', 'true');
                    }
                } else {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                    main.removeAttribute('aria-hidden');
                }
            }

            // Event listeners
            if (menuBtn) menuBtn.addEventListener('click', toggleSidebar);
            if (overlay) overlay.addEventListener('click', toggleSidebar);

            // --- Data Fetching ---
            async function fetchDashboardData() {
                // IMPORTANT: Replace this static return with your actual fetch call to your PHP endpoint (e.g., 'getDashboardData.php')
                // Since your backend is ready, you'd uncomment the fetch below:
                /* try {
                    const response = await fetch('getDashboardData.php');
                    if (!response.ok) throw new Error('Network response was not ok.');
                    return await response.json();
                } catch (error) {
                    console.error("Error fetching data:", error);
                    return {
                         availableVehicles: 0,
                         onGoingVehicles: 0,
                         totalSchedules: 0,
                         totalRoutes: 0
                    };
                }
                */
                
                // Static data for demonstration
                return {
                    availableVehicles: 12,
                    onGoingVehicles: 4,
                    totalSchedules: 9,
                    totalRoutes: 25
                };
            }

            // --- Chart.js Pie Chart Drawing ---
            function drawChart(data) {
                const ctx = document.getElementById('statusChart').getContext('2d');
                
                // Destroy previous instance if it exists to prevent overlap
                if (chartInstance) {
                    chartInstance.destroy();
                }

                chartInstance = new Chart(ctx, {
                    type: 'doughnut', // Doughnut chart looks cleaner than a standard pie
                    data: {
                        labels: [
                            'Available Vehicles',
                            'On-Going Vehicles',
                            'Total Schedules',
                            'Total Routes'
                        ],
                        datasets: [{
                            data: [
                                data.availableVehicles, 
                                data.onGoingVehicles, 
                                data.totalSchedules, 
                                data.totalRoutes 
                            ],
                            backgroundColor: [
                                '#10b981', // green-600
                                '#f59e0b', // amber-500
                                '#3b82f6', // blue-500
                                '#f87171'  // red-400
                            ],
                            hoverOffset: 10,
                            borderRadius: 4,
                            spacing: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right', // Place legend on the side
                                labels: {
                                    boxWidth: 20,
                                    padding: 15,
                                    font: {
                                        family: 'Inter',
                                        size: 14
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1) + '%';
                                        return `${label}: ${value} (${percentage})`;
                                    }
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        }
                    }
                });
            }

            // --- Main Render Function ---
            async function renderDashboard() {
                try {
                    dashboardData = await fetchDashboardData();
                    
                    // 1. Update card values
                    document.getElementById('availableVehicles').textContent = dashboardData.availableVehicles;
                    document.getElementById('onGoingVehicles').textContent = dashboardData.onGoingVehicles;
                    document.getElementById('totalSchedules').textContent = dashboardData.totalSchedules;
                    document.getElementById('totalRoutes').textContent = dashboardData.totalRoutes; // New card
                    
                    // 2. Draw the Chart
                    drawChart(dashboardData);

                } catch (error) {
                    console.error("Failed to render dashboard:", error);
                    // You might want to display an error message on the screen here
                }
            }
            
            // --- Initialization ---
            window.addEventListener('load', renderDashboard);
            // Re-render chart on resize to ensure proper dimensions
            window.addEventListener('resize', () => {
                if (chartInstance) {
                    chartInstance.resize();
                }
            });

        })();
    </script>
</body>
</html>
