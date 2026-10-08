<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/cards.php';

// جلب الألعاب مرتبة حسب حالة التوفر
$jeux = $pdo->query("SELECT * FROM jeux ORDER BY FIELD(etat, 'disponible', 'en_fonctionnement', 'en_maintenance', 'hors_service', 'ferme'), nom LIMIT 6")->fetchAll();

// جلب أنواع التذاكر النشطة
$types = $pdo->query('SELECT * FROM type_billet WHERE actif=1 ORDER BY prix')->fetchAll();

page_start('Accueil');
?>

<section class="hero">
  <div class="wrap">
    <div>
      <h1>Le parc où chaque tour compte.</h1>
      <p>Montagnes russes, grande roue, maison hantée : réservez votre créneau ou achetez vos billets en ligne, et entrez sans attendre.</p>
      <div class="cta">
        <a class="btn" href="#jeux">Découvrir nos jeux</a>
        <a class="btn alt" href="<?= url('jeux.php') ?>">Réserver maintenant</a>
      </div>
    </div>
    
    <aside class="stub">
      <h3>Billets du jour</h3>
      <span>Un tarif pour chaque visiteur</span>
      <ul>
        <?php foreach ($types as $t): ?>
          <li>
            <span><?= e($t['libelle']) ?></span>
            <b><?= money($t['prix']) ?></b>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>
  </div>
  <div class="bulbs"></div>
</section>

<div class="wrap content" id="jeux">
  <div class="sec-title">
    <h2>Nos attractions</h2>
    <a class="btn alt sm" href="<?= url('jeux.php') ?>">Voir tous les jeux</a>
  </div>
  
  <div class="grid">
    <?php foreach ($jeux as $g): ?>
        <?= game_card($g) ?>
    <?php endforeach; ?>
  </div>
</div>

<?php page_end(); ?>