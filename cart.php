<?php
include 'includes/header.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart items
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $cart_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
    
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    
    foreach ($cart_ids as $i => $id) {
        $stmt->bindValue($i + 1, $id);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $price = $product['discount_price'] ? $product['discount_price'] : $product['price'];
        $subtotal = $price * $quantity;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'stock' => $product['stock']
        ];
        
        $total += $subtotal;
    }
}

// Calculate shipping and tax
$shipping = 40; // Fixed shipping cost
$tax = $total * 0.18; // 18% GST
$grand_total = $total + $shipping + $tax;
?>

<div class="container my-5">
    <h2 class="mb-4">Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
            Your cart is empty. <a href="index.php">Continue shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="row cart-item mb-3 pb-3 border-bottom">
                                <div class="col-md-2 col-4">
                                    <img src="assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-fluid">
                                </div>
                                <div class="col-md-10 col-8">
                                    <div class="d-flex justify-content-between">
                                        <h5><a href="product.php?id=<?php echo $item['id']; ?>" class="text-decoration-none"><?php echo $item['name']; ?></a></h5>
                                        <a href="remove_from_cart.php?id=<?php echo $item['id']; ?>" class="text-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    <p class="text-success">₹<?php echo number_format($item['price'], 2); ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div class="input-group" style="width: 130px;">
                                            <a href="update_cart.php?id=<?php echo $item['id']; ?>&action=decrease" class="btn btn-outline-secondary">-</a>
                                            <input type="text" class="form-control text-center" value="<?php echo $item['quantity']; ?>" readonly>
                                            <a href="update_cart.php?id=<?php echo $item['id']; ?>&action=increase" class="btn btn-outline-secondary" <?php echo ($item['quantity'] >= $item['stock']) ? 'disabled' : ''; ?>>+</a>
                                        </div>
                                        <div class="fw-bold">
                                            ₹<?php echo number_format($item['subtotal'], 2); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($item['quantity'] >= $item['stock']): ?>
                                        <small class="text-danger">Maximum available quantity reached</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                            <a href="clear_cart.php" class="btn btn-outline-danger">
                                <i class="fas fa-trash me-2"></i>Clear Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>₹<?php echo number_format($shipping, 2); ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (18% GST)</span>
                            <span>₹<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold">₹<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-primary">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Accepted Payment Methods</h6>
                        <div class="d-flex gap-2 mt-2">
                            <i class="fab fa-cc-visa fa-2x text-primary"></i>
                            <i class="fab fa-cc-mastercard fa-2x text-danger"></i>
                            <i class="fab fa-cc-amex fa-2x text-info"></i>
                            <i class="fab fa-cc-paypal fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

