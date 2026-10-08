<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('responsable'); $d1 = $_GET['d1'] ?? date('Y-m-d'); if (!valid_date($d1)) $d1 = date('Y-m-d');
$s = $pdo->prepare("SELECT r.*, j.nom jeu, CONCAT(c.prenom,' ',c.nom) client FROM reservations r JOIN jeux j ON j.id=r.id_jeu JOIN utilisateurs c ON c.id=r.id_client WHERE j.id_responsable=? AND r.date_visite>=? AND r.statut<>'annulee' ORDER BY r.date_visite, r.heure"); $s->execute([$u['id'], $d1]); $rows = $s->fetchAll();
page_start('Planning des réservations', 'responsable');
?>
<form class="filters"><div><label>À partir du</label><input type="date" name="d1" value="<?= e($d1) ?>"></div><button class="btn">Afficher</button></form>
<div class="tablewrap"><table><tr><th>Date</th><th>Heure</th><th>Jeu</th><th>Client</th><th>Pers.</th><th>Statut</th></tr><?php foreach ($rows as $r): ?><tr><td><?= e(date('d/m/Y', strtotime($r['date_visite']))) ?></td><td><?= e(substr($r['heure'], 0, 5)) ?></td><td><?= e($r['jeu']) ?></td><td><?= e($r['client']) ?></td><td><?= (int)$r['nb_personnes'] ?></td><td><?= statut_badge($r['statut']) ?></td></tr><?php endforeach; ?></table></div>
<?php if (!$rows) echo '<p class="muted">Aucune réservation à venir sur vos jeux.</p>'; page_end('responsable');
