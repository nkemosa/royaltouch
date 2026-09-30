<?php
session_start();
require "config/db.php";
require "config/paystack.php";

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    header("Location: cart.php");
    exit;
}

$products = [];
$total = 0;

$ids = implode(',', array_map('intval', array_keys($cart)));
$result = $conn->query("SELECT * FROM products WHERE id IN ($ids)");

while ($p = $result->fetch_assoc()) {
    $products[$p['id']] = $p;
}

foreach ($cart as $id => $qty) {
    if (isset($products[$id])) {
        $total += $products[$id]['price'] * (int)$qty;
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $address === '') {
        $error = "Please fill in all required fields and enter a valid email address.";
    } elseif (!in_array($paymentMethod, ['cod', 'online'], true)) {
        $error = "Please select a payment method.";
    } else {
        $orderReference = 'ORD-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $receiptToken = bin2hex(random_bytes(32));

        $stmt = $conn->prepare(
            "INSERT INTO orders
            (order_reference, customer_name, email, phone, address, payment_method, payment_status, total, receipt_token)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)"
        );
        $stmt->bind_param(
            "ssssssds",
            $orderReference,
            $name,
            $email,
            $phone,
            $address,
            $paymentMethod,
            $total,
            $receiptToken
        );

        if (!$stmt->execute()) {
            $error = "Unable to create your order. Please try again.";
        } else {
            $orderId = $conn->insert_id;

            $itemStmt = $conn->prepare(
                "INSERT INTO order_items
                (order_id, product_id, product_name, price, quantity, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($cart as $id => $qty) {
                if (!isset($products[$id])) continue;
                $p = $products[$id];
                $quantity = (int)$qty;
                $subtotal = $p['price'] * $quantity;
                $productId = (int)$id;
                $productName = $p['name'];
                $price = (float)$p['price'];

                $itemStmt->bind_param(
                    "iisdid",
                    $orderId,
                    $productId,
                    $productName,
                    $price,
                    $quantity,
                    $subtotal
                );
                $itemStmt->execute();
            }

            if ($paymentMethod === 'cod') {
                $_SESSION['cart'] = [];
                header("Location: receipt.php?order=" . urlencode($orderReference) . "&token=" . urlencode($receiptToken));
                exit;
            }

            // Online payment: initialize Paystack transaction on the server.
            if (strpos($paystackSecretKey, 'REPLACE_WITH') !== false) {
                $conn->query("DELETE FROM order_items WHERE order_id=" . (int)$orderId);
                $conn->query("DELETE FROM orders WHERE id=" . (int)$orderId);
                $error = "Online payment is not configured yet. Add your Paystack secret key in config/paystack.php.";
            } else {
                $payload = [
                    'email' => $email,
                    'amount' => (string)round($total * 100), // NGN -> kobo
                    'currency' => 'NGN',
                    'reference' => $orderReference,
                    'callback_url' => rtrim($siteUrl, '/') . '/paystack_callback.php',
                    'metadata' => [
                        'order_reference' => $orderReference,
                        'customer_name' => $name,
                        'phone' => $phone
                    ]
                ];

                $ch = curl_init($paystackApiUrl . '/transaction/initialize');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $paystackSecretKey,
                        'Content-Type: application/json',
                        'Cache-Control: no-cache'
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30
                ]);

                $response = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    $error = "Could not connect to the payment gateway. Please try again.";
                } else {
                    $data = json_decode($response, true);

                    if (!empty($data['status']) && !empty($data['data']['authorization_url'])) {
                        $_SESSION['pending_order'] = $orderReference;
                        header("Location: " . $data['data']['authorization_url']);
                        exit;
                    }

                    $error = "Payment gateway could not start the transaction.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - My Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="topbar">
        <div class="container nav">
            <a class="logo" href="index.php">Alice Aura</a>
            <nav>
                <a href="index.php">Products</a>
                <a href="cart.php">🛒 Cart </span></a>
                <a href="admin/login.php">Admin</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="checkout-grid">
            <section class="card">
                <h1>Checkout</h1>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

                    <label>Email Address *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                    <label>Phone Number *</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>

                    <label>Delivery Address *</label>
                    <textarea name="address" rows="4" style="resize: none;" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

                    <label>Payment Method *</label>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" <?= (($_POST['payment_method'] ?? '') === 'cod') ? 'checked' : '' ?> required>
                            <span><strong>Pay on Delivery</strong><small>Pay when your order is delivered.</small></span>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="online" <?= (($_POST['payment_method'] ?? '') === 'online') ? 'checked' : '' ?>>
                            <span><strong>Pay Online</strong><small>Secure payment through Paystack.</small></span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success checkout-btn">Place Order</button>
                </form>
            </section>

            <aside class="card">
                <h2>Order Summary</h2>
                <?php foreach ($cart as $id => $qty): if (!isset($products[$id])) continue; ?>
                    <div class="summary-row">
                        <span><?= htmlspecialchars($products[$id]['name']) ?> × <?= (int)$qty ?></span>
                        <strong>₦<?= number_format($products[$id]['price'] * $qty, 2) ?></strong>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="summary-total">
                    <span>Total</span>
                    <strong>₦<?= number_format($total, 2) ?></strong>
                </div>
            </aside>
        </div>
    </main>
</body>

</html>