<?php
$pageTitle = 'Review Management';
$pageSubtitle = 'Monitor and moderate mall & tenant reviews.';
include("partials/nav.php");
?>

<section class="section active">
    <div class="section-header">
        <p></p>
        <div class="section-actions">
            <button class="btn-ghost btn">Filter</button>
            <button class="btn-primary btn">Export Reviews</button>
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
                    <tr>
                        <td><?= htmlspecialchars($row['customer_name']); ?></td>
                        <td><?= htmlspecialchars($row['company_name']); ?></td>
                        <td>★<?= $row['rating']; ?></td>
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
</body>

</html>