-- Database Schema for Payou Offers Platform

-- Create database
-- CREATE DATABASE IF NOT EXISTS payou_db;
-- USE payou_db;

-- Offers table
CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    brand_name VARCHAR(100),
    brand_emoji VARCHAR(10) DEFAULT '🎁',
    category VARCHAR(50) DEFAULT 'General',
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_cashback DECIMAL(10,2) DEFAULT 0,
    cashback_rate DECIMAL(5,2) DEFAULT 0,
    cashback_type ENUM('flat', 'percentage') DEFAULT 'flat',
    min_amount DECIMAL(10,2) DEFAULT 0,
    max_amount DECIMAL(10,2) DEFAULT 0,
    expiry_date DATE,
    promo_code VARCHAR(50),
    redirect_url VARCHAR(500),
    claimed_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    is_popular TINYINT(1) DEFAULT 0,
    status ENUM('active', 'expired', 'draft') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Offer images table
CREATE TABLE IF NOT EXISTS offer_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_label VARCHAR(100),
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
);

-- Offer steps table
CREATE TABLE IF NOT EXISTS offer_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    step_number INT NOT NULL,
    step_title VARCHAR(200) NOT NULL,
    step_description TEXT,
    step_time VARCHAR(50),
    is_completed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
);

-- Offer terms table
CREATE TABLE IF NOT EXISTS offer_terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    term_text TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    emoji VARCHAR(10) DEFAULT '📌',
    sort_order INT DEFAULT 0
);

-- Insert default categories
INSERT INTO categories (name, emoji, sort_order) VALUES 
('Food', '🍕', 1),
('Health', '💊', 2),
('Fashion', '👟', 3),
('Music', '🎵', 4),
('Travel', '✈️', 5),
('Entertainment', '🎬', 6),
('Shopping', '🛒', 7),
('Recharge', '📱', 8);

-- Insert sample offers
INSERT INTO offers (title, description, brand_name, brand_emoji, category, min_order_amount, max_cashback, cashback_rate, cashback_type, expiry_date, promo_code, claimed_count, rating, is_featured, is_verified, is_popular) VALUES
('Flat ₹150 Cashback on KFC Orders', 'Get Flat ₹150 cashback on your KFC order when you place an order above ₹500 via Payou. Valid on the KFC app and website across all participating outlets in India.', 'KFC', '🍗', 'Food', 500, 150, 15, 'flat', '2026-06-15', 'PAYOUKFC15', 209000, 4.8, 1, 1, 1),
('30% Cashback on First Pharmacy Order', 'Get 30% cashback on your first order from Pharmacy 24×7. Free home delivery on all orders above ₹299.', 'Pharmacy 24×7', '⚕️', 'Health', 299, 200, 30, 'percentage', '2026-11-15', 'PHARMACY30', 3200, 4.5, 1, 1, 0),
('20% Off on Nike First Purchase', 'Get up to 20% off on your first Nike purchase. Valid on all regular priced items.', 'Nike', '👟', 'Fashion', 1000, 500, 20, 'percentage', '2026-12-05', 'NIKE20', 1000, 4.7, 0, 1, 1),
('Premium Plan Free for 1 Month', 'Get Spotify Premium plan free for 1 month. Cancel anytime.', 'Spotify', '🎵', 'Music', 0, 0, 100, 'percentage', '2026-09-30', 'SPOTIFYFREE', 855, 4.6, 0, 1, 0),
('100% Cashback on First Uber Ride', 'Get 100% cashback on your first Uber ride. Maximum cashback ₹200.', 'Uber', '🚗', 'Travel', 50, 200, 100, 'flat', '2025-12-31', 'UBER100', 6900, 4.4, 1, 1, 1),
('10% Cashback on Electronics', 'Flat 10% cashback on electronics above ₹500. Valid on Amazon.in', 'Amazon', '📦', 'Shopping', 500, 1000, 10, 'flat', '2026-12-31', 'AMAZON10', 12500, 4.9, 1, 1, 1),
('50% Cashback on Movie Tickets', 'Get 50% cashback on movie tickets booked via Payou. Valid on all PVR, INOX, and Cinepolis.', 'Payou Movies', '🎬', 'Entertainment', 200, 150, 50, 'flat', '2026-04-30', 'MOVIES50', 5000, 4.3, 0, 1, 0),
('Flat ₹50 Cashback on Recharge', 'Get flat ₹50 cashback on prepaid mobile recharges above ₹199.', 'Payou Recharge', '📱', 'Recharge', 199, 50, 25, 'flat', '2026-12-31', 'RECHARGE50', 25000, 4.5, 1, 1, 1);

-- Insert offer images
INSERT INTO offer_images (offer_id, image_url, image_label, is_primary, sort_order) VALUES
(1, '🍗', 'KFC Zinger Bucket Deal', 1, 1),
(1, '🍔', 'KFC Chicken Burger Meal', 0, 2),
(1, '🎉', 'Party Pack – 12 Pcs', 0, 3),
(2, '💊', 'Pharmacy 24×7 Home Delivery', 1, 1),
(2, '💊', ' Medicines & Healthcare', 0, 2),
(3, '👟', 'Nike Collection', 1, 1),
(3, '👟', 'Nike Running Shoes', 0, 2),
(4, '🎵', 'Spotify Premium', 1, 1),
(5, '🚗', 'Uber First Ride', 1, 1),
(6, '📦', 'Amazon Electronics Sale', 1, 1),
(7, '🎬', 'Movie Tickets', 1, 1),
(8, '📱', 'Mobile Recharge', 1, 1);

-- Insert offer steps
INSERT INTO offer_steps (offer_id, step_number, step_title, step_description, step_time) VALUES
(1, 1, 'Open the Offer', 'Tap "Claim Now" to be redirected to the KFC website or app with your offer pre-applied.', 'Instant'),
(1, 2, 'Log in to KFC', 'Sign in with the same phone number linked to your Payou account for verification.', '~1 min'),
(1, 3, 'Add Items to Cart', 'Add your favourite KFC items worth ₹500 or more. Combo meals count too!', '~3–5 min'),
(1, 4, 'Apply Promo Code', 'Enter code PAYOUKFC15 at checkout. Discount applies automatically.', '~1 min'),
(1, 5, 'Complete Payment', 'Pay using any UPI, card, or net banking. Cashback is calculated post-payment.', '~2 min'),
(1, 6, 'Cashback Credited!', '₹150 cashback added to your Payou wallet. Use it on next purchase.', '24–48 hrs'),
(2, 1, 'Browse Products', 'Open Pharmacy 24×7 and add medicines to your cart.', '~2 min'),
(2, 2, 'Apply Coupon', 'Enter code PHARMACY30 at checkout.', 'Instant'),
(2, 3, 'Complete Payment', 'Pay for your order. Cashback will be credited within 24 hours.', '~2 min'),
(3, 1, 'Visit Nike Store', 'Browse Nike collection on Nike.com or app.', '~5 min'),
(3, 2, 'Apply Code', 'Use code NIKE20 at checkout.', 'Instant'),
(3, 3, 'Order Placed', 'Your order will be delivered in 3-5 business days.', '3–5 days');

-- Insert offer terms
INSERT INTO offer_terms (offer_id, term_text) VALUES
(1, 'Offer valid only on orders placed through the KFC app or website.'),
(1, 'Not combinable with other promo codes or KFC wallet offers.'),
(1, 'Cashback will be reversed if order is cancelled or refunded.'),
(1, 'Payou reserves the right to modify or withdraw this offer at any time.'),
(1, 'Cashback will not be credited on orders placed via third-party delivery apps.'),
(2, 'Valid only on first order per user.'),
(2, 'Prescription medicines not included in the offer.'),
(2, 'Cashback credited within 24 hours of order delivery.'),
(3, 'Valid on regular priced items only.'),
(3, 'Not applicable on sale/discounted items.'),
(3, 'One redemption per customer.');
