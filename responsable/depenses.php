<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('responsable');
$s = $pdo->prepare('SELECT d.*, j.nom jeu FROM depenses d JOIN jeux j ON j.id=d.id_jeu WHERE j.id_responsable=? ORDER BY date_depense DESC'); $s->execute([$u['id']]); $rows = $s->fetchAll();
page_start('Dépenses de mes jeux', 'responsable');
?>
<p class="muted">Consultation seule : les dépenses sont saisies par l'administration.</p>
<p class="total"><?= money(array_sum(array_column($rows, 'montant'))) ?></p>
<div class="tablewrap"><table><tr><th>Date</th><th>Jeu</th><th>Libellé</th><th>Catégorie</th><th class="right">Montant</th></tr><?php foreach ($rows as $r): ?><tr><td><?= e(date('d/m/Y', strtotime($r['date_depense']))) ?></td><td><?= e($r['jeu']) ?></td><td><?= e($r['libelle']) ?></td><td><?= e(CAT_DEPENSES[$r['categorie']] ?? $r['categorie']) ?></td><td class="right"><?= money($r['montant']) ?></td></tr><?php endforeach; ?></table></div>
<?php page_end('responsable');
