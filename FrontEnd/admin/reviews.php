<?php
$pageTitle = 'Review Management';
$pageSubtitle = 'Monitor and moderate mall & tenant reviews.';
include("partials/nav.php");
?>

<section class="section active">
    <div class="section-header">
        <p></p>
        <div class="section-actions">
            <button class="status-pill btn-ghost btn choose" id="pill">ALL</button>
            <select class="status-select" style="padding: 4px 12px;" id="status-select"
                onchange="updatereview(this.value)">
                <option value="All">All Reviews</option>
                <option value="Excellent">Excellent (5★)</option>
                <option value="Average">Average (3-4★)</option>
                <option value="Poor">Poor (1-2★)</option>
            </select>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Reviewer</th>
                    <th>Target</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $reviewquery = "SELECT 
    r.review_id,
    s.company_name AS company_name,
    c.name AS customer_name,
    r.review,
    r.rating,
    r.created_at
FROM reviews r
LEFT JOIN suppliers s ON r.supplier_id = s.supplier_id
LEFT JOIN customers c ON r.customer_id = c.customer_id
ORDER BY r.created_at DESC;
";
                $result = mysqli_query($conn, $reviewquery);
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <tr data-rating="<?php
                    if ($row['rating'] == 5)
                        echo 'Excellent';
                    elseif ($row['rating'] < 3)
                        echo 'Poor';
                    else
                        echo 'Average';
                    ?>">
                        <td><?= htmlspecialchars($row['customer_name']); ?></td>
                        <td><?= htmlspecialchars($row['company_name']); ?></td>
                        <td><?= $row['rating']; ?><span style="color: #eab308;">★</span></td>
                        <td><?= htmlspecialchars($row['review']); ?></td>
                        <?php
                        if ($row['rating'] < 3) { ?>    
                            <td><span class="card-chip status-pill status-banned">Poor</span></td>
                            <?php
                        } elseif ($row['rating'] == 5) { ?>
                            <td><span class="card-chip status-pill status-active">Excellent</span></td>
                            <?php
                        } else { ?>
                            <td><span class="card-chip status-pill status-pending">Average</span></td>
                            <?php
                        }
                        ?>
                        <td><?= date('M j, Y', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<script src="script.js"></script>

<script>
    function updatereview(value) {
        const rows = document.querySelectorAll("tbody tr");
        const pill = document.getElementById("pill");

        pill.className = "status-pill btn-ghost btn choose";

        if (value === "Excellent") pill.classList.add("status-active");
        if (value === "Average") pill.classList.add("status-pending");
        if (value === "Poor") pill.classList.add("status-banned");

        pill.textContent = value;

        rows.forEach(row => {
            const rowRating = row.dataset.rating;
            row.style.display = (value === "All" || rowRating === value) ? "" : "none";
        });
    }
</script>
</body>

</html>