<?php
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get product details
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product or quantity']);
        exit;
    }
    
    // Add to cart
    if (isset($_SESSION['cart'][$product_id])) {
        // Update quantity if already in cart
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        // Add new item to cart
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success', 
        'message' => 'Product added to cart', 
        'cart_count' => count($_SESSION['cart'])
    ]);
    exit;
}

// Redirect to home if accessed directly
header('Location: index.php');
exit;
?>

