<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

$_SESSION = [];
session_destroy();

jsonResponse(['success' => true]);
