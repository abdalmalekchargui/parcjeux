<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

page_start('À propos');
?>
<div class="wrap content">
  <div class="about-card">
    <div class="about-content">
      <span class="about-badge">🎪 Découvrir <?= e(PARC_NOM) ?></span>
      <h1>Un parc, six attractions, zéro file d'attente inutile</h1>
      <p>ParcJeux réunit des attractions pour tous les âges : sensations fortes, jeux en famille et manèges pour les tout-petits. Chaque jeu est suivi par un responsable dédié, entretenu selon un calendrier de révisions, et son état est visible en temps réel avant de réserver.</p>
      <p>Ce site permet de consulter les jeux, réserver un créneau, acheter des billets et retrouver ses tickets à tout moment.</p>

      <div class="about-stats">
        <div class="stat-mini"><b>6+</b><span>Attractions</span></div>
        <div class="stat-mini"><b>100%</b><span>En ligne</span></div>
        <div class="stat-mini"><b>0 min</b><span>Attente</span></div>
      </div>
    </div>

    <div class="about-visual">
      <div class="about-logo-box">
        <span class="logo-emoji">🎪</span>
        <h2><?= e(PARC_NOM) ?></h2>
        <p>L'aventure et le plaisir commencent ici !</p>
      </div>
    </div>
  </div>
</div>
<?php page_end(); ?>