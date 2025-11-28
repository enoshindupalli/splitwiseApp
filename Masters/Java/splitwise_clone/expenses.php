<?php
require_once 'functions.php';
redirectIfNotLoggedIn();

if (!isset($_GET['group_id'])) {
    header('Location: index.php');
    exit;
}

$group_id = intval($_GET['group_id']);
$user_id = $_SESSION['user_id'];

// Verify user membership in the group
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if ($stmt->rowCount() === 0) {
    die("Access denied.");
}

// Fetch group info
$stmt = $pdo->prepare("SELECT group_name FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

// Expenses and shares
$stmt = $pdo->prepare("
    SELECT e.id as expense_id, e.description, e.amount, e.paid_by, e.expense_date, u.first_name, u.last_name
    FROM expenses e
    JOIN users u ON e.paid_by = u.id
    WHERE e.group_id = ?
    ORDER BY e.expense_date DESC
");
$stmt->execute([$group_id]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch shares for all expenses
$shares = [];
if ($expenses) {
    $expense_ids = array_column($expenses, 'expense_id');
    $placeholders = implode(',', array_fill(0, count($expense_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM expense_shares WHERE expense_id IN ($placeholders)");
    $stmt->execute($expense_ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $share) {
        $shares[$share['expense_id']][] = $share;
    }
}

// Fetch members and compute balances
$stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?");
$stmt->execute([$group_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate balances
$balances = [];
foreach ($members as $m) {
    $balances[$m['id']] = 0;
}
foreach ($expenses as $exp) {
    $expense_id = $exp['expense_id'];
    $paid_by_id = $exp['paid_by'];
    $total_amount = $exp['amount'];
    if (isset($shares[$expense_id])) {
        foreach ($shares[$expense_id] as $share) {
            $balances[$share['user_id']] -= $share['share_amount'];
        }
    }
    $balances[$paid_by_id] += $total_amount;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Group Expenses - <?= htmlspecialchars($group['group_name']) ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg,#5eead4 0%, #a78bfa 100%);
            font-family: "Poppins", sans-serif;
        }
        .expense-wrapper {
            background: #fff;
            border-radius: 22px;
            padding: 42px 54px 36px 54px;
            margin: 38px auto;
            box-shadow: 0 10px 34px rgba(0,0,0,0.13);
            width: 1020px;
            max-width: 99vw;
        }
        .page-title {
            font-size: 2.1rem;
            font-weight: 800;
            color: #17455e;
            margin-bottom: 24px;
            letter-spacing: 0.1rem;
            display: flex; gap:18px; align-items: center;
        }
        .btn-row {
            margin-bottom: 26px;
            display: flex;
            gap: 16px;
        }
        .my-btn-primary {
            background: linear-gradient(135deg,#34e6c5,#3b82f6);
            color: #fff;
            font-weight: 800;
            border-radius: 10px;
            padding: 8px 23px;
            border: none;
            box-shadow: 0 4px 10px rgba(51,141,163,0.12);
            transition: background .22s, transform .1s;
        }
        .my-btn-primary:hover {
            background: linear-gradient(135deg,#23be98,#3065e7);
            transform: translateY(-2px);
            color: #fff;
        }
        .my-btn-secondary {
            background: #e5eaf2;
            color: #263359;
            font-weight: 700;
            border-radius: 10px;
            padding: 8px 18px;
            border: none;
        }
        .my-btn-secondary:hover {
            background: #cfe1f6;
            color: #223;
        }
        .btn-danger {
            font-weight: 700;
            border-radius: 10px;
            padding: 8px 18px;
        }
        .balances-row {
            display: flex; flex-wrap: wrap; gap:18px;
            margin-bottom: 32px;
        }
        .balance-card {
            background: linear-gradient(92deg,#daf7f1 0%,#f7fafe 100%);
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(20,184,166,0.10);
            flex:1 1 170px; min-width:170px; padding:18px 24px 15px 24px; text-align:center;
        }
        .balance-title {
            font-weight:700; margin-bottom:2px; color:#116a60; font-size:1.05rem;
        }
        .badge-success { background:#16a34a; }
        .badge-danger { background:#dc2626; }
        .badge-neutral { background:#676d74; background:#fcd34d; color:#333; }
        .balance-amt {
            font-weight:800; font-size:1.35rem; margin-bottom:2px;
            display:inline-block; margin-top:2px;
        }
        .expense-table {
            border-radius: 14px !important;
            background: #fff;
            overflow: hidden;
        }
        .expense-table th {
            background: #daf7f1;
            color: #208d79;
            font-size:1.04rem;
        }
        .expense-table td { font-size:1.07rem; vertical-align: middle; }
        .share-member { font-weight:700; color:#2563eb; }
        .share-amt { color:#059669; margin-left:4px; }
        .action-btns .btn { padding:5px 13px; }
        .expense-id { opacity:0.29; font-size:0.81rem; margin-left:3px; }
        @media(max-width:1000px) { .expense-wrapper { padding:2vw; width:99vw; } }
        @media(max-width:600px) {
            .expense-wrapper { padding:2vw; }
            .page-title { font-size:1.3rem; }
            .balance-title { font-size:0.98rem; }
            .balance-amt { font-size:1.07rem; }
        }
    </style>
</head>
<body>
    <div class="expense-wrapper">
        <div class="page-title">
            <i class="fa fa-users"></i>
            Expenses for: <span style="color:#36e0c2"><?= htmlspecialchars($group['group_name']); ?></span>
        </div>
        <div class="btn-row">
            <a href="index.php" class="my-btn-secondary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <a href="add_expense.php?group_id=<?= $group_id; ?>" class="my-btn-primary"><i class="fa fa-plus"></i> Add Expense</a>
            <a href="delete_group.php?group_id=<?= $group_id; ?>"
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this group and ALL its expenses?');">
                <i class="fa fa-trash"></i> Delete Group
            </a>
        </div>
        <div class="section-label" style="font-size:1.18rem;margin-bottom:12px;color:#165248;">
            <i class="fa fa-piggy-bank me-2"></i>Current Balances
        </div>
        <div class="balances-row">
            <?php foreach ($members as $m): 
                $amt = round($balances[$m['id']],2);
                $badge = $amt > 0 ? 'success' : ($amt < 0 ? 'danger' : 'neutral');
                $badgeText = $amt > 0 ? 'Gets Back' : ($amt < 0 ? 'Owes' : 'Settled');
            ?>
                <div class="balance-card">
                    <div class="balance-title">
                        <i class="fa fa-user-circle me-1"></i>
                        <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?>
                    </div>
                    <div class="balance-amt"><?= number_format($amt,2) ?></div>
                    <span class="badge badge-<?= $badge ?>" style="font-size:0.97rem;"><?= $badgeText ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="section-label" style="font-size:1.18rem;">
            <i class="fa fa-history me-2"></i>Expense History
        </div>
        <?php if (count($expenses) > 0): ?>
        <table class="table table-bordered expense-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Payer</th>
                    <th>Shares</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($expenses as $exp): ?>
                <tr>
                    <td><?= htmlspecialchars($exp['expense_date']); ?><span class="expense-id">#<?= $exp['expense_id']; ?></span></td>
                    <td><?= htmlspecialchars($exp['description']); ?></td>
                    <td><span style="font-weight:700;"><?= number_format($exp['amount'], 2); ?></span></td>
                    <td><span class="share-member"><i class="fa fa-user"></i> <?= htmlspecialchars($exp['first_name'] . ' ' . $exp['last_name']); ?></span></td>
                    <td>
                        <ul style="list-style:none;margin:0;padding:0;">
                        <?php
                        if (isset($shares[$exp['expense_id']])) {
                            foreach ($shares[$exp['expense_id']] as $share) {
                                $sn = '';
                                foreach ($members as $m) {
                                    if ($m['id'] == $share['user_id']) {
                                        $sn = $m['first_name'] . ' ' . $m['last_name'];
                                        break;
                                    }
                                }
                                echo "<li><span class='share-member'>" . htmlspecialchars($sn) . "</span>: <span class='share-amt'>" . number_format($share['share_amount'],2) . "</span></li>";
                            }
                        }
                        ?>
                        </ul>
                    </td>
                    <td class="action-btns">
                        <a href="edit_expense.php?id=<?= $exp['expense_id']; ?>&group_id=<?= $group_id; ?>" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i> Edit</a>
                        <a href="delete_expense.php?id=<?= $exp['expense_id']; ?>&group_id=<?= $group_id; ?>" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i> Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No expenses recorded yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
