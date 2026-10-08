<?php
// CRUD des jeux partagé entre l'admin (tous les jeux) et le responsable (ses jeux seulement)
function assign_game(PDO $pdo, int $jeu, ?int $resp): void {
    $pdo->prepare('UPDATE affectations SET date_fin=CURDATE() WHERE id_jeu=? AND date_fin IS NULL')->execute([$jeu]);
    $pdo->prepare('UPDATE jeux SET id_responsable=? WHERE id=?')->execute([$resp, $jeu]);
    if ($resp) $pdo->prepare('INSERT INTO affectations (id_jeu,id_responsable,date_debut) VALUES (?,?,CURDATE())')->execute([$jeu, $resp]);
}
function jeux_crud(PDO $pdo, array $u, string $area): void {
    $admin = $u['role'] === 'admin'; $self = 'jeux.php';
    $scope = $admin ? '' : ' AND id_responsable=' . (int)$u['id'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check(); $act = $_POST['action'] ?? ''; $id = post_int('id');
        if ($id) { $c = $pdo->prepare('SELECT * FROM jeux WHERE id=?' . $scope); $c->execute([$id]); if (!$c->fetch()) { flash('Jeu introuvable ou non autorisé.', 'err'); redirect("$area/$self"); } }
        if ($act === 'etat') {
            $e = $_POST['etat'] ?? ''; if (isset(ETATS[$e])) { $pdo->prepare('UPDATE jeux SET etat=? WHERE id=?')->execute([$e, $id]); flash('État mis à jour.'); }
        } elseif ($act === 'delete') {
            $n = $pdo->prepare('SELECT (SELECT COUNT(*) FROM reservations WHERE id_jeu=?)+(SELECT COUNT(*) FROM commandes WHERE id_jeu=?)'); $n->execute([$id, $id]);
            if ((int)$n->fetchColumn() > 0) flash('Ce jeu a des réservations ou ventes : passez-le en « Fermé temporairement » pour le désactiver.', 'err');
            else { $pdo->prepare('DELETE FROM jeux WHERE id=?')->execute([$id]); flash('Jeu supprimé.'); }
        } else {
            $d = ['nom' => clean('nom', 100), 'description' => clean('description', 1500), 'categorie' => clean('categorie', 50), 'age_min' => post_int('age_min'), 'capacite' => post_int('capacite'),
                  'duree' => post_int('duree_session'), 'tarif' => post_float('tarif'), 'etat' => $_POST['etat'] ?? 'disponible', 'emoji' => clean('emoji', 8) ?: '🎪',
                  'couleur' => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '') ? $_POST['couleur'] : '#e63946', 'image' => clean('image', 255), 'date' => clean('date_mise_service', 10) ?: date('Y-m-d')];
            $err = [];
            if ($d['nom'] === '' || $d['description'] === '' || $d['categorie'] === '') $err[] = 'Nom, description et catégorie obligatoires.';
            if ($d['capacite'] < 1 || $d['duree'] < 1 || $d['tarif'] < 0 || $d['age_min'] < 0 || $d['age_min'] > 99) $err[] = 'Capacité, durée, tarif et âge doivent être valides.';
            if (!isset(ETATS[$d['etat']])) $err[] = 'État invalide.';
            if ($d['image'] !== '' && !preg_match('#^(https?://|assets/)#', $d['image'])) $err[] = 'Image : URL http(s) ou chemin assets/…';
            if (!valid_date($d['date'])) $err[] = 'Date invalide.';
            if ($err) { flash(implode(' ', $err), 'err'); redirect("$area/$self" . ($id ? "?edit=$id" : '?add=1')); }
            $resp = $admin ? (post_int('id_responsable') ?: null) : (int)$u['id'];
            $vals = [$d['nom'], $d['description'], $d['categorie'], $d['age_min'], $d['capacite'], $d['duree'], $d['tarif'], $d['etat'], $d['image'] ?: null, $d['emoji'], $d['couleur'], $d['date']];
            if ($id) {
                $pdo->prepare('UPDATE jeux SET nom=?,description=?,categorie=?,age_min=?,capacite=?,duree_session=?,tarif=?,etat=?,image=?,emoji=?,couleur=?,date_mise_service=? WHERE id=?')->execute([...$vals, $id]);
                if ($admin) { $o = $pdo->prepare('SELECT id_responsable FROM jeux WHERE id=?'); $o->execute([$id]); if ((int)$o->fetchColumn() !== (int)$resp) assign_game($pdo, $id, $resp); }
                flash('Jeu modifié.');
            } else {
                $pdo->prepare('INSERT INTO jeux (nom,description,categorie,age_min,capacite,duree_session,tarif,etat,image,emoji,couleur,date_mise_service) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals);
                assign_game($pdo, (int)$pdo->lastInsertId(), $resp); flash('Jeu ajouté.');
            }
        }
        redirect("$area/$self");
    }
    $edit = null;
    if (isset($_GET['edit'])) { $s = $pdo->prepare('SELECT * FROM jeux WHERE id=?' . $scope); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch() ?: null; }
    if ($edit || isset($_GET['add'])) {
        $g = $edit ?: ['id' => 0, 'nom' => '', 'description' => '', 'categorie' => '', 'age_min' => 0, 'capacite' => 20, 'duree_session' => 5, 'tarif' => 30, 'etat' => 'disponible', 'image' => '', 'emoji' => '🎪', 'couleur' => '#e63946', 'date_mise_service' => date('Y-m-d'), 'id_responsable' => null];
        $resps = $admin ? $pdo->query("SELECT id,nom,prenom FROM utilisateurs WHERE role='responsable' AND statut='actif' ORDER BY nom")->fetchAll() : [];
        ?><div class="card"><h3><?= $edit ? 'Modifier le jeu' : 'Ajouter un jeu' ?></h3><form method="post" class="formgrid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
        <div><label>Nom</label><input name="nom" value="<?= e($g['nom']) ?>" required></div><div><label>Catégorie</label><input name="categorie" value="<?= e($g['categorie']) ?>" list="cats" required><datalist id="cats"><option>Sensations fortes</option><option>Famille</option><option>Enfants</option></datalist></div>
        <div><label>Âge minimum</label><input type="number" name="age_min" min="0" max="99" value="<?= (int)$g['age_min'] ?>"></div><div><label>Capacité par session</label><input type="number" name="capacite" min="1" value="<?= (int)$g['capacite'] ?>" required></div>
        <div><label>Durée de session (min)</label><input type="number" name="duree_session" min="1" value="<?= (int)$g['duree_session'] ?>" required></div><div><label>Tarif par personne (<?= e(DEVISE) ?>)</label><input type="number" step="0.01" min="0" name="tarif" value="<?= e($g['tarif']) ?>" required></div>
        <div><label>État</label><select name="etat"><?php foreach (ETATS as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $g['etat'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
        <div><label>Date de mise en service</label><input type="date" name="date_mise_service" value="<?= e($g['date_mise_service']) ?>"></div>
        <div><label>Emoji (affiché sans image)</label><input name="emoji" value="<?= e($g['emoji']) ?>" maxlength="8"></div><div><label>Couleur</label><input type="color" name="couleur" value="<?= e($g['couleur']) ?>"></div>
        <div class="full"><label>Image (URL ou assets/img/…, facultatif)</label><input name="image" value="<?= e($g['image']) ?>"></div>
        <?php if ($admin): ?><div><label>Responsable</label><select name="id_responsable"><option value="">— Aucun —</option><?php foreach ($resps as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === (int)$g['id_responsable'] ? 'selected' : '' ?>><?= e($r['prenom'] . ' ' . $r['nom']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="full"><label>Description</label><textarea name="description" rows="3" maxlength="1500" required><?= e($g['description']) ?></textarea></div>
        <div class="full"><button class="btn">Enregistrer</button> <a class="btn alt" href="<?= url("$area/$self") ?>">Annuler</a></div></form></div><?php
        return;
    }
    $q = trim($_GET['q'] ?? ''); $et = $_GET['etat'] ?? ''; $p = [];
    $sql = "SELECT j.*, CONCAT(u.prenom,' ',u.nom) resp FROM jeux j LEFT JOIN utilisateurs u ON u.id=j.id_responsable WHERE 1" . str_replace('id_responsable', 'j.id_responsable', $scope);
    if ($q !== '') { $sql .= ' AND (j.nom LIKE ? OR j.categorie LIKE ?)'; $p = ["%$q%", "%$q%"]; }
    if (isset(ETATS[$et])) { $sql .= ' AND j.etat=?'; $p[] = $et; }
    $st = $pdo->prepare($sql . ' ORDER BY j.nom'); $st->execute($p); $rows = $st->fetchAll();
    ?><form class="filters"><div><label>Recherche</label><input name="q" value="<?= e($q) ?>" placeholder="Nom ou catégorie"></div>
    <div><label>État</label><select name="etat"><option value="">Tous</option><?php foreach (ETATS as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $et ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    <button class="btn">Filtrer</button> <a class="btn dark" href="?add=1">+ Ajouter un jeu</a></form>
    <div class="tablewrap"><table><tr><th>Jeu</th><th>Catégorie</th><th>Âge</th><th>Capacité</th><th>Tarif</th><th>Responsable</th><th>État</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?><tr><td><?= e($r['emoji'] . ' ' . $r['nom']) ?></td><td><?= e($r['categorie']) ?></td><td><?= (int)$r['age_min'] ?>+</td><td><?= (int)$r['capacite'] ?></td><td><?= money($r['tarif']) ?></td><td><?= e($r['resp'] ?: '—') ?></td>
    <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="etat"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><select name="etat" onchange="this.form.submit()" style="width:auto"><?php foreach (ETATS as $k => $v): ?><option value="<?= e($k) ?>" <?= $k === $r['etat'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></form></td>
    <td><a class="btn sm alt" href="?edit=<?= (int)$r['id'] ?>">Modifier</a> <form method="post" data-confirm="Supprimer ce jeu ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm red">Supprimer</button></form></td></tr><?php endforeach; ?></table></div>
    <?php if (!$rows) echo '<p class="muted">Aucun jeu.</p>';
}
