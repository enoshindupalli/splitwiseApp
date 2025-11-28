<?php
require_once 'functions.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'join' && !empty($_POST['join_group_id'])) {
            // Join group
            $group_id = intval($_POST['join_group_id']);
            $stmt = $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->execute([$group_id, $user_id]);
        } elseif ($action === 'create' && !empty(trim($_POST['new_group_name']))) {
            // Create group and join
            $grp_name = trim($_POST['new_group_name']);
            $stmt = $pdo->prepare("INSERT INTO groups (group_name, created_by) VALUES (?, ?)");
            $stmt->execute([$grp_name, $user_id]);
            $new_group_id = $pdo->lastInsertId();
            $stmt2 = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt2->execute([$new_group_id, $user_id]);
        }
    }
}

header('Location: index.php');
exit;
?>
