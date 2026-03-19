<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Include database connection
include_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: orders.php');
    exit;
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$query = "SELECT oi.*, p.name, p.image FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $order_id);
$stmt->execute();
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate shipping and tax
$shipping = 40;
$tax = $subtotal * 0.18;

// Generate invoice number
$invoice_number = 'INV-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);

// Set invoice date
$invoice_date = date('d M Y', strtotime($order['created_at']));

// Set company details
$company_name = 'ShopKart E-Commerce';
$company_address = '123 Main Street, City, State, 123456';
$company_phone = '+91 1234567890';
$company_email = 'info@shopkart.com';
$company_website = 'www.shopkart.com';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice_number; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            line-height: 24px;
        }
        .invoice-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2874f0;
        }
        .invoice-details {
            margin-bottom: 20px;
        }
        .invoice-details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .invoice-details-label {
            font-weight: bold;
            width: 120px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .invoice-table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .invoice-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .invoice-total {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .invoice-total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
        }
        .invoice-total-label {
            font-weight: bold;
            width: 150px;
            text-align: right;
            margin-right: 20px;
        }
        .invoice-total-value {
            width: 100px;
            text-align: right;
        }
        .invoice-footer {
            margin-top: 30px;
            text-align: center;
            color: #777;
            font-size: 12px;
        }
        .btn-print {
            background-color: #2874f0;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        @media print {
            .btn-print {
                display: none;
            }
            .invoice-container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn btn-primary btn-print" onclick="window.print()">Print Invoice</button>
        
        <div class="invoice-container">
            <div class="invoice-header">
                <div class="row">
                    <div class="col-md-6">
                        <div class="invoice-title">INVOICE</div>
                        <div><?php echo $company_name; ?></div>
                        <div><?php echo $company_address; ?></div>
                        <div>Phone: <?php echo $company_phone; ?></div>
                        <div>Email: <?php echo $company_email; ?></div>
                        <div>Website: <?php echo $company_website; ?></div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="fw-bold">Invoice #: <?php echo $invoice_number; ?></div>
                        <div>Date: <?php echo $invoice_date; ?></div>
                        <div>Order #: <?php echo $order_id; ?></div>
                        <div>Payment Method: <?php echo ($order['payment_method'] == 'cod') ? 'Cash on Delivery' : 'Online Payment'; ?></div>
                        <div>Payment Status: <?php echo ucfirst($order['payment_status']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="fw-bold mb-2">Bill To:</div>
                    <div><?php echo $order['customer_name']; ?></div>
                    <div><?php echo $order['customer_email']; ?></div>
                    <div><?php echo $order['customer_phone']; ?></div>
                </div>
                <div class="col-md-6">
                    <div class="fw-bold mb-2">Ship To:</div>
                    <div><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                </div>
            </div>
            
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo $item['name']; ?></td>
                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td class="text-right">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="invoice-total">
                <div class="invoice-total-row">
                    <div class="invoice-total-label">Subtotal:</div>
                    <div class="invoice-total-value">₹<?php echo number_format($subtotal, 2); ?></div>
                </div>
                <div class="invoice-total-row">
                    <div class="invoice-total-label">Shipping:</div>
                    <div class="invoice-total-value">₹<?php echo number_format($shipping, 2); ?></div>
                </div>
                <div class="invoice-total-row">
                    <div class="invoice-total-label">Tax (18% GST):</div>
                    <div class="invoice-total-value">₹<?php echo number_format($tax, 2); ?></div>
                </div>
                <div class="invoice-total-row">
                    <div class="invoice-total-label fw-bold">Total:</div>
                    <div class="invoice-total-value fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
            </div>
            
            <div class="invoice-footer">
                <p>Thank you for your business!</p>
                <p>This is a computer-generated invoice and does not require a signature.</p>
            </div>
        </div>
    </div>
</body>
</html>

