<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Include database connection
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get product image before deleting
$query = "SELECT image FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $image = $product['image'];
    
    // Delete product from database
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $product_id);
    
    if ($stmt->execute()) {
        // Delete product image if it's not the default
        if ($image != 'default-product.jpg') {
            $image_path = '../assets/images/products/' . $image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Set success message
        $_SESSION['success_message'] = 'Product has been deleted successfully.';
    } else {
        // Set error message
        $_SESSION['error_message'] = 'Failed to delete product. Please try again.';
    }
}

// Redirect back to products page
header('Location: products.php');
exit;
?>

