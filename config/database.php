<!-- <?php
// Database configuration
// $host = "localhost";
// $dbname = "ecommerce_store";
// $username = "root";
// $password = "";

// try {
//     // Create PDO instance
//     $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
//     // Set the PDO error mode to exception
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
//     // Set default fetch mode to associative array
//     $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
//     // For backward compatibility, also set $db variable
//     $db = $conn;
    
// } catch(PDOException $e) {
//     die("Connection failed: " . $e->getMessage());
// }
// ?>
 -->
  <?php
class Database {
    private $host = "localhost";
    private $db_name = "ecommerce_store";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}
?>

