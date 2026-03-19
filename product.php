<?php
include 'includes/header.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = $_GET['id'];

// Fetch product details
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate discount percentage if applicable
$discount_percentage = 0;
if ($product['discount_price']) {
    $discount_percentage = round(($product['price'] - $product['discount_price']) / $product['price'] * 100);
}

// Fetch related products
$query = "SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product['category_id']);
$stmt->bindParam(2, $product_id);
$stmt->execute();
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch product reviews
$query = "SELECT r.*, u.name as user_name FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.product_id = ? 
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating = 0;
if (count($reviews) > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $avg_rating = round($total_rating / count($reviews), 1);
}
?>

<!-- Breadcrumb -->
<!-- <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="category.php?id=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo $product['name']; ?></li>
    </ol>
</nav> -->

<!-- Product Details -->
<div class="row product-details mb-4">
    <div class="col-md-5 mb-4 mb-md-0">
        <img src="assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="img-fluid product-details-image" id="main-product-image">
        
        <!-- Product Thumbnails (if you have multiple images) -->
        <div class="row mt-3">
            <div class="col-3">
                <img src="assets/images/products/<?php echo $product['image']; ?>" alt="Thumbnail" class="img-fluid product-thumbnail border">
            </div>
            <!-- Add more thumbnails if needed -->
        </div>
    </div>
    <div class="col-md-7">
        <h2><?php echo $product['name']; ?></h2>
        
        <!-- Rating -->
        <div class="mb-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <?php if ($i <= round($avg_rating)): ?>
                    <i class="fas fa-star text-warning"></i>
                <?php else: ?>
                    <i class="far fa-star text-warning"></i>
                <?php endif; ?>
            <?php endfor; ?>
            <span class="ms-2"><?php echo $avg_rating; ?> (<?php echo count($reviews); ?> reviews)</span>
        </div>
        
        <!-- Price -->
        <div class="price-section mb-3">
            <?php if ($product['discount_price']): ?>
                <h3 class="mb-0">₹<?php echo number_format($product['discount_price'], 2); ?> 
                    <small class="original-price">₹<?php echo number_format($product['price'], 2); ?></small>
                </h3>
                <span class="discount"><?php echo $discount_percentage; ?>% off</span>
            <?php else: ?>
                <h3 class="mb-0">₹<?php echo number_format($product['price'], 2); ?></h3>
            <?php endif; ?>
        </div>
        
        <!-- Stock -->
        <p class="mb-3">
            <?php if ($product['stock'] > 0): ?>
                <span class="badge bg-success">In Stock</span>
            <?php else: ?>
                <span class="badge bg-danger">Out of Stock</span>
            <?php endif; ?>
        </p>
        
        <!-- Description -->
        <div class="mb-4">
            <h5>Description</h5>
            <p><?php echo $product['description']; ?></p>
        </div>
        
        <!-- Add to Cart -->
        <?php if ($product['stock'] > 0): ?>
            <div class="d-flex align-items-center mb-4">
                <div class="input-group me-3" style="width: 130px;">
                    <button class="btn btn-outline-secondary" type="button" id="decrease-qty">-</button>
                    <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                    <button class="btn btn-outline-secondary" type="button" id="increase-qty">+</button>
                </div>
                <button onclick="addToCartWithQty(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo ($product['discount_price'] ? $product['discount_price'] : $product['price']); ?>, '<?php echo $product['image']; ?>')" class="btn btn-primary">
                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                </button>
            </div>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>Out of Stock</button>
        <?php endif; ?>
        
        <!-- Additional Info -->
        <div class="mt-4">
            <p><strong>Category:</strong> <?php echo $product['category_name']; ?></p>
            <p><strong>SKU:</strong> PROD-<?php echo $product['id']; ?></p>
            <div class="mt-3">
                <button class="btn btn-outline-primary me-2"><i class="far fa-heart me-1"></i>Add to Wishlist</button>
                <button class="btn btn-outline-primary"><i class="fas fa-share-alt me-1"></i>Share</button>
            </div>
        </div>
    </div>
</div>

<!-- Product Reviews -->
<div class="bg-white p-4 rounded shadow mb-4">
    <h4 class="mb-3">Customer Reviews</h4>
    
    <?php if (count($reviews) > 0): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between">
                    <h5><?php echo $review['user_name']; ?></h5>
                    <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                </div>
                <div class="mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= $review['rating']): ?>
                            <i class="fas fa-star text-warning"></i>
                        <?php else: ?>
                            <i class="far fa-star text-warning"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <p><?php echo $review['comment']; ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No reviews yet. Be the first to review this product!</p>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <h5 class="mt-4">Write a Review</h5>
        <form action="submit_review.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <div class="mb-3">
                <label for="rating" class="form-label">Rating</label>
                <select class="form-select" id="rating" name="rating" required>
                    <option value="">Select Rating</option>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Very Good</option>
                    <option value="3">3 - Good</option>
                    <option value="2">2 - Fair</option>
                    <option value="1">1 - Poor</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="comment" class="form-label">Your Review</label>
                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
        </form>
    <?php else: ?>
        <p class="mt-4"><a href="login.php">Login</a> to write a review.</p>
    <?php endif; ?>
</div>

<!-- Related Products -->
<div class="bg-white p-4 rounded shadow mb-4">
    <h4 class="mb-3">Related Products</h4>
    <div class="row">
        <?php foreach ($related_products as $related): ?>
            <div class="col-6 col-md-3 mb-4">
                <div class="product-card h-100 p-3">
                    <a href="product.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                        <img src="assets/images/products/<?php echo $related['image']; ?>" alt="<?php echo $related['name']; ?>" class="img-fluid product-image mb-3">
                        <h6 class="product-title text-dark"><?php echo $related['name']; ?></h6>
                    </a>
                    <div class="price-section">
                        <?php if ($related['discount_price']): ?>
                            <?php $rel_discount = round(($related['price'] - $related['discount_price']) / $related['price'] * 100); ?>
                            <h5 class="mb-0">₹<?php echo number_format($related['discount_price'], 2); ?> 
                                <small class="original-price">₹<?php echo number_format($related['price'], 2); ?></small>
                            </h5>
                            <span class="discount"><?php echo $rel_discount; ?>% off</span>
                        <?php else: ?>
                            <h5 class="mb-0">₹<?php echo number_format($related['price'], 2); ?></h5>
                        <?php endif; ?>
                    </div>
                    <button onclick="addToCart(<?php echo $related['id']; ?>, '<?php echo addslashes($related['name']); ?>', <?php echo ($related['discount_price'] ? $related['discount_price'] : $related['price']); ?>, '<?php echo $related['image']; ?>')" class="btn btn-primary btn-sm mt-2">Add to Cart</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Quantity increment/decrement
    document.getElementById('increase-qty').addEventListener('click', function() {
        var input = document.getElementById('quantity');
        var max = parseInt(input.getAttribute('max'));
        var value = parseInt(input.value);
        
        if (value < max) {
            input.value = value + 1;
        }
    });
    
    document.getElementById('decrease-qty').addEventListener('click', function() {
        var input = document.getElementById('quantity');
        var value = parseInt(input.value);
        
        if (value > 1) {
            input.value = value - 1;
        }
    });
    
    // Add to cart with quantity
    function addToCartWithQty(productId, productName, productPrice, productImage) {
        var quantity = document.getElementById('quantity').value;
        
        $.ajax({
            url: 'add_to_cart.php',
            type: 'POST',
            data: {
                product_id: productId,
                product_name: productName,
                product_price: productPrice,
                product_image: productImage,
                quantity: quantity
            },
            success: function(response) {
                const result = JSON.parse(response);
                
                if (result.status === 'success') {
                    alert('Product added to cart successfully!');
                    updateCartCount(result.cart_count);
                } else {
                    alert('Failed to add product to cart. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again later.');
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>

