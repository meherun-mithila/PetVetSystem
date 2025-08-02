<?php
require_once 'config.php';

echo "<h2>Database Connection Test</h2>";

try {
    echo "<p>✅ Database connection successful!</p>";
    
    // Check tables
    echo "<h3>Tables in database:</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach($tables as $table) {
        echo "<p>- $table</p>";
    }
    
    // Check users table
    echo "<h3>Users table:</h3>";
    $users = $pdo->query("SELECT user_id, name, email FROM users LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if ($users) {
        foreach($users as $user) {
            echo "<p>ID: {$user['user_id']}, Name: {$user['name']}, Email: {$user['email']}</p>";
        }
    } else {
        echo "<p>No users found</p>";
    }
    
    // Check patients table
    echo "<h3>Patients table:</h3>";
    $patients = $pdo->query("SELECT patient_id, animal_name, owner_id FROM patients LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if ($patients) {
        foreach($patients as $patient) {
            echo "<p>ID: {$patient['patient_id']}, Name: {$patient['animal_name']}, Owner: {$patient['owner_id']}</p>";
        }
    } else {
        echo "<p>No patients found</p>";
    }
    
    // Check appointments table
    echo "<h3>Appointments table:</h3>";
    $appointments = $pdo->query("SELECT appointment_id, patient_id, doctor_id, appointment_date FROM appointments LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if ($appointments) {
        foreach($appointments as $appointment) {
            echo "<p>ID: {$appointment['appointment_id']}, Patient: {$appointment['patient_id']}, Doctor: {$appointment['doctor_id']}, Date: {$appointment['appointment_date']}</p>";
        }
    } else {
        echo "<p>No appointments found</p>";
    }
    
    // Check doctors table
    echo "<h3>Doctors table:</h3>";
    $doctors = $pdo->query("SELECT doctor_id, name, specialization FROM doctors LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if ($doctors) {
        foreach($doctors as $doctor) {
            echo "<p>ID: {$doctor['doctor_id']}, Name: {$doctor['name']}, Specialization: {$doctor['specialization']}</p>";
        }
    } else {
        echo "<p>No doctors found</p>";
    }
    
} catch(PDOException $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
?> 