<?php
session_start();

// Clear cart
$_SESSION['cart'] = [];

// Redirect back to cart
header('Location: cart.php');
exit;
?>

