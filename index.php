<?php
require "config/db.php";
session_start();
$cart_count = 0;
foreach (($_SESSION['cart'] ?? []) as $q) $cart_count += $q;
$r = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="topbar">
        <div class="container nav"><a class="logo" href="index.php">Alice Aura</a>
            <nav><a href="index.php">Products</a><a href="cart.php">🛒 Cart <span class="badge"><?= $cart_count ?></span></a><a href="admin/login.php">Admin</a></nav>
        </div>
    </header>
    <main class="container">
        <h1>Our Products</h1>
        <div class="grid">
            <?php while ($p = $r->fetch_assoc()): ?><div class="card">
                    <?php if (!empty($p['image'])): ?>
                        <img src="images/products/<?= htmlspecialchars(basename($p['image'])) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%;height:220px;object-fit:cover;border-radius:8px;margin-bottom:12px">
                    <?php else: ?>
                        <div style="width:100%;height:220px;background:#f1f1f1;display:flex;align-items:center;justify-content:center;border-radius:8px;margin-bottom:12px">No image</div>
                    <?php endif; ?>
                    <h2><?= htmlspecialchars($p['name']) ?></h2>
                    <p><?= htmlspecialchars($p['description']) ?></p>
                    <div class="price">₦<?= number_format($p['price'], 2) ?></div><a class="btn" href="add_to_cart.php?id=<?= $p['id'] ?>">Add to Cart</a>
                </div><?php endwhile; ?>
        </div>
    </main>
    
</body>

</html>