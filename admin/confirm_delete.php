<?php include 'includes/header.php'; ?>

<?php
// Check if there's a confirmation request in session
if (!isset($_SESSION['confirm_delete'])) {
    header('Location: index.php');
    exit;
}

$confirm_data = $_SESSION['confirm_delete'];
$id = $confirm_data['id'];
$name = $confirm_data['name'];
$type = $confirm_data['type']; // 'category' or 'subcategory'

// Determine redirect URL based on type
$cancel_url = ($type == 'category') ? 'categories.php' : 'subcategories.php';
$confirm_url = ($type == 'category') ? "delete_category.php?id=$id&confirm=yes" : "delete_subcategory.php?id=$id&confirm=yes";

// Get additional data
$subcategory_count = isset($confirm_data['subcategory_count']) ? $confirm_data['subcategory_count'] : 0;
$product_count = isset($confirm_data['product_count']) ? $confirm_data['product_count'] : 0;
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Confirm Deletion</h4>
    <a href="<?php echo $cancel_url; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Cancel
    </a>
</div>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">Warning: This action cannot be undone!</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <h5 class="alert-heading">You are about to delete the following <?php echo $type; ?>:</h5>
            <p class="mb-0"><strong><?php echo htmlspecialchars($name); ?></strong></p>
        </div>
        
        <?php if ($type == 'category' && $subcategory_count > 0): ?>
            <div class="alert alert-danger">
                <p><strong>Warning:</strong> This category has <?php echo $subcategory_count; ?> subcategories that will also be deleted.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($product_count > 0): ?>
            <div class="alert alert-danger">
                <?php if ($type == 'category'): ?>
                    <p><strong>Warning:</strong> This category has <?php echo $product_count; ?> products that will be reassigned to the default category.</p>
                <?php else: ?>
                    <p><strong>Warning:</strong> This subcategory has <?php echo $product_count; ?> products that will be reassigned to the parent category.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <p>Are you sure you want to proceed with this deletion?</p>
        
        <div class="d-flex justify-content-end mt-4">
            <a href="<?php echo $cancel_url; ?>" class="btn btn-secondary me-2">Cancel</a>
            <a href="<?php echo $confirm_url; ?>" class="btn btn-danger">Yes, Delete</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

