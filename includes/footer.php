<?php
// includes/footer.php
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="brand-logo">BookYourShow</span>
                <p class="footer-desc">Your one-stop destination for booking movie tickets, event passes, and more. Experience entertainment like never before.</p>
                <div class="social-links">
                    <a href="#" class="social-link" title="Facebook">📘</a>
                    <a href="#" class="social-link" title="Twitter">🐦</a>
                    <a href="#" class="social-link" title="Instagram">📷</a>
                    <a href="#" class="social-link" title="YouTube">📺</a>
                </div>
            </div>
            <div>
                <h4 class="footer-heading">Movies</h4>
                <div class="footer-links">
                    <a href="<?php echo BASE_URL; ?>movies.php">Now Showing</a>
                    <a href="<?php echo BASE_URL; ?>movies.php">Coming Soon</a>
                    <a href="<?php echo BASE_URL; ?>movies.php">Top Rated</a>
                    <a href="<?php echo BASE_URL; ?>movies.php">Genres</a>
                </div>
            </div>
            <div>
                <h4 class="footer-heading">Events</h4>
                <div class="footer-links">
                    <a href="<?php echo BASE_URL; ?>events.php">Concerts</a>
                    <a href="<?php echo BASE_URL; ?>events.php">Sports</a>
                    <a href="<?php echo BASE_URL; ?>events.php">Comedy Shows</a>
                    <a href="<?php echo BASE_URL; ?>events.php">Festivals</a>
                </div>
            </div>
            <div>
                <h4 class="footer-heading">Help & Info</h4>
                <div class="footer-links">
                    <a href="<?php echo BASE_URL; ?>about.php">About Us</a>
                    <a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a>
                    <a href="<?php echo BASE_URL; ?>booking-history.php">My Bookings</a>
                    <a href="<?php echo BASE_URL; ?>operator-register.php">List Your Event</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> BookYourShow Clone. All rights reserved.</p>
            <p>Made with ❤️ using PHP & MySQL</p>
        </div>
    </div>
</footer>
<script src="<?php echo BASE_URL; ?>js/script.js?v=2"></script>
</body>
</html>
