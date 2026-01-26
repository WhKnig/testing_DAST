<?php
require_once __DIR__ . '/includes/header.php';

requireLogin();

// VULN: CSRF
// No CSRF token check here.
// An attacker can construct a form to POST to this page and change the user's email.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Potentially Stored XSS if we print email back raw (we use htmlspecialchars usually, but maybe we miss it somewhere?)
    if ($email) {
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        $_SESSION['flash_success'] = "Profile updated successfully.";
        // Refresh to see changes
        header('Location: profile.php');
        exit;
    }
}

// Fetch latest data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<div class="container" style="max-width: 600px; margin-top: 4rem; margin-bottom: 4rem;">
    <div class="form-card" style="max-width: 100%;">
        <div
            style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
            <div
                style="width: 64px; height: 64px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 1.5rem;">
                    <?php echo htmlspecialchars($user['username']); ?>
                </h1>
                <p style="color: var(--text-muted);">
                    <?php echo htmlspecialchars($user['role']); ?> account
                </p>
            </div>
            <a href="logout.php" class="btn btn-outline" style="margin-left: auto;">Log Out</a>
        </div>

        <h3 style="margin-bottom: 1rem;">Account Settings</h3>

        <form method="POST">
            <!-- VULN: No CSRF Token -->
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <!-- VULN: Reflected/Stored XSS if user puts XSS in email and we output it raw? (htmlspecialchars prevents it here) -->
                <input type="email" name="email" class="form-control"
                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>