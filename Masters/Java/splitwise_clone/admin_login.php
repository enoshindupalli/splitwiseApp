<?php
session_start();
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['role'] !== 'admin') { 
                    $errors[] = "Access denied. This is for admins only."; 
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    header('Location: admin_dashboard.php');
                    exit;
                }
            } else {
                $errors[] = "Invalid email or password.";
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
<title>Admin Login - Splitwise Clone</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
  margin: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #5eead4, #a78bfa);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: "Poppins", sans-serif;
}
.admin-login-card {
  width: 410px;
  max-width: 95vw;
  background: rgba(255,255,255,0.98);
  border-radius: 28px;
  padding: 50px 38px 38px 38px;
  box-shadow: 0 10px 44px 0 rgba(54,88,180,.13), 0 1.5px 4px rgba(25,229,194,.10);
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: fadeIn 0.85s cubic-bezier(.17,.67,.83,.67);
}
@keyframes fadeIn {
  0% { opacity: 0; transform: scale(0.93);}
  100% { opacity: 1; transform: scale(1);}
}
.admin-logo {
  margin-bottom: 14px;
  filter: drop-shadow(0 0 16px #34e6c590);
}
.admin-brand {
  font-size: 1.68rem;
  font-weight: 800;
  color: #232235;
  margin-bottom: 3px;
  text-align: center;
  letter-spacing: .03rem;
}
.admin-desc {
  color: #6c81a6;
  font-size: 0.98rem;
  font-weight: 500;
  margin-bottom: 30px;
  text-align: center;
}
.admin-login-card h2 {
  font-size: 1.21rem;
  font-weight: 700;
  color: #108e9c;
  margin-bottom: 27px;
  letter-spacing: .01rem;
  text-align: center;
}
form {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 28px;
  margin-bottom: 9px;
}
.form-group {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.form-group label {
  font-size: 1.07rem;
  color: #25405a;
  font-weight: 600;
  margin-bottom: 10px;
  margin-left: 1px;
  letter-spacing: .02rem;
}
.input-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
}
input[type="text"], input[type="password"] {
  width: 100%;
  max-width: 320px;
  height: 54px;
  padding: 0 20px;
  border-radius: 13px;
  border: 1.9px solid #dfe3eb;
  background: #f9fafc;
  font-size: 1.10rem;
  margin-bottom: 3px;
  box-shadow: 0 2px 8px 0 rgba(53,230,197,.03);
  transition: all 0.18s;
  text-align: left;
}
input:focus {
  border-color: #34e6c5;
  background: #fff;
  box-shadow: 0 0 0 6px rgba(52, 230, 197, 0.11);
  outline: none;
}
.login-btn {
  width: 100%;
  max-width: 320px;
  height: 53px;
  background: linear-gradient(135deg, #36e0c2, #3b82f6 80%);
  border: none;
  border-radius: 13px;
  color: #fff;
  font-size: 1.17rem;
  font-weight: 700;
  cursor: pointer;
  margin-top: 15px;
  box-shadow: 0 6px 20px rgba(59,130,246,.10);
  transition: all 0.18s;
}
.login-btn:hover {
  background: linear-gradient(128deg,#2bcabc,#2b63d9 88%);
  transform: translateY(-2px) scale(1.013);
  box-shadow: 0 10px 36px rgba(59,130,246,0.13);
}
.back-link {
  text-align: center;
  margin-top: 17px;
  font-size: 1.03rem;
}
.back-link a {
  text-decoration: none;
  color: #3b82f6;
  font-weight: 700;
  margin-left: 4px;
  transition: color .16s;
}
.back-link a:hover {
  color: #21edc2;
}
@media (max-width: 600px) {
  .admin-login-card { padding: 17px 2vw 13px 2vw; min-width: 0; }
  .admin-brand { font-size: 1.13rem;}
}
</style>
</head>
<body>
<div class="admin-login-card">
    <div class="admin-logo">
      <svg fill="none" viewBox="0 0 200 200" width="70" height="70">
        <rect x="40" y="40" width="40" height="40" rx="10" fill="#5fffc1"/>
        <rect x="80" y="80" width="60" height="60" rx="17" fill="#5fffc1" opacity="0.6"/>
        <rect x="50" y="110" width="60" height="25" rx="8" fill="#2859ff" opacity="0.3"/>
        <rect x="120" y="45" width="35" height="35" rx="10" fill="#e95aff" opacity="0.7"/>
      </svg>
    </div>
    <div class="admin-brand">Splitwise Admin</div>
    <div class="admin-desc">Admin panel access only</div>
    <h2>Admin Login</h2>
    <?php if (!empty($errors)): ?>
      <div style="color:#cf2e2e; background:#fff5f5; border-radius:8px; padding:10px 16px;margin-bottom:13px;font-weight:600; text-align:center;">
        <?php echo htmlspecialchars(implode("<br>", $errors)); ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="admin_login.php" autocomplete="off">
      <div class="form-group">
        <label for="email">Email</label>
        <div class="input-wrap">
          <input type="text" id="email" name="email" autocomplete="username" required />
        </div>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" autocomplete="current-password" required />
        </div>
      </div>
      <button type="submit" class="login-btn">Sign In</button>
    </form>
    <div class="back-link">
      <a href="login.php">Back to User Login</a>
    </div>
</div>
</body>
</html>
