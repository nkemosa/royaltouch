<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require "../config/db.php";
$id = (int)$_GET['id'];
$s = $conn->prepare("DELETE FROM products WHERE id=?");
$s->bind_param("i", $id);
$s->execute();
header("Location: dashboard.php");
exit;
