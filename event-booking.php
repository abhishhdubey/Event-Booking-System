<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: events.php');
    exit;
}

$event = $conn->query("SELECT * FROM events WHERE event_id = $id")->fetch_assoc();
if (!$event) {
    header('Location: events.php');
    exit;
}

$msg = '';
$coupon_msg = '';

$tickets = (int)($_POST['tickets'] ?? 1);
if ($tickets < 1 || $tickets > 10) $tickets = 1;

$subtotal = $tickets * $event['ticket_price'];
$discount = 0;
$coupon_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_coupon'])) {
        $coupon_code = trim($_POST['coupon_code']);
        $coupon = $conn->query("SELECT * FROM coupons WHERE code = '" . $conn->real_escape_string($coupon_code) . "' AND status = 'active' AND valid_until >= CURDATE()")->fetch_assoc();
        
        if ($coupon) {
            $user_id_check = (int)$_SESSION['user_id'];
            $used_check = $conn->query("SELECT booking_id FROM event_bookings WHERE user_id = $user_id_check AND coupon_code = '" . $conn->real_escape_string($coupon_code) . "' LIMIT 1")->fetch_assoc();
            
            if ($used_check) {
                $_SESSION['event_coupon'] = null;
                $coupon_msg = 'already_used';
            } else {
                $_SESSION['event_coupon'] = ['code' => $coupon_code, 'percent' => $coupon['discount_percent']];
                $coupon_msg = 'success';
            }
        } else {
            $_SESSION['event_coupon'] = null;
            $coupon_msg = 'invalid';
        }
    } elseif (isset($_POST['pay'])) {
        $payment_method = $_POST['payment_method'] ?? 'Credit/Debit Card';
        $coupon_code = $_SESSION['event_coupon']['code'] ?? NULL;
        
        $discount = 0;
        if (isset($_SESSION['event_coupon'])) {
            $discount = ($subtotal * $_SESSION['event_coupon']['percent']) / 100;
        }
        $total = $subtotal - $discount;
        
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO event_bookings (user_id, event_id, tickets, total_price, payment_method, coupon_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iiidss', $uid, $id, $tickets, $total, $payment_method, $coupon_code);
        
        if ($stmt->execute()) {
            $ebid = $conn->insert_id;
            $_SESSION['event_coupon'] = null;
            header("Location: event-payment-success.php?booking_id=$ebid");
            exit;
        } else {
            $msg = 'error:Booking failed. Please try again.';
        }
    }
}

// Recalculate discount based on current session
if (isset($_SESSION['event_coupon'])) {
    $coupon_code = $_SESSION['event_coupon']['code'];
    $discount = ($subtotal * $_SESSION['event_coupon']['percent']) / 100;
}

$total = $subtotal - $discount;

$pageTitle = 'Book Event - ' . htmlspecialchars($event['event_name']);
include 'includes/header.php';
?>
<div class="container" style="padding:40px 15px;max-width:1000px;">
    <div class="breadcrumb"><a href="events.php">Events</a> <span class="separator">›</span><a href="event-details.php?id=<?php echo $id; ?>"><?php echo htmlspecialchars($event['event_name']); ?></a> <span class="separator">›</span><span>Payment</span></div>

    <h2 class="section-title" style="margin-bottom:20px;">🎟️ Event Payment</h2>

    <?php if ($msg): list($type, $text) = explode(':', $msg, 2); ?>
    <div class="alert alert-<?php echo $type === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($text); ?></div>
    <?php if ($type === 'success'): ?>
    <div style="text-align:center;margin-top:20px;margin-bottom:40px;">
        <a href="events.php" class="btn btn-primary">Browse More Events</a>
        <a href="booking-history.php" class="btn btn-dark" style="margin-left:10px;">My Bookings</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (!$msg || strpos($msg, 'error') === 0): ?>
    <div style="display:grid;grid-template-columns:1fr 350px;gap:24px;align-items:start;">
        <!-- Left Column: Payment Process -->
        <div>
            <!-- Ticket Selection -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:24px;">
                <h4 style="margin-bottom:16px;">🎟️ Number of Tickets</h4>
                <form id="updateTicketsForm" method="POST">
                    <select name="tickets" class="form-control" style="max-width:200px;" onchange="document.getElementById('updateTicketsForm').submit();">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $tickets === $i ? 'selected' : ''; ?>><?php echo $i; ?> Ticket<?php echo $i > 1 ? 's' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <!-- Payment Method UI -->
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;">
                <h4 style="margin-bottom:20px;">Payment Options</h4>
                
                <form method="POST">
                    <input type="hidden" name="tickets" value="<?php echo $tickets; ?>">
                    <input type="hidden" name="pay" value="1">

                    <div class="payment-methods">
                        <?php
                        $methods = [
                            ['Credit/Debit Card', '💳', 'Visa, Mastercard, Rupay'],
                            ['UPI', '📱', 'Google Pay, PhonePe, Paytm'],
                        ];
                        foreach ($methods as $i => $m):
                        ?>
                        <label class="payment-method-label <?php echo $i === 0 ? 'selected' : ''; ?>" onclick="switchPayment('<?php echo $m[0]; ?>', event)">
                            <input type="radio" name="payment_method" value="<?php echo $m[0]; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> required>
                            <span class="payment-icon"><?php echo $m[1]; ?></span>
                            <div>
                                <div class="payment-name"><?php echo $m[0]; ?></div>
                                <div style="color:var(--text-muted);font-size:0.8rem;"><?php echo $m[2]; ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
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
                            <input type="text" class="form-control" id="upiIdInput" placeholder="yourname@upi" autocomplete="off">
                            <small style="color:var(--text-muted);font-size:0.78rem;margin-top:4px;display:block;">Example: 9876543210@paytm, name@gpay, name@phonepe</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg" style="width:100%;margin-top:20px;justify-content:center;">
                        🔒 Confirm Booking ₹<?php echo number_format($total, 2); ?>
                    </button>
                    <p style="text-align:center;color:var(--text-muted);font-size:0.8rem;margin-top:10px;">🔒 Your payment is 100% secure & encrypted</p>
                </form>
            </div>
        </div>

        <!-- Right Column: Order Summary -->
        <div>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;position:sticky;top:90px;">
                <h4 style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border);">🧾 Order Summary</h4>

                <div style="font-size:0.9rem;">
                    <div style="font-weight:600;margin-bottom:4px;"><?php echo htmlspecialchars($event['event_name']); ?></div>
                    <div style="color:var(--text-muted);margin-bottom:12px;">
                        📍 <?php echo htmlspecialchars($event['location']); ?><br>
                        📅 <?php echo date('D, d M Y', strtotime($event['event_date'])); ?>
                    </div>
                    
                    <div style="background:rgba(248,68,100,0.08);padding:10px;border-radius:8px;margin-bottom:12px;">
                        <strong>Tickets:</strong> <?php echo $tickets; ?>
                    </div>
                </div>

                <!-- Coupon Form -->
                <form method="POST" style="margin-bottom:16px;">
                    <input type="hidden" name="tickets" value="<?php echo $tickets; ?>">
                    <input type="hidden" name="apply_coupon" value="1">
                    <div style="display:flex;gap:8px;">
                        <input type="text" name="coupon_code" class="form-control" placeholder="Enter coupon code" value="<?php echo htmlspecialchars($coupon_code); ?>" required style="padding:8px;font-size:0.9rem;">
                        <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                    </div>
                    <?php if ($coupon_msg === 'success'): ?>
                    <p style="color:var(--green);font-size:0.82rem;margin-top:4px;">✅ Coupon applied! Saved ₹<?php echo number_format($discount, 2); ?></p>
                    <?php elseif ($coupon_msg === 'already_used'): ?>
                    <p style="color:#f59e0b;font-size:0.82rem;margin-top:4px;">⚠️ Already Used! This coupon can only be used once per user.</p>
                    <?php elseif ($coupon_msg === 'invalid'): ?>
                    <p style="color:var(--primary);font-size:0.82rem;margin-top:4px;">❌ Invalid or expired coupon code.</p>
                    <?php endif; ?>
                </form>

                <!-- Price Breakdown -->
                <div>
                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:0.88rem;">
                        <span style="color:var(--text-muted);">Ticket Price</span>
                        <span>₹<?php echo number_format($event['ticket_price'], 2); ?></span>
                    </div>
                    
                    <?php if ($discount > 0): ?>
                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:0.88rem;color:var(--green);">
                        <span>Coupon Discount (<?php echo htmlspecialchars($coupon_code); ?>)</span>
                        <span>-₹<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div style="border-top:2px solid var(--border);padding-top:12px;margin-top:8px;display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;">
                        <span>Total</span>
                        <span style="color:var(--primary);">₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function formatCard(input) {
    var val = input.value.replace(/\D/g, '').substring(0, 16);
    input.value = val.replace(/(.{4})/g, '$1 ').trim();
}

function switchPayment(method, evt) {
    var cardDiv = document.getElementById('cardDetails');
    var upiDiv  = document.getElementById('upiDetails');
    
    var cardInputs = [document.getElementById('cardNumInput'), document.getElementById('cardExpInput'), document.getElementById('cardCvvInput'), document.getElementById('cardNameInput')];
    var upiInput = document.getElementById('upiIdInput');
    
    // Update selected style
    document.querySelectorAll('.payment-method-label').forEach(function(lbl) {
        lbl.classList.remove('selected');
    });
    evt.currentTarget.classList.add('selected');
    
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
