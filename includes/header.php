<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRIMECOVE - Online Shopping</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <h2 class="text-white fw-bold">PRIMECOVE</h2>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <form class="d-flex mx-auto" action="search.php" method="GET">
                    <input class="form-control me-2" style="min-width: 300px;" type="search" name="query" placeholder="Search for products, brands and more" aria-label="Search">
                    <button class="btn btn-light" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="login.php">
                                <i class="fas fa-user me-1"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i> Cart
                            <?php
                            if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                                echo '<span class="badge bg-danger">' . count($_SESSION['cart']) . '</span>';
                            }
                            ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Category Menu -->
    <div class="bg-light py-2 shadow-sm">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ul class="nav justify-content-center">
                    <li class="nav-item">
                    <a class="nav-link text-dark" href="http://localhost/PRIMECOVE/" >Home</a>
                    </li>
                        <?php
                        // Include database connection
                        include_once 'config/database.php';
                        $database = new Database();
                        $db = $database->getConnection();
                        
                        // Fetch categories
                        $query = "SELECT * FROM categories LIMIT 8";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<li class="nav-item">';
                            echo '<a class="nav-link text-dark" href="category.php?id=' . $row['id'] . '">' . $row['name'] . '</a>';
                            echo '</li>';
                        }
                        ?>
                        <!-- <li class="nav-item">
                    <a class="nav-link text-dark" href="http://localhost/flipkart-clone1/feedback.php" >Feedback</a>
                    </li> -->
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container my-4">

