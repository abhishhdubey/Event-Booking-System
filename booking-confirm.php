<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$show_id = (int)($_POST['show_id'] ?? 0);
$selected_seats = trim($_POST['selected_seats'] ?? '');

if (!$show_id || !$selected_seats) { header('Location: movies.php'); exit; }

// Store in session for payment
$_SESSION['pending_booking'] = [
    'show_id' => $show_id,
    'seats' => $selected_seats
];

$show = $conn->query("
    SELECT s.*, m.title, m.language, m.duration, t.theatre_name, t.city, t.location
    FROM shows s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theatres t ON s.theatre_id = t.theatre_id
    WHERE s.show_id = $show_id
")->fetch_assoc();

$seatArr = explode(',', $selected_seats);
$seatCount = count($seatArr);
$subtotal = $seatCount * $show['price'];

// Food menu
$foods = $conn->query("SELECT * FROM foods ORDER BY food_name");

$pageTitle = 'Booking Confirmation - BookYourShow';
include 'includes/header.php';
?>

<div class="container" style="padding:40px 15px;">
    <div class="breadcrumb">
        <a href="index.php">Home</a> <span class="separator">›</span>
        <a href="movies.php">Movies</a> <span class="separator">›</span>
        <span>Booking Confirmation</span>
    </div>

    <h2 class="section-title" style="margin-bottom:20px;">🎟️ Confirm Your Booking</h2>

    <div style="display:grid;grid-template-columns:1fr 350px;gap:24px;">
        <div>
            <!-- Booking Summary Card -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
                <h4 style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">📋 Booking Details</h4>
                <?php
                $details = [
                    '🎬 Movie' => $show['title'],
                    '🏟️ Theatre' => $show['theatre_name'] . ', ' . $show['city'],
                    '📍 Location' => $show['location'],
                    '📅 Date' => date('D, d M Y', strtotime($show['show_date'])),
                    '🕐 Time' => date('h:i A', strtotime($show['show_time'])),
                    '🌐 Language' => $show['language'],
                    '💺 Seats' => implode(', ', $seatArr),
                    '🎫 Count' => $seatCount . ' ticket(s)'
                ];
                foreach($details as $k => $v):
                ?>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);font-size:0.9rem;">
                    <span style="color:var(--text-muted);"><?php echo $k; ?></span>
                    <span style="font-weight:500;"><?php echo htmlspecialchars($v); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Food Pre-booking -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;">
                <h4 style="margin-bottom:16px;">🍿 Add Food & Beverages (Optional)</h4>
                <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:20px;">Pre-book snacks and skip the queue at the counter!</p>
                <div class="food-grid">
                    <?php while($food = $foods->fetch_assoc()): ?>
                    <div class="food-card">
                        <div style="height:100px;background:linear-gradient(135deg,var(--bg-dark),var(--border));display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
                            <?php
                            $emojis = ['🍿' => 'Popcorn','🌮' => 'Nachos','🥤' => 'Pepsi','🎁' => 'Combo','🍔' => 'Burger','🌭' => 'Hot Dog','☕' => 'Coffee','💧' => 'Water'];
                            $emoji = '🍿';
                            foreach($emojis as $e => $n) {
                                if (stripos($food['food_name'], $n) !== false) { $emoji = $e; break; }
                            }
                            echo $emoji;
                            ?>
                        </div>
                        <div class="food-card-info">
                            <div class="food-name"><?php echo htmlspecialchars($food['food_name']); ?></div>
                            <div class="food-price">₹<?php echo $food['price']; ?></div>
                            <div class="food-counter" data-food="<?php echo htmlspecialchars($food['food_name']); ?>" data-price="<?php echo $food['price']; ?>">
                                <button type="button" class="minus">−</button>
                                <span class="food-qty">0</span>
                                <button type="button" class="plus">+</button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div style="margin-top:16px;padding:12px;background:rgba(248,68,100,0.08);border-radius:8px;font-size:0.9rem;">
                    Food items: <span id="foodSummary" style="color:var(--primary);">None</span><br>
                    Food total: <span id="foodTotal" style="font-weight:700;">₹0.00</span>
                </div>
            </div>
        </div>

        <!-- Price Summary -->
        <div>
            <form method="POST" action="payment.php" id="confirmForm">
                <input type="hidden" name="show_id" value="<?php echo $show_id; ?>">
                <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars($selected_seats); ?>">
                <input type="hidden" name="food_items" id="foodOrderInput" value="">

                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;position:sticky;top:90px;">
                    <h4 style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">💰 Price Breakdown</h4>

                    <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:0.9rem;">
                        <span style="color:var(--text-muted);">Tickets (<?php echo $seatCount; ?> × ₹<?php echo $show['price']; ?>)</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:0.9rem;" id="foodRow" style="display:none;">
                        <span style="color:var(--text-muted);">Food & Beverages</span>
                        <span id="foodTotalRow">₹0.00</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:0.9rem;">
                        <span style="color:var(--text-muted);">Convenience Fee</span>
                        <span>₹<?php echo number_format($seatCount * 20, 2); ?></span>
                    </div>
                    <div style="border-top:2px solid var(--border);padding-top:14px;margin-top:6px;display:flex;justify-content:space-between;font-size:1.2rem;font-weight:700;">
                        <span>Total</span>
                        <span id="grandTotal" style="color:var(--primary);">₹<?php echo number_format($subtotal + $seatCount * 20, 2); ?></span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:20px;justify-content:center;font-size:1rem;">
                        🔒 Proceed to Payment
                    </button>

                    <div style="text-align:center;margin-top:12px;color:var(--text-muted);font-size:0.8rem;">
                        🔒 Secured by 256-bit SSL encryption
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var baseSubtotal = <?php echo $subtotal; ?>;
var convFee = <?php echo $seatCount * 20; ?>;

// Override updateFoodSummary to also update grand total
var origUpdateFood = window.updateFoodSummary || function(){};
window.updateFoodSummary = function() {
    var total = 0;
    var items = [];
    for (var name in foodOrders) {
        total += foodOrders[name].qty * foodOrders[name].price;
        items.push(name + ' x' + foodOrders[name].qty);
    }
    var summaryEl = document.getElementById('foodSummary');
    var totalEl = document.getElementById('foodTotal');
    var totalRowEl = document.getElementById('foodTotalRow');
    var grandEl = document.getElementById('grandTotal');
    var inputEl = document.getElementById('foodOrderInput');
    if (summaryEl) summaryEl.textContent = items.join(', ') || 'None';
    if (totalEl) totalEl.textContent = '₹' + total.toFixed(2);
    if (totalRowEl) totalRowEl.textContent = '₹' + total.toFixed(2);
    if (grandEl) grandEl.textContent = '₹' + (baseSubtotal + convFee + total).toFixed(2);
    if (inputEl) inputEl.value = items.join('|');
};
</script>

<?php include 'includes/footer.php'; ?>

