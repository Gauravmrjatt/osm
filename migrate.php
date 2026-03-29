<?php
require_once 'config.php';

$conn = getDB();

// Add logo_image column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM offers LIKE 'logo_image'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE offers ADD logo_image VARCHAR(255) DEFAULT '' AFTER brand_emoji");
    echo "Added logo_image column successfully!<br>";
} else {
    echo "logo_image column already exists.<br>";
}

// Add video_file column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM offers LIKE 'video_file'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE offers ADD video_file VARCHAR(255) DEFAULT '' AFTER logo_image");
    echo "Added video_file column successfully!<br>";
} else {
    echo "video_file column already exists.<br>";
}

// Create uploads directory if not exists
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
    echo "Created uploads directory.<br>";
}

// Create banners table if it doesn't exist
$result = $conn->query("SHOW TABLES LIKE 'banners'");
if ($result->num_rows == 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(500) NOT NULL,
        link_url VARCHAR(500) DEFAULT '',
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created banners table successfully!<br>";
} else {
    echo "banners table already exists.<br>";
}

// Add link2 column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM offers LIKE 'link2'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE offers ADD link2 VARCHAR(500) DEFAULT '' AFTER redirect_url");
    echo "Added link2 column successfully!<br>";
} else {
    echo "link2 column already exists.<br>";
}

// Add payout_type column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM offers LIKE 'payout_type'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE offers ADD payout_type ENUM('instant', '24-72h') DEFAULT 'instant'");
    echo "Added payout_type column successfully!<br>";
} else {
    echo "payout_type column already exists.<br>";
}

echo "Migration complete!";
$conn->close();
?>
