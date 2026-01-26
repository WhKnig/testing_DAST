<?php
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULN: SQL Injection
    // Direct concatenation for authentication bypass capability (e.g. ' OR '1'='1)
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";

    try {
        $stmt = $pdo->query($sql);
        $user = $stmt ? $stmt->fetch() : null;

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['username']) . "!";
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['flash_error'] = "Invalid credentials.";
        }
    } catch (Exception $e) {
        // VULN: Error info leak
        $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
    }
}
?>

<div class="container" style="max-width: 450px; margin-top: 4rem; margin-bottom: 4rem;">
    <div class="form-card">
        <h2 style="text-align: center; margin-bottom: 2rem; font-size: 1.75rem;">Welcome Back</h2>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">Log In</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
            Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600;">Sign up</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>