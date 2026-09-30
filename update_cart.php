<?php
session_start();
foreach (($_POST['quantity'] ?? []) as $id => $qty) {
    $id = (int)$id;
    $qty = (int)$qty;
    if ($qty > 0) $_SESSION['cart'][$id] = $qty;
    else unset($_SESSION['cart'][$id]);
}
header("Location: cart.php");
exit;
