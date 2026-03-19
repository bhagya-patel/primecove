<?php include 'includes/header.php'; ?>

<?php
// Set default values for report filters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'last30days';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Prepare date conditions based on selected range
if ($date_range == 'today') {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
} elseif ($date_range == 'yesterday') {
    $start_date = date('Y-m-d', strtotime('-1 day'));
    $end_date = date('Y-m-d', strtotime('-1 day'));
} elseif ($date_range == 'last7days') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
} elseif ($date_range == 'last30days') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
} elseif ($date_range == 'thismonth') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-d');
} elseif ($date_range == 'lastmonth') {
    $start_date = date('Y-m-01', strtotime('-1 month'));
    $end_date = date('Y-m-t', strtotime('-1 month'));
} elseif ($date_range == 'custom') {
    // Custom date range is already set in $start_date and $end_date
}

// Get all categories for filter
$category_query = "SELECT * FROM categories ORDER BY name";
$category_stmt = $db->prepare($category_query);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $stmt->bindParam(2, $end_date);
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
    $query = "SELECT c.id, c.name, 
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
}

// Prepare data for charts
$chart_labels = [];
$chart_data = [];

if ($report_type == 'sales') {
    foreach ($report_data as $row) {
        $chart_labels[] = date('d M', strtotime($row['date']));
        $chart_data[] = $row['total_sales'];
    }
} elseif ($report_type == 'products' && count($report_data) > 0) {
    // Limit to top 10 products for chart
    $top_products = array_slice($report_data, 0, 10);
    foreach ($top_products as $row) {
        $chart_labels[] = $row['name'];
        $chart_data[] = $row['quantity_sold'];
    }
} elseif ($report_type == 'categories' && count($report_data) > 0) {
    foreach ($report_data as $row) {
        $chart_labels[] = $row['name'];
        $chart_data[] = $row['revenue'];
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Reports & Analytics</h4>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print me-2"></i>Print Report
    </button>
</div>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Report Type</label>
                <select class="form-select" id="report_type" name="report_type">
                    <option value="sales" <?php echo ($report_type == 'sales') ? 'selected' : ''; ?>>Sales Report</option>
                    <option value="products" <?php echo ($report_type == 'products') ? 'selected' : ''; ?>>Product Performance</option>
                    <option value="customers" <?php echo ($report_type == 'customers') ? 'selected' : ''; ?>>Customer Report</option>
                    <option value="categories" <?php echo ($report_type == 'categories') ? 'selected' : ''; ?>>Category Performance</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_range" class="form-label">Date Range</label>
                <select class="form-select" id="date_range" name="date_range">
                    <option value="today" <?php echo ($date_range == 'today') ? 'selected' : ''; ?>>Today</option>
                    <option value="yesterday" <?php echo ($date_range == 'yesterday') ? 'selected' : ''; ?>>Yesterday</option>
                    <option value="last7days" <?php echo ($date_range == 'last7days') ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="last30days" <?php echo ($date_range == 'last30days') ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="thismonth" <?php echo ($date_range == 'thismonth') ? 'selected' : ''; ?>>This Month</option>
                    <option value="lastmonth" <?php echo ($date_range == 'lastmonth') ? 'selected' : ''; ?>>Last Month</option>
                    <option value="custom" <?php echo ($date_range == 'custom') ? 'selected' : ''; ?>>Custom Range</option>
                </select>
            </div>
            
            <div class="col-md-3 custom-date-range" style="<?php echo ($date_range != 'custom') ? 'display: none;' : ''; ?>">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            
            <div class="col-md-3 custom-date-range" style="<?php echo ($date_range != 'custom') ? 'display: none;' : ''; ?>">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            
            <div class="col-md-3 category-filter" style="<?php echo ($report_type != 'products') ? 'display: none;' : ''; ?>">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Generate Report</button>
                <a href="reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary Cards -->
<?php if ($report_type == 'sales'): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="mb-0">₹<?php echo number_format($total_revenue, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Avg. Order Value</h5>
                    <h2 class="mb-0">₹<?php echo number_format($avg_order_value, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Period</h5>
                    <h6 class="mb-0"><?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></h6>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Chart -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <?php
            switch ($report_type) {
                case 'sales':
                    echo 'Sales Trend';
                    break;
                case 'products':
                    echo 'Top Products by Quantity Sold';
                    break;
                case 'categories':
                    echo 'Category Revenue Distribution';
                    break;
                default:
                    echo 'Report Visualization';
            }
            ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($chart_data)): ?>
            <div style="height: 400px;">
                <canvas id="reportChart"></canvas>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No data available for the selected period.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Report Data Table -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <?php
            switch ($report_type) {
                case 'sales':
                    echo 'Daily Sales Report';
                    break;
                case 'products':
                    echo 'Product Performance Report';
                    break;
                case 'customers':
                    echo 'Customer Report';
                    break;
                case 'categories':
                    echo 'Category Performance Report';
                    break;
            }
            ?>
        </h5>
    </div>
    <div class="card-body">
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
                        <?php if (count($report_data) > 0): ?>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo $row['order_count']; ?></td>
                                    <td>₹<?php echo number_format($row['total_sales'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['order_count'] > 0 ? $row['total_sales'] / $row['order_count'] : 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (count($report_data) > 0): ?>
                        <tfoot>
                            <tr class="table-primary">
                                <th>Total</th>
                                <th><?php echo $total_orders; ?></th>
                                <th>₹<?php echo number_format($total_revenue, 2); ?></th>
                                <th>₹<?php echo number_format($avg_order_value, 2); ?></th>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
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
                        <?php if (count($report_data) > 0): ?>
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
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No data available for the selected period.</td>
                            </tr>
                        <?php endif; ?>
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
                        <?php if (count($report_data) > 0): ?>
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
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No data available for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php elseif ($report_type == 'categories'): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Orders</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($report_data) > 0): ?>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td>
                                        <a href="products.php?category=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a>
                                    </td>
                                    <td><?php echo $row['order_count']; ?></td>
                                    <td><?php echo $row['quantity_sold']; ?></td>
                                    <td>₹<?php echo number_format($row['revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle custom date range fields
        const dateRangeSelect = document.getElementById('date_range');
        const customDateFields = document.querySelectorAll('.custom-date-range');
        
        dateRangeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateFields.forEach(field => field.style.display = 'block');
            } else {
                customDateFields.forEach(field => field.style.display = 'none');
            }
        });
        
        // Toggle category filter
        const reportTypeSelect = document.getElementById('report_type');
        const categoryFilter = document.querySelector('.category-filter');
        
        reportTypeSelect.addEventListener('change', function() {
            if (this.value === 'products') {
                categoryFilter.style.display = 'block';
            } else {
                categoryFilter.style.display = 'none';
            }
        });
        
        <?php if (!empty($chart_data)): ?>
        // Initialize chart
        const ctx = document.getElementById('reportChart').getContext('2d');
        const chartType = <?php echo ($report_type == 'categories') ? "'pie'" : "'bar'"; ?>;
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartData = <?php echo json_encode($chart_data); ?>;
        
        const reportChart = new Chart(ctx, {
            type: chartType,
            data: {
                labels: chartLabels,
                datasets: [{
                    label: <?php 
                        switch ($report_type) {
                            case 'sales':
                                echo "'Revenue (₹)'";
                                break;
                            case 'products':
                                echo "'Quantity Sold'";
                                break;
                            case 'categories':
                                echo "'Revenue (₹)'";
                                break;
                            default:
                                echo "'Value'";
                        }
                    ?>,
                    data: chartData,
                    backgroundColor: <?php echo ($report_type == 'categories') ? 
                        "['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#5a5c69', '#858796', '#6610f2', '#fd7e14', '#20c9a6']" : 
                        "'rgba(78, 115, 223, 0.8)'"; 
                    ?>,
                    borderColor: <?php echo ($report_type == 'categories') ? 
                        "['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#5a5c69', '#858796', '#6610f2', '#fd7e14', '#20c9a6']" : 
                        "'rgba(78, 115, 223, 1)'"; 
                    ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                <?php if ($report_type != 'categories'): ?>
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
                <?php endif; ?>
            }
        });
        <?php endif; ?>
    });
</script>

<?php include 'includes/footer.php'; ?>

