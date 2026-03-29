<?php
require_once 'config.php';
session_start();

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$conn = getDB();
$offer_id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM offers WHERE id = ?");
$stmt->bind_param("i", $offer_id);
$stmt->execute();
$offer = $stmt->get_result()->fetch_assoc();

if (!$offer) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

$steps = [];
$steps_stmt = $conn->prepare("SELECT * FROM offer_steps WHERE offer_id = ? ORDER BY step_number");
$steps_stmt->bind_param("i", $offer_id);
$steps_stmt->execute();
$steps_result = $steps_stmt->get_result();
while ($row = $steps_result->fetch_assoc()) {
    $steps[] = $row;
}

$terms = [];
$terms_stmt = $conn->prepare("SELECT * FROM offer_terms WHERE offer_id = ? ORDER BY sort_order");
$terms_stmt->bind_param("i", $offer_id);
$terms_stmt->execute();
$terms_result = $terms_stmt->get_result();
while ($row = $terms_result->fetch_assoc()) {
    $terms[] = $row;
}

$conn->close();

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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css"/>
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
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  .hgi-stroke { display: inline-block; vertical-align: middle; font-size: 20px; }

  body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(180deg, var(--bg-dark) 0%, var(--bg-card) 100%);
    color: var(--text);
    min-height: 100vh;
  }

  .navbar {
    position: sticky; top: 0; z-index: 200;
    background: rgba(11, 15, 20, 0.9);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    height: 60px;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 16px;
  }
  .logo { font-family:'Inter',sans-serif; font-weight:800; font-size:1.3rem; letter-spacing:-0.5px; }
  .logo span { color:var(--primary-light); }
  .back-btn {
    display:flex; align-items:center; gap:8px;
    background: rgba(255,255,255,0.08); color:var(--text);
    border:none; border-radius:12px; padding:8px 14px;
    font-family:'Inter',sans-serif; font-size:0.8rem; font-weight:600;
    cursor:pointer; transition:all 0.2s; text-decoration:none;
  }
  .back-btn:hover { background: var(--primary); color:#fff; }
  .back-btn svg { width:16px; height:16px; }

  .page {
    max-width: 600px;
    margin: 0 auto;
    padding: 16px;
    padding-bottom: 120px;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .card {
    background: var(--bg-card);
    border-radius: var(--radius);
    padding: 18px;
    border: 1px solid var(--border);
  }

  .card-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--text);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .card-title svg { width:18px; height:18px; color:var(--primary-light); }

  .brand-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background: var(--bg-card);
    border-radius: var(--radius);
    padding: 16px;
    border: 1px solid var(--border);
  }
  .brand-logo {
    width: 56px; height: 56px; border-radius: 14px;
    background: rgba(255,255,255,0.05);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; flex-shrink:0;
  }
  .brand-logo img { width:100%; height:100%; object-fit:cover; border-radius:14px; }
  .brand-info h2 { font-weight: 700; font-size: 1.1rem; color: var(--text); }
  .brand-info p { font-size:0.72rem; color:var(--text-sub); margin-top:3px; }
  .brand-badges { margin-left:auto; display:flex; flex-direction:row; gap:5px; flex-wrap:wrap; justify-content:flex-end; }
  .badge { font-size:0.62rem; font-weight:700; padding:3px 10px; border-radius:8px; }
  .badge.green { background:rgba(0,210,106,0.12); color:var(--green); }
  .badge.blue { background:rgba(30,107,255,0.12); color:var(--primary-light); }
  .badge.red { background:rgba(239,68,68,0.12); color:#ef4444; }

  .desc-text { font-size:0.82rem; color:var(--text-sub); line-height:1.7; }

  .stats-row { display:flex; gap:10px; margin-top:16px; }
  .stat-pill {
    flex:1; background:rgba(255,255,255,0.04); border-radius:12px; padding:12px 8px; text-align:center;
    border: 1px solid var(--border);
  }
  .stat-pill .sv { font-weight:800; font-size:1rem; color:var(--primary-light); }
  .stat-pill .sl { font-size:0.6rem; color:var(--text-sub); margin-top:2px; }

  .timeline { display:flex; flex-direction:column; gap:0; }
  .tl-item { display:flex; gap:14px; position:relative; }
  .tl-left { display:flex; flex-direction:column; align-items:center; width:32px; flex-shrink:0; }
  .tl-dot {
    width:32px; height:32px; border-radius:50%;
    background:rgba(30,107,255,0.15); color:var(--primary-light);
    display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:0.8rem; flex-shrink:0;
    border:2px solid var(--primary);
  }
  .tl-item.done .tl-dot { background:var(--primary); color:#fff; }
  .tl-line { width:2px; flex:1; background:rgba(30,107,255,0.2); min-height:20px; margin:4px 0; }
  .tl-item:last-child .tl-line { display:none; }
  .tl-body { padding-bottom:20px; flex:1; }
  .tl-step-title { font-weight:600; font-size:0.85rem; color:var(--text); margin-bottom:3px; }
  .tl-step-desc { font-size:0.72rem; color:var(--text-sub); line-height:1.5; }
  .tl-time {
    display:inline-flex; align-items:center; gap:4px;
    background:rgba(30,107,255,0.1); color:var(--primary-light);
    font-size:0.62rem; font-weight:600;
    padding:2px 8px; border-radius:6px; margin-top:6px;
  }
  .tl-time svg { width:10px; height:10px; }

  .terms-list { font-size:0.72rem; color:var(--text-sub); line-height:1.8; }
  .terms-list li { margin-bottom:6px; padding-left:4px; }

  .cta-btn {
    width:100%; padding:16px; border:none; border-radius:14px;
    background:linear-gradient(135deg, var(--primary), var(--primary-light));
    color:#fff; font-family:'Inter',sans-serif; font-weight:700; font-size:0.95rem;
    cursor:pointer; box-shadow:var(--glow);
    display:flex; align-items:center; justify-content:center; gap:10px;
    transition:all 0.25s; text-decoration:none;
  }
  .cta-btn:hover { transform:translateY(-2px); box-shadow:0 6px 24px rgba(30,107,255,0.5); }
  .cta-btn svg { width:18px; height:18px; }
  .cta-btn.expired { background: linear-gradient(135deg,#4b5563,#6b7280); cursor: not-allowed; }

  .cta-note { text-align:center; font-size:0.68rem; color:var(--text-sub); margin-top:8px; }
  .cta-note span { color:var(--primary-light); font-weight:600; }

  .promo-code {
    background:rgba(30,107,255,0.1); border:1px dashed rgba(30,107,255,0.3);
    border-radius:10px; padding:12px 16px;
    display:flex; align-items:center; justify-content:space-between;
    margin-top:12px;
  }
  .promo-code-label { font-size:0.65rem; color:var(--text-sub); }
  .promo-code-value { font-weight:800; font-size:1.1rem; color:var(--primary-light); letter-spacing:1px; }
  .copy-btn {
    background:var(--primary); color:#fff; border:none;
    padding:6px 12px; border-radius:6px;
    font-size:0.7rem; font-weight:600; cursor:pointer;
  }

  .modal-overlay {
    position:fixed; inset:0; z-index:1000;
    background:rgba(0,0,0,0.8); backdrop-filter:blur(8px);
    display:none; align-items:center; justify-content:center; padding:20px;
  }
  .modal-overlay.open { display:flex; }
  .modal {
    background:var(--bg-card); border-radius:24px; max-width:400px; width:100%;
    padding:28px; text-align:center;
    border: 1px solid var(--border);
    animation:popIn 0.3s cubic-bezier(.4,0,.2,1);
  }
  @keyframes popIn { from{transform:scale(0.9);opacity:0;} to{transform:scale(1);opacity:1;} }
  .modal-icon { font-size:2.5rem; margin-bottom:12px; }
  .modal h3 { font-weight:700; font-size:1.2rem; color:var(--text); margin-bottom:8px; }
  .modal p { font-size:0.8rem; color:var(--text-sub); line-height:1.6; margin-bottom:20px; }
  .modal-actions { display:flex; gap:10px; }
  .modal-cancel {
    flex:1; padding:12px; border:1px solid rgba(255,255,255,0.1);
    border-radius:12px; background:transparent; color:var(--text-sub);
    font-family:'Inter',sans-serif; font-weight:600; font-size:0.85rem;
    cursor:pointer;
  }
  .modal-confirm {
    flex:1; padding:12px; border:none;
    border-radius:12px; background:linear-gradient(135deg,var(--primary),var(--primary-light));
    color:#fff; font-family:'Inter',sans-serif; font-weight:700; font-size:0.85rem;
    cursor:pointer;
  }
  .modal-tip { font-size:0.65rem; color:var(--text-sub); margin-top:14px; }

  .cta-row { display:flex; flex-direction:column; gap:10px; }

  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
  .card, .brand-card { animation:fadeUp 0.3s ease both; }
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="back-btn">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back
  </a>
  <div class="logo">OS<span>M</span></div>
  <div style="width:40px;"></div>
</nav>

<div class="page">

  <!-- Media / Video or Logo -->
  <?php if (!empty($offer['video_file'])): ?>
  <div class="card" style="padding:0;overflow:hidden;">
    <video controls style="width:100%;height:auto;background:#000;">
      <source src="uploads/<?php echo htmlspecialchars($offer['video_file']); ?>" type="video/mp4">
      Your browser does not support the video tag.
    </video>
  </div>
  <?php elseif (!empty($offer['logo_image'])): ?>
  <div class="card" style="padding:0;overflow:hidden;">
    <img src="uploads/<?php echo htmlspecialchars($offer['logo_image']); ?>" style="width:100%;height:auto;">
  </div>
  <?php endif; ?>

  <!-- Brand Info -->
  <div class="brand-card">
    <div class="brand-logo">
      <?php if (!empty($offer['logo_image'])): ?>
        <img src="uploads/<?php echo htmlspecialchars($offer['logo_image']); ?>">
      <?php else: ?>
        <?php echo htmlspecialchars($offer['brand_emoji']); ?>
      <?php endif; ?>
    </div>
    <div class="brand-info">
      <h2><?php echo htmlspecialchars($offer['brand_name']); ?></h2>
      <p><?php echo htmlspecialchars($offer['category']); ?> · <?php echo formatNumber($offer['claimed_count']); ?>+ claimed</p>
    </div>
    <div class="brand-badges">
      <?php if ($offer['is_verified']): ?><span class="badge green">✓ Verified</span><?php endif; ?>
      <?php if ($offer['is_popular']): ?><span class="badge blue">🔥 Popular</span><?php endif; ?>
      <?php if ($is_expired): ?><span class="badge red">❌ Expired</span><?php endif; ?>
    </div>
  </div>

  <!-- Description -->
  <div class="card">
    <div class="card-title">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      About This Offer
    </div>
    <p class="desc-text"><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
    <div class="stats-row">
      <div class="stat-pill">
        <div class="sv"><?php echo formatNumber($offer['claimed_count']); ?></div>
        <div class="sl">Claimed</div>
      </div>
      <div class="stat-pill">
        <div class="sv"><?php echo $cashback_display; ?></div>
        <div class="sl">Cashback</div>
      </div>
      <div class="stat-pill">
        <div class="sv"><?php echo ($offer['payout_type'] ?? 'instant') === 'instant' ? '⚡' : '⏱'; ?></div>
        <div class="sl"><?php echo ($offer['payout_type'] ?? 'instant') === 'instant' ? 'Instant' : '24-72h'; ?></div>
      </div>
    </div>
  </div>

  <!-- Steps -->
  <div class="card">
    <div class="card-title">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      How to Claim
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
          <div class="tl-step-desc">Tap "Claim Now" to be redirected to the merchant.</div>
        </div>
      </div>
      <div class="tl-item">
        <div class="tl-left">
          <div class="tl-dot">2</div>
          <div class="tl-line"></div>
        </div>
        <div class="tl-body">
          <div class="tl-step-title">Apply Promo Code</div>
          <div class="tl-step-desc">Use code <strong><?php echo htmlspecialchars($offer['promo_code'] ?: 'PAYOU'); ?></strong></div>
        </div>
      </div>
      <div class="tl-item">
        <div class="tl-left">
          <div class="tl-dot">🎉</div>
        </div>
        <div class="tl-body">
          <div class="tl-step-title">Get Cashback!</div>
          <div class="tl-step-desc"><?php echo $cashback_display; ?> credited to your wallet.</div>
        </div>
      </div>
      <?php else: ?>
        <?php foreach ($steps as $index => $step): ?>
        <div class="tl-item">
          <div class="tl-left">
            <div class="tl-dot"><?php echo $step['is_completed'] ? '✓' : $step['step_number']; ?></div>
            <?php if ($index < count($steps) - 1): ?><div class="tl-line"></div><?php endif; ?>
          </div>
          <div class="tl-body">
            <div class="tl-step-title"><?php echo htmlspecialchars($step['step_title']); ?></div>
            <div class="tl-step-desc"><?php echo htmlspecialchars($step['step_description']); ?></div>
            <?php if ($step['step_time']): ?>
            <div class="tl-time"><?php echo htmlspecialchars($step['step_time']); ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Terms -->
  <?php if (!empty($terms)): ?>
  <div class="card">
    <div class="card-title">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Terms & Conditions
    </div>
    <ul class="terms-list">
      <?php foreach ($terms as $term): ?>
      <li><?php echo htmlspecialchars($term['term_text']); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- CTA Buttons -->
  <div class="cta-row">
    <?php if ($is_expired): ?>
      <button class="cta-btn expired" disabled>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        Offer Expired
      </button>
    <?php elseif (!empty($offer['redirect_url'])): ?>
      <button class="cta-btn" onclick="openModal('<?php echo htmlspecialchars($offer['redirect_url']); ?>')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        Claim <?php echo $cashback_display; ?> Now
      </button>
      <?php if (!empty($offer['link2'])): ?>
      <button class="cta-btn" onclick="openModal('<?php echo htmlspecialchars($offer['link2']); ?>')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        Tracking Link
      </button>
      <?php endif; ?>
    <?php else: ?>
      <button class="cta-btn" onclick="openModal('')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        Claim <?php echo $cashback_display; ?> Now
      </button>
    <?php endif; ?>
    
    <?php if (!$is_expired && $offer['promo_code']): ?>
    <div class="promo-code">
      <div>
        <div class="promo-code-label">Promo Code</div>
        <div class="promo-code-value"><?php echo htmlspecialchars($offer['promo_code']); ?></div>
      </div>
      <button class="copy-btn" onclick="copyCode()">Copy</button>
    </div>
    <?php endif; ?>
    
    <p class="cta-note">Cashback tracked automatically</p>
  </div>

</div>

<!-- Redirect Modal -->
<div class="modal-overlay" id="modal">
  <div class="modal">
    <div class="modal-icon">🚀</div>
    <h3>You're leaving OSM</h3>
    <p>You'll be redirected to <strong><?php echo htmlspecialchars($offer['brand_name']); ?></strong>. Make sure to log in so your <strong><?php echo $cashback_display; ?> cashback</strong> gets tracked!</p>
    <div class="modal-actions">
      <button class="modal-cancel" onclick="closeModal()">Cancel</button>
      <button class="modal-confirm" onclick="confirmRedirect()">Continue</button>
    </div>
  </div>
</div>

<script>
  let redirectUrl = '';

  function openModal(url) {
    redirectUrl = url;
    document.getElementById('modal').classList.add('open');
  }
  function closeModal() {
    document.getElementById('modal').classList.remove('open');
  }
  function confirmRedirect() {
    closeModal();
    if (redirectUrl) {
      window.open(redirectUrl, '_blank');
    } else {
      alert('Redirecting to <?php echo addslashes($offer['brand_name']); ?>… Cashback tracking activated!');
    }
  }
  document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });

  function copyCode() {
    const code = '<?php echo addslashes($offer['promo_code']); ?>';
    navigator.clipboard.writeText(code).then(() => {
      alert('Code copied: ' + code);
    });
  }
</script>
</body>
</html>
