<?php
require_once 'config.php';

$errors = [];
$success = '';
$profilePicPath = null;

// Avatar images
$avatars = [
    'avatars/avatar1.jpg',
    'avatars/avatar2.jpg',
    'avatars/avatar3.jpg',
    'avatars/avatar4.jpg',
    'avatars/avatar5.jpg',
];

// Fetch existing user data if editing
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT profile_pic, first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $selected_avatar = $_POST['avatar'] ?? '';

    if (empty($fname) || empty($lname) || empty($email) || empty($selected_avatar)) {
        $errors[] = "All fields and avatar must be selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password && $password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (!in_array($selected_avatar, $avatars)) {
        $errors[] = "Invalid avatar selection.";
    } else {
        $profilePicPath = $selected_avatar;
    }

    if (empty($errors)) {
        if ($userId) {
            // Update existing user
            $sql = "UPDATE users SET first_name=?, last_name=?, email=?, profile_pic=?";
            $params = [$fname, $lname, $email, $profilePicPath];

            if ($password) {
                $sql .= ", password_hash=?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id=?";
            $params[] = $userId;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $success = "Profile updated successfully!";
                // Update local data
                $userData['profile_pic'] = $profilePicPath;
                $userData['first_name'] = $fname;
                $userData['last_name'] = $lname;
                $userData['email'] = $email;
            } else {
                $errors[] = "Failed to update profile.";
            }
        } else {
            // New registration
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email already registered.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, profile_pic) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$fname, $lname, $email, $password_hash, $profilePicPath])) {
                    header('Location: login.php?registered=1');
                    exit;
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        }
    }
}

// Determine which avatar is selected for preview
$currentAvatar = $_POST['avatar'] ?? $userData['profile_pic'] ?? $avatars[0];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile / Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            font-family:"Poppins",sans-serif;
            background:linear-gradient(135deg,#5eead4 0%,#a78bfa 100%);
            padding:20px;
        }
        .card {
            width:480px;
            max-width:95%;
            background:#fff;
            border-radius:20px;
            padding:50px 40px;
            box-shadow:0 12px 35px rgba(0,0,0,0.15);
            text-align:center;
        }
        h1 { font-size:2rem; font-weight:700; margin-bottom:10px; color:#1e293b; }
        h4 { font-size:1rem; font-weight:500; margin-bottom:30px; color:#64748b; }
        .alert { background:#fee2e2; color:#b91c1c; padding:15px 20px; border-radius:10px; font-size:0.95rem; margin-bottom:20px; text-align:left; }
        .alert ul { margin-left:20px; }
        .success { background:#d1fae5; color:#065f46; padding:10px; border-radius:10px; margin-bottom:15px; }

        form { display:flex; flex-direction:column; gap:16px; text-align:left; }
        .form-group label { font-weight:600; display:block; margin-bottom:6px; color:#334155; }
        input[type="text"], input[type="email"], input[type="password"] { width:100%; height:50px; padding:0 16px; font-size:1rem; border:1.5px solid #d8dbe9; border-radius:12px; background:#f9fafc; transition:0.2s; }
        input:focus { border-color:#3b82f6; background:#fff; box-shadow:0 0 0 4px rgba(59,130,246,0.15); outline:none; }

        /* Avatar Selection */
        .avatar-preview { margin-bottom:20px; text-align:center; }
        .avatar-preview img { width:100px; height:100px; border-radius:50%; object-fit:cover; border:4px solid #3b82f6; transition:0.3s; }
        .avatar-selection { display:flex; flex-wrap:wrap; gap:15px; justify-content:center; margin-top:10px; }
        .avatar-label { cursor:pointer; border:3px solid transparent; padding:4px; border-radius:50%; transition:all 0.25s; }
        .avatar-label:hover { transform:scale(1.1); border-color:#3b82f6; }
        .avatar-label input[type="radio"] { display:none; }
        .avatar-label img { width:64px; height:64px; border-radius:50%; object-fit:cover; display:block; transition:0.25s; }
        .avatar-label input[type="radio"]:checked + img { border-color:#3b82f6; transform:scale(1.1); box-shadow:0 0 12px rgba(59,130,246,0.5); }

        .btn { width:100%; height:50px; border:none; border-radius:12px; font-size:1.1rem; font-weight:700; color:#fff; cursor:pointer; background:linear-gradient(90deg,#36e0c2,#3b82f6); transition:0.25s; margin-top:10px; }
        .btn:hover { background:linear-gradient(90deg,#2ec7aa,#2563eb); transform:translateY(-2px); }

        .login-link { text-align:center; font-size:1rem; margin-top:20px; color:#475569; }
        .login-link a { color:#3b82f6; font-weight:600; text-decoration:none; transition:0.2s; }
        .login-link a:hover { color:#10b981; }
    </style>
</head>
<body>
<div class="card">
    <h1><?= $userId ? 'Edit Profile' : 'Create Account' ?></h1>
    <h4><?= $userId ? 'Update your profile' : 'Join and manage your group expenses easily' ?></h4>

    <?php if (!empty($errors)): ?>
        <div class="alert"><ul>
            <?php foreach($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? $userData['first_name'] ?? '') ?>" required />
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? $userData['last_name'] ?? '') ?>" required />
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $userData['email'] ?? '') ?>" required />
        </div>
        <div class="form-group">
            <label>Password <?= $userId ? '(leave blank to keep current)' : '' ?></label>
            <input type="password" name="password" />
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" />
        </div>

        <!-- Profile Picture Label & Preview -->
        <div class="form-group">
            <label>Profile Picture</label>
            <div class="avatar-preview">
                <img id="selectedAvatar" src="<?= htmlspecialchars($currentAvatar) ?>" alt="Selected Avatar">
            </div>
        </div>

        <div class="form-group">
            <label>Select your Avatar</label>
            <div class="avatar-selection">
                <?php foreach($avatars as $avatar): ?>
                    <label class="avatar-label">
                        <input type="radio" name="avatar" value="<?= htmlspecialchars($avatar) ?>" 
                        <?= ($currentAvatar === $avatar) ? 'checked' : '' ?> required />
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" />
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn"><?= $userId ? 'Save Changes' : 'Register' ?></button>
        <?php if (!$userId): ?>
            <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
        <?php endif; ?>
    </form>
</div>

<script>
    // Update preview when user clicks an avatar
    const avatarInputs = document.querySelectorAll('.avatar-label input[type="radio"]');
    const preview = document.getElementById('selectedAvatar');
    avatarInputs.forEach(input => {
        input.addEventListener('change', () => {
            preview.src = input.value;
        });
    });
</script>
</body>
</html>
