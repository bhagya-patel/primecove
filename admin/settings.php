<?php include 'includes/header.php'; ?>

<?php
// Initialize settings array
$settings = [
    'site_name' => 'ShopKart',
    'site_email' => 'info@shopkart.com',
    'site_phone' => '+91 1234567890',
    'site_address' => '123 Main Street, City, State, 123456',
    'currency_symbol' => '₹',
    'tax_rate' => '18',
    'shipping_fee' => '40',
    'theme_mode' => 'light',
    'enable_reviews' => '1',
    'enable_wishlist' => '1',
    'items_per_page' => '12'
];

// Create settings table if not exists
$query = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$db->exec($query);

// Load settings from database
$query = "SELECT * FROM settings";
$stmt = $db->prepare($query);
$stmt->execute();
$db_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update settings array with values from database
foreach ($db_settings as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // General Settings
    if (isset($_POST['update_general'])) {
        $site_name = trim($_POST['site_name']);
        $site_email = trim($_POST['site_email']);
        $site_phone = trim($_POST['site_phone']);
        $site_address = trim($_POST['site_address']);
        
        // Validate input
        if (empty($site_name)) {
            $error_message = 'Site name is required.';
        } elseif (empty($site_email) || !filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Valid site email is required.';
        } else {
            // Update settings
            $settings_to_update = [
                'site_name' => $site_name,
                'site_email' => $site_email,
                'site_phone' => $site_phone,
                'site_address' => $site_address
            ];
            
            foreach ($settings_to_update as $key => $value) {
                // Check if setting exists
                $query = "SELECT id FROM settings WHERE setting_key = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $key);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update existing setting
                    $query = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $value);
                    $stmt->bindParam(2, $key);
                } else {
                    // Insert new setting
                    $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $key);
                    $stmt->bindParam(2, $value);
                }
                
                $stmt->execute();
                $settings[$key] = $value;
            }
            
            $success_message = 'General settings updated successfully.';
        }
    }
    
    // Store Settings
    if (isset($_POST['update_store'])) {
        $currency_symbol = trim($_POST['currency_symbol']);
        $tax_rate = (float)$_POST['tax_rate'];
        $shipping_fee = (float)$_POST['shipping_fee'];
        
        // Validate input
        if (empty($currency_symbol)) {
            $error_message = 'Currency symbol is required.';
        } elseif ($tax_rate < 0 || $tax_rate > 100) {
            $error_message = 'Tax rate must be between 0 and 100.';
        } elseif ($shipping_fee < 0) {
            $error_message = 'Shipping fee cannot be negative.';
        } else {
            // Update settings
            $settings_to_update = [
                'currency_symbol' => $currency_symbol,
                'tax_rate' => $tax_rate,
                'shipping_fee' => $shipping_fee
            ];
            
            foreach ($settings_to_update as $key => $value) {
                // Check if setting exists
                $query = "SELECT id FROM settings WHERE setting_key = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $key);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update existing setting
                    $query = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $value);
                    $stmt->bindParam(2, $key);
                } else {
                    // Insert new setting
                    $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $key);
                    $stmt->bindParam(2, $value);
                }
                
                $stmt->execute();
                $settings[$key] = $value;
            }
            
            $success_message = 'Store settings updated successfully.';
        }
    }
    
    // Appearance Settings
    if (isset($_POST['update_appearance'])) {
        $theme_mode = $_POST['theme_mode'];
        $items_per_page = (int)$_POST['items_per_page'];
        
        // Validate input
        if ($items_per_page <= 0) {
            $error_message = 'Items per page must be greater than zero.';
        } else {
            // Update settings
            $settings_to_update = [
                'theme_mode' => $theme_mode,
                'items_per_page' => $items_per_page
            ];
            
            foreach ($settings_to_update as $key => $value) {
                // Check if setting exists
                $query = "SELECT id FROM settings WHERE setting_key = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $key);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    // Update existing setting
                    $query = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $value);
                    $stmt->bindParam(2, $key);
                } else {
                    // Insert new setting
                    $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(1, $key);
                    $stmt->bindParam(2, $value);
                }
                
                $stmt->execute();
                $settings[$key] = $value;
            }
            
            // Set theme mode cookie
            setcookie('theme_mode', $theme_mode, time() + (86400 * 30), "/"); // 30 days
            
            $success_message = 'Appearance settings updated successfully.';
        }
    }
    
    // Features Settings
    if (isset($_POST['update_features'])) {
        $enable_reviews = isset($_POST['enable_reviews']) ? '1' : '0';
        $enable_wishlist = isset($_POST['enable_wishlist']) ? '1' : '0';
        
        // Update settings
        $settings_to_update = [
            'enable_reviews' => $enable_reviews,
            'enable_wishlist' => $enable_wishlist
        ];
        
        foreach ($settings_to_update as $key => $value) {
            // Check if setting exists
            $query = "SELECT id FROM settings WHERE setting_key = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $key);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing setting
                $query = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $value);
                $stmt->bindParam(2, $key);
            } else {
                // Insert new setting
                $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $key);
                $stmt->bindParam(2, $value);
            }
            
            $stmt->execute();
            $settings[$key] = $value;
        }
        
        $success_message = 'Features settings updated successfully.';
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Settings</h4>
</div>

<!-- Success Message -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Error Message -->
<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Settings Menu</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="settings-tabs" role="tablist">
                    <a class="list-group-item list-group-item-action active" id="general-tab" data-bs-toggle="list" href="#general" role="tab" aria-controls="general">
                        <i class="fas fa-cog me-2"></i> General
                    </a>
                    <a class="list-group-item list-group-item-action" id="store-tab" data-bs-toggle="list" href="#store" role="tab" aria-controls="store">
                        <i class="fas fa-store me-2"></i> Store
                    </a>
                    <a class="list-group-item list-group-item-action" id="appearance-tab" data-bs-toggle="list" href="#appearance" role="tab" aria-controls="appearance">
                        <i class="fas fa-palette me-2"></i> Appearance
                    </a>
                    <a class="list-group-item list-group-item-action" id="features-tab" data-bs-toggle="list" href="#features" role="tab" aria-controls="features">
                        <i class="fas fa-puzzle-piece me-2"></i> Features
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="tab-content">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="site_email" class="form-label">Site Email</label>
                                <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="site_phone" class="form-label">Site Phone</label>
                                <input type="text" class="form-control" id="site_phone" name="site_phone" value="<?php echo htmlspecialchars($settings['site_phone']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="site_address" class="form-label">Site Address</label>
                                <textarea class="form-control" id="site_address" name="site_address" rows="3"><?php echo htmlspecialchars($settings['site_address']); ?></textarea>
                            </div>
                            <button type="submit" name="update_general" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Store Settings -->
            <div class="tab-pane fade" id="store" role="tabpanel" aria-labelledby="store-tab">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Store Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="shipping_fee" class="form-label">Default Shipping Fee</label>
                                <input type="number" class="form-control" id="shipping_fee" name="shipping_fee" min="0" step="0.01" value="<?php echo htmlspecialchars($settings['shipping_fee']); ?>" required>
                            </div>
                            <button type="submit" name="update_store" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Appearance Settings -->
            <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Appearance Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Theme Mode</label>
                                <div class="d-flex">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio" name="theme_mode" id="theme_light" value="light" <?php echo ($settings['theme_mode'] == 'light') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="theme_light">
                                            <i class="fas fa-sun me-1 text-warning"></i> Light
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="theme_mode" id="theme_dark" value="dark" <?php echo ($settings['theme_mode'] == 'dark') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="theme_dark">
                                            <i class="fas fa-moon me-1 text-primary"></i> Dark
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="items_per_page" class="form-label">Items Per Page</label>
                                <input type="number" class="form-control" id="items_per_page" name="items_per_page" min="1" value="<?php echo htmlspecialchars($settings['items_per_page']); ?>" required>
                            </div>
                            <button type="submit" name="update_appearance" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Features Settings -->
            <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Features Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_reviews" name="enable_reviews" <?php echo ($settings['enable_reviews'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_reviews">Enable Product Reviews</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_wishlist" name="enable_wishlist" <?php echo ($settings['enable_wishlist'] == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_wishlist">Enable Wishlist Feature</label>
                                </div>
                            </div>
                            <button type="submit" name="update_features" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

