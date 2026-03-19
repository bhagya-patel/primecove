<?php include 'includes/header.php'; ?>

<?php
// Get all carts with user and product details
$query = "SELECT c.*, u.name as user_name, u.email as user_email, p.name as product_name, p.price, p.image
          FROM cart c
          JOIN users u ON c.user_id = u.id
          JOIN products p ON c.product_id = p.id
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
// the copi rite was shone in the last of the page in t`h`e form of contents are shequred to the p``atent time 
// the basic ```program of php laravel in  b  dhysufbuh bbw  inijbiv j `(scrit=== not login) insert into 
// Group cart items by user
$carts = [];
foreach ($cart_items as $item) {
    $user_id = $item['user_id'];
    if (!isset($carts[$user_id])) {
        $carts[$user_id] = [
            'user_name' => $item['user_name'],
            'user_email' => $item['user_email'],
            'items' => [],
            'total' => 0
        ];
    }
    $carts[$user_id]['items'][] = $item;
    $carts[$user_id]['total'] += $item['price'] * $item['quantity'];
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Carts Management</h4>
</div>

<!-- Carts List -->
<div class="row">
    <?php if (count($carts) > 0): ?>
        <?php foreach ($carts as $user_id => $cart): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $cart['user_name']; ?> (<?php echo $cart['user_email']; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/products/<?php echo $item['image']; ?>" alt="<?php echo $item['product_name']; ?>" style="width: 40px; height: 40px; object-fit: contain; margin-right: 10px;">
                                                    <?php echo $item['product_name']; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                            <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th>₹<?php echo number_format($cart['total'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5>No carts found</h5>
                    <p>All user carts are empty.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
