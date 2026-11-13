<?php
/**
 * config/database.php
 * Database settings + one function that gives every page a PDO connection.
 * If your XAMPP MySQL has a different user or password, change them here.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'lost_found_portal');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is an empty password
define('DB_CHARSET', 'utf8mb4');

// The project folder name inside C:\xampp\htdocs\
define('BASE_URL', '/lost_found_portal');

function get_db()
{
    static $pdo = null;               // reuse one connection per request
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errors become exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
            ]);
        } catch (PDOException $ex) {
            // Never print the real error (it could reveal the username/host).
            error_log('DB connection failed: ' . $ex->getMessage());
            http_response_code(500);
            die('Database connection failed. Start MySQL in the XAMPP Control Panel, '
              . 'import database/lost_found_portal.sql, and check config/database.php.');
        }
    }
    return $pdo;
}
