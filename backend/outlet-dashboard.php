<?php
// File: backend/outlet-dashboard.php
session_start();
require_once 'auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->isOutlet()) {
    header('Location: ../outlet-login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outlet Dashboard - Da Aloo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .order-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .order-header { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .order-number { font-weight: bold; color: #333; }
        .order-time { color: #666; font-size: 14px; }
        .customer-info { margin-bottom: 15px; }
        .order-items { margin-bottom: 15px; }
        .item { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .total { font-weight: bold; border-top: 2px solid #eee; padding-top: 10px; margin-top: 10px; }
        .actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-accept { background: #28a745; color: white; }
        .btn-preparing { background: #17a2b8; color: white; }
        .btn-ready { background: #ffc107; color: #333; }
        .btn-delivered { background: #007bff; color: white; }
        .btn-cancel { background: #dc3545; color: white; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .pending { background: #fff3cd; color: #856404; }
        .accepted { background: #cce5ff; color: #004085; }
        .preparing { background: #d4edda; color: #155724; }
        .ready { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Outlet Dashboard - <?php echo $_SESSION['outlet_name']; ?></h1>
            <p>Welcome, <?php echo $_SESSION['username']; ?> | <button id="logoutBtn" style="background: none; border: none; color: #007bff; cursor: pointer;">Logout</button></p>
        </div>
        
        <div id="ordersContainer" class="orders-grid">
            <!-- Orders will be loaded here -->
            <p style="text-align: center; grid-column: 1/-1;">Loading orders...</p>
        </div>
    </div>
    
    <script>
        const outletId = <?php echo $_SESSION['outlet_id']; ?>;
        
        async function loadOrders() {
            try {
                const response = await fetch(`backend/api/get-outlet-orders.php?outlet_id=${outletId}`);
                const data = await response.json();
                
                if (data.success) {
                    renderOrders(data.orders);
                }
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        }
        
        function renderOrders(orders) {
            const container = document.getElementById('ordersContainer');
            
            if (orders.length === 0) {
                container.innerHTML = '<p style="text-align: center; grid-column: 1/-1;">No orders yet</p>';
                return;
            }
            
            let html = '';
            
            orders.forEach(order => {
                const items = JSON.parse(order.items);
                let itemsHtml = '';
                
                items.forEach(item => {
                    itemsHtml += `
                        <div class="item">
                            <span>${item.quantity}x ${item.name}</span>
                            <span>Rs ${item.totalPrice}</span>
                        </div>
                    `;
                });
                
                html += `
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-number">${order.order_number}</span>
                            <span class="status-badge ${order.status}">${order.status}</span>
                        </div>
                        <div class="customer-info">
                            <strong>${order.customer_name}</strong><br>
                            ${order.phone}<br>
                            ${order.address}
                        </div>
                        <div class="order-items">
                            ${itemsHtml}
                        </div>
                        <div class="total">
                            Total: Rs ${order.total_amount}
                        </div>
                        <div class="actions">
                            ${order.status === 'pending' ? 
                                `<button class="btn btn-accept" onclick="updateOrder(${order.id}, 'accepted')">Accept</button>
                                 <button class="btn btn-cancel" onclick="updateOrder(${order.id}, 'cancelled')">Cancel</button>` : ''}
                            ${order.status === 'accepted' ? 
                                `<button class="btn btn-preparing" onclick="updateOrder(${order.id}, 'preparing')">Start Preparing</button>` : ''}
                            ${order.status === 'preparing' ? 
                                `<button class="btn btn-ready" onclick="updateOrder(${order.id}, 'ready')">Mark Ready</button>` : ''}
                            ${order.status === 'ready' ? 
                                `<button class="btn btn-delivered" onclick="updateOrder(${order.id}, 'delivered')">Mark Delivered</button>` : ''}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        async function updateOrder(orderId, status) {
            try {
                const response = await fetch('backend/api/update-order-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Order ${status} successfully!`);
                    loadOrders();
                } else {
                    alert('Error updating order: ' + result.message);
                }
            } catch (error) {
                console.error('Error updating order:', error);
                alert('Failed to update order');
            }
        }
        
        // Logout
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch('backend/login.php?logout=true');
            window.location.href = 'outlet-login.html';
        });
        
        // Load orders on page load
        loadOrders();
        // Refresh every 30 seconds
        setInterval(loadOrders, 30000);
    </script>
</body>
</html>