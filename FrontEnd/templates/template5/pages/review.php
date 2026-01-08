<?php
// 1. Connection & Initial Setup
include("../../BackEnd/config/dbconfig.php"); 

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 3;

// 2. Fetch Colors (Primary & Secondary)
$color_sql = "SELECT primary_color, secondary_color FROM shop_assets WHERE supplier_id = $supplier_id LIMIT 1";
$color_result = $conn->query($color_sql);
$primary_color = "#c5a059";   // Default Gold
$secondary_color = "#e0c08d"; // Default Champagne

if ($color_result && $color_result->num_rows > 0) {
    $color_row = $color_result->fetch_assoc();
    $primary_color = $color_row['primary_color'];
    $secondary_color = $color_row['secondary_color'];
}

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review_text'])) {
    $rating = (int)$_POST['rating'];
    $email  = trim($_POST['email']);
    $review_text = trim($_POST['review_text']);

    if ($rating > 0 && !empty($review_text) && !empty($email)) {
        $cust_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $cust_stmt->bind_param("s", $email);
        $cust_stmt->execute();
        $res = $cust_stmt->get_result();

        if ($res->num_rows > 0) {
            $customer = $res->fetch_assoc();
            $cid = $customer['customer_id'];

            $stmt = $conn->prepare("INSERT INTO reviews (supplier_id, customer_id, review, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iisi", $supplier_id, $cid, $review_text, $rating);

            if ($stmt->execute()) {
                echo "<script>alert('Review published to the archives.'); window.location.href='?supplier_id=$supplier_id';</script>";
            }
        } else {
            echo "<script>alert('Email not found. Please register as a client first.');</script>";
        }
    }
}

// 4. Logic for Stats Dashboard (Fixes the "Undefined" warnings)
$sql_stats = "SELECT rating FROM reviews WHERE supplier_id = $supplier_id";
$result_stats = $conn->query($sql_stats);

$total_reviews = 0;
$sum_ratings = 0;
$star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

if ($result_stats && $result_stats->num_rows > 0) {
    while ($row = $result_stats->fetch_assoc()) {
        $r = (int)$row['rating'];
        if (isset($star_counts[$r])) {
            $star_counts[$r]++;
            $sum_ratings += $r;
            $total_reviews++;
        }
    }
}
$avg_rating = $total_reviews > 0 ? number_format($sum_ratings / $total_reviews, 1) : "0.0";

// 5. Fetch Reviews for the "Archives"
$sql_reviews = "
    SELECT r.*, c.name, c.image 
    FROM reviews r 
    JOIN customers c ON r.customer_id = c.customer_id 
    WHERE r.supplier_id = $supplier_id 
    ORDER BY r.created_at DESC LIMIT 10";
$reviews_res = $conn->query($sql_reviews);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Section</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --gold: <?= $primary_color ?>; 
            --gold-leaf: <?= $secondary_color ?>
          

            
        }
        body{
            background-color: black;
        }
        
        /* Reveal animation styles */
        .reveal-on-scroll { opacity: 0; transform: translateY(20px); transition: 0.8s all ease; }
        .reveal-on-scroll.is-visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>

<div class="page-wrapper">
    <header class="page-header reveal-on-scroll">
        <h1 class="page-title"><?= htmlspecialchars($supplier['tags'] ?? '') ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars($supplier['description'] ?? '') ?></p>
    </header>

    <div class="stats-dashboard reveal-on-scroll">
        <div class="big-score-block">
            <div class="big-score"><?= $avg_rating ?></div>
            <div class="total-count"><?= $total_reviews ?></div>
            <div class="stars-row">
                <?php 
                $full_stars = floor((float)$avg_rating);
                for($i=0; $i<5; $i++) echo $i < $full_stars ? '★' : '☆'; 
                ?>
            </div>
        </div>

        <div class="bars-block">
            <?php foreach ([5, 4, 3, 2, 1] as $star): 
                $count = $star_counts[$star];
                $percent = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
            ?>
                <div class="hud-bar-row">
                    <span class="hud-label"><?= $star ?> Star</span>
                    <div class="hud-track"><div class="hud-fill" style="width: <?= $percent ?>%;"></div></div>
                    <span class="hud-value"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <section class="reviews-feed">
    <h3 class="section-heading">The Archives</h3>
    
    <?php if ($reviews_res && $reviews_res->num_rows > 0): ?>
        <?php while ($row = $reviews_res->fetch_assoc()): 
           
            $initial = strtoupper(substr($row['name'], 0, 1));
        ?>
            <article class="review-card reveal-on-scroll">
                <div class="reviewer-header">
                    <div class="reviewer-initial"><?= $initial ?></div>

                    <div class="reviewer-info">
                        <h4><?= htmlspecialchars($row['name']) ?></h4>
                        <span>EST. <?= strtoupper(date('F Y', strtotime($row['created_at']))) ?></span>
                    </div>
                    <div class="review-stars">
                        <?php for($i=0; $i<$row['rating']; $i++) echo '★'; ?>
                    </div>
                </div>
                <p class="review-body">"<?= htmlspecialchars($row['review']) ?>"</p>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; color: #666; font-style: italic; padding: 40px;">The archives are currently silent.</p>
    <?php endif; ?>
</section>

        <aside class="form-sticky-panel reveal-on-scroll">
            <div class="form-header">Leave Your Mark</div>
            <form method="POST">
               <div class="star-rating-select">
    <input type="radio" name="rating" id="r5" value="5" required>
    <label for="r5" title="5 stars">
        <svg viewBox="0 0 576 512"><path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"/></svg>
    </label>

    <input type="radio" name="rating" id="r4" value="4">
    <label for="r4" title="4 stars">
        <svg viewBox="0 0 576 512"><path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"/></svg>
    </label>

    <input type="radio" name="rating" id="r3" value="3">
    <label for="r3" title="3 stars">
        <svg viewBox="0 0 576 512"><path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"/></svg>
    </label>

    <input type="radio" name="rating" id="r2" value="2">
    <label for="r2" title="2 stars">
        <svg viewBox="0 0 576 512"><path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"/></svg>
    </label>

    <input type="radio" name="rating" id="r1" value="1">
    <label for="r1" title="1 star">
        <svg viewBox="0 0 576 512"><path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"/></svg>
    </label>
</div>
                
               
                <textarea name="review_text" class="luxury-input" rows="5" placeholder="Your experience..." required></textarea>
                
                <button type="submit" class="submit-btn">Publish Review</button>
            </form>
        </aside>
    </div>
</div>

<script>
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('is-visible');
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
</script>
</body>
</html>