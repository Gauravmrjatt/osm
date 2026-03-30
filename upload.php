<?php
require_once 'config.php';

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Type: application/json');

// Start session and check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$response = ['success' => false, 'filename' => '', 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = 'uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Handle image upload
    if (!empty($_FILES['logo_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION));
        $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Validate MIME type
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($_FILES['logo_image']['tmp_name']);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mime_type, $allowed_mimes)) {
                $response['error'] = 'Invalid image format';
                echo json_encode($response);
                exit;
            }
        }
        
        if (!in_array($ext, $allowed_images)) {
            $response['error'] = 'Invalid image format';
            echo json_encode($response);
            exit;
        }
        
        // Sanitize filename and use basename to prevent path traversal
        $new_filename = 'img_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        $target_path = $upload_dir . basename($new_filename);
        
        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
            $response['success'] = true;
            $response['filename'] = basename($new_filename);
            $response['type'] = 'image';
        } else {
            $response['error'] = 'Failed to upload image';
        }
        echo json_encode($response);
        exit;
    }
    
    // Handle video upload
    if (!empty($_FILES['video_file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
        $allowed_videos = ['mp4', 'webm'];
        
        // Validate MIME type
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($_FILES['video_file']['tmp_name']);
            $allowed_mimes = ['video/mp4', 'video/webm'];
            if (!in_array($mime_type, $allowed_mimes)) {
                $response['error'] = 'Invalid video format';
                echo json_encode($response);
                exit;
            }
        }
        
        if (!in_array($ext, $allowed_videos)) {
            $response['error'] = 'Invalid video format';
            echo json_encode($response);
            exit;
        }
        
        // Check file size (max 50MB)
        if ($_FILES['video_file']['size'] > 50 * 1024 * 1024) {
            $response['error'] = 'Video size must be less than 50MB';
            echo json_encode($response);
            exit;
        }
        
        $new_filename = 'vid_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        $target_path = $upload_dir . basename($new_filename);
        
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_path)) {
            $response['success'] = true;
            $response['filename'] = basename($new_filename);
            $response['type'] = 'video';
        } else {
            $response['error'] = 'Failed to upload video';
        }
        echo json_encode($response);
        exit;
    }
}

echo json_encode($response);
