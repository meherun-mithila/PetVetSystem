<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/status_helper.php';

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$adoption_requests = [];
$adoption_listings = [];
$error_message = "";

// Get staff user information
$staff_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Staff Member';

try {
    // Get all adoption requests with details (read-only for staff)
    $stmt = $pdo->prepare("
        SELECT ar.*, al.animal_name, al.species, al.age, al.description, al.status as listing_status,
               u.name as requester_name, u.email as requester_email, u.phone as requester_phone,
               p.name as posted_by_name
        FROM adoptionrequests ar
        JOIN adoptionlistings al ON ar.listing_id = al.listing_id
        JOIN users u ON ar.requested_by = u.user_id
        JOIN users p ON al.posted_by = p.user_id
        ORDER BY ar.date DESC
    ");
    $stmt->execute();
    $adoption_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all adoption listings (read-only for staff)
    $stmt = $pdo->prepare("
        SELECT al.*, u.name as posted_by_name, u.email as posted_by_email,
               COUNT(ar.request_id) as request_count
        FROM adoptionlistings al
        JOIN users u ON al.posted_by = u.user_id
        LEFT JOIN adoptionrequests ar ON al.listing_id = ar.listing_id
        GROUP BY al.listing_id
        ORDER BY al.listing_id DESC
    ");
    $stmt->execute();
    $adoption_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Failed to load data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoption Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Adoption Management</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($staff_name); ?></span>
                <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <div class="mb-6">
            <h2 class="text-3xl font-bold">Adoption Management</h2>
            <p class="text-gray-600 mt-2">View adoption requests and listings (Read-only access)</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Adoption Requests Section -->
        <div class="mb-8">
            <h3 class="text-2xl font-semibold mb-4">Adoption Requests</h3>
            
            <?php if (!empty($adoption_requests)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requester</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posted By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($adoption_requests as $request): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($request['animal_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['species']); ?> • Age: <?php echo htmlspecialchars($request['age']); ?> years</div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($request['description'], 0, 50)) . (strlen($request['description']) > 50 ? '...' : ''); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($request['requester_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['requester_email']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['requester_phone'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($request['date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo displayStatusBadge($request['status'], 'adoption'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($request['posted_by_name']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <p class="text-gray-500">No adoption requests found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Adoption Listings Section -->
        <div class="mb-8">
            <h3 class="text-2xl font-semibold mb-4">Adoption Listings</h3>
            
            <?php if (!empty($adoption_listings)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posted By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requests</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($adoption_listings as $listing): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($listing['animal_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($listing['species']); ?> • Age: <?php echo htmlspecialchars($listing['age']); ?> years</div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($listing['description'], 0, 50)) . (strlen($listing['description']) > 50 ? '...' : ''); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($listing['posted_by_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($listing['posted_by_email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_color = '';
                                    $status_bg = '';
                                    switch($listing['status']) {
                                        case 'Available':
                                            $status_color = 'text-green-800';
                                            $status_bg = 'bg-green-100';
                                            break;
                                        case 'Adopted':
                                            $status_color = 'text-blue-800';
                                            $status_bg = 'bg-blue-100';
                                            break;
                                        case 'Pending':
                                            $status_color = 'text-yellow-800';
                                            $status_bg = 'bg-yellow-100';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_bg . ' ' . $status_color; ?>">
                                        <?php echo $listing['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $listing['request_count']; ?> request(s)
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <p class="text-gray-500">No adoption listings found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
