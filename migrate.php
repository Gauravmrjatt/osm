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

echo "Migration complete!";
$conn->close();
?>
