<?php
session_start();
require_once 'database/function.php'; 
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helper: append email debug to a file
function append_email_log($text) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/email_debug.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// Minimal OTP email sender (uses PHPMailer SMTP like login.php)
function sendRegistrationEmail($email, $message) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'support@tcuregistrarrequest.site';
        $mail->Password = '#228JyiuS';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('support@tcuregistrarrequest.site', 'WondaGo');
        $mail->addAddress($email);
        $mail->isHTML(false);
        $mail->Subject = 'Your WondaGo Verification Code';
        $mail->Body = $message;

        // debug to log
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            append_email_log("SMTP Debug [level {$level}]: {$str}");
        };
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->send();
        append_email_log("Registration OTP sent to {$email}");
        return true;
    } catch (Exception $e) {
        append_email_log('Registration mailer error: ' . $e->getMessage());
        return false;
    }
}

// If user already started registration and pending data present, show OTP form flow
$showOtpForm = false;
$errors = [];
$old = [];

// If verifying OTP after initial registration start
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_register_otp') {
    $inputOtp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    if (isset($_SESSION['registration_otp']) && $inputOtp !== '' && $inputOtp == $_SESSION['registration_otp']) {
        // finalize registration using pending_registration
        if (isset($_SESSION['pending_registration']) && is_array($_SESSION['pending_registration'])) {
            $pr = $_SESSION['pending_registration'];
            // Double-check uniqueness
            $existingUser = $db->select('users', '*', ['email' => $pr['email']]);
            if (isset($existingUser['data']) && count($existingUser['data']) > 0) {
                $errors[] = 'Email already exists.';
                $showOtpForm = false;
            } else {
                $result = $db->insert('users', [
                    'name' => $pr['name'],
                    'email' => $pr['email'],
                    'password' => $pr['password_hash'],
                    'type' => 0,
                    'number' => $pr['number'],
                    'address' => $pr['address'],
                    'gender' => $pr['gender'],
                    'id_card' => $pr['id_card'],
                    'first_time' => 1,
                    'birthday' => $pr['birthday'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                if ($result['status'] === 'success') {
                    // clear pending session and otp
                    unset($_SESSION['pending_registration']);
                    unset($_SESSION['registration_otp']);
                    unset($_SESSION['registration_email']);
                    // redirect to login with success
                    header('Location: index.php?registered=1');
                    exit;
                } else {
                    $errors[] = 'Registration failed: ' . ($result['message'] ?? 'Unknown error');
                    $showOtpForm = true;
                }
            }
        } else {
            $errors[] = 'No pending registration found. Please register again.';
        }
    } else {
        $errors[] = 'Invalid verification code. Please try again.';
        $showOtpForm = true;
    }
}

// Handle initial registration start (validate and send OTP)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_register') {
    // Trim and collect
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal = trim($_POST['postal'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // keep old for re-population
    $old = compact('name','email','street','city','state','postal','country','number','gender','birthday');

    // server-side validations
    if ($name === '') $errors['name'] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
    if ($country === '') $errors['country'] = 'Country is required.';
    // Only require province/state and city when country is Philippines
    if ($country === 'Philippines') {
        if ($state === '') $errors['state'] = 'Province/State is required.';
        if ($city === '') $errors['city'] = 'City is required.';
    }
    if ($street === '') $errors['street'] = 'Street address is required.';
    if ($postal === '') $errors['postal'] = 'Postal code is required.';
    if ($number === '') $errors['number'] = 'Phone number is required.';
    if ($gender === '') $errors['gender'] = 'Gender is required.';
    if ($birthday === '') $errors['birthday'] = 'Birthday is required.';
    if ($password === '' || $confirm_password === '') $errors['password'] = 'Password and confirmation are required.';
    if ($password !== $confirm_password) $errors['password_confirm'] = 'Password and confirmation do not match.';
    if (strlen($password) < 6) $errors['password_len'] = 'Password must be at least 6 characters.';

    // birthday -> age check >= 18
    if ($birthday !== '') {
        $dob = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$dob) {
            $errors['birthday'] = 'Invalid date.';
        } else {
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 18) $errors['birthday_age'] = 'You must be at least 18 years old to register.';
        }
    }

    // ID Card upload required
    $id_card_path = '';
    if (!isset($_FILES['id_card']) || $_FILES['id_card']['error'] !== UPLOAD_ERR_OK) {
        $errors['id_card'] = 'Please upload a valid ID card.';
    } else {
        $uploadDir = 'assets/images/users/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '_' . basename($_FILES['id_card']['name']);
        $targetFile = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES['id_card']['tmp_name'], $targetFile)) {
            $errors['id_card'] = 'Failed to upload ID card.';
        } else {
            $id_card_path = $targetFile;
        }
    }

    // Check email uniqueness early
    if (empty($errors)) {
        $existingUser = $db->select('users', '*', ['email' => $email]);
        if (isset($existingUser['data']) && count($existingUser['data']) > 0) {
            $errors['email_exists'] = 'Email already exists.';
        }
    }

    if (empty($errors)) {
        // Everything validated - create pending registration and send OTP
        // include state/city even if empty (server will accept if country != Philippines)
        $address = "{$street}" . ($city !== '' ? ", {$city}" : "") . ($state !== '' ? ", {$state}" : "") . ", {$postal}, {$country}";
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $pending = [
            'name' => $name,
            'email' => $email,
            'password_hash' => $password_hash,
            'number' => $number,
            'address' => $address,
            'gender' => $gender,
            'id_card' => $id_card_path,
            'birthday' => $birthday
        ];
        $_SESSION['pending_registration'] = $pending;

        // generate 4-digit OTP
        $otp = strval(rand(1000,9999));
        $_SESSION['registration_otp'] = $otp;
        $_SESSION['registration_email'] = $email;

        // send OTP (best-effort)
        $msg = "Your WondaGo verification code is: {$otp}";
        $sent = sendRegistrationEmail($email, $msg);
        if (!$sent) {
            $errors[] = 'Failed to send verification email. Check server email configuration.';
            // keep showOtpForm false so user can fix or retry
        } else {
            $showOtpForm = true;
        }
    } else {
        // if errors, keep form shown (normal flow)
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php require_once 'includes/head.php'; ?>
    <body class="loading authentication-bg" data-layout-config='{"darkMode":false}'>
        <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xxl-6 col-lg-7">
                        <div class="card">
                            <div class="card-header pt-4 pb-4 text-center bg-primary">
                                <a href="index.php">
                                    <span><img src="assets/images/logo.png" alt="" height="60"></span>
                                </a>
                            </div>

                            <div class="card-body p-4">

                                <div class="text-center w-75 m-auto">
                                    <h4 class="text-dark-50 text-center pb-0 fw-bold">Sign Up</h4>
                                    <p class="text-muted mb-4">Create your account. Verification via email (OTP) required.</p>
                                </div>

                                <?php if (!empty($errors) && !$showOtpForm): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                        <?php foreach ($errors as $k => $e): ?>
                                            <li><?php echo htmlspecialchars(is_string($e) ? $e : (is_array($e) ? implode(' ', $e) : $e)); ?></li>
                                        <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if ($showOtpForm): ?>
                                    <div class="mb-3">
                                        <p>An OTP was sent to <strong><?php echo htmlspecialchars($_SESSION['registration_email'] ?? $old['email'] ?? ''); ?></strong>. Enter it below to complete registration.</p>
                                    </div>
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                        <input type="hidden" name="action" value="verify_register_otp">
                                        <div class="mb-3">
                                            <label for="otp" class="form-label">Verification Code</label>
                                            <input class="form-control" type="text" id="otp" name="otp" required placeholder="Enter 4-digit code" maxlength="4" pattern="\d{4}">
                                        </div>
                                        <div class="mb-3 text-center">
                                            <button class="btn btn-primary col col-12" type="submit"> Verify & Create Account </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <form id="registerForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" novalidate>
                                        <input type="hidden" name="action" value="start_register">

                                        <div class="mb-3">
                                            <label for="fullname" class="form-label">Full Name</label>
                                            <input class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" type="text" id="fullname" name="name" placeholder="Enter your name" required value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>">
                                            <div class="invalid-feedback">Full name is required.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="emailaddress" class="form-label">Email address</label>
                                            <input class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" type="email" id="emailaddress" name="email" required placeholder="Enter your email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                                            <div class="invalid-feedback">Valid email is required.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="country" class="form-label">Country</label>
                                            <select class="form-select <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>" id="country" name="country" required>
                                                <option value="">Select Country</option>
                                                <option value="Philippines" <?php echo (isset($old['country']) && $old['country']=='Philippines') ? 'selected' : ''; ?>>Philippines</option>
                                                <option value="Other" <?php echo (isset($old['country']) && $old['country']=='Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                            <div class="invalid-feedback">Country is required.</div>
                                        </div>

                                        <div id="philippines-province-block" style="display: none;">
                                            <div class="mb-3">
                                                <label for="state" class="form-label">Province</label>
                                                <!-- changed from select to free-text input to allow typing provinces not in list -->
                                                <input class="form-control <?php echo isset($errors['state']) ? 'is-invalid' : ''; ?>" type="text" id="state" name="state" placeholder="Enter province or region" value="<?php echo htmlspecialchars($old['state'] ?? ''); ?>">
                                                <div class="invalid-feedback">Province is required when country is Philippines.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="city" class="form-label">City</label>
                                                <input class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>" type="text" id="city" name="city" placeholder="Enter your city" value="<?php echo htmlspecialchars($old['city'] ?? ''); ?>">
                                                <div class="invalid-feedback">City is required when country is Philippines.</div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="street" class="form-label">Street Address</label>
                                            <input class="form-control <?php echo isset($errors['street']) ? 'is-invalid' : ''; ?>" type="text" id="street" name="street" placeholder="e.g. 123 Rizal Ave" required value="<?php echo htmlspecialchars($old['street'] ?? ''); ?>">
                                            <div class="invalid-feedback">Street address is required.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="postal" class="form-label">Postal Code</label>
                                            <input class="form-control <?php echo isset($errors['postal']) ? 'is-invalid' : ''; ?>" type="text" id="postal" name="postal" placeholder="e.g. 1300" required value="<?php echo htmlspecialchars($old['postal'] ?? ''); ?>">
                                            <div class="invalid-feedback">Postal code is required.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select class="form-select <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" id="gender" name="gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo (isset($old['gender']) && $old['gender']=='Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo (isset($old['gender']) && $old['gender']=='Female') ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                            <div class="invalid-feedback">Gender is required.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="birthday" class="form-label">Birthday</label>
                                            <input class="form-control <?php echo isset($errors['birthday']) ? 'is-invalid' : ''; ?>" type="date" id="birthday" name="birthday" required value="<?php echo htmlspecialchars($old['birthday'] ?? ''); ?>">
                                            <div class="invalid-feedback">You must be 18 years or older to register.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="number" class="form-label">Phone Number</label>
                                            <input class="form-control <?php echo isset($errors['number']) ? 'is-invalid' : ''; ?>" required type="tel" id="number" name="number" placeholder="e.g. 639XXXXXXXXX" value="<?php echo htmlspecialchars($old['number'] ?? ''); ?>" pattern="\d+">
                                            <div class="invalid-feedback">Phone number is required.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="id_card" class="form-label">Upload Valid ID</label>
                                            <input class="form-control <?php echo isset($errors['id_card']) ? 'is-invalid' : ''; ?>" type="file" id="id_card" name="id_card" accept="image/*,.pdf" required>
                                            <div class="invalid-feedback">Valid ID upload is required.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <div class="input-group input-group-merge">
                                                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                                                <div class="input-group-text" data-password="false">
                                                    <span class="password-eye"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                                        </div>

                                        <div class="mb-3 text-center">
                                            <button class="btn btn-primary col col-12" type="submit"> Sign Up </button>
                                        </div>

                                    </form>
                                <?php endif; ?>

                            </div> <!-- end card-body -->
                        </div>
                        <!-- end card -->

                        <div class="row mt-3">
                            <div class="col-12 text-center">
                                <p class="text-muted">Already have account? <a href="index.php" class="text-muted ms-1"><b>Log In</b></a></p>
                            </div> <!-- end col-->
                        </div>
                        <!-- end row -->

                    </div> <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end page -->
 

        <!-- bundle -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>
        <script>
            // Client-side validation + dynamic address/province logic
            (function(){
                const countryEl = document.getElementById('country');
                const provBlock = document.getElementById('philippines-province-block');
                const stateInput = document.getElementById('state');
                const cityInput = document.getElementById('city');

                function toggleProvince() {
                    if (!countryEl) return;
                    if (countryEl.value === 'Philippines') {
                        provBlock.style.display = 'block';
                        if (stateInput) stateInput.required = true;
                        if (cityInput) cityInput.required = true;
                    } else {
                        provBlock.style.display = 'none';
                        if (stateInput) {
                            stateInput.required = false;
                            stateInput.classList.remove('is-invalid');
                        }
                        if (cityInput) {
                            cityInput.required = false;
                            cityInput.classList.remove('is-invalid');
                        }
                    }
                }
                if (countryEl) {
                    countryEl.addEventListener('change', toggleProvince);
                    toggleProvince();
                }

                // Form validation before submit
                const form = document.getElementById('registerForm');
                if (form) {
                    form.addEventListener('submit', function(e){
                        // Simple HTML5 validity check
                        if (!form.checkValidity()) {
                            e.preventDefault();
                            e.stopPropagation();
                            // add bootstrap validation classes
                            Array.from(form.elements).forEach(function(el){
                                if (el.checkValidity && !el.checkValidity()) {
                                    el.classList.add('is-invalid');
                                } else {
                                    el.classList.remove('is-invalid');
                                }
                            });
                        }

                        // password confirmation check
                        const pass = document.getElementById('password');
                        const conf = document.getElementById('confirm_password');
                        if (pass && conf && pass.value !== conf.value) {
                            e.preventDefault();
                            e.stopPropagation();
                            conf.classList.add('is-invalid');
                            conf.nextElementSibling && (conf.nextElementSibling.textContent = 'Passwords do not match.');
                            return false;
                        }

                        // birthday age check (>=18)
                        const b = document.getElementById('birthday');
                        if (b && b.value) {
                            const dob = new Date(b.value);
                            const today = new Date();
                            let age = today.getFullYear() - dob.getFullYear();
                            const m = today.getMonth() - dob.getMonth();
                            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
                            if (age < 18) {
                                e.preventDefault();
                                e.stopPropagation();
                                b.classList.add('is-invalid');
                                return false;
                            }
                        }

                        // ensure phone number present
                        const number = document.getElementById('number');
                        if (number && number.value.trim() === '') {
                            e.preventDefault();
                            e.stopPropagation();
                            number.classList.add('is-invalid');
                            return false;
                        }

                        // If Philippines, ensure state and city exist
                        if (countryEl && countryEl.value === 'Philippines') {
                            if (stateInput && stateInput.value.trim() === '') {
                                e.preventDefault();
                                e.stopPropagation();
                                stateInput.classList.add('is-invalid');
                                return false;
                            }
                            if (cityInput && cityInput.value.trim() === '') {
                                e.preventDefault();
                                e.stopPropagation();
                                cityInput.classList.add('is-invalid');
                                return false;
                            }
                        }

                        return true;
                    }, false);
                }
            })();
        </script>
        
    </body>
</html>
