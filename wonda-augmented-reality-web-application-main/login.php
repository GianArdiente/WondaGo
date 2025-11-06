<?php
session_start();
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'database/function.php'; 

// --- NEW: determine safe next URL (from GET/POST) and redirect if already logged in ---
$next = '';
if (!empty($_REQUEST['next'])) {
    $candidate = $_REQUEST['next'];
    // Prevent open redirect: only allow relative paths (no scheme or double-slash)
    if (strpos($candidate, '://') === false && strpos($candidate, '//') === false && strpos($candidate, "\0") === false) {
        $next = $candidate;
    }
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    // If user already logged in, go directly to next (if provided), otherwise default by type
    if ($next !== '') {
        header('Location: ' . $next);
        exit;
    } else {
        $u = $_SESSION['user'];
        if (isset($u['type']) && $u['type'] == 1) {
            header('Location: dashboard.php');
        } else {
            header('Location: calendar.php');
        }
        exit;
    }
}

if (isset($_POST['otp']) && isset($_POST['email'])) {
    // Store generated OTP and email in session
    $_SESSION['otp'] = $_POST['otp'];
    $_SESSION['email'] = $_POST['email']; // Store email in session

    // Send OTP email to the provided email address
    $message = "Your OTP is: " . $_SESSION['otp'];
    $response = sendOTPEmail($_SESSION['email'], $message); // Use the stored email

    echo 'OTP stored and email sent.';
    exit;
}

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

// Function to send OTP email using SMTP (with debug logged)
function sendOTPEmail($email, $message)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP Server Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; // Hostinger SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'support@tcuregistrarrequest.site'; // Your email
        $mail->Password = '#228JyiuS'; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;  // Use TLS port
        $mail->setFrom('support@tcuregistrarrequest.site', 'WondaGo');
        $mail->addAddress($email); // Recipient's email
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = $message;

        // Debug output to log file
        $mail->SMTPDebug = 2; // verbose for debugging
        $mail->Debugoutput = function($str, $level) {
            append_email_log("SMTP Debug [level {$level}]: {$str}");
        };

        // Allow self-signed for local debug environments (keep cautious in prod)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->send();
        append_email_log("Email sent to {$email} (subject: {$mail->Subject})");
        return true;
    } catch (Exception $e) {
        $err = 'Mailer Error: ' . $mail->ErrorInfo;
        append_email_log($err);
        return ['status' => 'error', 'message' => $err];
    }
}

// Check for OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    // Verify OTP from session
    if (isset($_SESSION['otp']) && $_POST['verify_otp'] == $_SESSION['otp']) {
        // OTP is verified, return success
        echo 'success';
    } else {
        echo 'fail';
    }
    exit;
}

// Handle password reset via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password']) && isset($_POST['reset'])) {
    $email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    $password = $_POST['password'];

    // Check if user exists
    $user = $db->select('users', '*', ['email' => $email]);
    if (isset($user['data']) && count($user['data']) > 0) {
        // Hash new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $update = $db->update('users', ['password' => $hashedPassword], ['email' => $email]);
        echo 'success';
    } else {
        echo 'emailnotexist';
    }
    exit();
}

// Handle first-time verification via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['first_time_verify'])) {
    if (isset($_SESSION['otp']) && $_POST['first_time_verify'] == $_SESSION['otp']) {
        // Update first_time to 1 using pending_user
        $email = isset($_SESSION['pending_user']['email']) ? $_SESSION['pending_user']['email'] : '';
        if ($email !== '') {
            $update = $db->update('users', ['first_time' => 1], ['email' => $email]);
            // Promote pending_user to real session user (remove sensitive fields)
            $_SESSION['user'] = $_SESSION['pending_user'];
            unset($_SESSION['pending_user']);
            // Ensure session user has first_time = 1
            $_SESSION['user']['first_time'] = 1;
            // clear OTP after successful verification
            unset($_SESSION['otp']);
            unset($_SESSION['email']);
            echo 'success';
        } else {
            echo 'fail';
        }
    } else {
        echo 'fail';
    }
    exit;
}

// Handle login POST
$firstTimeLogin = false;
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password']) && !isset($_POST['reset'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user is locked out
    $currentAttempts = $_SESSION['login_attempts'] ?? 0;
    $lockoutTime = $_SESSION['lockout_time'] ?? 0;
    
    if ($currentAttempts >= 5 && time() < $lockoutTime) {
        $remainingTime = ceil(($lockoutTime - time()) / 60);
        $loginError = "Too many failed attempts. Please wait {$remainingTime} minute(s) before trying again.";
    } else {
        // Reset attempts if lockout time has passed
        if (time() >= $lockoutTime) {
            $_SESSION['login_attempts'] = 0;
            unset($_SESSION['lockout_time']);
        }

        $user = $db->select('users', '*', ['email' => $email]);
        if (isset($user['data']) && count($user['data']) > 0) {
            $userdata = $user['data'][0];
            if (password_verify($password, $userdata['password'])) {
                // Do NOT set full session user if first_time == 0.
                if ($userdata['first_time'] == 0) {
                    // Store pending user info in session and trigger front-end verification
                    // Remove password hash before placing into session
                    unset($userdata['password']);
                    $_SESSION['pending_user'] = $userdata;
                    $firstTimeLogin = true;
                } else {
                    // Normal login flow: set authenticated session user
                    unset($userdata['password']);
                    $_SESSION['user'] = $userdata;
                    $_SESSION['login_attempts'] = 0;
                    unset($_SESSION['lockout_time']);

                    if ($userdata['type'] == 1) {
                        header('Location: dashboard.php');
                    } else {
                        header('Location: calendar.php');
                    }
                    exit();
                }
            } else {
                $_SESSION['login_attempts'] = ($currentAttempts + 1);
                $attemptsLeft = 5 - $_SESSION['login_attempts'];
                
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['lockout_time'] = time() + (5 * 60); // 5 minutes lockout
                    $loginError = "Too many failed attempts. Your account has been locked for 5 minutes.";
                } else {
                    $loginError = "Wrong password. You have {$attemptsLeft} attempt(s) remaining.";
                }
            }
        } else {
            $_SESSION['login_attempts'] = ($currentAttempts + 1);
            $attemptsLeft = 5 - $_SESSION['login_attempts'];
            
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time() + (5 * 60); // 5 minutes lockout
                $loginError = "Too many failed attempts. Please wait 5 minutes before trying again.";
            } else {
                $loginError = "Account doesn't exist. You have {$attemptsLeft} attempt(s) remaining.";
            }
        }
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
                    <div class="col-xxl-4 col-lg-5">
                        <div class="card">

                            <!-- Logo -->
                            <div class="card-header pt-4 pb-4 text-center bg-primary">
                                <a href="login.php">
                                    <span><img src="assets/images/logo.png" alt="" height="60"></span>
                                </a>
                            </div>

                            <div class="card-body p-4">
                                
                                <div class="text-center w-75 m-auto">
                                    <h4 class="text-dark-50 text-center pb-0 fw-bold">Sign In</h4>
                                    <p class="text-muted mb-4">Enter your email address and password to access admin panel.</p>
                                </div>
                                                          <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST"> 


                                    <div class="mb-3">
                                        <label for="emailaddress" class="form-label">Email address</label>
                                        <input class="form-control" type="email" id="emailaddress" name="email" required="" placeholder="Enter your email">
                                    </div>

                                    <div class="mb-3">
                                        <a hidden href="pages-recoverpw.php" class="text-muted float-end"><small>Forgot your password?</small></a>
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group input-group-merge">
                                            <input type="password" id="password" name="password" class="form-control" required="" placeholder="Enter your password">
                                            <div class="input-group-text" data-password="false">
                                                <span class="password-eye"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 d-flex justify-content-between align-items-center">
                                        <div class="form-check mb-0"> 
                                            <input type="checkbox" class="form-check-input" id="checkbox-signin" checked>
                                            <label class="form-check-label" for="checkbox-signin">Remember me</label>
                                        </div>
                                        <a href="javascript:void(0)" onclick="askForInput()" class="text-muted">
                                            <small>Forgot your password?</small>
                                        </a>
                                    </div>


                                    <div class="mb-3 mb-0 text-center ">
                                        <button class="btn btn-primary col col-12" type="submit"> Log In </button>
                                    </div>

                                </form>
                            </div> <!-- end card-body -->
                        </div>
                        <!-- end card -->

                        <div class="row mt-3">
                            <div class="col-12 text-center">
                                <p class="text-muted">Don't have an account? <a href="pages-register.php" class="text-muted ms-1"><b>Sign Up</b></a></p>
                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->

                    </div> <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end page -->

        <!-- <footer class="footer footer-alt">
            2018 - <script>document.write(new Date().getFullYear())</script> © WondaGo
        </footer> -->

        <!-- bundle -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>
        
    </body>


       <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // Function to generate a 4-digit OTP
            function generateOTP() {
                return Math.floor(1000 + Math.random() * 9000); // Generates a random 4-digit number
            }

            <?php if ($firstTimeLogin): ?>
            // Trigger first-time verification on page load
            document.addEventListener('DOMContentLoaded', function() {
                triggerFirstTimeVerification();
            });
            <?php endif; ?>

            <?php if (!empty($loginError)): ?>
            // Show login error on page load
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: '<?php echo addslashes($loginError); ?>',
                    confirmButtonText: 'Try Again'
                });
            });
            <?php endif; ?>

            function askForInput() {
                Swal.fire({
                    title: 'Enter Your Email Address',
                    input: 'email',
                    inputValue: '',
                    showCancelButton: false,
                    allowOutsideClick: true, // Prevent closing the modal by clicking outside
                    confirmButtonText: 'Next',
                    preConfirm: (email) => {
                        if (!email) {
                            Swal.showValidationMessage('Email address is required');
                            return false; // Stop the process
                        }
                        return email;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const email = result.value;
                        const otp = generateOTP();
                        storeOTPInSession(otp, email);
                    }
                });
            }

            // Function to store OTP in PHP session via AJAX
            function storeOTPInSession(otp, email) {
                $.ajax({
                    url: 'login.php', // PHP file to handle session storage
                    type: 'POST',
                    data: { otp: otp, email: email }, // Send both OTP and email
                    success: function () {
                        console.log('OTP stored in session.');
                        // Now ask the user to enter the OTP
                        askForOTP(); // Call to ask for OTP input
                    }
                });
            }

            // Function to ask for OTP input
            function askForOTP(invalidMessage = '') {
                Swal.fire({
                    title: 'Enter Your OTP',
                    input: 'text',
                    inputValue: '',
                    showCancelButton: false,
                    allowOutsideClick: false,
                    text: invalidMessage, // Show the error message if provided
                    confirmButtonText: 'Submit',
                    preConfirm: (inputValue) => {
                        if (!inputValue) {
                            Swal.showValidationMessage('OTP is required');
                        }
                        return inputValue;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        verifyOTP(result.value); // Call verifyOTP with the entered OTP
                    }
                });
            }

            // Function to verify OTP
            function verifyOTP(inputOTP) {
                $.ajax({
                    url: 'login.php', // PHP file to verify OTP
                    type: 'POST',
                    data: { verify_otp: inputOTP }, // Send entered OTP for verification
                    success: function (response) {
                        if (response.trim() === 'success') {
                            askForPassword();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid OTP',
                                text: 'Please try again.'
                            }).then(() => {
                                askForOTP('Invalid OTP. Please try again.'); // Re-ask after displaying the error
                            });
                        }
                    }
                });
            }

            function askForPassword() {
                Swal.fire({
                    title: 'Enter Your Password',
                    input: 'password',
                    inputValue: '',
                    showCancelButton: false,
                    allowOutsideClick: false,
                    confirmButtonText: 'Submit',
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password is required');
                        }
                        return password;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitPassword(result.value); // Call to submit the entered password
                    }
                });
            }
            function submitPassword(password) {
                $.ajax({
                    url: 'login.php', // PHP file to handle password submission
                    type: 'POST',
                    data: { password: password, reset: "true" }, // Send the entered password for processing
                    success: function (response) {
                        console.log(response); // Log the response to inspect it
                        if (response.trim() === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Password Change Success',
                            });
                        } else if (response.trim() === 'emailnotexist') {
                            Swal.fire({
                                icon: 'error',
                                title: "Account doesn't Exist",
                            });
                        }
                    }
                });
            }

            // Function to trigger first-time verification
            function triggerFirstTimeVerification() {
                Swal.fire({
                    title: 'Account Verification Required',
                    text: 'This is your first time logging in. Please verify your account with OTP sent to your email.',
                    icon: 'info',
                    showCancelButton: false,
                    allowOutsideClick: false,
                    confirmButtonText: 'Continue'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Generate and send OTP for first-time verification
                        const otp = generateOTP();
                        // Use pending_user email (we don't set full session user until OTP verified)
                        const userEmail = '<?php echo isset($_SESSION["pending_user"]["email"]) ? $_SESSION["pending_user"]["email"] : ""; ?>';
                        storeFirstTimeOTP(otp, userEmail);
                    }
                });
            }

            // Function to store OTP for first-time verification
            function storeFirstTimeOTP(otp, email) {
                // Client-side validation: ensure email exists before sending payload
                if (!email || email.trim() === '') {
                    console.error('No email available to send OTP (pending_user.email is empty).');
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot send OTP',
                        text: 'Email address not available. Contact support or try again.'
                    });
                    return;
                }

                $.ajax({
                    url: 'login.php',
                    type: 'POST',
                    data: { otp: otp, email: email },
                    success: function (data, textStatus, jqXHR) {
                        console.log('First-time OTP AJAX success. server response:', data);
                        askForFirstTimeOTP();
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error('First-time OTP AJAX error:', textStatus, errorThrown, jqXHR.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'OTP send failed',
                            text: 'Failed to send OTP. Check server logs (logs/email_debug.log) for SMTP debug.'
                        });
                    }
                });
            }

            // Function to ask for first-time OTP input
            function askForFirstTimeOTP(invalidMessage = '') {
                Swal.fire({
                    title: 'Enter Verification Code',
                    text: 'Please enter the OTP sent to your email address.',
                    input: 'text',
                    inputValue: '',
                    showCancelButton: false,
                    allowOutsideClick: false,
                    inputAttributes: {
                        placeholder: 'Enter 4-digit OTP'
                    },
                    confirmButtonText: 'Verify',
                    preConfirm: (inputValue) => {
                        if (!inputValue) {
                            Swal.showValidationMessage('OTP is required');
                        }
                        return inputValue;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        verifyFirstTimeOTP(result.value);
                    }
                });
            }

            // Function to verify first-time OTP
            function verifyFirstTimeOTP(inputOTP) {
                $.ajax({
                    url: 'login.php',
                    type: 'POST',
                    data: { first_time_verify: inputOTP },
                    success: function (response) {
                        if (response.trim() === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Account Verified!',
                                text: 'Your account has been successfully verified.',
                                showCancelButton: false,
                                allowOutsideClick: false,
                                confirmButtonText: 'Continue'
                            }).then(() => {
                                // Use pending_user type for redirect (session user will be promoted on server)
                                const pendingUserType = '<?php echo isset($_SESSION["pending_user"]["type"]) ? $_SESSION["pending_user"]["type"] : ""; ?>';
                                if (pendingUserType == '1') {
                                    window.location.href = 'dashboard.php';
                                } else {
                                    window.location.href = 'calendar.php';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid OTP',
                                text: 'Please try again.'
                            }).then(() => {
                                askForFirstTimeOTP('Invalid OTP. Please try again.');
                            });
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.error('verifyFirstTimeOTP AJAX error:', textStatus, errorThrown, jqXHR.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification failed',
                            text: 'Server error while verifying OTP. Check logs/email_debug.log.'
                        });
                    }
                });
            }


        </script>

</html>
