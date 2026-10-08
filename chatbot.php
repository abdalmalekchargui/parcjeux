<?php
// Assistant FAQ basique (mots-clés) - répond en JSON
header('Content-Type: application/json; charset=utf-8');
$q = mb_strtolower(mb_substr(trim((string)($_GET['q'] ?? '')), 0, 200));
$faq = [
 [['horaire', 'ouvert', 'heure', 'fermeture'], 'Le parc est ouvert tous les jours de 10h à 21h. Les créneaux de réservation vont de 10h à 20h.'],
 [['tarif', 'prix', 'combien', 'coût', 'cout'], 'Les prix varient selon le jeu (page « Jeux ») et le type de billet (Enfant, Adulte, Famille, VIP, Pass Journée, Abonnement). Consultez l\'accueil pour les tarifs des billets.'],
 [['réserv', 'reserv'], 'Cliquez sur « Réserver » sur le jeu choisi, indiquez date, heure et nombre de personnes. Vous devez avoir un compte. Vous pouvez ensuite payer (simulation) et imprimer votre ticket.'],
 [['annul', 'rembours'], 'Une réservation est annulable depuis « Mes réservations » jusqu\'à la veille de la visite. Si elle était payée, elle est remboursée.'],
 [['billet', 'ticket', 'acheter'], 'Achetez un billet depuis la fiche d\'un jeu. Chaque billet a un code unique à présenter à l\'accueil ; retrouvez-les dans « Mes billets ».'],
 [['âge', 'age', 'enfant', 'minimum'], 'Chaque jeu a un âge minimum affiché sur sa fiche : de 2 ans (carrousel) à 12 ans (montagnes russes).'],
 [['maintenance', 'panne', 'indisponible'], 'Un jeu en maintenance ne peut pas être réservé ni acheté. Son état est affiché sur sa carte.'],
 [['compte', 'inscri', 'connexion', 'mot de passe'], 'Créez un compte gratuitement via « Inscription ». Le mot de passe doit contenir au moins 8 caractères, lettres et chiffres.'],
 [['contact', 'téléphone', 'adresse'], 'Écrivez-nous depuis la page Contact ou passez à l\'accueil du parc.'],
 [['bonjour', 'salut', 'hello'], 'Bonjour ! Que souhaitez-vous savoir sur le parc ?'],
];
foreach ($faq as [$keys, $rep]) foreach ($keys as $k) if (str_contains($q, $k)) { echo json_encode(['reponse' => $rep], JSON_UNESCAPED_UNICODE); exit; }
echo json_encode(['reponse' => 'Je n\'ai pas la réponse. Essayez : horaires, tarifs, réservation, annulation, billets, âge minimum.'], JSON_UNESCAPED_UNICODE);
