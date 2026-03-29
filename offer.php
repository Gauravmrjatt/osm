<?php
require_once 'config.php';
session_start();

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$conn = getDB();
$offer_id = $_GET['id'] ?? 0;

// Get offer details
$stmt = $conn->prepare("SELECT * FROM offers WHERE id = ?");
$stmt->bind_param("i", $offer_id);
$stmt->execute();
$offer = $stmt->get_result()->fetch_assoc();

if (!$offer) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

// Get offer images
$images = [];
$img_stmt = $conn->prepare("SELECT * FROM offer_images WHERE offer_id = ? ORDER BY sort_order");
$img_stmt->bind_param("i", $offer_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
while ($row = $img_result->fetch_assoc()) {
    $images[] = $row;
}

// Get offer steps
$steps = [];
$steps_stmt = $conn->prepare("SELECT * FROM offer_steps WHERE offer_id = ? ORDER BY step_number");
$steps_stmt->bind_param("i", $offer_id);
$steps_stmt->execute();
$steps_result = $steps_stmt->get_result();
while ($row = $steps_result->fetch_assoc()) {
    $steps[] = $row;
}

// Get offer terms
$terms = [];
$terms_stmt = $conn->prepare("SELECT * FROM offer_terms WHERE offer_id = ? ORDER BY sort_order");
$terms_stmt->bind_param("i", $offer_id);
$terms_stmt->execute();
$terms_result = $terms_stmt->get_result();
while ($row = $terms_result->fetch_assoc()) {
    $terms[] = $row;
}

$conn->close();

// Calculate values
$is_expired = isExpired($offer['expiry_date']);
$days_left = getDaysRemaining($offer['expiry_date']);
$cashback_display = $offer['cashback_type'] === 'flat' ? '₹' . number_format($offer['max_cashback']) : $offer['cashback_rate'] . '%';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>OSM – <?php echo htmlspecialchars($offer['title']); ?></title>
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
    --green-light: #d1fae5;
    --red: #ef4444;
    --orange: #f97316;
    --text: #1e1b4b;
    --text-sub: #6b7280;
    --bg: #f5f6fa;
    --card: #ffffff;
    --shadow-sm: 0 2px 8px rgba(79,70,229,0.07);
    --shadow-md: 0 6px 24px rgba(79,70,229,0.13);
    --shadow-lg: 0 16px 48px rgba(79,70,229,0.18);
    --radius: 22px;
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
    position: sticky; top: 0; z-index: 200;
    background: rgba(255,255,255,0.93);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid rgba(79,70,229,0.08);
    height: 62px;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px;
    box-shadow: 0 2px 12px rgba(79,70,229,0.07);
  }
  .logo { font-family:'Nunito',sans-serif; font-weight:900; font-size:1.55rem; color:var(--primary); letter-spacing:-0.5px; }
  .logo span { color:var(--text); }
  .back-btn {
    display:flex; align-items:center; gap:8px;
    background: var(--primary-light); color:var(--primary);
    border:none; border-radius:12px; padding:8px 14px;
    font-family:'Mulish',sans-serif; font-size:0.82rem; font-weight:600;
    cursor:pointer; transition:all 0.2s; text-decoration:none;
  }
  .back-btn:hover { background:var(--primary); color:#fff; }
  .back-btn svg { width:15px; height:15px; }

  .page {
    max-width: 980px;
    margin: 0 auto;
    padding: 28px 18px 100px;
    display: grid;
    grid-template-columns: 1fr;
    gap: 22px;
  }
  @media(min-width:768px) {
    .page { grid-template-columns: 1fr 380px; padding: 36px 28px 60px; gap: 28px; }
  }

  .carousel-wrap { position:relative; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-md); background:#fff; }
  .carousel-track { display:flex; transition:transform 0.45s cubic-bezier(.4,0,.2,1); }
  .carousel-slide {
    flex:0 0 100%; height:260px;
    display:flex; align-items:center; justify-content:center;
    font-size:5rem;
    position:relative; overflow:hidden;
  }
  .carousel-slide.s1 { background:linear-gradient(135deg,#fff5f5 0%,#ffd6c8 100%); }
  .carousel-slide.s2 { background:linear-gradient(135deg,#fff8e6 0%,#fde68a 100%); }
  .carousel-slide.s3 { background:linear-gradient(135deg,#f0fdf4 0%,#bbf7d0 100%); }
  .carousel-slide.s4 { background:linear-gradient(135deg,#eff6ff 0%,#bfdbfe 100%); }
  .carousel-slide .slide-label {
    position:absolute; bottom:16px; left:50%; transform:translateX(-50%);
    background:rgba(255,255,255,0.75); backdrop-filter:blur(8px);
    border-radius:20px; padding:5px 16px;
    font-size:0.72rem; font-weight:700; color:var(--text);
    white-space:nowrap;
  }
  .carousel-deco {
    position:absolute; top:14px; right:14px;
    background:var(--primary); color:#fff;
    font-family:'Nunito',sans-serif; font-weight:800; font-size:0.75rem;
    padding:4px 12px; border-radius:20px;
    box-shadow:0 4px 12px rgba(79,70,229,0.35);
  }

  .c-prev, .c-next {
    position:absolute; top:50%; transform:translateY(-50%);
    width:36px; height:36px; border-radius:50%;
    background:rgba(255,255,255,0.85); backdrop-filter:blur(6px);
    border:none; cursor:pointer; display:flex; align-items:center; justify-content:center;
    box-shadow:var(--shadow-sm); color:var(--text); transition:all 0.2s; z-index:10;
  }
  .c-prev { left:12px; } .c-next { right:12px; }
  .c-prev:hover, .c-next:hover { background:var(--primary); color:#fff; }
  .c-prev svg, .c-next svg { width:16px; height:16px; }

  .c-dots { display:flex; justify-content:center; gap:7px; padding:12px 0 6px; }
  .c-dot { width:8px; height:8px; border-radius:50%; background:rgba(79,70,229,0.2); cursor:pointer; transition:all 0.2s; }
  .c-dot.active { width:22px; border-radius:4px; background:var(--primary); }

  .brand-row {
    display:flex; align-items:center; gap:14px;
    background:#fff; border-radius:var(--radius-sm);
    padding:16px 18px; box-shadow:var(--shadow-sm);
  }
  .brand-logo {
    width:54px; height:54px; border-radius:14px;
    background:#fff5f5; display:flex; align-items:center; justify-content:center;
    font-size:2rem; flex-shrink:0;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
  }
  .brand-info h2 { font-family:'Nunito',sans-serif; font-weight:900; font-size:1.2rem; color:var(--text); }
  .brand-info p { font-size:0.75rem; color:var(--text-sub); margin-top:2px; }
  .brand-badges { margin-left:auto; display:flex; flex-direction:column; gap:5px; align-items:flex-end; }
  .badge { font-size:0.68rem; font-weight:700; padding:3px 10px; border-radius:20px; }
  .badge.green { background:var(--green-light); color:var(--green); }
  .badge.blue  { background:var(--primary-light); color:var(--primary); }
  .badge.red { background:#fee2e2; color:var(--red); }

  .card { background:#fff; border-radius:var(--radius-sm); padding:20px 20px; box-shadow:var(--shadow-sm); }
  .card-title { font-family:'Nunito',sans-serif; font-weight:800; font-size:0.95rem; color:var(--text); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
  .card-title svg { width:18px; height:18px; color:var(--primary); }

  .desc-text { font-size:0.86rem; color:var(--text-sub); line-height:1.75; }
  .desc-text strong { color:var(--text); font-weight:700; }

  .timeline { display:flex; flex-direction:column; gap:0; }
  .tl-item { display:flex; gap:16px; position:relative; }
  .tl-left { display:flex; flex-direction:column; align-items:center; width:36px; flex-shrink:0; }
  .tl-dot {
    width:36px; height:36px; border-radius:50%;
    background:var(--primary-light); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-family:'Nunito',sans-serif; font-weight:900; font-size:0.85rem;
    flex-shrink:0; position:relative; z-index:1;
    border:2px solid var(--primary);
    transition:all 0.3s;
  }
  .tl-item.done .tl-dot { background:var(--primary); color:#fff; }
  .tl-line { width:2px; flex:1; background:linear-gradient(to bottom, var(--primary) 0%, rgba(79,70,229,0.15) 100%); min-height:28px; margin:4px 0; }
  .tl-item:last-child .tl-line { display:none; }
  .tl-body { padding-bottom:24px; flex:1; }
  .tl-step-title { font-weight:700; font-size:0.9rem; color:var(--text); margin-bottom:4px; }
  .tl-step-desc { font-size:0.78rem; color:var(--text-sub); line-height:1.55; }
  .tl-time {
    display:inline-flex; align-items:center; gap:4px;
    background:var(--primary-light); color:var(--primary);
    font-size:0.68rem; font-weight:700;
    padding:2px 9px; border-radius:10px; margin-top:6px;
  }
  .tl-time svg { width:11px; height:11px; }

  .amount-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .amount-cell {
    background:linear-gradient(135deg,var(--primary-light),#e0e7ff);
    border-radius:var(--radius-sm); padding:16px 14px; text-align:center;
  }
  .amount-cell.green-cell { background:linear-gradient(135deg,var(--green-light),#a7f3d0); }
  .amount-cell .ac-label { font-size:0.7rem; font-weight:600; color:var(--text-sub); margin-bottom:6px; }
  .amount-cell .ac-value { font-family:'Nunito',sans-serif; font-weight:900; font-size:1.45rem; color:var(--text); }
  .amount-cell .ac-sub { font-size:0.68rem; color:var(--text-sub); margin-top:3px; }

  .payment-timeline {
    margin-top:16px;
    background: linear-gradient(90deg,var(--primary-light) 0%,#e0e7ff 100%);
    border-radius:12px; padding:14px 16px;
    display:flex; align-items:center; gap:0;
    position:relative; overflow:hidden;
  }
  .pt-step { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; }
  .pt-icon { width:32px; height:32px; border-radius:50%; background:#fff; box-shadow:var(--shadow-sm); display:flex; align-items:center; justify-content:center; font-size:1rem; margin-bottom:5px; }
  .pt-label { font-size:0.62rem; font-weight:700; color:var(--text); text-align:center; line-height:1.3; }
  .pt-time { font-size:0.6rem; color:var(--primary); font-weight:600; }
  .pt-connector { flex:1; height:2px; background:linear-gradient(90deg,var(--primary),var(--accent)); align-self:center; margin-bottom:22px; }

  .alert-box {
    border-radius:var(--radius-sm); padding:14px 16px;
    display:flex; gap:12px; align-items:flex-start;
    border:1.5px solid;
  }
  .alert-box.info { background:#eff6ff; border-color:#bfdbfe; }
  .alert-box.warn { background:#fffbeb; border-color:#fde68a; }
  .alert-box.success { background:#f0fdf4; border-color:#bbf7d0; }
  .alert-icon { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
  .alert-box.info .alert-icon { background:#dbeafe; }
  .alert-box.warn .alert-icon { background:#fef9c3; }
  .alert-box.success .alert-icon { background:#dcfce7; }
  .alert-content h4 { font-size:0.82rem; font-weight:700; color:var(--text); margin-bottom:2px; }
  .alert-content p { font-size:0.75rem; color:var(--text-sub); line-height:1.5; }

  .cta-btn {
    width:100%; padding:17px; border:none; border-radius:var(--radius-sm);
    background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);
    color:#fff; font-family:'Nunito',sans-serif; font-weight:900; font-size:1.05rem;
    cursor:pointer; box-shadow:0 6px 20px rgba(79,70,229,0.4);
    display:flex; align-items:center; justify-content:center; gap:10px;
    transition:all 0.25s; letter-spacing:0.2px;
    position:relative; overflow:hidden;
    text-decoration: none;
  }
  .cta-btn::after {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg,rgba(255,255,255,0.15),transparent);
    opacity:0; transition:opacity 0.2s;
  }
  .cta-btn:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(79,70,229,0.5); }
  .cta-btn:hover::after { opacity:1; }
  .cta-btn svg { width:20px; height:20px; }
  .cta-btn.expired { background: linear-gradient(135deg,#9ca3af,#6b7280); cursor: not-allowed; }

  .cta-note { text-align:center; font-size:0.72rem; color:var(--text-sub); margin-top:8px; }
  .cta-note span { color:var(--primary); font-weight:600; }

  .stats-row { display:flex; gap:10px; }
  .stat-pill {
    flex:1; background:#fff; border-radius:12px; padding:12px 8px; text-align:center;
    box-shadow:var(--shadow-sm);
  }
  .stat-pill .sv { font-family:'Nunito',sans-serif; font-weight:900; font-size:1.1rem; color:var(--primary); }
  .stat-pill .sl { font-size:0.65rem; color:var(--text-sub); margin-top:2px; }

  .modal-overlay {
    position:fixed; inset:0; z-index:1000;
    background:rgba(30,27,75,0.55); backdrop-filter:blur(6px);
    display:none; align-items:center; justify-content:center; padding:20px;
  }
  .modal-overlay.open { display:flex; }
  .modal {
    background:#fff; border-radius:24px; max-width:420px; width:100%;
    padding:32px 28px; text-align:center;
    box-shadow:0 24px 80px rgba(79,70,229,0.25);
    animation:popIn 0.3s cubic-bezier(.4,0,.2,1) both;
  }
  @keyframes popIn { from{transform:scale(0.88) translateY(20px);opacity:0;} to{transform:scale(1) translateY(0);opacity:1;} }
  .modal-icon { font-size:3.2rem; margin-bottom:16px; }
  .modal h3 { font-family:'Nunito',sans-serif; font-weight:900; font-size:1.3rem; color:var(--text); margin-bottom:10px; }
  .modal p { font-size:0.85rem; color:var(--text-sub); line-height:1.7; margin-bottom:22px; }
  .modal-actions { display:flex; gap:12px; }
  .modal-cancel {
    flex:1; padding:13px; border:1.5px solid rgba(79,70,229,0.25);
    border-radius:14px; background:#fff; color:var(--text-sub);
    font-family:'Mulish',sans-serif; font-weight:600; font-size:0.88rem;
    cursor:pointer; transition:all 0.2s;
  }
  .modal-cancel:hover { border-color:var(--primary); color:var(--primary); }
  .modal-confirm {
    flex:1; padding:13px; border:none;
    border-radius:14px; background:linear-gradient(135deg,#4f46e5,#6366f1);
    color:#fff; font-family:'Mulish',sans-serif; font-weight:700; font-size:0.88rem;
    cursor:pointer; box-shadow:0 4px 14px rgba(79,70,229,0.4);
    transition:all 0.2s;
  }
  .modal-confirm:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(79,70,229,0.5); }
  .modal-tip { font-size:0.72rem; color:var(--text-sub); margin-top:14px; display:flex; align-items:center; justify-content:center; gap:5px; }
  .modal-tip svg { width:13px; height:13px; color:var(--orange); }

  .bottom-nav {
    position:fixed; bottom:0; left:0; right:0; z-index:100;
    background:rgba(255,255,255,0.95); backdrop-filter:blur(16px);
    border-top:1px solid rgba(79,70,229,0.08);
    height:66px; display:flex; align-items:center; justify-content:space-around;
    padding:0 8px; box-shadow:0 -4px 20px rgba(79,70,229,0.08);
  }
  .nav-item {
    display:flex; flex-direction:column; align-items:center; gap:3px;
    cursor:pointer; padding:7px 16px; border-radius:14px; transition:all 0.2s; flex:1;
    text-decoration:none;
  }
  .nav-item svg { width:22px; height:22px; color:var(--text-sub); }
  .nav-item span { font-size:0.6rem; font-weight:600; color:var(--text-sub); }
  .nav-item.active { background:var(--primary-light); }
  .nav-item.active svg, .nav-item.active span { color:var(--primary); }
  @media(min-width:768px) { .bottom-nav { display:none; } }

  @keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
  .card { animation:fadeUp 0.4s ease both; }
  .card:nth-child(1){animation-delay:.05s}
  .card:nth-child(2){animation-delay:.1s}
  .card:nth-child(3){animation-delay:.15s}
  .card:nth-child(4){animation-delay:.2s}
  .card:nth-child(5){animation-delay:.25s}
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="back-btn">
    <i class="hgi-stroke hgi-arrow-left"></i>
    Back
  </a>
  <div class="logo">Pay<span>ou</span></div>
  <div style="width:74px;display:flex;justify-content:flex-end;">
    <?php if ($is_admin): ?>
    <a href="admin.php?edit=<?php echo $offer['id']; ?>" style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;color:var(--primary);cursor:pointer;text-decoration:none;">
      <i class="hgi-stroke hgi-edit-01"></i>
    </a>
    <?php endif; ?>
  </div>
</nav>

<div class="page">

  <div style="display:flex;flex-direction:column;gap:18px;">

    <div class="carousel-wrap">
      <div class="carousel-track" id="track">
        <?php if (!empty($offer['video_file'])): ?>
        <div class="carousel-slide s1">
          <video width="100%" height="100%" style="object-fit:contain;background:#000;" autoplay muted loop playsinline>
            <source src="uploads/<?php echo htmlspecialchars($offer['video_file']); ?>" type="video/mp4">
          </video>
          <div class="carousel-deco"><?php echo $cashback_display; ?></div>
          <div class="slide-label"><?php echo htmlspecialchars($offer['title']); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($offer['logo_image'])): ?>
        <div class="carousel-slide s2">
          <img src="uploads/<?php echo htmlspecialchars($offer['logo_image']); ?>" style="width:100%;height:100%;object-fit:contain;">
          <div class="carousel-deco"><?php echo $cashback_display; ?></div>
          <div class="slide-label"><?php echo htmlspecialchars($offer['title']); ?></div>
        </div>
        <?php endif; ?>
        <?php if (empty($offer['video_file']) && empty($offer['logo_image'])): ?>
        <div class="carousel-slide s1">
          <?php echo htmlspecialchars($offer['brand_emoji']); ?>
          <div class="carousel-deco"><?php echo $cashback_display; ?></div>
          <div class="slide-label"><?php echo htmlspecialchars($offer['title']); ?></div>
        </div>
        <?php endif; ?>
      </div>
      <button class="c-prev" onclick="slide(-1)">
        <i class="hgi-stroke hgi-arrow-left"></i>
      </button>
      <button class="c-next" onclick="slide(1)">
        <i class="hgi-stroke hgi-arrow-right"></i>
      </button>
      <div class="c-dots" id="dots">
        <?php 
        $totalSlides = 0;
        if (!empty($offer['video_file'])) $totalSlides++;
        if (!empty($offer['logo_image'])) $totalSlides++;
        if ($totalSlides == 0) $totalSlides = 1;
        ?>
        <?php for ($i = 0; $i < $totalSlides; $i++): ?>
        <div class="c-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="goTo(<?php echo $i; ?>)"></div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="brand-row">
      <div class="brand-logo">
        <?php if (!empty($offer['logo_image'])): ?>
        <img src="uploads/<?php echo htmlspecialchars($offer['logo_image']); ?>" style="width:100%;height:100%;object-fit:contain;">
        <?php else: ?>
        <?php echo htmlspecialchars($offer['brand_emoji']); ?>
        <?php endif; ?>
      </div>
      <div class="brand-info">
        <h2><?php echo htmlspecialchars($offer['brand_name']); ?></h2>
        <p><?php echo htmlspecialchars($offer['category']); ?> · Verified · <?php echo formatNumber($offer['claimed_count']); ?>+ claimed</p>
      </div>
      <div class="brand-badges">
        <?php if ($offer['is_verified']): ?><span class="badge green">✓ Verified</span><?php endif; ?>
        <?php if ($offer['is_popular']): ?><span class="badge blue">🔥 Popular</span><?php endif; ?>
        <?php if ($is_expired): ?><span class="badge red">❌ Expired</span><?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <i class="hgi-stroke hgi-file-02"></i>
        Offer Description
      </div>
      <p class="desc-text"><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
      <div class="stats-row" style="margin-top:16px;">
        <div class="stat-pill"><div class="sv"><?php echo formatNumber($offer['claimed_count']); ?></div><div class="sl">People Claimed</div></div>
        <div class="stat-pill"><div class="sv"><?php echo $offer['rating'] > 0 ? $offer['rating'] . '★' : 'N/A'; ?></div><div class="sl">Avg Rating</div></div>
        <div class="stat-pill"><div class="sv"><?php echo $cashback_display; ?></div><div class="sl">Max Cashback</div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <i class="hgi-stroke hgi-time"></i>
        How to Claim – Step by Step
      </div>
      <div class="timeline">
        <?php if (empty($steps)): ?>
        <div class="tl-item done">
          <div class="tl-left">
            <div class="tl-dot">✓</div>
            <div class="tl-line"></div>
          </div>
          <div class="tl-body">
            <div class="tl-step-title">Open the Offer</div>
            <div class="tl-step-desc">Tap "Claim Now" to be redirected to the merchant website.</div>
            <div class="tl-time"><i class="hgi-stroke hgi-time"></i> Instant</div>
          </div>
        </div>
        <div class="tl-item">
          <div class="tl-left">
            <div class="tl-dot">2</div>
            <div class="tl-line"></div>
          </div>
          <div class="tl-body">
            <div class="tl-step-title">Apply Promo Code</div>
            <div class="tl-step-desc">Enter code <strong><?php echo htmlspecialchars($offer['promo_code']); ?></strong> at checkout.</div>
            <div class="tl-time"><i class="hgi-stroke hgi-time"></i> Instant</div>
          </div>
        </div>
        <div class="tl-item">
          <div class="tl-left">
            <div class="tl-dot">🎉</div>
          </div>
          <div class="tl-body">
            <div class="tl-step-title">Cashback Credited!</div>
            <div class="tl-step-desc"><?php echo $cashback_display; ?> cashback added to your wallet within 24–48 hours.</div>
            <div class="tl-time"><i class="hgi-stroke hgi-time"></i> 24–48 hrs</div>
          </div>
        </div>
        <?php else: ?>
          <?php foreach ($steps as $index => $step): ?>
          <div class="tl-item <?php echo $step['is_completed'] ? 'done' : ''; ?>">
            <div class="tl-left">
              <div class="tl-dot"><?php echo $step['is_completed'] ? '✓' : $step['step_number']; ?></div>
              <?php if ($index < count($steps) - 1): ?><div class="tl-line"></div><?php endif; ?>
            </div>
            <div class="tl-body">
              <div class="tl-step-title"><?php echo htmlspecialchars($step['step_title']); ?></div>
              <div class="tl-step-desc"><?php echo htmlspecialchars($step['step_description']); ?></div>
              <?php if ($step['step_time']): ?>
              <div class="tl-time"><i class="hgi-stroke hgi-time"></i> <?php echo htmlspecialchars($step['step_time']); ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <div style="display:flex;flex-direction:column;gap:18px;">

    <div class="card">
      <div class="card-title">
        <i class="hgi-stroke hgi-wallet"></i>
        Amount & Payment Info
      </div>
      <div class="amount-grid">
        <div class="amount-cell">
          <div class="ac-label">Min. Order Value</div>
          <div class="ac-value">₹<?php echo number_format($offer['min_order_amount']); ?></div>
          <div class="ac-sub">to qualify</div>
        </div>
        <div class="amount-cell green-cell">
          <div class="ac-label">Max Cashback</div>
          <div class="ac-value"><?php echo $cashback_display; ?></div>
          <div class="ac-sub"><?php echo $offer['cashback_type'] === 'flat' ? 'flat cashback' : 'on order value'; ?></div>
        </div>
        <div class="amount-cell">
          <div class="ac-label">Cashback Rate</div>
          <div class="ac-value"><?php echo $offer['cashback_rate']; ?>%</div>
          <div class="ac-sub">of order value</div>
        </div>
        <div class="amount-cell green-cell">
          <div class="ac-label">Wallet Credit</div>
          <div class="ac-value">24h</div>
          <div class="ac-sub">avg. credit time</div>
        </div>
      </div>

      <?php if (!$is_expired && $offer['promo_code']): ?>
      <div style="margin-top:16px;background:var(--primary-light);border-radius:12px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;">
        <div>
          <div style="font-size:0.7rem;font-weight:600;color:var(--text-sub);">Promo Code</div>
          <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:1.2rem;color:var(--primary);"><?php echo htmlspecialchars($offer['promo_code']); ?></div>
        </div>
        <button onclick="copyCode()" style="background:var(--primary);color:#fff;border:none;padding:8px 14px;border-radius:8px;font-weight:600;font-size:0.75rem;cursor:pointer;">Copy</button>
      </div>
      <?php endif; ?>

      <div style="margin-top:16px;background:<?php echo $is_expired ? '#fee2e2' : '#fff0f0'; ?>;border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:10px;">
        <span style="font-size:1.3rem;">⏳</span>
        <div>
          <div style="font-size:0.78rem;font-weight:700;color:<?php echo $is_expired ? 'var(--red)' : '#ef4444'; ?>;">Offer Expires: <?php echo formatDate($offer['expiry_date']); ?></div>
          <div style="font-size:0.7rem;color:var(--text-sub);margin-top:2px;"><?php echo $is_expired ? 'This offer has ended' : $days_left . ' days left · Don\'t miss out!'; ?></div>
        </div>
        <?php if (!$is_expired): ?>
        <div style="margin-left:auto;background:#ef4444;color:#fff;font-size:0.68rem;font-weight:800;padding:4px 10px;border-radius:10px;font-family:'Nunito',sans-serif;"><?php echo $days_left; ?>d left</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">
        <i class="hgi-stroke hgi-information-circle"></i>
        Important Notices
      </div>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <div class="alert-box info">
          <div class="alert-icon">ℹ️</div>
          <div class="alert-content">
            <h4>You're being redirected</h4>
            <p>Clicking "Claim Now" will take you to the merchant's official website.</p>
          </div>
        </div>
        <div class="alert-box warn">
          <div class="alert-icon">⚠️</div>
          <div class="alert-content">
            <h4>Use same phone number</h4>
            <p>Log in with the same phone number to ensure cashback tracking.</p>
          </div>
        </div>
        <div class="alert-box success">
          <div class="alert-icon">✅</div>
          <div class="alert-content">
            <h4>One claim per user</h4>
            <p>This offer can be claimed once per account. Cashback is non-transferable.</p>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($terms)): ?>
    <div class="card" style="font-size:0.76rem;color:var(--text-sub);line-height:1.7;">
      <div class="card-title" style="margin-bottom:10px;">
        <i class="hgi-stroke hgi-checkmark-circle"></i>
        Terms & Conditions
      </div>
      <ul style="padding-left:16px;display:flex;flex-direction:column;gap:5px;">
        <?php foreach ($terms as $term): ?>
        <li><?php echo htmlspecialchars($term['term_text']); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php if ($is_expired): ?>
      <button class="cta-btn expired" disabled>
        <i class="hgi-stroke hgi-cancel"></i>
        Offer Expired
      </button>
      <p class="cta-note" style="color: var(--red);">This offer is no longer available</p>
      <?php elseif ($offer['redirect_url']): ?>
      <a href="<?php echo htmlspecialchars($offer['redirect_url']); ?>" target="_blank" class="cta-btn" onclick="openModal()">
        <i class="hgi-stroke hgi-arrow-up-right"></i>
        Claim Now – Go to <?php echo htmlspecialchars($offer['brand_name']); ?>
      </a>
      <?php else: ?>
      <button class="cta-btn" onclick="openModal()">
        <i class="hgi-stroke hgi-arrow-up-right"></i>
        Claim Now – Go to <?php echo htmlspecialchars($offer['brand_name']); ?>
      </button>
      <?php endif; ?>
      <p class="cta-note"><?php if (!$is_expired && $offer['promo_code']): ?>Use code <span><?php echo htmlspecialchars($offer['promo_code']); ?></span> · <?php endif; ?>Cashback tracked automatically</p>
    </div>

  </div>

</div>

<nav class="bottom-nav">
  <a href="index.php" class="nav-item">
    <i class="hgi-stroke hgi-home"></i>
    <span>Home</span>
  </a>
  <div class="nav-item">
    <i class="hgi-stroke hgi-history"></i>
    <span>History</span>
  </a>
  <a href="index.php" class="nav-item active">
    <i class="hgi-stroke hgi-tag"></i>
    <span>Offers</span>
  </a>
  <div class="nav-item">
    <i class="hgi-stroke hgi-calendar-02"></i>
    <span>Events</span>
  </div>
</nav>

<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-icon">🚀</div>
    <h3>You're leaving OSM</h3>
    <p>
      You'll be redirected to <strong><?php echo htmlspecialchars($offer['brand_name']); ?></strong> to complete your order.
      Make sure to log in with your registered phone number so your
      <strong><?php echo $cashback_display; ?> cashback</strong> is tracked and credited within 24–48 hours.
    </p>
    <div class="modal-actions">
      <button class="modal-cancel" onclick="closeModal()">Cancel</button>
      <button class="modal-confirm" onclick="confirmRedirect()">Yes, Continue →</button>
    </div>
    <div class="modal-tip">
      <i class="hgi-stroke hgi-information-circle"></i>
      Tip: Don't close the browser tab while ordering
    </div>
  </div>
</div>

<script>
  let cur = 0, total = <?php echo $totalSlides; ?>;
  const track = document.getElementById('track');
  const dots = document.querySelectorAll('.c-dot');

  function goTo(n) {
    cur = (n + total) % total;
    track.style.transform = `translateX(-${cur * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === cur));
  }
  function slide(dir) { goTo(cur + dir); }

  let auto = setInterval(() => slide(1), 3500);
  track.parentElement.addEventListener('mouseenter', () => clearInterval(auto));
  track.parentElement.addEventListener('mouseleave', () => { auto = setInterval(() => slide(1), 3500); });

  function openModal() {
    document.getElementById('modal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    document.getElementById('modal').classList.remove('open');
    document.body.style.overflow = '';
  }
  function confirmRedirect() {
    closeModal();
    alert('Redirecting to <?php echo addslashes($offer['brand_name']); ?>… Cashback tracking activated! 🎉');
  }
  document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });

  function copyCode() {
    const code = '<?php echo addslashes($offer['promo_code']); ?>';
    navigator.clipboard.writeText(code).then(() => {
      alert('Promo code copied: ' + code);
    });
  }
</script>
</body>
</html>
