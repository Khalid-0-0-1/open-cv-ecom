<?php
// Ret disse til dine egne MySQL-oplysninger
return [
    'host' => '127.0.0.1',
    'dbname' => 'simple_shop',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
    // Admin login - standard adgangskode er "admin123", skift den herunder.
    // Ny hash laves med: php -r "echo password_hash('nytpassword', PASSWORD_DEFAULT);"
    'admin_user' => 'admin',
    'admin_pass_hash' => '$2y$10$fnFu/PFqA94susquHhswHusgS.9ydGisPH7yQ1U8dGD/7GHmVce9m',
];