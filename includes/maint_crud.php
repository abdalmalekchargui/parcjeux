<?php
// Maintenance : l'admin gère tout ; le responsable déclare une panne sur ses jeux
function maint_page(PDO $pdo, array $u, string $area): void {
    $admin = $u['role'] === 'admin'; $scope = $admin ? '' : ' AND j.id_responsable=' . (int)$u['id'];
    $types = ['panne' => 'Panne', 'corrective' => 'Corrective', 'preventive' => 'Préventive', 'revision' => 'Révision'];
    $sts = ['signalee' => 'Signalée', 'planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check(); $id = post_int('id');
        $g = $pdo->prepare('SELECT j.* FROM jeux j WHERE j.id=?' . $scope); $g->execute([post_int('id_jeu')]); $jeu = $g->fetch();
        $d = ['type' => $_POST['type_intervention'] ?? '', 'desc' => clean('description', 1000), 'cout' => $admin ? post_float('cout') : 0.0, 'date' => clean('date_intervention', 10) ?: date('Y-m-d'),
              'next' => clean('date_prochaine_revision', 10), 'statut' => $admin ? ($_POST['statut'] ?? 'signalee') : 'signalee'];
        $err = [];
        if (!$jeu) $err[] = 'Jeu invalide ou non autorisé.';
        if (!isset($types[$d['type']])) $err[] = 'Type invalide.';
        if (!isset($sts[$d['statut']])) $err[] = 'Statut invalide.';
        if ($d['desc'] === '') $err[] = 'Description obligatoire.';
        if ($d['cout'] < 0) $err[] = 'Coût invalide.';
        if (!valid_date($d['date']) || ($d['next'] !== '' && !valid_date($d['next']))) $err[] = 'Date invalide.';
        if ($err) { flash(implode(' ', $err), 'err'); redirect("$area/maintenance.php" . ($id && $admin ? "?edit=$id" : '?add=1')); }
        if ($id && $admin) {
            $pdo->prepare('UPDATE maintenance SET id_jeu=?,type_intervention=?,description=?,cout=?,date_intervention=?,date_prochaine_revision=?,statut=? WHERE id=?')->execute([$jeu['id'], $d['type'], $d['desc'], $d['cout'], $d['date'], $d['next'] ?: null, $d['statut'], $id]);
        } else {
            $pdo->prepare('INSERT INTO maintenance (id_jeu,id_responsable,type_intervention,description,cout,date_intervention,date_prochaine_revision,statut) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$jeu['id'], $jeu['id_responsable'] ?: ($u['role'] === 'responsable' ? $u['id'] : null), $d['type'], $d['desc'], $d['cout'], $d['date'], $d['next'] ?: null, $d['statut']]);
            $id = (int)$pdo->lastInsertId();
        }
        // Un jeu en panne / intervention en cours est bloqué à la vente ; il redevient disponible à la fin
        $open = in_array($d['statut'], ['signalee', 'en_cours'], true) && in_array($d['type'], ['panne', 'corrective'], true);
        if ($open && (isset($_POST['bloquer']) || $admin)) $pdo->prepare("UPDATE jeux SET etat='en_maintenance' WHERE id=?")->execute([$jeu['id']]);
        if ($d['statut'] === 'terminee') {
            $o = $pdo->prepare("SELECT COUNT(*) FROM maintenance WHERE id_jeu=? AND statut IN ('signalee','en_cours') AND type_intervention IN ('panne','corrective')"); $o->execute([$jeu['id']]);
            if (!(int)$o->fetchColumn()) $pdo->prepare("UPDATE jeux SET etat='disponible' WHERE id=? AND etat='en_maintenance'")->execute([$jeu['id']]);
        }
        // Coût de l'intervention => dépense automatique (liée à la maintenance)
        if ($admin && $d['cout'] > 0) {
            $x = $pdo->prepare('SELECT id FROM depenses WHERE id_maintenance=?'); $x->execute([$id]); $dep = $x->fetchColumn();
            if ($dep) $pdo->prepare('UPDATE depenses SET montant=?,id_jeu=?,date_depense=? WHERE id=?')->execute([$d['cout'], $jeu['id'], $d['date'], $dep]);
            else $pdo->prepare("INSERT INTO depenses (libelle,categorie,description,montant,date_depense,type,id_jeu,id_utilisateur,id_maintenance) VALUES (?,'maintenance',?,?,?,'jeu',?,?,?)")->execute(['Maintenance : ' . $jeu['nom'], mb_substr($d['desc'], 0, 250), $d['cout'], $d['date'], $jeu['id'], $u['id'], $id]);
        }
        flash($admin ? 'Intervention enregistrée.' : 'Panne signalée. L\'administration est informée.'); redirect("$area/maintenance.php");
    }
    $jeux = $pdo->query('SELECT j.id,j.nom FROM jeux j WHERE 1' . $scope . ' ORDER BY j.nom')->fetchAll();
    $edit = null;
    if ($admin && isset($_GET['edit'])) { $s = $pdo->prepare('SELECT * FROM maintenance WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch() ?: null; }
    if ($edit || isset($_GET['add'])) {
        $m = $edit ?: ['id' => 0, 'id_jeu' => 0, 'type_intervention' => $admin ? 'preventive' : 'panne', 'description' => '', 'cout' => 0, 'date_intervention' => date('Y-m-d'), 'date_prochaine_revision' => '', 'statut' => 'signalee'];
        ?><div class="card"><h3><?= $admin ? ($edit ? 'Modifier l\'intervention' : 'Nouvelle intervention') : 'Signaler une panne ou un besoin de maintenance' ?></h3><form method="post" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
        <div><label>Jeu</label><select name="id_jeu" required><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>" <?= (int)$j['id'] === (int)$m['id_jeu'] ? 'selected' : '' ?>><?= e($j['nom']) ?></option><?php endforeach; ?></select></div>
        <div><label>Type</label><select name="type_intervention"><?php foreach ($types as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $m['type_intervention'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
        <div><label>Date</label><input type="date" name="date_intervention" value="<?= e($m['date_intervention']) ?>"></div><div><label>Prochaine révision</label><input type="date" name="date_prochaine_revision" value="<?= e($m['date_prochaine_revision']) ?>"></div>
        <?php if ($admin): ?><div><label>Coût (<?= e(DEVISE) ?>)</label><input type="number" step="0.01" min="0" name="cout" value="<?= e($m['cout']) ?>"></div><div><label>Statut</label><select name="statut"><?php foreach ($sts as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $m['statut'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
        <?php else: ?><div class="full"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="bloquer" value="1" style="width:auto" checked> Mettre le jeu « en maintenance » (bloque réservations et ventes)</label></div><?php endif; ?>
        <div class="full"><label>Description</label><textarea name="description" rows="3" maxlength="1000" required><?= e($m['description']) ?></textarea></div>
        <div class="full"><button class="btn">Enregistrer</button> <a class="btn alt" href="<?= url("$area/maintenance.php") ?>">Annuler</a></div></form></div><?php
        return;
    }
    $fj = (int)($_GET['jeu'] ?? 0); $fs = $_GET['statut'] ?? ''; $p = []; $sql = 'SELECT m.*, j.nom jeu FROM maintenance m JOIN jeux j ON j.id=m.id_jeu WHERE 1' . $scope;
    if ($fj) { $sql .= ' AND m.id_jeu=?'; $p[] = $fj; } if (isset($sts[$fs])) { $sql .= ' AND m.statut=?'; $p[] = $fs; }
    $st = $pdo->prepare($sql . ' ORDER BY m.date_intervention DESC'); $st->execute($p); $rows = $st->fetchAll();
    ?><form class="filters"><div><label>Jeu</label><select name="jeu"><option value="0">Tous</option><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>" <?= $fj === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['nom']) ?></option><?php endforeach; ?></select></div>
    <div><label>Statut</label><select name="statut"><option value="">Tous</option><?php foreach ($sts as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $fs ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <button class="btn">Filtrer</button> <a class="btn dark" href="?add=1"><?= $admin ? '+ Intervention' : '⚠ Signaler une panne' ?></a></form>
    <div class="tablewrap"><table><tr><th>Date</th><th>Jeu</th><th>Type</th><th>Description</th><th class="right">Coût</th><th>Prochaine révision</th><th>Statut</th><?php if ($admin): ?><th></th><?php endif; ?></tr>
    <?php foreach ($rows as $r): ?><tr><td><?= e(date('d/m/Y', strtotime($r['date_intervention']))) ?></td><td><?= e($r['jeu']) ?></td><td><?= e($types[$r['type_intervention']]) ?></td><td style="white-space:normal;min-width:220px"><?= e($r['description']) ?></td><td class="right"><?= money($r['cout']) ?></td><td><?= $r['date_prochaine_revision'] ? e(date('d/m/Y', strtotime($r['date_prochaine_revision']))) : '—' ?></td><td><?= badge($sts[$r['statut']], $r['statut'] === 'terminee' ? 'st-payee' : 'st-en_attente') ?></td>
    <?php if ($admin): ?><td><a class="btn sm alt" href="?edit=<?= (int)$r['id'] ?>">Modifier</a></td><?php endif; ?></tr><?php endforeach; ?></table></div>
    <?php if (!$rows) echo '<p class="muted">Aucune intervention.</p>';
}
