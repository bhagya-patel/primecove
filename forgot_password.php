<?php
include 'includes/header.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email exists
        $query = "SELECT id, name FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database (you would need a password_resets table)
            // For demo purposes, we'll just show a success message
            
            // In a real application, you would send an email with a reset link
            // containing the token
            
            $success = true;
        } else {
            $error = 'No account found with that email address';
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Forgot Password</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p>If an account exists with the email address you provided, we've sent password reset instructions to that email.</p>
                            <p>Please check your inbox and follow the instructions to reset your password.</p>
                            <p class="mb-0">If you don't receive an email within a few minutes, please check your spam folder.</p>
                        </div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Back to Login</a>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-4">Enter your email address and we'll send you instructions to reset your password.</p>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="login.php">Back to Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

