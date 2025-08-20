<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

try {
    // Prefer receptionist/front desk roles
    $stmt = $pdo->query("SELECT name, email, role FROM staff WHERE LOWER(role) IN ('receptionist','front desk','frontdesk','front-desk') ORDER BY name");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        // Fallback to any staff
        $fallback = $pdo->query("SELECT name, email, role FROM staff ORDER BY staff_id LIMIT 2");
        $rows = $fallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    echo json_encode([
        'success' => true,
        'contacts' => array_map(function($r) {
            return [
                'name' => (string)($r['name'] ?? ''),
                'email' => (string)($r['email'] ?? ''),
                'role' => (string)($r['role'] ?? ''),
            ];
        }, $rows),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load support contacts']);
}

