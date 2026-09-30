<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require "../config/db.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Order not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['order_status'] ?? 'pending';
    $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    if (in_array($status, $allowed, true)) {
        $update = $conn->prepare("UPDATE orders SET order_status=? WHERE id=?");
        $update->bind_param("si", $status, $id);
        $update->execute();
        header("Location: order.php?id=" . $id);
        exit;
    }
}

$itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id=?");
$itemStmt->bind_param("i", $id);
$itemStmt->execute();
$items = $itemStmt->get_result();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css">
    <title>Order <?= htmlspecialchars($order['order_reference']) ?></title>
</head>

<body>
    <header class="topbar">
        <div class="container nav"><b>Order Details</b>
            <nav><a href="dashboard.php">Dashboard</a><a href="logout.php">Logout</a></nav>
        </div>
    </header>
    <main class="container">
        <div class="card">
            <h1><?= htmlspecialchars($order['order_reference']) ?></h1>
            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($order['address'])) ?></p>
            <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?> / <?= htmlspecialchars($order['payment_status']) ?></p>

            <form method="post">
                <label>Order Status</label>
                <select name="order_status">
                    <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                        <option value="<?= $status ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-success">Update Status</button>
            </form>

            <table>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td>₦<?= number_format($item['price'], 2) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td>₦<?= number_format($item['subtotal'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <div class="total">Total: ₦<?= number_format($order['total'], 2) ?></div>
        </div>
    </main>
</body>

</html>