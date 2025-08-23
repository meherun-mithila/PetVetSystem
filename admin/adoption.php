<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/status_helper.php';

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$adoption_requests = [];
$adoption_listings = [];
$error_message = "";
$success_message = "";

// Get admin user information
$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin User';

// Ensure notifications table supports optional 'type' column; detect availability
$hasNotificationType = true;
try {
    // Probe for column existence
    $pdo->query("SELECT `type` FROM `notifications` LIMIT 0");
} catch (Throwable $t) {
    $hasNotificationType = false;
    // Try to add it if possible
    try {
        $pdo->exec("ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `type` varchar(50) DEFAULT 'general'");
        // Probe again
        try { $pdo->query("SELECT `type` FROM `notifications` LIMIT 0"); $hasNotificationType = true; } catch (Throwable $t2) { /* ignore */ }
    } catch (Throwable $t3) {
        // ignore; will insert without 'type'
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_request':
                try {
                    $request_id = $_POST['request_id'];
                    $listing_id = $_POST['listing_id'];
                    
                    // Update request status to approved
                    $stmt = $pdo->prepare("UPDATE adoptionrequests SET status = 'Approved' WHERE request_id = ?");
                    $stmt->execute([$request_id]);
                    
                    // Update listing status to adopted
                    $stmt = $pdo->prepare("UPDATE adoptionlistings SET status = 'Adopted' WHERE listing_id = ?");
                    $stmt->execute([$listing_id]);
                    
                    // Reject all other pending requests for this listing
                    $stmt = $pdo->prepare("UPDATE adoptionrequests SET status = 'Rejected' WHERE listing_id = ? AND request_id != ? AND status = 'Pending'");
                    $stmt->execute([$listing_id, $request_id]);
                    
                    // Create notification for the approved user
                    $stmt = $pdo->prepare("SELECT requested_by FROM adoptionrequests WHERE request_id = ?");
                    $stmt->execute([$request_id]);
                    $user_id = $stmt->fetchColumn();
                    
                    if ($user_id) {
                        if ($hasNotificationType) {
                            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'adoption_approved', NOW())");
                            $stmt->execute([$user_id, "Your adoption request has been approved! Please contact us to complete the adoption process."]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$user_id, "Your adoption request has been approved! Please contact us to complete the adoption process."]);
                        }
                    }
                    
                    $success_message = "Adoption request approved successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to approve request: " . $e->getMessage();
                }
                break;
                
            case 'reject_request':
                try {
                    $request_id = $_POST['request_id'];
                    
                    // Update request status to rejected
                    $stmt = $pdo->prepare("UPDATE adoptionrequests SET status = 'Rejected' WHERE request_id = ?");
                    $stmt->execute([$request_id]);
                    
                    // Create notification for the rejected user
                    $stmt = $pdo->prepare("SELECT requested_by FROM adoptionrequests WHERE request_id = ?");
                    $stmt->execute([$request_id]);
                    $user_id = $stmt->fetchColumn();
                    
                    if ($user_id) {
                        if ($hasNotificationType) {
                            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'adoption_rejected', NOW())");
                            $stmt->execute([$user_id, "Your adoption request has been rejected. Please contact us for more information."]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$user_id, "Your adoption request has been rejected. Please contact us for more information."]);
                        }
                    }
                    
                    $success_message = "Adoption request rejected successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to reject request: " . $e->getMessage();
                }
                break;
                
            case 'add_listing':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO adoptionlistings (posted_by, animal_name, species, age, description, status)
                        VALUES (?, ?, ?, ?, ?, 'Available')
                    ");
                    $stmt->execute([
                        $_POST['posted_by'],
                        $_POST['animal_name'],
                        $_POST['species'],
                        $_POST['age'],
                        $_POST['description']
                    ]);
                    $success_message = "Adoption listing added successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to add listing: " . $e->getMessage();
                }
                break;
                
            case 'update_listing_status':
                try {
                    $listing_id = $_POST['listing_id'];
                    $new_status = $_POST['new_status'];
                    
                    $stmt = $pdo->prepare("UPDATE adoptionlistings SET status = ? WHERE listing_id = ?");
                    $stmt->execute([$new_status, $listing_id]);
                    
                    $success_message = "Listing status updated successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to update status: " . $e->getMessage();
                }
                break;
        }
    }
}

try {
    // Get all adoption requests with details
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
    
    // Get all adoption listings
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
    
    // Get all users for posting listings
    $stmt = $pdo->prepare("SELECT user_id, name, email FROM users ORDER BY name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Adoption Management</h2>
            <button onclick="showAddListingForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Add New Listing
            </button>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Add Listing Form -->
        <div id="addListingForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Add New Adoption Listing</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add_listing">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Posted By</label>
                    <select name="posted_by" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select User</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pet Name</label>
                    <input type="text" name="animal_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Species</label>
                    <input type="text" name="species" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Age (Years)</label>
                    <input type="number" name="age" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Add Listing
                    </button>
                    <button type="button" onclick="hideAddListingForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($adoption_requests as $request): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($request['animal_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['species']); ?> • Age: <?php echo htmlspecialchars($request['age']); ?> years</div>
                                    <div class="text-sm text-gray-500">Posted by: <?php echo htmlspecialchars($request['posted_by_name']); ?></div>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($request['status'] === 'Pending'): ?>
                                        <form method="POST" class="inline-block mr-2">
                                            <input type="hidden" name="action" value="approve_request">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <input type="hidden" name="listing_id" value="<?php echo $request['listing_id']; ?>">
                                            <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Approve this adoption request?')">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="action" value="reject_request">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Reject this adoption request?')">
                                                Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400">No actions available</span>
                                    <?php endif; ?>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="update_listing_status">
                                        <input type="hidden" name="listing_id" value="<?php echo $listing['listing_id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded px-2 py-1">
                                            <option value="Available" <?php echo $listing['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="Adopted" <?php echo $listing['status'] === 'Adopted' ? 'selected' : ''; ?>>Adopted</option>
                                            <option value="Pending" <?php echo $listing['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        </select>
                                    </form>
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

    <script>
        function showAddListingForm() {
            document.getElementById('addListingForm').classList.remove('hidden');
        }
        
        function hideAddListingForm() {
            document.getElementById('addListingForm').classList.add('hidden');
        }
        
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
