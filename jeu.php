<?php
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php';
$s = $pdo->prepare('SELECT j.*, CONCAT(u.prenom," ",u.nom) resp FROM jeux j LEFT JOIN utilisateurs u ON u.id=j.id_responsable WHERE j.id=?');
$s->execute([(int)($_GET['id'] ?? 0)]); $g = $s->fetch();
if (!$g) { http_response_code(404); flash('Jeu introuvable.', 'err'); redirect('jeux.php'); }
$ok = bookable($g['etat']); $gi = game_img($g);
page_start($g['nom']);
?>
<div class="card"><div class="formgrid" style="align-items:center">
  <div class="game" style="border:0"><div class="pic" style="--c:<?= e($g['couleur']) ?>;border-radius:14px;height:260px;font-size:8rem<?= $gi ? ";background-image:url('" . e($gi) . "')" : '' ?>"><?= $gi ? '' : e($g['emoji']) ?></div></div>
  <div><h2><?= e($g['nom']) ?></h2><p><?= etat_badge($g['etat']) ?> <?= badge($g['categorie'], '') ?></p><p><?= nl2br(e($g['description'])) ?></p>
    <div class="meta"><span>👥 Capacité : <?= (int)$g['capacite'] ?> par session</span><span>⏱ <?= (int)$g['duree_session'] ?> min</span><span>🎂 Dès <?= (int)$g['age_min'] ?> ans</span><span>En service depuis le <?= e(date('d/m/Y', strtotime($g['date_mise_service']))) ?></span></div>
    <p class="total"><?= money($g['tarif']) ?> <small class="muted">par personne</small></p>
    <div class="actions"><a class="btn <?= $ok ? '' : 'off' ?>" href="<?= url('reserver.php?jeu=' . (int)$g['id']) ?>">Réserver</a><a class="btn alt <?= $ok ? '' : 'off' ?>" href="<?= url('acheter.php?jeu=' . (int)$g['id']) ?>">Acheter un billet</a></div>
    <?php if (!$ok): ?><p class="muted">Ce jeu n'est pas disponible pour le moment : réservation et achat sont désactivés.</p><?php endif; ?></div></div></div>
<?php page_end();
