<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('agent');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $n = $pdo->prepare("UPDATE reservations SET statut='utilisee' WHERE id=? AND statut IN ('confirmee','payee') AND date_visite=CURDATE()"); $n->execute([post_int('id')]);
    flash($n->rowCount() ? 'Réservation marquée comme utilisée.' : 'Possible uniquement le jour de la visite, pour une réservation confirmée ou payée.', $n->rowCount() ? 'ok' : 'err'); redirect('agent/reservations.php?q=' . urlencode(clean('q', 60)));
}
$q = trim($_GET['q'] ?? ''); $p = []; $sql = "SELECT r.*, j.nom jeu, CONCAT(c.prenom,' ',c.nom) client FROM reservations r JOIN jeux j ON j.id=r.id_jeu JOIN utilisateurs c ON c.id=r.id_client WHERE r.statut<>'annulee'";
if ($q !== '') { $sql .= ' AND (r.numero LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ? OR c.email LIKE ?)'; $p = array_fill(0, 4, "%$q%"); } else $sql .= ' AND r.date_visite>=CURDATE()';
$s = $pdo->prepare($sql . ' ORDER BY r.date_visite, r.heure LIMIT 100'); $s->execute($p); $rows = $s->fetchAll();
page_start('Réservations', 'agent');
?>
<form class="filters"><div><label>N° ou client</label><input name="q" value="<?= e($q) ?>" placeholder="Vide = à venir"></div><button class="btn">Rechercher</button></form>
<div class="tablewrap"><table><tr><th>N°</th><th>Client</th><th>Jeu</th><th>Visite</th><th>Pers.</th><th>Montant</th><th>Statut</th><th></th></tr><?php foreach ($rows as $r): ?><tr><td><?= e($r['numero']) ?></td><td><?= e($r['client']) ?></td><td><?= e($r['jeu']) ?></td><td><?= e(date('d/m/Y', strtotime($r['date_visite'])) . ' ' . substr($r['heure'], 0, 5)) ?></td><td><?= (int)$r['nb_personnes'] ?></td><td><?= money($r['montant_total']) ?></td><td><?= statut_badge($r['statut']) ?></td>
<td><?php if (in_array($r['statut'], ['confirmee', 'payee'], true) && $r['date_visite'] === date('Y-m-d')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>"><button class="btn sm">Marquer utilisée</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php if (!$rows) echo '<p class="muted">Aucune réservation trouvée.</p>'; page_end('agent');
