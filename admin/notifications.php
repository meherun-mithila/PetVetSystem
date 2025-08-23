<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? ($_SESSION['user_email'] ?? 'Admin');





// Fetch notifications with read stats
$notifications = [];
try {
    $stmt = $pdo->prepare("SELECT n.*, 
        SUM(CASE WHEN nr.reader_type='user' THEN 1 ELSE 0 END) AS user_reads,
        SUM(CASE WHEN nr.reader_type='staff' THEN 1 ELSE 0 END) AS staff_reads,
        SUM(CASE WHEN nr.reader_type='admin' THEN 1 ELSE 0 END) AS admin_reads
        FROM notifications n
        LEFT JOIN notification_reads nr ON nr.notification_id = n.notification_id
        GROUP BY n.notification_id
        ORDER BY n.created_at DESC");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Failed to load notifications: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Notifications</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6 space-y-6">


        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Recent Notifications</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Audience</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reads</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($notifications as $n): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($n['message']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($n['type']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($n['audience'] ?? 'user'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo $n['user_id'] ? ('User #' . (int)$n['user_id']) : ($n['audience'] === 'specific' ? 'User' : 'Broadcast'); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="mr-2">Users: <?php echo (int)($n['user_reads'] ?? 0); ?></span>
                                <span class="mr-2">Staff: <?php echo (int)($n['staff_reads'] ?? 0); ?></span>
                                <span>Admins: <?php echo (int)($n['admin_reads'] ?? 0); ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


</body>
</html>
