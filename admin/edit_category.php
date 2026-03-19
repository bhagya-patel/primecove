<?php include 'includes/header.php'; ?>

<?php
// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: categories.php');
    exit;
}

$category_id = (int)$_GET['id'];

// Get category details
$query = "SELECT * FROM categories WHERE id = ? AND parent_id IS NULL";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $category_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: categories.php');
    exit;
}

$category = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Category name is required';
    }
    
    // Check if category name already exists (excluding current category)
    $query = "SELECT id FROM categories WHERE name = ? AND id != ? AND parent_id IS NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $category_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Category name already exists';
    }
    
    // Handle image upload
    $image = $category['image']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, and GIF are allowed';
        } else {
            $file_name = time() . '_' . $_FILES['image']['name'];
            $upload_dir = '../assets/images/categories/';
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if it's not the default
                if ($image != 'default-category.jpg' && file_exists($upload_dir . $image)) {
                    unlink($upload_dir . $image);
                }
                $image = $file_name;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }
    
    // If no errors, update category in database
    if (empty($errors)) {
        $query = "UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $image);
        $stmt->bindParam(4, $category_id);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Refresh category data
            $query = "SELECT * FROM categories WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $category_id);
            $stmt->execute();
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = 'Failed to update category. Please try again.';
        }
    }
}

// Get subcategories for this category
$query = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $category_id);
$stmt->execute();
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Edit Category</h4>
    <a href="categories.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Categories
    </a>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Category has been updated successfully.
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
    <div class="col-md-8">
        <!-- Category Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Category Details</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($category['name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($category['description']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="image" class="form-label">Category Image</label>
                                <input type="file" class="form-control" id="image" name="image">
                                <div class="form-text">Recommended size: 500x500 pixels</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="text-center">
                                            <img id="image-preview" src="../assets/images/categories/<?php echo $category['image']; ?>" alt="Category Image Preview" class="img-fluid mb-2" style="max-height: 200px;">
                                            <p class="text-muted small">Current Image</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mt-3">
                        <a href="categories.php" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Subcategories List -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Subcategories</h5>
                <a href="add_subcategory.php?parent_id=<?php echo $category_id; ?>" class="btn btn-sm btn-light">
                    <i class="fas fa-plus me-1"></i> Add
                </a>
            </div>
            <div class="card-body">
                <?php if (count($subcategories) > 0): ?>
                    <ul class="list-group">
                        <?php foreach ($subcategories as $subcategory): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if (!empty($subcategory['image'])): ?>
                                        <img src="../assets/images/categories/<?php echo $subcategory['image']; ?>" alt="<?php echo $subcategory['name']; ?>" class="me-2" style="width: 30px; height: 30px; object-fit: contain;">
                                    <?php endif; ?>
                                    <?php echo $subcategory['name']; ?>
                                </div>
                                <div>
                                    <a href="edit_subcategory.php?id=<?php echo $subcategory['id']; ?>" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_subcategory.php?id=<?php echo $subcategory['id']; ?>" class="btn btn-sm btn-danger confirm-delete" data-bs-toggle="tooltip" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        No subcategories found for this category.
                        <a href="add_subcategory.php?parent_id=<?php echo $category_id; ?>" class="alert-link">Add a subcategory</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Image preview
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('image-preview').src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>

