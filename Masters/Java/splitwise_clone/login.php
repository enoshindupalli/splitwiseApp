<?php
session_start();
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];

                if ($_SESSION['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $errors[] = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "An error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign In - Splitwise Clone</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* Your existing CSS here - no changes needed */
body {
  margin: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #5eead4, #a78bfa);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: "Poppins", sans-serif;
}
.login-wrapper {
  display: flex;
  width: 860px;
  min-height: 480px;
  background: rgba(255, 255, 255, 0.18);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(18px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  transition: transform 0.3s ease;
}
.login-wrapper:hover {
  transform: scale(1.01);
}
.login-graphic {
  flex: 1.2;
  background: linear-gradient(160deg, #1a1a40, #202060);
  color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  position: relative;
  padding: 40px;
}
.login-graphic .brand {
  position: absolute;
  top: 36px;
  left: 36px;
  font-size: 1.8rem;
  font-weight: 700;
  letter-spacing: 2px;
}
.login-graphic svg {
  width: 180px;
  height: 180px;
  margin-bottom: 10px;
  filter: drop-shadow(0 0 20px #5fffc1);
}
.login-graphic p {
  font-size: 1rem;
  opacity: 0.8;
  margin-top: 10px;
}
.login-panel {
  flex: 1.6;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 45px;
  background: rgba(255, 255, 255, 0.9);
}
.login-panel h2 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: #222b45;
}
.login-panel p {
  color: #7a869a;
  font-size: 1rem;
  margin-bottom: 35px;
  text-align: center;
}
form {
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  align-items: center;
}
.form-group {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.form-group label {
  align-self: flex-start;
  font-size: 0.95rem;
  color: #444;
  font-weight: 600;
  margin-bottom: 6px;
  margin-left: 2px;
}
.input-wrap {
  position: relative;
  width: 100%;
}
input[type="text"], input[type="password"] {
  width: 100%;
  height: 48px;
  padding: 12px 45px 12px 13px;
  border-radius: 10px;
  border: 1.5px solid #dfe3eb;
  background: #f9fafc;
  font-size: 1rem;
  transition: all 0.25s ease;
  box-sizing: border-box;
}
input:focus {
  border-color: #34e6c5;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(52, 230, 197, 0.15);
  outline: none;
}
.input-wrap .fa-eye, .input-wrap .fa-eye-slash {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0a5b1;
  cursor: pointer;
  transition: color 0.2s;
}
.input-wrap .fa-eye:hover, .input-wrap .fa-eye-slash:hover {
  color: #16ba9b;
}
.login-btn {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #34e6c5, #3b82f6);
  border: none;
  border-radius: 10px;
  color: #fff;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
}
.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(59, 130, 246, 0.25);
}
.register-link {
  margin-top: 22px;
  text-align: center;
  font-size: 0.98rem;
  color: #7a869a;
}
.register-link a {
  color: #21edc2;
  font-weight: 600;
  text-decoration: none;
  margin-left: 4px;
  letter-spacing: 0.3px;
  transition: color 0.22s;
}
.register-link a:hover {
  color: #3b82f6;
}
@media(max-width: 900px) {
  .login-wrapper {
    flex-direction: column;
    width: 90vw;
  }
  .login-graphic {
    min-height: 200px;
  }
  .login-panel {
    padding: 30px 20px;
  }
}
</style>
</head>
<body>
<div class="login-wrapper">
  <!-- Left Side -->
  <div class="login-graphic">
    <div class="brand">Splitwise</div>
    <svg fill="none" viewBox="0 0 200 200">
      <rect x="40" y="40" width="40" height="40" rx="10" fill="#5fffc1"/>
      <rect x="80" y="80" width="60" height="60" rx="17" fill="#5fffc1" opacity="0.6"/>
      <rect x="50" y="110" width="60" height="25" rx="8" fill="#2859ff" opacity="0.3"/>
      <rect x="120" y="45" width="35" height="35" rx="10" fill="#e95aff" opacity="0.7"/>
    </svg>
    <p>Manage your shared expenses effortlessly</p>
  </div>
  <!-- Right Side -->
  <div class="login-panel">
    <h2>Welcome Back</h2>
    <p>Sign in to continue managing your bills</p>
    <?php if (!empty($errors)): ?>
      <div style="color:#cf2e2e; background:#fff5f5; border-radius:8px; padding:10px 16px; margin-bottom:18px; font-weight:600; text-align:center;">
        <?php echo htmlspecialchars(implode("<br>", $errors)); ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="login.php" autocomplete="off">
      <div class="form-group">
        <label for="username">User Name</label>
        <div class="input-wrap">
          <input type="text" id="username" name="email" autocomplete="username" required />
        </div>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" autocomplete="current-password" required />
          <i class="fa fa-eye" id="togglePwd"></i>
        </div>
      </div>
      <button class="login-btn" type="submit">Sign In</button>
    </form>
    <div class="register-link">
      Don't have an account?
      <a href="register.php">Register here</a>
    </div>
    <div class="register-link" style="margin-top: 10px;">
      <span>Are you an admin?</span>
      <a href="admin_login.php" style="color: #3b82f6; font-weight: 700; margin-left: 5px;">Admin Login</a>
    </div>
  </div>
</div>
<script>
const pwd = document.getElementById('password');
const togglePwd = document.getElementById('togglePwd');
togglePwd.onclick = () => {
  if (pwd.type === 'password') {
    pwd.type = 'text';
    togglePwd.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    pwd.type = 'password';
    togglePwd.classList.replace('fa-eye-slash', 'fa-eye');
  }
};
</script>
</body>
</html>
