-- Création de la base de données
CREATE DATABASE IF NOT EXISTS smarte_walet;
USE smarte_walet;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- ⚠️ NOUVEAU - Table pour les codes OTP
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('income', 'expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des revenus (MODIFIÉE - ajout user_id)
CREATE TABLE IF NOT EXISTS incomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    income_date DATE NOT NULL,
    category_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL, -- ⚠️ NOUVEAU
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des dépenses (MODIFIÉE - ajout user_id)
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    expense_date DATE NOT NULL,
    category_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL, -- ⚠️ NOUVEAU
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clés étrangères
ALTER TABLE incomes ADD CONSTRAINT fk_income_category 
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;
    
ALTER TABLE expenses ADD CONSTRAINT fk_expense_category 
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- ⚠️ NOUVEAU - Clés étrangères pour les utilisateurs
ALTER TABLE incomes ADD CONSTRAINT fk_income_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
    
ALTER TABLE expenses ADD CONSTRAINT fk_expense_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

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
SELECT * FROM users;