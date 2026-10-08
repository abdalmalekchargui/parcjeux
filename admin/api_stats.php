<?php
// Données JSON du tableau de bord (admin uniquement)
require __DIR__ . '/../includes/bootstrap.php'; require_role('admin');
header('Content-Type: application/json; charset=utf-8');
$period = $_GET['period'] ?? 'month';
$map = ['today' => [1, 'day'], 'week' => [7, 'day'], 'month' => [30, 'day'], 'year' => [12, 'month']];
[$n, $unit] = $map[$period] ?? $map['month'];
$labels = []; $keys = [];
for ($i = $n - 1; $i >= 0; $i--) {
    $t = strtotime($unit === 'day' ? "-$i day" : "first day of -$i month");
    $keys[] = date($unit === 'day' ? 'Y-m-d' : 'Y-m', $t); $labels[] = $unit === 'day' ? date('d/m', $t) : date('m/Y', $t);
}
$from = $unit === 'day' ? $keys[0] : $keys[0] . '-01'; $fmt = $unit === 'day' ? '%Y-%m-%d' : '%Y-%m';
function series(PDO $pdo, string $table, string $col, string $from, string $fmt, array $keys): array {
    $s = $pdo->prepare("SELECT DATE_FORMAT($col,'$fmt') k, SUM(montant) v FROM $table WHERE $col>=? GROUP BY k"); $s->execute([$from]);
    $m = array_column($s->fetchAll(), 'v', 'k'); return array_map(fn($k) => (float)($m[$k] ?? 0), $keys);
}
$res = $pdo->prepare("SELECT DATE_FORMAT(created_at,'$fmt') k, COUNT(*) v FROM reservations WHERE statut<>'annulee' AND created_at>=? GROUP BY k"); $res->execute([$from]);
$rm = array_column($res->fetchAll(), 'v', 'k');
$pop = $pdo->query("SELECT j.nom, (SELECT COUNT(*) FROM billets b WHERE b.id_jeu=j.id)+(SELECT COALESCE(SUM(nb_personnes),0) FROM reservations r WHERE r.id_jeu=j.id AND r.statut<>'annulee') v FROM jeux j ORDER BY v DESC")->fetchAll();
$etats = $pdo->query('SELECT etat, COUNT(*) v FROM jeux GROUP BY etat')->fetchAll();
echo json_encode([
    'labels' => $labels, 'recettes' => series($pdo, 'recettes', 'date_recette', $from, $fmt, $keys), 'depenses' => series($pdo, 'depenses', 'date_depense', $from, $fmt, $keys),
    'reservations' => array_map(fn($k) => (int)($rm[$k] ?? 0), $keys),
    'popularite' => ['labels' => array_column($pop, 'nom'), 'data' => array_map('intval', array_column($pop, 'v'))],
    'etats' => ['labels' => array_map(fn($r) => ETATS[$r['etat']], $etats), 'data' => array_map('intval', array_column($etats, 'v'))],
], JSON_UNESCAPED_UNICODE);
