<?php
// Common functions for user dashboard

function getUserPets($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getUserAppointments($pdo, $user_id, $limit = null) {
    try {
        $sql = "
            SELECT a.*, p.animal_name, p.species, d.name as doctor_name, d.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            WHERE p.owner_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getUpcomingAppointments($pdo, $user_id, $limit = null) {
    try {
        $sql = "
            SELECT a.*, p.animal_name, p.species, d.name as doctor_name, d.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            WHERE p.owner_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'Scheduled'
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getUserMedicalRecords($pdo, $user_id, $limit = null) {
    try {
        $sql = "
            SELECT mr.*, p.animal_name, p.species, d.name as doctor_name, d.specialization
            FROM medicalrecords mr
            JOIN patients p ON mr.patient_id = p.patient_id
            JOIN doctors d ON mr.doctor_id = d.doctor_id
            WHERE p.owner_id = ?
            ORDER BY mr.date DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getAvailableDoctors($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE availability = 'Available' ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function calculateTotalBills($medical_records) {
    $total = 0;
    foreach($medical_records as $record) {
        $total += $record['bills'] ?? 0;
    }
    return $total;
}

function formatCurrency($amount) {
    return '৳' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

function getAppointmentStatusClass($status) {
    switch($status) {
        case 'Scheduled': return 'bg-blue-100 text-blue-800';
        case 'Completed': return 'bg-green-100 text-green-800';
        case 'Cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}
?> 