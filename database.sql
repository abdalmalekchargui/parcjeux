-- Parc de Jeux - schéma MySQL (MCD -> MLD)
CREATE DATABASE IF NOT EXISTS parc_jeux CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parc_jeux;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS depenses, recettes, maintenance, billets, commandes, reservations, type_billet, affectations, jeux, utilisateurs;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE utilisateurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(60) NOT NULL,
  prenom VARCHAR(60) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  telephone VARCHAR(25) NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role ENUM('admin','responsable','agent','client') NOT NULL DEFAULT 'client',
  fonction VARCHAR(80) NULL,
  date_embauche DATE NULL,
  statut ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE jeux (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  categorie VARCHAR(50) NOT NULL,
  age_min TINYINT UNSIGNED NOT NULL DEFAULT 0,
  capacite SMALLINT UNSIGNED NOT NULL CHECK (capacite > 0),
  duree_session SMALLINT UNSIGNED NOT NULL COMMENT 'minutes',
  tarif DECIMAL(8,2) NOT NULL CHECK (tarif >= 0),
  etat ENUM('disponible','en_fonctionnement','en_maintenance','hors_service','ferme') NOT NULL DEFAULT 'disponible',
  image VARCHAR(255) NULL,
  emoji VARCHAR(16) NOT NULL DEFAULT '🎪',
  couleur VARCHAR(7) NOT NULL DEFAULT '#e63946',
  date_mise_service DATE NOT NULL,
  id_responsable INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_responsable) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE affectations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_jeu INT NOT NULL,
  id_responsable INT NOT NULL,
  date_debut DATE NOT NULL,
  date_fin DATE NULL,
  FOREIGN KEY (id_jeu) REFERENCES jeux(id) ON DELETE CASCADE,
  FOREIGN KEY (id_responsable) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE type_billet (
  id INT AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(60) NOT NULL UNIQUE,
  prix DECIMAL(8,2) NOT NULL CHECK (prix >= 0),
  actif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(20) NOT NULL UNIQUE,
  id_client INT NOT NULL,
  id_jeu INT NOT NULL,
  date_visite DATE NOT NULL,
  heure TIME NOT NULL,
  nb_personnes SMALLINT UNSIGNED NOT NULL CHECK (nb_personnes > 0),
  montant_total DECIMAL(10,2) NOT NULL,
  statut ENUM('en_attente','confirmee','payee','utilisee','annulee') NOT NULL DEFAULT 'confirmee',
  telephone VARCHAR(25) NULL,
  commentaire VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (id_jeu, date_visite, heure),
  FOREIGN KEY (id_client) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (id_jeu) REFERENCES jeux(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero VARCHAR(20) NOT NULL UNIQUE,
  id_client INT NULL COMMENT 'NULL = vente sur place anonyme',
  id_agent INT NULL,
  id_jeu INT NOT NULL,
  id_type INT NOT NULL,
  quantite SMALLINT UNSIGNED NOT NULL CHECK (quantite > 0),
  montant_total DECIMAL(10,2) NOT NULL,
  mode_paiement ENUM('carte','especes','simulation') NOT NULL DEFAULT 'simulation',
  statut_paiement ENUM('en_attente','paye','rembourse') NOT NULL DEFAULT 'paye',
  date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_client) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  FOREIGN KEY (id_agent) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  FOREIGN KEY (id_jeu) REFERENCES jeux(id) ON DELETE CASCADE,
  FOREIGN KEY (id_type) REFERENCES type_billet(id)
) ENGINE=InnoDB;

CREATE TABLE billets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_commande INT NOT NULL,
  id_type INT NOT NULL,
  id_jeu INT NOT NULL,
  code_unique VARCHAR(20) NOT NULL UNIQUE,
  date_visite DATE NOT NULL,
  prix_unitaire DECIMAL(8,2) NOT NULL,
  statut_utilisation ENUM('valide','utilise','annule') NOT NULL DEFAULT 'valide',
  date_utilisation DATETIME NULL,
  FOREIGN KEY (id_commande) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (id_type) REFERENCES type_billet(id),
  FOREIGN KEY (id_jeu) REFERENCES jeux(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE maintenance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_jeu INT NOT NULL,
  id_responsable INT NULL,
  type_intervention ENUM('panne','corrective','preventive','revision') NOT NULL,
  description TEXT NOT NULL,
  cout DECIMAL(10,2) NOT NULL DEFAULT 0,
  date_intervention DATE NOT NULL,
  date_prochaine_revision DATE NULL,
  statut ENUM('signalee','planifiee','en_cours','terminee') NOT NULL DEFAULT 'signalee',
  FOREIGN KEY (id_jeu) REFERENCES jeux(id) ON DELETE CASCADE,
  FOREIGN KEY (id_responsable) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE recettes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categorie ENUM('billets','reservations','abonnements','autres') NOT NULL,
  description VARCHAR(255) NOT NULL,
  montant DECIMAL(10,2) NOT NULL CHECK (montant >= 0),
  date_recette DATE NOT NULL,
  id_jeu INT NULL,
  id_commande INT NULL,
  id_reservation INT NULL,
  id_utilisateur INT NULL,
  FOREIGN KEY (id_jeu) REFERENCES jeux(id) ON DELETE SET NULL,
  FOREIGN KEY (id_commande) REFERENCES commandes(id) ON DELETE SET NULL,
  FOREIGN KEY (id_reservation) REFERENCES reservations(id) ON DELETE SET NULL,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE depenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(120) NOT NULL,
  categorie VARCHAR(40) NOT NULL,
  description VARCHAR(255) NULL,
  montant DECIMAL(10,2) NOT NULL CHECK (montant > 0),
  date_depense DATE NOT NULL,
  type ENUM('jeu','general') NOT NULL DEFAULT 'general',
  id_jeu INT NULL,
  id_utilisateur INT NULL,
  id_maintenance INT NULL,
  justificatif VARCHAR(255) NULL,
  FOREIGN KEY (id_jeu) REFERENCES jeux(id) ON DELETE SET NULL,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  FOREIGN KEY (id_maintenance) REFERENCES maintenance(id) ON DELETE SET NULL
) ENGINE=InnoDB;

USE parc_jeux;
INSERT INTO utilisateurs (id,nom,prenom,email,telephone,mot_de_passe,role,fonction,date_embauche) VALUES
(1,'Benali','Samir','admin@parcjeux.ma','0600000001','Admin@123','admin','Directeur du parc','2020-01-10'),
(2,'Alaoui','Karim','responsable@parcjeux.ma','0600000002','Responsable@123','responsable','Responsable grands manèges','2021-03-01'),
(3,'Tazi','Salma','resp2@parcjeux.ma','0600000003','Responsable@123','responsable','Responsable attractions familiales','2022-06-15'),
(4,'Idrissi','Youssef','agent@parcjeux.ma','0600000004','Agent@123','agent','Agent d''accueil','2023-02-01'),
(5,'Amrani','Lina','client@parcjeux.ma','0611111111','Client@123','client',NULL,NULL),
(6,'Berrada','Omar','omar@example.com','0622222222','Client@123','client',NULL,NULL),
(7,'Chraibi','Nada','nada@example.com','0633333333','Client@123','client',NULL,NULL),
(8,'Fassi','Mehdi','mehdi@example.com','0644444444','Client@123','client',NULL,NULL);

INSERT INTO jeux (id,nom,description,categorie,age_min,capacite,duree_session,tarif,etat,emoji,couleur,date_mise_service,id_responsable) VALUES
(1,'Montagnes russes','Looping, vrilles et 90 km/h : le grand frisson du parc sur 800 mètres de rails.','Sensations fortes',12,24,4,40,'disponible','🎢','#e63946','2021-05-01',2),
(2,'Grande roue','Cinquante mètres de hauteur pour voir toute la ville au coucher du soleil.','Famille',0,60,12,35,'en_fonctionnement','🎡','#f4a100','2021-05-01',3),
(3,'Autos tamponneuses','Foncez, tamponnez, recommencez : une piste électrique pour 20 conducteurs.','Famille',6,20,6,25,'disponible','🚗','#12b5a6','2022-04-10',3),
(4,'Carrousel','Le classique des plus petits, avec chevaux sculptés et musique d''orgue.','Enfants',2,30,5,20,'disponible','🎠','#d6409f','2020-09-01',3),
(5,'Maison hantée','Un parcours de 15 minutes dans le noir, pour les courageux uniquement.','Sensations fortes',10,12,15,45,'disponible','👻','#6c4bd1','2023-10-01',2),
(6,'Bateau pirate','Un grand balancier qui monte à 20 mètres : les ventres se nouent.','Sensations fortes',10,30,5,30,'en_maintenance','🏴‍☠️','#1f6feb','2022-07-01',2);

INSERT INTO affectations (id_jeu,id_responsable,date_debut) SELECT id,id_responsable,date_mise_service FROM jeux WHERE id_responsable IS NOT NULL;

INSERT INTO type_billet (id,libelle,prix) VALUES (1,'Enfant',30),(2,'Adulte',60),(3,'Famille',180),(4,'VIP',120),(5,'Pass Journée',150),(6,'Abonnement',900);

INSERT INTO reservations (id,numero,id_client,id_jeu,date_visite,heure,nb_personnes,montant_total,statut,telephone,created_at) VALUES
(1,'RES-0001',5,1,DATE_SUB(CURDATE(),INTERVAL 20 DAY),'10:00:00',4,160,'utilisee','0611111111',DATE_SUB(NOW(),INTERVAL 25 DAY)),
(2,'RES-0002',6,2,DATE_SUB(CURDATE(),INTERVAL 10 DAY),'16:00:00',3,105,'payee','0622222222',DATE_SUB(NOW(),INTERVAL 15 DAY)),
(3,'RES-0003',7,3,DATE_ADD(CURDATE(),INTERVAL 3 DAY),'14:00:00',5,125,'confirmee','0633333333',DATE_SUB(NOW(),INTERVAL 2 DAY)),
(4,'RES-0004',8,5,DATE_ADD(CURDATE(),INTERVAL 5 DAY),'18:00:00',2,90,'en_attente','0644444444',DATE_SUB(NOW(),INTERVAL 1 DAY)),
(5,'RES-0005',5,4,DATE_ADD(CURDATE(),INTERVAL 7 DAY),'11:00:00',6,120,'payee','0611111111',DATE_SUB(NOW(),INTERVAL 1 DAY)),
(6,'RES-0006',6,1,DATE_SUB(CURDATE(),INTERVAL 40 DAY),'12:00:00',4,160,'annulee','0622222222',DATE_SUB(NOW(),INTERVAL 45 DAY)),
(7,'RES-0007',7,5,DATE_SUB(CURDATE(),INTERVAL 70 DAY),'20:00:00',3,135,'utilisee','0633333333',DATE_SUB(NOW(),INTERVAL 75 DAY)),
(8,'RES-0008',5,2,DATE_ADD(CURDATE(),INTERVAL 10 DAY),'15:00:00',2,70,'confirmee','0611111111',NOW());

INSERT INTO commandes (id,numero,id_client,id_agent,id_jeu,id_type,quantite,montant_total,mode_paiement,statut_paiement,date_commande) VALUES
(1,'CMD-0001',5,NULL,1,2,2,120,'simulation','paye',DATE_SUB(NOW(),INTERVAL 150 DAY)),
(2,'CMD-0002',6,NULL,2,3,1,180,'simulation','paye',DATE_SUB(NOW(),INTERVAL 120 DAY)),
(3,'CMD-0003',7,NULL,3,1,3,90,'simulation','paye',DATE_SUB(NOW(),INTERVAL 90 DAY)),
(4,'CMD-0004',5,NULL,1,4,2,240,'simulation','paye',DATE_SUB(NOW(),INTERVAL 60 DAY)),
(5,'CMD-0005',8,NULL,5,2,4,240,'simulation','paye',DATE_SUB(NOW(),INTERVAL 45 DAY)),
(6,'CMD-0006',6,NULL,4,1,2,60,'simulation','paye',DATE_SUB(NOW(),INTERVAL 30 DAY)),
(7,'CMD-0007',5,4,1,2,3,180,'especes','paye',DATE_SUB(NOW(),INTERVAL 14 DAY)),
(8,'CMD-0008',7,NULL,2,3,2,360,'simulation','paye',DATE_SUB(NOW(),INTERVAL 7 DAY)),
(9,'CMD-0009',8,NULL,1,4,2,240,'simulation','paye',DATE_SUB(NOW(),INTERVAL 2 DAY)),
(10,'CMD-0010',5,NULL,5,2,1,60,'simulation','paye',DATE_SUB(NOW(),INTERVAL 1 DAY));

INSERT INTO billets (id_commande,id_type,id_jeu,code_unique,date_visite,prix_unitaire,statut_utilisation,date_utilisation)
SELECT c.id,c.id_type,c.id_jeu,CONCAT('PJ-',UPPER(SUBSTRING(MD5(CONCAT(c.id,'-',s.n)),1,8))),DATE(c.date_commande),t.prix,
  IF(c.id<=7,'utilise','valide'), IF(c.id<=7,c.date_commande,NULL)
FROM commandes c JOIN type_billet t ON t.id=c.id_type
JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) s ON s.n<=c.quantite;

INSERT INTO recettes (categorie,description,montant,date_recette,id_jeu,id_commande)
SELECT 'billets',CONCAT('Vente ',numero),montant_total,DATE(date_commande),id_jeu,id FROM commandes WHERE statut_paiement='paye';
INSERT INTO recettes (categorie,description,montant,date_recette,id_jeu,id_reservation)
SELECT 'reservations',CONCAT('Réservation ',numero),montant_total,DATE(created_at),id_jeu,id FROM reservations WHERE statut IN ('payee','utilisee');
INSERT INTO recettes (categorie,description,montant,date_recette,id_utilisateur) VALUES
('autres','Sponsoring - banque partenaire',2500,DATE_SUB(CURDATE(),INTERVAL 100 DAY),1),
('autres','Location de stand boutique',800,DATE_SUB(CURDATE(),INTERVAL 20 DAY),1),
('abonnements','Abonnement annuel - famille Amrani',900,DATE_SUB(CURDATE(),INTERVAL 35 DAY),1);

INSERT INTO maintenance (id,id_jeu,id_responsable,type_intervention,description,cout,date_intervention,date_prochaine_revision,statut) VALUES
(1,1,2,'preventive','Contrôle des freins et graissage des essieux',650,DATE_SUB(CURDATE(),INTERVAL 80 DAY),DATE_ADD(CURDATE(),INTERVAL 10 DAY),'terminee'),
(2,3,3,'corrective','Remplacement de deux moteurs de voitures',900,DATE_SUB(CURDATE(),INTERVAL 40 DAY),NULL,'terminee'),
(3,6,2,'panne','Bruit anormal sur le mécanisme du balancier',1200,DATE_SUB(CURDATE(),INTERVAL 3 DAY),DATE_ADD(CURDATE(),INTERVAL 30 DAY),'en_cours'),
(4,2,3,'revision','Révision annuelle de la structure',0,DATE_ADD(CURDATE(),INTERVAL 12 DAY),NULL,'planifiee');

INSERT INTO depenses (libelle,categorie,description,montant,date_depense,type,id_jeu,id_utilisateur,id_maintenance) VALUES
('Maintenance préventive','maintenance','Contrôle freins',650,DATE_SUB(CURDATE(),INTERVAL 80 DAY),'jeu',1,1,1),
('Moteurs autos tamponneuses','pieces','Deux moteurs neufs',900,DATE_SUB(CURDATE(),INTERVAL 40 DAY),'jeu',3,1,2),
('Réparation balancier','maintenance','Diagnostic et pièces',1200,DATE_SUB(CURDATE(),INTERVAL 3 DAY),'jeu',6,2,3),
('Électricité - mois -5','electricite',NULL,1800,DATE_SUB(CURDATE(),INTERVAL 150 DAY),'general',NULL,1,NULL),
('Électricité - mois -3','electricite',NULL,1950,DATE_SUB(CURDATE(),INTERVAL 90 DAY),'general',NULL,1,NULL),
('Électricité - mois -1','electricite',NULL,2100,DATE_SUB(CURDATE(),INTERVAL 28 DAY),'general',NULL,1,NULL),
('Salaires - mois -4','salaires','Équipe permanente',1500,DATE_SUB(CURDATE(),INTERVAL 120 DAY),'general',NULL,1,NULL),
('Salaires - mois -2','salaires','Équipe permanente',1500,DATE_SUB(CURDATE(),INTERVAL 60 DAY),'general',NULL,1,NULL),
('Salaires - mois -1','salaires','Équipe permanente',1500,DATE_SUB(CURDATE(),INTERVAL 30 DAY),'general',NULL,1,NULL),
('Sécurité privée','securite','Société de gardiennage',2400,DATE_SUB(CURDATE(),INTERVAL 45 DAY),'general',NULL,1,NULL),
('Campagne affichage','marketing','Affiches et réseaux sociaux',1500,DATE_SUB(CURDATE(),INTERVAL 20 DAY),'general',NULL,1,NULL),
('Nettoyage des allées','nettoyage',NULL,700,DATE_SUB(CURDATE(),INTERVAL 10 DAY),'general',NULL,1,NULL);
