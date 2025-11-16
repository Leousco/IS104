<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Forgot Password</title>
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
  }

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

  input[type="email"] {
    width: 100%;
    padding: 12px 14px; 
    border-radius: 8px;
    border: none;
    box-shadow: inset 2px 2px 5px #cbe3a4, inset -2px -2px 5px #f6f7ec;
    font-size: 1rem;
    background-color: #f6f7ec;
    color: #333;
  }

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

  .info-text {
    font-size: 0.9rem;
    color: #556b39;
    text-align: center;
    margin-bottom: 20px;
    line-height: 1.5;
  }
</style>
</head>

<body>
  <div class="container">
    <div class="top-header">
      <a href="../login.php" class="back-arrow">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>
      </a>
      <h2>Forgot Password</h2>
    </div>
    
    <form action="send_otp.php" method="POST">
      <p class="info-text">Enter your email address and we'll send you a one-time password to reset your account.</p>

      <input type="text" name="fake-email" style="display:none;" autocomplete="off">

      <div class="form-group">
        <label for="email">Email Address</label>
        <input 
          type="email" 
          id="email" 
          name="email"
          placeholder="Enter your email"
          autocomplete="new-email"
          required
        />
      </div>

      <button type="submit" class="action-btn">Send OTP</button>

      <div class="bottom-text">
        Remember your password? <a href="../login.php">Back to Login</a>
      </div>
    </form>
  </div>
</body>
</html>