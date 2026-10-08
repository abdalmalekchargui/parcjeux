# ParcJeux — gestion d'un parc de jeux (PHP / MySQL / PDO)

## Installation (XAMPP / WAMP)
1. Copier le dossier `parcjeux` dans `htdocs` (XAMPP) ou `www` (WAMP).
2. Démarrer Apache et MySQL, ouvrir phpMyAdmin et **importer `database.sql`** (crée la base `parc_jeux` + données de démonstration).
3. Si besoin, adapter `config/config.php` (dossier `BASE_URL`, identifiants MySQL).
4. Ouvrir http://localhost/parcjeux/

PHP 8.1+ requis (extensions pdo_mysql, mbstring, fileinfo : activées par défaut dans XAMPP).

## Comptes de démonstration
| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@parcjeux.ma | Admin@123 |
| Responsable de jeu | responsable@parcjeux.ma | Responsable@123 |
| Agent d'accueil | agent@parcjeux.ma | Agent@123 |
| Client | client@parcjeux.ma | Client@123 |

## Structure
```
config/config.php        paramètres (BDD, devise, nom du parc)
includes/                bootstrap (PDO, sessions, CSRF, RBAC), layout, composants, CRUD partagés
index.php, jeux.php, jeu.php, a-propos.php, contact.php      site public
login/register/logout/profil.php                              authentification
reserver.php, acheter.php, mes_reservations.php, mes_billets.php, ticket.php   espace client
chatbot.php              assistant FAQ (mots-clés)
admin/                   dashboard (+api_stats.php), jeux, users (responsables/agents/clients),
                         reservations, billets, recettes, depenses, maintenance, parametres
responsable/             dashboard, jeux, reservations, maintenance, depenses
agent/                   validate (billets), vente (sur place), reservations
assets/css, assets/js    interface (responsive, animations, compteurs, chatbot)
uploads/                 justificatifs (exécution PHP interdite via .htaccess)
database.sql             schéma complet + jeu de données
```

## Règles de gestion (à reprendre dans le rapport)
- **Réservation** : jeu réservable seulement s'il est « disponible » ou « en fonctionnement ». Capacité contrôlée par créneau (jeu + date + heure), avec verrou transactionnel (`SELECT … FOR UPDATE`) contre les doubles réservations.
- **Statuts** : en attente → confirmée → payée → utilisée ; annulée. Paiement simulé : crée la recette automatiquement.
- **Annulation** : par le client jusqu'à la veille de la visite ; une réservation payée est remboursée (recette retirée).
- **Billets** : un code unique par billet ; prix total = prix du type de billet × quantité (recalculé côté serveur) ; une recette est créée à chaque vente. Un billet n'est utilisable qu'une fois et à partir de sa date de visite.
- **Maintenance** : une panne/intervention corrective ouverte met le jeu « en maintenance » (réservation et achat bloqués) ; à la clôture, il redevient disponible. Un coût saisi crée une dépense liée.
- **Résultat** = total des recettes − total des dépenses.
- Un jeu ayant des réservations ou ventes ne peut pas être supprimé (passer en « Fermé temporairement »). Les clients ne sont pas supprimés, seulement désactivés.

## Sécurité
Mots de passe stockés en clair (visibles dans phpMyAdmin, choix pédagogique pour le projet) · PDO + requêtes préparées (émulation désactivée) · échappement `htmlspecialchars` de toute sortie · jeton CSRF sur tous les POST · `session_regenerate_id` à la connexion + cookies HttpOnly/SameSite · limitation des tentatives de connexion · contrôle de rôle sur chaque page (`require_role`) et filtrage par propriétaire (responsable) · upload limité (PDF/JPG/PNG, 2 Mo, type MIME vérifié, nom aléatoire).

## Bibliothèques externes (à citer dans la documentation)
Chart.js 4.4.1 (cdnjs) pour les graphiques du dashboard · Google Fonts « Bricolage Grotesque » (repli sur la police système si hors ligne). Aucun framework PHP.

## Limites connues / pistes
- Le « PDF » est une page imprimable (Imprimer → Enregistrer au format PDF) ; pour générer de vrais fichiers PDF, ajouter Dompdf ou FPDF.
- Les images des jeux sont des illustrations emoji colorées ; renseigner une URL d'image dans le formulaire d'un jeu pour utiliser une photo.
- Les livrables de conception (MCD, MLD, MCT), le rapport, le guide utilisateur et le PowerPoint restent à produire par votre groupe ; le schéma `database.sql` correspond au MLD.
