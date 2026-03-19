<?php include 'includes/header.php'; ?>

<?php
// Process form submission
$errors = [];
$success = false;

// Get all parent categories
$query = "SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$parent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $parent_id = (int)$_POST['parent_id'];
    $description = trim($_POST['description']);
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Subcategory name is required';
    }
    
    if ($parent_id <= 0) {
        $errors[] = 'Please select a parent category';
    }
    
    // Check if subcategory name already exists under the same parent
    $query = "SELECT id FROM categories WHERE name = ? AND parent_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $parent_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Subcategory name already exists under this parent category';
    }
    
    // Handle image upload
    $image = 'default-category.jpg'; // Default image
    
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
                $image = $file_name;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }
    
    // If no errors, insert subcategory into database
    if (empty($errors)) {
        $query = "INSERT INTO categories (name, description, image, parent_id) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $image);
        $stmt->bindParam(4, $parent_id);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = 'Failed to add subcategory. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Add New Subcategory</h4>
    <div>
        <a href="subcategories.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Subcategories
        </a>
        <a href="add_category.php" class="btn btn-outline-primary">
            <i class="fas fa-plus me-1"></i> Add Parent Category
        </a>
    </div>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Subcategory has been added successfully.
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

<!-- Subcategory Form -->
<div class="card">
    <div class="card-body">
        <?php if (count($parent_categories) > 0): ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="parent_id" name="parent_id" required>
                                <option value="">Select Parent Category</option>
                                <?php foreach ($parent_categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($parent_id) && $parent_id == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="image" class="form-label">Subcategory Image</label>
                            <input type="file" class="form-control" id="image" name="image">
                            <div class="form-text">Recommended size: 500x500 pixels</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center">
                                        <img id="image-preview" src="../assets/images/categories/default-category.jpg" alt="Subcategory Image Preview" class="img-fluid mb-2" style="max-height: 200px;">
                                        <p class="text-muted small">Image Preview</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end mt-3">
                    <button type="reset" class="btn btn-secondary me-2">Reset</button>
                    <button type="submit" class="btn btn-primary">Add Subcategory</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                <h5 class="alert-heading">No Parent Categories Found!</h5>
                <p>You need to create at least one parent category before adding subcategories.</p>
                <hr>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="add_category.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Parent Category
                    </a>
                </div>
            </div>
        <?php endif; ?>
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

