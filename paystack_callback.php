<?php
session_start();
require "config/db.php";
require "config/paystack.php";

$reference = trim($_GET['reference'] ?? '');

if ($reference === '') {
    die("Payment reference is missing.");
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_reference=? LIMIT 1");
$stmt->bind_param("s", $reference);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

if ($order['payment_status'] === 'paid') {
    header("Location: receipt.php?order=" . urlencode($order['order_reference']) . "&token=" . urlencode($order['receipt_token']));
    exit;
}

$ch = curl_init($paystackApiUrl . '/transaction/verify/' . rawurlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $paystackSecretKey,
        'Cache-Control: no-cache'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    die("Unable to verify the payment. Please contact the store.");
}

$data = json_decode($response, true);
$payment = $data['data'] ?? [];

$expectedAmount = (int)round(((float)$order['total']) * 100);
$paidAmount = (int)($payment['amount'] ?? 0);

if (
    !empty($data['status']) &&
    ($payment['status'] ?? '') === 'success' &&
    $paidAmount === $expectedAmount &&
    ($payment['reference'] ?? '') === $reference
) {
    $update = $conn->prepare(
        "UPDATE orders SET payment_status='paid', paystack_reference=?, order_status='processing' WHERE id=?"
    );
    $update->bind_param("si", $reference, $order['id']);
    $update->execute();

    $_SESSION['cart'] = [];

    header("Location: receipt.php?order=" . urlencode($order['order_reference']) . "&token=" . urlencode($order['receipt_token']));
    exit;
}

$fail = $conn->prepare("UPDATE orders SET payment_status='failed' WHERE id=?");
$fail->bind_param("i", $order['id']);
$fail->execute();

echo '<!DOCTYPE html><html><head><link rel="stylesheet" href="css/style.css"><title>Payment Failed</title></head><body><main class="container"><div class="card" style="margin:60px auto;text-align:center;max-width:600px"><h1>Payment Not Completed</h1><p>We could not confirm your payment. Your order has not been marked as paid.</p><a class="btn" href="checkout.php">Return to Checkout</a> <a class="btn btn-secondary" href="index.php">Store</a></div></main></body></html>';
