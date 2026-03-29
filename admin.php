<?php
require_once 'config.php';

session_start();

$message = '';
$message_type = '';
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Password verification
if (!$is_logged_in) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_token'] = md5(uniqid(rand(), true));
            $is_logged_in = true;
        } else {
            $message = 'Incorrect password. Please try again.';
            $message_type = 'error';
        }
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle form submissions
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDB();
    
    // Delete offer
    if (isset($_POST['delete_offer'])) {
        $id = intval($_POST['offer_id']);
        
        // Get offer to delete associated files
        $result = $conn->query("SELECT logo_image, video_file FROM offers WHERE id = $id");
        if ($result && $row = $result->fetch_assoc()) {
            // Delete logo image
            if (!empty($row['logo_image']) && file_exists('uploads/' . $row['logo_image'])) {
                unlink('uploads/' . $row['logo_image']);
            }
            // Delete video file
            if (!empty($row['video_file']) && file_exists('uploads/' . $row['video_file'])) {
                unlink('uploads/' . $row['video_file']);
            }
        }
        
        $conn->query("DELETE FROM offers WHERE id = $id");
        $message = 'Offer and associated files deleted successfully.';
        $message_type = 'success';
        header('Location: admin.php?tab=offers');
        exit;
    }
    
    // Save offer (add or edit)
    if (isset($_POST['save_offer'])) {
        $id = intval($_POST['offer_id'] ?? 0);
        
        $title = $_POST['title'];
        $description = $_POST['description'];
        $brand_name = $_POST['brand_name'];
        $brand_emoji = $_POST['brand_emoji'];
        
        // Handle logo image upload (from hidden input or new upload)
        $logo_image = $_POST['logo_image'] ?? '';
        
        // Handle video upload (from hidden input or new upload)
        $video_file = $_POST['video_file'] ?? '';
        
        $category = $_POST['category'];
        $min_order_amount = floatval($_POST['min_order_amount']);
        $max_cashback = floatval($_POST['max_cashback']);
        $cashback_rate = floatval($_POST['cashback_rate']);
        $cashback_type = $_POST['cashback_type'];
        $expiry_date = $_POST['expiry_date'];
        $promo_code = $_POST['promo_code'];
        $redirect_url = $_POST['redirect_url'];
        $link2 = $_POST['link2'] ?? '';
        $claimed_count = intval($_POST['claimed_count']);
        $rating = floatval($_POST['rating']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $status = in_array($_POST['status'], ['active', 'expired', 'draft']) ? $_POST['status'] : 'active';
        
        // Steps
        $steps = [];
        if (!empty($_POST['step_title'])) {
            foreach ($_POST['step_title'] as $i => $step_title) {
                if (!empty($step_title)) {
                    $steps[] = [
                        'title' => $step_title,
                        'description' => $_POST['step_desc'][$i] ?? '',
                        'time' => $_POST['step_time'][$i] ?? ''
                    ];
                }
            }
        }
        
        // Terms
        $terms = [];
        if (!empty($_POST['term_text'])) {
            foreach ($_POST['term_text'] as $term) {
                if (!empty($term)) {
                    $terms[] = $term;
                }
            }
        }
        
        if ($id > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE offers SET title=?, description=?, brand_name=?, brand_emoji=?, logo_image=?, video_file=?, category=?, min_order_amount=?, max_cashback=?, cashback_rate=?, cashback_type=?, min_amount=?, max_amount=?, expiry_date=?, promo_code=?, redirect_url=?, link2=?, claimed_count=?, rating=?, is_featured=?, is_verified=?, is_popular=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssssssdddddddiiiii", $title, $description, $brand_name, $brand_emoji, $logo_image, $video_file, $category, $min_order_amount, $max_cashback, $cashback_rate, $cashback_type, $min_order_amount, $max_cashback, $expiry_date, $promo_code, $redirect_url, $link2, $claimed_count, $rating, $is_featured, $is_verified, $is_popular, $status, $id);
            $stmt->execute();
            
            // Delete old steps and terms
            $conn->query("DELETE FROM offer_steps WHERE offer_id = $id");
            $conn->query("DELETE FROM offer_terms WHERE offer_id = $id");
            
            $message = 'Offer updated successfully.';
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO offers (title, description, brand_name, brand_emoji, logo_image, video_file, category, min_order_amount, max_cashback, cashback_rate, cashback_type, min_amount, max_amount, expiry_date, promo_code, redirect_url, link2, claimed_count, rating, is_featured, is_verified, is_popular, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssdddsddssssidiiis", $title, $description, $brand_name, $brand_emoji, $logo_image, $video_file, $category, $min_order_amount, $max_cashback, $cashback_rate, $cashback_type, $min_order_amount, $max_cashback, $expiry_date, $promo_code, $redirect_url, $link2, $claimed_count, $rating, $is_featured, $is_verified, $is_popular, $status);
            $stmt->execute();
            $id = $conn->insert_id;
            
            $message = 'Offer created successfully.';
        }
        
        // Insert steps
        foreach ($steps as $i => $step) {
            $step_num = $i + 1;
            $step_title = $step['title'];
            $step_desc = $step['description'];
            $step_time = $step['time'];
            $stmt = $conn->prepare("INSERT INTO offer_steps (offer_id, step_number, step_title, step_description, step_time) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $id, $step_num, $step_title, $step_desc, $step_time);
            $stmt->execute();
        }
        
        // Insert terms
        foreach ($terms as $i => $term) {
            $term_num = $i + 1;
            $term_text = $term;
            $stmt = $conn->prepare("INSERT INTO offer_terms (offer_id, term_text, sort_order) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $id, $term_text, $term_num);
            $stmt->execute();
        }
        
        $message_type = 'success';
        header('Location: admin.php?tab=offers');
        exit;
    }
    
    $conn->close();
}

// Get categories
$conn = getDB();
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY sort_order");
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}
$default_category = !empty($categories) ? $categories[0]['name'] : 'Bank Accounts';

// Banner management
$banners = [];
$banner_result = $conn->query("SELECT * FROM banners ORDER BY sort_order");
while ($row = $banner_result->fetch_assoc()) {
    $banners[] = $row;
}

// Handle banner upload
if ($is_logged_in && isset($_POST['upload_banner'])) {
    if (!empty($_FILES['banner_image']['name'])) {
        $upload_dir = 'uploads/';
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target)) {
            $link_url = $_POST['banner_link'] ?? '';
            $sort_order = intval($_POST['banner_order'] ?? count($banners) + 1);
            $stmt = $conn->prepare("INSERT INTO banners (image_url, link_url, sort_order) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $filename, $link_url, $sort_order);
            $stmt->execute();
            $message = 'Banner uploaded successfully.';
            $message_type = 'success';
            header('Location: admin.php?tab=banners');
            exit;
        }
    }
}

// Delete banner
if ($is_logged_in && isset($_POST['delete_banner'])) {
    $banner_id = intval($_POST['banner_id']);
    $result = $conn->query("SELECT image_url FROM banners WHERE id = $banner_id");
    if ($result && $row = $result->fetch_assoc()) {
        if (!empty($row['image_url']) && file_exists('uploads/' . $row['image_url'])) {
            unlink('uploads/' . $row['image_url']);
        }
    }
    $conn->query("DELETE FROM banners WHERE id = $banner_id");
    $message = 'Banner deleted successfully.';
    $message_type = 'success';
    header('Location: admin.php?tab=banners');
    exit;
}

// Toggle banner status
if ($is_logged_in && isset($_POST['toggle_banner'])) {
    $banner_id = intval($_POST['banner_id']);
    $conn->query("UPDATE banners SET status = IF(status='active','inactive','active') WHERE id = $banner_id");
    header('Location: admin.php?tab=banners');
    exit;
}

// Refresh banners
$banners = [];
$banner_result = $conn->query("SELECT * FROM banners ORDER BY sort_order");
while ($row = $banner_result->fetch_assoc()) {
    $banners[] = $row;
}

// Get all offers
$offers_result = $conn->query("SELECT * FROM offers ORDER BY created_at DESC");
$offers = $offers_result->fetch_all(MYSQLI_ASSOC);

// Get offer for editing
$edit_offer = null;
$is_new_offer = false;
if (isset($_GET['edit']) && $is_logged_in) {
    $edit_id = intval($_GET['edit']);
    
    if ($edit_id > 0) {
        $edit_result = $conn->query("SELECT * FROM offers WHERE id = $edit_id");
        $edit_offer = $edit_result->fetch_assoc();
        
        if ($edit_offer) {
            // Get steps
            $steps_result = $conn->query("SELECT * FROM offer_steps WHERE offer_id = $edit_id ORDER BY step_number");
            $edit_offer['steps'] = $steps_result->fetch_all(MYSQLI_ASSOC);
            
            // Get terms
            $terms_result = $conn->query("SELECT * FROM offer_terms WHERE offer_id = $edit_id ORDER BY sort_order");
            $edit_offer['terms'] = $terms_result->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        // New offer form (edit=0)
        $is_new_offer = true;
        $edit_offer = [
            'id' => 0,
            'title' => '',
            'description' => '',
            'brand_name' => '',
            'brand_emoji' => '🎁',
            'category' => $default_category,
            'min_order_amount' => 0,
            'max_cashback' => 0,
            'cashback_rate' => 0,
            'cashback_type' => 'flat',
            'expiry_date' => date('Y-m-d', strtotime('+30 days')),
            'promo_code' => '',
            'redirect_url' => '',
            'link2' => '',
            'claimed_count' => 0,
            'rating' => 0,
            'is_featured' => 0,
            'is_verified' => 0,
            'is_popular' => 0,
            'status' => 'active',
            'steps' => [],
            'terms' => []
        ];
    }
}

// Category management
$active_tab = $_GET['tab'] ?? 'offers';
$edit_category = null;

// Delete category
if ($is_logged_in && isset($_POST['delete_category'])) {
    $cat_id = intval($_POST['category_id']);
    $conn->query("DELETE FROM categories WHERE id = $cat_id");
    $message = 'Category deleted successfully.';
    $message_type = 'success';
    header('Location: admin.php?tab=categories');
    exit;
}

// Add/Edit category
if ($is_logged_in && isset($_POST['save_category'])) {
    $cat_id = intval($_POST['category_id'] ?? 0);
    $cat_name = trim($_POST['category_name']);
    $cat_emoji = $_POST['category_emoji'];
    $cat_order = intval($_POST['category_order']);
    
    if (!empty($cat_name)) {
        if ($cat_id > 0) {
            $stmt = $conn->prepare("UPDATE categories SET name=?, emoji=?, sort_order=? WHERE id=?");
            $stmt->bind_param("ssii", $cat_name, $cat_emoji, $cat_order, $cat_id);
            $stmt->execute();
            $message = 'Category updated successfully.';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, emoji, sort_order) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $cat_name, $cat_emoji, $cat_order);
            $stmt->execute();
            $message = 'Category added successfully.';
        }
        $message_type = 'success';
        header('Location: admin.php?tab=categories');
        exit;
    }
}

// Get category for edit
if ($is_logged_in && isset($_GET['edit_category'])) {
    $edit_cat_id = intval($_GET['edit_category']);
    $cat_result = $conn->query("SELECT * FROM categories WHERE id = $edit_cat_id");
    $edit_category = $cat_result->fetch_assoc();
}

// Refresh categories list
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY sort_order");
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>OSM – Admin Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css"/>
<style>
  :root {
    --primary: #4f46e5;
    --primary-light: #eef2ff;
    --green: #10b981;
    --red: #ef4444;
    --orange: #f97316;
    --text: #1e1b4b;
    --text-sub: #6b7280;
    --bg: #f5f6fa;
    --card: #ffffff;
    --shadow-sm: 0 2px 8px rgba(79,70,229,0.07);
    --shadow-md: 0 6px 24px rgba(79,70,229,0.13);
    --radius: 14px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  .hgi-stroke { display: inline-block; vertical-align: middle; font-size: 20px; }
  body { font-family: 'Mulish', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding: 20px; }

  .navbar {
    background: #fff; border-radius: var(--radius); padding: 16px 24px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: var(--shadow-sm); margin-bottom: 24px;
  }
  .logo { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 1.4rem; color: var(--primary); }
  .nav-links { display: flex; gap: 16px; align-items: center; }
  .nav-links a { color: var(--text-sub); text-decoration: none; font-weight: 600; font-size: 0.85rem; }
  .nav-links a:hover { color: var(--primary); }
  .nav-links .btn { padding: 8px 16px; background: var(--primary-light); color: var(--primary); border-radius: 8px; }
  .nav-links .btn-logout { background: #fee2e2; color: var(--red); }

  .container { max-width: 1200px; margin: 0 auto; }
  
  .admin-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
  .admin-tabs .tab-btn {
    padding: 10px 20px; border: none; background: #fff; color: var(--text-sub);
    font-family: 'Mulish', sans-serif; font-weight: 600; font-size: 0.9rem;
    border-radius: 10px; cursor: pointer; transition: all 0.2s;
    box-shadow: var(--shadow-sm);
  }
  .admin-tabs .tab-btn:hover { background: var(--primary-light); color: var(--primary); }
  .admin-tabs .tab-btn.active { background: var(--primary); color: #fff; }
  
  .card {
    background: #fff; border-radius: var(--radius); padding: 24px;
    box-shadow: var(--shadow-sm); margin-bottom: 24px;
  }
  .card-title { font-family: 'Nunito', sans-serif; font-weight: 800; font-size: 1.1rem; margin-bottom: 16px; }

  .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; }
  .message.success { background: #d1fae5; color: var(--green); }
  .message.error { background: #fee2e2; color: var(--red); }

  .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
  .form-group { margin-bottom: 16px; }
  .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-sub); margin-bottom: 6px; }
  .form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 10px 14px; border: 1.5px solid #e5e7eb;
    border-radius: 10px; font-family: inherit; font-size: 0.9rem;
    transition: border-color 0.2s;
  }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none; border-color: var(--primary);
  }
  .form-group textarea { resize: vertical; min-height: 100px; }
  .checkbox-group { display: flex; gap: 16px; flex-wrap: wrap; }
  .checkbox-group label { display: flex; align-items: center; gap: 6px; font-weight: 500; cursor: pointer; }
  .checkbox-group input { width: auto; }

  .btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px;
    border: none; border-radius: 10px; font-family: inherit; font-weight: 700;
    font-size: 0.9rem; cursor: pointer; transition: all 0.2s; text-decoration: none;
  }
  .btn-primary { background: var(--primary); color: #fff; }
  .btn-primary:hover { background: #4338ca; transform: translateY(-1px); }
  .btn-secondary { background: var(--bg); color: var(--text); }
  .btn-secondary:hover { background: #e5e7eb; }
  .btn-danger { background: #fee2e2; color: var(--red); }
  .btn-danger:hover { background: #fecaca; }
  .btn-sm { padding: 6px 12px; font-size: 0.75rem; }

  .offers-table { width: 100%; border-collapse: collapse; }
  .offers-table th, .offers-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
  .offers-table th { font-size: 0.75rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; }
  .offers-table td { font-size: 0.85rem; }
  .offers-table tr:hover { background: var(--bg); }
  .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
  .status-active { background: #d1fae5; color: var(--green); }
  .status-expired { background: #fee2e2; color: var(--red); }
  .status-draft { background: #fef3c7; color: var(--orange); }

  .action-btns { display: flex; gap: 8px; }
  .steps-list, .terms-list { display: flex; flex-direction: column; gap: 12px; }
  .step-item, .term-item { display: flex; gap: 8px; align-items: flex-start; }
  .step-item input, .term-item input { flex: 1; }
  .step-item .time-input { width: 120px; flex: none; }
  .remove-btn { background: #fee2e2; color: var(--red); border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; flex-shrink: 0; }

  .password-form { max-width: 400px; margin: 100px auto; text-align: center; }
  .password-form .card { padding: 40px; }
  .password-form h2 { margin-bottom: 8px; }
  .password-form p { color: var(--text-sub); margin-bottom: 24px; }
  .password-form input { text-align: center; font-size: 1.1rem; padding: 14px; }

  @media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .offers-table { display: block; overflow-x: auto; }
  }
</style>
</head>
<body>

<?php if (!$is_logged_in): ?>
<div class="password-form">
  <div class="card">
    <h2 class="logo">Pay<span>ou</span> Admin</h2>
    <p>Enter password to access admin panel</p>
    <?php if ($message): ?>
    <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="form-group">
        <input type="password" name="password" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
    </form>

    <a href="index.php" style="display: block; margin-top: 16px; color: var(--primary); text-decoration: none; font-size: 0.85rem;">← Back to Home</a>
  </div>
</div>

<?php else: ?>

<div class="container">
  <nav class="navbar">
    <div class="logo">Pay<span>ou</span> Admin</div>
    <div class="nav-links">
      <a href="index.php">View Site</a>
      <a href="?logout=1" class="btn btn-logout">Logout</a>
    </div>
  </nav>

  <?php if ($message): ?>
  <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
  <?php endif; ?>

  <div class="admin-tabs">
    <a href="admin.php?tab=offers" class="tab-btn <?php echo $active_tab === 'offers' ? 'active' : ''; ?>">Offers</a>
    <a href="admin.php?tab=categories" class="tab-btn <?php echo $active_tab === 'categories' ? 'active' : ''; ?>">Categories</a>
    <a href="admin.php?tab=banners" class="tab-btn <?php echo $active_tab === 'banners' ? 'active' : ''; ?>">Banners</a>
  </div>

  <?php if ($active_tab === 'banners'): ?>
  
  <!-- Banners Management -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h2 class="card-title" style="margin: 0;">Manage Banners</h2>
    </div>
    
    <form method="post" enctype="multipart/form-data" style="background: var(--bg); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
      <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 12px; align-items: end;">
        <div class="form-group" style="margin: 0;">
          <label>Upload Banner Image</label>
          <input type="file" name="banner_image" accept="image/*" required>
        </div>
        <div class="form-group" style="margin: 0;">
          <label>Link URL (optional)</label>
          <input type="text" name="banner_link" placeholder="https://example.com">
        </div>
        <div class="form-group" style="margin: 0;">
          <label>Order</label>
          <input type="number" name="banner_order" value="<?php echo count($banners) + 1; ?>" min="1" style="width: 80px;">
        </div>
        <button type="submit" name="upload_banner" class="btn btn-primary">Upload</button>
      </div>
    </form>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
      <?php foreach ($banners as $banner): ?>
      <div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; position: relative;">
        <img src="uploads/<?php echo htmlspecialchars($banner['image_url']); ?>" style="width: 100%; height: 120px; object-fit: cover;">
        <div style="padding: 10px; display: flex; flex-direction: column; gap: 8px;">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <span class="status-badge status-<?php echo $banner['status']; ?>"><?php echo ucfirst($banner['status']); ?></span>
            <span style="font-size: 0.75rem; color: var(--text-sub);">Order: <?php echo $banner['sort_order']; ?></span>
          </div>
          <div style="display: flex; gap: 8px;">
            <form method="post" style="flex: 1;">
              <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
              <button type="submit" name="toggle_banner" class="btn btn-secondary btn-sm" style="width: 100%;"><?php echo $banner['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this banner?');">
              <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
              <button type="submit" name="delete_banner" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <?php if (empty($banners)): ?>
    <p style="text-align: center; color: var(--text-sub); padding: 40px;">No banners uploaded yet. Upload your first banner above.</p>
    <?php endif; ?>
  </div>

  <?php elseif ($active_tab === 'categories'): ?>
  
  <!-- Categories Management -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h2 class="card-title" style="margin: 0;">Manage Categories</h2>
      <a href="?tab=categories&edit_category=0" class="btn btn-primary btn-sm">+ Add Category</a>
    </div>
    
    <?php if ($edit_category !== null || (isset($_GET['edit_category']) && $_GET['edit_category'] == 0)): ?>
    <!-- Add/Edit Category Form -->
    <form method="post" style="background: var(--bg); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
      <input type="hidden" name="category_id" value="<?php echo $edit_category['id'] ?? 0; ?>">
      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
        <div class="form-group" style="margin: 0;">
          <label>Category Name</label>
          <input type="text" name="category_name" value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group" style="margin: 0;">
          <label>Emoji</label>
          <input type="text" name="category_emoji" value="<?php echo htmlspecialchars($edit_category['emoji'] ?? '📁'); ?>" maxlength="10">
        </div>
        <div class="form-group" style="margin: 0;">
          <label>Sort Order</label>
          <input type="number" name="category_order" value="<?php echo $edit_category['sort_order'] ?? count($categories) + 1; ?>" min="1">
        </div>
        <button type="submit" name="save_category" class="btn btn-primary">Save</button>
      </div>
      <a href="admin.php?tab=categories" class="btn btn-secondary btn-sm" style="margin-top: 12px;">Cancel</a>
    </form>
    <?php endif; ?>
    
    <table class="offers-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Emoji</th>
          <th>Name</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
          <td><?php echo $cat['sort_order']; ?></td>
          <td style="font-size: 1.5rem;"><?php echo htmlspecialchars($cat['emoji']); ?></td>
          <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
          <td>
            <div class="action-btns">
              <a href="?tab=categories&edit_category=<?php echo $cat['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                <input type="hidden" name="tab" value="categories">
                <button type="submit" name="delete_category" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php elseif ($edit_offer || $is_new_offer): ?>
  <!-- Edit/Add Offer Form -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2 class="card-title" style="margin: 0;"><?php echo ($edit_offer && $edit_offer['id'] > 0) ? 'Edit Offer' : 'Add New Offer'; ?></h2>
      <a href="admin.php?tab=offers" class="btn btn-secondary btn-sm">Cancel</a>
    </div>
    
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="offer_id" value="<?php echo $edit_offer['id'] ?? 0; ?>">
      
      <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-sub); margin-bottom: 12px;">Basic Info</h3>
      <div class="form-grid">
        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" value="<?php echo htmlspecialchars($edit_offer['title'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label>Brand Name *</label>
          <input type="text" name="brand_name" value="<?php echo htmlspecialchars($edit_offer['brand_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label>Brand Emoji</label>
          <input type="text" name="brand_emoji" value="<?php echo htmlspecialchars($edit_offer['brand_emoji'] ?? '🎁'); ?>" maxlength="10">
        </div>
        <div class="form-group">
          <label>Brand Logo Image</label>
          <div id="logo-preview-container">
            <?php if (!empty($edit_offer['logo_image'])): ?>
            <div style="margin-bottom: 8px;" id="logo-preview">
              <img src="uploads/<?php echo htmlspecialchars($edit_offer['logo_image']); ?>" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd;">
              <input type="hidden" name="logo_image" value="<?php echo htmlspecialchars($edit_offer['logo_image']); ?>">
              <button type="button" onclick="removeLogo()" style="margin-left:5px;padding:2px 8px;background:#fee2e2;color:#ef4444;border:none;border-radius:4px;cursor:pointer;">✕</button>
            </div>
            <?php endif; ?>
          </div>
          <input type="file" id="logo-input" accept="image/*" onchange="uploadFile(this, 'logo')">
          <small style="color: var(--text-sub);">Select image to upload</small>
          <div id="logo-progress" style="display:none;margin-top:8px;">
            <div style="background:#e5e7eb;border-radius:4px;height:8px;overflow:hidden;">
              <div id="logo-progress-bar" style="background:var(--primary);height:100%;width:0%;transition:width 0.3s;"></div>
            </div>
            <small id="logo-progress-text" style="color:var(--text-sub);">Uploading... 0%</small>
          </div>
        </div>
        <div class="form-group">
          <label>Promo Video (MP4/WebM)</label>
          <div id="video-preview-container">
            <?php if (!empty($edit_offer['video_file'])): ?>
            <div style="margin-bottom: 8px;" id="video-preview">
              <video width="120" height="80" style="object-fit:contain;border-radius:8px;border:1px solid #ddd;" controls>
                <source src="uploads/<?php echo htmlspecialchars($edit_offer['video_file']); ?>" type="video/mp4">
              </video>
              <input type="hidden" name="video_file" value="<?php echo htmlspecialchars($edit_offer['video_file']); ?>">
              <button type="button" onclick="removeVideo()" style="margin-left:5px;padding:2px 8px;background:#fee2e2;color:#ef4444;border:none;border-radius:4px;cursor:pointer;">✕</button>
            </div>
            <?php endif; ?>
          </div>
          <input type="file" id="video-input" accept="video/mp4,video/webm" onchange="uploadFile(this, 'video')">
          <small style="color: var(--text-sub);">Select video to upload (max 50MB)</small>
          <div id="video-progress" style="display:none;margin-top:8px;">
            <div style="background:#e5e7eb;border-radius:4px;height:8px;overflow:hidden;">
              <div id="video-progress-bar" style="background:var(--primary);height:100%;width:0%;transition:width 0.3s;"></div>
            </div>
            <small id="video-progress-text" style="color:var(--text-sub);">Uploading... 0%</small>
          </div>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['name']; ?>" <?php echo (isset($edit_offer['category']) && $edit_offer['category'] === $cat['name']) ? 'selected' : ''; ?>>
              <?php echo $cat['emoji'] . ' ' . $cat['name']; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      
      <div class="form-group">
        <label>Description</label>
        <textarea name="description"><?php echo htmlspecialchars($edit_offer['description'] ?? ''); ?></textarea>
      </div>
      
      <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-sub); margin: 20px 0 12px;">Cashback Details</h3>
      <div class="form-grid">
        <div class="form-group">
          <label>Min Order Amount (₹)</label>
          <input type="number" name="min_order_amount" value="<?php echo $edit_offer['min_order_amount'] ?? 0; ?>" min="0" step="1">
        </div>
        <div class="form-group">
          <label>Max Cashback (₹)</label>
          <input type="number" name="max_cashback" value="<?php echo $edit_offer['max_cashback'] ?? 0; ?>" min="0" step="1">
        </div>
        <div class="form-group">
          <label>Cashback Rate (%)</label>
          <input type="number" name="cashback_rate" value="<?php echo $edit_offer['cashback_rate'] ?? 0; ?>" min="0" max="100" step="0.1">
        </div>
        <div class="form-group">
          <label>Cashback Type</label>
          <select name="cashback_type">
            <option value="flat" <?php echo (isset($edit_offer['cashback_type']) && $edit_offer['cashback_type'] === 'flat') ? 'selected' : ''; ?>>Flat Amount</option>
            <option value="percentage" <?php echo (isset($edit_offer['cashback_type']) && $edit_offer['cashback_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
          </select>
        </div>
      </div>
      
      <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-sub); margin: 20px 0 12px;">Offer Details</h3>
      <div class="form-grid">
        <div class="form-group">
          <label>Expiry Date</label>
          <input type="date" name="expiry_date" value="<?php echo $edit_offer['expiry_date'] ?? ''; ?>">
        </div>
        <div class="form-group">
          <label>Promo Code</label>
          <input type="text" name="promo_code" value="<?php echo htmlspecialchars($edit_offer['promo_code'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Link 1 (Primary)</label>
          <input type="url" name="redirect_url" value="<?php echo htmlspecialchars($edit_offer['redirect_url'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Link 2 (Optional)</label>
          <input type="url" name="link2" value="<?php echo htmlspecialchars($edit_offer['link2'] ?? ''); ?>" placeholder="Optional second link">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="active" <?php echo (isset($edit_offer['status']) && $edit_offer['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="expired" <?php echo (isset($edit_offer['status']) && $edit_offer['status'] === 'expired') ? 'selected' : ''; ?>>Expired</option>
            <option value="draft" <?php echo (isset($edit_offer['status']) && $edit_offer['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
          </select>
        </div>
      </div>
      
      <div class="form-grid">
        <div class="form-group">
          <label>Claimed Count</label>
          <input type="number" name="claimed_count" value="<?php echo $edit_offer['claimed_count'] ?? 0; ?>" min="0">
        </div>
        <div class="form-group">
          <label>Rating</label>
          <input type="number" name="rating" value="<?php echo $edit_offer['rating'] ?? 0; ?>" min="0" max="5" step="0.1">
        </div>
      </div>
      
      <div class="form-group">
        <label>Options</label>
        <div class="checkbox-group">
          <label><input type="checkbox" name="is_featured" <?php echo (isset($edit_offer['is_featured']) && $edit_offer['is_featured']) ? 'checked' : ''; ?>> Featured</label>
          <label><input type="checkbox" name="is_verified" <?php echo (isset($edit_offer['is_verified']) && $edit_offer['is_verified']) ? 'checked' : ''; ?>> Verified</label>
          <label><input type="checkbox" name="is_popular" <?php echo (isset($edit_offer['is_popular']) && $edit_offer['is_popular']) ? 'checked' : ''; ?>> Popular</label>
        </div>
      </div>
      
      <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-sub); margin: 20px 0 12px;">Steps</h3>
      <div class="steps-list" id="steps-list">
        <?php 
        $steps = $edit_offer['steps'] ?? [];
        if (empty($steps)) {
            $steps = [['step_title' => '', 'step_description' => '', 'step_time' => '']];
        }
        foreach ($steps as $i => $step): ?>
        <div class="step-item">
          <span style="width: 24px; height: 24px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;"><?php echo $i + 1; ?></span>
          <input type="text" name="step_title[]" placeholder="Step title" value="<?php echo htmlspecialchars($step['step_title'] ?? ''); ?>">
          <input type="text" name="step_desc[]" placeholder="Description" value="<?php echo htmlspecialchars($step['step_description'] ?? ''); ?>">
          <input type="text" name="step_time[]" placeholder="Time (e.g., ~2 min)" class="time-input" value="<?php echo htmlspecialchars($step['step_time'] ?? ''); ?>">
          <button type="button" class="remove-btn" onclick="this.parentElement.remove();">×</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="addStep()" style="margin-top: 8px;">+ Add Step</button>
      
      <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-sub); margin: 20px 0 12px;">Terms & Conditions</h3>
      <div class="terms-list" id="terms-list">
        <?php 
        $terms = $edit_offer['terms'] ?? [];
        if (empty($terms)) {
            $terms = [['term_text' => '']];
        }
        foreach ($terms as $i => $term): ?>
        <div class="term-item">
          <span style="width: 24px; height: 24px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;"><?php echo $i + 1; ?></span>
          <input type="text" name="term_text[]" placeholder="Term condition" value="<?php echo htmlspecialchars($term['term_text'] ?? ''); ?>">
          <button type="button" class="remove-btn" onclick="this.parentElement.remove();">×</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="addTerm()" style="margin-top: 8px;">+ Add Term</button>
      
      <div style="margin-top: 24px; display: flex; gap: 12px;">
        <button type="submit" name="save_offer" class="btn btn-primary">Save Offer</button>
        <a href="admin.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
  
  <script>
    function addStep() {
      const list = document.getElementById('steps-list');
      const count = list.children.length + 1;
      const html = `<div class="step-item">
        <span style="width: 24px; height: 24px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">${count}</span>
        <input type="text" name="step_title[]" placeholder="Step title">
        <input type="text" name="step_desc[]" placeholder="Description">
        <input type="text" name="step_time[]" placeholder="Time (e.g., ~2 min)" class="time-input">
        <button type="button" class="remove-btn" onclick="this.parentElement.remove(); updateStepNumbers();">×</button>
      </div>`;
      list.insertAdjacentHTML('beforeend', html);
    }
    
    function addTerm() {
      const list = document.getElementById('terms-list');
      const count = list.children.length + 1;
      const html = `<div class="term-item">
        <span style="width: 24px; height: 24px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">${count}</span>
        <input type="text" name="term_text[]" placeholder="Term condition">
        <button type="button" class="remove-btn" onclick="this.parentElement.remove(); updateTermNumbers();">×</button>
      </div>`;
      list.insertAdjacentHTML('beforeend', html);
    }
    
    function updateStepNumbers() {
      document.querySelectorAll('#steps-list .step-item').forEach((item, i) => {
        item.querySelector('span').textContent = i + 1;
      });
    }
    
    function updateTermNumbers() {
      document.querySelectorAll('#terms-list .term-item').forEach((item, i) => {
        item.querySelector('span').textContent = i + 1;
      });
    }
  </script>

  <?php elseif ($active_tab === 'offers' || ($active_tab === '')): ?>
  
  <!-- Offers List -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h2 class="card-title" style="margin: 0;">All Offers</h2>
      <a href="?tab=offers&edit=0" class="btn btn-primary btn-sm">+ Add New Offer</a>
    </div>
    
    <table class="offers-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Brand</th>
          <th>Category</th>
          <th>Cashback</th>
          <th>Expires</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($offers as $offer): ?>
        <?php 
          $cashback = $offer['cashback_type'] === 'flat' ? '₹' . number_format($offer['max_cashback']) : $offer['cashback_rate'] . '%';
          $status_class = 'status-' . $offer['status'];
        ?>
        <tr>
          <td><?php echo $offer['id']; ?></td>
          <td><?php echo htmlspecialchars(substr($offer['title'], 0, 40)); ?><?php echo strlen($offer['title']) > 40 ? '...' : ''; ?></td>
          <td><?php echo htmlspecialchars($offer['brand_emoji'] . ' ' . $offer['brand_name']); ?></td>
          <td><?php echo htmlspecialchars($offer['category']); ?></td>
          <td><strong><?php echo $cashback; ?></strong></td>
          <td><?php echo date('d M Y', strtotime($offer['expiry_date'])); ?></td>
          <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($offer['status']); ?></span></td>
          <td>
            <div class="action-btns">
              <a href="?tab=offers&edit=<?php echo $offer['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this offer?');">
                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                <input type="hidden" name="tab" value="offers">
                <button type="submit" name="delete_offer" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($offers)): ?>
        <tr>
          <td colspan="8" style="text-align: center; color: var(--text-sub); padding: 40px;">
            No offers found. <a href="?edit=0" style="color: var(--primary);">Create your first offer</a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php endif; ?>
  
  <div style="text-align: center; padding: 20px; color: var(--text-sub); font-size: 0.75rem;">
    <a href="index.php" style="color: var(--primary); text-decoration: none;">View Website</a>
  </div>
</div>

<?php endif; ?>

<script>
function uploadFile(input, type) {
    const file = input.files[0];
    if (!file) return;
    
    const progressContainer = document.getElementById(type + '-progress');
    const progressBar = document.getElementById(type + '-progress-bar');
    const progressText = document.getElementById(type + '-progress-text');
    const previewContainer = document.getElementById(type + '-preview-container');
    
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = 'Uploading... 0%';
    
    const formData = new FormData();
    if (type === 'logo') {
        formData.append('logo_image', file);
    } else {
        formData.append('video_file', file);
    }
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressText.textContent = 'Uploading... ' + percent + '%';
        }
    });
    
    xhr.addEventListener('load', function() {
        progressBar.style.width = '100%';
        progressText.textContent = 'Upload complete!';
        
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Show preview
                let html = '';
                if (type === 'logo') {
                    html = '<div style="margin-bottom:8px;" id="logo-preview">' +
                        '<img src="uploads/' + response.filename + '" style="width:60px;height:60px;object-fit:contain;border-radius:8px;border:1px solid #ddd;">' +
                        '<input type="hidden" name="logo_image" value="' + response.filename + '">' +
                        '<button type="button" onclick="removeLogo()" style="margin-left:5px;padding:2px 8px;background:#fee2e2;color:#ef4444;border:none;border-radius:4px;cursor:pointer;">✕</button></div>';
                } else {
                    html = '<div style="margin-bottom:8px;" id="video-preview">' +
                        '<video width="120" height="80" style="object-fit:contain;border-radius:8px;border:1px solid #ddd;" controls>' +
                        '<source src="uploads/' + response.filename + '" type="video/mp4">' +
                        '</video>' +
                        '<input type="hidden" name="video_file" value="' + response.filename + '">' +
                        '<button type="button" onclick="removeVideo()" style="margin-left:5px;padding:2px 8px;background:#fee2e2;color:#ef4444;border:none;border-radius:4px;cursor:pointer;">✕</button></div>';
                }
                previewContainer.innerHTML = html;
                input.value = ''; // Clear input
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                }, 1500);
            } else {
                progressText.textContent = 'Error: ' + response.error;
                progressBar.style.background = '#ef4444';
            }
        } catch (e) {
            progressText.textContent = 'Error parsing response';
            progressBar.style.background = '#ef4444';
        }
    });
    
    xhr.addEventListener('error', function() {
        progressText.textContent = 'Upload failed';
        progressBar.style.background = '#ef4444';
    });
    
    xhr.open('POST', 'upload.php', true);
    xhr.send(formData);
}

function removeLogo() {
    document.getElementById('logo-preview-container').innerHTML = '';
}

function removeVideo() {
    document.getElementById('video-preview-container').innerHTML = '';
}
</script>

</body>
</html>
