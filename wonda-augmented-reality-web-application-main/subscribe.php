<?php require_once 'includes/config.php'; ?>  

<?php 
$subscriptions = $db->select('subscriptions', '*');
$discounts = $db->select('news', '*');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Store current URL with all parameters for redirect after login
    $current_url = $_SERVER['REQUEST_URI'];
    header('Location: login.php?next=' . urlencode($current_url));
    exit;
}

// Get booking parameters from URL or session
$preselected_subscription_id = $_GET['subscription_id'] ?? $_SESSION['booking_subscription_id'] ?? '';
$preselected_venue = $_GET['venue'] ?? $_SESSION['booking_venue'] ?? '';

// Store booking data in session for persistence
if (!empty($_GET['subscription_id'])) {
    $_SESSION['booking_subscription_id'] = $_GET['subscription_id'];
}
if (!empty($_GET['venue'])) {
    $_SESSION['booking_venue'] = $_GET['venue'];
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscription_id'])) {
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : [];
    $user_id = isset($user['id']) ? $user['id'] : null;
    $unique_id = $_POST['unique_id'] ?? strtoupper(bin2hex(random_bytes(5)));
    $type = $_POST['subscription_type'] ?? '';
    $subscription = $_POST['subscription_id'] ?? '';
    $reservation_date = $_POST['reservation_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $total_amount = $_POST['total_amount'] ?? '0.00';
    $payment_method = $_POST['payment_method'] ?? '';
    $counts = $_POST['count'] ?? [];
    $status = 0;
    $hasError = false;

    // Handle file upload
    $proof_filename = '';
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $proof_filename = uniqid('proof_', true) . '.' . $ext;
        $target_dir = __DIR__ . '/assets/images/payments/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        move_uploaded_file($_FILES['proof']['tmp_name'], $target_dir . $proof_filename);
    }

    // Get original price per category from the selected subscription
    $subscriptionOptions = $subscriptions['data'] ?? [];
    $selectedSub = null;
    foreach ($subscriptionOptions as $sub) {
        if ($sub['id'] == $subscription) {
            $selectedSub = $sub;
            break;
        }
    }
    $priceObj = [];
    if ($selectedSub && isset($selectedSub['price'])) {
        foreach (explode(',', $selectedSub['price']) as $pair) {
            $kv = explode(':', $pair, 2);
            if (count($kv) == 2) {
                $priceObj[trim($kv[0])] = floatval($kv[1]);
            }
        }
    }

    // Get all categories from priceObj (even if count is 0)
    $categories = array_keys($priceObj);

    // Get discount per category
    $discounts = [];
    foreach ($categories as $cat) {
        $discounts[$cat] = isset($_POST["discount_$cat"]) ? floatval($_POST["discount_$cat"]) : 0;
    }

    $guest_inputs = isset($_POST['guest_names']) && is_array($_POST['guest_names'])
        ? array_values(array_filter(array_map('trim', $_POST['guest_names']), 'strlen'))
        : [];

    // Validate counts based on guest names
    $errors = [];
    $guest_count = count($guest_inputs);

    // Sum submitted counts (ensure integer)
    $sum_counts = 0;
    if (is_array($counts)) {
        foreach ($counts as $cat => $c) {
            $sum_counts += (int)$c;
        }
    }

    // If guest names provided, ensure counts match number of guests
    if ($guest_count > 0) {
        if ($sum_counts === 0) {
            $errors[] = 'Please select at least one ticket or slot that matches the guest names.';
            $hasError = true;
        } elseif ($sum_counts !== $guest_count) {
            $errors[] = 'The number of selected slots (' . $sum_counts . ') must match the number of guest names (' . $guest_count . ').';
            $hasError = true;
        }
    } else {
        // No guest names provided — require at least one slot selected
        if ($sum_counts === 0) {
            $errors[] = 'Please select at least one ticket or slot for this booking.';
            $hasError = true;
        }
    }


    if ($hasError) {
        // Use simple JS alert to show errors and avoid white page
        $msg = implode("\n", $errors);
        echo '<script>alert(' . json_encode($msg) . '); window.history.back();</script>';
        exit;
    }

    // Store guest names JSON (empty string when none)
    $guest_names = count($guest_inputs) ? json_encode($guest_inputs) : '';

    // Build payment_info string
    $payment_info = "Amount:{$total_amount},Payment:" . ucfirst($payment_method) . ",Proof:{$proof_filename}";
    foreach ($categories as $cat) {
        $count = isset($counts[$cat]) ? (int)$counts[$cat] : 0;
        $orig_price = isset($priceObj[$cat]) ? $priceObj[$cat] : 0;
        $discount = isset($discounts[$cat]) ? $discounts[$cat] : 0;
        $subtotal = isset($_POST["subtotal_$cat"]) ? floatval($_POST["subtotal_$cat"]) : 0;
        $payment_info .= ",{$cat}:{$count}:{$orig_price}:{$discount}:{$subtotal}";
    }

    $data = [
        'unique_id' => $unique_id,
        'user_id' => $user_id,
        'type' => $type,
        'subscription' => $subscription,
        'reservation_date' => date('Y-m-d H:i:s', strtotime($reservation_date)),
        'end_date' => date('Y-m-d H:i:s', strtotime($end_date)),
        'payment_info' => $payment_info,
        'guest_names' => $guest_names,
        'status' => $status
    ];

    $result = $db->insert('transactions', $data);

    if ($result['status'] === 'success') {
        // Clear session booking data after successful booking
        unset($_SESSION['booking_subscription_id']);
        unset($_SESSION['booking_venue']);
        
        // Show success message and redirect
        echo '<script>
            alert("Booking submitted successfully! Your transaction ID is: ' . $unique_id . '");
            window.location.href = "table.php?table=transactions";
        </script>';
        exit;
    } else {
        // Show simple alert on DB error to avoid rendering a blank page
        echo '<script>alert(' . json_encode('Error: ' . ($result['message'] ?? 'Unknown')) . '); window.history.back();</script>';
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>  

<body class="loading" data-layout-color="light" data-leftbar-theme="light" data-layout-mode="fluid" data-rightbar-onstart="true">
    <!-- Begin page -->
    <div class="wrapper">
        <?php require_once 'includes/sidebar.php'; ?>  
        <?php require_once 'includes/topbar.php'; ?>  

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">
                <!-- Start Content-->
                <div class="container-fluid">

                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Book / Subscribe</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Book / Subscribe</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end page title --> 

                    <div class="row">
                        <div class="col-xl-12 col-lg-7">
                            <div class="card">
                                <div class="card-body">
                                    <div class="tab-content">
                                        <div class="tab-pane show active" id="settings">
                                            <?php
                                            // Get user info from session
                                            $user = isset($_SESSION['user']) ? json_decode(json_encode($_SESSION['user']), true) : [];
                                            $firstName = isset($user['name']) ? htmlspecialchars($user['name']) : '';
                                            $birthdate = isset($user['birthdate']) ? htmlspecialchars($user['birthdate']) : '';
                                            $city = isset($user['address']) ? htmlspecialchars($user['address']) : '';
                                            $email = isset($user['email']) ? htmlspecialchars($user['email']) : '';
                                            $number = isset($user['number']) ? htmlspecialchars($user['number']) : '';
                                            $unique_id = strtoupper(bin2hex(random_bytes(5))); // 10-char hex

                                            // Prepare subscriptions and discounts
                                            $subscriptionOptions = $subscriptions['data'] ?? [];
                                            $discountOptions = $discounts['data'] ?? [];
                                            $discount = $discountOptions[0] ?? null;
                                            ?>

                                            <!-- Display welcome message with preselected info -->
                                            <?php if (!empty($preselected_subscription_id)): ?>
                                                <?php
                                                $preselectedSub = null;
                                                foreach ($subscriptionOptions as $sub) {
                                                    if ($sub['id'] == $preselected_subscription_id) {
                                                        $preselectedSub = $sub;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if ($preselectedSub): ?>
                                                    <div class="alert alert-info">
                                                        <i class="mdi mdi-information"></i> 
                                                        <strong>Welcome back!</strong> You have selected: <strong><?php echo htmlspecialchars($preselectedSub['name']); ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <form method="post" action="subscribe.php" enctype="multipart/form-data">
                                                <h5 class="mb-4 text-uppercase d-flex justify-content-between align-items-center">
                                                    <span><i class="mdi mdi-account-circle me-1"></i> Personal Info</span>
                                                    <a href="pages-profile.php" class="btn btn-outline-primary btn-sm" role="button">
                                                        <i class="mdi mdi-account-edit"></i> Update Profile
                                                    </a>
                                                </h5>
                                                
                                                <!-- Personal info fields -->
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="firstname" class="form-label">Name</label>
                                                            <input disabled type="text" class="form-control" id="firstname" name="name" value="<?php echo $firstName; ?>" placeholder="Enter name" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="birthdate" class="form-label">Birthdate</label>
                                                            <input disabled type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo $birthdate; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="userbio" class="form-label">Address</label>
                                                            <input disabled type="text" class="form-control" id="userbio" name="city" value="<?php echo $city; ?>" placeholder="City">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="useremail" class="form-label">Email Address</label>
                                                            <input disabled type="email" class="form-control" id="useremail" name="email" value="<?php echo $email; ?>" placeholder="Enter email" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="usernumber" class="form-label">Phone Number</label>
                                                            <input disabled type="text" class="form-control" id="usernumber" name="number" value="<?php echo $number; ?>" placeholder="Enter phone number">
                                                        </div>
                                                    </div>
                                                </div>

                                                <h5 class="mb-4 text-uppercase mt-4"><i class="mdi mdi-ticket me-1"></i> Subscription Selection</h5>
                                                <div class="mb-3">
                                                    <label for="subscription_id" class="form-label">Subscription</label>
                                                    <select class="form-select" id="subscription_id" name="subscription_id" required onchange="updatePriceBreakdown(); handleReservationType();"> 
                                                        <option value="">Select Subscription</option>
                                                        <optgroup label="Tickets" id="optgroup-tickets"></optgroup>
                                                        <optgroup label="Event Venues" id="optgroup-venues"></optgroup>
                                                        <optgroup label="Memberships" id="optgroup-memberships"></optgroup>
                                                    </select>
                                                </div>

                                                <script>
                                                // Organize subscriptions into optgroups by type
                                                (function() {
                                                    var options = [
                                                        <?php foreach ($subscriptionOptions as $sub): ?>
                                                        {
                                                            id: "<?php echo $sub['id']; ?>",
                                                            name: "<?php echo htmlspecialchars($sub['name'], ENT_QUOTES); ?>",
                                                            type: "<?php echo $sub['type']; ?>",
                                                            price: "<?php echo htmlspecialchars($sub['price'], ENT_QUOTES); ?>",
                                                            discount: "<?php echo htmlspecialchars($sub['discount'], ENT_QUOTES); ?>"
                                                        },
                                                        <?php endforeach; ?>
                                                    ];
                                                    var tickets = '';
                                                    var venues = '';
                                                    var memberships = '';
                                                    options.forEach(function(sub) {
                                                        var opt = '<option value="'+sub.id+'" data-type="'+sub.type+'" data-price="'+sub.price+'" data-discount="'+sub.discount+'">'+sub.name+'</option>';
                                                        if (sub.type == '0') tickets += opt;
                                                        else if (sub.type == '1') venues += opt;
                                                        else if (sub.type == '2') memberships += opt;
                                                    });
                                                    document.getElementById('optgroup-tickets').innerHTML = tickets;
                                                    document.getElementById('optgroup-venues').innerHTML = venues;
                                                    document.getElementById('optgroup-memberships').innerHTML = memberships;
                                                    
                                                    // Auto-select if preselected subscription ID exists
                                                    var preselectedId = "<?php echo $preselected_subscription_id; ?>";
                                                    if (preselectedId) {
                                                        setTimeout(function() {
                                                            document.getElementById('subscription_id').value = preselectedId;
                                                            // Trigger change events to update everything
                                                            var event = new Event('change', { bubbles: true });
                                                            document.getElementById('subscription_id').dispatchEvent(event);
                                                            updatePriceBreakdown();
                                                            handleReservationType();
                                                        }, 100);
                                                    }
                                                })();

                                                // Restrict reservation dates for tickets to 1 day only
                                                function handleReservationType() {
                                                    var subSel = document.getElementById('subscription_id');
                                                    var selected = subSel.options[subSel.selectedIndex];
                                                    var type = selected ? selected.getAttribute('data-type') : null;
                                                    var resStart = document.getElementById('reservation_date');
                                                    var resEnd = document.getElementById('end_date');
                                                    
                                                    if (type === '0') {
                                                        // Ticket: force end date = start date, disable end date input
                                                        resEnd.value = resStart.value;
                                                        resEnd.readOnly = true;
                                                        resEnd.setAttribute('disabled', 'disabled');
                                                        resStart.addEventListener('change', function() {
                                                            resEnd.value = resStart.value;
                                                        });
                                                    } else {
                                                        // Other types: enable end date input
                                                        resEnd.readOnly = false;
                                                        resEnd.removeAttribute('disabled');
                                                    }
                                                }

                                                document.addEventListener('DOMContentLoaded', function() {
                                                    handleReservationType();
                                                });
                                                </script>

                                                <div class="mb-3">
                                                    <label for="discount_id" class="form-label">Discount</label>
                                                    <select class="form-select" id="discount_id" name="discount_id" onchange="updatePriceBreakdown()">
                                                        <option value="">No Discount</option>
                                                        <?php foreach ($discountOptions as $d): ?>
                                                            <option value="<?php echo $d['id']; ?>" data-discount="<?php echo htmlspecialchars($d['discount']); ?>">
                                                                <?php echo htmlspecialchars($d['name']); ?>  
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <!-- Reservation Dates -->
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="reservation_date" class="form-label">Reservation Start Date & Time</label>
                                                            <input type="datetime-local" class="form-control" id="reservation_date" name="reservation_date" required onchange="updatePriceBreakdown(); handleReservationType();">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="end_date" class="form-label">Reservation End Date & Time</label>
                                                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required onchange="updatePriceBreakdown()">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3" id="type-info" style="display:none;" hidden>
                                                    <label class="form-label">Type</label>
                                                    <input type="text" class="form-control" id="subscription_type" name="subscription_type" value="" readonly>
                                                </div>

                                                <div class="mb-3" id="guest-names-section" style="display:none;">
                                                    <label class="form-label">Guest Names</label>
                                                    <div id="guest-names-list"></div>
                                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addGuestNameInput()">Add Guest</button>
                                                </div>

                                                <script>
                                                // Show/hide guest names section based on subscription type
                                                function handleGuestNamesVisibility() {
                                                    var subSel = document.getElementById('subscription_id');
                                                    var selected = subSel.options[subSel.selectedIndex];
                                                    var type = selected ? selected.getAttribute('data-type') : null;
                                                    var guestSection = document.getElementById('guest-names-section');
                                                    var list = document.getElementById('guest-names-list');

                                                    // Show for tickets and event venues only
                                                    if (type === '0' || type === '1') {
                                                        guestSection.style.display = '';
                                                        // Ensure at least one input exists and is required
                                                        if (list.children.length === 0) {
                                                            addGuestNameInput();
                                                        } else {
                                                            updateGuestRequired();
                                                        }
                                                    } else {
                                                        guestSection.style.display = 'none';
                                                        // Clear guest inputs when not applicable
                                                        list.innerHTML = '';
                                                    }
                                                }

                                                document.getElementById('subscription_id').addEventListener('change', handleGuestNamesVisibility);
                                                document.addEventListener('DOMContentLoaded', handleGuestNamesVisibility);

                                                // Add guest name input
                                                function addGuestNameInput(value = '') {
                                                    var list = document.getElementById('guest-names-list');
                                                    var idx = list.children.length;
                                                    var div = document.createElement('div');
                                                    div.className = 'input-group mb-2';
                                                    // Use a wrapper button that calls removeGuest(this) to also update required state
                                                    div.innerHTML = `
                                                        <input type="text" class="form-control guest-name-input" name="guest_names[]" placeholder="Guest Name" value="${value}">
                                                        <button type="button" class="btn btn-outline-danger" onclick="removeGuest(this)">Remove</button>
                                                    `;
                                                    list.appendChild(div);
                                                    updateGuestRequired();
                                                }

                                                function removeGuest(btn) {
                                                    var wrapper = btn.parentNode;
                                                    if (wrapper) wrapper.remove();
                                                    updateGuestRequired();
                                                }

                                                // Ensure at least one guest input is present and required when section visible
                                                function updateGuestRequired() {
                                                    var list = document.getElementById('guest-names-list');
                                                    var inputs = list.querySelectorAll('.guest-name-input');
                                                    // If section hidden, nothing to do
                                                    var guestSection = document.getElementById('guest-names-section');
                                                    if (!guestSection || guestSection.style.display === 'none') return;

                                                    // Make first input required, others optional
                                                    inputs.forEach(function(inp, i) {
                                                        if (i === 0) inp.setAttribute('required', 'required');
                                                        else inp.removeAttribute('required');
                                                    });

                                                    // If no inputs, add one
                                                    if (inputs.length === 0) addGuestNameInput();
                                                }

                                                // Form submit validation: require at least one non-empty guest when guest section visible
                                                (function() {
                                                    var form = document.querySelector('form[action="subscribe.php"]');
                                                    if (!form) return;
                                                    form.addEventListener('submit', function(e) {
                                                        var guestSection = document.getElementById('guest-names-section');
                                                        if (guestSection && guestSection.style.display !== 'none') {
                                                            var inputs = Array.from(document.querySelectorAll('#guest-names-list .guest-name-input'));
                                                            // Count non-empty entries
                                                            var nonEmpty = inputs.map(i => i.value.trim()).filter(v => v !== '');
                                                            if (nonEmpty.length === 0) {
                                                                e.preventDefault();
                                                                alert('Please add at least one guest name for this subscription.');
                                                                // focus first input or Add Guest button
                                                                var firstInput = inputs[0];
                                                                if (firstInput) firstInput.focus();
                                                                else document.querySelector('#guest-names-section button').focus();
                                                                return false;
                                                            }
                                                        }
                                                        return true;
                                                    });
                                                })();
                                                </script>

                                                <div class="mb-3" id="price-breakdown" style="display:none;">
                                                    <label class="form-label">Price Breakdown</label>
                                                    <div id="breakdown-table"></div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Transaction Reference:</label>
                                                    <input type="text" class="form-control" name="unique_id" value="<?php echo $unique_id; ?>" readonly maxlength="10">
                                                </div>

                                                <div class="mb-3" id="payment-method-section" style="display:none;">
                                                    <label class="form-label">Payment Method</label>
                                                    <div class="d-flex align-items-center">
                                                        <select class="form-select me-3" name="payment_method" id="payment_method">
                                                            <!-- Options will be loaded from payment_methods.json -->
                                                        </select>
                                                        <!-- Larger preview so QR is easy to scan by camera -->
                                                        <img id="payment_method_image" src="" alt="Payment method" style="height:220px; width:auto; display:none; border:1px solid #ddd; padding:6px; border-radius:6px; object-fit:contain;">
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3" id="proof-section" style="display:none;">
                                                    <label class="form-label">Upload Proof of Payment</label>
                                                    <input required type="file" class="form-control" name="proof" accept="image/*">
                                                    <a href="#" id="view-payment-methods" style="font-size:0.9em; margin-top:5px; display:inline-block;">View payment methods</a>
                                                </div>

                                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                                <script>
                                                // Load payment methods from JSON and populate select
                                                fetch('assets/payment_methods.json')
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        let select = document.getElementById('payment_method');
                                                        select.innerHTML = '';
                                                        data.forEach(function(method, idx) {
                                                            let opt = document.createElement('option');
                                                            opt.value = method.name;
                                                            opt.textContent = method.name;
                                                            if (method.image) opt.setAttribute('data-image', 'assets/images/' + method.image);
                                                            // keep first item selected by default
                                                            if (idx === 0) opt.selected = true;
                                                            select.appendChild(opt);
                                                        });
                                                        // ensure image updates on change and show initial image if available
                                                        function updatePaymentMethodImage() {
                                                            const img = document.getElementById('payment_method_image');
                                                            const opt = select.options[select.selectedIndex];
                                                            const src = opt ? opt.getAttribute('data-image') : null;
                                                            if (src) {
                                                                img.src = src;
                                                                img.style.display = '';
                                                            } else {
                                                                img.src = '';
                                                                img.style.display = 'none';
                                                            }
                                                        }
                                                        select.addEventListener('change', updatePaymentMethodImage);
                                                        updatePaymentMethodImage();
                                                    });

                                                document.getElementById('view-payment-methods').addEventListener('click', function(e) {
                                                    e.preventDefault();
                                                    fetch('assets/payment_methods.json')
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            // build a larger, camera-friendly gallery (big QR images)
                                                            let html = '<div style="text-align:center;">';
                                                            data.forEach(function(method) {
                                                                html += `<div style="margin-bottom:18px; display:flex; align-items:center; gap:12px; justify-content:center;">
                                                                            <img src="assets/images/${method.image}" alt="${method.name}" style="height:320px; max-width:100%; object-fit:contain; border:1px solid #ddd; padding:8px; border-radius:6px;">
                                                                            <div style="font-size:1.1em;">${method.name}</div>
                                                                        </div>`;
                                                            });
                                                            html += '</div>';
                                                            Swal.fire({
                                                                title: 'Payment Methods',
                                                                html: html,
                                                                showCloseButton: true,
                                                                showConfirmButton: false,
                                                                width: 720,
                                                                customClass: { popup: 'swal2-large-image' }
                                                            });
                                                        });
                                                });
                                                </script>

                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-success mt-2"><i class="mdi mdi-content-save"></i> Submit Booking</button>
                                                    <a href="events-list.php" class="btn btn-secondary mt-2 ms-2"><i class="mdi mdi-arrow-left"></i> Back to Events</a>
                                                </div>
                                            </form>

                                            <!-- Price calculation scripts -->
                                            <script>
                                            function parsePriceString(str) {
                                                let obj = {};
                                                str.split(',').forEach(function(pair) {
                                                    let [k, v] = pair.split(':');
                                                    obj[k.trim()] = parseFloat(v);
                                                });
                                                return obj;
                                            }

                                            function getReservationDays() {
                                                let start = document.getElementById('reservation_date').value;
                                                let end = document.getElementById('end_date').value;
                                                if (!start || !end) return 1;
                                                let startDate = new Date(start);
                                                let endDate = new Date(end);
                                                let diff = endDate - startDate;
                                                if (isNaN(diff) || diff <= 0) return 1;
                                                let msPerDay = 24 * 60 * 60 * 1000;
                                                let days = Math.ceil(diff / msPerDay);
                                                return days > 0 ? days : 1;
                                            }

                                            function updatePriceBreakdown() {
                                                const oldCounts = {};
                                                document.querySelectorAll('[id^="count_"]').forEach(input => {
                                                    oldCounts[input.id.replace('count_', '')] = parseInt(input.value || 0);
                                                });

                                                let subSel = document.getElementById('subscription_id');
                                                let discSel = document.getElementById('discount_id');
                                                let typeInfo = document.getElementById('type-info');
                                                let typeField = document.getElementById('subscription_type');
                                                let priceBreakdown = document.getElementById('price-breakdown');
                                                let breakdownTable = document.getElementById('breakdown-table');
                                                let paymentSection = document.getElementById('payment-method-section');
                                                let proofSection = document.getElementById('proof-section');

                                                let selected = subSel.options[subSel.selectedIndex];
                                                if (!selected || !selected.value) {
                                                    typeInfo.style.display = 'none';
                                                    priceBreakdown.style.display = 'none';
                                                    paymentSection.style.display = 'none';
                                                    proofSection.style.display = 'none';
                                                    breakdownTable.innerHTML = '';
                                                    return;
                                                }
                                                
                                                let type = selected.getAttribute('data-type');
                                                let priceStr = selected.getAttribute('data-price');
                                                let priceObj = parsePriceString(priceStr);

                                                // Discount
                                                let discountObj = {};
                                                if (discSel.selectedIndex > 0) {
                                                    let discOpt = discSel.options[discSel.selectedIndex];
                                                    let discStr = discOpt.getAttribute('data-discount');
                                                    if (discStr) discountObj = parsePriceString(discStr);
                                                }

                                                // Show type
                                                let typeText = '';
                                                if (type == '0') typeText = 'Ticket';
                                                else if (type == '1') typeText = 'Event Venue';
                                                else if (type == '2') typeText = 'Membership';
                                                
                                                typeField.value = type;
                                                typeField.setAttribute('data-type-name', typeText);
                                                typeInfo.style.display = '';
       paymentSection.style.display = '';
                                                    proofSection.style.display = '';

                                                // Show breakdown
                                                let categories = Object.keys(priceObj);
                                                let html = '<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Category</th><th>Count</th><th>Unit Price</th><th>Discount (%)</th><th>Subtotal</th></tr></thead><tbody>';
                                                let total = 0;

                                                categories.forEach(function(cat) {
                                                    let countId = 'count_' + cat;
                                                    let previousValue = oldCounts[cat] ?? 0;
                                                    let unit = priceObj[cat] || 0;
                                                    let disc = discountObj[cat] || 0;
                                                    let discounted = unit - (unit * (disc / 100));
                                                    discounted = Math.max(discounted, 0);
                                                    let days = getReservationDays();
                                                    let subtotal = previousValue * discounted * days;
                                                    subtotal = Math.round(subtotal * 100) / 100;
                                                    total += subtotal;

                                                    html += '<tr>';
                                                    html += '<td>' + cat + '</td>';
                                                    html += `<td>
                                                                <input type="number" min="0" value="${previousValue}" name="count[${cat}]" id="${countId}" class="form-control count-input" style="width:80px;display:inline;">
                                                                <input type="hidden" name="subtotal_${cat}" id="hidden_subtotal_${cat}" value="${subtotal.toFixed(2)}">
                                                                <input type="hidden" name="discount_${cat}" id="hidden_discount_${cat}" value="${disc}">
                                                            </td>`;
                                                    html += '<td>' + unit + '</td>';
                                                    html += '<td>' + disc + '</td>';
                                                    html += `<td id="subtotal_${cat}">${subtotal.toFixed(2)}</td>`;
                                                    html += '</tr>';
                                                });

                                                html += '</tbody></table></div>';
                                                html += '<div class="mb-2"><strong>Total Amount:</strong> <input type="text" class="form-control" id="total_amount" name="total_amount" value="'+total.toFixed(2)+'" readonly></div>';
                                                breakdownTable.innerHTML = html;
                                                priceBreakdown.style.display = '';

                                                // Re-attach event listeners
                                                document.querySelectorAll('.count-input').forEach(input => {
                                                    input.addEventListener('input', () => computeTotals(priceObj, discountObj));
                                                });

                                                document.getElementById('reservation_date').addEventListener('change', () => computeTotals(priceObj, discountObj));
                                                document.getElementById('end_date').addEventListener('change', () => computeTotals(priceObj, discountObj));

                                                computeTotals(priceObj, discountObj);
                                            }

                                            function computeTotals(priceObj, discountObj) {
                                                let total = 0;
                                                let days = getReservationDays();
                                                Object.keys(priceObj).forEach(function(cat) {
                                                    let count = parseInt(document.getElementById('count_'+cat)?.value || 0);
                                                    let unit = priceObj[cat] || 0;
                                                    let disc = discountObj[cat] || 0;
                                                    let discounted = unit - (unit * (disc / 100));
                                                    discounted = Math.max(discounted, 0);
                                                    let subtotal = count * discounted * days;
                                                    subtotal = Math.round(subtotal * 100) / 100;
                                                    document.getElementById('subtotal_'+cat).innerText = subtotal.toFixed(2);
                                                    
                                                    let hiddenSubtotal = document.getElementById('hidden_subtotal_'+cat);
                                                    if (hiddenSubtotal) hiddenSubtotal.value = subtotal.toFixed(2);
                                                    let hiddenDiscount = document.getElementById('hidden_discount_'+cat);
                                                    if (hiddenDiscount) hiddenDiscount.value = disc;
                                                    total += subtotal;
                                                });
                                                document.getElementById('total_amount').value = total.toFixed(2);
                                            }

                                            document.addEventListener('DOMContentLoaded', function() {
                                                updatePriceBreakdown();
                                            });
                                            </script>
                                             
                                        </div>
                                        <!-- end settings content-->
                                    </div> <!-- end tab-content -->
                                </div> <!-- end card body -->
                            </div> <!-- end card -->
                        </div> <!-- end col -->
                    </div>
                    <!-- end row-->

                </div>
                <!-- container -->
            </div>
            <!-- content -->
        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <?php require 'includes/right-sidebar.php'; ?>   

    <div class="rightbar-overlay"></div>
    <!-- /End-bar -->

    <!-- bundle -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
    // Disable Monday, Tuesday, Wednesday (days 1, 2, 3)
    function setDateRestrictions() {
        const reservationDate = document.getElementById('reservation_date');
        const endDate = document.getElementById('end_date');
        
        function validateDate(input) {
            const selectedDate = new Date(input.value);
            const dayOfWeek = selectedDate.getDay(); // 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday
            
            if (dayOfWeek >= 1 && dayOfWeek <= 3) { // Monday to Wednesday
                alert('Sorry, we are closed on Monday, Tuesday, and Wednesday. Please select another day.');
                input.value = '';
                return false;
            }
            return true;
        }
        
        // Add event listeners
        reservationDate.addEventListener('change', function() {
            if (validateDate(this)) {
                // If reservation date is valid and it's a ticket, update end date
                handleReservationType();
            }
        });
        
        endDate.addEventListener('change', function() {
            validateDate(this);
        });
        
        // Set minimum date to today
        const today = new Date();
        const todayString = today.toISOString().slice(0, 16);
        reservationDate.setAttribute('min', todayString);
        endDate.setAttribute('min', todayString);
    }

    // Initialize date restrictions when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setDateRestrictions();
    });
    </script>

</body>

</html>