<?php
require_once 'config/config.php';
require_once 'includes/database.php';
require_once 'includes/security.php';
require_once 'includes/mail.php';

include 'templates/header.php';

$pageTitle = 'Register';

$userType = 'consumer';

if (isset($_GET['type']) && in_array($_GET['type'], ['consumer', 'market'])) {
    $userType = $_GET['type'];
}

$email = '';
$password = '';
$confirmPassword = '';
$fullname = '';
$marketName = '';
$city = '';
$district = '';
$error = '';
$success = '';


$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $district = isset($_POST['district']) ? trim($_POST['district']) : '';
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    
    // user type specific fields
    if ($userType === 'market') {
        $marketName = isset($_POST['market_name']) ? trim($_POST['market_name']) : '';
    } else {
        $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    }
    
    // sanitize inputs
    $email = sanitizeInput($email);
    $city = sanitizeInput($city);
    $district = sanitizeInput($district);
    if ($userType === 'market') {
        $marketName = sanitizeInput($marketName);
    } else {
        $fullname = sanitizeInput($fullname);
    }
   
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    }
    // Validate user type 
    elseif (!in_array($userType, ['market', 'consumer'], true)) {
        $error = 'Invalid user type.';
    }

    elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    elseif (empty($password) || strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    }
    elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    }
    elseif (empty($city)) {
        $error = 'Please enter your city.';
    }
    elseif (empty($district)) {
        $error = 'Please enter your district.';
    }
    elseif ($userType === 'market' && empty($marketName)) {
        $error = 'Please enter your market name.';
    }
    elseif ($userType === 'consumer' && empty($fullname)) {
        $error = 'Please enter your full name.';
    }
    else {
        $conn = getConnection();
        
        // check if mail already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetchAll();
        
        if (count($result) > 0) {
            $error = 'Email address already in use. Please use a different email or login.';
        } else {
            $conn->beginTransaction();
            
            try {
                // Hash password
                $hashedPassword = hashPassword($password);
                
                $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, is_verified) VALUES (?, ?, ?, 0)");
                $stmt->execute([$email, $hashedPassword, $userType]);
                $userId = $conn->lastInsertId();
           
                if ($userType === 'market') {
                    $stmt = $conn->prepare("INSERT INTO market_profiles (user_id, market_name, city, district) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $marketName, $city, $district]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO consumer_profiles (user_id, fullname, city, district) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $fullname, $city, $district]);
                }
                
                // Generate verification code
                $verificationCode = generateVerificationCode();
                $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours
                
                // Store verification code
                $stmt = $conn->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $verificationCode, $expiresAt]);
                
                $conn->commit();

                $name = $userType === 'market' ? $marketName : $fullname;
                $mailResult = sendVerificationEmail($email, $name, $verificationCode);
                
                // store user ID in session for verification
                $_SESSION['unverified_user_id'] = $userId;
                
                // show error
                if (!$mailResult) {
                    $_SESSION['flash_message'] = 'Registration successful, but we could not send a verification email. Please request a new code on the verification page.';
                    $_SESSION['flash_type'] = 'warning';
                }
                
                // redirect to verification page
                if (!headers_sent()) {
                    header('Location: verify.php');
                    exit;
                } else {
                    
                    echo '<script>window.location.href = "verify.php";</script>';
                    echo '<noscript><meta http-equiv="refresh" content="0;url=verify.php"></noscript>';
                    echo 'If you are not redirected, please <a href="verify.php">click here</a>.';
                    exit;
                }
            } catch (Exception $e) {
                // rollback transaction on error
                $conn->rollback();
                $error = 'Registration failed. Please try again later. Error: ' . $e->getMessage();
            }
        }

        $conn = null;
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-container my-5">
                <h2 class="text-center mb-4">Create an Account</h2>
                
                <!-- User Type Selection Tabs -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $userType === 'consumer' ? 'active' : ''; ?>" href="register.php?type=consumer">Consumer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $userType === 'market' ? 'active' : ''; ?>" href="register.php?type=market">Market</a>
                    </li>
                </ul>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Registration Form -->
                <!-- Action URL includes the current userType to preserve state -->
                <form action="register.php?type=<?php echo htmlspecialchars($userType); ?>" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <!-- No hidden user_type field -->
                    
                    <!-- Email Field -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    
                    <!-- Password Fields -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                            <div class="invalid-feedback">Password must be at least 8 characters.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                    </div>
                    
                    <?php if ($userType === 'market'): ?>
                        <!-- Market Fields -->
                        <div class="mb-3">
                            <label for="market_name" class="form-label">Market Name</label>
                            <input type="text" class="form-control" id="market_name" name="market_name" value="<?php echo htmlspecialchars($marketName); ?>" required>
                            <div class="invalid-feedback">Please enter your market name.</div>
                        </div>
                    <?php else: ?>
                        <!-- Consumer Fields -->
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Location Fields -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                            <div class="invalid-feedback">Please enter your city.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="district" class="form-label">District</label>
                            <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($district); ?>" required>
                            <div class="invalid-feedback">Please enter your district.</div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-success">Register</button>
                    </div>
                    
                    <p class="text-center">Already have an account? <a href="login.php">Login</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
<script>

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const userTypeField = document.querySelector('input[name="type"]');
            if (userTypeField && !userTypeField.value) {
                e.preventDefault();
                alert('User type is empty. Current value: "' + userTypeField.value + '". Will be set to "<?php echo htmlspecialchars($userType); ?>"');
                userTypeField.value = '<?php echo htmlspecialchars($userType); ?>';
                form.submit();
            }
        });
    }
});
</script> 