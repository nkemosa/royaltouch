<?php
session_start();
$id = (int)($_GET['id'] ?? 0);
unset($_SESSION['cart'][$id]);
header("Location: cart.php");
exit;
