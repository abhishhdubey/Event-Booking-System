<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$show_id = (int)($_POST['show_id'] ?? 0);
$selected_seats = trim($_POST['selected_seats'] ?? '');
$food_items = trim($_POST['food_items'] ?? '');

if (!$show_id || !$selected_seats) {
    header('Location: movies.php');
    exit;
}

$show = $conn->query("SELECT s.*, m.title, t.theatre_name, t.city FROM shows s JOIN movies m ON s.movie_id = m.movie_id JOIN theatres t ON s.theatre_id = t.theatre_id WHERE s.show_id = $show_id")->fetch_assoc();

$seatArr = explode(',', $selected_seats);
$seatCount = count($seatArr);
$subtotal = $seatCount * $show['price'];
$convFee = $seatCount * 20;

// Calculate food cost from food_items string
$foodTotal = 0;
if ($food_items) {
    $fitems = explode('|', $food_items);
    foreach ($fitems as $fi) {
        $parts = explode(' x', $fi);
        if (count($parts) == 2) {
            $fname = trim($parts[0]);
            $fqty = (int)$parts[1];
            $frow = $conn->query("SELECT price FROM foods WHERE food_name = '" . $conn->real_escape_string($fname) . "'")->fetch_assoc();
            if ($frow)
                $foodTotal += $frow['price'] * $fqty;
        }
    }
}
$total = $subtotal + $convFee + $foodTotal;

// Coupon handling
$coupon_code = '';
$discount = 0;
$coupon_msg = '';

if (isset($_POST['apply_coupon']) || isset($_POST['coupon_code'])) {
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    if ($coupon_code) {
        $coupon = $conn->query("SELECT * FROM coupons WHERE coupon_code = '$coupon_code' AND expiry_date >= CURDATE()")->fetch_assoc();
        if ($coupon) {
            // Check if this coupon is already used by this user
            $user_id_check = (int)$_SESSION['user_id'];
            $used_check = $conn->query("SELECT booking_id FROM bookings WHERE user_id = $user_id_check AND coupon_code = '$coupon_code' LIMIT 1")->fetch_assoc();
            if ($used_check) {
                $_SESSION['coupon'] = null;
                $coupon_msg = 'already_used';
            }
            else {
                $discount = ($total * $coupon['discount_percent']) / 100;
                $total = $total - $discount;
                $_SESSION['coupon'] = ['code' => $coupon_code, 'discount' => $discount];
                $coupon_msg = 'success';
            }
        }
        else {
            $_SESSION['coupon'] = null;
            $coupon_msg = 'invalid';
        }
    }
}
elseif (isset($_SESSION['coupon'])) {
    $coupon_code = $_SESSION['coupon']['code'];
    $discount = $_SESSION['coupon']['discount'];
    $total -= $discount;
}

// Payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    $payment_method = $_POST['payment_method'] ?? 'Credit/Debit Card';
    $user_id = (int)$_SESSION['user_id'];
    $final_total = $total;
    $status = 'confirmed';

    // Insert booking (with coupon code tracking)
    $used_coupon = $coupon_code ?: null;
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, show_id, seats, total_price, booking_status, coupon_code) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iisdss', $user_id, $show_id, $selected_seats, $final_total, $status, $used_coupon);
    $stmt->execute();
    $booking_id = $conn->insert_id;

    // Insert payment
    $pstmt = $conn->prepare("INSERT INTO payments (booking_id, payment_method, amount, payment_status) VALUES (?, ?, ?, ?)");
    $pstatus = 'success';
    $pstmt->bind_param('isds', $booking_id, $payment_method, $final_total, $pstatus);
    $pstmt->execute();

    // Insert food order if any
    if ($food_items) {
        $food_total = 0;
        $items = explode('|', $food_items);
        foreach ($items as $item) {
            $parts = explode(' x', $item);
            if (count($parts) == 2) {
                $fname = trim($parts[0]);
                $fqty = (int)$parts[1];
                $frow = $conn->query("SELECT price FROM foods WHERE food_name = '" . $conn->real_escape_string($fname) . "'")->fetch_assoc();
                if ($frow)
                    $food_total += $frow['price'] * $fqty;
            }
        }
        $fstmt = $conn->prepare("INSERT INTO food_orders (booking_id, food_items, total_price) VALUES (?, ?, ?)");
        $fstmt->bind_param('isd', $booking_id, $food_items, $food_total);
        $fstmt->execute();
    }

    // --- True API Integrations: Send Email & SMS Notification ---
    $user = $conn->query("SELECT email, phone, name FROM users WHERE user_id = $user_id")->fetch_assoc();
    $bms_id = 'BMS' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    $ticket_msg = "Hello {$user['name']}, your BookYourShow ticket $bms_id is confirmed! Amount paid: Rs.$final_total. Enjoy the show!";

    // 1. Send Email via SendGrid API (cURL)
    if (defined('SENDGRID_API_KEY') && SENDGRID_API_KEY !== 'YOUR_SENDGRID_API_KEY_HERE') {
        $email_data = [
            'personalizations' => [['to' => [['email' => $user['email']]]]],
            'from' => ['email' => SENDGRID_FROM_EMAIL, 'name' => SENDGRID_FROM_NAME],
            'subject' => 'Ticket Confirmed: ' . $bms_id,
            'content' => [['type' => 'text/html', 'value' => "<h3>Booking Confirmed!</h3><p>$ticket_msg</p>"]]
        ];

        $ch_email = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch_email, CURLOPT_POST, 1);
        curl_setopt($ch_email, CURLOPT_POSTFIELDS, json_encode($email_data));
        curl_setopt($ch_email, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . SENDGRID_API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch_email, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch_email);
        curl_close($ch_email);
    }

    // 2. Send SMS via Twilio API (cURL)
    if (defined('TWILIO_ACCOUNT_SID') && TWILIO_ACCOUNT_SID !== 'YOUR_TWILIO_SID_HERE') {
        $twilio_url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json";
        $sms_data = [
            'From' => TWILIO_FROM_NUMBER,
            'To' => '+91' . $user['phone'], // Assuming Indian numbers
            'Body' => $ticket_msg
        ];

        $ch_sms = curl_init($twilio_url);
        curl_setopt($ch_sms, CURLOPT_POST, 1);
        curl_setopt($ch_sms, CURLOPT_POSTFIELDS, http_build_query($sms_data));
        curl_setopt($ch_sms, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
        curl_setopt($ch_sms, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch_sms);
        curl_close($ch_sms);
    }

    // Clear coupon
    unset($_SESSION['coupon']);

    header("Location: payment-success.php?booking_id=$booking_id");
    exit;
}

$pageTitle = 'Payment - BookYourShow';
include 'includes/header.php';
?>

<div class="container" style="padding:40px 15px;">
    <div class="breadcrumb">
        <a href="index.php">Home</a> <span class="separator">›</span>
        <span>Payment</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 350px;gap:24px;">
        <!-- Payment Methods -->
        <div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
                <h3 style="margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);">💳 Choose Payment Method</h3>

                <form method="POST" id="paymentForm">
                    <input type="hidden" name="show_id" value="<?php echo $show_id; ?>">
                    <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars($selected_seats); ?>">
                    <input type="hidden" name="food_items" value="<?php echo htmlspecialchars($food_items); ?>">
                    <input type="hidden" name="coupon_code" value="<?php echo htmlspecialchars($coupon_code); ?>">
                    <input type="hidden" name="pay" value="1">

                    <div class="payment-methods">
                        <?php
$methods = [
    ['Credit/Debit Card', '💳', 'Visa, Mastercard, Rupay'],
    ['UPI', '📱', 'Google Pay, PhonePe, Paytm'],
];
foreach ($methods as $i => $m):
?>
                        <label class="payment-method-label <?php echo $i === 0 ? 'selected' : ''; ?>" onclick="switchPayment('<?php echo $m[0]; ?>')">
                            <input type="radio" name="payment_method" value="<?php echo $m[0]; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> required>
                            <span class="payment-icon"><?php echo $m[1]; ?></span>
                            <div>
                                <div class="payment-name"><?php echo $m[0]; ?></div>
                                <div style="color:var(--text-muted);font-size:0.8rem;"><?php echo $m[2]; ?></div>
                            </div>
                        </label>
                        <?php
endforeach; ?>
                    </div>

                    <!-- Card details (shown by default) -->
                    <div id="cardDetails" style="margin-top:20px;background:var(--bg-dark);padding:20px;border-radius:var(--radius);border:1px solid var(--border);">
                        <h5 style="margin-bottom:16px;font-size:0.95rem;">💳 Card Details</h5>
                        <div class="form-group">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="cardNumInput" placeholder="1234 5678 9012 3456" maxlength="19" oninput="formatCard(this)" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="cardExpInput" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cardCvvInput" placeholder="123" maxlength="3" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="cardNameInput" placeholder="Full name as on card" required>
                        </div>
                    </div>

                    <!-- UPI details (hidden by default) -->
                    <div id="upiDetails" style="display:none;margin-top:20px;background:var(--bg-dark);padding:20px;border-radius:var(--radius);border:1px solid var(--border);">
                        <h5 style="margin-bottom:16px;font-size:0.95rem;">📱 Enter UPI ID</h5>
                        <div class="form-group">
                            <label class="form-label">UPI ID</label>
                            <input type="text" class="form-control" id="upiId" placeholder="yourname@upi" autocomplete="off">
                            <small style="color:var(--text-muted);font-size:0.78rem;margin-top:4px;display:block;">Example: 9876543210@paytm, name@gpay, name@phonepe</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg" style="width:100%;margin-top:20px;justify-content:center;">
                        🔒 Pay ₹<?php echo number_format($total, 2); ?> Now
                    </button>
                    <p style="text-align:center;color:var(--text-muted);font-size:0.8rem;margin-top:10px;">🔒 Your payment is 100% secure & encrypted</p>
                </form>
            </div>
        </div>

        <!-- Order Summary -->
        <div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;position:sticky;top:90px;">
                <h4 style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">🧾 Order Summary</h4>

                <div style="font-size:0.9rem;">
                    <div style="font-weight:600;margin-bottom:4px;"><?php echo htmlspecialchars($show['title']); ?></div>
                    <div style="color:var(--text-muted);margin-bottom:12px;">
                        <?php echo htmlspecialchars($show['theatre_name']); ?>, <?php echo htmlspecialchars($show['city']); ?><br>
                        <?php echo date('D, d M Y h:i A', strtotime($show['show_date'] . ' ' . $show['show_time'])); ?>
                    </div>
                    <div style="background:rgba(248,68,100,0.08);padding:10px;border-radius:8px;margin-bottom:12px;">
                        <strong>Seats:</strong> <?php echo htmlspecialchars($selected_seats); ?>
                    </div>
                </div>

                <!-- Coupon Box -->
                <form method="POST" id="couponForm" style="margin-bottom:16px;">
                    <input type="hidden" name="show_id" value="<?php echo $show_id; ?>">
                    <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars($selected_seats); ?>">
                    <input type="hidden" name="food_items" value="<?php echo htmlspecialchars($food_items); ?>">
                    <input type="hidden" name="apply_coupon" value="1">
                    <div class="coupon-box">
                        <input type="text" name="coupon_code" id="couponCode" placeholder="Enter coupon code" value="<?php echo htmlspecialchars($coupon_code); ?>" style="background:var(--bg-dark);border:1px solid var(--border);color:var(--text-light);padding:10px 14px;border-radius:8px;outline:none;flex:1;">
                        <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                    </div>
                    <?php if ($coupon_msg === 'success'): ?>
                    <p style="color:var(--green);font-size:0.82rem;margin-top:4px;">✅ Coupon applied! Saved ₹<?php echo number_format($discount, 2); ?></p>
                    <?php
elseif ($coupon_msg === 'already_used'): ?>
                    <p style="color:#f59e0b;font-size:0.82rem;margin-top:4px;">⚠️ Already Used! This coupon can only be used once per user.</p>
                    <?php
elseif ($coupon_msg === 'invalid'): ?>
                    <p style="color:var(--primary);font-size:0.82rem;margin-top:4px;">❌ Invalid or expired coupon code.</p>
                    <?php
endif; ?>
                </form>

                <!-- Price Breakdown -->
                <div style="border-top:1px solid var(--border);padding-top:14px;">
                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:0.88rem;">
                        <span style="color:var(--text-muted);">Tickets (<?php echo $seatCount; ?>)</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($foodTotal > 0): ?>
                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:0.88rem;">
                        <span style="color:var(--text-muted);">🍿 Food &amp; Beverages</span>
                        <span>₹<?php echo number_format($foodTotal, 2); ?></span>
                    </div>
                    <?php
endif; ?>
                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:0.88rem;">
                        <span style="color:var(--text-muted);">Convenience Fee</span>
                        <span>₹<?php echo number_format($convFee, 2); ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:0.88rem;color:var(--green);">
                        <span>Coupon Discount (<?php echo htmlspecialchars($coupon_code); ?>)</span>
                        <span>-₹<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <?php
endif; ?>
                    <div style="border-top:2px solid var(--border);padding-top:12px;margin-top:8px;display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;">
                        <span>Total</span>
                        <span style="color:var(--primary);">₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatCard(input) {
    var val = input.value.replace(/\D/g, '').substring(0, 16);
    input.value = val.replace(/(.{4})/g, '$1 ').trim();
}
function switchPayment(method) {
    var cardDiv = document.getElementById('cardDetails');
    var upiDiv  = document.getElementById('upiDetails');
    
    var cardInputs = [document.getElementById('cardNumInput'), document.getElementById('cardExpInput'), document.getElementById('cardCvvInput'), document.getElementById('cardNameInput')];
    var upiInput = document.getElementById('upiId');
    
    // Update selected style
    document.querySelectorAll('.payment-method-label').forEach(function(lbl) {
        lbl.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    
    if (method === 'UPI') {
        cardDiv.style.display = 'none';
        upiDiv.style.display  = 'block';
        cardInputs.forEach(input => input.removeAttribute('required'));
        upiInput.setAttribute('required', 'required');
    } else {
        cardDiv.style.display = 'block';
        upiDiv.style.display  = 'none';
        cardInputs.forEach(input => input.setAttribute('required', 'required'));
        upiInput.removeAttribute('required');
    }
}
</script>

<?php include 'includes/footer.php'; ?>

