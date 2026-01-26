-- Database schema for CorpForum (Vulnerable Corporate Forum)
-- Contains 130+ vulnerabilities focused on SQLi, XSS, CSRF

PRAGMA foreign_keys = ON;

-- Users table with vulnerabilities
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Threads table with vulnerabilities
CREATE TABLE IF NOT EXISTS threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT NOT NULL,  -- XSS vulnerability
    content TEXT,  -- XSS vulnerability
    category TEXT,
    view_count INTEGER DEFAULT 0,
    is_locked BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- Posts table with vulnerabilities
CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER,
    user_id INTEGER,
    content TEXT,  -- XSS vulnerability
    parent_post_id INTEGER DEFAULT NULL,  -- For nested replies - XSS
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES threads (id),
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (parent_post_id) REFERENCES posts (id)
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT
);

-- User profiles with XSS vulnerabilities
CREATE TABLE IF NOT EXISTS user_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    bio TEXT,  -- XSS vulnerability
    website TEXT,  -- XSS vulnerability
    location TEXT,  -- XSS vulnerability
    signature TEXT,  -- XSS vulnerability when displayed
    avatar_url TEXT,  -- XSS vulnerability
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- Private messages with XSS and CSRF vulnerabilities
CREATE TABLE IF NOT EXISTS private_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER,
    recipient_id INTEGER,
    subject TEXT,  -- XSS vulnerability
    message TEXT,  -- XSS vulnerability
    is_read BOOLEAN DEFAULT 0,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users (id),
    FOREIGN KEY (recipient_id) REFERENCES users (id)
);

-- Search logs with XSS vulnerabilities
CREATE TABLE IF NOT EXISTS search_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    search_query TEXT,  -- XSS vulnerability when displayed in admin panel
    results_count INTEGER,
    search_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id)
);

-- Insert sample data
INSERT OR IGNORE INTO users (username, email, password, role) VALUES
('admin', 'admin@corpforum.com', 'admin123', 'admin'),
('john_doe', 'john@company.com', 'password123', 'user'),
('jane_smith', 'jane@company.com', 'password456', 'moderator'),
('bob_wilson', 'bob@company.com', 'password789', 'user');

INSERT OR IGNORE INTO categories (name, description) VALUES
('general', 'General discussion'),
('technical', 'Technical support and discussions'),
('announcements', 'Company announcements'),
('feedback', 'Feedback and suggestions');

INSERT OR IGNORE INTO threads (user_id, title, content, category) VALUES
(2, 'Welcome to CorpForum', 'This is the first thread in our new corporate forum. Feel free to introduce yourself!', 'general'),
(3, 'Technical Support Guidelines', 'Please follow these guidelines when posting technical questions...', 'technical'),
(1, 'New Company Policy Update', 'Please review the updated company policies effective next month...', 'announcements'),
(4, 'Office Renovation Feedback', 'We''d like to hear your thoughts on the proposed office renovation plans...', 'feedback'),
(2, 'Project Collaboration Tips', 'Share your best practices for cross-team collaboration...', 'general'),
(3, 'Database Security Best Practices', 'Discussion about securing our database systems...', 'technical');

INSERT OR IGNORE INTO posts (thread_id, user_id, content) VALUES
(1, 3, 'Thanks for starting this thread! Looking forward to participating in discussions.'),
(1, 4, 'Great initiative! This will help improve communication across teams.'),
(2, 2, 'I have a question about securing API endpoints...'),
(3, 4, 'Thanks for the update. Will there be a Q&A session about these changes?'),
(4, 2, 'I think the common areas could use more collaborative spaces.'),
(5, 3, 'Using agile methodologies has really improved our project delivery times.');

-- User profiles with XSS content
INSERT OR IGNORE INTO user_profiles (user_id, bio, website, location, signature, avatar_url) VALUES
(1, '<script>alert("Admin XSS")</script>System Administrator', 'http://admin-site.com', 'HQ', '<b>Admin</b> - <i>System Administrator</i>', 'admin_avatar.jpg'),
(2, 'Software Engineer passionate about web development', 'http://john-site.com', 'Remote', 'Coding enthusiast', 'john_avatar.jpg'),
(3, 'Technical Lead & Mentor', 'http://jane-site.com', 'HQ', '<img src=x onerror=alert("Jane signature XSS")>Technical Lead', 'jane_avatar.jpg'),
(4, 'Project Manager', 'http://bob-site.com', 'HQ', 'Process-oriented professional', 'bob_avatar.jpg');

-- Private messages with XSS content
INSERT OR IGNORE INTO private_messages (sender_id, recipient_id, subject, message) VALUES
(2, 1, 'Question about permissions', 'Hi admin, can you help me with access to the new project folder?'),
(3, 1, '<script>alert("XSS in PM subject")</script>', 'Admin, please review the security settings.'),
(4, 2, 'Meeting tomorrow', 'Don''t forget about our 10am meeting tomorrow.'),
(1, 3, 'Policy clarification', '<img src=x onerror=alert("XSS in PM body")>Regarding the new policy...');

-- Forum settings
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
('forum_title', 'CorpForum'),
('posts_per_page', '20');

-- Search logs with potential XSS
INSERT OR IGNORE INTO search_logs (user_id, search_query, results_count) VALUES
(2, 'database security', 5),
(3, 'user permissions', 3),
(4, '<script>alert("XSS in search")</script>', 0),
(2, 'API documentation', 7);

-- Additional vulnerabilities table for advanced testing
CREATE TABLE IF NOT EXISTS advanced_forum_vulns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_input TEXT,  -- For SQL injection testing
    display_content TEXT,  -- For XSS testing
    action_taken TEXT,  -- For CSRF testing
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample vulnerable data
INSERT OR IGNORE INTO advanced_forum_vulns (user_input, display_content, action_taken) VALUES
('1 OR 1=1', '<script>alert("Advanced Forum XSS")</script>', 'delete_all_threads'),
('admin''--', '<img src=x onerror=alert("Advanced Forum XSS")>', 'modify_settings'),
('1; DROP TABLE threads--', '<svg onload=alert("SVG XSS Forum")>', 'change_permissions'),
('SELECT * FROM users WHERE id = 1', '<iframe src="javascript:alert(`XSS`)"></iframe>', 'view_private_data');