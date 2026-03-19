<?php
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart items
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $cart_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
    
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    
    foreach ($cart_ids as $i => $id) {
        $stmt->bindValue($i + 1, $id);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $price = $product['discount_price'] ? $product['discount_price'] : $product['price'];
        $subtotal = $price * $quantity;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
        
        $total += $subtotal;
    }
}

// Calculate shipping and tax
$shipping = 40; // Fixed shipping cost
$tax = $total * 0.18; // 18% GST
$grand_total = $total + $shipping + $tax;

$errors = [];
$success = false;

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $payment_method = $_POST['payment_method'];
    
    // Basic validation
    if (empty($name) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        $errors[] = 'All fields are required';
    }
    
    if (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $errors[] = 'Pincode must be 6 digits';
    }
    
    if (!in_array($payment_method, ['cod', 'online'])) {
        $errors[] = 'Invalid payment method';
    }
    
    // If no errors, process the order
    if (empty($errors)) {
        // Create shipping address
        $shipping_address = "$name\n$address\n$city, $state - $pincode\nPhone: $phone";
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert order
            $query = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, payment_status, order_status) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            $payment_status = ($payment_method == 'cod') ? 'pending' : 'completed';
            $order_status = 'processing';
            
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $grand_total);
            $stmt->bindParam(3, $shipping_address);
            $stmt->bindParam(4, $payment_method);
            $stmt->bindParam(5, $payment_status);
            $stmt->bindParam(6, $order_status);
            
            $stmt->execute();
            $order_id = $db->lastInsertId();
            
            // Insert order items
            foreach ($cart_items as $item) {
                $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                          VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(1, $order_id);
                $stmt->bindParam(2, $item['id']);
                $stmt->bindParam(3, $item['quantity']);
                $stmt->bindParam(4, $item['price']);
                
                $stmt->execute();
                
                // Update product stock
                $query = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $item['quantity']);
                $stmt->bindParam(2, $item['id']);
                $stmt->execute();
            }
            
            // Commit transaction
            $db->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            // Set success flag
            $success = true;
            
            // Redirect to order confirmation page
            // Ensure $order_id is valid
            if (isset($order_id) && !empty($order_id)) {
                // Sanitize and encode the order ID
                $order_id = urlencode($order_id);

                // Use JavaScript for redirection
                echo "<script>
                        window.location.href = 'order_confirmation.php?id=$order_id';
                    </script>";
                exit;
            } else {
                die("Invalid order ID. Please try again.");
            }
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollBack();
            $errors[] = 'An error occurred while processing your order. Please try again.';
        }
    }
}
?>

<div class="container my-5">
    <h2 class="mb-4">Checkout</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="pincode" class="form-label">Pincode</label>
                                <input type="text" class="form-control" id="pincode" name="pincode" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                            <label class="form-check-label" for="cod">
                                Cash on Delivery (COD)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="online" value="online">
                            <label class="form-check-label" for="online">
                                Online Payment (Credit/Debit Card, UPI, Net Banking)
                            </label>
                        </div>
                        
                        <div id="online-payment-form" class="mt-3" style="display: none;">
                            <div class="alert alert-info border-0" style="background-color: #e8f8f2; color: #1e7e54;">
                                <i class="fas fa-info-circle me-2"></i>You will be redirected to the secure PrimeCove Pay gateway after clicking Place Order.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Items (<?php echo count($cart_items); ?>)</h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($cart_items as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <?php echo $item['name']; ?> <span class="text-muted">x <?php echo $item['quantity']; ?></span>
                                        </div>
                                        <span>₹<?php echo number_format($item['subtotal'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>₹<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (18% GST)</span>
                            <span>₹<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold">₹<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Place Order
                            </button>
                            <a href="cart.php" class="btn btn-outline-secondary">
                                Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
#paymentTabs .nav-link {
    transition: all 0.2s ease;
}
#paymentTabs .nav-link.active {
    background-color: white !important;
    color: #000 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.bank-btn:focus, .bank-btn.active {
    background-color: #f8f9fa !important;
    border-color: #51cb99 !important;
    box-shadow: 0 0 0 0.2rem rgba(81, 203, 153, 0.25) !important;
}
</style>

<!-- PrimeCove Pay Modal -->
<div class="modal fade" id="primecovePayModal" tabindex="-1" aria-labelledby="primecovePayModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <!-- Modal Header / Top Section (Green) -->
        <div style="background-color: #4cd49c; padding: 24px; color: white;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="mb-0 fw-bold d-flex align-items-center" style="font-size: 1.25rem;">
                        PrimeCove Pay 
                        <span class="badge rounded-pill ms-2" style="background-color: rgba(255,255,255,0.2); font-size: 0.7rem; font-weight: normal;">
                            <i class="fas fa-shield-alt me-1"></i> Secure
                        </span>
                    </h5>
                    <small style="opacity: 0.9;">Simulated Payment Gateway</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity: 1; background-color: rgba(255,255,255,0.2); border-radius: 50%; padding: 0.5rem;"></button>
            </div>
            
            <div style="background-color: rgba(255,255,255,0.15); border-radius: 8px; padding: 16px;">
                <p class="mb-1" style="font-size: 0.85rem; opacity: 0.9;">Total Amount</p>
                <h2 class="fw-bold mb-2">₹ <?php echo number_format($grand_total, 2); ?></h2>
                <small style="opacity: 0.9;">Order details will be sent to your email</small>
            </div>
        </div>
        
        <!-- Modal Body (White) -->
        <div class="modal-body p-4" style="background-color: #f8f9fa;">
            <!-- Custom Tabs -->
            <ul class="nav nav-pills mb-4 d-flex" id="paymentTabs" role="tablist" style="background-color: #e9ecef; border-radius: 8px; padding: 4px;">
              <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link active w-100" id="card-tab" data-bs-toggle="pill" data-bs-target="#card-payment" type="button" role="tab" style="color: #495057; border-radius: 6px; font-weight: 500;">
                    <i class="far fa-credit-card me-1"></i> Card
                </button>
              </li>
              <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link w-100" id="upi-tab" data-bs-toggle="pill" data-bs-target="#upi-payment" type="button" role="tab" style="color: #495057; border-radius: 6px; font-weight: 500;">
                    <i class="fas fa-mobile-alt me-1"></i> UPI
                </button>
              </li>
              <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link w-100" id="net-banking-tab" data-bs-toggle="pill" data-bs-target="#net-banking-payment" type="button" role="tab" style="color: #495057; border-radius: 6px; font-weight: 500;">
                    <i class="fas fa-university me-1"></i> Net Banking
                </button>
              </li>
            </ul>
            
            <div class="tab-content" id="paymentTabsContent">
                <!-- Card Tab -->
                <div class="tab-pane fade show active" id="card-payment" role="tabpanel">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 0.85rem; color: #495057;">Card Number</label>
                        <input type="text" class="form-control" placeholder="4242 4242 4242 4242" style="padding: 12px; border-radius: 8px; border: 1px solid #ced4da;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 0.85rem; color: #495057;">Cardholder Name</label>
                        <input type="text" class="form-control" placeholder="John Doe" style="padding: 12px; border-radius: 8px; border: 1px solid #ced4da;">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem; color: #495057;">Expiry</label>
                            <input type="text" class="form-control" placeholder="MM/YY" style="padding: 12px; border-radius: 8px; border: 1px solid #ced4da;">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold" style="font-size: 0.85rem; color: #495057;">CVV</label>
                            <input type="password" class="form-control" placeholder="&bull;&bull;&bull;" style="padding: 12px; border-radius: 8px; border: 1px solid #ced4da;" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <!-- UPI Tab -->
                <div class="tab-pane fade" id="upi-payment" role="tabpanel">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size: 0.85rem; color: #495057;">UPI ID</label>
                        <input type="text" class="form-control" placeholder="yourname@upi" style="padding: 12px; border-radius: 8px; border: 1px solid #ced4da;">
                        <div class="form-text mt-2" style="font-size: 0.8rem;">Enter your UPI ID linked to any bank account</div>
                    </div>
                </div>
                
                <!-- Net Banking Tab -->
                <div class="tab-pane fade" id="net-banking-payment" role="tabpanel">
                    <label class="form-label fw-bold mb-3" style="font-size: 0.85rem; color: #495057;">Select Your Bank</label>
                    <div class="row g-2">
                        <div class="col-6"><button type="button" class="btn btn-outline-secondary w-100 text-start bg-white bank-btn" style="border-radius: 8px; padding: 10px; font-size: 0.85rem;">State Bank of India</button></div>
                        <div class="col-6"><button type="button" class="btn btn-outline-secondary w-100 text-start bg-white bank-btn" style="border-radius: 8px; padding: 10px; font-size: 0.85rem;">HDFC Bank</button></div>
                        <div class="col-6"><button type="button" class="btn btn-outline-secondary w-100 text-start bg-white bank-btn" style="border-radius: 8px; padding: 10px; font-size: 0.85rem;">ICICI Bank</button></div>
                        <div class="col-6"><button type="button" class="btn btn-outline-secondary w-100 text-start bg-white bank-btn" style="border-radius: 8px; padding: 10px; font-size: 0.85rem;">Axis Bank</button></div>
                        <div class="col-6"><button type="button" class="btn btn-outline-secondary w-100 text-start bg-white bank-btn" style="border-radius: 8px; padding: 10px; font-size: 0.85rem;">Punjab National Bank</button></div>
                        <div class="col-6"><button type="button" class="btn btn-outline-secondary w-100 text-start bg-white bank-btn" style="border-radius: 8px; padding: 10px; font-size: 0.85rem;">Bank of Baroda</button></div>
                    </div>
                </div>
            </div>
            
            <button type="button" id="payNowBtn" class="btn w-100 mt-4 text-white fw-bold" style="background-color: #8be0bd; border-radius: 8px; padding: 12px; font-size: 1.1rem; border: none; transition: background-color 0.2s;">
                Pay ₹<?php echo number_format($grand_total, 2); ?>
            </button>
            <script>
                // Change button color to darker green on hover
                const payNowBtn = document.getElementById('payNowBtn');
                payNowBtn.addEventListener('mouseover', () => payNowBtn.style.backgroundColor = '#74cba7');
                payNowBtn.addEventListener('mouseout', () => payNowBtn.style.backgroundColor = '#8be0bd');
            </script>
            
            <div class="text-center mt-3">
                <small style="color: #6c757d; font-size: 0.75rem;"><i class="fas fa-lock text-warning me-1"></i> This is a demo payment. No real money is charged.</small>
            </div>
        </div>
    </div>
  </div>
</div>

<script>
    // Toggle online payment form and handle modal
    document.addEventListener('DOMContentLoaded', function() {
        const codRadio = document.getElementById('cod');
        const onlineRadio = document.getElementById('online');
        const onlinePaymentForm = document.getElementById('online-payment-form');
        const checkoutForm = document.querySelector('form');
        
        function togglePaymentForm() {
            if (onlineRadio.checked) {
                onlinePaymentForm.style.display = 'block';
            } else {
                onlinePaymentForm.style.display = 'none';
            }
        }
        
        codRadio.addEventListener('change', togglePaymentForm);
        onlineRadio.addEventListener('change', togglePaymentForm);

        // Form submission interception
        checkoutForm.addEventListener('submit', function(e) {
            if (onlineRadio.checked && !window.paymentCompleted) {
                e.preventDefault();
                // Validate native required fields first
                if (!checkoutForm.checkValidity()) {
                    checkoutForm.reportValidity();
                    return;
                }
                const modal = new bootstrap.Modal(document.getElementById('primecovePayModal'));
                modal.show();
            }
        });

        // Payment Simulation Logic
        const payBtn = document.getElementById('payNowBtn');
        if(payBtn) {
            payBtn.addEventListener('click', function() {
                // Basic validation based on active tab
                const activeTab = document.querySelector('#paymentTabs .nav-link.active').id;
                let isValid = true;
                
                if (activeTab === 'card-tab') {
                    const inputs = document.querySelectorAll('#card-payment input');
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            isValid = false;
                            input.style.borderColor = '#dc3545';
                        } else {
                            input.style.borderColor = '#ced4da';
                        }
                    });
                    // Validate card number: must be 16 digits
                    const cardNumInput = document.querySelector('#card-payment input[placeholder]');
                    const cardDigits = cardNumInput.value.replace(/\s/g, '');
                    if (!/^\d{16}$/.test(cardDigits)) {
                        isValid = false;
                        cardNumInput.style.borderColor = '#dc3545';
                        cardNumInput.placeholder = 'Enter 16-digit card number';
                    }
                    // Validate expiry MM/YY
                    const expiryInput = document.querySelector('#card-payment input[placeholder="MM/YY"]');
                    if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiryInput.value.trim())) {
                        isValid = false;
                        expiryInput.style.borderColor = '#dc3545';
                    }
                } else if (activeTab === 'upi-tab') {
                    const input = document.querySelector('#upi-payment input');
                    // UPI format: localpart@bankcode
                    if (!/^[a-zA-Z0-9.\-_]{2,}@[a-zA-Z]{2,}$/.test(input.value.trim())) {
                        isValid = false;
                        input.style.borderColor = '#dc3545';
                        input.placeholder = 'Invalid UPI ID (e.g. name@okaxis)';
                    } else {
                        input.style.borderColor = '#ced4da';
                    }
                } else if (activeTab === 'net-banking-tab') {
                    const activeBank = document.querySelector('.bank-btn.active');
                    if (!activeBank) {
                        isValid = false;
                        alert('Please select a bank for Net Banking.');
                    }
                }

                if (!isValid) {
                    return;
                }

                const originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                this.disabled = true;

                // Simulate payment delay
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-check-circle me-2"></i> Payment Successful';
                    this.style.backgroundColor = '#28a745';
                    window.paymentCompleted = true;
                    
                    setTimeout(() => {
                        const modalEl = document.getElementById('primecovePayModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        // Submit the actual checkout form
                        checkoutForm.submit();
                    }, 1500);
                }, 2000);
            });
        }
        
        // Bank button selection effect
        const bankBtns = document.querySelectorAll('.bank-btn');
        bankBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                bankBtns.forEach(b => b.classList.remove('active', 'border-success'));
                this.classList.add('active', 'border-success');
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

