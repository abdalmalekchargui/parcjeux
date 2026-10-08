<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php'; require __DIR__ . '/../includes/jeux_crud.php';
$u = require_role('responsable'); page_start('Mes jeux', 'responsable'); jeux_crud($pdo, $u, 'responsable'); page_end('responsable');
