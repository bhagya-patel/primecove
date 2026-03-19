<?php
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'orders.php';
    header('Location: login.php');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details
$query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->bindParam(2, $user_id);
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

// Get order status history (if you have a status_history table)
$has_status_history = false;
$status_history = [];

// Check if the order can be cancelled
$can_cancel = in_array($order['order_status'], ['pending', 'processing']);

// Process order cancellation
if (isset($_POST['cancel_order']) && $can_cancel) {
    $query = "UPDATE orders SET order_status = 'cancelled' WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $order_id);
    $stmt->bindParam(2, $user_id);
    
    if ($stmt->execute()) {
        // Update order status in the current page data
        $order['order_status'] = 'cancelled';
        $can_cancel = false;
        
        // Add success message
        $success_message = "Your order has been cancelled successfully.";
    }
}
?>

<div class="container my-5">
    <!-- <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order #<?php echo $order_id; ?></li>
        </ol>
    </nav> -->
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Order #<?php echo $order_id; ?></h5>
            <div>
                <button class="btn btn-sm btn-light" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Invoice
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Order Information</h6>
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
            
            <?php if ($can_cancel): ?>
                <div class="mb-4">
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <button type="submit" name="cancel_order" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i> Cancel Order
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($has_status_history): ?>
                <div class="mb-4">
                    <h6>Order Status Timeline</h6>
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
            <?php endif; ?>
            
            <h6>Order Items</h6>
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
                                        <img src="assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="me-2" style="width: 50px; height: 50px; object-fit: contain;">
                                        <div>
                                            <a href="product.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none">
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
    
    <?php if ($order['order_status'] == 'delivered'): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Write a Review</h5>
            </div>
            <div class="card-body">
                <p>Share your experience with the products you purchased.</p>
                <div class="row">
                    <?php foreach ($order_items as $item): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="me-2" style="width: 50px; height: 50px; object-fit: contain;">
                                        <div>
                                            <h6 class="mb-0"><?php echo $item['name']; ?></h6>
                                        </div>
                                    </div>
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>#reviews" class="btn btn-outline-primary btn-sm">
                                        Write a Review
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="text-center mt-4">
        <a href="orders.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i> Back to My Orders
        </a>
    </div>
</div>

<style>
    @media print {
        .navbar, .bg-light, .footer, .btn, form, .breadcrumb {
            display: none !important;
        }
        .container {
            width: 100% !important;
            max-width: 100% !important;
        }
        .card {
            border: none !important;
        }
        .card-header {
            background-color: #f8f9fa !important;
            color: #000 !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>

