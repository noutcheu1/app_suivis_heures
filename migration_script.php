<?php

// Script de migration pour ajouter la colonne id à la table intervenants
require_once 'vendor/autoload.php';

$host = '127.0.0.1';
$port = '3307';
$dbname = 'bdchaudoudoux_horaire';
$username = 'chaudoudoux';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion réussie à la base de données\n";
    
    // Vérifier si la colonne id existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM intervenants LIKE 'id'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "La colonne 'id' existe déjà dans la table intervenants\n";
    } else {
        echo "Ajout de la colonne 'id' à la table intervenants...\n";
        
        $sql = "ALTER TABLE intervenants ADD COLUMN id INT AUTO_INCREMENT UNIQUE FIRST";
        $pdo->exec($sql);
        
        echo "Colonne 'id' ajoutée avec succès\n";
    }
    
    // Vérifier la structure finale
    $stmt = $pdo->query("DESCRIBE intervenants");
    echo "\nStructure de la table intervenants:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']}) " . ($row['Key'] ? "KEY: {$row['Key']}" : "") . "\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
