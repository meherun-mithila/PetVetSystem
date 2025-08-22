<?php
// Run database fixes for patients table
require_once 'config.php';

echo "<h2>Running Database Fixes for Patients Table</h2>";

try {
    // Add missing columns to patients table
    $queries = [
        "ALTER TABLE patients ADD COLUMN IF NOT EXISTS `weight` decimal(5,2) DEFAULT NULL AFTER `age`",
        "ALTER TABLE patients ADD COLUMN IF NOT EXISTS `color` varchar(50) DEFAULT NULL AFTER `breed`",
        "ALTER TABLE patients ADD COLUMN IF NOT EXISTS `medical_history` text DEFAULT NULL AFTER `gender`",
        "ALTER TABLE patients ADD COLUMN IF NOT EXISTS `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `medical_history`"
    ];
    
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "<p style='color: green;'>✓ Executed: " . htmlspecialchars($query) . "</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠ Column might already exist: " . htmlspecialchars($query) . "</p>";
        }
    }
    
    // Update sample data
    $updateQueries = [
        "UPDATE patients SET weight = 15.5, color = 'Brown', medical_history = 'No previous issues' WHERE patient_id = 1",
        "UPDATE patients SET weight = 8.2, color = 'Orange', medical_history = 'Regular checkups only' WHERE patient_id = 2",
        "UPDATE patients SET weight = 12.0, color = 'Black', medical_history = 'Vaccinated, healthy' WHERE patient_id = 3",
        "UPDATE patients SET weight = 18.0, color = 'White', medical_history = 'Annual checkup due' WHERE patient_id = 4",
        "UPDATE patients SET weight = 6.5, color = 'Gray', medical_history = 'Spayed, healthy' WHERE patient_id = 5"
    ];
    
    foreach ($updateQueries as $query) {
        try {
            $pdo->exec($query);
            echo "<p style='color: green;'>✓ Updated sample data: " . htmlspecialchars($query) . "</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Failed to update: " . htmlspecialchars($query) . "</p>";
        }
    }
    
    // Verify the structure
    echo "<h3>Current Patients Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE patients");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<h3>Sample Patient Data:</h3>";
    $stmt = $pdo->query("SELECT patient_id, animal_name, weight, color, medical_history FROM patients LIMIT 5");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Weight</th><th>Color</th><th>Medical History</th></tr>";
    foreach ($patients as $patient) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($patient['patient_id']) . "</td>";
        echo "<td>" . htmlspecialchars($patient['animal_name']) . "</td>";
        echo "<td>" . htmlspecialchars($patient['weight'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($patient['color'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($patient['medical_history'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>✓ Database fixes completed successfully!</p>";
    echo "<p><a href='admin/patients.php'>Go to Patients Management</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
