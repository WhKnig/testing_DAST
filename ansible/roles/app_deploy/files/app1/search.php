<?php
require_once __DIR__ . '/includes/header.php';

$query = $_GET['q'] ?? '';

// VULN: Reflected XSS
// We are echoing $query without htmlspecialchars() inside the "Search Results" header.
?>

<div class="container hero"
    style="background: var(--bg-card); padding: 3rem; border-radius: var(--radius); margin-bottom: 3rem; text-align: center; border: 1px solid var(--border);">
    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 1rem;">Search Results</h1>
    <?php if ($query): ?>
        <p style="font-size: 1.1rem; color: var(--text-muted);">
            <!-- VULN IS HERE -->
            Showing results for: <strong>
                <?php echo $query; ?>
            </strong>
        </p>
    <?php endif; ?>
</div>

<div class="container">
    <?php if ($query):
        // Mock search logic
        // We might want actual products to show up to look real
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? LIMIT 12");
        $term = "%$query%";
        $stmt->execute([$term, $term]);
        $results = $stmt->fetchAll();
        ?>
        <?php if ($results): ?>
            <div class="grid grid-cols-4">
                <?php foreach ($results as $product): ?>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <div class="product-info">
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
        <?php else: ?>
            <div style="text-align: center; color: var(--text-muted); padding: 4rem;">
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>No products found matching your criteria.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>