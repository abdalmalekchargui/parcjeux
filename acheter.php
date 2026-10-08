<?php
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php'; require __DIR__ . '/includes/gate.php';
$u = client_gate('acheter un billet'); $g = load_jeu($pdo);
$types = $pdo->query('SELECT * FROM type_billet WHERE actif=1 ORDER BY prix')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $date = clean('date_visite', 10);
    if (!valid_date($date) || $date < date('Y-m-d')) flash('Date de visite invalide.', 'err');
    else try {
        $r = vendre_billets($pdo, (int)$g['id'], post_int('id_type'), post_int('quantite'), (int)$u['id'], null, $date, 'simulation');
        flash('Paiement simulé accepté. Commande ' . $r['numero'] . ' : ' . money($r['total']) . '.'); redirect('mes_billets.php');
    } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); }
}
page_start('Acheter : ' . $g['nom']);
?>
<div class="card narrow" style="max-width:680px"><h2><?= e($g['emoji'] . ' ' . $g['nom']) ?></h2>
<form method="post" class="formgrid" data-calc><?= csrf_field() ?><input type="hidden" name="id_jeu" value="<?= (int)$g['id'] ?>">
<div><label>Type de billet</label><select name="id_type"><?php foreach ($types as $t): ?><option value="<?= (int)$t['id'] ?>" data-prix="<?= e($t['prix']) ?>"><?= e($t['libelle'] . ' : ' . money($t['prix'])) ?></option><?php endforeach; ?></select></div>
<div><label>Quantité</label><input type="number" name="quantite" min="1" max="20" value="1" required></div>
<div><label>Date de visite</label><input type="date" name="date_visite" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required></div>
<div><label>Client</label><input value="<?= e($u['prenom'] . ' ' . $u['nom'] . ' · ' . $u['email']) ?>" disabled></div>
<div class="full"><span class="total" id="total"></span><br><small class="muted">Paiement simulé : aucun débit réel.</small></div>
<div class="full"><button class="btn">Payer et recevoir mes billets</button></div></form></div>
<?php page_end();
