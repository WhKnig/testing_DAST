const express = require('express');
const { Pool } = require('pg');
const bodyParser = require('body-parser');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const path = require('path');

const app = express();
const port = 3000;

// DB connection
const pool = new Pool({
    user: 'postgres',
    host: 'localhost',
    database: 'securebank',
    password: 'password',
    port: 5432,
});

app.set('view engine', 'ejs');
app.use(express.static('public'));
app.use(bodyParser.urlencoded({ extended: true }));
app.use(cookieParser());
app.use(session({
    secret: 'securebank_session_secret',
    resave: false,
    saveUninitialized: true
}));

// Middleware
const requireLogin = (req, res, next) => {
    if (req.session.userId) {
        next();
    } else {
        res.redirect('/login');
    }
};

// Routes
app.get('/', requireLogin, async (req, res) => {
    try {
        const client = await pool.connect();

        // Fetch User
        const userRes = await client.query('SELECT * FROM users WHERE id = $1', [req.session.userId]);
        const user = userRes.rows[0];

        // Fetch Accounts
        const accRes = await client.query('SELECT * FROM accounts WHERE user_id = $1', [req.session.userId]);
        const accounts = accRes.rows;

        // Calculate Total Assets
        let total = 0;
        accounts.forEach(a => total += parseFloat(a.balance));

        // Fetch Recent Transactions (Vulnerable SQLi potential here if we weren't using parameterized for *fetching*, but let's put it in the search/filter)
        // Let's create a vulnerable view for transactions

        client.release();
        res.render('dashboard', { user, accounts, total });
    } catch (err) {
        console.error(err);
        res.send("Error");
    }
});

app.get('/transactions', requireLogin, async (req, res) => {
    const accountId = req.query.account;
    const search = req.query.search;

    try {
        const client = await pool.connect();
        let query = `SELECT * FROM transactions WHERE account_id = ${accountId}`; // VULN: SQLi (Direct concatenation)

        if (search) {
            query += ` AND description LIKE '%${search}%'`; // VULN: SQLi
        }

        query += ' ORDER BY date DESC LIMIT 20';

        // Vulnerable Execution
        const result = await client.query(query);
        const transactions = result.rows;

        client.release();
        res.render('transactions', { transactions, accountId, search });
    } catch (err) {
        res.send("Database Error: " + err.message); // Info Leak
    }
});

app.get('/transfer', requireLogin, async (req, res) => {
    const client = await pool.connect();
    const accRes = await client.query('SELECT * FROM accounts WHERE user_id = $1', [req.session.userId]);
    client.release();
    res.render('transfer', { accounts: accRes.rows });
});

app.post('/transfer', requireLogin, async (req, res) => {
    const { from_account, to_account, amount } = req.body;

    // VULN: CSRF (Missing Token)
    // Also logic error: Can transfer negative amounts, or transfer to same account to double money if race condition (too complex for basic scanner check, sticking to CSRF).

    // Simple check
    if (amount <= 0) return res.send("Invalid amount");

    try {
        const client = await pool.connect();

        // Deduct
        await client.query('UPDATE accounts SET balance = balance - $1 WHERE id = $2', [amount, from_account]);

        // Add (Mock transfer to external or internal)
        // Check if to_account is numeric (internal ID) or string (external)
        // For simplicity, just deduct.

        client.release();
        res.redirect('/?msg=Transfer+Successful');
    } catch (err) {
        res.send("Error");
    }
});

app.get('/login', (req, res) => {
    res.render('login');
});

app.post('/login', async (req, res) => {
    const { username, password } = req.body;

    // VULN: Logic / Weak Password (no hash) is simulated
    // We can add SQLi here too if we want, but we have it in transactions.

    const client = await pool.connect();
    const result = await client.query('SELECT * FROM users WHERE username = $1 AND password = $2', [username, password]);
    client.release();

    if (result.rows.length > 0) {
        req.session.userId = result.rows[0].id;
        res.redirect('/');
    } else {
        res.redirect('/login?error=Invalid Credentials');
    }
});

app.get('/logout', (req, res) => {
    req.session.destroy();
    res.redirect('/login');
});

app.listen(port, () => {
    console.log(`SecureBank running on port ${port}`);
});