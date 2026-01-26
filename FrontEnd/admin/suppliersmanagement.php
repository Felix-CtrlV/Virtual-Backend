<?php
$pageTitle = 'Company Management';
$pageSubtitle = 'Manage and view company details.';
include("partials/nav.php");

$supplierid = isset($_GET['supplierid']) ? $_GET['supplierid'] : 0;
// We need adminid for the back link
$adminid = $_SESSION['adminid'] ?? 0;

if ($supplierid <= 0) {
    echo '<div class="section-header"><div><p></p></div><div class="section-actions"><a href="viewsuppliers.php?adminid='.urlencode($adminid).'" class="btn btn-ghost">Back to List</a></div></div>';
    echo '<section class="section active"><div class="card"><p style="text-align: center; padding: 30px; color: var(--muted);">No supplier selected. Please select a supplier from the list.</p></div></section>';
    echo '<script src="script.js"></script></body></html>';
    exit;
}

$supplierquery = "SELECT 
    s.*, 
    c.*,
    sa.logo, 
    sa.banner, 
    sa.template_type, 
    rp.paid_date AS contract_start, 
    rp.due_date AS contract_end
FROM suppliers s 
LEFT JOIN companies c 
    ON c.supplier_id = s.supplier_id
LEFT JOIN shop_assets sa 
    ON sa.supplier_id = s.supplier_id 
LEFT JOIN (
    SELECT rp1.* FROM rent_payments rp1 
    INNER JOIN (
        SELECT supplier_id, MAX(paid_date) AS latest_paid
        FROM rent_payments
        GROUP BY supplier_id
    ) rp2 
    ON rp1.supplier_id = rp2.supplier_id 
       AND rp1.paid_date = rp2.latest_paid
) rp 
    ON rp.supplier_id = s.supplier_id
WHERE s.supplier_id = $supplierid;
";
$supplierresult = mysqli_query($conn, $supplierquery);
$supplierrow = mysqli_fetch_assoc($supplierresult);

$companyid = $supplierrow['company_id'];

$reviewquery = "SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total_reviews 
    FROM reviews 
    WHERE supplier_id = $supplierid";
$reviewresult = mysqli_query($conn, $reviewquery);
$reviewrow = mysqli_fetch_assoc($reviewresult);

$banner_string = $supplierrow["banner"];
$banners = explode(",", $banner_string);
$banner_count = count($banners);
$banner1 = $banners[0] ?? '';

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
                    <?php
                    if ($supplierrow['template_type'] == 'video' && $banner1): ?>
                        <video class="company_image" autoplay muted loop playsinline
                            src="../uploads/shops/<?= $supplierid ?>/<?= $banner1 ?>" style="height: 100%;"></video>
                    <?php elseif ($banner1): ?>
                        <img src="../uploads/shops/<?= $supplierid ?>/<?= $banner1 ?>" alt="Hero Banner"
                            class="company_image" style="height: 100%;">
                    <?php else: ?>
                         <div style="height:100%; display:flex; align-items:center; justify-content:center; background:#f1f5f9;">No Banner</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="company_status" style="margin-top: 12px;">
                <div class="card-value"><?= htmlspecialchars($supplierrow['company_name'] ?? 'Unknown Company') ?></div>
                <div class="company_status_right">
                    <span class="card-chip status-pill <?= $statusClass ?>">
                        <?= ucfirst(htmlspecialchars($status)) ?>
                    </span>
                </div>
            </div>
            <p style="font-size: 13px; color: var(--muted); margin-top: 6px;">
                <?= htmlspecialchars($supplierrow['description'] ?? '') ?>
            </p>
        </div>

        <div class="card">
            <div class="card-title">Key Metrics</div>
            <p style="font-size: 13px; margin-top: 6px; margin-bottom: 10px;">
                <strong>Monthly Rent:</strong> $<?= number_format($supplierrow['renting_price'] ?? 0, 2) ?>
            </p>
            <p style="font-size: 13px; margin-bottom: 10px;">
                <strong>Last Payment:</strong>
                <?= !empty($supplierrow['contract_start']) ? date('M d, Y', strtotime($supplierrow['contract_start'])) : 'N/A' ?>
            </p>
            <p style="font-size: 13px; margin-bottom: 10px;">
                <strong>Average Rating:</strong>
                <?= $reviewrow['avg_rating'] ? $reviewrow['avg_rating'] : 'N/A' ?><span style="color: #eab308;">★</span>
            </p>
            <p style="font-size: 13px; margin-bottom: 10px;">
                <strong>Due In:</strong>
                <?php
                if (!empty($supplierrow['contract_end'])) {
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
                <label style="display: block; font-size: 12px; color: var(--muted); margin-bottom: 8px;">Change Status</label>
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
                <strong>Email:</strong> <?= htmlspecialchars($supplierrow['email'] ?? '') ?><br>
                <strong>Phone:</strong> <?= htmlspecialchars($supplierrow['phone'] ?? '') ?><br>
                <strong>Location:</strong> <?= htmlspecialchars($supplierrow['address'] ?? '') ?>
            </p>
        </div>

        <div class="card">
            <div class="card-title">Contract Details</div>
            <p style="font-size: 12px; color: var(--muted); margin-top: 6px;">
                <strong>Contract:</strong>
                <?= !empty($supplierrow['contract_start']) ? date('M d, Y', strtotime($supplierrow['contract_start'])) : 'N/A' ?>
                <strong> - </strong>
                <?= !empty($supplierrow['contract_end']) ? date('M d, Y', strtotime($supplierrow['contract_end'])) : 'N/A' ?>
            </p>
        </div>

        <div class="card">
            <div class="card-title">Tags</div>
            <p style="margin-top: 6px;">
                <?php
                $tags = $supplierrow['tags'] ?? '';
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
    
    <div id="banModal" class="modal-overlay" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center;">
        <div style="background: #1e293b; padding:25px; border-radius:8px; width:400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <h3>Ban Company</h3>
            <input type="hidden" id="banSupplierId">
            <label style="display:block; margin-top:10px; font-size:0.85rem; color: #94a3b8; margin-bottom: 5px;">Reason For Banning</label>
            <textarea id="banReason" rows="3" style="width:100%; resize: none; padding:8px; border:1px solid #334155; border-radius:6px; box-sizing:border-box; background-color: #0f172a; color: white;" placeholder="Violation of terms..."></textarea>
    
            <label style="display:block; margin-top:10px; font-size:0.85rem; color: #94a3b8; margin-bottom: 5px;">Banned Until</label>
            <input type="date" id="banDate" style="width:100%; padding:8px; border:1px solid #334155; border-radius:6px; box-sizing:border-box; background-color: #0f172a; color: white;">
            
            <div style="margin-top:15px; display:flex; justify-content:flex-end; gap:10px;">
                <button onclick="closeBanModal()" class="btn btn-ghost">Cancel</button>
                <button onclick="confirmSupplierBan()" class="btn btn-primary">Confirm</button>
            </div>
        </div>
    </div>
</section>

<script>
    // 1. Handle Status Change
    function updateSupplierStatus(supplierId, newStatus) {
        // If Banned is selected, intercept and show modal
        if (newStatus === 'banned') {
            document.getElementById('banSupplierId').value = supplierId;
            document.getElementById('banReason').value = '';
            document.getElementById('banDate').value = '';
            document.getElementById('banModal').style.display = 'flex';
            return;
        }

        if (!confirm(`Change status to "${newStatus}"?`)) {
            location.reload(); // Reset select if cancelled
            return;
        }

        // Standard Update (Active/Inactive)
        fetch('utils/update_user_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: supplierId, role: 'supplier', status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Status updated!');
                location.reload(); 
            } else {
                alert('Error: ' + data.message);
                location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            alert("Request failed");
        });
    }

    // 2. Ban Workflow
    function closeBanModal() {
        document.getElementById('banModal').style.display = 'none';
        location.reload(); // Reset the select box
    }

    function confirmSupplierBan() {
        const id = document.getElementById('banSupplierId').value;
        const reason = document.getElementById('banReason').value;
        const banned_until = document.getElementById('banDate').value;

        if (!reason || !banned_until) {
            alert("Please provide a reason and date.");
            return;
        }

        fetch('utils/handle_ban.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, role: 'supplier', reason: reason, banned_until: banned_until })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Supplier Banned successfully.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
</script>
<script src="script.js"></script>
</body>
</html>