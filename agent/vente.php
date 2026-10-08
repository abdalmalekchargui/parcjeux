<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
$u = require_role('agent');
$jeux = $pdo->query("SELECT id,nom,emoji FROM jeux WHERE etat IN ('disponible','en_fonctionnement') ORDER BY nom")->fetchAll();
$types = $pdo->query('SELECT * FROM type_billet WHERE actif=1 ORDER BY prix')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $mode = in_array($_POST['mode'] ?? '', ['especes', 'carte'], true) ? $_POST['mode'] : 'especes'; $client = null; $em = clean('email', 120);
    if ($em !== '') { $c = $pdo->prepare("SELECT id FROM utilisateurs WHERE email=? AND role='client'"); $c->execute([$em]); $client = $c->fetchColumn() ?: null; if (!$client) { flash('Aucun client avec cet email (laissez vide pour une vente anonyme).', 'err'); redirect('agent/vente.php'); } }
    try { $r = vendre_billets($pdo, post_int('id_jeu'), post_int('id_type'), post_int('quantite'), $client ? (int)$client : null, (int)$u['id'], date('Y-m-d'), $mode); flash('Vente enregistrée : ' . $r['numero'] . ' · ' . money($r['total'])); redirect('ticket.php?type=cmd&id=' . $r['id']); }
    catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); redirect('agent/vente.php'); }
}
page_start('Vente sur place', 'agent');
?>
<div class="card narrow" style="max-width:680px"><form method="post" class="formgrid" data-calc><?= csrf_field() ?>
<div><label>Jeu</label><select name="id_jeu"><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>"><?= e($j['emoji'] . ' ' . $j['nom']) ?></option><?php endforeach; ?></select></div>
<div><label>Type de billet</label><select name="id_type"><?php foreach ($types as $t): ?><option value="<?= (int)$t['id'] ?>" data-prix="<?= e($t['prix']) ?>"><?= e($t['libelle'] . ' : ' . money($t['prix'])) ?></option><?php endforeach; ?></select></div>
<div><label>Quantité</label><input type="number" name="quantite" min="1" max="20" value="1" required></div><div><label>Paiement</label><select name="mode"><option value="especes">Espèces</option><option value="carte">Carte</option></select></div>
<div class="full"><label>Email du client (facultatif)</label><input type="email" name="email"></div><div class="full"><span class="total" id="total"></span></div><div class="full"><button class="btn">Encaisser et imprimer</button></div></form></div>
<?php page_end('agent');
