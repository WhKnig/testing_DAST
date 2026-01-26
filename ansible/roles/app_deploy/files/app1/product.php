<?php
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Product ID required");
}

// VULN: SQL Injection
// Instead of prepared statement, we'll concatenate directly if the input looks 'safe' enough to pass basic filters but fail advanced checks?
// Or just plain simple SQLi. "Real" sites often have it due to negligence.
// Let's do simple SQLi.
global $pdo;
$sql = "SELECT * FROM products WHERE id = " . $id;

try {
    $stmt = $pdo->query($sql);
    $product = $stmt ? $stmt->fetch() : null;
} catch (Exception $e) {
    // VULN: Detailed error exposure?
    $product = null;
    echo "<!-- SQL Error: " . $e->getMessage() . " -->";
}

if (!$product) {
    echo "<div class='container'><div class='alert alert-danger'>Product not found.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$cat_stmt->execute([$product['category_id']]);
$category = $cat_stmt->fetch();
?>

<div class="container" style="margin-bottom: 2rem;">
    <div style="color: var(--text-muted); font-size: 0.9rem;">
        <a href="index.php">Home</a>
        <?php if ($category): ?>
            &gt; <a href="category.php?id=<?php echo $category['id']; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </a>
        <?php endif; ?>
        &gt;
        <?php echo htmlspecialchars($product['name']); ?>
    </div>
</div>

<div class="container grid" style="grid-template-columns: 1fr 1fr; align-items: start;">
    <div style="background: white; padding: 2rem; border-radius: var(--radius); border: 1px solid var(--border);">
        <img src="<?php echo htmlspecialchars($product['image']); ?>"
            alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; border-radius: var(--radius);">
    </div>

    <div>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($product['name']); ?>
        </h1>
        <div class="product-price" style="font-size: 2rem; margin-bottom: 1.5rem;">$
            <?php echo number_format($product['price'], 2); ?>
        </div>

        <p style="line-height: 1.8; color: var(--text-muted); margin-bottom: 2rem;">
            <?php echo htmlspecialchars($product['description']); ?>
        </p>

        <form action="api/cart.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <input type="number" name="quantity" value="1" min="1" class="form-control" style="width: 100px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Add to Cart</button>
            </div>
        </form>

        <div style="padding: 1.5rem; background: var(--bg-body); border-radius: var(--radius);">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;"><i class="fas fa-truck"></i> Fast
                Delivery</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Free shipping on orders over $100. Delivered in 2-4
                business days.</p>
        </div>
    </div>
</div>

<section class="container" style="margin-top: 4rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">Customer Reviews</h2>
    <div
        style="background: white; padding: 2rem; border-radius: var(--radius); border: 1px solid var(--border); text-align: center; color: var(--text-muted);">
        No reviews yet. Be the first to review!
        <!-- Potential stored XSS here if we implemented review submission -->
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>