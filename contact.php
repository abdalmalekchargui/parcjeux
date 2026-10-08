<?php
require __DIR__ . '/includes/bootstrap.php'; require __DIR__ . '/includes/layout.php';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); if (filter_var(clean('email', 120), FILTER_VALIDATE_EMAIL) && clean('message', 1000) !== '') { $sent = true; flash('Merci, votre message a bien été envoyé.'); redirect('contact.php'); } else flash('Email valide et message requis.', 'err'); }
page_start('Contact');
?>
<div class="card narrow" style="max-width:640px"><h2>Nous écrire</h2><p class="muted">Accueil du parc : 10h-21h, tous les jours.</p>
<form method="post" class="formgrid"><?= csrf_field() ?><div><label>Nom</label><input name="nom" required></div><div><label>Email</label><input type="email" name="email" required></div>
<div class="full"><label>Message</label><textarea name="message" rows="5" maxlength="1000" required></textarea></div><div class="full"><button class="btn">Envoyer</button></div></form></div>
<?php page_end();
