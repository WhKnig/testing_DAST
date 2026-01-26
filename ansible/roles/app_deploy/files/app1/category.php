<?php
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<div class='container'><div class='alert alert-danger'>Category not specified.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch Category info
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    echo "<div class='container'><div class='alert alert-danger'>Category not found.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Breadcrumbs
$crumbs = get_breadcrumbs($id);

// Check if there are subcategories
$subcats = get_categories($id);

// Fetch products in this category
$products = get_products_by_category($id);

?>

<div class="container" style="margin-bottom: 2rem;">
    <div style="color: var(--text-muted); font-size: 0.9rem;">
        <a href="index.php">Home</a>
        <?php foreach ($crumbs as $crumb): ?>
            &gt; <a href="category.php?id=<?php echo $crumb['id']; ?>">
                <?php echo htmlspecialchars($crumb['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<section style="margin-bottom: 3rem;">
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">
        <?php echo htmlspecialchars($category['name']); ?>
    </h1>
    <?php if ($category['description']): ?>
        <p style="color: var(--text-muted); font-size: 1.1rem;">
            <?php echo htmlspecialchars($category['description']); ?>
        </p>
    <?php endif; ?>
</section>

<?php if ($subcats): ?>
    <section style="margin-bottom: 3rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">Subcategories</h2>
        <div class="grid grid-cols-4">
            <?php foreach ($subcats as $sub): ?>
                <a href="category.php?id=<?php echo $sub['id']; ?>" class="product-card"
                    style="align-items: center; justify-content: center; padding: 2rem; background: var(--bg-body); border: none;">
                    <div style="font-size: 1.25rem; font-weight: 600; text-align: center;">
                        <?php echo htmlspecialchars($sub['name']); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section>
    <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">Products</h2>

    <?php if (empty($products) && empty($subcats)): ?>
        <p style="color: var(--text-muted);">No products found in this category.</p>
    <?php elseif ($products): ?>
        <div class="grid grid-cols-4">
            <?php foreach ($products as $product): ?>
                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <div class="product-info">
                        <div class="product-category">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </div>
                        <div class="product-title">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </div>
                        <div class="product-price">$
                            <?php echo number_format($product['price'], 2); ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>