<?php
session_start();
include("config.php");

$rememberMeDuration = 60 * 60 * 24 * 30; // 30 days

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['ban_time'] = null;
}

$banRemaining = 0;
if ($_SESSION['ban_time'] && time() < $_SESSION['ban_time']) {
    $banRemaining = $_SESSION['ban_time'] - time();
    $message = "Too many failed attempts. Please try again after 1 minute.";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']);

    $sql = "SELECT * FROM users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['PasswordHash'])) {
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Role'] = strtoupper($user['Role']);
            $_SESSION['Name'] = $user['FirstName'];
            $_SESSION['Email'] = $user['Email'];

            if ($remember) {
              setcookie('remember_email', $user['Email'], time() + $rememberMeDuration, "/SADPROJ", "", false, true);
            } else {
                setcookie('remember_email', '', time() - 3600, "/SADPROJ");
            }

            switch ($_SESSION['Role']) {
                case "ADMIN":
                    header("Location: adminSide/admin_dashboard.php");
                    break;
                case "DRIVER":
                    header("Location: driver_dashboard.php");
                    break;
                case "PASSENGER":
                    header("Location: passenger_dashboard.php");
                    break;
                default:
                    header("Location: login.php?error=invalid_role");
                    break;
            }
            exit();
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['ban_time'] = time() + 60;
                $_SESSION['login_attempts'] = 0;
                $banRemaining = 60;
                $message = "Too many failed attempts. You are banned for 1 minute.";
            } else {
                $message = "Invalid password.";
            }
        }
    } else {
        $message = "No account found with that email.";
    }
}

$prefillEmail = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login & Sign Up</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');
* { box-sizing: border-box; }

body {
    background-color: #d9eab1;
    font-family: 'Inter', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
}
body::-webkit-scrollbar { display: none; width: 0; }

.login-wrapper {
    display: flex;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    width: 800px;
    max-width: 95%;
}

.login-left {
    flex: 1;
    background-color: #166943;
    color: #f6f7ec;
    padding: 40px 40px 40px 40px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding-top: 99px;
    border-top-left-radius: 16px;
    border-bottom-left-radius: 16px;
}

.login-left h1 { font-size: 28px; margin-bottom: 10px; }
.login-left p { font-size: 16px; margin-bottom: 30px; }
.login-left ul { list-style: none; padding: 0; }
.login-left li { margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 14px; }

.login-right {
    flex: 1;
    background-color: #f6f7ec;
    padding: 60px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.login-right h2 { font-size: 28px; margin-bottom: 10px; color: #166943; }
.login-right p { margin-bottom: 20px; color: #3c6463; }

form label { font-size: 0.875rem; font-weight: 600; color: #1c6641; display: block; margin: 7px 0 6px; }
input[type="text"], input[type="email"], input[type="password"] {
    width: 100%;
    padding: 10px 40px 10px 14px;
    border-radius: 8px;
    border: none;
    box-shadow: inset 2px 2px 5px #cbe3a4, inset -2px -2px 5px #f6f7ec;
    font-size: 1rem;
    background-color: #f6f7ec;
    color: #333;
}
.input-wrapper { position: relative; }
.toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 22px; height: 22px; opacity: 0.7; }

.options { display: flex; justify-content: space-between; align-items: center; margin-top: -10px; }
.checkbox-label { display: inline-flex; align-items: center; font-size: 0.875rem; color: #1c6641; user-select: none; }
.forgot { color: #3c6463; font-weight: 500; font-size: 0.875rem; text-decoration: none; margin-left: auto; cursor: pointer; }
.forgot:hover { text-decoration: underline; }

.action-btn {
    width: 100%;
    background-color: #0a0a0a;
    color: #f6f7ec;
    font-size: 1rem;
    font-weight: 700;
    border: none;
    border-radius: 8px;
    padding: 12px;
    margin: 24px 0 12px 0;
    cursor: pointer;
    box-shadow: 1px 2px 6px rgba(0,0,0,0.3);
}
.action-btn:hover { background-color: #222; }

.bottom-text { font-size: 0.85rem; text-align: center; color: #446a2b; padding-bottom: 16px; }
.bottom-text a { color: #166943; font-weight: 700; text-decoration: none; }
.bottom-text a:hover { text-decoration: underline; }

.form-error-centered {
    text-align: center;
    margin: 6px auto;
    padding: 10px 14px;
    color: #cc0000;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.95rem;
    max-width: 320px;
}

#ban-countdown { color: #cc0000; padding: 12px 16px; margin-top: 12px; border-radius: 6px; font-weight: 600; font-size: 0.95rem; text-align: center; }
#ban-countdown strong { font-weight: 700; color: #b30000; }

.feature-list { list-style: none; padding: 0; margin-top: 30px; }
.feature-icon { width: 32px; height: 32px; flex-shrink: 0; }

.logo-container { display: flex; justify-content: center; align-items: center; margin-bottom: 20px; }
.logo-container svg { width: 48px; height: 48px; color: #f6f7ec; }
</style>
</head>

<body>
<div class="login-wrapper">
    <div class="login-left">
        <div class="logo-container">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4 16v2a2 2 0 0 0 2 2v1a1 1 0 0 0 2 0v-1h8v1a1 1 0 0 0 2 0v-1a2 2 0 0 0 2-2v-2H4zm0-2h16V6a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v8zm2-6h12v2H6V8zm0 4h12v2H6v-2z"/>
            </svg>
        </div>
        <h1>NovaCore: <br>City Transportation</h1>
        <p>Your gateway to smart urban mobility</p>
        <ul class="feature-list">
            <li>
                <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png" alt="Routes Icon" class="feature-icon">
                <div><strong>Real-Time Routes</strong><br>Track buses and trains live</div>
            </li>
            <li>
                <img src="https://cdn-icons-png.flaticon.com/512/1041/1041916.png" alt="Pass Icon" class="feature-icon">
                <div><strong>Digital Passes</strong><br>Access with QR or NFC</div>
            </li>
            <li>
                <img src="https://cdn-icons-png.flaticon.com/512/1828/1828884.png" alt="Clock Icon" class="feature-icon">
                <div><strong>24/7 Service</strong><br>Plan trips anytime, anywhere</div>
            </li>
        </ul>
    </div>

    <div class="login-right">
        <h2>Welcome Back!</h2>
        <p>Please login to your account</p>

        <?php if (!empty($message)): ?>
            <div id="error-message" class="form-error-centered">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="login-email">Email</label>
            <input 
              type="email" 
              id="login-email" 
              name="email" 
              placeholder="Enter email" 
              required 
              value="<?php echo htmlspecialchars(!empty($prefillEmail) ? $prefillEmail : (isset($_POST['email']) ? $_POST['email'] : '')); ?>"
            >

            <label for="login-password">Password</label>
            <div class="input-wrapper">
                <input type="password" id="login-password" name="password" placeholder="Enter password" required minlength="6">
                <img src="https://cdn-icons-png.flaticon.com/512/159/159604.png" alt="Toggle Password" class="toggle-password" id="toggleLoginPassword" onclick="togglePasswordVisibility('login-password', 'toggleLoginPassword')">
            </div>

            <div id="ban-countdown"></div>

            <div class="options">
              <label class="checkbox-label">
                <input type="checkbox" name="remember_me" <?php echo !empty($prefillEmail) ? 'checked' : ''; ?>>
                Remember Me
              </label>
                <a href="forgotPassword/forgot_password.php" class="forgot">Forgot Password?</a>
            </div>

            <button type="submit" class="action-btn">Log In</button>
            <div class="bottom-text">
                Don't have an account? <a href="user_creation.php">Sign Up</a>
            </div>
        </form>
    </div>
</div>

<script>
const banRemaining = <?php echo $banRemaining; ?>;

function togglePasswordVisibility(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(toggleId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.src = 'https://cdn-icons-png.flaticon.com/512/2767/2767146.png';
        icon.alt = 'Hide Password';
    } else {
        input.type = 'password';
        icon.src = 'https://cdn-icons-png.flaticon.com/512/159/159604.png';
        icon.alt = 'Show Password';
    }
}

if (banRemaining > 0) {
    const countdownEl = document.getElementById('ban-countdown');
    const loginBtn = document.querySelector('.action-btn');
    let remaining = banRemaining;
    loginBtn.disabled = true;

    const updateCountdown = () => {
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        countdownEl.innerHTML = `<strong>Please wait ${minutes}:${seconds.toString().padStart(2,'0')}</strong> before trying again.`;
        remaining--;
        if (remaining < 0) {
            clearInterval(timer);
            loginBtn.disabled = false;
            countdownEl.textContent = '';
            location.reload();
        }
    };
    updateCountdown();
    const timer = setInterval(updateCountdown, 1000);
}

document.addEventListener('DOMContentLoaded', () => {
    const emailInput = document.getElementById('login-email');
    const passwordInput = document.getElementById('login-password');
    const errorMessage = document.getElementById('error-message');

    function clearError() {
        if (errorMessage) {
            errorMessage.textContent = '';
        }
    }

    emailInput.addEventListener('input', clearError);
    passwordInput.addEventListener('input', clearError);
});
</script>
</body>
</html>
