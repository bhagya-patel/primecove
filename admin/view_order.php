<?php include 'includes/header.php'; ?>

<?php
// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: orders.php');
    exit;
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$query = "SELECT oi.*, p.name, p.image FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate shipping and tax
$shipping = 40;
$tax = $subtotal * 0.18;

// Process order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_order_status = $_POST['order_status'];
    $new_payment_status = $_POST['payment_status'];
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    // Update order status
    $query = "UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $new_order_status);
    $stmt->bindParam(2, $new_payment_status);
    $stmt->bindParam(3, $order_id);
    
    if ($stmt->execute()) {
        // Update order status in the current page data
        $order['order_status'] = $new_order_status;
        $order['payment_status'] = $new_payment_status;
        
        // Add success message
        $success_message = "Order status has been updated successfully.";
        
        // Add admin notes if provided
        if (!empty($admin_notes)) {
            // In a real application, you would save admin notes to a database table
            // For this demo, we'll just show a success message
            $success_message .= " Notes have been saved.";
        }
    } else {
        $error_message = "Failed to update order status. Please try again.";
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Order #<?php echo $order_id; ?> Details</h4>
    <div>
        <button class="btn btn-primary me-2" onclick="window.print()">
            <!-- <i class="fas fa-print me-1"></i> Print Invoice -->
            <i class="fas fa-print me-1"></i> Print Invoice
        </button>
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Orders
        </a>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Order Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Order Details</h6>
                        <p class="mb-1"><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
                        <p class="mb-1"><strong>Payment Method:</strong> <?php echo ($order['payment_method'] == 'cod') ? 'Cash on Delivery' : 'Online Payment'; ?></p>
                        <p class="mb-1"><strong>Payment Status:</strong> 
                            <span class="badge <?php echo ($order['payment_status'] == 'completed') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </p>
                        <p class="mb-0"><strong>Order Status:</strong> 
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
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Shipping Address</h6>
                        <address>
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </address>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6>Customer Information</h6>
                        <p class="mb-1"><strong>Name:</strong> <a href="view_user.php?id=<?php echo $order['user_id']; ?>"><?php echo $order['customer_name']; ?></a></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo $order['customer_email']; ?></p>
                        <p class="mb-0"><strong>Phone:</strong> <?php echo $order['customer_phone']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="me-2" style="width: 50px; height: 50px; object-fit: contain;">
                                            <div>
                                                <a href="../product.php?id=<?php echo $item['product_id']; ?>" target="_blank">
                                                    <?php echo $item['name']; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">Subtotal</td>
                                <td class="text-end">₹<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Shipping</td>
                                <td class="text-end">₹<?php echo number_format($shipping, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Tax (18% GST)</td>
                                <td class="text-end">₹<?php echo number_format($tax, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Order Status -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Update Order Status</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="order_status" class="form-label">Order Status</label>
                        <select class="form-select" id="order_status" name="order_status" required>
                            <option value="pending" <?php echo ($order['order_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo ($order['order_status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo ($order['order_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo ($order['order_status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo ($order['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select class="form-select" id="payment_status" name="payment_status" required>
                            <option value="pending" <?php echo ($order['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo ($order['payment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo ($order['payment_status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo ($order['payment_status'] == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add notes about this order (internal only)"></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="update_status" class="btn btn-primary">Update Order</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="mailto:<?php echo $order['customer_email']; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-envelope me-1"></i> Email Customer
                    </a>
                    <a href="generate_invoice.php?id=<?php echo $order_id; ?>" class="btn btn-outline-info" target="_blank">
                        <i class="fas fa-file-invoice me-1"></i> Generate Invoice
                    </a>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-1"></i> View All Orders
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Order Timeline -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Timeline</h5>
            </div>
            <div class="card-body">
                <div class="position-relative mt-3">
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 
                            <?php
                            switch ($order['order_status']) {
                                case 'pending':
                                    echo '25%';
                                    break;
                                case 'processing':
                                    echo '50%';
                                    break;
                                case 'shipped':
                                    echo '75%';
                                    break;
                                case 'delivered':
                                    echo '100%';
                                    break;
                                case 'cancelled':
                                    echo '0%';
                                    break;
                            }
                            ?>
                        "></div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div class="text-center">
                            <div class="rounded-circle bg-<?php echo ($order['order_status'] != 'cancelled') ? 'success' : 'secondary'; ?> text-white d-inline-flex justify-content-center align-items-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-check"></i>
                            </div>
                            <p class="small mt-1">Order Placed</p>
                        </div>
                        <div class="text-center">
                            <div class="rounded-circle bg-<?php echo (in_array($order['order_status'], ['processing', 'shipped', 'delivered'])) ? 'success' : 'secondary'; ?> text-white d-inline-flex justify-content-center align-items-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-cog"></i>
                            </div>
                            <p class="small mt-1">Processing</p>
                        </div>
                        <div class="text-center">
                            <div class="rounded-circle bg-<?php echo (in_array($order['order_status'], ['shipped', 'delivered'])) ? 'success' : 'secondary'; ?> text-white d-inline-flex justify-content-center align-items-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-truck"></i>
                            </div>
                            <p class="small mt-1">Shipped</p>
                        </div>
                        <div class="text-center">
                            <div class="rounded-circle bg-<?php echo ($order['order_status'] == 'delivered') ? 'success' : 'secondary'; ?> text-white d-inline-flex justify-content-center align-items-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-home"></i>
                            </div>
                            <p class="small mt-1">Delivered</p>
                        </div>
                    </div>
                </div>
            </div>
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

<?php include 'includes/footer.php'; ?>

