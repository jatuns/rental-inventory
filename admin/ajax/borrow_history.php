<?php
/**
 * AJAX - Get Borrow History for Equipment
 */
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireRole(['admin']);

$equipmentId = intval($_GET['equipment_id'] ?? 0);

if ($equipmentId <= 0) {
    echo '<p class="text-danger">Invalid equipment ID.</p>';
    exit();
}

$conn = getConnection();

// Get equipment name
$stmt = $conn->prepare("SELECT name FROM equipment WHERE id = ?");
$stmt->bind_param("i", $equipmentId);
$stmt->execute();
$equipment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$equipment) {
    echo '<p class="text-danger">Equipment not found.</p>';
    exit();
}

// Get borrow history
$stmt = $conn->prepare("
    SELECT bh.*, u.first_name, u.last_name, u.student_id
    FROM borrow_history bh
    JOIN users u ON bh.user_id = u.id
    WHERE bh.equipment_id = ?
    ORDER BY bh.checkout_date DESC
    LIMIT 20
");
$stmt->bind_param("i", $equipmentId);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<h6><?php echo htmlspecialchars($equipment['name']); ?></h6>

<?php if (empty($history)): ?>
    <p class="text-muted">No borrow history for this equipment.</p>
<?php else: ?>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Student</th>
                <th>Checkout</th>
                <th>Return</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($h['first_name'] . ' ' . $h['last_name']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($h['student_id'] ?? 'N/A'); ?></small>
                    </td>
                    <td><?php echo formatDate($h['checkout_date']); ?></td>
                    <td>
                        <?php if ($h['return_date']): ?>
                            <?php echo formatDate($h['return_date']); ?>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Not Returned</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
