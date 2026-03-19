<?php include 'includes/header.php'; ?>

<?php
// Execute the SQL to update the categories table if not already done
$check_column_query = "SHOW COLUMNS FROM categories LIKE 'parent_id'";
$stmt = $db->prepare($check_column_query);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // Column doesn't exist, add it
    $alter_table_query = "ALTER TABLE categories 
                         ADD COLUMN parent_id INT DEFAULT NULL,
                         ADD CONSTRAINT fk_parent_category 
                         FOREIGN KEY (parent_id) REFERENCES categories(id) 
                         ON DELETE CASCADE";
    $db->exec($alter_table_query);
    
    // Add index for better performance
    $db->exec("CREATE INDEX idx_parent_id ON categories(parent_id)");
}

// Set default values for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Set default values for filtering
$search = isset($_GET['search']) ? $_GET['search'] : '';
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

// Build the query for subcategories
$query = "SELECT c.*, p.name as parent_name 
          FROM categories c 
          LEFT JOIN categories p ON c.parent_id = p.id 
          WHERE c.parent_id IS NOT NULL";

$params = [];

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ? OR p.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($parent_id > 0) {
    $query .= " AND c.parent_id = ?";
    $params[] = $parent_id;
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

// Add pagination to the query
$query .= " ORDER BY p.name, c.name LIMIT $offset, $records_per_page";

// Execute the query
$stmt = $db->prepare($query);

if (!empty($params)) {
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
}

$stmt->execute();
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all parent categories for filter
$parent_query = "SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name";
$parent_stmt = $db->prepare($parent_query);
$parent_stmt->execute();
$parent_categories = $parent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for success or error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear messages after displaying
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Subcategories Management</h4>
    <a href="add_subcategory.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add New Subcategory
    </a>
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

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search subcategories..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <label for="parent_id" class="form-label">Parent Category</label>
                <select class="form-select" id="parent_id" name="parent_id">
                    <option value="0">All Parent Categories</option>
                    <?php foreach ($parent_categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($parent_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-grid gap-2 w-100">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="subcategories.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Subcategories Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Subcategory Name</th>
                        <th>Parent Category</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($subcategories) > 0): ?>
                        <?php foreach ($subcategories as $subcategory): ?>
                            <?php
                            // Count products in this subcategory
                            $query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(1, $subcategory['id']);
                            $stmt->execute();
                            $product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <tr>
                                <td><?php echo $subcategory['id']; ?></td>
                                <td>
                                    <?php if (!empty($subcategory['image'])): ?>
                                        <img src="../assets/images/categories/<?php echo $subcategory['image']; ?>" alt="<?php echo $subcategory['name']; ?>" style="width: 50px; height: 50px; object-fit: contain;">
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $subcategory['name']; ?></td>
                                <td>
                                    <a href="categories.php?id=<?php echo $subcategory['parent_id']; ?>">
                                        <?php echo $subcategory['parent_name']; ?>
                                    </a>
                                </td>
                                <td><?php echo substr($subcategory['description'], 0, 100) . (strlen($subcategory['description']) > 100 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $product_count; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_subcategory.php?id=<?php echo $subcategory['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_subcategory.php?id=<?php echo $subcategory['id']; ?>" class="btn btn-sm btn-danger confirm-delete" data-bs-toggle="tooltip" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="../category.php?id=<?php echo $subcategory['id']; ?>" target="_blank" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No subcategories found</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&parent_id=<?php echo $parent_id; ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&parent_id=<?php echo $parent_id; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&parent_id=<?php echo $parent_id; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

