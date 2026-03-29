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
        $conn->query("DELETE FROM offers WHERE id = $id");
        $message = 'Offer deleted successfully.';
        $message_type = 'success';
    }
    
    // Save offer (add or edit)
    if (isset($_POST['save_offer'])) {
        $id = intval($_POST['offer_id'] ?? 0);
        
        $title = $_POST['title'];
        $description = $_POST['description'];
        $brand_name = $_POST['brand_name'];
        $brand_emoji = $_POST['brand_emoji'];
        
        // Handle logo image upload
        $logo_image = $_POST['existing_logo'] ?? '';
        if (!empty($_FILES['logo_image']['name'])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION);
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_path)) {
                $logo_image = $new_filename;
            }
        }
        
        $category = $_POST['category'];
        $min_order_amount = floatval($_POST['min_order_amount']);
        $max_cashback = floatval($_POST['max_cashback']);
        $cashback_rate = floatval($_POST['cashback_rate']);
        $cashback_type = $_POST['cashback_type'];
        $expiry_date = $_POST['expiry_date'];
        $promo_code = $_POST['promo_code'];
        $redirect_url = $_POST['redirect_url'];
        $claimed_count = intval($_POST['claimed_count']);
        $rating = floatval($_POST['rating']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_verified = isset($_POST['is_verified']) ? 1 : 0;
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $status = in_array($_POST['status'], ['active', 'expired', 'draft']) ? $_POST['status'] : 'active';
        
        // Steps
        $steps = [];
        if (!empty($_POST['step_title'])) {
            foreach ($_POST['step_title'] as $i => $title) {
                if (!empty($title)) {
                    $steps[] = [
                        'title' => $title,
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
            $stmt = $conn->prepare("UPDATE offers SET title=?, description=?, brand_name=?, brand_emoji=?, logo_image=?, category=?, min_order_amount=?, max_cashback=?, cashback_rate=?, cashback_type=?, expiry_date=?, promo_code=?, redirect_url=?, claimed_count=?, rating=?, is_featured=?, is_verified=?, is_popular=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssdddssssidiiisi", $title, $description, $brand_name, $brand_emoji, $logo_image, $category, $min_order_amount, $max_cashback, $cashback_rate, $cashback_type, $expiry_date, $promo_code, $redirect_url, $claimed_count, $rating, $is_featured, $is_verified, $is_popular, $status, $id);
            $stmt->execute();
            
            // Delete old steps and terms
            $conn->query("DELETE FROM offer_steps WHERE offer_id = $id");
            $conn->query("DELETE FROM offer_terms WHERE offer_id = $id");
            
            $message = 'Offer updated successfully.';
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO offers (title, description, brand_name, brand_emoji, logo_image, category, min_order_amount, max_cashback, cashback_rate, cashback_type, expiry_date, promo_code, redirect_url, claimed_count, rating, is_featured, is_verified, is_popular, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdddssssidiiis", $title, $description, $brand_name, $brand_emoji, $logo_image, $category, $min_order_amount, $max_cashback, $cashback_rate, $cashback_type, $expiry_date, $promo_code, $redirect_url, $claimed_count, $rating, $is_featured, $is_verified, $is_popular, $status);
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
            'category' => 'General',
            'min_order_amount' => 0,
            'max_cashback' => 0,
            'cashback_rate' => 0,
            'cashback_type' => 'flat',
            'expiry_date' => date('Y-m-d', strtotime('+30 days')),
            'promo_code' => '',
            'redirect_url' => '',
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

  <?php if ($edit_offer || $is_new_offer): ?>
  <!-- Edit/Add Offer Form -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2 class="card-title" style="margin: 0;"><?php echo ($edit_offer && $edit_offer['id'] > 0) ? 'Edit Offer' : 'Add New Offer'; ?></h2>
      <a href="admin.php" class="btn btn-secondary btn-sm">Cancel</a>
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
          <?php if (!empty($edit_offer['logo_image'])): ?>
          <div style="margin-bottom: 8px;">
            <img src="uploads/<?php echo htmlspecialchars($edit_offer['logo_image']); ?>" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd;">
            <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($edit_offer['logo_image']); ?>">
          </div>
          <?php endif; ?>
          <input type="file" name="logo_image" accept="image/*">
          <small style="color: var(--text-sub);">Leave empty to keep existing logo</small>
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
          <label>Redirect URL</label>
          <input type="url" name="redirect_url" value="<?php echo htmlspecialchars($edit_offer['redirect_url'] ?? ''); ?>">
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

  <?php else: ?>
  
  <!-- Offers List -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h2 class="card-title" style="margin: 0;">All Offers</h2>
      <a href="?edit=0" class="btn btn-primary btn-sm">+ Add New Offer</a>
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
              <a href="?edit=<?php echo $offer['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this offer?');">
                <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
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

</body>
</html>
