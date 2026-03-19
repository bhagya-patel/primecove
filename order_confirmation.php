<?php
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
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
    header('Location: index.php');
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
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success fa-5x"></i>
                    </div>
                    <h2 class="mb-3">Thank You for Your Order!</h2>
                    <p class="lead mb-4">Your order has been placed successfully.</p>
                    <div class="d-flex justify-content-center mb-4">
                        <div class="bg-light px-4 py-2 rounded">
                            <span class="text-muted">Order ID:</span>
                            <span class="fw-bold">#<?php echo $order_id; ?></span>
                        </div>
                    </div>
                    <p>We've sent a confirmation email to <strong><?php echo $_SESSION['user_email']; ?></strong> with the order details.</p>
                    <div class="mt-4">
   <!-- -------------------------------------------------------------------------------------------------------------------- -->
                    <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn btn-primary me-2">View Order Details</a>
                        <a href="index.php" class="btn btn-outline-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p class="mb-1"><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> <?php echo ($order['payment_method'] == 'cod') ? 'Cash on Delivery' : 'Online Payment'; ?></p>
                            <p class="mb-0"><strong>Status:</strong> <?php echo ucfirst($order['order_status']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Shipping Address</h6>
                            <address>
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </address>
                        </div>
                    </div>
                    
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
                                                <?php echo $item['name']; ?>
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>

