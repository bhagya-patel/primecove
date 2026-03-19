<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if review ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: reviews.php');
    exit;
}

$review_id = (int)$_GET['id'];

// Include database connection
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Delete review
$query = "DELETE FROM reviews WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $review_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Review has been deleted successfully.';
} else {
    $_SESSION['error_message'] = 'Failed to delete review. Please try again.';
}

// Redirect back to reviews page
header('Location: reviews.php');
exit;

