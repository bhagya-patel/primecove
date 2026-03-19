<?php include 'includes/header.php'; ?>

<!-- Main Banner Carousel -->
<div id="mainCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner rounded shadow">
        <div class="carousel-item active">
            <img src="assets/images/banner1.png" class="d-block w-100" alt="Banner 1">
        </div>
        <div class="carousel-item">
            <img src="assets/images/banare2.jpg" class="d-block w-100" alt="Banner 2">
        </div>
        <div class="carousel-item">
            <img src="assets/images/banner3.jpg" class="d-block w-100" alt="Banner 3">
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<!-- Categories Section -->
<div class="bg-white p-3 rounded shadow mb-4">
    <h4 class="mb-3">Shop by Category</h4>
    <div class="row">
        <?php
        // Fetch categories
        $query = "SELECT * FROM categories";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<div class="col-6 col-md-3 col-lg-2 mb-3">';
            echo '<a href="category.php?id=' . $row['id'] . '" class="text-decoration-none">';
            echo '<div class="category-item">';
            echo '<img src="assets/images/categories/' . $row['image'] . '" alt="' . $row['name'] . '" class="img-fluid">';
            echo '<h6 class="text-dark">' . $row['name'] . '</h6>';
            echo '</div>';
            echo '</a>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- Featured Products -->
<div class="bg-white p-3 rounded shadow mb-4">
    <h4 class="mb-3">Featured Products</h4>
    <div class="row">
        <?php
        // Fetch featured products (products with discount)
        $query = "SELECT * FROM products WHERE discount_price IS NOT NULL ORDER BY (price - discount_price) DESC LIMIT 8";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $discount_percentage = round(($row['price'] - $row['discount_price']) / $row['price'] * 100);
            
            echo '<div class="col-6 col-md-3 mb-4">';
            echo '<div class="product-card h-100 p-3">';
            echo '<a href="product.php?id=' . $row['id'] . '" class="text-decoration-none">';
            echo '<img src="assets/images/products/' . $row['image'] . '" alt="' . $row['name'] . '" class="img-fluid product-image mb-3">';
            echo '<h6 class="product-title text-dark">' . $row['name'] . '</h6>';
            echo '</a>';
            echo '<div class="price-section">';
            echo '<h5 class="mb-0">₹' . number_format($row['discount_price'], 2) . ' <small class="original-price">₹' . number_format($row['price'], 2) . '</small></h5>';
            echo '<span class="discount">' . $discount_percentage . '% off</span>';
            echo '</div>';
            echo '<button onclick="addToCart(' . $row['id'] . ', \'' . addslashes($row['name']) . '\', ' . $row['discount_price'] . ', \'' . $row['image'] . '\')" class="btn btn-primary btn-sm mt-2">Add to Cart</button>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<!-- New Arrivals -->
<div class="bg-white p-3 rounded shadow mb-4">
    <h4 class="mb-3">New Arrivals</h4>
    <div class="row">
        <?php
        // Fetch new arrivals (most recently added products)
        $query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 8";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<div class="col-6 col-md-3 mb-4">';
            echo '<div class="product-card h-100 p-3">';
            echo '<a href="product.php?id=' . $row['id'] . '" class="text-decoration-none">';
            echo '<img src="assets/images/products/' . $row['image'] . '" alt="' . $row['name'] . '" class="img-fluid product-image mb-3">';
            echo '<h6 class="product-title text-dark">' . $row['name'] . '</h6>';
            echo '</a>';
            echo '<div class="price-section">';
            
            if ($row['discount_price']) {
                $discount_percentage = round(($row['price'] - $row['discount_price']) / $row['price'] * 100);
                echo '<h5 class="mb-0">₹' . number_format($row['discount_price'], 2) . ' <small class="original-price">₹' . number_format($row['price'], 2) . '</small></h5>';
                echo '<span class="discount">' . $discount_percentage . '% off</span>';
            } else {
                echo '<h5 class="mb-0">₹' . number_format($row['price'], 2) . '</h5>';
            }
            
            echo '</div>';
            echo '<button onclick="addToCart(' . $row['id'] . ', \'' . addslashes($row['name']) . '\', ' . ($row['discount_price'] ? $row['discount_price'] : $row['price']) . ', \'' . $row['image'] . '\')" class="btn btn-primary btn-sm mt-2">Add to Cart</button>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

