<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = trim($data['username'] ?? '');
$password = (string)($data['password'] ?? '');

$cfg = require __DIR__ . '/config.php';

if ($username === '' || $password === '' ||
    !hash_equals($cfg['admin_user'], $username) ||
    !password_verify($password, $cfg['admin_pass_hash'])
) {
    jsonResponse(['error' => 'Forkert brugernavn eller adgangskode'], 401);
}

session_regenerate_id(true);
$_SESSION['is_admin'] = true;

jsonResponse(['success' => true]);
