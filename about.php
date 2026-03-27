<?php
session_start();
require_once 'config/config.php';
$pageTitle = 'About Us - BookYourShow';
include 'includes/header.php';
?>

<section class="page-hero">
    <h1>🎬 About BookYourShow</h1>
    <p>Your gateway to entertainment since 2024</p>
</section>

<div class="container" style="padding:60px 15px;">
    <!-- Mission -->
    <div style="max-width:800px;margin:0 auto 60px;text-align:center;">
        <h2 style="font-size:2rem;font-weight:800;margin-bottom:20px;">Our Mission</h2>
        <p style="color:var(--text-muted);font-size:1.1rem;line-height:1.9;">
            BookYourShow is India's leading entertainment ticketing platform. We connect millions of entertainment seekers with thousands of movies, events, concerts, sports, and plays across the country. Our mission is to make entertainment accessible, convenient, and unforgettable.
        </p>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:60px;">
        <?php $stats = [['10M+','Happy Customers'],['5000+','Movies Listed'],['1000+','Events'],['200+','Cities']]; foreach($stats as $s): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:30px;text-align:center;">
            <div style="font-size:2.2rem;font-weight:800;background:var(--gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"><?php echo $s[0]; ?></div>
            <div style="color:var(--text-muted);font-size:0.9rem;margin-top:6px;"><?php echo $s[1]; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Features -->
    <h2 style="text-align:center;font-size:1.8rem;font-weight:800;margin-bottom:40px;">Why Choose Us?</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:24px;margin-bottom:60px;">
        <?php $features = [['🎟️','Easy Booking','Book tickets in under 2 minutes with our streamlined checkout.'],['💺','Seat Selection','Choose your perfect seat from our visual seat maps.'],['🍿','Food Pre-booking','Skip queues at the counter with pre-ordered food & beverages.'],['📱','Multi-Platform','Access from any device - mobile, tablet, or desktop.'],['🔒','Secure Payments','100% secure payments with multiple payment options.'],['🏷️','Great Offers','Exclusive discounts, cashback, and special offers.']]; foreach($features as $f): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;text-align:center;transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="font-size:2.5rem;margin-bottom:12px;"><?php echo $f[0]; ?></div>
            <h4 style="margin-bottom:8px;"><?php echo $f[1]; ?></h4>
            <p style="color:var(--text-muted);font-size:0.85rem;line-height:1.7;"><?php echo $f[2]; ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CTA -->
    <div style="background:var(--gradient);border-radius:var(--radius);padding:50px;text-align:center;">
        <h2 style="font-size:2rem;font-weight:800;margin-bottom:12px;">Ready to Experience More?</h2>
        <p style="color:rgba(255,255,255,0.85);margin-bottom:24px;">Join millions of happy customers. Create your free account today.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="register.php" class="btn btn-dark btn-lg">🚀 Get Started Free</a>
            <a href="movies.php" class="btn" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.5);padding:16px 40px;border-radius:50px;font-weight:600;">Browse Movies</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

