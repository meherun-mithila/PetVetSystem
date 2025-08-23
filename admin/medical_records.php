<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$medical_records = [];
$error_message = "";

try {
    // Pagination settings
    $valid_sizes = [10, 25, 50, 100];
    $records_per_page = isset($_GET['size']) && in_array((int)$_GET['size'], $valid_sizes, true) ? (int)$_GET['size'] : 25;
    $current_page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

    // Total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM medicalrecords");
    $count_stmt->execute();
    $total_records = (int)$count_stmt->fetchColumn();

    $total_pages = max(1, (int)ceil($total_records / $records_per_page));
    if ($current_page > $total_pages) { $current_page = $total_pages; }
    $offset = ($current_page - 1) * $records_per_page;

    // Page fetch
    $stmt = $pdo->prepare(
        "SELECT mr.*, p.animal_name, p.species, u.name as owner_name, d.name as doctor_name, d.specialization
         FROM medicalrecords mr
         JOIN patients p ON mr.patient_id = p.patient_id
         JOIN users u ON p.owner_id = u.user_id
         JOIN doctors d ON mr.doctor_id = d.doctor_id
         ORDER BY mr.date DESC
         LIMIT " . (int)$records_per_page . " OFFSET " . (int)$offset
    );
    $stmt->execute();
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load medical records.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Medical Records</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-bold">Medical Records Management</h2>
                <p class="text-gray-600 mt-1">Showing page <?php echo (int)$current_page; ?> of <?php echo (int)$total_pages; ?> (<?php echo (int)$total_records; ?> total)</p>
            </div>
            <div>
                <label class="text-sm text-gray-600 mr-2">Page size</label>
                <select onchange="changePageSize(this.value)" class="border-gray-300 rounded-md px-2 py-1">
                    <?php foreach([10,25,50,100] as $size): ?>
                        <option value="<?php echo $size; ?>" <?php echo $records_per_page===$size? 'selected' : ''; ?>><?php echo $size; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($medical_records)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diagnosis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($medical_records as $record): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['animal_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['species']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['owner_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['specialization']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($record['date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 50)) . (strlen($record['diagnosis']) > 50 ? '...' : ''); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                ৳<?php echo number_format($record['bills'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-600">
                    Showing
                    <?php
                        $from = $total_records ? ($offset + 1) : 0;
                        $to = min($offset + $records_per_page, $total_records);
                        echo $from . ' to ' . $to . ' of ' . $total_records . ' records';
                    ?>
                </div>
                <div class="flex items-center space-x-1">
                    <?php
                        $queryBase = '?size=' . (int)$records_per_page . '&page=';
                        $prevDisabled = $current_page <= 1;
                        $nextDisabled = $current_page >= $total_pages;
                    ?>
                    <a href="<?php echo $prevDisabled ? '#' : $queryBase . ($current_page - 1); ?>" class="px-3 py-1 rounded border <?php echo $prevDisabled ? 'text-gray-400 border-gray-200 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'; ?>">Prev</a>
                    <?php
                        $start = max(1, $current_page - 2);
                        $end = min($total_pages, $current_page + 2);
                        if ($start > 1) echo '<span class=\'px-2\'>...</span>';
                        for ($i=$start; $i<=$end; $i++) {
                            $active = $i === $current_page;
                            echo '<a href="' . $queryBase . $i . '" class="px-3 py-1 rounded border ' . ($active ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 hover:bg-gray-50') . '">' . $i . '</a>';
                        }
                        if ($end < $total_pages) echo '<span class=\'px-2\'>...</span>';
                    ?>
                    <a href="<?php echo $nextDisabled ? '#' : $queryBase . ($current_page + 1); ?>" class="px-3 py-1 rounded border <?php echo $nextDisabled ? 'text-gray-400 border-gray-200 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'; ?>">Next</a>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">No medical records found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 