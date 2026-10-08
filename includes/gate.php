<?php
// Réservé aux clients connectés ; les visiteurs voient un message avec boutons de connexion/inscription
function client_gate(string $action): array {
    $u = user();
    if ($u && $u['role'] === 'client') return $u;
    if ($u) { flash('Seuls les comptes clients peuvent ' . $action . '.', 'warn'); redirect(home_for($u['role'])); }
    $_SESSION['next'] = $_SERVER['REQUEST_URI'];
    page_start('Connexion requise');
    echo '<div class="card narrow"><h2>Connexion requise</h2><p>Vous devez être connecté pour ' . e($action) . '.</p><div class="actions"><a class="btn" href="' . e(url('login.php')) . '">Se connecter</a><a class="btn alt" href="' . e(url('register.php')) . '">Créer un compte</a></div></div>';
    page_end(); exit;
}
function load_jeu(PDO $pdo): array {
    $s = $pdo->prepare('SELECT * FROM jeux WHERE id=?'); $s->execute([(int)($_GET['jeu'] ?? $_POST['id_jeu'] ?? 0)]); $g = $s->fetch();
    if (!$g) { flash('Jeu introuvable.', 'err'); redirect('jeux.php'); }
    if (!bookable($g['etat'])) { flash('« ' . $g['nom'] . ' » n\'est pas disponible (' . (ETATS[$g['etat']] ?? '') . ').', 'warn'); redirect('jeux.php'); }
    return $g;
}
