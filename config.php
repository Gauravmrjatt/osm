<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'payou_db');

// Admin password (change this)
define('ADMIN_PASSWORD', 'payou123');

// Upload directory
define('UPLOAD_DIR', 'uploads/');

// Create upload directory if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Connect to database
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Format numbers
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'k';
    }
    return $num;
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Get days remaining
function getDaysRemaining($date) {
    $expiry = strtotime($date);
    $now = time();
    $diff = $expiry - $now;
    return floor($diff / (60 * 60 * 24));
}

// Check if offer is expired
function isExpired($date) {
    return strtotime($date) < time();
}

// Get category emoji
function getCategoryEmoji($category) {
    $emojis = [
        'Food' => '🍕',
        'Health' => '💊',
        'Fashion' => '👟',
        'Music' => '🎵',
        'Travel' => '✈️',
        'Entertainment' => '🎬',
        'Shopping' => '🛒',
        'Recharge' => '📱',
        'General' => '🎁'
    ];
    return $emojis[$category] ?? '🎁';
}
?>
