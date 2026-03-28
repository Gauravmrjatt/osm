# Payou - Offers & Cashback Platform

A dynamic PHP/MySQL web application for managing and displaying offers and cashback deals.

## Features

- **Homepage** - Lists all offers dynamically from MySQL database
- **Category Filtering** - Filter offers by Food, Health, Fashion, Travel, etc.
- **Amount Range Filter** - Filter by minimum/maximum cashback amount
- **Sorting Options** - Sort by Newest, Most Popular, Expiring Soon, Highest Cashback
- **Offer Details Page** - Full offer information with steps, terms, and promo codes
- **Admin Panel** - Password-protected interface to add/edit/delete offers
- **Featured Offers Carousel** - Highlighted offers at the top of the homepage

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP
- **Database:** MySQL

## Installation

### 1. Database Setup

```bash
# Import the database schema
mysql -u root -p < database.sql
```

### 2. Configuration

Edit `config.php` to update:
- Database credentials (if different from default)
- Admin password (default: `payou123`)

### 3. Run the Application

```bash
# Start PHP development server
php -S localhost:8000
```

Open `http://localhost:8000` in your browser.

## Project Structure

```
├── index.php          # Homepage with offer listings
├── offer.php          # Individual offer details page
├── admin.php          # Admin panel for managing offers
├── config.php         # Database configuration & helpers
├── database.sql       # MySQL database schema
└── README.md         # This file
```

## Admin Panel

Access the admin panel at: `http://localhost:8000/admin.php`

- **Default Password:** `payou123`

### Admin Features:
- Add new offers with all details
- Edit existing offers
- Delete offers
- Manage offer steps and terms
- Toggle Featured/Verified/Popular flags

## Database Schema

### Tables:
- `offers` - Main offers table
- `offer_images` - Multiple images per offer
- `offer_steps` - Step-by-step guide for each offer
- `offer_terms` - Terms and conditions
- `categories` - Offer categories

## Screenshots

The application features:
- Modern, responsive UI design
- Glassmorphism effects (blur backgrounds)
- Animated cards and transitions
- Mobile-friendly bottom navigation
- Desktop sidebar navigation
