<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$notifications = [];
$error_message = "";

try {
    $user_id = $_SESSION['user_id'];
    
    // Ensure notifications.audience exists or detect its availability
    $hasAudience = true;
    try {
        $pdo->exec("ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `audience` enum('user','staff','admin','all') DEFAULT 'user'");
    } catch (Throwable $t) {
        // Fallback: probe availability via a harmless select
        try {
            $pdo->query("SELECT `audience` FROM `notifications` LIMIT 0");
        } catch (Throwable $t2) {
            $hasAudience = false;
        }
    }
    
    // Ensure notifications.created_at exists or detect its availability
    $hasCreatedAt = true;
    try {
        $pdo->exec("ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `created_at` timestamp DEFAULT CURRENT_TIMESTAMP");
    } catch (Throwable $t) {
        // Fallback: probe availability via a harmless select
        try {
            $pdo->query("SELECT `created_at` FROM `notifications` LIMIT 0");
        } catch (Throwable $t2) {
            $hasCreatedAt = false;
        }
    }
    // Ensure notification_reads table exists; if not, try to create it. Gracefully degrade if not permitted.
    $hasReadsTable = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `notification_reads` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `notification_id` int(11) NOT NULL,
            `reader_type` enum('user','staff','admin') NOT NULL,
            `reader_id` int(11) NOT NULL,
            `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `notification_id` (`notification_id`),
            KEY `reader_idx` (`reader_type`, `reader_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    } catch (Throwable $t) {
        $hasReadsTable = false;
    }
    
    // Get all notifications for the user: direct, audience 'user', or 'all'
    if ($hasReadsTable) {
        if ($hasAudience) {
            $stmt = $pdo->prepare(" 
                SELECT n.*, 
                       EXISTS(
                           SELECT 1 FROM notification_reads r 
                           WHERE r.notification_id = n.notification_id AND r.reader_type='user' AND r.reader_id = ?
                       ) AS already_read
                FROM notifications n
                WHERE (n.user_id = ?)
                   OR (n.audience IN ('user','all') AND n.user_id IS NULL)
                ORDER BY " . ($hasCreatedAt ? "n.created_at DESC" : "n.notification_id DESC") . "
            ");
            $stmt->execute([$user_id, $user_id]);
        } else {
            // No audience column: show direct to user and any broadcast (user_id IS NULL)
            $stmt = $pdo->prepare(" 
                SELECT n.*, 
                       EXISTS(
                           SELECT 1 FROM notification_reads r 
                           WHERE r.notification_id = n.notification_id AND r.reader_type='user' AND r.reader_id = ?
                       ) AS already_read
                FROM notifications n
                WHERE (n.user_id = ?)
                   OR (n.user_id IS NULL)
                ORDER BY " . ($hasCreatedAt ? "n.created_at DESC" : "n.notification_id DESC") . "
            ");
            $stmt->execute([$user_id, $user_id]);
        }
    } else {
        // Fallback without read tracking table
        if ($hasAudience) {
            $stmt = $pdo->prepare(" 
                SELECT n.*, 0 AS already_read
                FROM notifications n
                WHERE (n.user_id = ?)
                   OR (n.audience IN ('user','all') AND n.user_id IS NULL)
                ORDER BY " . ($hasCreatedAt ? "n.created_at DESC" : "n.notification_id DESC") . "
            ");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $pdo->prepare(" 
                SELECT n.*, 0 AS already_read
                FROM notifications n
                WHERE (n.user_id = ?)
                   OR (n.user_id IS NULL)
                ORDER BY " . ($hasCreatedAt ? "n.created_at DESC" : "n.notification_id DESC") . "
            ");
            $stmt->execute([$user_id]);
        }
    }
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark notifications as read if they were clicked
    if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
        $notification_id = (int)$_GET['mark_read'];
        if ($hasReadsTable) {
            $stmt = $pdo->prepare("INSERT INTO notification_reads (notification_id, reader_type, reader_id, read_at)
                SELECT ?, 'user', ?, NOW() FROM DUAL WHERE NOT EXISTS (
                    SELECT 1 FROM notification_reads WHERE notification_id=? AND reader_type='user' AND reader_id=?
                )");
            $stmt->execute([$notification_id, $user_id, $notification_id, $user_id]);
        } // else: silently ignore mark-read when table doesn't exist
        
        // Redirect to remove the GET parameter
        header("Location: notifications.php");
        exit();
    }
    
} catch(PDOException $e) {
    $error_message = "Failed to load notifications: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'vet-blue': '#2c5aa0',
                        'vet-dark-blue': '#1e3d72',
                        'vet-coral': '#ff6b6b'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-vet-blue text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?></h1>
            <nav class="flex items-center space-x-6">
                <a href="dashboard.php" class="text-white hover:text-gray-200 transition-colors">Dashboard</a>
                <a href="pets.php" class="text-white hover:text-gray-200 transition-colors">My Pets</a>
                <a href="appointments.php" class="text-white hover:text-gray-200 transition-colors">Appointments</a>
                <a href="adoption.php" class="text-white hover:text-gray-200 transition-colors">Adoption</a>
                <a href="notifications.php" class="text-white font-semibold border-b-2 border-white">Notifications</a>
                <a href="../logout.php" class="bg-vet-dark-blue px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">Logout</a>
            </nav>
        </div>
    </header>

    <div class="max-w-4xl mx-auto p-6">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Notifications</h2>
            <p class="text-gray-600 mt-2">Appointment alerts, approvals, vaccination reminders, and more</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($notifications)): ?>
            <div class="space-y-4">
                <?php foreach($notifications as $notification): ?>
                    <?php
                        $type = $notification['type'] ?? 'general';
                        $styles = 'border-blue-500';
                        $icon = '📢';
                        if ($type === 'appointment') { $styles = 'border-indigo-500'; $icon='📅'; }
                        if ($type === 'approval') { $styles = 'border-green-500'; $icon='✅'; }
                        if ($type === 'vaccination') { $styles = 'border-yellow-500'; $icon='💉'; }
                    ?>
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 <?php echo $styles; ?>">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="text-2xl"><?php echo $icon; ?></span>
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        <?php
                                            switch($type) {
                                                case 'appointment': echo 'Appointment Alert'; break;
                                                case 'approval': echo 'Approval Update'; break;
                                                case 'vaccination': echo 'Vaccination Reminder'; break;
                                                default: echo 'Notification';
                                            }
                                        ?>
                                    </h3>
                                </div>
                                
                                <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($notification['message']); ?></p>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">
                                        <?php 
                                        if ($hasCreatedAt && isset($notification['created_at'])) {
                                            echo date('M j, Y \a\t g:i A', strtotime($notification['created_at']));
                                        } else {
                                            echo 'Recent';
                                        }
                                        ?>
                                    </span>
                                    <?php if (!$notification['already_read']): ?>
                                        <a href="?mark_read=<?php echo $notification['notification_id']; ?>" class="text-sm text-vet-blue hover:text-vet-dark-blue font-medium">Mark as Read</a>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400">✓ Read</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-md">
                <div class="text-6xl mb-4">🔔</div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No Notifications</h3>
                <p class="text-gray-600 mb-4">You're all caught up! No new notifications at the moment.</p>
                <div class="space-x-4">
                    <a href="adoption.php" class="inline-flex items-center px-4 py-2 bg-vet-blue text-white rounded-lg hover:bg-vet-dark-blue transition-colors">Browse Pets for Adoption</a>
                    <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">Go to Dashboard</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
