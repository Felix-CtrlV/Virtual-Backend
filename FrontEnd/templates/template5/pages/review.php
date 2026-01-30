<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php

include("../../BackEnd/config/dbconfig.php"); 

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 3;


$color_sql = "SELECT primary_color, secondary_color FROM shop_assets WHERE company_id = $company_id LIMIT 1";
$color_result = $conn->query($color_sql);
$primary_color = "#c5a059";   
$secondary_color = "#e0c08d"; 

if ($color_result && $color_result->num_rows > 0) {
    $color_row = $color_result->fetch_assoc();
    $primary_color = $color_row['primary_color'];
    $secondary_color = $color_row['secondary_color'];
}

// 3. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['review_text'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
    
    $cid = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null; 

    if (!$cid) {
        echo "<script>alert('Please log in as a client first to leave a review.');</script>";
    } elseif ($rating <= 0) {
        echo "<script>alert('Please select a star rating.');</script>";
    } elseif (empty($review_text)) {
        echo "<script>alert('Please write your review text.');</script>";
    } else {
        
        $stmt = $conn->prepare("INSERT INTO reviews (supplier_id, customer_id, review, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisi", $supplier_id, $cid, $review_text, $rating);

        if ($stmt->execute()) {
           echo "
    <script>
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      background: 'black',
      color: 'white',
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    })

    Toast.fire({
      icon: 'success',
      title: 'Review Published Successfully'
    }).then(() => {
        window.location.href='?supplier_id=$supplier_id';
    });
    </script>";
            exit();
        } else {
            echo "<script>alert('Error: Could not save review.');</script>";
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

    <?php if (isset($_SESSION['customer_id'])): ?>
        <form method="POST">
            <div class="star-rating-select">
                <?php for($i=5; $i>=1; $i--): ?>
                    <input type="radio" name="rating" id="r<?= $i ?>" value="<?= $i ?>" <?= $i==5 ? 'required' : '' ?>>
                    <label for="r<?= $i ?>" title="<?= $i ?> stars">
                        <svg viewBox="0 0 576 512"><path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"/></svg>
                    </label>
                <?php endfor; ?>
            </div>
            
            <textarea name="review_text" class="luxury-input" rows="5" placeholder="Your experience..." required></textarea>
            
            <button type="submit" class="submit-btn">Publish Review</button>
        </form>
    <?php else: ?>
       <div style="text-align: center; padding: 20px;">
    <p style="color: #e0c08d; font-style: italic; margin-bottom: 20px;">Please join the archives to leave your mark.</p>
    
    <a href="../customerLogin.php?supplier_id=<?= $supplier_id ?>" class="submit-btn" style="text-decoration: none; display: inline-block;">Login to Review</a>
</div>
    <?php endif; ?>
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