<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$company_phone_bd = '+880 1712-345678';

try {
    // Discover available columns on staff table
    $cols = [];
    try {
        $result = $pdo->query('DESCRIBE staff');
        $cols = $result->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        $cols = [];
    }
    $hasPhone = in_array('phone', $cols, true);

    // If phone column is missing, try to add it
    if (!$hasPhone) {
        try {
            $pdo->exec("ALTER TABLE staff ADD COLUMN phone VARCHAR(32) NULL AFTER email");
            // Re-check schema
            $result = $pdo->query('DESCRIBE staff');
            $cols = $result->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $hasPhone = in_array('phone', $cols, true);
        } catch (Throwable $e) {
            // Ignore if cannot alter; we'll still return data without phone field
        }
    }

    $fields = 'name, email, role' . ($hasPhone ? ', phone' : '');

    // Prefer receptionist/front desk roles
    $stmt = $pdo->query("SELECT $fields FROM staff WHERE LOWER(role) IN ('receptionist','front desk','frontdesk','front-desk') ORDER BY name");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        // Fallback to any staff
        $fallback = $pdo->query("SELECT $fields FROM staff ORDER BY staff_id LIMIT 2");
        $rows = $fallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    echo json_encode([
        'success' => true,
        'contacts' => array_map(function($r) use ($hasPhone, $company_phone_bd) {
            $phone = $hasPhone ? (string)($r['phone'] ?? '') : '';
            if ($phone === '') {
                $phone = $company_phone_bd; // Fallback to BD company phone when staff phone is missing
            }
            return [
                'name' => (string)($r['name'] ?? ''),
                'email' => (string)($r['email'] ?? ''),
                'role' => (string)($r['role'] ?? ''),
                'phone' => $phone,
            ];
        }, $rows),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load support contacts']);
}

