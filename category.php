<?php
include 'includes/header.php';

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$category_id = (int)$_GET['id'];

// Get category details
$query = "SELECT * FROM categories WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $category_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

$category = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default values for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Set default values for sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Build the query
$query = "SELECT * FROM products WHERE category_id = ?";

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY COALESCE(discount_price, price) ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY COALESCE(discount_price, price) DESC";
        break;
    case 'newest':
        $query .= " ORDER BY created_at DESC";
        break;
    case 'popularity':
        // This would ideally be based on sales or views, but for simplicity:
        $query .= " ORDER BY id DESC";
        break;
    default:
        $query .= " ORDER BY id DESC";
}

// Count total records for pagination
$count_query = $query;
$stmt = $db->prepare($count_query);
$stmt->bindParam(1, $category_id);
$stmt->execute();
$total_records = $stmt->rowCount();
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to the query
$query .= " LIMIT $offset, $records_per_page";

// Execute the query
$stmt = $db->prepare($query);
$stmt->bindParam(1, $category_id);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $category['name']; ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Categories</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php
                        // Get all categories
                        $query = "SELECT * FROM categories ORDER BY name";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($categories as $cat) {
                            $active = ($cat['id'] == $category_id) ? 'active' : '';
                            echo '<li class="list-group-item ' . $active . '">';
                            echo '<a href="category.php?id=' . $cat['id'] . '" class="text-decoration-none ' . ($active ? 'text-white' : 'text-dark') . '">' . $cat['name'] . '</a>';
                            echo '</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Price Range</h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                        <div class="mb-3">
                            <label for="min_price" class="form-label">Min Price</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" min="0" value="<?php echo isset($_GET['min_price']) ? (int)$_GET['min_price'] : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="max_price" class="form-label">Max Price</label>
                            <input type="number" class="form-control" id="max_price" name="max_price" min="0" value="<?php echo isset($_GET['max_price']) ? (int)$_GET['max_price'] : ''; ?>">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?php echo $category['name']; ?></h2>
                <div class="d-flex align-items-center">
                    <span class="me-2">Sort by:</span>
                    <select class="form-select" id="sort-select" onchange="window.location.href=this.value">
                        <option value="category.php?id=<?php echo $category_id; ?>&sort=default" <?php echo ($sort == 'default') ? 'selected' : ''; ?>>Default</option>
                        <option value="category.php?id=<?php echo $category_id; ?>&sort=price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="category.php?id=<?php echo $category_id; ?>&sort=price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="category.php?id=<?php echo $category_id; ?>&sort=newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="category.php?id=<?php echo $category_id; ?>&sort=popularity" <?php echo ($sort == 'popularity') ? 'selected' : ''; ?>>Popularity</option>
                    </select>
                </div>
            </div>
            
            <?php if (!empty($category['description'])): ?>
                <div class="alert alert-info mb-4">
                    <?php echo $category['description']; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($products) > 0): ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-6 mb-4">
                            <div class="product-card h-100 p-3">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                    <img src="assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="img-fluid product-image mb-3">
                                    <h6 class="product-title text-dark"><?php echo $product['name']; ?></h6>
                                </a>
                                <div class="price-section">
                                    <?php if ($product['discount_price']): ?>
                                        <?php $discount_percentage = round(($product['price'] - $product['discount_price']) / $product['price'] * 100); ?>
                                        <h5 class="mb-0">₹<?php echo number_format($product['discount_price'], 2); ?> <small class="original-price">₹<?php echo number_format($product['price'], 2); ?></small></h5>
                                        <span class="discount"><?php echo $discount_percentage; ?>% off</span>
                                    <?php else: ?>
                                        <h5 class="mb-0">₹<?php echo number_format($product['price'], 2); ?></h5>
                                    <?php endif; ?>
                                </div>
                                <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo ($product['discount_price'] ? $product['discount_price'] : $product['price']); ?>, '<?php echo $product['image']; ?>')" class="btn btn-primary btn-sm mt-2">Add to Cart</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $i; ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $category_id; ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No products found in this category.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

