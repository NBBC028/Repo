<?php
session_start();

// Define the config path
$configDir = __DIR__ . '/../config';
$configFile = $configDir . '/config.php';

// Check if config directory exists, if not create it
if (!is_dir($configDir)) {
    mkdir($configDir, 0755, true);
}

// Check if config.php exists, if not create a default one
if (!file_exists($configFile)) {
    $defaultConfig = "<?php
\$servername = 'localhost';
\$username = 'root';
\$password = '';
\$dbname = 'neust_repository';

// Create connection
\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);

// Check connection
if (\$conn->connect_error) {
    die('Connection failed: ' . \$conn->connect_error);
}
?>";
    file_put_contents($configFile, $defaultConfig);
    echo "A default config.php file has been created at: $configFile<br>";
    echo "Please check the database name and credentials.";
    exit;
}

// Include the config file
require_once $configFile;

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Only handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Sanitize input
    $research_id = isset($_POST['research_id']) ? intval($_POST['research_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $user_id = $_SESSION['user_id'];

    // Validate input
    if ($research_id <= 0) {
        die("Invalid research ID.");
    }

    if ($rating < 1 || $rating > 5) {
        die("Invalid rating value. Must be between 1 and 5.");
    }

    // Insert or update rating
    $stmt = $conn->prepare("
        INSERT INTO research_ratings (research_id, user_id, rating, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            rating = VALUES(rating),
            updated_at = NOW()
    ");

    if (!$stmt) {
        die("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iii", $research_id, $user_id, $rating);

    if ($stmt->execute()) {
        // Redirect to dashboard with success message
        header("Location: dashboard.php?rating_success=1");
        exit;
    } else {
        die("Database execution error: " . $stmt->error);
    }

} else {
    die("Invalid request method.");
}
?>
