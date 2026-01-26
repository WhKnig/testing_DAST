<?php
require_once __DIR__ . '/includes/header.php';

$cart = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;

if ($cart) {
    // Inefficient query in loop but simple for mock
    foreach ($cart as $pid => $qty) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $p = $stmt->fetch();
        if ($p) {
            $p['qty'] = $qty;
            $products[] = $p;
            $total += $p['price'] * $qty;
        }
    }
}
?>

<div class="container" style="margin-bottom: 4rem;">
    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem;">Shopping Cart</h1>

    <?php if (empty($products)): ?>
        <div
            style="text-align: center; padding: 4rem; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Your cart is empty.</p>
            <a href="index.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 2rem; display: grid;">
            <div
                style="background: var(--bg-card); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: var(--bg-body); border-bottom: 1px solid var(--border);">
                        <tr>
                            <th style="text-align: left; padding: 1rem;">Product</th>
                            <th style="padding: 1rem;">Quantity</th>
                            <th style="text-align: right; padding: 1rem;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>"
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <div>
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">$
                                            <?php echo number_format($p['price'], 2); ?> / unit
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php echo $p['qty']; ?>
                                </td>
                                <td style="padding: 1rem; text-align: right; font-weight: 600;">
                                    $
                                    <?php echo number_format($p['price'] * $p['qty'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-card" style="margin: 0; padding: 1.5rem; height: fit-content;">
                <h3 style="margin-bottom: 1rem;">Order Summary</h3>
                <div
                    style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--text-muted);">
                    <span>Subtotal</span>
                    <span>$
                        <?php echo number_format($total, 2); ?>
                    </span>
                </div>
                <div
                    style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; color: var(--text-muted);">
                    <span>Tax (Est.)</span>
                    <span>$0.00</span>
                </div>
                <div
                    style="display: flex; justify-content: space-between; margin-bottom: 2rem; font-size: 1.25rem; font-weight: 700; border-top: 1px solid var(--border); padding-top: 1rem;">
                    <span>Total</span>
                    <span>$
                        <?php echo number_format($total, 2); ?>
                    </span>
                </div>
                <button class="btn btn-primary" style="width: 100%;">Checkout</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>