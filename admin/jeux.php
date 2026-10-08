<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php'; require __DIR__ . '/../includes/jeux_crud.php';
$u = require_role('admin'); page_start('Jeux', 'admin'); jeux_crud($pdo, $u, 'admin'); page_end('admin');
