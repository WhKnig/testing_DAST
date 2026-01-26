<?php
$host = 'localhost'; // In a real scenario this might be an env var
$db = 'secureshop';
$user = 'root'; // Assuming default local setup or testbench user
$pass = 'root'; // Assuming default
$charset = 'utf8mb4';

// For this testbench, we might need to adjust credentials if they differ.
// Based on previous files, it seems they used SQLite in other apps or local simple creds.
// App1 (login.php) used 'config.php' and 'database.php'.
// Let's stick to SQLite for simplicity and portability within the testbench, 
// as it avoids external service dependency issues unless MySQL is guaranteed.
// Re-reading 'app2/app.py' it used 'forum.db' (sqlite).
// 'app1' originally had 'database.php'. Let's check its content if we can... 
// Actually, I deleted it. But 'app2' used SQLite. 'app3' used Postgres.
// I will use SQLite for App1 to ensure it runs self-contained without needing a MySQL server provisioning.
// This is safer for a "testbench" artifact.

$db_file = __DIR__ . '/../shop.db';

$dsn = "sqlite:$db_file";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
} catch (\PDOException $e) {
    // In a real app we wouldn't show this, but for dev/debugging:
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
?>