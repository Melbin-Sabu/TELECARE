<?php include 'get Pharmacists.php'; ?>

<!-- Pharmacist Details Section -->
<div id="staff-section" class="card bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">Pharmacy Staff</h2>
        <a href="add_pharmacist.php" class="btn-primary flex items-center text-white px-4 py-2 rounded hover:bg-blue-600 transition-all">
            <i class="fas fa-plus mr-2"></i> Add New Pharmacist
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">License No.</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-left">Qualification</th>
                    <th class="px-4 py-3 text-left">Joining Date</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pharmacists)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500">No pharmacists found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pharmacists as $pharmacist): ?>
                    <tr>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($pharmacist['first_name'] . ' ' . $pharmacist['last_name']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($pharmacist['license_number']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($pharmacist['email']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($pharmacist['phone']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($pharmacist['qualification']); ?></td>
                        <td class="px-4 py-3"><?php echo date('M d, Y', strtotime($pharmacist['joining_date'])); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs <?php echo $pharmacist['status'] == 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo htmlspecialchars($pharmacist['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="view_pharmacist.php?id=<?php echo $pharmacist['id']; ?>" class="text-blue-500">View</a> |
                            <a href="edit_pharmacist.php?id=<?php echo $pharmacist['id']; ?>" class="text-green-500">Edit</a> |
                            <a href="delete_pharmacist.php?id=<?php echo $pharmacist['id']; ?>" class="text-red-500" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
