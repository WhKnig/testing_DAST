<?php
require_once __DIR__ . '/includes/header.php';
$featured = get_featured_products();
?>

<div class="hero"
    style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; padding: 4rem 2rem; border-radius: var(--radius); margin-bottom: 3rem; text-align: center;">
    <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 1rem;">Future Tech is Here</h1>
    <p style="font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9;">Discover the latest in premium electronics and
        accessories.</p>
    <a href="search.php?q=new" class="btn"
        style="background: white; color: var(--primary); padding: 1rem 2rem; font-weight: 700; font-size: 1.125rem;">Shop
        Now</a>
</div>

<section>
    <h2
        style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; display: inline-block;">
        Featured Products</h2>

    <div class="grid grid-cols-4">
        <?php foreach ($featured as $product): ?>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                <img src="<?php echo htmlspecialchars($product['image']); ?>"
                    alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                <div class="product-info">
                    <div class="product-category">Featured</div>
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
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>