<?php
session_start();
include("config.php");

// Add session tracking (no class or DB changes)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['ban_time'] = null;
}

// Calculate remaining ban time
$banRemaining = 0;
if ($_SESSION['ban_time'] && time() < $_SESSION['ban_time']) {
    $banRemaining = $_SESSION['ban_time'] - time(); // seconds remaining
    $message = "Too many failed attempts. Please try again after 1 minute.";
}

// Remember me cookie duration (30 days)
$rememberMeDuration = 30 * 24 * 60 * 60;

// Handle POST login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']); // checkbox from the form

    // Fetch user
    $sql = "SELECT * FROM users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['PasswordHash'])) {
            // Reset login attempts on successful login
            $_SESSION['login_attempts'] = 0;
            $_SESSION['ban_time'] = null;

            // Store session details
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Role'] = strtoupper($user['Role']);
            $_SESSION['Name'] = $user['FirstName'];
            $_SESSION['Email'] = $user['Email'];

            // Set or remove the "remember me" cookie
            if ($remember) {
                setcookie('remember_email', $user['Email'], time() + $rememberMeDuration, "/SADPROJ", "", false, true);
            } else {
                setcookie('remember_email', '', time() - 3600, "/SADPROJ");
            }

            // Redirect by role
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
                $_SESSION['ban_time'] = time() + (1 * 60); // 1 minute ban
                $_SESSION['login_attempts'] = 0;
                $banRemaining = 1 * 60;
                $message = "Too many failed attempts. You are banned for 1 minute.";
            } else {
                $message = "Invalid password.";
            }
        }
    } else {
        $message = "No account found with that email.";
    }
}

// Pre-fill email if "remember me" cookie exists
$prefillEmail = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login & Sign Up Form</title>
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

  .container {
    width: 360px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
    background-color: #f6f7ec;
    display: none;
  }

  .container.active { display: block; }

  .top-header {
    position: relative;
    width: 100%;
    height: 130px;
    background-color: #166943;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 75px;
  }

  .top-header h2 {
    color: #f6f7ec;
    font-size: 1.7rem;
    font-weight: 700;
    margin-top: 80px;
    text-align: center;
  }

  .back-arrow {
    position: absolute;
    top: 16px;
    left: 16px;
    cursor: pointer;
  }
  .back-arrow svg {
    width: 24px;
    height: 24px;
    fill: #f6f7ec;
  }

  form { 
    padding: 30px 30px 25px 30px;
  }

  form label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1c6641;
    display: block;
    margin-bottom: 8px;
    margin-top: 0;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .input-wrapper {
    position: relative;
  }

  input[type="text"], input[type="email"], input[type="password"] {
    width: 100%;
    padding: 12px 40px 12px 14px; 
    border-radius: 8px;
    border: none;
    box-shadow: inset 2px 2px 5px #cbe3a4, inset -2px -2px 5px #f6f7ec;
    font-size: 1rem;
    background-color: #f6f7ec;
    color: #333;
  }

  .toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    width: 22px;
    height: 22px;
    opacity: 0.7;
  }

  .remember-forgot-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 16px 0 20px 0;
  }

  .remember-forgot-row a{
    margin-top: -10px;
  }

  .checkbox-label {
    display: inline-flex;
    align-items: center;
    color: #1c6641;
    font-size: 0.875rem;
    user-select: none;
    cursor: pointer;
    gap: 6px;
  }

  .checkbox-label input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
  }

  .forgot {
    color: #3c6463;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    text-decoration: none;
    user-select: none;
  }
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
    margin: 0 0 20px 0;
    cursor: pointer;
    box-shadow: 1px 2px 6px rgba(0,0,0,0.3);
    transition: background-color 0.2s;
  }
  .action-btn:hover { background-color: #222; }
  .action-btn:disabled {
    background-color: #666;
    cursor: not-allowed;
    opacity: 0.6;
  }

  .social-text { 
    font-size: 0.85rem; 
    text-align: center; 
    color: #556b39; 
    margin-bottom: 16px; 
  }
  
  .social-icons { 
    display: flex; 
    justify-content: center; 
    gap: 20px; 
    margin-bottom: 20px; 
  }
  
  .social-icons img { 
    width: 36px; 
    height: 36px; 
    cursor: pointer; 
  }

  .bottom-text { 
    font-size: 0.85rem; 
    text-align: center; 
    color: #446a2b; 
    padding-bottom: 0;
    margin-bottom: 0;
  }
  
  .bottom-text a { 
    color: #4a6274; 
    font-weight: 700; 
    text-decoration: none; 
    cursor: pointer; 
  }
  
  .bottom-text a:hover { 
    text-decoration: underline; 
  }

  .form-error-centered {
    text-align: center;
    margin: 0 0 20px 0;
    padding: 10px 14px;
    color: #cc0000;
    background-color: #ffe6e6;
    border: 1px solid #ffcccc;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .ban-message {
    background-color: #ffe6e6;
    color: #cc0000;
    border: 1px solid #cc0000;
    padding: 10px 15px;
    margin: 0 0 20px 0;
    border-radius: 5px;
    font-weight: bold;
    text-align: center;
    animation: fadeIn 0.5s ease-in-out;
  }

  #ban-countdown {
    color: #cc0000;
    padding: 12px 16px;
    margin: 0 0 20px 0;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: center;
    background-color: #fff5f5;
    border: 1px solid #ffcccc;
  }

  #ban-countdown strong {
    font-weight: 700;
    color: #b30000;
  }

  .fade-out {
    opacity: 0;
    transition: opacity 0.4s ease-out;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
</style>
</head>

<body>
  <div class="container login active">
    <div class="top-header">
      <h2>Login</h2>
    </div>
    
    <form method="POST" action="">
      <?php if (!empty($message)): ?>
        <div id="error-message" class="form-error-centered">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <div id="ban-countdown" style="display: none;"></div>

      <div class="form-group">
        <label for="login-email">Email</label>
        <input 
          type="email" 
          id="login-email" 
          name="email"
          placeholder="Enter email"
          required
          value="<?php echo htmlspecialchars($prefillEmail); ?>" 
        />
      </div>

      <div class="form-group">
        <label for="login-password">Password</label>
        <div class="input-wrapper">
          <input 
            type="password" 
            id="login-password" 
            name="password" 
            placeholder="Enter password" 
            required 
            minlength="6" 
          />
          <img 
            src="https://cdn-icons-png.flaticon.com/512/159/159604.png" 
            alt="Toggle Password" 
            class="toggle-password" 
            id="toggleLoginPassword"
            onclick="togglePasswordVisibility('login-password', 'toggleLoginPassword')" 
          />
        </div>
      </div>

      <div class="remember-forgot-row">
        <label class="checkbox-label">
          <input type="checkbox" name="remember_me" <?php echo !empty($prefillEmail) ? 'checked' : ''; ?>>
          Remember Me
        </label>
        <a href="forgot_password.php" class="forgot">Forgot Password?</a>
      </div>

      <button type="submit" class="action-btn" id="login-btn">Log In</button>

      <div class="bottom-text">
        Don't have an account? <a id="show-signup" href="user_creation.php">Sign Up</a>
      </div>
    </form>
  </div>

  <script>
    // Inject PHP banRemaining value
    const banRemaining = <?php echo $banRemaining; ?>;

    // Toggle password visibility
    function togglePasswordVisibility(inputId, toggleId) {
      const passwordInput = document.getElementById(inputId);
      const toggleIcon = document.getElementById(toggleId);

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.src = 'https://cdn-icons-png.flaticon.com/512/2767/2767146.png';
        toggleIcon.alt = 'Hide Password';
      } else {
        passwordInput.type = 'password';
        toggleIcon.src = 'https://cdn-icons-png.flaticon.com/512/159/159604.png';
        toggleIcon.alt = 'Show Password';
      }
    }

    // Real-time countdown logic
    if (banRemaining > 0) {
      const countdownEl = document.getElementById('ban-countdown');
      const loginBtn = document.getElementById('login-btn');
      let remaining = banRemaining;

      countdownEl.style.display = 'block';
      loginBtn.disabled = true;

      const updateCountdown = () => {
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        countdownEl.innerHTML = `<strong>Please wait ${minutes}:${seconds.toString().padStart(2, '0')}</strong> before trying again.`;
        remaining--;

        if (remaining < 0) {
          countdownEl.classList.add('fade-out');
          loginBtn.disabled = false;
          clearInterval(timer);
          setTimeout(() => {
            countdownEl.textContent = '';
            countdownEl.style.display = 'none';
            countdownEl.classList.remove('fade-out');
            location.reload(); // optional: refresh page when ban ends
          }, 500);
        }
      };

      updateCountdown();
      const timer = setInterval(updateCountdown, 1000);
    }

    // Clear error message on input
    document.addEventListener('DOMContentLoaded', () => {
      const emailInput = document.getElementById('login-email');
      const passwordInput = document.getElementById('login-password');
      const errorMessage = document.getElementById('error-message');

      function clearError() {
        if (errorMessage) {
          errorMessage.classList.add('fade-out');
          setTimeout(() => {
            errorMessage.style.display = 'none';
            errorMessage.classList.remove('fade-out');
          }, 400);
        }
      }

      if (emailInput) emailInput.addEventListener('input', clearError);
      if (passwordInput) passwordInput.addEventListener('input', clearError);
    });
  </script>
</body>
</html>