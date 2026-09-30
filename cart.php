<?php
session_start();
require "config/db.php";
$cart = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;
if ($cart) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $r = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    while ($x = $r->fetch_assoc()) $products[$x['id']] = $x;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cart</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="topbar">
        <div class="container nav"><a class="logo" href="index.php">Alice Aura</a>
            <nav><a href="index.php">Products</a><a href="admin/login.php">Admin</a></nav>
        </div>
    </header>
    <main class="container">
        <h1>Your Cart</h1>
        <?php if (!$cart): ?><div class="card">
                <p>Your cart is empty.</p><a class="btn" href="index.php">Continue Shopping</a>
            </div>
        <?php else: ?><form method="post" action="update_cart.php">
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($cart as $id => $qty): if (!isset($products[$id])) continue;
                        $p = $products[$id];
                        $sub = $p['price'] * $qty;
                        $total += $sub; ?><tr>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>₦<?= number_format($p['price'], 2) ?></td>
                            <td><input style="width:80px" type="number" min="1" name="quantity[<?= $id ?>]" value="<?= $qty ?>"></td>
                            <td>₦<?= number_format($sub, 2) ?></td>
                            <td><a class="btn btn-danger" href="remove_from_cart.php?id=<?= $id ?>">Remove</a></td>
                        </tr><?php endforeach; ?>
                </table>
                <button>Update Cart</button> <a class="btn btn-secondary" href="index.php">Continue Shopping</a> <a class="btn btn-success" href="checkout.php">Checkout</a>
                <div class="total">Total: ₦<?= number_format($total, 2) ?></div>
            </form><?php endif; ?>
    </main>
</body>

</html>