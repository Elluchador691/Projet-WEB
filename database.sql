-- ============================================================
-- database.sql — Schéma de la base de données IdeaStage
-- 
-- Instructions :
--   1. Ouvre phpMyAdmin (http://localhost/phpmyadmin)
--   2. Crée une base de données nommée "ideastage"
--   3. Clique sur "Importer" et sélectionne ce fichier
-- ============================================================

CREATE DATABASE IF NOT EXISTS ideastage
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ideastage;

-- ─────────────────────────────────────────
-- Table : utilisateurs
-- Stocke les comptes étudiants et recruteurs
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS utilisateurs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    prenom      VARCHAR(100)        NOT NULL,
    nom         VARCHAR(100)        NOT NULL,
    email       VARCHAR(255)        NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255)       NOT NULL,          -- Hashé avec password_hash()
    role        ENUM('etudiant','recruteur') NOT NULL DEFAULT 'etudiant',
    created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────
-- Table : offres
-- Toutes les offres de stage / alternance
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS offres (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    recruteur_id    INT             NOT NULL,
    titre           VARCHAR(255)    NOT NULL,
    entreprise      VARCHAR(150)    NOT NULL,
    type            ENUM('Stage','Alternance') NOT NULL,
    localisation    VARCHAR(150)    NOT NULL,
    duree           VARCHAR(50)     NOT NULL,           -- Ex : "6 mois", "24 mois"
    salaire         DECIMAL(8,2)    DEFAULT NULL,       -- Mensuel en €
    teletravail     VARCHAR(50)     DEFAULT NULL,       -- Ex : "2 jours/semaine"
    date_debut      DATE            DEFAULT NULL,
    description     TEXT            NOT NULL,
    competences     TEXT            DEFAULT NULL,       -- JSON array ex: ["React","Node.js"]
    a_la_une        TINYINT(1)      NOT NULL DEFAULT 0,
    actif           TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recruteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- Table : candidatures
-- Candidatures envoyées par les étudiants
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS candidatures (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    offre_id    INT             NOT NULL,
    prenom      VARCHAR(100)    NOT NULL,
    nom         VARCHAR(100)    NOT NULL,
    email       VARCHAR(255)    NOT NULL,
    telephone   VARCHAR(30)     DEFAULT NULL,
    message     TEXT            NOT NULL,
    cv_filename VARCHAR(255)    DEFAULT NULL,           -- Nom du fichier CV uploadé
    statut      ENUM('envoyée','vue','retenue','refusée') NOT NULL DEFAULT 'envoyée',
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offre_id) REFERENCES offres(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- Données de démonstration
-- ─────────────────────────────────────────

-- Compte recruteur de démo (mot de passe : "demo1234")
INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, role) VALUES
('Admin', 'IdeaStage', 'admin@ideastage.fr', '$2y$12$YourHashedPasswordHere', 'recruteur');

-- Offres de démo
INSERT INTO offres (recruteur_id, titre, entreprise, type, localisation, duree, salaire, teletravail, date_debut, description, competences, a_la_une) VALUES
(1, 'Développeur Full-Stack React / Node', 'TechVision', 'Alternance', 'Paris', '24 mois', 1200.00, '2 jours/semaine', '2026-09-01',
 'Nous recherchons un développeur Full-Stack capable de travailler sur des applications modernes en React et Node.js.',
 '["React","Node.js","TypeScript","API REST","Git"]', 1),

(1, 'Stage UX/UI Designer', 'DesignStudio', 'Stage', 'Lyon', '6 mois', 1200.00, NULL, '2026-06-01',
 'DesignStudio est une agence spécialisée dans la conception d interfaces digitales modernes.',
 '["Figma","Sketch","Prototypage"]', 0),

(1, 'Alternance Data Analyst', 'DataCorp', 'Alternance', 'Bordeaux', '12 mois', 1100.00, NULL, '2026-09-01',
 'DataCorp est une entreprise spécialisée dans l analyse et l exploitation des données.',
 '["Python","SQL","Power BI"]', 1),

(1, 'Stage Marketing Digital', 'GrowthHive', 'Stage', 'Nantes', '4 mois', 700.00, NULL, '2026-06-01',
 'GrowthHive est une agence spécialisée dans l acquisition digitale.',
 '["SEO","Google Ads","Analytics"]', 1),

(1, 'Alternance Cybersécurité', 'SecureNet', 'Alternance', 'Toulouse', '24 mois', 1300.00, NULL, '2026-09-01',
 'SecureNet est une entreprise spécialisée dans la sécurité informatique.',
 '["Réseau","Pentest","SIEM"]', 1),

(1, 'Stage Finance d Entreprise', 'FinGroup', 'Stage', 'Paris', '6 mois', 1000.00, NULL, '2026-06-01',
 'FinGroup est un cabinet de conseil financier accompagnant les entreprises.',
 '["Excel","Analyse financière","Reporting"]', 1);