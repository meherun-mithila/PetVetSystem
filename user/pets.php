<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$user_id = $_SESSION['user_id'];
$pets = [];
$error_message = "";
$success_message = "";

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $animal_name = trim($_POST['animal_name']);
            $species = trim($_POST['species']);
            $breed = trim($_POST['breed']);
            $age = (int)$_POST['age'];
            $weight = (float)$_POST['weight'];
            $color = trim($_POST['color']);
            
            if (empty($animal_name) || empty($species)) {
                $error_message = "Pet name and species are required.";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO patients (owner_id, animal_name, species, breed, age, weight, color, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$user_id, $animal_name, $species, $breed, $age, $weight, $color]);
                    $success_message = "Pet registered successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to register pet.";
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $patient_id = (int)$_POST['patient_id'];
            $animal_name = trim($_POST['animal_name']);
            $species = trim($_POST['species']);
            $breed = trim($_POST['breed']);
            $age = (int)$_POST['age'];
            $weight = (float)$_POST['weight'];
            $color = trim($_POST['color']);
            
            if (empty($animal_name) || empty($species)) {
                $error_message = "Pet name and species are required.";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE patients 
                        SET animal_name = ?, species = ?, breed = ?, age = ?, weight = ?, color = ?
                        WHERE patient_id = ? AND owner_id = ?
                    ");
                    $stmt->execute([$animal_name, $species, $breed, $age, $weight, $color, $patient_id, $user_id]);
                    $success_message = "Pet updated successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to update pet.";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $patient_id = (int)$_POST['patient_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ? AND owner_id = ?");
                $stmt->execute([$patient_id, $user_id]);
                $success_message = "Pet removed successfully!";
            } catch(PDOException $e) {
                $error_message = "Failed to remove pet.";
            }
        }
    }
}

// Get user's pets
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE owner_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load pets.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pets - <?php echo $clinic_name; ?></title>
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
    <header class="bg-gradient-to-r from-vet-blue to-vet-dark-blue text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">🐾</span>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo $clinic_name; ?></h1>
                        <p class="text-blue-200 text-sm">Pet Owner Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-blue-200 text-sm">Pet Owner</p>
                    </div>
                    <a href="../index.php?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-3">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-600 hover:text-vet-blue transition-colors">Dashboard</a>
                <a href="pets.php" class="text-vet-blue font-semibold border-b-2 border-vet-blue pb-2">My Pets</a>
                <a href="appointments.php" class="text-gray-600 hover:text-vet-blue transition-colors">Appointments</a>
                <a href="medical_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Medical Records</a>
                <a href="profile.php" class="text-gray-600 hover:text-vet-blue transition-colors">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Pets</h1>
            <button onclick="showAddForm()" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-3 rounded-lg transition-colors">
                + Register New Pet
            </button>
        </div>

        <!-- Add Pet Form (Hidden by default) -->
        <div id="addPetForm" class="hidden bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Register New Pet</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Name *</label>
                        <input type="text" name="animal_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Species *</label>
                        <select name="species" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                            <option value="">Select Species</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Rabbit">Rabbit</option>
                            <option value="Fish">Fish</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Breed</label>
                        <input type="text" name="breed" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age (years)</label>
                        <input type="number" name="age" min="0" max="30" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                        <input type="number" name="weight" min="0" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="text" name="color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                </div>
                <div class="flex space-x-4">
                    <button type="submit" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-2 rounded-lg transition-colors">
                        Register Pet
                    </button>
                    <button type="button" onclick="hideAddForm()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Pets List -->
        <?php if (!empty($pets)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($pets as $pet): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($pet['animal_name']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($pet['species']); ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="showEditForm(<?php echo htmlspecialchars(json_encode($pet)); ?>)" class="text-blue-600 hover:text-blue-800">
                                ✏️
                            </button>
                            <button onclick="deletePet(<?php echo $pet['patient_id']; ?>)" class="text-red-600 hover:text-red-800">
                                🗑️
                            </button>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p><strong>Breed:</strong> <?php echo htmlspecialchars($pet['breed'] ?: 'Not specified'); ?></p>
                        <p><strong>Age:</strong> <?php echo htmlspecialchars($pet['age']); ?> years</p>
                        <p><strong>Weight:</strong> <?php echo htmlspecialchars($pet['weight']); ?> kg</p>
                        <p><strong>Color:</strong> <?php echo htmlspecialchars($pet['color'] ?: 'Not specified'); ?></p>
                        <p><strong>Registered:</strong> <?php echo date('M j, Y', strtotime($pet['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">🐕</div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">No Pets Registered</h2>
                <p class="text-gray-600 mb-6">Start by registering your first pet to get started.</p>
                <button onclick="showAddForm()" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-3 rounded-lg transition-colors">
                    Register Your First Pet
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Pet Form Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Pet</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Name *</label>
                        <input type="text" name="animal_name" id="edit_animal_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Species *</label>
                        <select name="species" id="edit_species" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                            <option value="">Select Species</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                            <option value="Bird">Bird</option>
                            <option value="Rabbit">Rabbit</option>
                            <option value="Fish">Fish</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Breed</label>
                        <input type="text" name="breed" id="edit_breed" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age (years)</label>
                        <input type="number" name="age" id="edit_age" min="0" max="30" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                        <input type="number" name="weight" id="edit_weight" min="0" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="text" name="color" id="edit_color" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                </div>
                <div class="flex space-x-4">
                    <button type="submit" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-2 rounded-lg transition-colors">
                        Update Pet
                    </button>
                    <button type="button" onclick="hideEditModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Confirm Delete</h2>
            <p class="text-gray-600 mb-6">Are you sure you want to remove this pet? This action cannot be undone.</p>
            <form method="POST" class="flex space-x-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="patient_id" id="delete_patient_id">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors">
                    Delete
                </button>
                <button type="button" onclick="hideDeleteModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addPetForm').classList.remove('hidden');
        }

        function hideAddForm() {
            document.getElementById('addPetForm').classList.add('hidden');
        }

        function showEditForm(pet) {
            document.getElementById('edit_patient_id').value = pet.patient_id;
            document.getElementById('edit_animal_name').value = pet.animal_name;
            document.getElementById('edit_species').value = pet.species;
            document.getElementById('edit_breed').value = pet.breed || '';
            document.getElementById('edit_age').value = pet.age;
            document.getElementById('edit_weight').value = pet.weight;
            document.getElementById('edit_color').value = pet.color || '';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function hideEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function deletePet(patientId) {
            document.getElementById('delete_patient_id').value = patientId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideEditModal();
            }
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });
    </script>
</body>
</html> 