<?php
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'profile.php';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

// Process profile update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be 10 digits';
    }
    
    // If no errors, update the user profile
    if (empty($errors)) {
        $query = "UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $phone);
        $stmt->bindParam(3, $address);
        $stmt->bindParam(4, $user_id);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Update session variable
            $_SESSION['user_name'] = $name;
            
            // Refresh user data
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Process password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password)) {
        $errors[] = 'Current password is required';
    } else {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
    }
    
    if (empty($new_password)) {
        $errors[] = 'New password is required';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters long';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'New passwords do not match';
    }
    
    // If no errors, update the password
    if (empty($errors)) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $hashed_password);
        $stmt->bindParam(2, $user_id);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = 'Failed to update password. Please try again.';
        }
    }
}

// Get user's recent orders
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            
            <div class="list-group mb-4">
                <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">Profile</a>
                <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">My Orders</a>
                <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">Change Password</a>
                <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="list">Addresses</a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">My Wishlist</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">My Profile</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success && isset($_POST['update_profile'])): ?>
                                <div class="alert alert-success">
                                    Your profile has been updated successfully.
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($errors) && isset($_POST['update_profile'])): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <div class="form-text">Email cannot be changed</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">My Orders</h5>
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
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($order['order_status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="orders.php" class="btn btn-primary">View All Orders</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    You haven't placed any orders yet. <a href="index.php">Continue shopping</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="password">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success && isset($_POST['change_password'])): ?>
                                <div class="alert alert-success">
                                    Your password has been changed successfully.
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($errors) && isset($_POST['change_password'])): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">Password must be at least 6 characters long</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Addresses Tab -->
                <div class="tab-pane fade" id="addresses">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">My Addresses</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <p>Your default address:</p>
                                <?php if (!empty($user['address'])): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
                                <?php else: ?>
                                    <p class="mb-0">No address added yet. Please update your profile to add an address.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="#profile" class="btn btn-primary" data-bs-toggle="list">Update Address</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

