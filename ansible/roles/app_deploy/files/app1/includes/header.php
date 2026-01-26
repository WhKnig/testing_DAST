<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Flash messages
$flash_error = $_SESSION['flash_error'] ?? null;
$flash_success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$current_user = null;
if (isset($_SESSION['user_id'])) {
    // VULN: Simple query, but we might add SQLi here if we want headers to be vuln? 
    // Nah, keeping header clean-ish for now, focus vuln on parameters.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureShop - Premium Electronics</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="site-header">
        <div class="container nav-wrapper">
            <a href="index.php" class="brand">
                <i class="fas fa-cube"></i> SecureShop
            </a>

            <div class="search-bar">
                <form action="search.php" method="GET">
                    <i class="fas fa-search search-icon"></i>
                    <!-- VULN: XSS reflected in search.php -->
                    <input type="text" name="q" class="search-input"
                        placeholder="Search products, brands, and categories...">
                </form>
            </div>

            <nav class="nav-links">
                <?php if ($current_user): ?>
                    <a href="cart.php" class="btn btn-outline">
                        <i class="fas fa-shopping-cart"></i> Cart
                    </a>
                    <div class="dropdown">
                        <a href="profile.php" class="btn btn-primary">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($current_user['username']); ?>
                        </a>
                        <!-- Logout would validly be here -->
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Log in</a>
                    <a href="register.php" class="btn btn-primary">Sign up</a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Deep Category Menu -->
        <div class="container category-nav">
            <ul class="category-list">
                <?php
                // Fetch top-level categories
                $cats = get_categories(null);
                foreach ($cats as $cat): ?>
                    <li class="category-item">
                        <a href="category.php?id=<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </header>

    <main class="container">
        <?php if ($flash_error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($flash_error); ?>
            </div>
        <?php endif; ?>

        <?php if ($flash_success): ?>
            <!-- VULN: Potential XSS if success message isn't sanitized? Let's keep it safe for now to focus on intentional ones -->
            <div class="alert" style="background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;">
                <?php echo htmlspecialchars($flash_success); ?>
            </div>
        <?php endif; ?>