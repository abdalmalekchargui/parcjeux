<?php
require __DIR__ . '/../includes/bootstrap.php'; require __DIR__ . '/../includes/layout.php'; require __DIR__ . '/../includes/jeux_crud.php';
$me = require_role('admin');
$role = $_GET['role'] ?? $_POST['role'] ?? 'client'; if (!in_array($role, ['responsable', 'agent', 'client'], true)) $role = 'client';
$titre = ['responsable' => 'Responsables', 'agent' => 'Agents d\'accueil', 'client' => 'Clients'][$role];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $act = $_POST['action'] ?? ''; $id = post_int('id');
    $ex = null; if ($id) { $s = $pdo->prepare('SELECT * FROM utilisateurs WHERE id=? AND role=?'); $s->execute([$id, $role]); $ex = $s->fetch(); if (!$ex) { flash('Utilisateur introuvable.', 'err'); redirect("admin/users.php?role=$role"); } }
    if ($act === 'toggle' && $ex) { $pdo->prepare('UPDATE utilisateurs SET statut=? WHERE id=?')->execute([$ex['statut'] === 'actif' ? 'inactif' : 'actif', $id]); flash('Statut modifié.'); }
    elseif ($act === 'delete' && $ex && $role !== 'client') { $pdo->prepare('DELETE FROM utilisateurs WHERE id=?')->execute([$id]); flash('Compte supprimé (ses jeux sont désaffectés).'); }
    elseif ($act === 'save') {
        $d = ['nom' => clean('nom', 60), 'prenom' => clean('prenom', 60), 'email' => clean('email', 120), 'tel' => clean('telephone', 25), 'fonction' => clean('fonction', 80), 'emb' => clean('date_embauche', 10), 'statut' => ($_POST['statut'] ?? '') === 'inactif' ? 'inactif' : 'actif'];
        $pw = (string)($_POST['password'] ?? ''); $err = [];
        if ($d['nom'] === '' || $d['prenom'] === '') $err[] = 'Nom et prénom obligatoires.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Email invalide.';
        if ($d['tel'] !== '' && !preg_match('/^[0-9 +().-]{8,20}$/', $d['tel'])) $err[] = 'Téléphone invalide.';
        if ($d['emb'] !== '' && !valid_date($d['emb'])) $err[] = 'Date d\'embauche invalide.';
        if (($id === 0 || $pw !== '') && (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw))) $err[] = 'Mot de passe : 8 caractères, lettres et chiffres.';
        $c = $pdo->prepare('SELECT id FROM utilisateurs WHERE email=? AND id<>?'); $c->execute([$d['email'], $id]); if ($c->fetch()) $err[] = 'Email déjà utilisé.';
        if ($err) { flash(implode(' ', $err), 'err'); redirect("admin/users.php?role=$role" . ($id ? "&edit=$id" : '&add=1')); }
        if ($id) {
            $pdo->prepare('UPDATE utilisateurs SET nom=?,prenom=?,email=?,telephone=?,fonction=?,date_embauche=?,statut=? WHERE id=?')->execute([$d['nom'], $d['prenom'], $d['email'], $d['tel'] ?: null, $d['fonction'] ?: null, $d['emb'] ?: null, $d['statut'], $id]);
            if ($pw !== '') $pdo->prepare('UPDATE utilisateurs SET mot_de_passe=? WHERE id=?')->execute([$pw, $id]);
        } else {
            $pdo->prepare('INSERT INTO utilisateurs (nom,prenom,email,telephone,mot_de_passe,role,fonction,date_embauche,statut) VALUES (?,?,?,?,?,?,?,?,?)')->execute([$d['nom'], $d['prenom'], $d['email'], $d['tel'] ?: null, $pw, $role, $d['fonction'] ?: null, $d['emb'] ?: null, $d['statut']]);
            $id = (int)$pdo->lastInsertId();
        }
        if ($role === 'responsable') { // affectations multiples
            $sel = array_map('intval', (array)($_POST['jeux'] ?? []));
            $cur = $pdo->prepare('SELECT id FROM jeux WHERE id_responsable=?'); $cur->execute([$id]); $cur = array_map('intval', $cur->fetchAll(PDO::FETCH_COLUMN));
            foreach (array_diff($cur, $sel) as $j) assign_game($pdo, $j, null);
            foreach (array_diff($sel, $cur) as $j) assign_game($pdo, $j, $id);
        }
        flash('Compte enregistré.');
    }
    redirect("admin/users.php?role=$role");
}
page_start($titre, 'admin');
$edit = null; if (isset($_GET['edit'])) { $s = $pdo->prepare('SELECT * FROM utilisateurs WHERE id=? AND role=?'); $s->execute([(int)$_GET['edit'], $role]); $edit = $s->fetch() ?: null; }
if ($edit || isset($_GET['add'])):
    $x = $edit ?: ['id' => 0, 'nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'fonction' => '', 'date_embauche' => '', 'statut' => 'actif'];
    $jeux = $role === 'responsable' ? $pdo->query('SELECT id,nom,id_responsable FROM jeux ORDER BY nom')->fetchAll() : []; ?>
<div class="card"><h3><?= $edit ? 'Modifier' : 'Ajouter' ?> : <?= e($titre) ?></h3><form method="post" class="formgrid"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><input type="hidden" name="role" value="<?= e($role) ?>">
<div><label>Nom</label><input name="nom" value="<?= e($x['nom']) ?>" required></div><div><label>Prénom</label><input name="prenom" value="<?= e($x['prenom']) ?>" required></div>
<div><label>Email</label><input type="email" name="email" value="<?= e($x['email']) ?>" required></div><div><label>Téléphone</label><input name="telephone" value="<?= e($x['telephone']) ?>"></div>
<div><label>Mot de passe <?= $edit ? '(laisser vide pour conserver)' : '' ?></label><input type="password" name="password" minlength="8" <?= $edit ? '' : 'required' ?>></div>
<div><label>Statut</label><select name="statut"><option value="actif" <?= $x['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option><option value="inactif" <?= $x['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option></select></div>
<?php if ($role !== 'client'): ?><div><label>Fonction</label><input name="fonction" value="<?= e($x['fonction']) ?>"></div><div><label>Date d'embauche</label><input type="date" name="date_embauche" value="<?= e($x['date_embauche']) ?>"></div><?php endif; ?>
<?php if ($role === 'responsable'): ?><div class="full"><label>Jeux affectés (Ctrl/Cmd pour en choisir plusieurs)</label><select name="jeux[]" multiple size="6"><?php foreach ($jeux as $j): ?><option value="<?= (int)$j['id'] ?>" <?= (int)$j['id_responsable'] === (int)$x['id'] && $x['id'] ? 'selected' : '' ?>><?= e($j['nom']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
<div class="full"><button class="btn">Enregistrer</button> <a class="btn alt" href="?role=<?= e($role) ?>">Annuler</a></div></form></div>
<?php else:
    $q = trim($_GET['q'] ?? ''); $p = [$role]; $sql = "SELECT u.*, (SELECT GROUP_CONCAT(nom SEPARATOR ', ') FROM jeux WHERE id_responsable=u.id) jeux FROM utilisateurs u WHERE role=?";
    if ($q !== '') { $sql .= ' AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
    $st = $pdo->prepare($sql . ' ORDER BY nom'); $st->execute($p); $rows = $st->fetchAll(); ?>
<form class="filters"><input type="hidden" name="role" value="<?= e($role) ?>"><div><label>Recherche</label><input name="q" value="<?= e($q) ?>" placeholder="Nom, prénom ou email"></div><button class="btn">Chercher</button> <a class="btn dark" href="?role=<?= e($role) ?>&add=1">+ Ajouter</a></form>
<div class="tablewrap"><table><tr><th>Nom</th><th>Email</th><th>Téléphone</th><?php if ($role === 'responsable'): ?><th>Jeux affectés</th><?php endif; ?><th>Statut</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['prenom'] . ' ' . $r['nom']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['telephone'] ?: '—') ?></td><?php if ($role === 'responsable'): ?><td><?= e($r['jeux'] ?: '—') ?></td><?php endif; ?><td><?= badge($r['statut'], $r['statut'] === 'actif' ? 'st-payee' : 'st-annulee') ?></td>
<td><a class="btn sm alt" href="?role=<?= e($role) ?>&edit=<?= (int)$r['id'] ?>">Modifier</a>
<form method="post"><?= csrf_field() ?><input type="hidden" name="role" value="<?= e($role) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn sm"><?= $r['statut'] === 'actif' ? 'Désactiver' : 'Activer' ?></button></form>
<?php if ($role !== 'client'): ?><form method="post" data-confirm="Supprimer ce compte ?"><?= csrf_field() ?><input type="hidden" name="role" value="<?= e($role) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn sm red">Supprimer</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php endif; page_end('admin');
