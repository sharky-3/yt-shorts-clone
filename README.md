# yt-shorts-clone

A YouTube Shorts clone built with PHP, MySQL, and vanilla JS.

## Features
- Account system (register / login / logout) with cookie-based "remember me"
- Video upload (MP4 / WebM)
- Vertical swipe feed with auto play/pause
- Byte-range video streaming (scrubbing works)
- Like / unlike (AJAX)
- Comments (AJAX)
- Follow / unfollow users
- User profile pages
- FFmpeg thumbnail generation (optional)

## Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+
- A web server (Apache / Nginx) or `php -S localhost:8080`
- FFmpeg (optional – for thumbnails)

## Setup

### 1. Clone / download
```bash
git clone https://github.com/you/yt-shorts-clone.git
cd yt-shorts-clone
```

### 2. Create the database
```bash
mysql -u root -p < schema.sql
```

### 3. Configure
Edit `config.php` and set your DB credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'yt_shorts');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

### 4. Set permissions
```bash
chmod -R 755 uploads/
```

### 5. Run
```bash
# Quick dev server (PHP built-in)
php -S localhost:8080

# Or configure Apache/Nginx to point to this directory
```

Open http://localhost:8080 — register an account, upload a video, enjoy!

## Project structure
```
yt-shorts-clone/
├── config.php      ← DB credentials & constants
├── db.php          ← PDO helper + auth helpers
├── schema.sql      ← Database schema (run once)
├── index.php       ← Main feed page
├── auth.php        ← Register / Login / Logout
├── upload.php      ← Video upload
├── feed.php        ← JSON API for infinite scroll
├── stream.php      ← Byte-range video streaming
├── like.php        ← AJAX like/unlike
├── comment.php     ← AJAX comments (GET + POST)
├── follow.php      ← AJAX follow/unfollow
├── profile.php     ← User profile page
├── uploads/        ← Uploaded videos (created automatically)
│   └── thumbs/     ← Thumbnails
└── assets/
    ├── style.css   ← All styles
    └── app.js      ← Feed, likes, comments logic
```

## Notes
- Passwords are hashed with `password_hash()` (bcrypt)
- All DB queries use PDO prepared statements
- Videos are served through `stream.php` (not exposed as a public directory)
- "Remember me" tokens are stored hashed in the DB
