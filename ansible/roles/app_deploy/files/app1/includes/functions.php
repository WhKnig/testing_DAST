<?php

function get_categories($parent_id = null)
{
    global $pdo;
    if ($parent_id === null) {
        // SQLite handling for NULL comparison
        $sql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        $sql = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$parent_id]);
    }
    return $stmt->fetchAll();
}

function get_products_by_category($category_id)
{
    global $pdo;
    // We might want to include subcategories' products too (recursive), 
    // but for simplicity let's stick to direct mapping or use a recursive query if SQLite supports it (CTE).
    // For a scanner trap, simple depth is fine.
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? LIMIT 20");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll();
}

function get_featured_products()
{
    global $pdo;
    // Just random 8 products for now
    $stmt = $pdo->query("SELECT * FROM products ORDER BY RANDOM() LIMIT 8");
    return $stmt->fetchAll();
}

function get_breadcrumbs($category_id)
{
    global $pdo;
    $crumbs = [];
    $current_id = $category_id;

    while ($current_id) {
        $stmt = $pdo->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
        $stmt->execute([$current_id]);
        $cat = $stmt->fetch();
        if ($cat) {
            array_unshift($crumbs, $cat);
            $current_id = $cat['parent_id'];
        } else {
            break;
        }
    }
    return $crumbs;
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
?>