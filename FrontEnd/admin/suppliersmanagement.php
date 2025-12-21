<?php
$pageTitle = 'Supplier Management';
$pageSubtitle = 'Manage and view supplier details.';
include("partials/nav.php");

$supplierid = isset($_GET['supplierid']) ? $_GET['supplierid'] : 0;

if ($supplierid <= 0) {
    echo '<div class="section-header"><div><p></p></div><div class="section-actions"><a href="viewsuppliers.php?adminid=<?= urlencode($adminid) ?>" class="btn btn-ghost">Back to List</a></div></div>';
    echo '<section class="section active"><div class="card"><p style="text-align: center; padding: 30px; color: var(--muted);">No supplier selected. Please select a supplier from the list.</p></div></section>';
    echo '<script src="script.js"></script></body></html>';
    exit;
}

$supplierquery = "SELECT s.*, sa.logo, sa.banner, rp.paid_date AS contract_start, rp.due_date AS contract_end
    FROM suppliers s 
    LEFT JOIN shop_assets sa ON sa.supplier_id = s.supplier_id 
    LEFT JOIN (
        SELECT rp1.* FROM rent_payments rp1 
        INNER JOIN (
            SELECT supplier_id, MAX(paid_date) AS latest_paid
            FROM rent_payments
            GROUP BY supplier_id
        ) rp2 ON rp1.supplier_id = rp2.supplier_id AND rp1.paid_date = rp2.latest_paid 
    ) rp ON rp.supplier_id = s.supplier_id
    WHERE s.supplier_id = $supplierid";
$supplierresult = mysqli_query($conn, $supplierquery);
$supplierrow = mysqli_fetch_assoc($supplierresult);

$reviewquery = "SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total_reviews 
    FROM reviews 
    WHERE supplier_id = $supplierid";
$reviewresult = mysqli_query($conn, $reviewquery);
$reviewrow = mysqli_fetch_assoc($reviewresult);

$status = $supplierrow['status'];
$statusClass = match ($status) {
    'active' => 'status-active',
    'inactive' => 'status-inactive',
    'banned' => 'status-banned',
    default => 'status-inactive'
};
?>

<section class="section active">
    <div class="section-header">
        <div>
            <p>Detailed profile and management for selected supplier.</p>
        </div>
        <div class="section-actions">
            <a href="viewsuppliers.php?adminid=<?= urlencode($adminid) ?>" class="btn btn-ghost">Back to List</a>
        </div>
    </div>

    <div class="grid">
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <div class="company_image_container" style="height: 150px;">
                    <img class="company_image" style="height: 150px;"
                        src="../uploads/shops/<?= $supplierid ?>/<?= $supplierrow['banner'] ?>" alt="Banner">
                </div>
            </div>
            <div class="company_status" style="margin-top: 12px;">
                <div class="card-value"><?= htmlspecialchars($supplierrow['company_name']) ?></div>
                <div class="company_status_right">
                    <span class="card-chip status-pill <?= $statusClass ?>">
                        <?= ucfirst(htmlspecialchars($status)) ?>
                    </span>
                </div>
            </div>
            <p style="font-size: 13px; color: var(--muted); margin-top: 6px;">
                <?= htmlspecialchars($supplierrow['description']) ?>
            </p>
        </div>

        <div class="card">
            <div class="card-title">Key Metrics</div>
            <p style="font-size: 13px; margin-top: 6px; margin-bottom: 10px;">
                <strong>Monthly Rent:</strong> $<?= number_format($supplierrow['renting_price'], 2) ?>
            </p>
            <p style="font-size: 13px; margin-bottom: 10px;">
                <strong>Last Payment:</strong>
                <?= $supplierrow['contract_start'] ? date('M d, Y', strtotime($supplierrow['contract_start'])) : 'N/A' ?>
            </p>
            <p style="font-size: 13px; margin-bottom: 10px;">
                <strong>Average Rating:</strong>
                <?= $reviewrow['avg_rating'] ? $reviewrow['avg_rating'] : 'N/A' ?><span style="color: #eab308;">★</span>
            </p>
            <p style="font-size: 13px; margin-bottom: 10px;">
                <strong>Due In:</strong>
                <?php
                if ($supplierrow['contract_end']) {
                    $dueDate = new DateTime($supplierrow['contract_end']);
                    $today = new DateTime();
                    $interval = $today->diff($dueDate);

                    if ($today > $dueDate) {
                        $dueText = "Overdue";
                        $dueClass = "status-banned";
                    } elseif ($interval->days === 0) {
                        $dueText = "Due today";
                        $dueClass = "status-pending";
                    } else {
                        $dueText = $interval->days . " day" . ($interval->days > 1 ? "s" : "") . " left";
                        $dueClass = "status-active";
                    }

                    echo '<span class="status-pill ' . $dueClass . '">' . $dueText . '</span>';
                } else {
                    echo '<span class="status-pill status-inactive">No payment</span>';
                }
                ?>
            </p>
        </div>

        <div class="card">
            <div class="card-title">Status Management</div>
            <div style="margin-top: 12px;">
                <label style="display: block; font-size: 12px; color: var(--muted); margin-bottom: 8px;">Change
                    Status</label>
                <select id="status-select" class="status-select" style="width: 100%;"
                    onchange="updateSupplierStatus(<?= $supplierid ?>, this.value)">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
                </select>
            </div>
            <div style="margin-top: 12px; padding: 10px; background: rgba(148, 163, 184, 0.08); border-radius: 8px;">
                <div style="font-size: 11px; color: var(--muted); margin-bottom: 4px;">Current Status</div>
                <span class="card-chip status-pill <?= $statusClass ?>" style="font-size: 12px; padding: 4px 10px;">
                    <?= ucfirst(htmlspecialchars($status)) ?>
                </span>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Contact Information</div>
            <p style="font-size: 12px; color: var(--muted); margin-top: 6px;">
                <strong>Email:</strong> <?= htmlspecialchars($supplierrow['email']) ?><br>
                <strong>Phone:</strong> <?= htmlspecialchars($supplierrow['phone']) ?><br>
                <strong>Location:</strong> <?= htmlspecialchars($supplierrow['address']) ?>
            </p>
        </div>

        <div class="card">
            <div class="card-title">Contract Details</div>
            <p style="font-size: 12px; color: var(--muted); margin-top: 6px;">
                <strong>Contract:</strong>
                <?= $supplierrow['contract_start'] ? date('M d, Y', strtotime($supplierrow['contract_start'])) : 'N/A' ?>
                <strong> - </strong>
                <?= $supplierrow['contract_end'] ? date('M d, Y', strtotime($supplierrow['contract_end'])) : 'N/A' ?>
            </p>
        </div>

        <div class="card">
            <div class="card-title">Tags</div>
            <p style="margin-top: 6px;">
                <?php
                $tags = $supplierrow['tags'];
                if (!empty($tags)) {
                    $tagArray = array_map('trim', explode(',', $tags));
                    foreach ($tagArray as $tag) {
                        echo '<span class="badge-soft">' . htmlspecialchars($tag) . '</span>';
                    }
                } else {
                    echo '<span class="badge-soft" style="color: var(--muted);">No tags</span>';
                }
                ?>
            </p>
        </div>
    </div>
</section>

<script>
    function updateSupplierStatus(supplierId, newStatus) {
        if (!confirm(`Are you sure you want to change this supplier's status to "${newStatus}"?`)) {
            location.reload();
            return;
        }

        fetch('utils/update_supplier_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                supplier_id: supplierId,
                status: newStatus,
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update status'));
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                location.reload();
            });
    }
</script>

<script src="script.js"></script>
</body>

</html>