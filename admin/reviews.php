<?php include 'includes/header.php'; ?>

<?php
// Set default values for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Set default values for filtering
$search = isset($_GET['search']) ? $_GET['search'] : '';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Build the query
$query = "SELECT r.*, p.name as product_name, u.name as user_name, u.email as user_email 
          FROM reviews r 
          JOIN products p ON r.product_id = p.id 
          JOIN users u ON r.user_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR r.comment LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($rating_filter > 0) {
    $query .= " AND r.rating = ?";
    $params[] = $rating_filter;
}

if ($product_id > 0) {
    $query .= " AND r.product_id = ?";
    $params[] = $product_id;
}

// Count total records for pagination
$count_query = $query;
$stmt = $db->prepare($count_query);

if (!empty($params)) {
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
}

$stmt->execute();
$total_records = $stmt->rowCount();
$total_pages = ceil($total_records / $records_per_page);

// Add sorting and pagination to the query
$query .= " ORDER BY r.created_at DESC LIMIT $offset, $records_per_page";

// Execute the query
$stmt = $db->prepare($query);

if (!empty($params)) {
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
}

$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products for filter
$product_query = "SELECT id, name FROM products ORDER BY name";
$product_stmt = $db->prepare($product_query);
$product_stmt->execute();
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for success or error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear messages after displaying
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Reviews Management</h4>
</div>

<!-- Success Message -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Error Message -->
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Product, User, Comment" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label for="product_id" class="form-label">Product</label>
                <select class="form-select" id="product_id" name="product_id">
                    <option value="0">All Products</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo ($product_id == $product['id']) ? 'selected' : ''; ?>>
                            <?php echo $product['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="rating" class="form-label">Rating</label>
                <select class="form-select" id="rating" name="rating">
                    <option value="0">All Ratings</option>
                    <option value="5" <?php echo ($rating_filter == 5) ? 'selected' : ''; ?>>5 Stars</option>
                    <option value="4" <?php echo ($rating_filter == 4) ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="3" <?php echo ($rating_filter == 3) ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="2" <?php echo ($rating_filter == 2) ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="1" <?php echo ($rating_filter == 1) ? 'selected' : ''; ?>>1 Star</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="reviews.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Reviews Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo $review['id']; ?></td>
                                <td>
                                    <a href="../product.php?id=<?php echo $review['product_id']; ?>" target="_blank">
                                        <?php echo $review['product_name']; ?>
                                    </a>
                                </td>
                                <td>
                                    <div><?php echo $review['user_name']; ?></div>
                                    <small class="text-muted"><?php echo $review['user_email']; ?></small>
                                </td>
                                <td>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </td>
                                <td><?php echo substr(htmlspecialchars($review['comment']), 0, 50) . (strlen($review['comment']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo date('d M Y', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewReviewModal<?php echo $review['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="delete_review.php?id=<?php echo $review['id']; ?>" class="btn btn-sm btn-danger confirm-delete" data-bs-toggle="tooltip" title="Delete Review">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- View Review Modal -->
                                    <div class="modal fade" id="viewReviewModal<?php echo $review['id']; ?>" tabindex="-1" aria-labelledby="viewReviewModalLabel<?php echo $review['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewReviewModalLabel<?php echo $review['id']; ?>">Review Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6>Product</h6>
                                                            <p>
                                                                <a href="../product.php?id=<?php echo $review['product_id']; ?>" target="_blank">
                                                                    <?php echo $review['product_name']; ?>
                                                                </a>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>User</h6>
                                                            <p>
                                                                <a href="view_user.php?id=<?php echo $review['user_id']; ?>">
                                                                    <?php echo $review['user_name']; ?> (<?php echo $review['user_email']; ?>)
                                                                </a>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6>Rating</h6>
                                                            <p>
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <?php if ($i <= $review['rating']): ?>
                                                                        <i class="fas fa-star text-warning"></i>
                                                                    <?php else: ?>
                                                                        <i class="far fa-star text-warning"></i>
                                                                    <?php endif; ?>
                                                                <?php endfor; ?>
                                                                (<?php echo $review['rating']; ?> out of 5)
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Date</h6>
                                                            <p><?php echo date('d M Y, h:i A', strtotime($review['created_at'])); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="mb-0">
                                                        <h6>Comment</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <a href="delete_review.php?id=<?php echo $review['id']; ?>" class="btn btn-danger confirm-delete">
                                                        <i class="fas fa-trash me-1"></i> Delete Review
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No reviews found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&rating=<?php echo $rating_filter; ?>&product_id=<?php echo $product_id; ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&rating=<?php echo $rating_filter; ?>&product_id=<?php echo $product_id; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&rating=<?php echo $rating_filter; ?>&product_id=<?php echo $product_id; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

