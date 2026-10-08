<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = post_int('id'); $lib = clean('libelle', 60); $prix = post_float('prix'); $actif = isset($_POST['actif']) ? 1 : 0;
    if ($lib === '' || $prix < 0) flash('Libellé et prix valides requis.', 'err');
    else try {
        if ($id) $pdo->prepare('UPDATE type_billet SET libelle=?,prix=?,actif=? WHERE id=?')->execute([$lib, $prix, $actif, $id]);
        else $pdo->prepare('INSERT INTO type_billet (libelle,prix,actif) VALUES (?,?,1)')->execute([$lib, $prix]);
        flash('Tarif enregistré.');
    } catch (PDOException $ex) { flash('Ce libellé existe déjà.', 'err'); }
    redirect('admin/parametres.php');
}
$rows = $pdo->query('SELECT * FROM type_billet ORDER BY prix')->fetchAll();
page_start('Tarifs & paramètres', 'admin');
?>
<p class="muted">Les types de billets désactivés ne sont plus proposés à la vente ; l'historique reste intact.</p>
<div class="tablewrap"><table><tr><th>Type de billet</th><th>Prix (<?= e(DEVISE) ?>)</th><th>Actif</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><td><input name="libelle" value="<?= e($r['libelle']) ?>" required></td><td><input type="number" step="0.01" min="0" name="prix" value="<?= e($r['prix']) ?>" required></td><td><input type="checkbox" name="actif" style="width:auto" <?= $r['actif'] ? 'checked' : '' ?>></td><td><button class="btn sm">Enregistrer</button></td></form></tr><?php endforeach; ?></table></div>
<div class="card" style="margin-top:20px"><h3>Nouveau type de billet</h3><form method="post" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="0"><div><label>Libellé</label><input name="libelle" required></div><div><label>Prix</label><input type="number" step="0.01" min="0" name="prix" required></div><div class="full"><button class="btn">Ajouter</button></div></form></div>
<?php page_end('admin');
