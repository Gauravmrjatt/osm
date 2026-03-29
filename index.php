<?php
require_once 'config.php';
session_start();

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$conn = getDB();

// Get categories
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY sort_order");
while ($row = $cat_result->fetch_assoc()) {
  $categories[] = $row;
}

// Get active banners
$banners = [];
$banner_result = $conn->query("SELECT * FROM banners ORDER BY sort_order");
while ($row = $banner_result->fetch_assoc()) {
  $banners[] = $row;
}

// Get filter values
$category_filter = $_GET['category'] ?? 'All';
$sort_by = $_GET['sort'] ?? 'newest';

// Build query
$sql = "SELECT * FROM offers WHERE status = 'active'";
$params = [];
$types = "";

if ($category_filter !== 'All') {
  $sql .= " AND category = ?";
  $params[] = $category_filter;
  $types .= "s";
}

// Sorting
switch ($sort_by) {
  case 'newest':
    $sql .= " ORDER BY created_at DESC";
    break;
  case 'popular':
    $sql .= " ORDER BY claimed_count DESC";
    break;
  case 'expiry':
    $sql .= " ORDER BY expiry_date ASC";
    break;
  case 'cashback':
    $sql .= " ORDER BY max_cashback DESC";
    break;
  default:
    $sql .= " ORDER BY is_featured DESC, created_at DESC";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$offers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get featured offers
$featured_sql = "SELECT * FROM offers WHERE status = 'active' AND is_featured = 1 ORDER BY is_popular DESC, created_at DESC LIMIT 5";
$featured_result = $conn->query($featured_sql);
$featured_offers = $featured_result->fetch_all(MYSQLI_ASSOC);

// Get stats
$stats_sql = "SELECT 
    COUNT(*) as total_offers,
    SUM(claimed_count) as total_claimed,
    SUM(CASE WHEN expiry_date >= CURDATE() THEN 1 ELSE 0 END) as active_offers,
    COALESCE(SUM(max_cashback), 0) as max_cashback
FROM offers WHERE status = 'active'";
$stats = $conn->query($stats_sql)->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>OSM – Offers & Cashback</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css" />
  <style>
    :root {
      --bg-dark: #05070A;
      --bg-card: #0B0F14;
      --primary: #1E6BFF;
      --primary-light: #3EA6FF;
      --green: #00D26A;
      --text: #FFFFFF;
      --text-sub: #9AA4B2;
      --border: rgba(255,255,255,0.08);
      --shadow: 0 4px 20px rgba(0,0,0,0.4);
      --glow: 0 0 10px rgba(30,107,255,0.5);
      --radius: 18px;
      --radius-sm: 14px;
      --radius-pill: 999px;
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    .hgi-stroke {
      display: inline-block;
      vertical-align: middle;
      font-size: 20px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(180deg, var(--bg-dark) 0%, var(--bg-card) 100%);
      color: var(--text);
      min-height: 100vh;
      overflow-x: hidden;
    }

    .navbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(11, 15, 20, 0.9);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      padding: 0 16px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .logo {
      font-family: 'Inter', sans-serif;
      font-weight: 800;
      font-size: 1.4rem;
      letter-spacing: -0.5px;
    }

    .logo span {
      color: var(--primary-light);
    }

    .profile-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-sub);
      cursor: pointer;
    }

    .profile-icon svg {
      width: 18px;
      height: 18px;
    }

    .page-wrap {
      max-width: 600px;
      margin: 0 auto;
      padding: 16px;
      padding-bottom: 100px;
    }

    /* Hero Banner */
    .hero-banner {
      background: linear-gradient(135deg, #1a1f2e 0%, #0d1117 100%);
      border-radius: 20px;
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 20px;
      border: 1px solid var(--border);
      position: relative;
      overflow: hidden;
    }

    .hero-banner::before {
      content: '';
      position: absolute;
      right: -30px;
      top: -30px;
      width: 150px;
      height: 150px;
      background: radial-gradient(circle, rgba(30,107,255,0.15) 0%, transparent 70%);
    }

    .hero-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      flex-shrink: 0;
    }

    .hero-content {
      flex: 1;
    }

    .hero-label {
      font-size: 0.65rem;
      color: var(--text-sub);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 4px;
    }

    .hero-title {
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 2px;
    }

    .hero-title span {
      color: var(--primary-light);
    }

    .hero-subtitle {
      font-size: 0.75rem;
      color: var(--text-sub);
    }

    /* Tabs */
    .category-tabs {
      display: flex;
      gap: 8px;
      overflow-x: auto;
      scrollbar-width: none;
      margin-bottom: 20px;
      padding-bottom: 4px;
    }

    .category-tabs::-webkit-scrollbar {
      display: none;
    }

    .tab-pill {
      flex: 0 0 auto;
      padding: 10px 18px;
      border-radius: var(--radius-pill);
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.22s;
      white-space: nowrap;
      background: transparent;
      border: 1px solid rgba(255,255,255,0.1);
      color: var(--text-sub);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .tab-pill.active {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: #fff;
      border: none;
      box-shadow: var(--glow);
    }

    .tab-pill:hover:not(.active) {
      border-color: var(--primary);
      color: var(--text);
    }

    /* Offer Card */
    .offers-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 24px;
    }

    .offer-card {
      background: var(--bg-card);
      border-radius: var(--radius);
      padding: 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      border: 1px solid var(--border);
      cursor: pointer;
      transition: all 0.25s;
      text-decoration: none;
      color: inherit;
      position: relative;
    }

    .offer-card:hover {
      transform: translateY(-2px);
      border-color: rgba(30,107,255,0.3);
      box-shadow: var(--shadow);
    }

    .offer-card.expired {
      opacity: 0.5;
    }

    .offer-status {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 0.6rem;
      font-weight: 700;
      color: var(--green);
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .offer-status::before {
      content: '';
      width: 6px;
      height: 6px;
      background: var(--green);
      border-radius: 50%;
    }

    .offer-logo {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      overflow: hidden;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      background: rgba(255,255,255,0.05);
    }

    .offer-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .offer-body {
      flex: 1;
      min-width: 0;
    }

    .offer-brand {
      font-size: 0.7rem;
      font-weight: 600;
      color: var(--text-sub);
      margin-bottom: 2px;
    }

    .offer-title {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 6px;
    }

    .offer-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .offer-claimed {
      font-size: 0.68rem;
      color: var(--text-sub);
      display: flex;
      align-items: center;
      gap: 3px;
    }

    .offer-claimed svg {
      width: 11px;
      height: 11px;
    }

    .offer-cashback {
      font-size: 0.65rem;
      font-weight: 700;
      background: rgba(0,210,106,0.12);
      color: var(--green);
      padding: 2px 8px;
      border-radius: 6px;
    }

    .offer-cta {
      padding: 10px 16px;
      border-radius: var(--radius-pill);
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: #fff;
      font-size: 0.7rem;
      font-weight: 700;
      white-space: nowrap;
      flex-shrink: 0;
      box-shadow: var(--glow);
    }

    /* Stats Section */
    .stats-section {
      margin-bottom: 24px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }

    .stat-card {
      background: var(--bg-card);
      border-radius: var(--radius-sm);
      padding: 16px 10px;
      text-align: center;
      border: 1px solid var(--border);
    }

    .stat-icon {
      width: 32px;
      height: 32px;
      margin: 0 auto 6px;
      background: rgba(30,107,255,0.15);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-light);
    }

    .stat-icon svg {
      width: 16px;
      height: 16px;
    }

    .stat-value {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text);
    }

    .stat-label {
      font-size: 0.6rem;
      color: var(--text-sub);
      margin-top: 2px;
    }

    /* Trust Badges */
    .trust-badges {
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
      padding: 20px 0;
    }

    .trust-badge {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.65rem;
      color: var(--text-sub);
    }

    .trust-badge svg {
      width: 14px;
      height: 14px;
      color: var(--primary-light);
    }

    /* Filter Bar */
    .filter-bar {
      margin-bottom: 16px;
    }

    .filter-select {
      border: 1px solid rgba(255,255,255,0.1);
      background: var(--bg-card);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      font-size: 0.75rem;
      font-weight: 500;
      padding: 8px 12px;
      border-radius: 10px;
      cursor: pointer;
      outline: none;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239AA4B2' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 28px;
    }

    .filter-select:focus {
      border-color: var(--primary);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-sub);
    }

    .empty-state p {
      font-size: 0.85rem;
      margin-bottom: 12px;
    }

    .empty-state a {
      color: var(--primary-light);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.8rem;
    }

    /* Animations */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .offer-card {
      animation: fadeUp 0.3s ease both;
    }

    .offer-card:nth-child(1) { animation-delay: 0.02s; }
    .offer-card:nth-child(2) { animation-delay: 0.04s; }
    .offer-card:nth-child(3) { animation-delay: 0.06s; }
    .offer-card:nth-child(4) { animation-delay: 0.08s; }
    .offer-card:nth-child(5) { animation-delay: 0.10s; }
  </style>
</head>

<body>

  <nav class="navbar">
    <div class="logo">OS<span>M</span></div>
    <?php if ($is_admin): ?>
    <a href="admin" class="profile-icon" title="Admin Panel">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </a>
    <?php endif; ?>
  </nav>

  <div class="page-wrap">



    <!-- Banners Section -->
    <?php if (!empty($banners)): ?>
    <div class="banners-section" style="margin-bottom: 20px;">
      <div class="banners-scroll" id="bannersScroll" style="display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; padding-bottom: 4px; scroll-snap-type: x mandatory;">
        <?php foreach ($banners as $banner): ?>
          <?php if (!empty($banner['image_url'])): ?>
            <a href="<?php echo htmlspecialchars($banner['link_url'] ?? '#'); ?>" style="flex: 0 0 calc(100vw - 32px); display: block; width: calc(100vw - 32px); max-width: 400px; scroll-snap-align: start;">
              <img src="/uploads/<?php echo htmlspecialchars($banner['image_url']); ?>" alt="<?php echo htmlspecialchars($banner['title'] ?? 'Banner'); ?>" style="width: 100%; height: 100px; border-radius: 14px; object-fit: cover;">
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Featured Offers -->
    <?php if (!empty($featured_offers)): ?>
    <div class="featured-section" style="margin-bottom: 24px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text);">Featured Offers</h3>
        <span style="font-size: 0.65rem; color: var(--primary-light); font-weight: 600;">🔥 HOT</span>
      </div>
      <div class="featured-scroll" style="display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; padding-bottom: 4px;">
        <?php foreach ($featured_offers as $offer): ?>
          <?php
          $is_expired = isExpired($offer['expiry_date']);
          $cashback_text = $offer['cashback_type'] === 'flat' ? '₹' . number_format($offer['max_cashback']) : $offer['cashback_rate'] . '%';
          ?>
          <a href="offer?id=<?php echo $offer['id']; ?>" style="flex: 0 0 160px; text-decoration: none; color: inherit;">
            <div class="featured-card" style="background: var(--bg-card); border-radius: var(--radius); padding: 14px; border: 1px solid var(--border); height: 100%;">
              <div class="offer-logo" style="width: 40px; height: 40px; margin-bottom: 10px;">
                <?php if (!empty($offer['logo_image'])): ?>
                  <img src="uploads/<?php echo htmlspecialchars($offer['logo_image']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                <?php else: ?>
                  <span style="font-size: 1.2rem;"><?php echo htmlspecialchars($offer['brand_emoji']); ?></span>
                <?php endif; ?>
              </div>
              <div class="offer-title" style="font-size: 0.8rem; font-weight: 600; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($offer['title']); ?></div>
              <div class="offer-cashback" style="font-size: 0.65rem; font-weight: 700; background: rgba(0,210,106,0.12); color: var(--green); padding: 3px 8px; border-radius: 6px; display: inline-block;"><?php echo $cashback_text; ?> Cashback</div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Category Tabs -->
    <div class="category-tabs">
      <a href="index" class="tab-pill <?php echo $category_filter === 'All' ? 'active' : ''; ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a href="index.php?category=<?php echo urlencode($cat['name']); ?>" class="tab-pill <?php echo $category_filter === $cat['name'] ? 'active' : ''; ?>">
          <?php echo $cat['emoji'] . ' ' . htmlspecialchars($cat['name']); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <form method="get">
        <?php if ($category_filter !== 'All'): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
        <?php endif; ?>
        <select name="sort" class="filter-select" onchange="this.form.submit()">
          <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest</option>
          <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
          <option value="expiry" <?php echo $sort_by === 'expiry' ? 'selected' : ''; ?>>Expiring Soon</option>
          <option value="cashback" <?php echo $sort_by === 'cashback' ? 'selected' : ''; ?>>Highest Cashback</option>
        </select>
      </form>
    </div>

    <!-- Offers List -->
    <div class="offers-list">
      <?php foreach ($offers as $offer): ?>
        <?php
        $is_expired = isExpired($offer['expiry_date']);
        $cashback_text = $offer['cashback_type'] === 'flat' ? '₹' . number_format($offer['max_cashback']) : $offer['cashback_rate'] . '%';
        ?>
        <a href="offer?id=<?php echo $offer['id']; ?>" class="offer-card <?php echo $is_expired ? 'expired' : ''; ?>">
          <?php if (!$is_expired): ?>
            <div class="offer-status">LIVE</div>
          <?php endif; ?>
          
          <div class="offer-logo">
            <?php if (!empty($offer['logo_image'])): ?>
              <img src="uploads/<?php echo htmlspecialchars($offer['logo_image']); ?>">
            <?php else: ?>
              <span><?php echo htmlspecialchars($offer['brand_emoji']); ?></span>
            <?php endif; ?>
          </div>
          
          <div class="offer-body">
            <div class="offer-brand"><?php echo htmlspecialchars($offer['brand_name']); ?></div>
            <div class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></div>
            <div class="offer-meta">
              <span class="offer-claimed">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <?php echo formatNumber($offer['claimed_count']); ?>
              </span>
              <span class="offer-cashback"><?php echo $cashback_text; ?> Cashback</span>
            </div>
          </div>
          
          <div class="offer-cta">
            Claim Now
          </div>
        </a>
      <?php endforeach; ?>

      <?php if (empty($offers)): ?>
        <div class="empty-state">
          <p>No offers found matching your criteria.</p>
          <a href="index">Clear filters</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="stat-value"><?php echo formatNumber($stats['total_claimed'] ?? 0); ?></div>
          <div class="stat-label">Total Claims</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </div>
          <div class="stat-value"><?php echo $stats['active_offers'] ?? 0; ?></div>
          <div class="stat-label">Active Offers</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="stat-value">₹<?php echo number_format($stats['max_cashback'] ?? 0); ?></div>
          <div class="stat-label">Max Earning</div>
        </div>
      </div>
    </div>

    <!-- Trust Badges -->
    <div class="trust-badges">
      <div class="trust-badge">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        100% Safe
      </div>
      <div class="trust-badge">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Instant Payout
      </div>
      <div class="trust-badge">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        24/7 Support
      </div>
      <div class="trust-badge">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Trusted
      </div>
    </div>

  </div>

  <script>
    document.querySelectorAll('.tab-pill').forEach(pill => {
      pill.addEventListener('click', function() {
        document.querySelectorAll('.tab-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // Auto-scroll banners
    (function() {
      const scroll = document.getElementById('bannersScroll');
      if (!scroll) return;
      const banners = scroll.querySelectorAll('a');
      if (banners.length <= 1) return;
      let current = 0;
      setInterval(() => {
        current = (current + 1) % banners.length;
        scroll.scrollTo({ left: banners[current].offsetLeft - 16, top: 0, behavior: 'smooth' });
      }, 3000);
    })();
  </script>

</body>

</html>
