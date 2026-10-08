<?php
require __DIR__ . '/includes/bootstrap.php';
$_SESSION = []; session_destroy(); session_start(); flash('Vous êtes déconnecté.'); redirect('index.php');
