<?php include 'includes/header.php'; ?>

<?php
// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = (int)$_GET['id'];

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: users.php');
    exit;
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's orders
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total orders and spending
$query = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders WHERE user_id = ? AND order_status != 'cancelled'";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's reviews
$query = "SELECT r.*, p.name as product_name FROM reviews r 
          JOIN products p ON r.product_id = p.id 
          WHERE r.user_id = ? 
          ORDER BY r.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">User Details</h4>
    <div>
        <a href="edit_user.php?id=<?php echo $user_id; ?>" class="btn btn-primary me-2">
            <i class="fas fa-edit me-1"></i> Edit User
        </a>
        <a href="users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
    </div>
</div>

<div class="row">
    <!-- User Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold">Name</h6>
                    <p><?php echo htmlspecialchars($user['name']); ?></p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold">Email</h6>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold">Phone</h6>
                    <p><?php echo htmlspecialchars($user['phone']); ?></p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold">Role</h6>
                    <p>
                        <span class="badge <?php echo ($user['is_admin'] == 1) ? 'bg-danger' : 'bg-success'; ?>">
                            <?php echo ($user['is_admin'] == 1) ? 'Administrator' : 'Customer'; ?>
                        </span>
                    </p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold">Registered On</h6>
                    <p><?php echo date('d M Y, h:i A', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="mb-0">
                    <h6 class="fw-bold">Address</h6>
                    <p><?php echo !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : 'No address provided'; ?></p>
                </div>
            </div>
        </div>
        
        <!-- User Stats -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">User Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h2 class="text-primary"><?php echo $order_stats['total_orders'] ?? 0; ?></h2>
                        <p class="text-muted mb-0">Total Orders</p>
                    </div>
                    <div class="col-6 mb-3">
                        <h2 class="text-success">₹<?php echo number_format($order_stats['total_spent'] ?? 0, 2); ?></h2>
                        <p class="text-muted mb-0">Total Spent</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="orders.php?search=<?php echo urlencode($user['email']); ?>" class="btn btn-sm btn-light">View All Orders</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($order['order_status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning text-dark';
                                                    break;
                                                case 'processing':
                                                    $status_class = 'bg-info text-white';
                                                    break;
                                                case 'shipped':
                                                    $status_class = 'bg-primary text-white';
                                                    break;
                                                case 'delivered':
                                                    $status_class = 'bg-success text-white';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger text-white';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        This user has not placed any orders yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Reviews -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Reviews</h5>
                <a href="reviews.php?search=<?php echo urlencode($user['email']); ?>" class="btn btn-sm btn-light">View All Reviews</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_reviews) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_reviews as $review): ?>
                                    <tr>
                                        <td><?php echo $review['product_name']; ?></td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?php echo substr(htmlspecialchars($review['comment']), 0, 50) . (strlen($review['comment']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo date('d M Y', strtotime($review['created_at'])); ?></td>
                                        <td>
                                            <a href="reviews.php?id=<?php echo $review['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        This user has not submitted any reviews yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

