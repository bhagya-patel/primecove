<?php
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'wishlist.php';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Process add to wishlist
if (isset($_GET['add']) && !empty($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    
    // Check if product exists
    $query = "SELECT id FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $product_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Check if already in wishlist
        $query = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $product_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Add to wishlist
            $query = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $product_id);
            $stmt->execute();
        }
    }
    
    // Redirect to remove the GET parameter
    header('Location: wishlist.php');
    exit;
}

// Process remove from wishlist
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $wishlist_id = (int)$_GET['remove'];
    
    // Remove from wishlist
    $query = "DELETE FROM wishlist WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $wishlist_id);
    $stmt->bindParam(2, $user_id);
    $stmt->execute();
    
    // Redirect to remove the GET parameter
    header('Location: wishlist.php');
    exit;
}

// Get wishlist items
$query = "SELECT w.id as wishlist_id, p.* FROM wishlist w 
          JOIN products p ON w.product_id = p.id 
          WHERE w.user_id = ? 
          ORDER BY w.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5">
    <h2 class="mb-4">My Wishlist</h2>
    
    <?php if (empty($wishlist_items)): ?>
        <div class="alert alert-info">
            Your wishlist is empty. <a href="index.php">Continue shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-md-3 col-6 mb-4">
                    <div class="product-card h-100 p-3">
                        <div class="position-absolute top-0 end-0 p-2">
                            <a href="wishlist.php?remove=<?php echo $item['wishlist_id']; ?>" class="text-danger" title="Remove from wishlist">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                        <a href="product.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                            <img src="assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="img-fluid product-image mb-3">
                            <h6 class="product-title text-dark"><?php echo $item['name']; ?></h6>
                        </a>
                        <div class="price-section">
                            <?php if ($item['discount_price']): ?>
                                <?php $discount_percentage = round(($item['price'] - $item['discount_price']) / $item['price'] * 100); ?>
                                <h5 class="mb-0">₹<?php echo number_format($item['discount_price'], 2); ?> <small class="original-price">₹<?php echo number_format($item['price'], 2); ?></small></h5>
                                <span class="discount"><?php echo $discount_percentage; ?>% off</span>
                            <?php else: ?>
                                <h5 class="mb-0">₹<?php echo number_format($item['price'], 2); ?></h5>
                            <?php endif; ?>
                        </div>
                        <button onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo ($item['discount_price'] ? $item['discount_price'] : $item['price']); ?>, '<?php echo $item['image']; ?>')" class="btn btn-primary btn-sm mt-2">Add to Cart</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

