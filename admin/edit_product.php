<?php include 'includes/header.php'; ?>

<?php
// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Location: products.php');
  exit;
}

$product_id = (int)$_GET['id'];

// Get product details
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
  header('Location: products.php');
  exit;
}

$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate and sanitize input
  $name = trim($_POST['name']);
  $category_id = (int)$_POST['category_id'];
  $description = trim($_POST['description']);
  $price = (float)$_POST['price'];
  $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
  $stock = (int)$_POST['stock'];
  
  // Validation
  if (empty($name)) {
      $errors[] = 'Product name is required';
  }
  
  if ($category_id <= 0) {
      $errors[] = 'Please select a valid category';
  }
  
  if ($price <= 0) {
      $errors[] = 'Price must be greater than zero';
  }
  
  if ($discount_price !== null && $discount_price >= $price) {
      $errors[] = 'Discount price must be less than regular price';
  }
  
  if ($stock < 0) {
      $errors[] = 'Stock cannot be negative';
  }
  
  // Handle image upload
  $image = $product['image']; // Keep existing image by default
  
  if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
      $file_type = $_FILES['image']['type'];
      
      if (!in_array($file_type, $allowed_types)) {
          $errors[] = 'Invalid file type. Only JPG, PNG, and GIF are allowed';
      } else {
          $file_name = time() . '_' . $_FILES['image']['name'];
          $upload_dir = '../assets/images/products/';
          $upload_path = $upload_dir . $file_name;
          
          if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
              // Delete old image if it's not the default
              if ($image != 'default-product.jpg' && file_exists($upload_dir . $image)) {
                  unlink($upload_dir . $image);
              }
              $image = $file_name;
          } else {
              $errors[] = 'Failed to upload image';
          }
      }
  }
  
  // If no errors, update product in database
  if (empty($errors)) {
      $query = "UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, discount_price = ?, stock = ?, image = ? WHERE id = ?";
      $stmt = $db->prepare($query);
      $stmt->bindParam(1, $category_id);
      $stmt->bindParam(2, $name);
      $stmt->bindParam(3, $description);
      $stmt->bindParam(4, $price);
      $stmt->bindParam(5, $discount_price);
      $stmt->bindParam(6, $stock);
      $stmt->bindParam(7, $image);
      $stmt->bindParam(8, $product_id);
      
      if ($stmt->execute()) {
          $success = true;
          
          // Refresh product data
          $query = "SELECT * FROM products WHERE id = ?";
          $stmt = $db->prepare($query);
          $stmt->bindParam(1, $product_id);
          $stmt->execute();
          $product = $stmt->fetch(PDO::FETCH_ASSOC);
      } else {
          $errors[] = 'Failed to update product. Please try again.';
      }
  }
}

// Get all categories (including subcategories)
$query = "SELECT id, name, parent_id FROM categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize categories into parent and subcategories
$categories = [];
$subcategories = [];

foreach ($all_categories as $cat) {
    if ($cat['parent_id'] === null) {
        $categories[] = $cat;
    } else {
        $subcategories[$cat['parent_id']][] = $cat;
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Edit Product</h4>
  <a href="products.php" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-2"></i>Back to Products
  </a>
</div>

<!-- Success Message -->
<?php if ($success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Success!</strong> Product has been updated successfully.
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

<!-- Product Form -->
<div class="card">
  <div class="card-body">
      <form action="" method="POST" enctype="multipart/form-data">
          <div class="row">
              <div class="col-md-8">
                  <div class="mb-3">
                      <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($product['name']); ?>">
                  </div>
                  
                  <div class="mb-3">
                      <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                      <select class="form-select" id="category_id" name="category_id" required>
                          <option value="">Select Category</option>
                          <?php foreach ($categories as $category): ?>
                              <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                  <!-- Parent category as an option -->
                                  <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                      <?php echo htmlspecialchars($category['name']); ?> (Main)
                                  </option>
                                  
                                  <!-- Subcategories -->
                                  <?php if (isset($subcategories[$category['id']])): ?>
                                      <?php foreach ($subcategories[$category['id']] as $subcategory): ?>
                                          <option value="<?php echo $subcategory['id']; ?>" <?php echo ($product['category_id'] == $subcategory['id']) ? 'selected' : ''; ?>>
                                              &nbsp;&nbsp;<?php echo htmlspecialchars($subcategory['name']); ?>
                                          </option>
                                      <?php endforeach; ?>
                                  <?php endif; ?>
                              </optgroup>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  
                  <div class="mb-3">
                      <label for="description" class="form-label">Description</label>
                      <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                  </div>
              </div>
              
              <div class="col-md-4">
                  <div class="mb-3">
                      <label for="image" class="form-label">Product Image</label>
                      <input type="file" class="form-control" id="image" name="image">
                      <div class="form-text">Recommended size: 500x500 pixels</div>
                      
                      <?php if (!empty($product['image'])): ?>
                          <div class="mt-2">
                              <p>Current Image:</p>
                              <img src="../assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="img-thumbnail" style="max-width: 150px;">
                          </div>
                      <?php endif; ?>
                  </div>
                  
                  <div class="mb-3">
                      <label for="price" class="form-label">Price (₹) <span class="text-danger">*</span></label>
                      <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required value="<?php echo $product['price']; ?>">
                  </div>
                  
                  <div class="mb-3">
                      <label for="discount_price" class="form-label">Discount Price (₹)</label>
                      <input type="number" class="form-control" id="discount_price" name="discount_price" step="0.01" min="0" value="<?php echo $product['discount_price']; ?>">
                      <div class="form-text">Leave empty if no discount</div>
                  </div>
                  
                  <div class="mb-3">
                      <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                      <input type="number" class="form-control" id="stock" name="stock" min="0" required value="<?php echo $product['stock']; ?>">
                  </div>
              </div>
          </div>
          
          <div class="text-end mt-3">
              <a href="products.php" class="btn btn-secondary me-2">Cancel</a>
              <button type="submit" class="btn btn-primary">Update Product</button>
          </div>
      </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

