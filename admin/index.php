<?php include 'includes/header.php'; ?>

<?php
// Get counts for dashboard
// Total products
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$products_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total categories
$query = "SELECT COUNT(*) as total FROM categories";
$stmt = $db->prepare($query);
$stmt->execute();
$categories_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total users
$query = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$users_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total orders
$query = "SELECT COUNT(*) as total FROM orders";
$stmt = $db->prepare($query);
$stmt->execute();
$orders_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue
$query = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'";
$stmt = $db->prepare($query);
$stmt->execute();
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent orders
$query = "SELECT o.*, u.name as user_name FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Order status counts for chart
$query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$stmt = $db->prepare($query);
$stmt->execute();
$order_status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly sales for chart (last 6 months)
$query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total 
          FROM orders 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
          GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
          ORDER BY month";
$stmt = $db->prepare($query);
$stmt->execute();
$monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Dashboard Cards -->
<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="dashboard-card text-center">
            <div class="card-icon text-primary">
                <i class="fas fa-box"></i>
            </div>
            <h2><?php echo $products_count; ?></h2>
            <p class="text-muted mb-0">Total Products</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="dashboard-card text-center">
            <div class="card-icon text-success">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2><?php echo $orders_count; ?></h2>
            <p class="text-muted mb-0">Total Orders</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="dashboard-card text-center">
            <div class="card-icon text-info">
                <i class="fas fa-users"></i>
            </div>
            <h2><?php echo $users_count; ?></h2>
            <p class="text-muted mb-0">Total Customers</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="dashboard-card text-center">
            <div class="card-icon text-warning">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <h2>₹<?php echo number_format($revenue, 2); ?></h2>
            <p class="text-muted mb-0">Total Revenue</p>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Sales Chart -->
    <div class="col-md-8 mb-4">
        <div class="dashboard-card">
            <h5 class="mb-3">Monthly Sales</h5>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Order Status Chart -->
    <div class="col-md-4 mb-4">
        <div class="dashboard-card">
            <h5 class="mb-3">Order Status</h5>
            <div class="chart-container">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_orders) > 0): ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['user_name']; ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($order['order_status']) {
                                            case 'pending':
                                                $status_class = 'bg-warning text-dark';
                                                break;
                                            case 'processing':
                                                $status_class = 'bg-info text-white';
                                                break;
                                            case 'shipped':
                                                $status_class = 'bg-primary text-white';
                                                break;
                                            case 'delivered':
                                                $status_class = 'bg-success text-white';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-danger text-white';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($order['order_status']); ?></span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Products -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Low Stock Products</h5>
                <a href="products.php" class="btn btn-sm btn-primary">View All Products</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get low stock products
                        $query = "SELECT p.*, c.name as category_name FROM products p 
                                  LEFT JOIN categories c ON p.category_id = c.id 
                                  WHERE p.stock <= 10 
                                  ORDER BY p.stock ASC LIMIT 5";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($low_stock_products) > 0):
                            foreach ($low_stock_products as $product):
                        ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="me-2" style="width: 40px; height: 40px; object-fit: contain;">
                                        <?php echo $product['name']; ?>
                                    </div>
                                </td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php if ($product['stock'] <= 5): ?>
                                        <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><?php echo $product['stock']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="6" class="text-center">No low stock products found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Prepare data for charts
    const orderStatusLabels = <?php 
        $labels = [];
        $data = [];
        foreach ($order_status_data as $status) {
            $labels[] = ucfirst($status['order_status']);
            $data[] = $status['count'];
        }
        echo json_encode($labels);
    ?>;
    
    const orderStatusData = <?php echo json_encode($data); ?>;
    
    const monthlyLabels = <?php 
        $labels = [];
        $data = [];
        foreach ($monthly_sales as $sale) {
            $date = new DateTime($sale['month'] . '-01');
            $labels[] = $date->format('M Y');
            $data[] = $sale['total'];
        }
        echo json_encode($labels);
    ?>;
    
    const monthlySalesData = <?php echo json_encode($data); ?>;
    
    // Order Status Chart
    const orderStatusChart = new Chart(
        document.getElementById('orderStatusChart'),
        {
            type: 'doughnut',
            data: {
                labels: orderStatusLabels,
                datasets: [{
                    data: orderStatusData,
                    backgroundColor: [
                        '#ffc107', // pending
                        '#17a2b8', // processing
                        '#007bff', // shipped
                        '#28a745', // delivered
                        '#dc3545'  // cancelled
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        }
    );
    
    // Monthly Sales Chart
    const salesChart = new Chart(
        document.getElementById('salesChart'),
        {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Monthly Sales (₹)',
                    data: monthlySalesData,
                    fill: false,
                    borderColor: '#2874f0',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );
</script>

<?php include 'includes/footer.php'; ?>

