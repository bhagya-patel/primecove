<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if subcategory ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: subcategories.php');
    exit;
}

$subcategory_id = (int)$_GET['id'];

// Include database connection
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Check if it's a subcategory (has parent_id)
$query = "SELECT * FROM categories WHERE id = ? AND parent_id IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $subcategory_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    $_SESSION['error_message'] = 'Invalid subcategory ID.';
    header('Location: subcategories.php');
    exit;
}

$subcategory = $stmt->fetch(PDO::FETCH_ASSOC);
$image = $subcategory['image'];

// Get products count for this subcategory
$query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $subcategory_id);
$stmt->execute();
$product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// If products exist, reassign them to parent category
if ($product_count > 0) {
    $query = "UPDATE products SET category_id = ? WHERE category_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $subcategory['parent_id']);
    $stmt->bindParam(2, $subcategory_id);
    $stmt->execute();
}

// Delete subcategory
$query = "DELETE FROM categories WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $subcategory_id);

if ($stmt->execute()) {
    // Delete subcategory image if it's not the default
    if ($image != 'default-category.jpg') {
        $image_path = '../assets/images/categories/' . $image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $_SESSION['success_message'] = 'Subcategory has been deleted successfully. ' . 
                                   ($product_count > 0 ? $product_count . ' products were reassigned to the parent category.' : '');
} else {
    $_SESSION['error_message'] = 'Failed to delete subcategory. Please try again.';
}

// Redirect back to subcategories page
header('Location: subcategories.php');
exit;
?>

