<?php
// Database fix script for PetVet system
require_once 'config.php';

echo "<h2>Running Database Fixes...</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Check if appointments table has the old column names
    $result = $pdo->query("DESCRIBE appointments");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('date', $columns) && !in_array('appointment_date', $columns)) {
        echo "<p class='info'>Fixing appointments table column names...</p>";
        $pdo->exec("ALTER TABLE appointments CHANGE COLUMN `date` `appointment_date` date DEFAULT NULL");
        $pdo->exec("ALTER TABLE appointments CHANGE COLUMN `time` `appointment_time` time DEFAULT NULL");
        echo "<p class='success'>✅ Appointments table column names fixed</p>";
    } else {
        echo "<p class='info'>Appointments table columns already correct</p>";
    }
    
    // Check if reason column exists
    if (!in_array('reason', $columns)) {
        echo "<p class='info'>Adding missing columns to appointments table...</p>";
        $pdo->exec("ALTER TABLE appointments ADD COLUMN `reason` text DEFAULT NULL AFTER `status`");
        echo "<p class='success'>✅ Reason column added to appointments</p>";
    } else {
        echo "<p class='info'>Reason column already exists</p>";
    }
    
    // Check patients table
    $result = $pdo->query("DESCRIBE patients");
    $patient_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('created_at', $patient_columns)) {
        echo "<p class='info'>Adding missing columns to patients table...</p>";
        $pdo->exec("ALTER TABLE patients ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `gender`");
        echo "<p class='success'>✅ Created_at column added to patients</p>";
    } else {
        echo "<p class='info'>Created_at column already exists</p>";
    }
    
    if (!in_array('weight', $patient_columns)) {
        $pdo->exec("ALTER TABLE patients ADD COLUMN `weight` decimal(5,2) DEFAULT NULL AFTER `age`");
        echo "<p class='success'>✅ Weight column added to patients</p>";
    }
    
    if (!in_array('color', $patient_columns)) {
        $pdo->exec("ALTER TABLE patients ADD COLUMN `color` varchar(50) DEFAULT NULL AFTER `breed`");
        echo "<p class='success'>✅ Color column added to patients</p>";
    }
    
    // Check doctors table
    $result = $pdo->query("DESCRIBE doctors");
    $doctor_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('contact', $doctor_columns) && !in_array('phone', $doctor_columns)) {
        echo "<p class='info'>Fixing doctors table...</p>";
        $pdo->exec("ALTER TABLE doctors CHANGE COLUMN `contact` `phone` varchar(20) DEFAULT NULL");
        echo "<p class='success'>✅ Contact column renamed to phone</p>";
    } else {
        echo "<p class='info'>Phone column already exists</p>";
    }
    
    if (!in_array('email', $doctor_columns)) {
        $pdo->exec("ALTER TABLE doctors ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `phone`");
        echo "<p class='success'>✅ Email column added to doctors</p>";
    } else {
        echo "<p class='info'>Email column already exists</p>";
    }
    
    // Update doctors with email addresses
    echo "<p class='info'>Updating doctors with email addresses...</p>";
    $pdo->exec("UPDATE doctors SET email = CONCAT(LOWER(REPLACE(name, ' ', '.')), '@petvet.com') WHERE email IS NULL");
    echo "<p class='success'>✅ Doctor emails updated</p>";
    
    // Check if appointments table is empty
    $appointment_count = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    
    if ($appointment_count == 0) {
        echo "<p class='info'>Adding sample appointments data...</p>";
        $appointments_data = [
            [1, 1, 'CURDATE()', "'09:00:00'", "'Scheduled'", "'Routine checkup'"],
            [2, 2, 'CURDATE()', "'10:30:00'", "'Scheduled'", "'Vaccination'"],
            [3, 3, 'CURDATE()', "'14:00:00'", "'Completed'", "'Dental cleaning'"],
            [4, 4, 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)', "'11:00:00'", "'Scheduled'", "'Surgery consultation'"],
            [5, 5, 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)', "'15:30:00'", "'Scheduled'", "'Emergency care'"],
            [6, 6, 'DATE_ADD(CURDATE(), INTERVAL -1 DAY)', "'08:00:00'", "'Completed'", "'X-Ray examination'"],
            [7, 7, 'DATE_ADD(CURDATE(), INTERVAL -1 DAY)', "'13:00:00'", "'Completed'", "'Ultrasound'"],
            [8, 8, 'DATE_ADD(CURDATE(), INTERVAL 2 DAY)', "'10:00:00'", "'Scheduled'", "'Microchipping'"],
            [9, 9, 'DATE_ADD(CURDATE(), INTERVAL 2 DAY)', "'16:00:00'", "'Scheduled'", "'Grooming'"],
            [10, 10, 'DATE_ADD(CURDATE(), INTERVAL 3 DAY)', "'09:30:00'", "'Scheduled'", "'Behavioral therapy'"]
        ];
        
        foreach ($appointments_data as $appointment) {
            $sql = "INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, status, reason) VALUES ({$appointment[0]}, {$appointment[1]}, {$appointment[2]}, {$appointment[3]}, {$appointment[4]}, {$appointment[5]})";
            $pdo->exec($sql);
        }
        echo "<p class='success'>✅ Sample appointments added</p>";
    } else {
        echo "<p class='info'>Appointments already exist</p>";
    }
    
    // Update patients table with created_at dates (backfill for existing records)
    echo "<p class='info'>Updating patients with created_at dates...</p>";
    $pdo->exec("UPDATE patients SET created_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY) WHERE created_at IS NULL");
    echo "<p class='success'>✅ Patient dates updated</p>";
    
    // Add some additional sample appointments for better testing
    if ($appointment_count < 15) {
        echo "<p class='info'>Adding additional sample appointments...</p>";
        $additional_appointments = [
            [11, 11, 'CURDATE()', "'12:00:00'", "'Scheduled'", "'Lab testing'"],
            [12, 12, 'CURDATE()', "'17:00:00'", "'Scheduled'", "'Nutrition consultation'"],
            [13, 13, 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)', "'08:30:00'", "'Scheduled'", "'Boarding check-in'"],
            [14, 14, 'DATE_ADD(CURDATE(), INTERVAL 1 DAY)', "'14:30:00'", "'Scheduled'", "'Weight management'"],
            [15, 15, 'DATE_ADD(CURDATE(), INTERVAL 2 DAY)', "'11:30:00'", "'Scheduled'", "'Spay/Neuter surgery'"]
        ];
        
        foreach ($additional_appointments as $appointment) {
            $sql = "INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, status, reason) VALUES ({$appointment[0]}, {$appointment[1]}, {$appointment[2]}, {$appointment[3]}, {$appointment[4]}, {$appointment[5]})";
            $pdo->exec($sql);
        }
        echo "<p class='success'>✅ Additional appointments added</p>";
    }
    
    // Check medicalrecords table
    $result = $pdo->query("DESCRIBE medicalrecords");
    $medical_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('record_date', $medical_columns)) {
        echo "<p class='info'>Adding record_date column to medicalrecords table...</p>";
        $pdo->exec("ALTER TABLE medicalrecords ADD COLUMN `record_date` date DEFAULT NULL AFTER `date`");
        echo "<p class='success'>✅ Record_date column added to medicalrecords</p>";
        
        // Copy data from date column to record_date column
        echo "<p class='info'>Copying date data to record_date...</p>";
        $pdo->exec("UPDATE medicalrecords SET record_date = date WHERE record_date IS NULL");
        echo "<p class='success'>✅ Date data copied to record_date</p>";
    } else {
        echo "<p class='info'>Record_date column already exists</p>";
    }
    
    // Check if cost column exists (for billing)
    if (!in_array('cost', $medical_columns)) {
        echo "<p class='info'>Adding cost column to medicalrecords table...</p>";
        $pdo->exec("ALTER TABLE medicalrecords ADD COLUMN `cost` decimal(10,2) DEFAULT NULL AFTER `bills`");
        echo "<p class='success'>✅ Cost column added to medicalrecords</p>";
        
        // Copy data from bills column to cost column
        echo "<p class='info'>Copying bills data to cost...</p>";
        $pdo->exec("UPDATE medicalrecords SET cost = bills WHERE cost IS NULL");
        echo "<p class='success'>✅ Bills data copied to cost</p>";
    } else {
        echo "<p class='info'>Cost column already exists</p>";
    }
    
    // Add location functionality to doctors table
    echo "<p class='info'>Adding location functionality to doctors table...</p>";
    
    // Check if location_id column exists in doctors table
    if (!in_array('location_id', $doctor_columns)) {
        $pdo->exec("ALTER TABLE doctors ADD COLUMN `location_id` int(11) DEFAULT NULL AFTER `area`");
        echo "<p class='success'>✅ Location_id column added to doctors</p>";
    } else {
        echo "<p class='info'>Location_id column already exists</p>";
    }
    
    // Check if address column exists in doctors table
    if (!in_array('address', $doctor_columns)) {
        $pdo->exec("ALTER TABLE doctors ADD COLUMN `address` text DEFAULT NULL AFTER `location_id`");
        echo "<p class='success'>✅ Address column added to doctors</p>";
    } else {
        echo "<p class='info'>Address column already exists</p>";
    }
    
    // Check if latitude and longitude columns exist in doctors table
    if (!in_array('latitude', $doctor_columns)) {
        $pdo->exec("ALTER TABLE doctors ADD COLUMN `latitude` decimal(10,8) DEFAULT NULL AFTER `address`");
        echo "<p class='success'>✅ Latitude column added to doctors</p>";
    } else {
        echo "<p class='info'>Latitude column already exists</p>";
    }
    
    if (!in_array('longitude', $doctor_columns)) {
        $pdo->exec("ALTER TABLE doctors ADD COLUMN `longitude` decimal(11,8) DEFAULT NULL AFTER `latitude`");
        echo "<p class='success'>✅ Longitude column added to doctors</p>";
    } else {
        echo "<p class='info'>Longitude column already exists</p>";
    }
    
    // Populate locations table with sample data
    echo "<p class='info'>Populating locations table...</p>";
    $locations_data = [
        ['Dhaka'],
        ['Chittagong'],
        ['Sylhet'],
        ['Khulna'],
        ['Barisal'],
        ['Rajshahi'],
        ['Rangpur'],
        ['Mymensingh'],
        ['Comilla'],
        ['Narayanganj']
    ];
    
    foreach ($locations_data as $location) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO locations (city) VALUES (?)");
        $stmt->execute($location);
    }
    echo "<p class='success'>✅ Locations table populated</p>";
    
    // Update doctors with location_id based on their area
    echo "<p class='info'>Updating doctors with location_id...</p>";
    $pdo->exec("UPDATE doctors d JOIN locations l ON d.area = l.city SET d.location_id = l.location_id WHERE d.location_id IS NULL");
    echo "<p class='success'>✅ Doctors location_id updated</p>";
    
    // Add sample addresses and coordinates for doctors
    echo "<p class='info'>Adding sample addresses and coordinates...</p>";
    $addresses = [
        [1, 'House 123, Road 12, Dhanmondi, Dhaka', 23.7461, 90.3768],
        [2, 'Plot 456, Agrabad, Chittagong', 22.3419, 91.8132],
        [3, 'House 789, Zindabazar, Sylhet', 24.8949, 91.8687],
        [4, 'Plot 321, Khalishpur, Khulna', 22.8088, 89.2467],
        [5, 'House 654, Sadar Road, Barisal', 22.7010, 90.3535],
        [6, 'Plot 987, Motihar, Rajshahi', 24.3745, 88.6042],
        [7, 'House 147, Kandirpar, Comilla', 23.4607, 91.1809],
        [8, 'Plot 258, Siddhirganj, Narayanganj', 23.6231, 90.4998],
        [9, 'House 369, Medical College Road, Rajshahi', 24.3745, 88.6042],
        [10, 'Plot 741, Circuit House Road, Mymensingh', 24.7471, 90.4203],
        [11, 'House 852, Banani, Dhaka', 23.7937, 90.4066],
        [12, 'Plot 963, Nasirabad, Chittagong', 22.3419, 91.8132],
        [13, 'House 159, Boyra, Khulna', 22.8088, 89.2467],
        [14, 'Plot 357, Sadar Road, Barisal', 22.7010, 90.3535],
        [15, 'House 486, Zindabazar, Sylhet', 24.8949, 91.8687],
        [16, 'Plot 753, Medical College Road, Rangpur', 25.7439, 89.2752],
        [17, 'House 951, Gulshan, Dhaka', 23.7937, 90.4066],
        [18, 'Plot 264, Medical College Road, Rajshahi', 24.3745, 88.6042],
        [19, 'House 375, Kandirpar, Comilla', 23.4607, 91.1809],
        [20, 'Plot 486, Circuit House Road, Mymensingh', 24.7471, 90.4203]
    ];
    
    foreach ($addresses as $address) {
        $stmt = $pdo->prepare("UPDATE doctors SET address = ?, latitude = ?, longitude = ? WHERE doctor_id = ?");
        $stmt->execute([$address[1], $address[2], $address[3], $address[0]]);
    }
    echo "<p class='success'>✅ Doctor addresses and coordinates updated</p>";
    
    echo "<h3 class='success'>✅ All database fixes completed successfully!</h3>";
    echo "<p><a href='admin/dashboard.php' style='background:blue;color:white;padding:10px;text-decoration:none;border-radius:5px;'>Go to Admin Dashboard</a></p>";
    
} catch(PDOException $e) {
    echo "<h3 class='error'>❌ Error: " . $e->getMessage() . "</h3>";
    echo "<p>Please check your database connection and try again.</p>";
}
?> 