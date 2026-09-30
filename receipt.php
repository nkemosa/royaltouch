<?php
require "config/db.php";

$orderRef = trim($_GET['order'] ?? '');
$token = trim($_GET['token'] ?? '');

if ($orderRef === '' || $token === '') {
    die("Invalid receipt link.");
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_reference=? AND receipt_token=? LIMIT 1");
$stmt->bind_param("ss", $orderRef, $token);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Receipt not found.");
}

$itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC");
$itemStmt->bind_param("i", $order['id']);
$itemStmt->execute();
$items = $itemStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= htmlspecialchars($order['order_reference']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="container">
    <div class="receipt card">
        <div class="receipt-head">
            <div>
                <h1>Payment Receipt</h1>
                <p>Order: <strong><?= htmlspecialchars($order['order_reference']) ?></strong></p>
            </div>
            <button class="btn no-print" onclick="window.print()">Print Receipt</button>
        </div>

        <div class="receipt-status">
            <?php if ($order['payment_method'] === 'cod'): ?>
                <strong>PAYMENT ON DELIVERY</strong>
                <span>Payment status: Pending</span>
            <?php elseif ($order['payment_status'] === 'paid'): ?>
                <strong>PAYMENT SUCCESSFUL</strong>
                <span>Payment status: Paid</span>
            <?php else: ?>
                <strong>PAYMENT STATUS: <?= strtoupper(htmlspecialchars($order['payment_status'])) ?></strong>
            <?php endif; ?>
        </div>

        <div class="customer-info">
            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p><strong>Delivery Address:</strong><br><?= nl2br(htmlspecialchars($order['address'])) ?></p>
            <p><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
        </div>

        <table>
            <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
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

        <p class="receipt-note">
            <?php if ($order['payment_method'] === 'cod'): ?>
                Please keep this receipt and present it when your order is delivered.
            <?php else: ?>
                Thank you for your payment.
            <?php endif; ?>
        </p>

        <div class="no-print">
            <a class="btn" href="index.php">Continue Shopping</a>
        </div>
    </div>
</main>
</body>
</html>
