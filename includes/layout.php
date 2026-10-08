<?php
// Gabarit commun. $area = site | admin | responsable | agent
function nav_items(string $area): array {
    $u = user();
    if ($area === 'admin') return [
        ['admin/dashboard.php', 'Tableau de bord', '▦'], ['admin/jeux.php', 'Jeux', '🎢'],
        ['admin/users.php?role=responsable', 'Responsables', '👷'], ['admin/users.php?role=agent', 'Agents', '🎫'],
        ['admin/users.php?role=client', 'Clients', '👪'], ['admin/reservations.php', 'Réservations', '📅'],
        ['admin/billets.php', 'Billets', '🎟'], ['admin/recettes.php', 'Recettes', '＋'],
        ['admin/depenses.php', 'Dépenses', '－'], ['admin/maintenance.php', 'Maintenance', '🔧'],
        ['admin/parametres.php', 'Tarifs & paramètres', '⚙'],
    ];
    if ($area === 'responsable') return [
        ['responsable/dashboard.php', 'Tableau de bord', '▦'], ['responsable/jeux.php', 'Mes jeux', '🎢'],
        ['responsable/jeux.php?add=1', 'Ajouter un jeu', '＋'], ['responsable/reservations.php', 'Réservations', '📅'],
        ['responsable/maintenance.php', 'Maintenance', '🔧'], ['responsable/depenses.php', 'Dépenses', '－'],
        ['profil.php', 'Profil', '👤'],
    ];
    if ($area === 'agent') return [
        ['agent/validate.php', 'Valider un billet', '✔'], ['agent/vente.php', 'Vente sur place', '🎫'],
        ['agent/reservations.php', 'Réservations', '📅'], ['profil.php', 'Profil', '👤'],
    ];
    $n = [['index.php', 'Accueil'], ['jeux.php', 'Jeux'], ['a-propos.php', 'À propos'], ['contact.php', 'Contact']];
    if ($u && $u['role'] === 'client') { $n[] = ['mes_reservations.php', 'Mes réservations']; $n[] = ['mes_billets.php', 'Mes billets']; $n[] = ['profil.php', 'Mon profil']; }
    return $n;
}

function page_start(string $title, string $area = 'site'): void {
    $u = user(); $items = nav_items($area); $cur = basename($_SERVER['SCRIPT_NAME']); $dash = $area !== 'site';
    $flash = flash(); $full = !$dash && $cur === 'index.php';
    ?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · <?= e(PARC_NOM) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"></head>
<body class="<?= $dash ? 'dash' : 'site' ?>" data-base="<?= e(BASE_URL) ?>">
<?php if ($dash): ?>
<aside class="side" id="side"><a class="brand" href="<?= url($area === 'admin' ? 'admin/dashboard.php' : home_for($u['role'] ?? 'client')) ?>">🎪 <?= e(PARC_NOM) ?></a>
  <nav><?php foreach ($items as [$href, $label, $ic]): $active = basename(parse_url($href, PHP_URL_PATH)) === $cur && (!str_contains($href, '?') || str_contains($_SERVER['REQUEST_URI'], substr($href, strpos($href, '?') + 1))); ?>
    <a href="<?= url($href) ?>" class="<?= $active ? 'on' : '' ?>"><span><?= $ic ?></span><?= e($label) ?></a><?php endforeach; ?>
    <a href="<?= url('index.php') ?>"><span>🏠</span>Voir le site</a>
    <a href="<?= url('logout.php') ?>"><span>⏻</span>Déconnexion</a></nav>
  <div class="who"><?= e(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?><br><small><?= e(ROLES[$u['role'] ?? 'client'] ?? '') ?></small></div></aside>
<main class="main"><header class="topbar"><button id="burger" aria-label="Menu">☰</button><h1><?= e($title) ?></h1></header><div class="content">
<?php else: ?>
<header class="nav"><a class="brand" href="<?= url('index.php') ?>">🎪 <?= e(PARC_NOM) ?></a>
  <button id="burger" aria-label="Menu">☰</button>
  <nav id="topnav"><?php foreach ($items as [$href, $label]): ?><a href="<?= url($href) ?>" class="<?= basename($href) === $cur ? 'on' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
  <?php if ($u): if ($u['role'] !== 'client'): ?><a href="<?= url(home_for($u['role'])) ?>" class="pill">Mon espace</a><?php endif; ?><a href="<?= url('logout.php') ?>">Déconnexion</a>
  <?php else: ?><a href="<?= url('login.php') ?>">Connexion</a><a href="<?= url('register.php') ?>" class="pill">Inscription</a><?php endif; ?></nav></header>
<main class="<?= $full ? 'content full' : 'content wrap' ?>">
<?php endif;
    if ($flash): ?><?= $full ? '<div class="wrap">' : '' ?><div class="alert <?= e($flash[1]) ?>" role="alert"><?= e($flash[0]) ?></div><?= $full ? '</div>' : '' ?><?php endif;
}

function page_end(string $area = 'site'): void {
    echo $area === 'site' ? '</main>' : '</div></main>';
    if ($area === 'site') echo '<footer class="foot"><div class="wrap">© ' . date('Y') . ' ' . e(PARC_NOM) . ' · Projet académique de gestion de parc de jeux</div></footer>';
    echo '<script src="' . e(url('assets/js/app.js')) . '"></script>';
    if ($area === 'site') echo '<div id="chat"></div>';
    echo '</body></html>';
}