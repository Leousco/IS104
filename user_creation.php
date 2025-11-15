<?php
session_start();
include 'config.php';
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

// Clear old verification session if this is a fresh GET request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  unset($_SESSION['generated_code']);
  unset($_SESSION['reg_firstname'], $_SESSION['reg_lastname'], $_SESSION['reg_email'], $_SESSION['reg_password'], $_SESSION['reg_password_plain'], $_SESSION['reg_role'], $_SESSION['reg_phone']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
  // Verification phase
  if (isset($_POST['verification_code']) && isset($_SESSION['generated_code'])) {
      $user_code = $_POST['verification_code'];

      if ($user_code === $_SESSION['generated_code']) {
          
          $stmt = $conn->prepare("
              INSERT INTO users (FirstName, LastName, Email, PasswordHash, Role, is_verified)
              VALUES (?, ?, ?, ?, ?, ?)
          ");
          $verified = "Yes";
          $stmt->bind_param(
              "ssssss",
              $_SESSION['reg_firstname'],
              $_SESSION['reg_lastname'],
              $_SESSION['reg_email'],
              $_SESSION['reg_password'],
              $_SESSION['reg_role'],
              $verified
          );

          if ($stmt->execute()) {
            $message = "✅ Account verified and registered successfully! Redirecting to login...";
            session_unset();
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const popup = document.getElementById('popup-message');
                    popup.textContent = " . json_encode($message) . ";
                    popup.classList.add('success');
                    popup.style.display = 'block';
                    setTimeout(() => { 
                        window.location.href = 'login.php'; 
                    }, 3000); // redirect after 3 seconds
                });
            </script>";
        }                 
          else {
              $message = "❌ Error: " . $stmt->error;
          }
      } else {
          $message = "❌ Invalid verification code. Please check your email.";
      }
  } else {
      
      $firstname = $_POST['firstname'];
      $lastname  = $_POST['lastname'];
      $email     = $_POST['email'];
      $password  = $_POST['password'];
      $role      = "PASSENGER";

      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      // Store info temporarily in session
      $_SESSION['reg_firstname']      = $firstname;
      $_SESSION['reg_lastname']       = $lastname;
      $_SESSION['reg_email']          = $email;
      $_SESSION['reg_password']       = $hashed_password;
      $_SESSION['reg_password_plain'] = $password; 
      $_SESSION['reg_role']           = $role;
      $_SESSION['reg_phone']          = $_POST['phone'];

      // Generate 6-digit code
      $verification_code = strval(rand(100000, 999999));
      $_SESSION['generated_code'] = $verification_code;

      // Send email
      $mail = new PHPMailer(true);
      try {
          $mail->isSMTP();
          $mail->Host = 'smtp.gmail.com';
          $mail->SMTPAuth = true;
          $mail->Username = 'novacore.mailer@gmail.com';
          $mail->Password = 'yjwc zsaa jltv vekq';
          $mail->SMTPSecure = 'tls';
          $mail->Port = 587;

          $mail->setFrom('novacore.mailer@gmail.com', 'NovaCore Team');
          $mail->addAddress($email);
          $mail->isHTML(true);
          $mail->Subject = 'Verify Your Email';
          $mail->Body = "<h2>Welcome, $firstname!</h2>
                         <p>Enter this verification code to complete registration:</p>
                         <h3>$verification_code</h3>";

          $mail->send();
      } catch (Exception $e) {
          $mail->ErrorInfo;
      }
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Passenger Registration</title>
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
  body::-webkit-scrollbar { display: none; }

  .container {
    width: 360px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    overflow: hidden;
    background-color: #f6f7ec;
  }

  .top-header {
    width: 100%;
    height: 90px;
    background-color: #166943;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 75px;
    position: relative;
  }

  .top-header h2 {
    color: #f6f7ec;
    font-size: 1.7rem;
    font-weight: 700;
    margin-top: 40px;
    text-align: center;
  }

  .back-arrow { position: absolute; top: 16px; left: 16px; cursor: pointer; }
  .back-arrow svg { width: 24px; height: 24px; fill: #f6f7ec; }

  form { padding: 30px; }

  form label {
    font-size: 0.875rem;
    font-weight: bold;
    color: #006400;
    display: block;
    margin-top: 15px;
    margin-bottom: 5px;
  }

  .input-wrapper { position: relative; margin-bottom: 10px; }
  input[type="text"], input[type="email"], input[type="password"] {
    width: 100%;
    padding: 10px 40px 10px 12px;
    box-sizing: border-box;
    border-radius: 8px;
    border: 1px solid #38761D;
    background-color: #F8FFF0;
  }

  .toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    width: 20px;
    height: 20px;
    opacity: 0.7;
  }

  .toggle-password:hover { opacity: 1; }

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
  }
  .action-btn:hover { background-color: #222; }

  #combined-password-feedback { margin-top: 20px; text-align: center; }
  #password-match-text, #password-strength-text { font-size: 85%; font-weight: bold; }

  .match-status-nomatch { color: #CC0000 !important; }
  .match-status-match { color: #006400 !important; }
  .match-status-warning { color: #FFA500 !important; }
  .status-weak { color: #CC0000 !important; }
  .status-moderate { color: #FFA500 !important; }
  .status-strong { color: #006400 !important; }
  #strength-progress-container { height: 8px; width: 100%; background-color: #e0e0e0; border-radius: 4px; overflow: hidden; margin: 10px 0; }
  #strength-progress-bar { height: 100%; width: 0%; background-color: #2196F3; transition: width 0.3s ease, background-color 0.3s ease; border-radius: 4px; }

  .popup-message {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #FFD700;
    color: #000000;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    font-weight: bold;
    font-size: 0.95rem;
    z-index: 1000;
    text-align: center;
  }
  .popup-message.success { background-color: #FFD700; color: #000000; } 
  .popup-message.error { background-color: #FF4500; color: #ffffff; }

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

</style>
</head>
<body>
<div class="container">
<div id="popup-message" class="popup-message" style="display:none;"></div>
  <div class="top-header">
    <div class="back-arrow" id="back-to-login" title="Go Back">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
        <path d="M14 19l-7-7 7-7" stroke="#f6f7ec" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h2>Passenger Sign Up</h2>
  </div>


  <?php if (isset($_SESSION['generated_code'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const popup = document.getElementById('popup-message');
        popup.textContent = '📧 Verification code sent to <?php echo addslashes($_SESSION["reg_email"]); ?>';
        popup.classList.add('success');
        popup.style.display = 'block';
        setTimeout(() => { popup.style.display = 'none'; }, 5000);
    });
    </script>
  <?php endif; ?>


    <form method="POST" action="" onsubmit="return validateFormSubmission();">
      <label for="first-name">First Name</label>
      <input type="text" id="first-name" name="firstname" placeholder="John" minlength="2" required 
            value="<?php echo (isset($_SESSION['reg_firstname']) && isset($_POST['firstname'])) ? htmlspecialchars($_SESSION['reg_firstname']) : ''; ?>"
            oninput="autoCapitalize(this)" />

      <label for="last-name">Last Name</label>
      <input type="text" id="last-name" name="lastname" placeholder="Doe" minlength="2" required
            value="<?php echo (isset($_SESSION['reg_lastname']) && isset($_POST['lastname'])) ? htmlspecialchars($_SESSION['reg_lastname']) : ''; ?>"
            oninput="autoCapitalize(this)" />

      <label for="phone">Phone Number</label>
      <div class="input-wrapper">
        <input type="text" id="phone" name="phone" placeholder="09*********" pattern="^\d{11}$" title="Phone number must be exactly 11 digits"
              value="<?php echo (isset($_SESSION['reg_phone']) && isset($_POST['phone'])) ? htmlspecialchars($_SESSION['reg_phone']) : ''; ?>"
              oninput="updateAsterisks(this)" />
        <span id="custom-placeholder"></span>
      </div>

      <label for="email-signup">Email</label>
      <input type="email" id="email-signup" name="email" placeholder="User_Name!1@gmail.com" required
            value="<?php echo (isset($_SESSION['reg_email']) && isset($_POST['email'])) ? htmlspecialchars($_SESSION['reg_email']) : ''; ?>"
            oninput="validateStrictEmail(this)" />

      <label for="password-signup">Password</label>
      <div class="input-wrapper">
        <input type="password" id="password-signup" name="password" placeholder="Enter a password" required minlength="6"
              value="<?php echo (isset($_SESSION['reg_password_plain']) && isset($_POST['password'])) ? htmlspecialchars($_SESSION['reg_password_plain']) : ''; ?>"
              oninput="handleCombinedFeedback()" />
        <img src="https://cdn-icons-png.flaticon.com/512/159/159604.png" alt="Show Password" class="toggle-password"
            id="toggleSignupPassword" onclick="togglePasswordVisibility('password-signup','toggleSignupPassword')"/>
      </div>

      <label for="confirm-password">Confirm Password</label>
      <div class="input-wrapper">
        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm your password" required minlength="6"
              value="<?php echo (isset($_SESSION['reg_password_plain']) && isset($_POST['password'])) ? htmlspecialchars($_SESSION['reg_password_plain']) : ''; ?>"
              oninput="handleCombinedFeedback()" />
        <img src="https://cdn-icons-png.flaticon.com/512/159/159604.png" alt="Show Password" class="toggle-password"
            id="toggleConfirmPassword" onclick="togglePasswordVisibility('confirm-password','toggleConfirmPassword')" />
      </div>

      <div id="combined-password-feedback">
        <div id="password-match-text">Password Matching</div>
        <div id="strength-progress-container"><div id="strength-progress-bar"></div></div>
        <div id="password-strength-text">Password Strength</div>
      </div>


      <?php if (isset($_SESSION['generated_code'])): ?>
          <label for="verification_code">Verification Code</label>
          <input type="text" id="verification_code" name="verification_code" placeholder="Enter code from email" required>
      <?php endif; ?>


      <button type="submit" class="action-btn">
        <?php echo isset($_SESSION['generated_code']) ? 'Verify' : 'Register'; ?>
      </button>

      <div class="bottom-text">
        Already have an account? <a href="login.php">Sign In</a>
      </div>
  </form>
</div>

<script>
  function autoCapitalize(inputField) {
    let value = inputField.value;
    if (value.length > 0) {
      const formattedValue = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
      if (inputField.value !== formattedValue) { inputField.value = formattedValue; }
    }
    inputField.setCustomValidity(value.length < 2 ? 'Name must be at least 2 characters.' : '');
  }

  function validateStrictEmail(inputField) {
    const emailValue = inputField.value.trim();
    if (!emailValue.endsWith('@gmail.com')) { inputField.setCustomValidity('Email address must end with @gmail.com.'); return; }
    const localPart = emailValue.slice(0, -10);
    const basicPattern = /^[a-zA-Z0-9._%+-]+$/;
    if (localPart.length === 0) { inputField.setCustomValidity('Email username cannot be empty.'); }
    else if (!basicPattern.test(localPart)) { inputField.setCustomValidity('Email username contains invalid characters.'); }
    else { inputField.setCustomValidity(''); }
  }

  function getPasswordStrengthData(password) {
    const MIN_LENGTH = 6, MAX_LENGTH = 50;
    let score = 0, strengthClass = '', strengthText = '';
    if (password.length === 0) return { score:0, strengthClass:'', strengthText:'Enter a password' };
    if (password.length < MIN_LENGTH) return { score:0, strengthClass:'weak', strengthText:'Too short' };
    if (password.length > MAX_LENGTH) return { score:100, strengthClass:'strong', strengthText:'Strong (but too long!)' };

    const hasLowercase = /[a-z]/.test(password);
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[!@#$%^&*()_+={}\[\]|\\:;"'<,>.?/~`-]/.test(password);
    
    score += Math.min(20, password.length*2);
    if(hasLowercase) score +=20;
    if(hasUppercase) score +=20;
    if(hasNumber) score +=20;
    if(hasSpecial) score +=20;

    if(score<40){strengthClass='weak';strengthText='Weak';}
    else if(score<80){strengthClass='moderate';strengthText='Moderate';}
    else{strengthClass='strong';strengthText='Strong';}

    score = Math.min(100,score);
    return {score,strengthClass,strengthText};
  }

  function handleCombinedFeedback() {
    const password = document.getElementById('password-signup').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const strengthData = getPasswordStrengthData(password);
    const progressBar = document.getElementById('strength-progress-bar');
    const strengthTextElement = document.getElementById('password-strength-text');

    progressBar.style.width = strengthData.score + '%';
    progressBar.className = 'bar-' + strengthData.strengthClass;
    strengthTextElement.textContent = 'Password ' + strengthData.strengthText;
    strengthTextElement.className = 'status-' + strengthData.strengthClass;

    const matchTextElement = document.getElementById('password-match-text');
    matchTextElement.className = '';
    if(password.length===0 && confirmPassword.length===0) { matchTextElement.textContent='Password Matching'; }
    else if(password.length>0 && confirmPassword.length>0) {
      if(password===confirmPassword){ matchTextElement.textContent='Password Matching'; matchTextElement.className='match-status-match'; }
      else{ matchTextElement.textContent='Password Not Matching'; matchTextElement.className='match-status-nomatch'; }
    } else if(confirmPassword.length>0){ matchTextElement.textContent='Password Matching: Fill main password'; matchTextElement.className='match-status-warning'; }
    else{ matchTextElement.textContent='Password Matching: Confirm your password'; matchTextElement.className='match-status-warning'; }
  }

  function validateFormSubmission() {
    const password = document.getElementById('password-signup').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const matchTextElement = document.getElementById('password-match-text');

    handleCombinedFeedback();
    matchTextElement.textContent = '';

    if(password !== confirmPassword){
      matchTextElement.textContent='Submission Failed: Passwords must match.';
      matchTextElement.className='match-status-nomatch';
      return false;
    }

    if(password.length < 6){
      matchTextElement.textContent='Submission Failed: Password must be at least 6 characters.';
      matchTextElement.className='match-status-nomatch';
      return false;
    }

    const strengthData = getPasswordStrengthData(password);
    if(strengthData.strengthClass==='weak'){
      const strengthTextElement = document.getElementById('password-strength-text');
      strengthTextElement.textContent='Submission Failed: Password is too weak.';
      strengthTextElement.className='status-weak';
      return false;
    }
    return true;
  }

  function togglePasswordVisibility(inputId, toggleId){
    const input = document.getElementById(inputId);
    const icon = document.getElementById(toggleId);
    if(input.type==='password'){
      input.type='text';
      icon.src='https://cdn-icons-png.flaticon.com/512/2767/2767146.png';
      icon.alt='Hide Password';
    } else {
      input.type='password';
      icon.src='https://cdn-icons-png.flaticon.com/512/159/159604.png';
      icon.alt='Show Password';
    }
  }

  function updateAsterisks(inputField){
    const maxLength=11;
    inputField.value = inputField.value.replace(/\D/g,'');
    let currentLength = inputField.value.length;
    const remaining=maxLength-currentLength;
    if(currentLength>maxLength){ inputField.value=inputField.value.slice(0,maxLength); currentLength=maxLength; }
    const placeholderElement=document.getElementById('custom-placeholder');
    placeholderElement.textContent=remaining>=0 ? '*'.repeat(remaining) : '';
  }
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Keep password feedback on verification phase
    if (document.getElementById('password-signup').value.length > 0) {
        handleCombinedFeedback();
    }

    // Show popup message if PHP $message exists
    <?php if (!empty($message)): ?>
        const popup = document.getElementById('popup-message');
        popup.textContent = '<?php echo addslashes($message); ?>';
        popup.classList.add('<?php echo (strpos($message, "✅") !== false) ? "success" : "error"; ?>');
        popup.style.display = 'block';
        // Hide after 5 seconds
        setTimeout(() => { popup.style.display = 'none'; }, 5000);
    <?php endif; ?>
});
</script>

</body>
</html>
