<?php
session_start();
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $_SESSION['cart'] ??= [];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
}
header("Location: cart.php");
exit;
