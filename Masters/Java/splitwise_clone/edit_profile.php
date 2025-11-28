<?php
require_once 'functions.php'; // Should include config.php and set $pdo
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = "";

// Avatar paths
$avatars = [
    'avatars/avatar1.jpg',
    'avatars/avatar2.jpg',
    'avatars/avatar3.jpg',
    'avatars/avatar4.jpg',
    'avatars/avatar5.jpg',
];

// Fetch user info
$stmt = $pdo->prepare("SELECT first_name, last_name, email, profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $selected_avatar = $_POST['avatar'] ?? $user['profile_pic'];

    if (empty($fname) || empty($lname) || empty($email) || empty($selected_avatar)) {
        $errors[] = "All fields and avatar must be selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!in_array($selected_avatar, $avatars)) {
        $errors[] = "Invalid avatar selection.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_pic = ? WHERE id = ?");
        $result = $stmt->execute([$fname, $lname, $email, $selected_avatar, $user_id]);
        if ($result) {
            $success = "Profile updated successfully.";
            $user['first_name'] = $fname;
            $user['last_name'] = $lname;
            $user['email'] = $email;
            $user['profile_pic'] = $selected_avatar;
        } else {
            $errors[] = "Failed to update profile. Please try again. Error info: " . implode(', ', $stmt->errorInfo());
        }
    }
}

// Current avatar for preview
$currentAvatar = $user['profile_pic'] ?? $avatars[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Profile</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: #f0f4f8;
            padding: 40px 15px;
            display: flex;
            justify-content: center;
        }
        .edit-card {
            width: 470px;
            max-width: 95vw;
            background: #fff;
            border-radius: 26px;
            padding: 50px 40px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
            text-align: center;
        }
        h2 { text-align: center; margin-bottom: 25px; color: #1e293b; font-weight: 700; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #334155; }
        .form-group input[type="text"], .form-group input[type="email"] { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid #d8dbe9; font-size: 1rem; }
        .form-group input:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 4px rgba(59,130,246,0.15); }
        .avatar-preview { margin-bottom: 20px; text-align: center; }
        .avatar-preview img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #3b82f6; transition: 0.3s; }
        .avatar-selection { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
        .avatar-btn {
            display: inline-block;
            border: 2.5px solid transparent;
            border-radius: 50%;
            transition: border-color 0.18s, box-shadow 0.18s;
            box-shadow: 0 2px 8px rgba(44,62,80,0.07);
            cursor: pointer;
            padding: 6px;
        }
        .avatar-btn.active img,
        .avatar-btn input[type="radio"]:checked + img {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.16);
        }
        .avatar-btn img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            border: 3px solid transparent;
            transition: border-color 0.2s, box-shadow 0.18s;
        }
        .avatar-btn:hover img {
            border-color: #14b8a6;
            box-shadow: 0 0 0 3px rgba(20,184,166,0.12);
        }
        .avatar-btn input[type="radio"] { display: none; }
        button[type="submit"] { width: 100%; padding: 12px; font-size: 1.05rem; font-weight: 700; color: #fff; background: linear-gradient(90deg, #36e0c2, #3b82f6); border: none; border-radius: 12px; cursor: pointer; transition: all 0.25s; }
        button[type="submit"]:hover { background: linear-gradient(90deg, #2ec7aa, #2563eb); transform: translateY(-2px); }
        .back-link { display: block; text-align: center; margin-top: 18px; color: #3b82f6; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #2563eb; }
    </style>
</head>
<body>
<div class="edit-card">
    <h2>Edit Profile</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <label>Profile Picture</label>
        <div class="avatar-preview">
            <img id="selectedAvatar" src="<?= htmlspecialchars($currentAvatar) ?>" alt="Profile Picture">
        </div>
    </div>

    <form method="POST" action="edit_profile.php" autocomplete="off">
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label>Select your Avatar</label>
            <div class="avatar-selection">
                <?php foreach ($avatars as $avatar): ?>
                    <label class="avatar-btn<?= ($user['profile_pic'] === $avatar) ? ' active' : '' ?>">
                        <input type="radio" name="avatar" value="<?= htmlspecialchars($avatar) ?>" <?= ($user['profile_pic'] === $avatar) ? 'checked' : '' ?> required>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit">Save Changes</button>
    </form>
    <a href="index.php" class="back-link">Back to Dashboard</a>
</div>

<script>
    // Update profile picture preview & highlight active avatar
    const avatarInputs = document.querySelectorAll('.avatar-btn input[type="radio"]');
    const preview = document.getElementById('selectedAvatar');
    avatarInputs.forEach(input => {
        input.addEventListener('change', () => {
            preview.src = input.value;
            document.querySelectorAll('.avatar-btn').forEach(function(label) {
                label.classList.remove('active');
            });
            input.parentElement.classList.add('active');
        });
    });
</script>
</body>
</html>
