-- Création de la base de données
CREATE DATABASE IF NOT EXISTS smarte_welet;
USE smarte_welet;

-- Table des revenus
CREATE TABLE IF NOT EXISTS incomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    income_date DATE NOT NULL,
    category_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des dépenses (CORRIGÉE)
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    expense_date DATE NOT NULL,
    category_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('income', 'expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clés étrangères
ALTER TABLE incomes ADD CONSTRAINT fk_income_category 
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;
    
ALTER TABLE expenses ADD CONSTRAINT fk_expense_category 
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- Insertion de catégories par défaut
INSERT INTO categories (name, type) VALUES
('Salaire', 'income'),
('Freelance', 'income'),
('Investissement', 'income'),
('Autre revenu', 'income'),
('Alimentation', 'expense'),
('Transport', 'expense'),
('Logement', 'expense'),
('Loisirs', 'expense'),
('Santé', 'expense'),
('Éducation', 'expense'),
('Autre dépense', 'expense');

-- Données de test
INSERT INTO incomes (description, amount, income_date, category_id) VALUES
('Salaire Novembre', 8000.00, '2024-11-01', 1),
('Projet Freelance', 2500.00, '2024-11-15', 2);

INSERT INTO expenses (description, amount, expense_date, category_id) VALUES
('Courses du mois', 1200.00, '2024-11-05', 5),
('Loyer', 3000.00, '2024-11-01', 7),
('Essence', 600.00, '2024-11-10', 6);