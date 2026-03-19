<?php
session_start();

// Check if cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if product ID and action are provided
if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['action'])) {
    $product_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    // Check if product is in cart
    if (isset($_SESSION['cart'][$product_id])) {
        if ($action === 'increase') {
            // Increase quantity
            $_SESSION['cart'][$product_id]++;
        } elseif ($action === 'decrease') {
            // Decrease quantity
            $_SESSION['cart'][$product_id]--;
            
            // Remove if quantity is 0
            if ($_SESSION['cart'][$product_id] <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
}

// Redirect back to cart
header('Location: cart.php');
exit;
?>

