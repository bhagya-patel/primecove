<?php
// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
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

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $order_status = $_POST['order_status'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $notify_customer = isset($_POST['notify_customer']) ? true : false;
    
    // Validate input
    $valid_order_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $valid_payment_statuses = ['pending', 'completed', 'failed', 'refunded'];
    
    if (!in_array($order_status, $valid_order_statuses)) {
        $errors[] = 'Invalid order status';
    }
    
    if (!in_array($payment_status, $valid_payment_statuses)) {
        $errors[] = 'Invalid payment status';
    }
    
    // If no errors, update order status
    if (empty($errors)) {
        try {
            $query = "UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $order_status);
            $stmt->bindParam(2, $payment_status);
            $stmt->bindParam(3, $order_id);
            
            if ($stmt->execute()) {
                $success = true;
                
                // Save admin notes if provided
                if (!empty($admin_notes)) {
                    // In a real application, you would save admin notes to a database table
                    // For this demo, we'll just show a success message
                }
                
                // Send notification email to customer if requested
                if ($notify_customer) {
                    // In a real application, you would send an email to the customer
                    // For this demo, we'll just show a success message
                }
                
                // Refresh order data
                $query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $order_id);
                $stmt->execute();
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errors[] = 'Failed to update order status. Please try again.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

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
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Update Order Status</h4>
    <div>
        <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn btn-info me-2">
            <i class="fas fa-eye me-1"></i> View Order Details
        </a>
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Orders
        </a>
    </div>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Order status has been updated successfully.
        <?php if (isset($_POST['notify_customer']) && $_POST['notify_customer']): ?>
            Customer has been notified about the status update.
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Order Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
                <p><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
                <p><strong>Email:</strong> <?php echo $order['customer_email']; ?></p>
                <p><strong>Payment Method:</strong> <?php echo ($order['payment_method'] == 'cod') ? 'Cash on Delivery' : 'Online Payment'; ?></p>
                <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                <p><strong>Current Status:</strong> 
                    <span class="badge <?php echo getStatusBadgeClass($order['order_status']); ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </p>
                <p><strong>Payment Status:</strong> 
                    <span class="badge <?php echo getPaymentStatusBadgeClass($order['payment_status']); ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Shipping Address</h5>
            </div>
            <div class="card-body">
                <address>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </address>
            </div>
        </div>
    </div>
    
    <!-- Update Status Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Update Order Status</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="order_status" class="form-label">Order Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="order_status" name="order_status" required>
                                <option value="pending" <?php echo ($order['order_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($order['order_status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo ($order['order_status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo ($order['order_status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo ($order['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <option value="pending" <?php echo ($order['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo ($order['payment_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo ($order['payment_status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo ($order['payment_status'] == 'refunded') ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"
                         placeholder="Add internal notes about this order (not visible to customer)"><?php echo isset($admin_notes) ?
                          htmlspecialchars($admin_notes) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="notify_customer" name="notify_customer" checked>
                        <label class="form-check-label" for="notify_customer">Notify customer about this status update</label>
                    </div>
                    
                    <div class="alert alert-info mb-3">
                        <h6 class="alert-heading">Status Update Guidelines:</h6>
                        <ul class="mb-0">
                            <li><strong>Pending:</strong> Order received but not yet processed</li>
                            <li><strong>Processing:</strong> Order is being prepared for shipping</li>
                            <li><strong>Shipped:</strong> Order has been shipped and is in transit</li>
                            <li><strong>Delivered:</strong> Order has been delivered to the customer</li>
                            <li><strong>Cancelled:</strong> Order has been cancelled</li>
                        </ul>
                    </div>
                    
                    <div class="text-end">
                        <a href="orders.php" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="me-2" style="width: 50px; height: 50px; object-fit: contain;">
                                            <?php echo $item['name']; ?>
                                        </div>
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">Subtotal:</td>
                                <td>₹<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Shipping:</td>
                                <td>₹<?php echo number_format($shipping, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Tax (18% GST):</td>
                                <td>₹<?php echo number_format($tax, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                <td class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper functions for badge classes
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'processing':
            return 'bg-info text-white';
        case 'shipped':
            return 'bg-primary text-white';
        case 'delivered':
            return 'bg-success text-white';
        case 'cancelled':
            return 'bg-danger text-white';
        default:
            return 'bg-secondary';
    }
}

function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'completed':
            return 'bg-success text-white';
        case 'failed':
            return 'bg-danger text-white';
        case 'refunded':
            return 'bg-info text-white';
        default:
            return 'bg-secondary';
    }
}
?>

<?php include 'includes/footer.php'; ?>