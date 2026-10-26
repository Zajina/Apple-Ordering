<?php
declare(strict_types=1);

/*
 * KJL Collective database connection.
 * Default XAMPP settings: MySQL user root, blank password.
 */
$host = 'localhost';
$dbname = 'ordering_system';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('KJL Collective database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to connect to the KJL Collective database. Check that MySQL is running in XAMPP and that the settings in db.php are correct.');
}
