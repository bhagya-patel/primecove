<?php
include 'includes/header.php';

// Check if search query is provided
if (!isset($_GET['query']) || empty($_GET['query'])) {
    header('Location: index.php');
    exit;
}

$search_query = trim($_GET['query']);

// Set default values for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Set default values for sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// Build the query
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?";

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY COALESCE(p.discount_price, p.price) ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY COALESCE(p.discount_price, p.price) DESC";
        break;
    case 'newest':
        $query .= " ORDER BY p.created_at DESC";
        break;
    default:
        $query .= " ORDER BY p.id DESC";
}

// Count total records for pagination
$count_query = $query;
$stmt = $db->prepare($count_query);
$search_param = "%$search_query%";
$stmt->bindParam(1, $search_param);
$stmt->bindParam(2, $search_param);
$stmt->bindParam(3, $search_param);
$stmt->execute();
$total_records = $stmt->rowCount();
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to the query
$query .= " LIMIT $offset, $records_per_page";

// Execute the query
$stmt = $db->prepare($query);
$stmt->bindParam(1, $search_param);
$stmt->bindParam(2, $search_param);
$stmt->bindParam(3, $search_param);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Search Results</li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
        <div class="d-flex align-items-center">
            <span class="me-2">Sort by:</span>
            <select class="form-select" id="sort-select" onchange="window.location.href=this.value">
                <option value="search.php?query=<?php echo urlencode($search_query); ?>&sort=default" <?php echo ($sort == 'default') ? 'selected' : ''; ?>>Default</option>
                <option value="search.php?query=<?php echo urlencode($search_query); ?>&sort=price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="search.php?query=<?php echo urlencode($search_query); ?>&sort=price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="search.php?query=<?php echo urlencode($search_query); ?>&sort=newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
            </select>
        </div>
    </div>
    
    <p>Found <?php echo $total_records; ?> result(s) for your search.</p>
    
    <?php if (count($products) > 0): ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-3 col-6 mb-4">
                    <div class="product-card h-100 p-3">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                            <img src="assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="img-fluid product-image mb-3">
                            <h6 class="product-title text-dark"><?php echo $product['name']; ?></h6>
                        </a>
                        <div class="text-muted small mb-2"><?php echo $product['category_name']; ?></div>
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
                        <a class="page-link" href="?query=<?php echo urlencode($search_query); ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?query=<?php echo urlencode($search_query); ?>&page=<?php echo $i; ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?query=<?php echo urlencode($search_query); ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            No products found matching your search criteria. Try different keywords or <a href="index.php">browse our categories</a>.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

