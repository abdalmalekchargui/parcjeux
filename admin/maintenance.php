<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php'; require __DIR__ . '/../includes/maint_crud.php';
$u = require_role('admin'); page_start('Maintenance', 'admin'); maint_page($pdo, $u, 'admin'); page_end('admin');
