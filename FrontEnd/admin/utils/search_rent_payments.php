<?php
include("../../../BackEnd/config/dbconfig.php");
$adminid = $_SESSION["adminid"] ?? null;

$search = $_POST['search'] ?? '';
$statusFilter = $_POST['status'] ?? 'all';

$conditions = ["s.status = 'active'", "c.status = 'active'"];
$params = [];
$types = "";

if (!empty($search)) {
    $like = "%$search%";
    $conditions[] = "(c.company_name LIKE ? OR s.name LIKE ?)";
    $params = array_merge($params, [$like, $like]);
    $types .= "ss";
}

$where = implode(" AND ", $conditions);

$sql = "
SELECT 
    s.supplier_id,
    c.*,
    s.name AS supplier_name,
    s.email,
    rp.paid_date AS last_paid_date,
    rp.due_date
FROM suppliers s
INNER JOIN companies c
    ON c.supplier_id = s.supplier_id
LEFT JOIN (
    SELECT rp1.*
    FROM rent_payments rp1
    INNER JOIN (
        SELECT company_id, MAX(paid_date) AS latest_paid
        FROM rent_payments
        GROUP BY company_id
    ) rp2
        ON rp1.company_id = rp2.company_id
       AND rp1.paid_date = rp2.latest_paid
) rp
    ON rp.company_id = c.company_id
WHERE $where
";

if ($statusFilter === 'paid') {
    $sql .= " AND rp.due_date IS NOT NULL AND rp.due_date >= CURRENT_DATE";
} elseif ($statusFilter === 'unpaid') {
    $sql .= " AND (rp.paid_date IS NULL OR rp.due_date < CURRENT_DATE)";
} elseif ($statusFilter === 'overdue') {
    $sql .= " AND rp.due_date IS NOT NULL AND rp.due_date < CURRENT_DATE";
}

$sql .= " ORDER BY (rp.due_date IS NULL) ASC, rp.due_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params) && !empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$count = 0;

while ($row = $result->fetch_assoc()) {
    $count++;
    $dueText = 'N/A';
    $dueClass = 'status-inactive';
    if (!empty($row['due_date'])) {
        $dueDate = new DateTime($row['due_date']);
        $today = new DateTime();
        $interval = $today->diff($dueDate);
        if ($today > $dueDate) {
            $dueText = 'Overdue';
            $dueClass = 'status-banned';
        } elseif ($interval->days === 0) {
            $dueText = 'Due today';
            $dueClass = 'status-pending';
        } elseif ($interval->days <= 7) {
            $dueText = $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' left';
            $dueClass = 'status-pending';
        } else {
            $dueText = $interval->days . ' days left';
            $dueClass = 'status-active';
        }
    } elseif (empty($row['last_paid_date'])) {
        $dueText = 'No payment';
        $dueClass = 'status-inactive';
    }
    ?>
    <tr>
        <td>
            <div style="font-weight:500;"><?= htmlspecialchars($row['company_name'] ?? 'Unknown') ?></div>
            <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($row['supplier_name'] ?? $row['name'] ?? '') ?></div>
        </td>
        <td>$<?= number_format((float)($row['renting_price'] ?? 0), 2) ?></td>
        <td><?= !empty($row['last_paid_date']) ? date('M d, Y', strtotime($row['last_paid_date'])) : 'N/A' ?></td>
        <td><?= !empty($row['due_date']) ? date('M d, Y', strtotime($row['due_date'])) : 'N/A' ?></td>
        <td><span class="status-pill card-chip <?= $dueClass ?>"><?= htmlspecialchars($dueText) ?></span></td>
        <td>
            <a class="button" href="suppliersmanagement.php?adminid=<?= $_SESSION['adminid'] ?? '' ?>&supplierid=<?= $row['supplier_id'] ?>" style="padding:5px 12px;font-size:11px;">View</a>
        </td>
    </tr>
    <?php
}

if ($count === 0) {
    echo "<tr><td colspan='6' style='text-align:center;padding:30px;color:var(--muted);'>No companies found.</td></tr>";
}
