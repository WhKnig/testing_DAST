<?php
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Basic validation
    if ($username && $email && $password) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = "Username already taken.";
        } else {
            // VULN: Stored XSS possible in username/email if not properly handled on output (we're using htmlspecialchars in header, so maybe safe there, but Profile might be vulnerable?)
            // Let's stick to standard insecure practices: weak password hashing (none for this testbench simplification/vulnerability).
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password]);

            $_SESSION['flash_success'] = "Account created! Please log in.";
            header('Location: login.php');
            exit;
        }
    } else {
        $_SESSION['flash_error'] = "All fields are required.";
    }
}
?>

<div class="container" style="max-width: 450px; margin-top: 4rem; margin-bottom: 4rem;">
    <div class="form-card">
        <h2 style="text-align: center; margin-bottom: 2rem; font-size: 1.75rem;">Create Account</h2>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">Sign Up</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
            Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 600;">Log in</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>