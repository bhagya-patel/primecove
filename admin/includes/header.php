<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - PRIMECOVE</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>
<body>
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="admin-sidebar">
            <div class="sidebar-header">
                <h3>PRIMECOVE Admin</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="products.php" class="<?php echo ($current_page == 'products.php' || $current_page == 'add_product.php' || $current_page == 'edit_product.php') ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li>
                    <a href="categories.php" class="<?php echo ($current_page == 'categories.php' || $current_page == 'add_category.php' || $current_page == 'edit_category.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="<?php echo ($current_page == 'orders.php' || $current_page == 'view_order.php') ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li>
                    <a href="users.php" class="<?php echo ($current_page == 'users.php' || $current_page == 'view_user.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li>
                    <a href="reviews.php" class="<?php echo ($current_page == 'reviews.php') ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content" class="admin-content">
            <!-- Navbar -->
            <nav class="admin-navbar d-flex justify-content-between align-items-center rounded">
                <button type="button" id="sidebarCollapse" class="btn btn-primary d-md-none">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0">
                    <?php
                    switch ($current_page) {
                        case 'index.php':
                            echo 'Dashboard';
                            break;
                        case 'products.php':
                            echo 'Products Management';
                            break;
                        case 'add_product.php':
                            echo 'Add New Product';
                            break;
                        case 'edit_product.php':
                            echo 'Edit Product';
                            break;
                        case 'categories.php':
                            echo 'Categories Management';
                            break;
                        case 'add_category.php':
                            echo 'Add New Category';
                            break;
                        case 'edit_category.php':
                            echo 'Edit Category';
                            break;
                        case 'orders.php':
                            echo 'Orders Management';
                            break;
                        case 'view_order.php':
                            echo 'Order Details';
                            break;
                        case 'users.php':
                            echo 'Users Management';
                            break;
                        case 'view_user.php':
                            echo 'User Details';
                            break;
                        case 'reviews.php':
                            echo 'Reviews Management';
                            break;
                        case 'settings.php':
                            echo 'Settings';
                            break;
                        default:
                            echo 'Admin Panel';
                    }
                    ?>
                </h4>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['admin_name']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

