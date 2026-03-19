<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: categories.php');
    exit;
}

$category_id = (int)$_GET['id'];

// Include database connection
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get category details
$query = "SELECT * FROM categories WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $category_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    $_SESSION['error_message'] = 'Invalid category ID.';
    header('Location: categories.php');
    exit;
}

$category = $stmt->fetch(PDO::FETCH_ASSOC);
$image = $category['image'];

// Check for subcategories
$query = "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $category_id);
$stmt->execute();
$subcategory_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get products count for this category
$query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $category_id);
$stmt->execute();
$product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// If confirmation is not provided, ask for confirmation
if (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes') {
    $_SESSION['confirm_delete'] = [
        'id' => $category_id,
        'name' => $category['name'],
        'subcategory_count' => $subcategory_count,
        'product_count' => $product_count,
        'type' => 'category'
    ];
    header('Location: confirm_delete.php');
    exit;
}

// Start transaction
$db->beginTransaction();

try {
    // Delete all subcategories
    if ($subcategory_count > 0) {
        // Get subcategory images first
        $query = "SELECT id, image FROM categories WHERE parent_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete subcategory images
        foreach ($subcategories as $subcategory) {
            if ($subcategory['image'] != 'default-category.jpg' && file_exists('../assets/images/categories/' . $subcategory['image'])) {
                unlink('../assets/images/categories/' . $subcategory['image']);
            }
        }
        
        // Delete all subcategories
        $query = "DELETE FROM categories WHERE parent_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
    }
    
    // Reassign products to default category or delete them
    if ($product_count > 0) {
        // Option 1: Reassign products to a default category (category ID 1)
        $query = "UPDATE products SET category_id = 1 WHERE category_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        
        // Option 2: Delete products (uncomment if you want to delete instead)
        /*
        $query = "DELETE FROM products WHERE category_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        */
    }
    
    // Delete the category
    $query = "DELETE FROM categories WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $category_id);
    $stmt->execute();
    
    // Delete category image if it's not the default
    if (!empty($image) && $image != 'default-category.jpg') {
        $image_path = '../assets/images/categories/' . $image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Commit transaction
    $db->commit();
    
    $_SESSION['success_message'] = 'Category has been deleted successfully. ' . 
                                   ($subcategory_count > 0 ? $subcategory_count . ' subcategories were also deleted. ' : '') .
                                   ($product_count > 0 ? $product_count . ' products were reassigned to the default category.' : '');
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    $_SESSION['error_message'] = 'Failed to delete category: ' . $e->getMessage();
}

// Redirect back to categories page
header('Location: categories.php');
exit;
?>

