import sqlite3
import os
import random
import hashlib
from datetime import datetime, timedelta

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_FILE = os.path.join(BASE_DIR, 'forum.db')

print(f"Initializing Forum DB at {DB_FILE}...")

if os.path.exists(DB_FILE):
    os.remove(DB_FILE)

conn = sqlite3.connect(DB_FILE)
cursor = conn.cursor()

# Enable Foreign Keys
cursor.execute("PRAGMA foreign_keys = ON")

# 1. Users
cursor.execute("""
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT,
    role TEXT DEFAULT 'user',
    reputation INTEGER DEFAULT 0,
    avatar TEXT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
""")

# 2. Categories
cursor.execute("""
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    slug TEXT NOT NULL UNIQUE
)
""")

# 3. Threads
cursor.execute("""
CREATE TABLE threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    user_id INTEGER,
    title TEXT NOT NULL,
    views INTEGER DEFAULT 0,
    is_pinned BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)
""")

# 4. Posts
cursor.execute("""
CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER,
    user_id INTEGER,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES threads(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)
""")

# Seed Users
roles = ['admin', 'moderator', 'user']
users = [
    ('admin', 'admin123', 'admin@corp.com', 'admin'),
    ('moderator', 'mod123', 'mod@corp.com', 'moderator'),
    ('alice', 'alice123', 'alice@corp.com', 'user'),
    ('bob', 'bob123', 'bob@corp.com', 'user'),
    ('charlie', 'charlie123', 'charlie@corp.com', 'user'),
]

for u in users:
    cursor.execute("INSERT INTO users (username, password, email, role, reputation, avatar) VALUES (?, ?, ?, ?, ?, ?)",
                   (u[0], u[1], u[2], u[3], random.randint(0, 100), f"https://ui-avatars.com/api/?name={u[0]}"))

# Get User IDs
cursor.execute("SELECT id FROM users")
user_ids = [row[0] for row in cursor.fetchall()]

# Seed Categories
categories = [
    ('Announcements', 'Official news and updates', 'announcements'),
    ('General Discussion', 'Talk about anything related to the company', 'general'),
    ('Technical Support', 'Get help with your products', 'tech-support'),
    ('Feature Requests', 'Suggest new ideas', 'features'),
    ('Off-Topic', 'Anything else', 'off-topic')
]

for c in categories:
    cursor.execute("INSERT INTO categories (name, description, slug) VALUES (?, ?, ?)", c)

# Get Category IDs
cursor.execute("SELECT id FROM categories")
cat_ids = [row[0] for row in cursor.fetchall()]

# Seed Threads and Posts (Complex Structure)
titles = [
    "How do I reset my password?",
    "New version 2.0 released!",
    "Feature Request: Dark Mode",
    "Bug in the login page",
    "Best practices for security",
    "Weekly standup notes",
    "Help with API integration",
    "Server downtime planned",
    "Welcome to the new employees",
    "Coffee machine broken again"
]

print("Seeding threads...")
for _ in range(30):
    cat = random.choice(cat_ids)
    user = random.choice(user_ids)
    title = random.choice(titles) + f" [{random.randint(100, 999)}]"
    
    cursor.execute("INSERT INTO threads (category_id, user_id, title, views) VALUES (?, ?, ?, ?)",
                   (cat, user, title, random.randint(10, 5000)))
    thread_id = cursor.lastrowid
    
    # Original Post
    content = f"This is the start of the thread about {title}. " * 5
    cursor.execute("INSERT INTO posts (thread_id, user_id, content) VALUES (?, ?, ?)",
                   (thread_id, user, content))
    
    # Replies
    num_replies = random.randint(0, 15)
    for _ in range(num_replies):
        r_user = random.choice(user_ids)
        r_content = f"Reply number {random.randint(1, 1000)}. Checking in."
        cursor.execute("INSERT INTO posts (thread_id, user_id, content) VALUES (?, ?, ?)",
                       (thread_id, r_user, r_content))

conn.commit()
conn.close()
print("Database initialized.")
