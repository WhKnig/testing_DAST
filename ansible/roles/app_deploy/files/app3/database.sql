-- Database schema for SecureBank (Vulnerable Banking Application)
-- Contains 140+ vulnerabilities focused on SQLi, XSS, CSRF

-- Users table with vulnerabilities
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),  -- XSS vulnerability
    phone VARCHAR(20),
    address TEXT,  -- XSS vulnerability
    role VARCHAR(20) DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Accounts table with vulnerabilities
CREATE TABLE IF NOT EXISTS accounts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    account_number VARCHAR(20) UNIQUE,
    account_type VARCHAR(20) DEFAULT 'checking',
    balance DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active'
);

-- Transactions table with vulnerabilities
CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    from_account INTEGER,
    to_account INTEGER,
    amount DECIMAL(15, 2),
    description TEXT,  -- XSS vulnerability
    transaction_type VARCHAR(20) DEFAULT 'transfer',
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL
);

-- Loans table with vulnerabilities
CREATE TABLE IF NOT EXISTS loans (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    loan_type VARCHAR(50),
    amount DECIMAL(15, 2),
    interest_rate DECIMAL(5, 2),
    term_months INTEGER,
    status VARCHAR(20) DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Credit cards table with vulnerabilities
CREATE TABLE IF NOT EXISTS credit_cards (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    card_number VARCHAR(20),  -- Encrypted in real apps, but vulnerable here
    card_type VARCHAR(20),
    expiry_date DATE,
    cvv VARCHAR(4),  -- Should not be stored, but vulnerable here
    credit_limit DECIMAL(10, 2),
    balance DECIMAL(10, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'active'
);

-- Investment portfolios with XSS vulnerabilities
CREATE TABLE IF NOT EXISTS investments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    investment_name VARCHAR(100),  -- XSS vulnerability
    investment_type VARCHAR(50),
    amount_invested DECIMAL(15, 2),
    current_value DECIMAL(15, 2),
    purchase_date DATE,
    status VARCHAR(20) DEFAULT 'active'
);

-- Messages with XSS and CSRF vulnerabilities
CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER REFERENCES users(id),
    recipient_id INTEGER REFERENCES users(id),
    subject VARCHAR(255),  -- XSS vulnerability
    message TEXT,  -- XSS vulnerability
    is_read BOOLEAN DEFAULT FALSE,
    priority VARCHAR(20) DEFAULT 'normal',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User settings with CSRF vulnerabilities
CREATE TABLE IF NOT EXISTS user_settings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    setting_key VARCHAR(100),
    setting_value TEXT  -- Potential XSS if displayed
);

-- Audit logs with XSS vulnerabilities
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    action VARCHAR(100),  -- XSS vulnerability when displayed in admin panel
    details TEXT,  -- XSS vulnerability
    ip_address VARCHAR(45),
    user_agent TEXT,  -- XSS vulnerability
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bill payments with XSS vulnerabilities
CREATE TABLE IF NOT EXISTS bill_payments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    account_id INTEGER REFERENCES accounts(id),
    payee VARCHAR(100),  -- XSS vulnerability
    amount DECIMAL(10, 2),
    payment_date DATE,
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT  -- XSS vulnerability
);

-- External transfers with XSS vulnerabilities
CREATE TABLE IF NOT EXISTS external_transfers (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    source_account INTEGER REFERENCES accounts(id),
    recipient_name VARCHAR(100),  -- XSS vulnerability
    recipient_account VARCHAR(50),
    recipient_bank VARCHAR(100),  -- XSS vulnerability
    amount DECIMAL(15, 2),
    transfer_reason TEXT,  -- XSS vulnerability
    status VARCHAR(20) DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO users (username, email, password, full_name, phone, address, role) VALUES
('admin', 'admin@securebank.com', 'admin123', '<script>alert("Admin XSS")</script>Admin User', '123-456-7890', '123 Admin St, Admin City', 'admin'),
('john_doe', 'john@securebank.com', 'password123', 'John Doe', '111-222-3333', '456 Main St, Anytown', 'user'),
('jane_smith', 'jane@securebank.com', 'password456', 'Jane Smith', '444-555-6666', '<img src=x onerror=alert("XSS in address")>789 Oak Ave, Sometown', 'user'),
('bob_wilson', 'bob@securebank.com', 'password789', 'Bob Wilson', '777-888-9999', '321 Pine Rd, Othertown', 'user')
ON CONFLICT (username) DO NOTHING;

INSERT INTO accounts (user_id, account_number, account_type, balance) VALUES
(1, 'ACC001', 'checking', 5000.00),
(2, 'ACC002', 'savings', 10000.00),
(3, 'ACC003', 'checking', 7500.00),
(4, 'ACC004', 'business', 15000.00),
(2, 'ACC005', 'credit', 2000.00)
ON CONFLICT DO NOTHING;

INSERT INTO transactions (from_account, to_account, amount, description, transaction_type, status) VALUES
(1, 2, 100.00, 'Transfer to John', 'transfer', 'completed'),
(2, 3, 50.00, '<script>alert("XSS in transaction")</script>', 'transfer', 'completed'),
(3, 4, 200.00, 'Business expense', 'transfer', 'completed'),
(4, 1, 75.00, 'Consulting fee', 'transfer', 'completed'),
(2, 4, 150.00, 'Loan payment', 'transfer', 'completed')
ON CONFLICT DO NOTHING;

INSERT INTO loans (user_id, loan_type, amount, interest_rate, term_months) VALUES
(2, 'Personal', 5000.00, 5.5, 36),
(3, 'Auto', 15000.00, 4.2, 60),
(4, 'Home', 200000.00, 3.8, 360)
ON CONFLICT DO NOTHING;

INSERT INTO credit_cards (user_id, card_number, card_type, credit_limit, balance) VALUES
(2, '1234567890123456', 'Visa', 5000.00, 1200.00),
(3, '9876543210987654', 'MasterCard', 7500.00, 3000.00),
(4, '1111222233334444', 'American Express', 10000.00, 4500.00)
ON CONFLICT DO NOTHING;

INSERT INTO investments (user_id, investment_name, investment_type, amount_invested, current_value) VALUES
(2, 'Tech Stocks', 'Stocks', 5000.00, 5500.00),
(3, '<script>alert("XSS in investment")</script>', 'Bonds', 10000.00, 10200.00),
(4, 'Mutual Fund', 'Funds', 15000.00, 16000.00)
ON CONFLICT DO NOTHING;

INSERT INTO messages (sender_id, recipient_id, subject, message) VALUES
(2, 1, 'Account Inquiry', 'Hi, I have a question about my account.'),
(3, 1, '<script>alert("XSS in message subject")</script>', 'Admin, please check my loan application.'),
(4, 2, 'Business Transfer', 'Can you help me with a business transfer?'),
(1, 3, 'System Update', '<img src=x onerror=alert("XSS in message body")>System will be down for maintenance.')
ON CONFLICT DO NOTHING;

INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES
(1, 'theme', 'dark'),
(1, 'notifications', 'email'),
(2, 'theme', 'light'),
(3, 'language', 'en'),
(4, 'two_factor', 'enabled')
ON CONFLICT DO NOTHING;

INSERT INTO bill_payments (user_id, account_id, payee, amount, notes) VALUES
(2, 2, 'Electric Company', 120.00, 'Monthly electricity bill'),
(3, 3, '<script>alert("XSS in payee")</script>', 85.50, 'Gas bill'),
(4, 4, 'Internet Provider', 65.00, 'Fiber internet service')
ON CONFLICT DO NOTHING;

INSERT INTO external_transfers (user_id, source_account, recipient_name, recipient_account, recipient_bank, amount, transfer_reason) VALUES
(2, 2, 'Landlord Rent', '987654321', 'First National Bank', 1200.00, 'Monthly rent'),
(3, 3, '<script>alert("XSS in recipient")</script>', '111222333', 'Secure Bank', 300.00, 'Gift'),
(4, 4, 'Contractor Payment', '444555666', 'City Bank', 2500.00, 'Renovation work')
ON CONFLICT DO NOTHING;

-- Audit logs with potential XSS
INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES
(2, 'login', 'User logged in successfully', '192.168.1.100', 'Mozilla/5.0...'),
(3, 'transfer', 'Made transfer of $100', '192.168.1.101', '<script>alert("XSS in user agent")</script>'),
(4, 'account_view', 'Viewed account details', '192.168.1.102', 'Mozilla/5.0...'),
(2, 'settings_update', 'Updated notification settings', '192.168.1.100', 'Mozilla/5.0...')
ON CONFLICT DO NOTHING;

-- Additional vulnerabilities table for advanced testing
CREATE TABLE IF NOT EXISTS advanced_bank_vulns (
    id SERIAL PRIMARY KEY,
    user_input TEXT,  -- For SQL injection testing
    display_content TEXT,  -- For XSS testing
    action_taken VARCHAR(255),  -- For CSRF testing
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample vulnerable data
INSERT INTO advanced_bank_vulns (user_input, display_content, action_taken) VALUES
('1 OR 1=1', '<script>alert("Advanced Bank XSS")</script>', 'delete_all_accounts'),
('admin''--', '<img src=x onerror=alert("Advanced Bank XSS")>', 'modify_permissions'),
('1; DROP TABLE transactions--', '<svg onload=alert("SVG XSS Bank")>', 'transfer_all_funds'),
('SELECT * FROM users WHERE id = 1', '<iframe src="javascript:alert(`XSS`)"></iframe>', 'view_private_info')
ON CONFLICT DO NOTHING;