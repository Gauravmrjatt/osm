<?php
require_once 'config.php';

header('Content-Type: application/json');

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
        
        if (!in_array($ext, $allowed_images)) {
            $response['error'] = 'Invalid image format';
            echo json_encode($response);
            exit;
        }
        
        $new_filename = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
            $response['success'] = true;
            $response['filename'] = $new_filename;
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
        
        $new_filename = 'vid_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_path)) {
            $response['success'] = true;
            $response['filename'] = $new_filename;
            $response['type'] = 'video';
        } else {
            $response['error'] = 'Failed to upload video';
        }
        echo json_encode($response);
        exit;
    }
}

echo json_encode($response);
?>
