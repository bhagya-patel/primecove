<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Include database connection
include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate input
if ($product_id <= 0 || $rating <= 0 || $rating > 5 || empty($comment)) {
    $_SESSION['review_error'] = 'Please provide a valid rating and comment.';
    header('Location: product.php?id=' . $product_id);
    exit;
}

// Check if product exists
$query = "SELECT id FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

// Check if user has already reviewed this product
$query = "SELECT id FROM reviews WHERE product_id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $product_id);
$stmt->bindParam(2, $user_id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    // Update existing review
    $query = "UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE product_id = ? AND user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $rating);
    $stmt->bindParam(2, $comment);
    $stmt->bindParam(3, $product_id);
    $stmt->bindParam(4, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['review_success'] = 'Your review has been updated successfully.';
    } else {
        $_SESSION['review_error'] = 'Failed to update your review. Please try again.';
    }
} else {
    // Insert new review
    $query = "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $product_id);
    $stmt->bindParam(2, $user_id);
    $stmt->bindParam(3, $rating);
    $stmt->bindParam(4, $comment);
    
    if ($stmt->execute()) {
        $_SESSION['review_success'] = 'Your review has been submitted successfully.';
    } else {
        $_SESSION['review_error'] = 'Failed to submit your review. Please try again.';
    }
}

// Redirect back to product page
header('Location: product.php?id=' . $product_id . '#reviews');
exit;
?>

