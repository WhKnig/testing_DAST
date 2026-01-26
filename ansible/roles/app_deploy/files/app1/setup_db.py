import sqlite3
import os
import random
import hashlib

# Path to the database file
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_FILE = os.path.join(BASE_DIR, 'shop.db')

print(f"Initializing Database at {DB_FILE}...")

if os.path.exists(DB_FILE):
    os.remove(DB_FILE)

conn = sqlite3.connect(DB_FILE)
cursor = conn.cursor()

# Enable foreign keys
cursor.execute("PRAGMA foreign_keys = ON")

# Create Tables
cursor.execute("""
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
""")

cursor.execute("""
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    parent_id INTEGER,
    description TEXT,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
)
""")

cursor.execute("""
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    description TEXT,
    image TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
)
""")

cursor.execute("""
CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    user_id INTEGER,
    rating INTEGER,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)
""")

# Seed Users
cursor.execute("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)", 
               ('admin', 'admin@secureshop.com', 'admin123', 'admin'))
cursor.execute("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)", 
               ('john_doe', 'john@example.com', 'user123', 'user'))

# Seed Categories (Deep Nesting)
departments = ['Electronics', 'Home & Garden', 'Fashion', 'Sports', 'Automotive']

def create_subcategories(parent_id, current_depth, max_depth):
    if current_depth > max_depth:
        return

    count = random.randint(2, 4)
    
    for i in range(1, count + 1):
        name = f"Category Level {current_depth} - Item {i}"
        if current_depth == 2:
            types = ['Computers', 'Audio', 'Visual', 'Network', 'Gadgets']
            name = f"{random.choice(types)} {i}"
        
        cursor.execute("INSERT INTO categories (name, parent_id) VALUES (?, ?)", (name, parent_id))
        new_id = cursor.lastrowid
        
        # Populate products
        if random.random() > 0.3: # 70% chance
            create_products(new_id)
            
        create_subcategories(new_id, current_depth + 1, max_depth)

def create_products(cat_id):
    count = random.randint(1, 5)
    for _ in range(count):
        price = round(random.uniform(10, 2000), 2)
        random_hash = hashlib.md5(str(random.random()).encode()).hexdigest()[:6]
        name = f"Product {random_hash}"
        cursor.execute("INSERT INTO products (category_id, name, price, description, image) VALUES (?, ?, ?, ?, ?)", 
                       (cat_id, name, price, f"This is a high quality {name}.", "assets/images/placeholder.jpg"))

for dept in departments:
    cursor.execute("INSERT INTO categories (name, parent_id) VALUES (?, NULL)", (dept,))
    root_id = cursor.lastrowid
    create_subcategories(root_id, 2, 5)

conn.commit()
conn.close()

print("Database initialized and seeded successfully.")
