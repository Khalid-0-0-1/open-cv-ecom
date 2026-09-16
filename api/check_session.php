<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

jsonResponse(['isAdmin' => isAdmin()]);
