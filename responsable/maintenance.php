<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php'; require __DIR__ . '/../includes/maint_crud.php';
$u = require_role('responsable'); page_start('Maintenance', 'responsable'); maint_page($pdo, $u, 'responsable'); page_end('responsable');
