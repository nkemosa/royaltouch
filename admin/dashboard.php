<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require "../config/db.php";

$count = $conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$orderCount = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$pendingCount = $conn->query("SELECT COUNT(*) c FROM orders WHERE order_status IN ('pending','processing')")->fetch_assoc()['c'];
$orders = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 20");
$r = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css">
    <title>Dashboard</title>
</head>

<body>
    <header class="topbar">
        <div class="container nav">
            <b>Admin Dashboard</b>
            <nav>
                <a href="../index.php">Products</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></h1>

        <div class="dashboard-cards">
            <div class="stat">
                <h2><?= $count ?></h2>
                <p>Total Products</p>
            </div>
            <div class="stat">
                <h2><?= $orderCount ?></h2>
                <p>Total Orders</p>
            </div>
            <div class="stat">
                <h2><?= $pendingCount ?></h2>
                <p>Active Orders</p>
            </div>
        </div>

        <a class="btn btn-success" href="add_product.php">+ Add Product</a>

        <h2>Recent Orders</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Order Status</th>
                    <th>Date</th>
                </tr>
                <?php while ($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><a href="order.php?id=<?= (int)$o['id'] ?>"><?= htmlspecialchars($o['order_reference']) ?></a></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td>₦<?= number_format($o['total'], 2) ?></td>
                        <td><?= htmlspecialchars($o['payment_method']) ?> / <?= htmlspecialchars($o['payment_status']) ?></td>
                        <td><?= htmlspecialchars($o['order_status']) ?></td>
                        <td><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <h2>Products</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
                <?php while ($p = $r->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td>₦<?= number_format($p['price'], 2) ?></td>
                        <td>
                            <a class="btn" href="edit_product.php?id=<?= (int)$p['id'] ?>">Edit</a>
                            <a class="btn btn-danger" href="delete_product.php?id=<?= (int)$p['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </main>
</body>

</html>