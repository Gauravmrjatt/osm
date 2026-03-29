# Payou - Offers & Cashback Platform

## Project Overview

**Payou** (also referenced as "OSM Offers") is a dynamic PHP/MySQL web application for managing and displaying offers and cashback deals. The platform allows users to browse, filter, and claim offers from various brands across categories like Banking, Shopping, Entertainment, Recharge, and more.

### Core Features

- **Homepage** - Dynamic offer listings with filtering and sorting
- **Category Filtering** - Filter offers by Food, Health, Fashion, Travel, Bank Accounts, Demat Accounts, UPI, Investment, Shopping, Entertainment
- **Amount Range Filter** - Filter by minimum/maximum cashback amount
- **Sorting Options** - Sort by Newest, Most Popular, Expiring Soon, Highest Cashback
- **Offer Details Page** - Full offer information with step-by-step guides, terms, and promo codes
- **Admin Panel** - Password-protected interface to manage offers, categories, and banners
- **Featured Offers Carousel** - Highlighted offers at the top of the homepage
- **Banner Management** - Upload and manage promotional banners
- **Responsive Design** - Mobile-first with bottom navigation, desktop sidebar

## Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML5, CSS3, JavaScript, Google Fonts (Mulish, Nunito), HugeIcons |
| Backend | PHP (procedural with MySQLi) |
| Database | MySQL |
| Design System | Custom CSS with CSS Variables, Glassmorphism effects |

## Project Structure

```
osm/
├── index.php          # Homepage with offer listings, filters, and categories
├── offer.php          # Individual offer details page
├── admin.php          # Admin panel for managing offers, categories, banners
├── config.php         # Database configuration & helper functions
├── upload.php         # AJAX endpoint for image/video uploads
├── migrate.php        # Database migration script
├── database.sql       # MySQL database schema and seed data
├── README.md          # Project documentation
├── QWEN.md            # This file - comprehensive project context
├── temp.html          # Temporary/scratch file
├── temp2.html         # Temporary/scratch file
└── uploads/           # Directory for uploaded images and videos
```

## Database Schema

### Tables

| Table | Description |
|-------|-------------|
| `offers` | Main offers table with all offer details |
| `offer_images` | Multiple images per offer |
| `offer_steps` | Step-by-step guide for each offer |
| `offer_terms` | Terms and conditions |
| `categories` | Offer categories with emojis and sort order |
| `banners` | Promotional banners for homepage carousel |

### Key Offer Fields

- `title`, `description`, `brand_name`, `brand_emoji`
- `logo_image`, `video_file` (uploaded media)
- `category`, `min_order_amount`, `max_cashback`, `cashback_rate`, `cashback_type`
- `expiry_date`, `promo_code`, `redirect_url`, `link2`
- `claimed_count`, `rating`, `is_featured`, `is_verified`, `is_popular`
- `status` (active/expired/draft)

## Building and Running

### Prerequisites

- PHP 7.4+ (MySQLi extension)
- MySQL 5.7+ or MariaDB
- Web server (Apache/Nginx) or PHP built-in server

### Installation

#### 1. Database Setup

```bash
# Create database and import schema
mysql -u root -p < database.sql
```

Or manually run the SQL in `database.sql` through phpMyAdmin or MySQL client.

#### 2. Configuration

Edit `config.php` to update:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'osm_offers');
define('DB_PASS', '6818215cb9f098');
define('DB_NAME', 'osm_offers_db');
define('ADMIN_PASSWORD', 'payou123');  // Change this!
define('UPLOAD_DIR', 'uploads/');
```

#### 3. Run Migrations

```bash
# Run migration script to ensure schema is up-to-date
php migrate.php
```

Or visit `http://localhost:8000/migrate.php` in browser.

#### 4. Start Development Server

```bash
# Start PHP development server
php -S localhost:8000
```

Open `http://localhost:8000` in your browser.

### Directory Permissions

Ensure the `uploads/` directory is writable:

```bash
mkdir -p uploads
chmod 777 uploads
```

## Key Endpoints

| URL | Description |
|-----|-------------|
| `/` or `/index.php` | Homepage with offer listings |
| `/offer.php?id={id}` | Individual offer details |
| `/admin.php` | Admin panel (password protected) |
| `/upload.php` | AJAX upload endpoint (POST) |
| `/migrate.php` | Database migration runner |

### Admin Panel Access

- **URL:** `http://localhost:8000/admin.php`
- **Default Password:** `payou123` (change in `config.php`)

### Admin Features

- **Offers Tab:** Add, edit, delete offers with steps and terms
- **Categories Tab:** Manage offer categories
- **Banners Tab:** Upload and manage homepage banners

## Development Conventions

### Coding Style

- **PHP:** Procedural style with MySQLi prepared statements
- **Naming:** Snake_case for variables and database columns
- **Constants:** Uppercase with `define()` for configuration
- **HTML/CSS:** Embedded styles in `<style>` tags (no external CSS files)
- **Icons:** HugeIcons stroke-rounded font via CDN

### Database Access Pattern

```php
$conn = getDB();  // From config.php
$stmt = $conn->prepare("SELECT * FROM table WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();
```

### Helper Functions (config.php)

| Function | Description |
|----------|-------------|
| `getDB()` | Returns MySQLi connection with utf8mb4 charset |
| `formatNumber($num)` | Formats numbers (1.5M, 2.5k) |
| `formatDate($date)` | Formats date as "29 Mar 2026" |
| `getDaysRemaining($date)` | Returns days until expiry |
| `isExpired($date)` | Checks if offer is expired |
| `getCategoryEmoji($category)` | Returns emoji for category |

### Session Management

- Admin authentication via `$_SESSION['admin_logged_in']`
- Session started with `session_start()` in each protected file

### Security Notes

- Password stored in plain text in `config.php` (change for production)
- Prepared statements used for SQL queries
- File uploads validated by extension
- HTML output escaped with `htmlspecialchars()`

## API / AJAX Endpoints

### POST /upload.php

Handles image and video uploads for offers.

**Request:**
- `logo_image` (file) - Image upload (jpg, jpeg, png, gif, webp)
- `video_file` (file) - Video upload (mp4, webm, max 50MB)

**Response:**
```json
{
  "success": true,
  "filename": "img_1234567890_1234.jpg",
  "type": "image"
}
```

## Design System

### CSS Variables (index.php)

```css
:root {
  --primary: #4f46e5;
  --primary-light: #eef2ff;
  --accent: #6366f1;
  --green: #10b981;
  --red: #ef4444;
  --orange: #f97316;
  --text: #1e1b4b;
  --text-sub: #6b7280;
  --bg: #f5f6fa;
  --card: #ffffff;
  --radius: 20px;
  --radius-sm: 14px;
}
```

### Responsive Breakpoints

- **Mobile:** < 768px (bottom navigation visible)
- **Desktop:** ≥ 768px (sidebar navigation visible)

### UI Components

- **Promo Cards:** Gradient cards for featured offers
- **Offer Cards:** List items with logo, title, metadata
- **Expire Cards:** Horizontal scroll for expiring offers
- **Stats Bar:** 3-column grid for statistics
- **Timeline:** Step-by-step guide visualization
- **Modal:** Confirmation dialogs for actions

## Common Tasks

### Adding a New Offer

1. Login to admin panel (`/admin.php`)
2. Click "Add Offer" or navigate to `?edit=0`
3. Fill in offer details:
   - Title, description, brand name
   - Upload logo image or video
   - Set category, amounts, cashback
   - Add steps and terms
   - Set expiry date and flags
4. Save

### Uploading a Banner

1. Go to Admin → Banners tab
2. Upload image, set optional link URL
3. Set sort order
4. Click Upload

### Running Migrations

After database changes or initial setup:

```bash
php migrate.php
```

## Troubleshooting

### Database Connection Failed

- Verify MySQL is running
- Check credentials in `config.php`
- Ensure database `osm_offers_db` exists

### Uploads Not Working

- Check `uploads/` directory exists and is writable
- Verify PHP upload settings in `php.ini`:
  - `upload_max_filesize`
  - `post_max_size`
  - `file_uploads = On`

### Session Issues

- Ensure `session_start()` is called before any output
- Check PHP session configuration

## Files Reference

| File | Lines | Purpose |
|------|-------|---------|
| `config.php` | ~80 | DB config, helper functions |
| `index.php` | ~786 | Homepage with filters and listings |
| `offer.php` | ~727 | Offer details page |
| `admin.php` | ~1014 | Admin panel (CRUD operations) |
| `upload.php` | ~70 | AJAX file upload handler |
| `migrate.php` | ~50 | Database migration runner |
| `database.sql` | ~100 | Schema and seed data |

## Notes

- The application uses inline CSS (no external stylesheets)
- All PHP files use procedural MySQLi (no ORM or framework)
- Session-based admin authentication
- File uploads stored in `uploads/` directory
- Default sample data included in `database.sql`
