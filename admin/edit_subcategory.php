<?php include 'includes/header.php'; ?>

<?php
// Check if subcategory ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: subcategories.php');
    exit;
}

$subcategory_id = (int)$_GET['id'];

// Get subcategory details
$query = "SELECT * FROM categories WHERE id = ? AND parent_id IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $subcategory_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: subcategories.php');
    exit;
}

$subcategory = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all parent categories
$query = "SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$parent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
$errors = [];
$success = false;

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
    
    // Check if subcategory name already exists under the same parent (excluding current subcategory)
    $query = "SELECT id FROM categories WHERE name = ? AND parent_id = ? AND id != ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $parent_id);
    $stmt->bindParam(3, $subcategory_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Subcategory name already exists under this parent category';
    }
    
    // Handle image upload
    $image = $subcategory['image']; // Keep existing image by default
    
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
    
    // If no errors, update subcategory in database
    if (empty($errors)) {
        $query = "UPDATE categories SET name = ?, description = ?, image = ?, parent_id = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $image);
        $stmt->bindParam(4, $parent_id);
        $stmt->bindParam(5, $subcategory_id);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Refresh subcategory data
            $query = "SELECT * FROM categories WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $subcategory_id);
            $stmt->execute();
            $subcategory = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = 'Failed to update subcategory. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Edit Subcategory</h4>
    <a href="subcategories.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Subcategories
    </a>
</div>

<!-- Success Message -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Subcategory has been updated successfully.
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
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="parent_id" name="parent_id" required>
                            <option value="">Select Parent Category</option>
                            <?php foreach ($parent_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($subcategory['parent_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($subcategory['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($subcategory['description']); ?></textarea>
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
                                    <img id="image-preview" src="../assets/images/categories/<?php echo $subcategory['image']; ?>" alt="Subcategory Image Preview" class="img-fluid mb-2" style="max-height: 200px;">
                                    <p class="text-muted small">Current Image</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-3">
                <a href="subcategories.php" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Subcategory</button>
            </div>
        </form>
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

