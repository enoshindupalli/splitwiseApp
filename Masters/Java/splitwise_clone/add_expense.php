<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

if (!isset($_GET['group_id'])) {
    header('Location: index.php');
    exit;
}
$group_id = intval($_GET['group_id']);

// Check member
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if ($stmt->rowCount() === 0) { die("You are not a member of this group."); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $paid_by = intval($_POST['paid_by']);
    $expense_date = $_POST['expense_date'];

    // Fetch group members
    $membersStmt = $pdo->prepare("SELECT u.id FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?");
    $membersStmt->execute([$group_id]);
    $group_members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

    $member_count = count($group_members);

    if ($amount <= 0 || empty($description) || empty($expense_date)) {
        $errors[] = "Please fill all fields correctly.";
    } elseif ($member_count == 0) {
        $errors[] = "No group members found.";
    } elseif ($paid_by !== $user_id) {
        $errors[] = "Payer must be yourself.";
    } else {
        // Insert the expense
        $stmt = $pdo->prepare("INSERT INTO expenses (group_id, amount, paid_by, description, expense_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$group_id, $amount, $paid_by, $description, $expense_date]);
        $expense_id = $pdo->lastInsertId();

        // Divide the amount equally
        $share_each = round($amount / $member_count, 2);

        $stmt2 = $pdo->prepare("INSERT INTO expense_shares (expense_id, user_id, share_amount) VALUES (?, ?, ?)");

        foreach ($group_members as $member) {
            $stmt2->execute([$expense_id, $member['id'], $share_each]);
        }

        header("Location: expenses.php?group_id=$group_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Expense to Group</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
    body {
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(135deg, #5eead4 0%, #a78bfa 100%);
        font-family: "Poppins", sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .expense-container {
        background: #fff;
        border-radius: 22px;
        padding: 44px 44px 38px 44px;
        box-shadow: 0 10px 38px rgba(0,0,0,0.13);
        width: 630px;
        max-width: 97vw;
        margin-top: 30px;
    }
    .title {
        font-weight: 700;
        font-size: 2.1rem;
        color: #222b45;
        margin-bottom: 35px;
        text-align: center;
    }
    .btn-row {
        margin-top: 30px;
        display: flex;
        gap: 14px;
        justify-content: flex-end;
    }
    .btn-main {
        font-weight: 600;
        border-radius: 10px;
        font-size: 1rem;
        padding: 13px 22px;
        transition: background 0.28s, transform 0.19s;
        border: none;
        box-shadow: 0 4px 14px rgba(52,230,197,0.09);
    }
    .btn-primary {
        background: linear-gradient(135deg, #34e6c5, #3b82f6);
        color: #fff;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #26bca7, #3177db);
        transform: translateY(-2px);
    }
    .btn-secondary {
        background: #e5e7eb;
        color: #344962;
        border: none;
    }
    .btn-secondary:hover {
        background: #c9d2e1;
        color: #1d293b;
    }
    </style>
</head>
<body>
<div class="expense-container">
    <div class="title">Add Expense to Group</div>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger rounded-3 p-3 mb-4 text-center" role="alert">
            <?php echo implode("<br>", $errors); ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="amount" class="form-label">Amount ($):</label>
            <input type="number" step="0.01" id="amount" name="amount" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description:</label>
            <input type="text" id="description" name="description" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="expense_date" class="form-label">Expense Date:</label>
            <input type="date" id="expense_date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
        </div>
        <div class="mb-3">
            <label class="form-label">Paid By:</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>" disabled />
            <input type="hidden" name="paid_by" value="<?php echo $_SESSION['user_id']; ?>" />
        </div>
        <div class="btn-row">
            <button type="submit" class="btn-main btn-primary">Add Expense</button>
            <a href="expenses.php?group_id=<?php echo $group_id; ?>" class="btn-main btn-secondary">Back to Expenses</a>
        </div>
    </form>
</div>
</body>
</html>
