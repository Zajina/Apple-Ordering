const express = require('express');
const mysql = require('mysql2/promise');
require('dotenv').config();

const app = express();
app.use(express.json());

// Create MySQL connection pool matching your schema
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'appleordering_db',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// --- API ENDPOINTS ---

// 1. GET: Fetch all orders with full customer and product details joined
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
        console.error('Database Error:', err);
        res.status(500).json({ success: false, error: 'Failed to retrieve orders' });
    }
});

// 2. POST: Create a new order (automatically references customers and products tables)
app.post('/api/orders', async (req, res) => {
    const { customer_id, product_id, quantity, total_amount, shipping_address } = req.body;

    // Validation
    if (!customer_id || !product_id || !total_amount || !shipping_address) {
        return res.status(400).json({ 
            success: false, 
            error: 'Missing required fields: customer_id, product_id, total_amount, and shipping_address are required.' 
        });
    }

    try {
        const [result] = await pool.query(
            `INSERT INTO orders (customer_id, product_id, quantity, total_amount, shipping_address) 
             VALUES (?, ?, ?, ?, ?)`,
            [customer_id, product_id, quantity || 1, total_amount, shipping_address]
        );

        res.status(201).json({ 
            success: true, 
            message: 'Order placed successfully!', 
            order_id: result.insertId 
        });
    } catch (err) {
        console.error('Database Error:', err);
        res.status(500).json({ success: false, error: 'Failed to insert order into database' });
    }
});

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Server is running live on http://localhost:${PORT}`);
});