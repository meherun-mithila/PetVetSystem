<?php
require_once 'config.php';

try {
    $pdo->exec("INSERT IGNORE INTO staff (name, email, password, role) VALUES 
        ('Sarah Wilson', 'staff@petvet.com', 'staff123', 'Receptionist'),
        ('Mike Johnson', 'mike@petvet.com', 'staff123', 'Nurse'),
        ('Lisa Chen', 'lisa@petvet.com', 'staff123', 'Veterinary Technician'),
        ('David Brown', 'david@petvet.com', 'staff123', 'Lab Assistant')");
    
    echo "Staff data added successfully!";
} catch(PDOException $e) {
    echo "Error adding staff data: " . $e->getMessage();
}
?> 