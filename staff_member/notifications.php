<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$error_message = "";
$success_message = "";
$staff_id = $_SESSION['staff_id'] ?? ($_SESSION['user_id'] ?? null);
$staff_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? ($_SESSION['user_email'] ?? 'Staff');

// Mark as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $nid = (int)$_GET['read'];
    try {
        $stmt = $pdo->prepare("INSERT INTO notification_reads (notification_id, reader_type, reader_id, read_at)
            SELECT ?, 'staff', ?, NOW() FROM DUAL WHERE NOT EXISTS (
                SELECT 1 FROM notification_reads WHERE notification_id=? AND reader_type='staff' AND reader_id=?
            )");
        $stmt->execute([$nid, $staff_id, $nid, $staff_id]);
        $success_message = 'Marked as read.';
    } catch (PDOException $e) {
        $error_message = 'Failed to mark as read: ' . $e->getMessage();
    }
}

// Fetch notifications for staff audience or broadcast
$notifications = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications 
        WHERE audience IN ('all','staff') OR (audience='admin' AND 1=0)
        ORDER BY created_at DESC");
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
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($staff_name); ?></span>
                <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-5xl mx-auto p-6">
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($notifications as $n): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($n['message']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($n['type']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?read=<?php echo $n['notification_id']; ?>" class="text-blue-600 hover:text-blue-800">Mark as read</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
