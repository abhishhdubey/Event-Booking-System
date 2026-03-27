// BookYourShow Clone - Main JavaScript
// =============================================

// ---- Dark Mode Toggle ----
function initDarkMode() {
    const toggle = document.getElementById('darkToggle');
    const body = document.body;
    const saved = localStorage.getItem('darkMode');
    if (saved === 'light') {
        body.classList.add('light-mode');
        if (toggle) toggle.textContent = '☀️';
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            body.classList.toggle('light-mode');
            if (body.classList.contains('light-mode')) {
                localStorage.setItem('darkMode', 'light');
                toggle.textContent = '☀️';
            } else {
                localStorage.setItem('darkMode', 'dark');
                toggle.textContent = '🌙';
            }
        });
    }
}

// ---- Hero Slider ----
function initHeroSlider() {
    var slides = document.querySelectorAll('.hero-slide');
    var dots   = document.querySelectorAll('.hero-dot');
    if (!slides.length) return;

    var current = 0;
    var timer;
    var transitioning = false;

    // Show first slide immediately with NO transition
    slides[0].style.transition = 'none';
    slides[0].classList.add('active');
    if (dots[0]) dots[0].classList.add('active');

    // Re-enable transitions after a short delay
    setTimeout(function () {
        slides.forEach(function (s) { s.style.transition = ''; });
    }, 100);

    function showSlide(n) {
        if (transitioning) return;
        transitioning = true;
        slides[current].classList.remove('active');
        dots.forEach(function (d) { d.classList.remove('active'); });
        current = (n + slides.length) % slides.length;
        slides[current].classList.add('active');
        if (dots[current]) dots[current].classList.add('active');
        setTimeout(function () { transitioning = false; }, 800);
    }

    function next() { showSlide(current + 1); }

    dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () {
            clearInterval(timer);
            showSlide(i);
            timer = setInterval(next, 6000);
        });
    });

    // Only start auto-sliding after 2 seconds so the page feels settled
    timer = setInterval(next, 6000);
}

// ---- Seat Selection ----
var selectedSeats = [];

function initSeatSelection() {
    var seats = document.querySelectorAll('.seat:not(.booked)');
    var summaryEl = document.getElementById('selectedSeatsSummary');
    var countEl = document.getElementById('selectedCount');
    var totalEl = document.getElementById('totalPrice');
    var hiddenInput = document.getElementById('selectedSeatsInput');
    var price = parseFloat(document.getElementById('seatPrice') ? document.getElementById('seatPrice').value : 0);

    seats.forEach(function (seat) {
        seat.addEventListener('click', function () {
            var seatNum = seat.getAttribute('data-seat');
            if (seat.classList.contains('selected')) {
                seat.classList.remove('selected');
                seat.classList.add('available');
                selectedSeats = selectedSeats.filter(function (s) { return s !== seatNum; });
            } else {
                if (selectedSeats.length >= 8) {
                    showToast('Maximum 8 seats can be selected', 'error');
                    return;
                }
                seat.classList.add('selected');
                seat.classList.remove('available');
                selectedSeats.push(seatNum);
            }
            if (summaryEl) summaryEl.textContent = selectedSeats.length ? selectedSeats.join(', ') : 'None';
            if (countEl) countEl.textContent = selectedSeats.length;
            if (totalEl) totalEl.textContent = '₹' + (selectedSeats.length * price).toFixed(2);
            if (hiddenInput) hiddenInput.value = selectedSeats.join(',');
        });
    });
}

// ---- Payment Method Selection ----
function initPaymentMethods() {
    var labels = document.querySelectorAll('.payment-method-label');
    labels.forEach(function (label) {
        label.addEventListener('click', function () {
            labels.forEach(function (l) { l.classList.remove('selected'); });
            label.classList.add('selected');
        });
    });
}

// ---- Form Validation ----
function validateRegisterForm() {
    var name = document.getElementById('name');
    var email = document.getElementById('email');
    var phone = document.getElementById('phone');
    var password = document.getElementById('password');
    var confirm = document.getElementById('confirm_password');

    if (name && name.value.trim().length < 2) {
        showToast('Please enter your full name', 'error');
        name.focus();
        return false;
    }
    if (email && !isValidEmail(email.value)) {
        showToast('Please enter a valid email address', 'error');
        email.focus();
        return false;
    }
    if (phone && !/^[0-9]{10}$/.test(phone.value)) {
        showToast('Phone number must be 10 digits', 'error');
        phone.focus();
        return false;
    }
    if (password && password.value.length < 6) {
        showToast('Password must be at least 6 characters', 'error');
        password.focus();
        return false;
    }
    if (confirm && password && confirm.value !== password.value) {
        showToast('Passwords do not match', 'error');
        confirm.focus();
        return false;
    }
    return true;
}

function validateLoginForm() {
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    if (email && !isValidEmail(email.value)) {
        showToast('Please enter a valid email', 'error');
        return false;
    }
    if (password && !password.value) {
        showToast('Please enter your password', 'error');
        return false;
    }
    return true;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ---- Password Toggle ----
function initPasswordToggle() {
    var toggles = document.querySelectorAll('.input-toggle');
    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var input = toggle.previousElementSibling;
            if (input && input.type === 'password') {
                input.type = 'text';
                toggle.textContent = '🙈';
            } else if (input) {
                input.type = 'password';
                toggle.textContent = '👁';
            }
        });
    });
}

// ---- Toast Notifications ----
function showToast(message, type) {
    type = type || 'info';
    var container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    var icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<span>' + (icons[type] || 'ℹ️') + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        toast.style.transition = 'all 0.4s ease';
        setTimeout(function () { toast.remove(); }, 400);
    }, 4000);
}

// ---- Trailer Modal ----
function getYouTubeEmbedUrl(url) {
    if (!url) return '';
    var videoId = '';

    // Already an embed URL: https://www.youtube.com/embed/VIDEO_ID
    var embedMatch = url.match(/youtube\.com\/embed\/([^?&]+)/);
    if (embedMatch) {
        videoId = embedMatch[1];
    }

    // Standard watch URL: https://www.youtube.com/watch?v=VIDEO_ID (may have extra params)
    if (!videoId) {
        var watchMatch = url.match(/[?&]v=([^&]+)/);
        if (watchMatch) {
            videoId = watchMatch[1];
        }
    }

    // Short URL: https://youtu.be/VIDEO_ID
    if (!videoId) {
        var shortMatch = url.match(/youtu\.be\/([^?&]+)/);
        if (shortMatch) {
            videoId = shortMatch[1];
        }
    }

    if (videoId) {
        return 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
    }

    // Fallback: return as-is (non-YouTube links)
    return url;
}

function openTrailer(url) {
    var modal = document.getElementById('trailerModal');
    var frame = document.getElementById('trailerFrame');
    if (modal && frame) {
        frame.src = getYouTubeEmbedUrl(url);
        modal.classList.add('show');
    }
}

function closeTrailer() {
    var modal = document.getElementById('trailerModal');
    var frame = document.getElementById('trailerFrame');
    if (modal) modal.classList.remove('show');
    if (frame) frame.src = '';
}

// ---- Food Counter ----
function initFoodCounters() {
    document.querySelectorAll('.food-counter').forEach(function (counter) {
        var minusBtn = counter.querySelector('.minus');
        var plusBtn = counter.querySelector('.plus');
        var qtyEl = counter.querySelector('.food-qty');
        var foodName = counter.getAttribute('data-food');
        var foodPrice = parseFloat(counter.getAttribute('data-price') || 0);

        if (!qtyEl) return;

        minusBtn && minusBtn.addEventListener('click', function () {
            var qty = parseInt(qtyEl.textContent);
            if (qty > 0) {
                qtyEl.textContent = qty - 1;
                updateFoodOrder(foodName, qty - 1, foodPrice);
            }
        });

        plusBtn && plusBtn.addEventListener('click', function () {
            var qty = parseInt(qtyEl.textContent);
            qtyEl.textContent = qty + 1;
            updateFoodOrder(foodName, qty + 1, foodPrice);
        });
    });
}

var foodOrders = {};

function updateFoodOrder(name, qty, price) {
    if (qty === 0) {
        delete foodOrders[name];
    } else {
        foodOrders[name] = { qty: qty, price: price };
    }
    updateFoodSummary();
}

function updateFoodSummary() {
    var total = 0;
    var items = [];
    for (var name in foodOrders) {
        total += foodOrders[name].qty * foodOrders[name].price;
        items.push(name + ' x' + foodOrders[name].qty);
    }
    var summaryEl = document.getElementById('foodSummary');
    var totalEl = document.getElementById('foodTotal');
    var inputEl = document.getElementById('foodOrderInput');
    if (summaryEl) summaryEl.textContent = items.join(', ') || 'None';
    if (totalEl) totalEl.textContent = '₹' + total.toFixed(2);
    if (inputEl) inputEl.value = items.join('|');
}

// ---- Hamburger / Mobile Nav ----
function initMobileNav() {
    var hamburger = document.getElementById('hamburger');
    var mobileNav = document.getElementById('mobileNav');
    if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function () {
            mobileNav.classList.toggle('show');
        });
    }
}

// ---- Coupon Apply (Client-side preview) ----
function initCoupon() {
    var btn = document.getElementById('applyCoupon');
    if (btn) {
        btn.addEventListener('click', function () {
            var code = document.getElementById('couponCode').value.trim();
            if (!code) {
                showToast('Please enter a coupon code', 'error');
                return;
            }
            // Submit the form to let PHP handle it
            var form = document.getElementById('couponForm');
            if (form) form.submit();
        });
    }
}

// ---- Star Rating ----
function initStarRating() {
    var stars = document.querySelectorAll('.star-label');
    var ratingInput = document.getElementById('ratingInput');
    stars.forEach(function (star, index) {
        star.addEventListener('click', function () {
            var val = index + 1;
            if (ratingInput) ratingInput.value = val;
            stars.forEach(function (s, i) {
                s.style.color = i < val ? '#ffd700' : '#2e2e4e';
            });
        });
    });
}

// ---- Search Filter ----
function filterMovies() {
    var input = document.getElementById('movieSearch');
    if (!input) return;
    var filter = input.value.toUpperCase();
    var cards = document.querySelectorAll('.movie-card');
    cards.forEach(function (card) {
        var title = card.querySelector('.movie-title');
        if (title && title.textContent.toUpperCase().indexOf(filter) > -1) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// ---- Print Ticket ----
function printTicket() {
    window.print();
}

// ---- Smooth scroll to section ----
function scrollTo(id) {
    var el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
}

// ---- Initialize everything on DOM ready ----
document.addEventListener('DOMContentLoaded', function () {
    initDarkMode();
    initHeroSlider();
    initSeatSelection();
    initPaymentMethods();
    initPasswordToggle();
    initMobileNav();
    initFoodCounters();
    initCoupon();
    initStarRating();

    // Close trailer modal on outside click
    var modal = document.getElementById('trailerModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeTrailer();
        });
    }

    // Restore city in navbar selector
    var citySelect = document.getElementById('citySelect');
    if (citySelect) {
        var savedCity = localStorage.getItem('city');
        if (savedCity) {
            for (var i = 0; i < citySelect.options.length; i++) {
                if (citySelect.options[i].value === savedCity) {
                    citySelect.selectedIndex = i;
                    break;
                }
            }
        }
        citySelect.addEventListener('change', function () {
            localStorage.setItem('city', this.value);
        });
    }
});

