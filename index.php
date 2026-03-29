<?php
require_once 'config.php';

$conn = getDB();

// Get categories
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY sort_order");
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get filter values
$category_filter = $_GET['category'] ?? 'All';
$min_amount = $_GET['min_amount'] ?? 0;
$max_amount = $_GET['max_amount'] ?? 10000;
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

if ($min_amount > 0) {
    $sql .= " AND min_order_amount >= ?";
    $params[] = $min_amount;
    $types .= "d";
}

if ($max_amount < 10000) {
    $sql .= " AND (max_cashback <= ? OR max_cashback = 0)";
    $params[] = $max_amount;
    $types .= "d";
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

// Get featured offers for carousel
$featured_sql = "SELECT * FROM offers WHERE status = 'active' AND is_featured = 1 ORDER BY is_popular DESC, created_at DESC LIMIT 5";
$featured_result = $conn->query($featured_sql);
$featured_offers = $featured_result->fetch_all(MYSQLI_ASSOC);

// Get stats
$stats_sql = "SELECT 
    COUNT(*) as total_offers,
    SUM(claimed_count) as total_claimed,
    SUM(CASE WHEN expiry_date >= CURDATE() THEN 1 ELSE 0 END) as active_offers
FROM offers WHERE status = 'active'";
$stats = $conn->query($stats_sql)->fetch_assoc();

// Get expiring soon
$expiring_sql = "SELECT * FROM offers WHERE status = 'active' AND expiry_date >= CURDATE() AND DATEDIFF(expiry_date, CURDATE()) <= 7 ORDER BY expiry_date ASC LIMIT 3";
$expiring_result = $conn->query($expiring_sql);
$expiring_offers = $expiring_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>OSM – Offers & Cashback</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css"/>
<style>
  :root {
    --primary: #4f46e5;
    --primary-light: #eef2ff;
    --accent: #6366f1;
    --green: #10b981;
    --red: #ef4444;
    --orange: #f97316;
    --yellow: #eab308;
    --text: #1e1b4b;
    --text-sub: #6b7280;
    --bg: #f5f6fa;
    --card: #ffffff;
    --shadow-sm: 0 2px 8px rgba(79,70,229,0.07);
    --shadow-md: 0 6px 24px rgba(79,70,229,0.12);
    --shadow-lg: 0 16px 48px rgba(79,70,229,0.16);
    --radius: 20px;
    --radius-sm: 14px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  .hgi-stroke { display: inline-block; vertical-align: middle; font-size: 20px; }

  body {
    font-family: 'Mulish', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  .navbar {
    position: sticky; top: 0; z-index: 100;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(79,70,229,0.08);
    padding: 0 24px;
    height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 2px 12px rgba(79,70,229,0.07);
  }
  .logo {
    font-family: 'Nunito', sans-serif;
    font-weight: 900;
    font-size: 1.6rem;
    color: var(--primary);
    letter-spacing: -0.5px;
  }
  .logo span { color: var(--text); }
  .nav-icons { display: flex; gap: 18px; align-items: center; }
  .nav-icon {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--primary-light);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.2s;
    color: var(--primary);
  }
  .nav-icon:hover { background: var(--primary); color: #fff; transform: scale(1.08); }
  .nav-icon svg { width: 18px; height: 18px; }
  .admin-link {
    font-size: 0.75rem; color: var(--text-sub); text-decoration: none;
    padding: 6px 12px; border-radius: 8px; background: var(--bg);
  }
  .admin-link:hover { background: var(--primary-light); color: var(--primary); }

  .page-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 20px 100px;
  }

  .promo-scroll {
    display: flex; gap: 16px;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
    padding-bottom: 4px;
    margin-bottom: 36px;
  }
  .promo-scroll::-webkit-scrollbar { display: none; }
  .promo-card {
    flex: 0 0 auto;
    scroll-snap-align: start;
    border-radius: var(--radius);
    padding: 22px 26px;
    display: flex; align-items: center; gap: 20px;
    min-width: 300px; max-width: 400px;
    box-shadow: var(--shadow-md);
    position: relative; overflow: hidden;
    transition: transform 0.25s;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    cursor: pointer;
  }
  .promo-card:hover { transform: translateY(-3px); }
  .promo-card.salmon { background: linear-gradient(135deg, rgba(255, 240, 235, 0.9) 0%, rgba(255, 214, 200, 0.9) 100%); }
  .promo-card.mint { background: linear-gradient(135deg, rgba(232, 253, 244, 0.9) 0%, rgba(199, 246, 227, 0.9) 100%); }
  .promo-card.lavender { background: linear-gradient(135deg, rgba(240, 238, 255, 0.9) 0%, rgba(221, 214, 255, 0.9) 100%); }
  .promo-card.blue { background: linear-gradient(135deg, rgba(224, 242, 254, 0.9) 0%, rgba(186, 230, 253, 0.9) 100%); }
  
  .promo-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: rgba(255,255,255,0.6);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; flex-shrink: 0;
  }
  .promo-text h3 {
    font-family: 'Nunito', sans-serif;
    font-weight: 800; font-size: 1.05rem;
    color: var(--text); line-height: 1.3;
  }
  .promo-text p {
    font-size: 0.82rem; color: var(--text-sub);
    margin-top: 4px; line-height: 1.4;
  }
  .promo-badge {
    position: absolute; top: 14px; right: 16px;
    background: rgba(79, 70, 229, 0.85);
    backdrop-filter: blur(4px);
    color: #fff; font-size: 0.65rem; font-weight: 700;
    padding: 3px 9px; border-radius: 20px;
    font-family: 'Nunito', sans-serif; letter-spacing: 0.3px;
  }

  .section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap; gap: 12px;
  }
  .section-title {
    font-family: 'Nunito', sans-serif;
    font-weight: 900; font-size: 1.35rem; color: var(--text);
  }
  
  .filter-bar {
    display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
  }
  .filter-select {
    border: 1.5px solid var(--primary);
    background: #fff; color: var(--primary);
    font-family: 'Mulish', sans-serif;
    font-size: 0.82rem; font-weight: 600;
    padding: 7px 14px 7px 12px;
    border-radius: 12px; cursor: pointer;
    outline: none; appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%234f46e5' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 28px;
  }
  .filter-input {
    border: 1.5px solid rgba(79,70,229,0.3);
    background: #fff; color: var(--text);
    font-family: 'Mulish', sans-serif;
    font-size: 0.82rem; font-weight: 600;
    padding: 7px 12px;
    border-radius: 12px; outline: none;
    width: 100px;
  }
  .filter-input:focus { border-color: var(--primary); }

  .category-tabs {
    display: flex; gap: 10px;
    overflow-x: auto; scrollbar-width: none;
    margin-bottom: 22px;
    padding-bottom: 4px;
  }
  .category-tabs::-webkit-scrollbar { display: none; }
  .tab-pill {
    flex: 0 0 auto;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.8rem; font-weight: 600;
    cursor: pointer; transition: all 0.22s;
    border: 1.5px solid transparent;
    white-space: nowrap;
    background: #fff;
    color: var(--text-sub);
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .tab-pill.active {
    background: var(--primary); color: #fff;
    box-shadow: 0 4px 14px rgba(79,70,229,0.35);
  }
  .tab-pill:hover:not(.active) { border-color: var(--primary); color: var(--primary); }

  .offers-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 40px; }

  .offer-card {
    background: var(--card);
    border-radius: var(--radius-sm);
    padding: 16px 18px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    transition: all 0.25s;
    border: 1.5px solid transparent;
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
  }
  .offer-card::before {
    content: '';
    position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--primary);
    opacity: 0;
    transition: opacity 0.2s;
    border-radius: 3px 0 0 3px;
  }
  .offer-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: rgba(79,70,229,0.15);
  }
  .offer-card:hover::before { opacity: 1; }
  .offer-card.expired { opacity: 0.65; }

  .offer-logo {
    width: 52px; height: 52px; border-radius: 14px;
    overflow: hidden; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }
  .offer-logo img { width: 100%; height: 100%; object-fit: cover; }

  .offer-body { flex: 1; min-width: 0; }
  .offer-brand { font-size: 0.72rem; font-weight: 600; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
  .offer-title {
    font-size: 0.92rem; font-weight: 700; color: var(--text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 6px;
  }
  .offer-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .offer-claimed { font-size: 0.72rem; color: var(--text-sub); display: flex; align-items: center; gap: 4px; }
  .offer-claimed svg { width: 12px; height: 12px; }
  .offer-expiry {
    font-size: 0.7rem; font-weight: 600;
    display: flex; align-items: center; gap: 3px;
  }
  .offer-expiry.active { color: var(--primary); }
  .offer-expiry.expired-label { color: var(--red); }
  .offer-cashback {
    font-size: 0.7rem; font-weight: 700;
    background: var(--green-light);
    color: var(--green);
    padding: 2px 8px; border-radius: 8px;
  }
  .offer-arrow {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--primary-light);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: var(--primary);
    transition: all 0.2s;
  }
  .offer-card:hover .offer-arrow { background: var(--primary); color: #fff; }
  .offer-arrow svg { width: 16px; height: 16px; }

  .expire-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 40px;
  }
  .expire-card {
    border-radius: var(--radius-sm);
    padding: 18px 14px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    text-align: center;
    min-height: 120px;
    cursor: pointer;
    box-shadow: var(--shadow-sm);
    transition: transform 0.25s, box-shadow 0.25s;
    position: relative; overflow: hidden;
    text-decoration: none; color: inherit;
  }
  .expire-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
  .expire-card.pizza { background: linear-gradient(135deg, #ffcba4 0%, #ff8c69 100%); }
  .expire-card.flight { background: linear-gradient(135deg, #c7d9ff 0%, #93b4f7 100%); }
  .expire-card.ticket { background: linear-gradient(135deg, #d4f5a0 0%, #a3e635 100%); }
  .expire-card.food { background: linear-gradient(135deg, #fed7aa 0%, #fb923c 100%); }
  .expire-card.health { background: linear-gradient(135deg, #bbf7d0 0%, #4ade80 100%); }
  .expire-card.travel { background: linear-gradient(135deg, #c7d9ff 0%, #93b4f7 100%); }
  .expire-emoji { font-size: 2rem; margin-bottom: 8px; }
  .expire-text {
    font-size: 0.75rem; font-weight: 700;
    color: rgba(0,0,0,0.75); line-height: 1.35;
  }
  .expire-badge {
    position: absolute; top: 10px; right: 10px;
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(4px);
    font-size: 0.6rem; font-weight: 700;
    padding: 2px 7px; border-radius: 10px;
    color: rgba(0,0,0,0.65);
  }

  .stats-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 36px;
  }
  .stat-card {
    background: #fff;
    border-radius: var(--radius-sm);
    padding: 18px 14px;
    text-align: center;
    box-shadow: var(--shadow-sm);
  }
  .stat-value {
    font-family: 'Nunito', sans-serif;
    font-weight: 900; font-size: 1.5rem;
    color: var(--primary);
  }
  .stat-label { font-size: 0.72rem; color: var(--text-sub); margin-top: 2px; font-weight: 500; }

  .bottom-nav {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(16px);
    border-top: 1px solid rgba(79,70,229,0.08);
    height: 68px;
    display: flex; align-items: center; justify-content: space-around;
    padding: 0 8px;
    box-shadow: 0 -4px 20px rgba(79,70,229,0.08);
  }
  .nav-item {
    display: flex; flex-direction: column;
    align-items: center; gap: 4px;
    cursor: pointer; padding: 8px 18px;
    border-radius: 14px;
    transition: all 0.2s;
    flex: 1;
    text-decoration: none; color: var(--text-sub);
  }
  .nav-item svg { width: 22px; height: 22px; color: var(--text-sub); transition: color 0.2s; }
  .nav-item span { font-size: 0.62rem; font-weight: 600; color: var(--text-sub); transition: color 0.2s; }
  .nav-item.active { background: var(--primary-light); }
  .nav-item.active svg, .nav-item.active span { color: var(--primary); }

  .fab {
    position: fixed; bottom: 80px; right: 20px; z-index: 200;
    width: 54px; height: 54px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    box-shadow: 0 6px 20px rgba(79,70,229,0.45);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: transform 0.2s;
  }
  .fab:hover { transform: scale(1.1) rotate(5deg); }
  .fab svg { width: 24px; height: 24px; color: #fff; }

  @media (min-width: 768px) {
    .page-wrap { padding: 36px 32px 40px; }
    .bottom-nav { display: none; }
    .fab { display: none; }

    .desktop-sidebar { display: flex; gap: 32px; }
    .sidebar { width: 220px; flex-shrink: 0; }
    .sidebar-nav {
      background: #fff;
      border-radius: var(--radius);
      padding: 16px 12px;
      box-shadow: var(--shadow-sm);
      display: flex; flex-direction: column; gap: 4px;
    }
    .sidebar-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 14px; border-radius: 12px;
      cursor: pointer; transition: all 0.2s;
      font-size: 0.85rem; font-weight: 600;
      color: var(--text-sub);
      text-decoration: none;
    }
    .sidebar-item svg { width: 18px; height: 18px; }
    .sidebar-item.active { background: var(--primary-light); color: var(--primary); }
    .sidebar-item:hover:not(.active) { background: var(--bg); color: var(--text); }

    .main-content { flex: 1; min-width: 0; }
    .offers-list { gap: 10px; }
    .expire-grid { grid-template-columns: repeat(3, 1fr); }
    .stats-bar { grid-template-columns: repeat(3, 1fr); }
  }

  @media (max-width: 767px) {
    .desktop-sidebar { display: contents; }
    .sidebar { display: none; }
    .main-content { width: 100%; }
    .navbar { padding: 0 16px; }
    .page-wrap { padding: 20px 14px 90px; }
    .promo-card { min-width: 260px; }
    .expire-grid { grid-template-columns: 1fr; }
    .stats-bar { grid-template-columns: 1fr; }
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .offer-card { animation: fadeUp 0.4s ease both; }
  .offer-card:nth-child(1) { animation-delay: 0.05s; }
  .offer-card:nth-child(2) { animation-delay: 0.10s; }
  .offer-card:nth-child(3) { animation-delay: 0.15s; }
  .offer-card:nth-child(4) { animation-delay: 0.20s; }
  .offer-card:nth-child(5) { animation-delay: 0.25s; }
  .expire-card { animation: fadeUp 0.4s ease both; }
  .expire-card:nth-child(1) { animation-delay: 0.3s; }
  .expire-card:nth-child(2) { animation-delay: 0.35s; }
  .expire-card:nth-child(3) { animation-delay: 0.40s; }
</style>
</head>
<body>

<nav class="navbar">
  <div class="logo">Pay<span>ou</span></div>
  <div class="nav-icons">
    <a href="admin.php" class="admin-link">Admin</a>
    <div class="nav-icon">
      <i class="hgi-stroke hgi-user-account"></i>
    </div>
    <div class="nav-icon">
      <i class="hgi-stroke hgi-notification"></i>
    </div>
    <div class="nav-icon">
      <i class="hgi-stroke hgi-time"></i>
    </div>
  </div>
</nav>

<div class="page-wrap">
  <div class="desktop-sidebar">

    <aside class="sidebar">
      <div class="sidebar-nav">
        <a href="index.php" class="sidebar-item active">
          <i class="hgi-stroke hgi-home"></i>
          Home
        </a>
        <div class="sidebar-item">
          <i class="hgi-stroke hgi-tag"></i>
          Offers
        </div>
        <div class="sidebar-item">
          <i class="hgi-stroke hgi-history"></i>
          History
        </div>
        <div class="sidebar-item">
          <i class="hgi-stroke hgi-setting"></i>
          Account
        </div>
      </div>

      <div style="margin-top:20px; background:#fff; border-radius:var(--radius); padding:18px 16px; box-shadow:var(--shadow-sm);">
        <div style="font-family:'Nunito',sans-serif;font-weight:800;font-size:0.9rem;margin-bottom:14px;color:var(--text);">Your Savings</div>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div>
            <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:1.4rem;color:var(--primary);">₹2,840</div>
            <div style="font-size:0.72rem;color:var(--text-sub);">Total saved</div>
          </div>
          <div>
            <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:1.4rem;color:var(--green);">14</div>
            <div style="font-size:0.72rem;color:var(--text-sub);">Offers redeemed</div>
          </div>
          <div>
            <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:1.4rem;color:var(--orange);">3</div>
            <div style="font-size:0.72rem;color:var(--text-sub);">Expiring this week</div>
          </div>
        </div>
      </div>
    </aside>

    <div class="main-content">

      <!-- PROMO CARDS -->
      <?php if (!empty($featured_offers)): ?>
      <div class="promo-scroll">
        <?php foreach ($featured_offers as $index => $offer): ?>
        <?php $colors = ['salmon', 'mint', 'lavender', 'blue']; ?>
        <a href="offer.php?id=<?php echo $offer['id']; ?>" class="promo-card <?php echo $colors[$index % 4]; ?>">
          <?php if ($offer['is_popular']): ?><div class="promo-badge">HOT 🔥</div><?php endif; ?>
          <div class="promo-icon"><?php echo htmlspecialchars($offer['brand_emoji']); ?></div>
          <div class="promo-text">
            <h3><?php echo htmlspecialchars($offer['title']); ?></h3>
            <p><?php echo htmlspecialchars(substr($offer['description'], 0, 80)) . '...'; ?></p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- STATS (mobile only) -->
      <div class="stats-bar" style="display:none;" id="mobile-stats">
        <div class="stat-card">
          <div class="stat-value"><?php echo formatNumber($stats['total_claimed'] ?? 0); ?></div>
          <div class="stat-label">Total Saved</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['total_offers'] ?? 0; ?></div>
          <div class="stat-label">Offers</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['active_offers'] ?? 0; ?></div>
          <div class="stat-label">Active</div>
        </div>
      </div>

      <!-- CATEGORY TABS -->
      <div class="category-tabs">
        <a href="index.php" class="tab-pill <?php echo $category_filter === 'All' ? 'active' : ''; ?>">All</a>
        <?php foreach ($categories as $cat): ?>
        <a href="index.php?category=<?php echo urlencode($cat['name']); ?>" class="tab-pill <?php echo $category_filter === $cat['name'] ? 'active' : ''; ?>">
          <?php echo $cat['emoji'] . ' ' . htmlspecialchars($cat['name']); ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- TOP OFFERS -->
      <div class="section-header">
        <h2 class="section-title">Top Offers</h2>
        <form class="filter-bar" method="get">
          <?php if ($category_filter !== 'All'): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
          <?php endif; ?>
          <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest</option>
            <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
            <option value="expiry" <?php echo $sort_by === 'expiry' ? 'selected' : ''; ?>>Expiring Soon</option>
            <option value="cashback" <?php echo $sort_by === 'cashback' ? 'selected' : ''; ?>>Highest Cashback</option>
          </select>
          <input type="number" name="min_amount" class="filter-input" placeholder="Min ₹" value="<?php echo $min_amount; ?>" min="0" onchange="this.form.submit()">
          <input type="number" name="max_amount" class="filter-input" placeholder="Max ₹" value="<?php echo $max_amount < 10000 ? $max_amount : ''; ?>" min="0" onchange="this.form.submit()">
        </form>
      </div>

      <div class="offers-list">
        <?php foreach ($offers as $offer): ?>
        <?php 
          $is_expired = isExpired($offer['expiry_date']);
          $days_left = getDaysRemaining($offer['expiry_date']);
          $cashback_text = $offer['cashback_type'] === 'flat' ? '₹' . number_format($offer['max_cashback']) : $offer['cashback_rate'] . '%';
        ?>
        <a href="offer.php?id=<?php echo $offer['id']; ?>" class="offer-card <?php echo $is_expired ? 'expired' : ''; ?>">
          <div class="offer-logo" style="background:#fff5f5;">
            <span><?php echo htmlspecialchars($offer['brand_emoji']); ?></span>
          </div>
          <div class="offer-body">
            <div class="offer-brand"><?php echo htmlspecialchars($offer['brand_name']); ?></div>
            <div class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></div>
            <div class="offer-meta">
              <span class="offer-claimed">
                <i class="hgi-stroke hgi-users"></i>
                <?php echo formatNumber($offer['claimed_count']); ?> claimed
              </span>
              <span class="offer-cashback"><?php echo $cashback_text; ?> cashback</span>
              <?php if (!$is_expired): ?>
              <span class="offer-expiry active">📅 Ends <?php echo formatDate($offer['expiry_date']); ?></span>
              <?php else: ?>
              <span class="offer-expiry expired-label">❌ Expired</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="offer-arrow">
            <i class="hgi-stroke hgi-arrow-right"></i>
          </div>
        </a>
        <?php endforeach; ?>
        
        <?php if (empty($offers)): ?>
        <div style="text-align:center; padding: 40px; color: var(--text-sub);">
          <p>No offers found matching your criteria.</p>
          <a href="index.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Clear filters</a>
        </div>
        <?php endif; ?>
      </div>

      <!-- EXPIRE SOON -->
      <?php if (!empty($expiring_offers)): ?>
      <div class="section-header">
        <h2 class="section-title">Expire Soon ⏰</h2>
        <a href="index.php?sort=expiry" style="font-size:0.82rem;color:var(--primary);font-weight:600;text-decoration:none;">See all</a>
      </div>

      <div class="expire-grid">
        <?php foreach ($expiring_offers as $exp_offer): ?>
        <?php 
          $cat_class = strtolower($exp_offer['category']) === 'food' ? 'food' : 
                      (strtolower($exp_offer['category']) === 'travel' ? 'travel' : 
                      (strtolower($exp_offer['category']) === 'health' ? 'health' : 'ticket'));
          $days = getDaysRemaining($exp_offer['expiry_date']);
        ?>
        <a href="offer.php?id=<?php echo $exp_offer['id']; ?>" class="expire-card <?php echo $cat_class; ?>">
          <div class="expire-badge"><?php echo $days; ?> days left</div>
          <div class="expire-emoji"><?php echo htmlspecialchars($exp_offer['brand_emoji']); ?></div>
          <div class="expire-text"><?php echo htmlspecialchars($exp_offer['title']); ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<div class="fab">
  <i class="hgi-stroke hgi-grid"></i>
</div>

<nav class="bottom-nav">
  <a href="index.php" class="nav-item active">
    <i class="hgi-stroke hgi-home"></i>
    <span>Home</span>
  </a>
  <div class="nav-item">
    <i class="hgi-stroke hgi-history"></i>
    <span>History</span>
  </a>
  <a href="admin.php" class="nav-item">
    <i class="hgi-stroke hgi-tag"></i>
    <span>Offers</span>
  </a>
  <div class="nav-item">
    <i class="hgi-stroke hgi-calendar-02"></i>
    <span>Events</span>
  </div>
</nav>

<script>
  function handleResize() {
    const stats = document.getElementById('mobile-stats');
    if (window.innerWidth < 768) {
      stats.style.display = 'grid';
    } else {
      stats.style.display = 'none';
    }
  }
  handleResize();
  window.addEventListener('resize', handleResize);

  document.querySelectorAll('.tab-pill').forEach(pill => {
    pill.addEventListener('click', function() {
      document.querySelectorAll('.tab-pill').forEach(p => p.classList.remove('active'));
      this.classList.add('active');
    });
  });

  document.querySelectorAll('.sidebar-item').forEach(item => {
    item.addEventListener('click', function() {
      document.querySelectorAll('.sidebar-item').forEach(s => s.classList.remove('active'));
      this.classList.add('active');
    });
  });

  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function() {
      document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
      this.classList.add('active');
    });
  });
</script>

</body>
</html>
