# 🎬 BookYourShow Clone

A full-stack **Event & Movie Booking System** built with PHP and MySQL — inspired by BookMyShow. Users can browse movies and events, book tickets with seat selection, make payments, and receive QR-coded tickets. Operators can manage events and shows, while Admins have full control over the platform.

---

## 🚀 Features

### 👤 User Panel
- Register / Login (with Google OAuth support)
- Browse **Movies** (Now Showing, Coming Soon, Top Rated)
- Browse **Events** (Concerts, Sports, Comedy Shows, etc.)
- **City-based filtering** for movies and events
- Watch trailers (YouTube embed)
- **Seat selection** before booking
- **Coupon / Discount** code support at checkout
- **Online Payment** simulation
- QR Code tickets delivered after booking
- Booking history & profile management
- Wishlist for movies

### 🎪 Organizer (Operator) Dashboard
- Register as an event organizer
- Submit new **Events** for admin approval
- Schedule **Movie Shows** at theatres
- **Theatre licence** submission & management
- Add custom theatres/venues
- Edit & manage existing events
- View show status (Live / Pending)

### 🛡️ Admin Panel
- Full dashboard with stats overview
- Manage **Movies** (Add, Edit, Delete, Approve)
- Manage **Events** (Approve / Reject with notes)
- Manage **Shows** (View all scheduled shows)
- Manage **Organizers** (Approve / Suspend accounts)
- Manage **Theatre Licences** (Approve / Reject)
- Manage **Users**, **Bookings**, **Theatres**
- **Coupon management** (Create discount codes)
- **Ad management** system
- Admin notifications for new licence requests

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.x (Procedural + MySQLi) |
| Database | MySQL |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Local Server | XAMPP (Apache + MySQL) |
| Auth | Session-based + Google OAuth 2.0 |
| Email | SendGrid API |
| SMS | Twilio API |
| UI Libraries | Select2, Font Awesome |

---

## 📁 Project Structure

```
bookmyshow-clone/
├── admin/                  # Admin panel pages
│   ├── manage-movies.php
│   ├── manage-events.php
│   ├── manage-shows.php
│   ├── manage-organizers.php
│   ├── manage-licences.php
│   ├── manage-users.php
│   ├── manage-bookings.php
│   ├── manage-coupons.php
│   └── ...
├── assets/
│   └── images/             # Movie posters, event banners
├── config/
│   └── config.php          # DB + API configuration
├── css/
│   └── style.css           # Global styles (dark theme)
├── database/
│   ├── bookmyshow.sql      # Main database schema + seed data
│   └── migration_v2.sql    # Additional migrations
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── session_guard.php
├── js/
│   └── script.js
├── index.php               # Homepage
├── movies.php              # Movies listing
├── movie-details.php       # Movie detail + trailer
├── events.php              # Events listing
├── event-details.php       # Event detail page
├── seat-selection.php      # Seat picker
├── payment.php             # Payment page
├── payment-success.php     # Movie ticket (with QR)
├── event-payment-success.php # Event ticket (with QR)
├── booking-history.php
├── operator-dashboard.php  # Organizer dashboard
├── operator-login.php
├── operator-register.php
├── admin-dashboard.php
└── ...
```

---

## ⚙️ Setup & Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.x + MySQL)
- Git

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/abhishhdubey/Event-Booking-System.git
cd Event-Booking-System
```

**2. Move to XAMPP's htdocs**
```
Place the folder inside: C:/xampp/htdocs/bookmyshow-clone/
```

**3. Import the database**
- Start XAMPP → Start Apache & MySQL
- Open [phpMyAdmin](http://localhost/phpmyadmin)
- Create a new database: `bookmyshow_clone`
- Import `database/bookmyshow.sql`
- Then import `database/migration_v2.sql`

**4. Configure the app**

Edit `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Your MySQL password
define('DB_NAME', 'bookmyshow_clone');
```

**5. (Optional) Configure APIs**

In `config/config.php`, add your keys for:
- **Google OAuth** → [Google Cloud Console](https://console.cloud.google.com/)
- **SendGrid** (email tickets) → [SendGrid](https://sendgrid.com/)
- **Twilio** (SMS) → [Twilio Console](https://console.twilio.com/)

**6. Run the app**
```
http://localhost/bookmyshow-clone/
```

---

## 🔐 Default Credentials

| Role | URL | Email | Password |
|------|-----|-------|----------|
| Admin | `/admin-dashboard.php` | `admin@bookyourshow.com` | `admin123` |
| Operator | `/operator-login.php` | *(Register a new account)* | — |
| User | `/login.php` | *(Register a new account)* | — |

> ⚠️ Change the admin password after first login!

---

## 📸 Screenshots

> *(Add screenshots of your homepage, movie listing, seat selection, and admin panel here)*

---

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first.

---

## 📄 License

This project is for **educational purposes** only and is not affiliated with BookMyShow.

---

<div align="center">
  Made with ❤️ by <a href="https://github.com/abhishhdubey">Abhishek Dubey</a>
</div>
