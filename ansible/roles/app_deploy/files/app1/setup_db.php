<?php
require_once __DIR__ . '/includes/db.php';

echo "Initializing Database...\n";

// Drop tables
$pdo->exec("DROP TABLE IF EXISTS reviews");
$pdo->exec("DROP TABLE IF EXISTS products");
$pdo->exec("DROP TABLE IF EXISTS categories");
$pdo->exec("DROP TABLE IF EXISTS users");

// Create Users Table
$pdo->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Create Categories Table (Self-referencing for infinite nesting)
$pdo->exec("CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    parent_id INTEGER,
    description TEXT,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
)");

// Create Products Table
$pdo->exec("CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    description TEXT,
    image TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
)");

echo "Tables created.\n";

// ---------------------------------------------------------
// Seed Users
// ---------------------------------------------------------
// Admin
$admin_pass = 'admin123';
$pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['admin', 'admin@secureshop.com', $admin_pass, 'admin']);

// User
$user_pass = 'user123';
$pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['john_doe', 'john@example.com', $user_pass, 'user']);

echo "Users seeded.\n";

// ---------------------------------------------------------
// Seed Categories (Deep Nesting Logic)
// ---------------------------------------------------------
// We want deeply nested categories to challenge crawlers.
// Level 1: Departments (Electronics, Home, Fashion)
// Level 2: Types (Computers, Audio, Cameras)
// Level 3: Subtypes (Laptops, Desktops, Headphones, Speakers)
// Level 4: Specs (Gaming, Business, Wireless, Wired)
// Level 5: Brands/Series (High-End, Budget, Pro)

$departments = ['Electronics', 'Home & Garden', 'Fashion', 'Sports', 'Automotive'];

function create_subcategories($parent_id, $current_depth, $max_depth)
{
    global $pdo;
    if ($current_depth > $max_depth)
        return;

    // Number of subcategories for this node
    $count = rand(2, 4);

    for ($i = 1; $i <= $count; $i++) {
        $name = "Category Level $current_depth - Item $i";
        // Make some names realistic based on depth would be cool, but generic is fine for structural complexity
        if ($current_depth == 2) {
            $types = ['Computers', 'Audio', 'Visual', 'Network', 'Gadgets'];
            $name = $types[array_rand($types)] . " " . $i;
        }

        $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
        $stmt->execute([$name, $parent_id]);
        $new_id = $pdo->lastInsertId();

        // Populate products in this category
        if (rand(0, 10) > 3) { // 70% chance to have products
            create_products($new_id);
        }

        create_subcategories($new_id, $current_depth + 1, $max_depth);
    }
}

function create_products($cat_id)
{
    global $pdo;
    $count = rand(1, 5);
    for ($i = 0; $i < $count; $i++) {
        $price = rand(10, 2000) + 0.99;
        $name = "Product " . substr(md5(uniqid()), 0, 6);
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, price, description, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $cat_id,
            $name,
            $price,
            "This is a high quality $name. Best in class performance.",
            "assets/images/placeholder.jpg"
        ]);
    }
}

foreach ($departments as $dept) {
    $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, NULL)");
    $stmt->execute([$dept]);
    $root_id = $pdo->lastInsertId();

    // Create nested structure
    create_subcategories($root_id, 2, 5); // Depth 5
}

echo "Categories and Products seeded (Deep Nesting Complete).\n";
echo "Done.\n";
?>