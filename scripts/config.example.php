<?php
// Copy OUTSIDE public_html, to ~/niwff-private/config.php. Never commit credentials.
return [
    'dsn' => 'mysql:host=localhost;dbname=REPLACE_DB;charset=utf8mb4',
    'user' => 'REPLACE_DB_USER',
    'password' => 'REPLACE_DB_PASSWORD',
    // Optional verified destination; empty disables email and leaves dashboard queue active.
    'admin_email' => '',
    'from_email' => '',
];
