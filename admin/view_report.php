<?php include 'includes/header.php'; ?>

<?php
// Check if report type is provided
if (!isset($_GET['type']) || empty($_GET['type'])) {
    header('Location: reports.php');
    exit;
}

$report_type = $_GET['type'];
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Validate report type
$valid_report_types = ['sales', 'products', 'customers', 'categories'];
if (!in_array($report_type, $valid_report_types)) {
    header('Location: reports.php');
    exit;
}

// Get report title
$report_title = '';
switch ($report_type) {
    case 'sales':
        $report_title = 'Sales Report';
        break;
    case 'products':
        $report_title = 'Product Performance Report';
        break;
    case 'customers':
        $report_title = 'Customer Report';
        break;
    case 'categories':
        $report_title = 'Category Performance Report';
        break;
}

// Generate report data based on report type
if ($report_type == 'sales') {
    // Sales Report - Daily sales for the selected period
    $query = "SELECT DATE(created_at) as date, COUNT(*) as order_count, SUM(total_amount) as total_sales 
              FROM orders 
              WHERE created_at BETWEEN ? AND ? 
              AND order_status != 'cancelled'
              GROUP BY DATE(created_at) 
              ORDER BY date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $start_date);
    $stmt->bindParam(2, $end_date . ' 23:59:59');
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_orders = 0;
    $total_revenue = 0;
    foreach ($report_data as $row) {
        $total_orders += $row['order_count'];
        $total_revenue += $row['total_sales'];
    }
    
    // Get average order value
    $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
    
} elseif ($report_type == 'products') {
    // Product Performance Report
    $query = "SELECT p.id, p.name, p.image, p.price, p.discount_price, 
              COUNT(oi.id) as order_count, SUM(oi.quantity) as quantity_sold, 
              SUM(oi.price * oi.quantity) as revenue
              FROM products p
              LEFT JOIN order_items oi ON p.id = oi.product_id
              LEFT JOIN orders o ON oi.order_id = o.id
              WHERE (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
              AND (o.order_status != 'cancelled' OR o.order_status IS NULL)";
    
    if ($category_id > 0) {
        $query .= " AND p.category_id = ?";
    }
    
    $query .= " GROUP BY p.id
                ORDER BY quantity_sold DESC, revenue DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $start_date);
    $stmt->bindParam(2, $end_date . ' 23:59:59');
    
    if ($category_id > 0) {
        $stmt->bindParam(3, $category_id);
    }
    
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($report_type == 'customers') {
    // Customer Report
    $query = "SELECT u.id, u.name, u.email, u.phone, 
              COUNT(o.id) as order_count, SUM(o.total_amount) as total_spent,
              MAX(o.created_at) as last_order_date
              FROM users u
              LEFT JOIN orders o ON u.id = o.user_id
              WHERE u.is_admin = 0
              AND (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
              AND (o.order_status != 'cancelled' OR o.order_status IS NULL)
              GROUP BY u.id
              ORDER BY total_spent DESC, order_count DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $start_date);
    $stmt->bindParam(2, $end_date . ' 23:59:59');
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($report_type == 'categories') {
    // Category Performance Report
    $query = "SELECT c.id, c.name, c.parent_id,
              COUNT(DISTINCT o.id) as order_count, 
              SUM(oi.quantity) as quantity_sold, 
              SUM(oi.price * oi.quantity) as revenue
              FROM categories c
              LEFT JOIN products p ON c.id = p.category_id
              LEFT JOIN order_items oi ON p.id = oi.product_id
              LEFT JOIN orders o ON oi.order_id = o.id
              WHERE (o.created_at BETWEEN ? AND ? OR o.created_at IS NULL)
              AND (o.order_status != 'cancelled' OR o.order_status IS NULL)
              GROUP BY c.id
              ORDER BY revenue DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $start_date);
    $stmt->bindParam(2, $end_date . ' 23:59:59');
    $stmt->execute();
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get parent category names for subcategories
    foreach ($report_data as $key => $category) {
        if (!is_null($category['parent_id'])) {
            $query = "SELECT name FROM categories WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $category['parent_id']);
            $stmt->execute();
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            $report_data[$key]['parent_name'] = $parent ? $parent['name'] : 'Unknown';
        }
    }
}

// Get all categories for filter
$category_query = "SELECT id, name, parent_id FROM categories ORDER BY name";
$category_stmt = $db->prepare($category_query);
$category_stmt->execute();
$all_categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize categories into parent and subcategories for display
$categories = [];
$subcategories = [];

foreach ($all_categories as $cat) {
    if ($cat['parent_id'] === null) {
        $categories[] = $cat;
    } else {
        $subcategories[$cat['parent_id']][] = $cat;
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?php echo $report_title; ?></h4>
    <div>
        <button class="btn btn-primary me-2" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Report
        </button>
        <a href="reports.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Reports
        </a>
    </div>
</div>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <input type="hidden" name="type" value="<?php echo $report_type; ?>">
            
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            
            <?php if ($report_type == 'products'): ?>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                <!-- Parent category as an option -->
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?> (Main)
                                </option>
                                
                                <!-- Subcategories -->
                                <?php if (isset($subcategories[$category['id']])): ?>
                                    <?php foreach ($subcategories[$category['id']] as $subcategory): ?>
                                        <option value="<?php echo $subcategory['id']; ?>" <?php echo ($category_id == $subcategory['id']) ? 'selected' : ''; ?>>
                                            &nbsp;&nbsp;<?php echo htmlspecialchars($subcategory['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="view_report.php?type=<?php echo $report_type; ?>" class="btn btn-secondary">Reset Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary -->
<?php if ($report_type == 'sales'): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="mb-0">₹<?php echo number_format($total_revenue, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Avg. Order Value</h5>
                    <h2 class="mb-0">₹<?php echo number_format($avg_order_value, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Report Data -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?php echo $report_title; ?> (<?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($report_data)): ?>
            <div class="alert alert-info">
                No data available for the selected period.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <?php if ($report_type == 'sales'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Avg. Order Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo $row['order_count']; ?></td>
                                    <td>₹<?php echo number_format($row['total_sales'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['order_count'] > 0 ? $row['total_sales'] / $row['order_count'] : 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th>Total</th>
                                <th><?php echo $total_orders; ?></th>
                                <th>₹<?php echo number_format($total_revenue, 2); ?></th>
                                <th>₹<?php echo number_format($avg_order_value, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php elseif ($report_type == 'products'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Orders</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/images/products/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" class="me-2" style="width: 40px; height: 40px; object-fit: contain;">
                                            <a href="../product.php?id=<?php echo $row['id']; ?>" target="_blank"><?php echo $row['name']; ?></a>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($row['discount_price']): ?>
                                            ₹<?php echo number_format($row['discount_price'], 2); ?>
                                            <small class="text-muted text-decoration-line-through">₹<?php echo number_format($row['price'], 2); ?></small>
                                        <?php else: ?>
                                            ₹<?php echo number_format($row['price'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['order_count']; ?></td>
                                    <td><?php echo $row['quantity_sold']; ?></td>
                                    <td>₹<?php echo number_format($row['revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_type == 'customers'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Last Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a>
                                    </td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['phone']; ?></td>
                                    <td><?php echo $row['order_count']; ?></td>
                                    <td>₹<?php echo number_format($row['total_spent'], 2); ?></td>
                                    <td><?php echo $row['last_order_date'] ? date('d M Y', strtotime($row['last_order_date'])) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($report_type == 'categories'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Orders</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo is_null($row['parent_id']) ? 'categories.php?id=' : 'subcategories.php?id='; ?><?php echo $row['id']; ?>">
                                            <?php echo $row['name']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (is_null($row['parent_id'])): ?>
                                            <span class="badge bg-primary">Main Category</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Subcategory of <?php echo $row['parent_name']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['order_count']; ?></td>
                                    <td><?php echo $row['quantity_sold']; ?></td>
                                    <td>₹<?php echo number_format($row['revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart for visualization -->
<?php if (!empty($report_data)): ?>
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Visual Representation</h5>
        </div>
        <div class="card-body">
            <div style="height: 400px;">
                <canvas id="reportChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('reportChart').getContext('2d');
            
            <?php if ($report_type == 'sales'): ?>
                // Sales chart data
                const labels = <?php 
                    $chart_labels = [];
                    $chart_data = [];
                    foreach ($report_data as $row) {
                        $chart_labels[] = date('d M', strtotime($row['date']));
                        $chart_data[] = $row['total_sales'];
                    }
                    echo json_encode($chart_labels);
                ?>;
                
                const data = <?php echo json_encode($chart_data); ?>;
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Daily Sales (₹)',
                            data: data,
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
                });
            <?php elseif ($report_type == 'products'): ?>
                // Products chart data (top 10)
                const labels = <?php 
                    $chart_labels = [];
                    $chart_data = [];
                    $top_products = array_slice($report_data, 0, 10);
                    foreach ($top_products as $row) {
                        $chart_labels[] = $row['name'];
                        $chart_data[] = $row['quantity_sold'];
                    }
                    echo json_encode($chart_labels);
                ?>;
                
                const data = <?php echo json_encode($chart_data); ?>;
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Quantity Sold',
                            data: data,
                            backgroundColor: 'rgba(78, 115, 223, 0.8)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            borderWidth: 1
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
                });
            <?php elseif ($report_type == 'customers'): ?>
                // Customers chart data (top 10)
                const labels = <?php 
                    $chart_labels = [];
                    $chart_data = [];
                    $top_customers = array_slice($report_data, 0, 10);
                    foreach ($top_customers as $row) {
                        $chart_labels[] = $row['name'];
                        $chart_data[] = $row['total_spent'];
                    }
                    echo json_encode($chart_labels);
                ?>;
                
                const data = <?php echo json_encode($chart_data); ?>;
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Spent (₹)',
                            data: data,
                            backgroundColor: 'rgba(28, 200, 138, 0.8)',
                            borderColor: 'rgba(28, 200, 138, 1)',
                            borderWidth: 1
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
                });
            <?php elseif ($report_type == 'categories'): ?>
                // Categories chart data
                const labels = <?php 
                    $chart_labels = [];
                    $chart_data = [];
                    foreach ($report_data as $row) {
                        $chart_labels[] = $row['name'] . (is_null($row['parent_id']) ? '' : ' (Sub)');
                        $chart_data[] = $row['revenue'];
                    }
                    echo json_encode($chart_labels);
                ?>;
                
                const data = <?php echo json_encode($chart_data); ?>;
                
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
                                '#5a5c69', '#858796', '#6610f2', '#fd7e14', '#20c9a6',
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            <?php endif; ?>
        });
    </script>
<?php endif; ?>

<style>
    @media print {
        .admin-sidebar, .admin-navbar, .btn, form, .breadcrumb {
            display: none !important;
        }
        .admin-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .card-header {
            background-color: #f8f9fa !important;
            color: #000 !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>

