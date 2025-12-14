<?php
// File: backend/admin-dashboard.php
session_start();
require_once 'auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../admin-login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Da Aloo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .sidebar { width: 250px; background: #333; color: white; height: 100vh; position: fixed; }
        .main-content { margin-left: 250px; padding: 20px; }
        .sidebar-header { padding: 20px; background: #222; }
        .sidebar-nav a { display: block; padding: 15px 20px; color: white; text-decoration: none; border-bottom: 1px solid #444; }
        .sidebar-nav a:hover { background: #444; }
        .header { background: white; padding: 15px 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .table-container { background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .pending { background: #fff3cd; color: #856404; }
        .accepted { background: #cce5ff; color: #004085; }
        .preparing { background: #d4edda; color: #155724; }
        .ready { background: #d1ecf1; color: #0c5460; }
        .delivered { background: #28a745; color: white; }
        .cancelled { background: #f8d7da; color: #721c24; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Da Aloo Admin</h2>
            <p>Welcome, <?php echo $_SESSION['username']; ?></p>
        </div>
        <nav class="sidebar-nav">
            <a href="#dashboard">Dashboard</a>
            <a href="#orders">Orders</a>
            <a href="#outlets">Outlets</a>
            <a href="#reports">Reports</a>
            <a href="#settings">Settings</a>
            <a href="#" id="logoutBtn">Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Admin Dashboard</h1>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Today's Orders</h3>
                <p id="todayOrders">0</p>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <p id="pendingOrders">0</p>
            </div>
            <div class="stat-card">
                <h3>Revenue</h3>
                <p id="todayRevenue">Rs 0</p>
            </div>
            <div class="stat-card">
                <h3>Active Outlets</h3>
                <p id="activeOutlets">0</p>
            </div>
        </div>
        
        <div class="table-container">
            <h3 style="padding: 20px 20px 0;">Recent Orders</h3>
            <div id="ordersTable">
                <!-- Orders will be loaded here -->
                <p style="padding: 20px; text-align: center;">Loading orders...</p>
            </div>
        </div>
    </div>
    
    <script>
        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('api/get-orders.php?limit=10');
                const data = await response.json();
                
                if (data.success) {
                    renderOrdersTable(data.orders);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }
        
        function renderOrdersTable(orders) {
            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Outlet</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            orders.forEach(order => {
                html += `
                    <tr>
                        <td>${order.order_number}</td>
                        <td>${order.customer_name}<br><small>${order.phone}</small></td>
                        <td>${order.outlet_name}</td>
                        <td>Rs ${order.total_amount}</td>
                        <td><span class="status-badge ${order.status}">${order.status}</span></td>
                        <td>${new Date(order.created_at).toLocaleTimeString()}</td>
                        <td>
                            <button class="btn btn-primary" onclick="viewOrder(${order.id})">View</button>
                            <button class="btn btn-danger" onclick="updateStatus(${order.id}, 'cancelled')">Cancel</button>
                        </td>
                    </tr>
                `;
            });
            
            html += `</tbody></table>`;
            document.getElementById('ordersTable').innerHTML = html;
        }
        
        // Logout
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch('backend/login.php?logout=true');
            window.location.href = 'admin-login.html';
        });
        
        // Load initial data
        loadDashboardData();
        setInterval(loadDashboardData, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>