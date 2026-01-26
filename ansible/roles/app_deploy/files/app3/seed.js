const { Pool } = require('pg');

const pool = new Pool({
    user: 'postgres',
    host: 'localhost',
    database: 'securebank',
    password: 'password', // Standard testbench creds
    port: 5432,
});

async function seed() {
    console.log('Seeding SecureBank DB...');

    try {
        // cleanup
        await pool.query('DROP TABLE IF EXISTS transactions CASCADE');
        await pool.query('DROP TABLE IF EXISTS accounts CASCADE');
        await pool.query('DROP TABLE IF EXISTS users CASCADE');

        // Users
        await pool.query(`
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(100) NOT NULL,
                full_name VARCHAR(100),
                role VARCHAR(20) DEFAULT 'user'
            )
        `);

        // Accounts
        await pool.query(`
            CREATE TABLE accounts (
                id SERIAL PRIMARY KEY,
                user_id INTEGER REFERENCES users(id),
                type VARCHAR(20) NOT NULL,
                balance DECIMAL(12,2) DEFAULT 0.00,
                account_number VARCHAR(20) UNIQUE
            )
        `);

        // Transactions
        await pool.query(`
            CREATE TABLE transactions (
                id SERIAL PRIMARY KEY,
                account_id INTEGER REFERENCES accounts(id),
                amount DECIMAL(12,2) NOT NULL,
                description TEXT,
                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);

        // Data
        const users = [
            ['admin', 'admin123', 'System Administrator', 'admin'],
            ['john_doe', 'john123', 'John Doe', 'user'],
            ['jane_smith', 'jane123', 'Jane Smith', 'user']
        ];

        for (const u of users) {
            const res = await pool.query(
                'INSERT INTO users (username, password, full_name, role) VALUES ($1, $2, $3, $4) RETURNING id',
                u
            );
            const userId = res.rows[0].id;

            if (u[3] === 'user') {
                // Checking
                const acc1 = await pool.query(
                    'INSERT INTO accounts (user_id, type, balance, account_number) VALUES ($1, $2, $3, $4) RETURNING id',
                    [userId, 'Checking', (Math.random() * 5000 + 1000).toFixed(2), 'CHK-' + Math.floor(Math.random() * 1000000)]
                );

                // Savings
                const acc2 = await pool.query(
                    'INSERT INTO accounts (user_id, type, balance, account_number) VALUES ($1, $2, $3, $4) RETURNING id',
                    [userId, 'Savings', (Math.random() * 20000 + 5000).toFixed(2), 'SAV-' + Math.floor(Math.random() * 1000000)]
                );

                // Seed Transactions
                const accountIds = [acc1.rows[0].id, acc2.rows[0].id];
                const descriptions = ['Grocery Store', 'Salary Deposit', 'Online Purchase', 'Utilities Bill', 'ATM Withdrawal'];

                for (const accId of accountIds) {
                    for (let i = 0; i < 10; i++) {
                        const amount = (Math.random() * 500 - 100).toFixed(2);
                        const desc = descriptions[Math.floor(Math.random() * descriptions.length)];
                        await pool.query(
                            'INSERT INTO transactions (account_id, amount, description) VALUES ($1, $2, $3)',
                            [accId, amount, desc]
                        );
                    }
                }
            }
        }

        console.log('Seeding complete.');
        process.exit(0);

    } catch (err) {
        console.error(err);
        process.exit(1);
    }
}

seed();
