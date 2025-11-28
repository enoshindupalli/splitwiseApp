<?php
require_once 'functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

$stmt = $pdo->query("SELECT id, first_name, last_name, email FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Admin Dashboard - Splitwise Clone</title>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
<style>
body {
    min-height: 100vh;
    margin: 0;
    background: linear-gradient(135deg, #5eead4, #a78bfa 90%);
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}
.admin-container {
    background: #fff;
    border-radius: 22px;
    margin-top: 56px;
    padding: 40px 55px 38px 55px;
    box-shadow: 0 10px 38px rgba(42,88,151,.10),0 1.5px 7px rgba(128,122,218,.10);
    width: 900px;
    max-width: 99vw;
}
.heading {
    font-weight: 800;
    font-size: 2.1rem;
    letter-spacing: .01rem;
    color: #232235;
    margin-bottom: 22px;
}
.btn-row {
    display: flex;
    gap: 12px;
    margin-bottom: 22px;
}
.btn-back {
    background: #e5eaf2;
    color: #263359;
    font-weight: 700;
    border-radius: 10px;
    padding: 8px 19px;
    border: none;
    font-size: 1.03rem;
    transition: background .22s, color .18s;
    text-decoration: none;
}
.btn-back:hover {
    background: #becfe6;
    color: #197189;
}
.btn-logout {
    background: linear-gradient(135deg, #ff5e62, #ff9966);
    color: #fff;
    font-weight: 700;
    border-radius: 10px;
    padding: 8px 19px;
    border: none;
    font-size: 1.03rem;
    transition: background .22s, color .18s;
    text-decoration: none;
}
.btn-logout:hover {
    background: linear-gradient(135deg, #e5383b, #ffba66);
    color: #fff;
}
.section-title {
    font-weight: 700;
    margin-bottom: 18px;
    font-size: 1.24rem;
    color: #393fac;
}
.table {
    border-radius: 12px;
    background: #f6f8fe;
    box-shadow: 0 0px 7px rgba(119,198,235,.06);
}
.table th {
    background: #daf7f1;
    color: #208d79;
    font-weight: 600;
    font-size: 1.08rem;
}
.table td {
    font-size: 1.07rem;
    color: #25274d;
}
@media (max-width: 1000px) {
    .admin-container { padding: 24px 3vw 18px 3vw; width: 99vw; }
}
@media (max-width: 600px) {
    .admin-container { padding: 10px 1vw 8px 1vw; margin-top: 10px; }
    .heading { font-size: 1.2rem;}
}
</style>
</head>
<body>
<div class="admin-container">
    <div class="heading">Admin Dashboard</div>
    <div class="btn-row">
        <a href="index.php" class="btn btn-back">Back to User Dashboard</a>
        <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>
    <div class="section-title">Registered Users</div>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Group Memberships</th>
            <th>Recent Activity</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <?php
                    $stmt2 = $pdo->prepare("SELECT g.group_name FROM groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?");
                    $stmt2->execute([$user['id']]);
                    $groups = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                    echo htmlspecialchars(implode(', ', $groups));
                    ?>
                </td>
                <td>
                    <?php
                    $stmt3 = $pdo->prepare("SELECT MAX(last_activity) as recent FROM (
                        SELECT MAX(created_at) as last_activity FROM expenses WHERE paid_by = ?
                        UNION
                        SELECT MAX(joined_at) as last_activity FROM group_members WHERE user_id = ?
                    ) t");
                    $stmt3->execute([$user['id'], $user['id']]);
                    $recent = $stmt3->fetchColumn();
                    echo $recent ? $recent : 'No activity';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
