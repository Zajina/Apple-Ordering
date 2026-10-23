const express = require('express');
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(express.static('public'));

// MySQL Connection Pool
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'appleordering_db',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// --- AUTHENTICATION ENDPOINTS ---

// 1. REGISTER: Create a new customer account
app.post('/api/auth/register', async (req, res) => {
    const { first_name, last_name, email, password, phone } = req.body;

    if (!first_name || !last_name || !email || !password) {
        return res.status(400).json({ success: false, error: 'All required fields must be filled.' });
    }

    try {
        // Check if email already exists
        const [existing] = await pool.query('SELECT * FROM customers WHERE email = ?', [email]);
        if (existing.length > 0) {
            return res.status(400).json({ success: false, error: 'Email is already registered.' });
        }

        // Hash the password
        const salt = await bcrypt.genSalt(10);
        const hashedPassword = await bcrypt.hash(password, salt);

        // Insert new customer
        const [result] = await pool.query(
            `INSERT INTO customers (first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)`,
            [first_name, last_name, email, hashedPassword, phone || null]
        );

        res.status(201).json({ 
            success: true, 
            message: 'Registration successful!', 
            customer_id: result.insertId 
        });
    } catch (err) {
        console.error('Register Error:', err);
        res.status(500).json({ success: false, error: 'Server error during registration.' });
    }
});

// 2. LOGIN: Authenticate existing customer
app.post('/api/auth/login', async (req, res) => {
    const { email, password } = req.body;

    if (!email || !password) {
        return res.status(400).json({ success: false, error: 'Email and password are required.' });
    }

    try {
        const [rows] = await pool.query('SELECT * FROM customers WHERE email = ?', [email]);
        if (rows.length === 0) {
            return res.status(401).json({ success: false, error: 'Invalid email or password.' });
        }

        const customer = rows[0];

        // Compare password
        const isMatch = await bcrypt.compare(password, customer.password);
        if (!isMatch) {
            return res.status(401).json({ success: false, error: 'Invalid email or password.' });
        }

        // Send back customer details (excluding password)
        res.status(200).json({
            success: true,
            message: 'Login successful!',
            customer: {
                customer_id: customer.customer_id,
                first_name: customer.first_name,
                last_name: customer.last_name,
                email: customer.email
            }
        });
    } catch (err) {
        console.error('Login Error:', err);
        res.status(500).json({ success: false, error: 'Server error during login.' });
    }
});

// --- STORE ENDPOINTS ---

// Fetch available products
app.get('/api/products', async (req, res) => {
    try {
        const [rows] = await pool.query('SELECT * FROM products WHERE stock_quantity > 0');
        res.status(200).json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, error: 'Failed to retrieve products' });
    }
});

// Fetch all orders
app.get('/api/orders', async (req, res) => {
    try {
        const [rows] = await pool.query(`
            SELECT 
                o.order_id,
                CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                c.email AS customer_email,
                CONCAT(p.model_name, ' (', p.storage_capacity, ' - ', p.color, ')') AS item_ordered,
                p.price AS unit_price,
                o.quantity,
                o.total_amount,
                o.order_status,
                o.shipping_address,
                o.order_date
            FROM orders o
            JOIN customers c ON o.customer_id = c.customer_id
            JOIN products p ON o.product_id = p.product_id
            ORDER BY o.order_date DESC
        `);
        res.status(200).json({ success: true, count: rows.length, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, error: 'Failed to retrieve orders' });
    }
});

// Create order
app.post('/api/orders', async (req, res) => {
    const { customer_id, product_id, quantity, total_amount, shipping_address } = req.body;

    if (!customer_id || !product_id || !total_amount || !shipping_address) {
        return res.status(400).json({ success: false, error: 'Missing required fields.' });
    }

    try {
        const [result] = await pool.query(
            `INSERT INTO orders (customer_id, product_id, quantity, total_amount, shipping_address) VALUES (?, ?, ?, ?, ?)`,
            [customer_id, product_id, quantity || 1, total_amount, shipping_address]
        );

        res.status(201).json({ success: true, message: 'Order placed successfully!', order_id: result.insertId });
    } catch (err) {
        res.status(500).json({ success: false, error: 'Failed to insert order' });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Server running on http://localhost:${PORT}`);
});